<?php
require_once __DIR__ . '/db_conexion.php';
$db = new DBConexion();
echo "--- Estructura friendship ---\n";
$res2 = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='friendship'");
while ($row = $res2->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['column_name']} ({$row['data_type']})\n";
}
