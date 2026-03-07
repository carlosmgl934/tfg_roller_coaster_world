<?php
require_once __DIR__ . '/api/php/database/db_conexion.php';
try {
    $db = new DBConexion();
    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "TABLES:\n" . implode("\n", $tables);
} catch (Exception $e) {
    echo $e->getMessage();
}
