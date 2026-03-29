<?php
require_once 'api/database/db_conexion.php';
$db = new DBConexion();
$s = $db->query('SELECT user_id, park_id, rank_position FROM user_park_credits');
$res = [];
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    $res[] = $r;
}
echo json_encode($res, JSON_PRETTY_PRINT);
