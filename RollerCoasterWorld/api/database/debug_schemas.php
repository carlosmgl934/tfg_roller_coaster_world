<?php
require_once __DIR__ . '/db_conexion.php';
$db = new DBConexion();
$res = $db->query("SELECT table_schema, column_name, data_type FROM information_schema.columns WHERE table_name='users' ORDER BY table_schema");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['table_schema']}.users.{$row['column_name']} ({$row['data_type']})\n";
}
$res = $db->query("SELECT table_schema, column_name, data_type FROM information_schema.columns WHERE table_name='friendship' ORDER BY table_schema");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['table_schema']}.friendship.{$row['column_name']} ({$row['data_type']})\n";
}
