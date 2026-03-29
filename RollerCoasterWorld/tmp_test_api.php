<?php
session_start();
$_SESSION['user_id'] = 74;
$_SESSION['firebase_uid'] = 'w9eTeDpPtpYEsTIO1g6dkWJtRRr2';

$_GET['action'] = 'user_tops';
$_GET['filter'] = 'friends';

ob_start();
require_once 'api/php/parks.php';
$output = ob_get_clean();

echo $output;
