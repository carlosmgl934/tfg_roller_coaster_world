<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

// Opcional: verificación extra con Firebase (más segura, requiere Firebase PHP SDK)