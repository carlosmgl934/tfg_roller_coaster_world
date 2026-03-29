<?php
require_once __DIR__ . '/db_conexion.php';
$db = new DBConexion();
// dump user_park_credits
$res = $db->query("SELECT * FROM user_park_credits LIMIT 3");
while($r = $res->fetch(PDO::FETCH_ASSOC)) print_r($r);

// dump park_ratings
$res2 = $db->query("SELECT * FROM park_ratings WHERE user_id = 74 ORDER BY note DESC LIMIT 4");
while($r = $res2->fetch(PDO::FETCH_ASSOC)) print_r($r);
