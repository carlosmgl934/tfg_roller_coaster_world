<?php

/**
 * Importar datos de RCDB (JSON) a Supabase (PostgreSQL)
 *
 * Uso:  php db_import.php ../scraper/rcdb_complete.json
 *
 * Cambios respecto a versión anterior:
 *  - city vacío ya NO descarta el coaster (se usa 'Desconocido' como fallback)
 *  - park_id se obtiene con SELECT cuando ON CONFLICT no hace INSERT
 *  - Conversión ft→m y mph→kmh si las unidades métricas faltan
 *  - Progress cada 1000 registros para no saturar consola
 */

require_once __DIR__ . '/db_conexion.php';

if ($argc < 2) {
    echo "Uso: php db_import.php <ruta_al_json>\n";
    echo "Ejemplo: php db_import.php ../scraper/rcdb_complete.json\n";
    exit(1);
}

$json_path = $argv[1];
if (!file_exists($json_path)) {
    echo "Archivo no encontrado: $json_path\n";
    exit(1);
}

echo "Leyendo JSON...\n";
$json_content = file_get_contents($json_path);
$data = json_decode($json_content, true);

if ($data === null) {
    echo "Error al parsear el JSON\n";
    exit(1);
}

// Soporte para JSON con clave 'coasters' o array directo
$coasters = $data['coasters'] ?? (is_array($data) ? $data : []);

if (empty($coasters)) {
    echo "No se encontraron coasters en el JSON\n";
    exit(1);
}

$total = count($coasters);
echo "Total de coasters en el JSON: $total\n\n";

$db = new DBConexion();

$inserted = 0;
$updated = 0;
$skipped = 0;
$parks_created = 0;

// ── Prepared statements ────────────────────────────────────────────────────

// Buscar parque por nombre
$park_find = $db->prepare("SELECT id FROM parks WHERE park_name = :park_name LIMIT 1");

// Insertar parque. ON CONFLICT devuelve id SOLO si hace INSERT;
// si ya existe, RETURNING no devuelve nada → usaremos SELECT de fallback.
$park_insert = $db->prepare("
    INSERT INTO parks (
        park_name, park_location, park_country,
        num_coasters, operating_coasters, stars
    ) VALUES (
        :park_name, :park_location, :park_country,
        0, 0, 0
    )
    ON CONFLICT (park_name) DO NOTHING
    RETURNING id
");

// Insertar / actualizar coaster con ON CONFLICT (rcdb_id)
$coaster_upsert = $db->prepare("
    INSERT INTO coasters (
        rcdb_id, rcdb_url, coaster_name, park_id,
        coaster_manufacter, coaster_model, coaster_status,
        imagen_url, height, speed, coaster_length,
        inversions, opening_year, stars
    ) VALUES (
        :rcdb_id, :rcdb_url, :coaster_name, :park_id,
        :manufacter, :model, :status,
        :imagen_url, :height, :speed, :length,
        :inversions, :year, 0
    )
    ON CONFLICT (rcdb_id) DO UPDATE SET
        rcdb_url           = EXCLUDED.rcdb_url,
        coaster_name       = EXCLUDED.coaster_name,
        park_id            = EXCLUDED.park_id,
        coaster_manufacter = EXCLUDED.coaster_manufacter,
        coaster_model      = EXCLUDED.coaster_model,
        coaster_status     = EXCLUDED.coaster_status,
        imagen_url         = EXCLUDED.imagen_url,
        height             = EXCLUDED.height,
        speed              = EXCLUDED.speed,
        coaster_length     = EXCLUDED.coaster_length,
        inversions         = EXCLUDED.inversions,
        opening_year       = EXCLUDED.opening_year
");

// ── Helper: obtener o crear park_id ───────────────────────────────────────
function getOrCreatePark($park_find, $park_insert, &$parks_created, $name, $location, $country): int|false
{
    // 1. Intentar insertar (ON CONFLICT DO NOTHING)
    $park_insert->execute([
        ':park_name' => $name,
        ':park_location' => $location,
        ':park_country' => $country,
    ]);
    $id = $park_insert->fetchColumn();

    if ($id !== false) {
        $parks_created++;
        return (int) $id;
    }

    // 2. Fallback: ya existía → buscarlo por nombre
    $park_find->execute([':park_name' => $name]);
    $row = $park_find->fetch(PDO::FETCH_ASSOC);
    return $row ? (int) $row['id'] : false;
}

// ── Conversión de unidades ─────────────────────────────────────────────────
function toMetric(array $item): array
{
    // Altura
    $height = $item['height_m'] ?? null;
    if ($height === null && isset($item['height_ft'])) {
        $height = round($item['height_ft'] * 0.3048, 1);
    }

    // Velocidad
    $speed = $item['speed_kmh'] ?? null;
    if ($speed === null && isset($item['speed_mph'])) {
        $speed = round($item['speed_mph'] * 1.60934, 1);
    }

    // Longitud
    $length = $item['length_m'] ?? null;
    if ($length === null && isset($item['length_ft'])) {
        $length = round($item['length_ft'] * 0.3048, 1);
    }

    return [$height, $speed, $length];
}

// ── Loop principal ─────────────────────────────────────────────────────────
foreach ($coasters as $index => $item) {

    $rcdb_id = $item['id'] ?? $item['rcdb_id'] ?? null;

    // Sin rcdb_id no podemos hacer ON CONFLICT, saltamos
    if (!$rcdb_id) {
        $skipped++;
        continue;
    }

    // Datos del parque — city vacío ya no descarta el coaster
    $park_name = trim($item['park'] ?? 'Desconocido');
    $park_location = trim($item['city'] ?? '') ?: 'Desconocido';
    $park_country = $item['country'] ?? null;

    $park_id = getOrCreatePark($park_find, $park_insert, $parks_created, $park_name, $park_location, $park_country);

    if ($park_id === false) {
        // Rarísimo, pero si ocurre no podemos insertar el coaster
        $skipped++;
        continue;
    }

    [$height, $speed, $length] = toMetric($item);

    $coaster_upsert->execute([
        ':rcdb_id' => $rcdb_id,
        ':rcdb_url' => $item['rcdb_url'] ?? "https://rcdb.com/{$rcdb_id}.htm",
        ':coaster_name' => $item['name'] ?? 'Sin nombre',
        ':park_id' => $park_id,
        ':manufacter' => $item['make'] ?? null,
        ':model' => $item['model'] ?? null,
        ':status' => $item['status'] ?? null,
        ':imagen_url' => $item['main_image_url'] ?? null,
        ':height' => $height,
        ':speed' => $speed,
        ':length' => $length,
        ':inversions' => $item['inversions'] ?? 0,
        ':year' => $item['year'] ?? null,
    ]);

    $rows = $coaster_upsert->rowCount();
    if ($rows == 1) {
        $inserted++;
    } elseif ($rows == 2) {
        $updated++;   // PostgreSQL rowCount = 2 cuando hace UPDATE
    } else {
        $skipped++;
    }

    $done = $inserted + $updated + $skipped;
    if ($done % 1000 === 0) {
        echo " [{$done}/{$total}] insertadas: $inserted | actualizadas: $updated | saltadas: $skipped\n";
    }
}

echo "\n===============================\n";
echo " Importación completada\n";
echo "===============================\n";
echo " Coasters insertadas:   $inserted\n";
echo " Coasters actualizadas: $updated\n";
echo " Coasters saltadas:     $skipped\n";
echo " Parques nuevos:        $parks_created\n";
echo "===============================\n";
?>