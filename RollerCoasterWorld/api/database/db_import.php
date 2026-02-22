<?php

/**
 * Importar datos de RCDB (JSON) a Supabase (PostgreSQL)
 * Versión final adaptada: accede a $data['coasters'], valida campos obligatorios,
 * salta entradas inválidas y usa ON CONFLICT para evitar duplicados.
 */

require_once __DIR__ . '/db_conexion.php';

if ($argc < 2) {
    echo "Uso: php db_import.php <ruta_al_json>\n";
    echo "Ejemplo: php db_import.php ../scrapper/rcdb_complete.json\n";
    exit(1);
}

$json_path = $argv[1];
if (!file_exists($json_path)) {
    echo "Archivo no encontrado: $json_path\n";
    exit(1);
}

// Leer y parsear JSON
$json_content = file_get_contents($json_path);
$data = json_decode($json_content, true);

if ($data === null) {
    echo "Error al parsear el JSON\n";
    exit(1);
}

// Acceder al array real de coasters (está dentro de la clave "coasters")
$coasters = $data['coasters'] ?? [];

if (empty($coasters) || !is_array($coasters)) {
    echo "No se encontró un array válido en 'coasters' dentro del JSON\n";
    exit(1);
}

$db = new DBConexion();

$inserted = 0;
$updated = 0;
$skipped = 0;
$parks_processed = 0;

$total = count($coasters);
echo "Procesando $total coasters...\n";

// Preparar statements
$park_stmt = $db->prepare("
    INSERT INTO parks (
        park_name, park_location, park_country, imagen_url,
        num_coasters, operating_coasters, opening_year,
        precio_entrada, stars, latitude, longitude
    ) VALUES (
        :park_name, :park_location, :park_country, :imagen_url,
        :num_coasters, :operating_coasters, :opening_year,
        :precio_entrada, :stars, :latitude, :longitude
    )
    ON CONFLICT (park_name) DO UPDATE SET
        park_location = EXCLUDED.park_location,
        park_country = EXCLUDED.park_country,
        imagen_url = EXCLUDED.imagen_url,
        num_coasters = EXCLUDED.num_coasters,
        operating_coasters = EXCLUDED.operating_coasters,
        opening_year = EXCLUDED.opening_year,
        precio_entrada = EXCLUDED.precio_entrada,
        stars = EXCLUDED.stars,
        latitude = EXCLUDED.latitude,
        longitude = EXCLUDED.longitude
    RETURNING id
");

$coaster_stmt = $db->prepare("
    INSERT INTO coasters (
        rcdb_id, rcdb_url, coaster_name, park_id,
        coaster_manufacter, coaster_model, coaster_status,
        imagen_url, height, speed, coaster_length,
        inversions, opening_year, stars
    ) VALUES (
        :rcdb_id, :rcdb_url, :coaster_name, :park_id,
        :coaster_manufacter, :coaster_model, :coaster_status,
        :imagen_url, :height, :speed, :coaster_length,
        :inversions, :opening_year, :stars
    )
    ON CONFLICT (rcdb_id) DO UPDATE SET
        rcdb_url = EXCLUDED.rcdb_url,
        coaster_name = EXCLUDED.coaster_name,
        park_id = EXCLUDED.park_id,
        coaster_manufacter = EXCLUDED.coaster_manufacter,
        coaster_model = EXCLUDED.coaster_model,
        coaster_status = EXCLUDED.coaster_status,
        imagen_url = EXCLUDED.imagen_url,
        height = EXCLUDED.height,
        speed = EXCLUDED.speed,
        coaster_length = EXCLUDED.coaster_length,
        inversions = EXCLUDED.inversions,
        opening_year = EXCLUDED.opening_year,
        stars = EXCLUDED.stars
");

foreach ($coasters as $index => $item) {
    // Normalizar y validar campos obligatorios
    $park_name     = trim($item['park'] ?? '');
    $park_location = trim($item['city'] ?? '');   // 'city' en tu JSON

    if (empty($park_name) || empty($park_location)) {
        $msg = "Saltando entrada inválida (índice $index) → ";
        $msg .= empty($park_name) ? "park vacío " : "";
        $msg .= empty($park_location) ? "city/park_location vacío " : "";
        $msg .= "(rcdb_id: " . ($item['id'] ?? 'sin id') . ")\n";
        echo $msg;
        $skipped++;
        continue;
    }

    // Insertar / actualizar parque
    $park_stmt->execute([
        ':park_name'          => $park_name,
        ':park_location'      => $park_location,
        ':park_country'       => $item['country'] ?? null,
        ':imagen_url'         => $item['main_image_url'] ?? null,
        ':num_coasters'       => 0,  // no lo tienes en JSON, ponemos 0 por defecto
        ':operating_coasters' => 0,
        ':opening_year'       => $item['year'] ?? null,
        ':precio_entrada'     => null,
        ':stars'              => 0,
        ':latitude'           => null,
        ':longitude'          => null,
    ]);

    $park_id = $park_stmt->fetchColumn();
    if ($park_id) {
        $parks_processed++;
    }

    // Insertar / actualizar coaster
    $coaster_stmt->execute([
        ':rcdb_id'             => $item['id'] ?? null,
        ':rcdb_url'            => $item['rcdb_url'] ?? null,
        ':coaster_name'        => $item['name'] ?? null,
        ':park_id'             => $park_id,
        ':coaster_manufacter'  => $item['make'] ?? null,
        ':coaster_model'       => $item['model'] ?? null,
        ':coaster_status'      => $item['status'] ?? null,
        ':imagen_url'          => $item['main_image_url'] ?? null,
        ':height'              => $item['height_m'] ?? null,
        ':speed'               => $item['speed_kmh'] ?? null,
        ':coaster_length'      => $item['length_m'] ?? null,
        ':inversions'          => $item['inversions'] ?? 0,
        ':opening_year'        => $item['year'] ?? null,
        ':stars'               => 0,
    ]);

    $rowCount = $coaster_stmt->rowCount();
    if ($rowCount > 0) {
        $inserted++;
    } else {
        $skipped++;
    }

    $total_processed = $inserted + $skipped;
    if ($total_processed % 500 == 0) {
        echo " $total_processed procesadas ($inserted insertadas/actualizadas, $skipped saltadas)...\n";
    }
}

echo "\nImportación completada:\n";
echo " - Coasters procesadas exitosamente: $inserted\n";
echo " - Entradas saltadas (inválidas): $skipped\n";
echo " - Parques procesados (insertados/actualizados): $parks_processed\n";
echo "¡Proceso finalizado!\n";
?>