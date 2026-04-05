<?php
/**
 * bulk_update.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Script de mantenimiento: actualiza el contador reviews_count de TODOS
 * los parques y coasters con una única SQL masiva (sin pasar por StatsHelper).
 *
 * Cuándo ejecutarlo: después de importaciones masivas de ratings, o para
 * reparar contadores desincronizados.
 *
 * Uso (terminal desde la raíz del proyecto):
 *   php api/php/utils/bulk_update.php
 * ─────────────────────────────────────────────────────────────────────────────
 */
require_once __DIR__ . '/../../database/db_conexion.php';

try {
    $db = new DBConexion();
    
    echo "Updating all parks with bulk SQL...\n";
    $db->query("UPDATE parks p SET reviews_count = (SELECT COUNT(*) FROM park_ratings pr WHERE pr.park_id = p.id)");
    echo "Parks updated.\n";
    
    echo "Updating all coasters with bulk SQL...\n";
    $db->query("UPDATE coasters c SET reviews_count = (SELECT COUNT(*) FROM coaster_ratings cr WHERE cr.coaster_id = c.id)");
    echo "Coasters updated.\n";
    
    echo "Bulk sync complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
