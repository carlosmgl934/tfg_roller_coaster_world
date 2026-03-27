<?php
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
