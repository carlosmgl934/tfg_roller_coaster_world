<?php
require_once __DIR__ . '/db_conexion.php';
$db = new DBConexion();
$res = $db->query("SELECT check_clause 
                   FROM information_schema.check_constraints 
                   WHERE constraint_name = 'friendship_estado_solicitud_check'");
$row = $res->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "Check constraint: " . $row['check_clause'] . "\n";
} else {
    echo "Check constraint not found in information_schema.\n";
    // Let's try to get it by looking at raw pg_catalog if available
    $res2 = $db->query("SELECT pg_get_constraintdef(oid) 
                        FROM pg_constraint 
                        WHERE conname = 'friendship_estado_solicitud_check'");
    $row2 = $res2->fetch(PDO::FETCH_ASSOC);
    if ($row2) {
        echo "PG constraint def: " . $row2['pg_get_constraintdef'] . "\n";
    }
}
