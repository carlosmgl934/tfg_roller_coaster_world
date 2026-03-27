<?php
require_once __DIR__ . '/../utils/StatsHelper.php';
require_once __DIR__ . '/../../database/db_conexion.php';

try {
    $db = new DBConexion();
    
    // 1. Actualizar todos los parques
    echo "Updating all parks...\n";
    $parks = $db->query("SELECT id FROM parks")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($parks as $id) {
        StatsHelper::updateParkStats((int)$id);
    }
    
    // 2. Actualizar todas las coasters
    echo "Updating all coasters...\n";
    $coasters = $db->query("SELECT id FROM coasters")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($coasters as $id) {
        StatsHelper::updateCoasterStats((int)$id);
    }
    
    echo "Initial sync complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
