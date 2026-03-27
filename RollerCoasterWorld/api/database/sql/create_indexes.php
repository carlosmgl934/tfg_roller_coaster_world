<?php
require_once __DIR__ . '/../db_conexion.php';

try {
    function getPdo() {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'] ?? '5432';
        $dbname = $_ENV['DB_NAME'] ?? 'postgres';
        $user = $_ENV['DB_USER'] ?? 'postgres';
        $password = $_ENV['DB_PASS'];
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        return new PDO($dsn, $user, $password);
    }

    $pdo = getPdo();
    
    // Crear índice para park_id en coasters
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_coasters_park_id ON coasters(park_id)");
    
    // Crear índice para coaster_id en coaster_ratings
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_coaster_ratings_coaster_id ON coaster_ratings(coaster_id)");
    
    // Crear índice para park_id en park_ratings
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_park_ratings_park_id ON park_ratings(park_id)");
    
    echo "Indexes created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
