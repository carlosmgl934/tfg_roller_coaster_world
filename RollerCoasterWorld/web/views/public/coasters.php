<?php
require_once __DIR__ . '/../partials/header.php';

// Protección: redirige si no hay sesión activa
if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

