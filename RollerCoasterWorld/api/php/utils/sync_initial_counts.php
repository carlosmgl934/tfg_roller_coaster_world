<?php
/**
 * sync_initial_counts.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Script de mantenimiento: recalcula las estadísticas de TODOS los parques
 * y coasters usando StatsHelper (valoración media, número de coasters, etc.).
 *
 * Es la versión completa de bulk_update: usa la lógica de StatsHelper en lugar
 * de SQLs simples, así que actualiza también stars y operating_coasters.
 *
 * Cuándo ejecutarlo: tras importar datos nuevos, migraciones o cuando los
 * contadores estén muy desincronizados.
 *
 * Uso (terminal desde la raíz del proyecto):
 *   php api/php/utils/sync_initial_counts.php
 * ─────────────────────────────────────────────────────────────────────────────
 */
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
