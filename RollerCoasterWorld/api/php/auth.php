<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Archivo de log
$log = __DIR__ . '/auth_debug.log';
file_put_contents($log, date('Y-m-d H:i:s') . " ── Nueva petición recibida ──\n", FILE_APPEND);

// Raw input
$raw = file_get_contents('php://input');
file_put_contents($log, "RAW INPUT: " . $raw . "\n", FILE_APPEND);

// Decodificar JSON
$input = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
  file_put_contents($log, "JSON error: " . json_last_error_msg() . "\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'JSON inválido']);
  exit;
}
file_put_contents($log, "Input parseado: " . print_r($input, true) . "\n", FILE_APPEND);

// Comprobar id_token
$id_token = $input['id_token'] ?? null;
if (!$id_token) {
  file_put_contents($log, "Falta id_token\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Falta id_token']);
  exit;
}

// Decodificar JWT (solo payload)
$parts = explode('.', $id_token);
if (count($parts) !== 3) {
  file_put_contents($log, "JWT malformado\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Token malformado']);
  exit;
}

$payload_b64 = str_replace(['-', '_'], ['+', '/'], $parts[1]);
$payload_b64 = str_pad($payload_b64, strlen($payload_b64) + (4 - strlen($payload_b64) % 4) % 4, '=', STR_PAD_RIGHT);
$tokenData = json_decode(base64_decode($payload_b64), true);

if (!$tokenData) {
  file_put_contents($log, "No se pudo decodificar payload\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Payload inválido']);
  exit;
}

file_put_contents($log, "Payload decodificado: " . print_r($tokenData, true) . "\n", FILE_APPEND);

// Validaciones básicas
$expected_project = 'tfg-roller-coaster-world-auth';
if (!isset($tokenData['iss']) || strpos($tokenData['iss'], $expected_project) === false) {
  file_put_contents($log, "Issuer inválido\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Token de otro proyecto']);
  exit;
}
if (!isset($tokenData['aud']) || $tokenData['aud'] !== $expected_project) {
  file_put_contents($log, "Audience inválido\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Audience incorrecto']);
  exit;
}
if (!isset($tokenData['exp']) || $tokenData['exp'] < time()) {
  file_put_contents($log, "Token expirado\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Token expirado']);
  exit;
}

// Datos extraídos
$firebase_uid = $tokenData['user_id'] ?? $tokenData['sub'] ?? null;
$email = $tokenData['email'] ?? null;
$username = $input['username'] ?? ($email ? explode('@', $email)[0] : 'Usuario_' . substr($firebase_uid ?? '', 0, 8));

if (!$firebase_uid || !$email) {
  file_put_contents($log, "Faltan uid o email en token\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Faltan datos del token']);
  exit;
}

file_put_contents($log, "Datos para insertar: uid=$firebase_uid, email=$email, username=$username\n", FILE_APPEND);

// ── CONEXIÓN Y QUERY ─────────────────────────────────────────────────────────
file_put_contents($log, "Intentando cargar db_conexion.php...\n", FILE_APPEND);

$conexionPath = __DIR__ . '/../database/db_conexion.php';

if (!file_exists($conexionPath)) {
  file_put_contents($log, "ERROR: db_conexion.php NO existe en: $conexionPath\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Archivo db_conexion.php no encontrado']);
  exit;
}

try {
  require_once $conexionPath;
  file_put_contents($log, "db_conexion.php cargado OK\n", FILE_APPEND);

  $db = new DBConexion();
  file_put_contents($log, "DBConexion instanciada OK\n", FILE_APPEND);

  $stmt = $db->prepare("
      INSERT INTO users (username, email, firebase_uid, rol)
      VALUES (:username, :email, :firebase_uid, 'user')
      ON CONFLICT (firebase_uid) DO UPDATE SET 
          email = EXCLUDED.email,
          username = EXCLUDED.username
      RETURNING id
  ");

  file_put_contents($log, "Query preparada OK\n", FILE_APPEND);

  $exec = $stmt->execute([
    ':username'     => $username,
    ':email'        => $email,
    ':firebase_uid' => $firebase_uid
  ]);

  if (!$exec) {
    $err = $stmt->errorInfo();
    file_put_contents($log, "Error execute: " . print_r($err, true) . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error al ejecutar insert']);
    exit;
  }

  $newId = $stmt->fetchColumn();

  if ($newId) {
    file_put_contents($log, "Insertado nuevo ID: $newId\n", FILE_APPEND);
    echo json_encode([
      'success' => true,
      'message' => 'Usuario creado en Supabase',
      'user_id' => $newId
    ]);
  } else {
    file_put_contents($log, "No insertado (ya existía, actualizado)\n", FILE_APPEND);
    echo json_encode([
      'success' => true,
      'message' => 'Usuario ya existía en Supabase (actualizado)'
    ]);
  }
} catch (Throwable $e) {
  file_put_contents($log, "FATAL ERROR en conexión o query: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
  echo json_encode([
    'success' => false,
    'message' => 'Error interno al conectar o insertar: ' . $e->getMessage()
  ]);
  exit;
}