<?php
require_once __DIR__ . '/db_conexion.php';
$db = new DBConexion();

echo "--- Tablas en public ---\n";
$res = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo $row['table_name'] . "\n";
}

echo "\n--- Estructura park_ratings ---\n";
$res2 = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='park_ratings'");
while ($row = $res2->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['column_name']} ({$row['data_type']})\n";
}

echo "\n--- Datos de ejemplo en park_ratings ---\n";
$res3 = $db->query("SELECT * FROM park_ratings LIMIT 3");
while ($row = $res3->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}
