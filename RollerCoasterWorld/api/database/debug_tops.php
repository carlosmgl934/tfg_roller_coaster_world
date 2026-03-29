<?php
require_once __DIR__ . '/db_conexion.php';
$db = new DBConexion();
// check how coaster tops are stored.
echo "--- coaster_ratings ---\n";
$res4 = $db->query("SELECT user_id, count(*) as c FROM coaster_ratings GROUP BY user_id ORDER BY c DESC LIMIT 3");
while ($r = $res4->fetch(PDO::FETCH_ASSOC)) print_r($r);

// Are there any table for user tops? No, user tops are derived from user ratings.
