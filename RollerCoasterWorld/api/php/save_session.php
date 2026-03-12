<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../database/db_conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['firebase_uid']) && isset($input['email'])) {
  $_SESSION['firebase_uid'] = $input['firebase_uid'];
  $_SESSION['user_email'] = $input['email'];

  // Buscar el user_id en la BD
  $db = new DBConexion();
  $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :firebase_uid");
  $stmt->bindValue(':firebase_uid', $input['firebase_uid']);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $_SESSION['user_id'] = $user['id'];
  }

  echo json_encode(['success' => true, 'message' => 'Sesión PHP actualizada']);
}
else {
  echo json_encode(['success' => false, 'message' => 'Faltan firebase_uid o email']);
}
?>