<?php
session_start();
$_SESSION['firebase_uid'] = 'debug_user';
$_SESSION['user_rol'] = 'admin';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "Testing admin_coasters.php filterCoasters...\n";
$_GET['action'] = 'filterCoasters';
$_GET['page'] = 1;
$_GET['search'] = 'Dragon';
require_once 'C:\xampp\htdocs\tfg\tfg_roller_coaster_world\RollerCoasterWorld\api\php\admin\admin_coasters.php';
