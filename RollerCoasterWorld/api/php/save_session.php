<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['firebase_uid']) && isset($input['email'])) {
  $_SESSION['firebase_uid'] = $input['firebase_uid'];
  $_SESSION['user_email'] = $input['email'];

  // Log para depurar (opcional)
  file_put_contents(__DIR__ . '/../logs/session_log.txt', date('Y-m-d H:i:s') . " - Sesión guardada: UID=" . $input['firebase_uid'] . ", Email=" . $input['email'] . "\n", FILE_APPEND);

  echo json_encode(['success' => true, 'message' => 'Sesión PHP actualizada']);
} else {
  echo json_encode(['success' => false, 'message' => 'Faltan firebase_uid o email']);
}
?>