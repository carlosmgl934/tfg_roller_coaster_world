<?php
require_once __DIR__ . '/../db_conexion.php';

$db = new DBConexion();

try {
    echo "Starting migration...\n";

    // 1. Add created_at to coasters
    $sqlCoasters = "ALTER TABLE coasters ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $db->query($sqlCoasters);
    echo "Column created_at added to coasters table (if not exists).\n";

    // 2. Add created_at to parks
    $sqlParks = "ALTER TABLE parks ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    $db->query($sqlParks);
    echo "Column created_at added to parks table (if not exists).\n";

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
