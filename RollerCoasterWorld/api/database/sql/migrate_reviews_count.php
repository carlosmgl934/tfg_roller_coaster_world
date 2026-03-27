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
    
    // 1. Añadir columnas si no existen
    $pdo->exec("ALTER TABLE parks ADD COLUMN IF NOT EXISTS reviews_count INT DEFAULT 0");
    $pdo->exec("ALTER TABLE coasters ADD COLUMN IF NOT EXISTS reviews_count INT DEFAULT 0");
    
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
