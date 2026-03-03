<?php
require_once 'structure/header.php';

// Protección: redirige si no hay sesión activa
if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/firebase/auth/login.php');
  exit;
}

