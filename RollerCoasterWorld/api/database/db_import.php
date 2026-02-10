<?php
/**
 * Importar JSON del scrapper a la base de datos
 * Se ejecuta UNA sola vez para la carga inicial
 * Uso: php db_import.php ruta/al/archivo.json
 */

require_once __DIR__ . '/db_conexion.php';

// ============================================================
// Obtener ruta del JSON
// ============================================================
if ($argc < 2) {
    echo "Uso: php db_import.php <ruta_al_json>\n";
    echo "Ejemplo: php db_import.php ../scrapper/rcdb_complete.json\n";
    exit(1);
}

$json_path = $argv[1];
if (!file_exists($json_path)) {
    echo "❌ Archivo no encontrado: $json_path\n";
    exit(1);
}

// ============================================================
// Leer JSON
// ============================================================
$json_content = file_get_contents($json_path);
$data = json_decode($json_content, true);

if ($data === null) {
    echo "❌ Error al parsear el JSON\n";
    exit(1);
}

// Si el JSON tiene clave 'coasters', usar esa; si no, es un array directo
$coasters = isset($data['coasters']) ? $data['coasters'] : $data;
echo "📊 Coasters en el JSON: " . count($coasters) . "\n";

// ============================================================
// Conectar a BD
// ============================================================
$db = new DBConexion();
$conn = $db->getConexion();

$inserted = 0;
$skipped = 0;
$parks_created = 0;

foreach ($coasters as $c) {
    $rcdb_id = $c['id'] ?? $c['rcdb_id'] ?? null;
    if (!$rcdb_id) {
        $skipped++;
        continue;
    }

    // Comprobar si ya existe
    $stmt = $conn->prepare("SELECT id FROM coasters WHERE rcdb_id = ?");
    $stmt->execute([$rcdb_id]);
    if ($stmt->fetch()) {
        $skipped++;
        continue;
    }

    // ---- PARQUE: buscar o crear ----
    $park_name = $c['park'] ?? 'Desconocido';
    $stmt = $conn->prepare("SELECT id FROM parks WHERE park_name = ?");
    $stmt->execute([$park_name]);
    $park_row = $stmt->fetch();

    if ($park_row) {
        $park_id = $park_row['id'];
    } else {
        $city = $c['city'] ?? '';
        $country = $c['country'] ?? '';
        $stmt = $conn->prepare("INSERT INTO parks (park_name, park_location, park_country) VALUES (?, ?, ?)");
        $stmt->execute([$park_name, $city, $country]);
        $park_id = $conn->lastInsertId();
        $parks_created++;
    }

    // ---- Calcular valores en métrico ----
    $height = $c['height_m'] ?? null;
    if (!$height && isset($c['height_ft'])) {
        $height = round($c['height_ft'] * 0.3048, 1);
    }

    $speed = $c['speed_kmh'] ?? null;
    if (!$speed && isset($c['speed_mph'])) {
        $speed = round($c['speed_mph'] * 1.60934, 1);
    }

    $length = $c['length_m'] ?? null;
    if (!$length && isset($c['length_ft'])) {
        $length = round($c['length_ft'] * 0.3048, 1);
    }

    // ---- INSERTAR COASTER ----
    $stmt = $conn->prepare("
        INSERT INTO coasters (rcdb_id, rcdb_url, coaster_name, park_id,
            coaster_manufacter, coaster_model, coaster_status, imagen_url,
            height, speed, coaster_length, inversions, opening_year)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $rcdb_id,
        $c['rcdb_url'] ?? "https://rcdb.com/{$rcdb_id}.htm",
        $c['name'] ?? 'Sin nombre',
        $park_id,
        $c['make'] ?? null,
        $c['model'] ?? null,
        $c['status'] ?? null,
        $c['main_image_url'] ?? null,
        $height,
        $speed,
        $length,
        $c['inversions'] ?? 0,
        $c['year'] ?? null,
    ]);

    $inserted++;

    if ($inserted % 500 == 0) {
        echo "   📥 $inserted insertadas...\n";
    }
}

echo "\n✅ Importación completada:\n";
echo "   Coasters insertadas: $inserted\n";
echo "   Coasters saltadas (duplicadas): $skipped\n";
echo "   Parques nuevos creados: $parks_created\n";

?>