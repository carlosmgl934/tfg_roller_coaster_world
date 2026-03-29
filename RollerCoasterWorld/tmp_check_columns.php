<?php
require_once 'api/database/db_conexion.php';
$db = new DBConexion();
$stmt = $db->query("SELECT * FROM users LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode(array_keys($row), JSON_PRETTY_PRINT);
