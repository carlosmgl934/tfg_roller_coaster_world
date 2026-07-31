<?php
require_once __DIR__ . '/../utils/SessionManager.php';
header('Content-Type: application/json');

// Log para depurar
$logDir = __DIR__ . '/../../../logs';
if (!is_dir($logDir)) {
  mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/session_log.txt';

$rawInput = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - POST recibido: " . $rawInput . "\n", FILE_APPEND);

$input = json_decode($rawInput, true);

if (isset($input['firebase_uid']) && isset($input['email'])) {
  // Seguridad: regenerar el ID de sesión al hacer login para prevenir session fixation.
  // El ID antiguo se elimina de la BD y el navegador recibirá el nuevo Set-Cookie en esta respuesta.
  session_regenerate_id(true);

  $_SESSION['firebase_uid'] = $input['firebase_uid'];
  $_SESSION['user_email'] = $input['email'];

  // Buscar el user_id en la BD usando el firebase_uid
  try {
    require_once __DIR__ . '/../../database/db_conexion.php';
    $db = new DBConexion();
    $stmt = $db->prepare("SELECT id, rol, username, profile_image, email FROM users WHERE firebase_uid = :uid LIMIT 1");
    $stmt->execute([':uid' => $input['firebase_uid']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $_SESSION['user_id'] = (int) $row['id'];
      $_SESSION['user_rol'] = $row['rol'] ?? 'user';
      $_SESSION['username'] = $row['username'] ?? '';
      $_SESSION['profile_image'] = $row['profile_image'] ?? null;
      $_SESSION['user_email'] = $row['email'] ?? $input['email']; // Usa el correo de la bd si existe

      file_put_contents($logFile, date('Y-m-d H:i:s') . " - Sesión guardada: UID=" . $input['firebase_uid'] . " | user_id=" . $row['id'] . "\n", FILE_APPEND);
      // CRÍTICO: forzar escritura del handler ANTES de que termine el script
      session_write_close();
      file_put_contents($logFile, date('Y-m-d H:i:s') . " - session_write_close() ejecutado correctamente\n", FILE_APPEND);
      echo json_encode(['success' => true, 'message' => 'Sesión PHP actualizada', 'user_id' => $row['id']]);
    } else {
      // El usuario aún no existe en BD (registro justo hecho); guardar sin user_id por ahora
      file_put_contents($logFile, date('Y-m-d H:i:s') . " - Usuario no encontrado en BD para UID=" . $input['firebase_uid'] . "\n", FILE_APPEND);
      session_write_close();
      echo json_encode(['success' => true, 'message' => 'Sesión guardada (usuario pendiente de BD)']);
    }
  } catch (Throwable $e) {
    // Si falla la BD, al menos guardamos la sesión Firebase
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error BD: " . $e->getMessage() . "\n", FILE_APPEND);
    session_write_close();
    echo json_encode(['success' => true, 'message' => 'Sesión Firebase guardada (BD no disponible)']);
  }
} else {
  file_put_contents($logFile, date('Y-m-d H:i:s') . " - Faltan datos\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Faltan firebase_uid o email']);
}
?>