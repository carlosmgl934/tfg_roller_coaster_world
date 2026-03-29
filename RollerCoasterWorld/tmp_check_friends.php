<?php
require_once 'api/database/db_conexion.php';
$db = new DBConexion();
$s = $db->query("SELECT * FROM friendship WHERE estado_solicitud = 'ACEPTADA'");
$res = [];
while($r = $s->fetch(PDO::FETCH_ASSOC)) {
    $res[] = $r;
}
echo json_encode($res, JSON_PRETTY_PRINT);
