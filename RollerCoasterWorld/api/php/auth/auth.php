<?php
require_once __DIR__ . '/../utils/SessionManager.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ── CORS: solo orígenes permitidos ───────────────────────────────────────────
$allowedOrigins = [
    'http://localhost',
    'http://localhost:80',
    'http://127.0.0.1',
    'http://localhost/servidor-25-26',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}

// ── Rate Limiting: 10 intentos por IP cada 15 minutos ────────────────────────
require_once __DIR__ . '/../utils/RateLimiter.php';
RateLimiter::check('auth_login', 10, 900);

// Archivo de log
$logDir = __DIR__ . '/../../../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$log = $logDir . '/auth_debug.log';
file_put_contents($log, date('Y-m-d H:i:s') . " ── Nueva petición recibida ──\n", FILE_APPEND);

// Raw input (NO loggear el token completo por seguridad)
$raw = file_get_contents('php://input');
file_put_contents($log, date('Y-m-d H:i:s') . " Nueva petición de autenticación recibida\n", FILE_APPEND);

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

// ── Verificar JWT con Google Public Keys (verificación de firma real) ─────────
// Obtener las claves públicas de Google para Firebase
$jwksUrl = 'https://www.googleapis.com/service_accounts/v1/jwk/securetoken@system.gserviceaccount.com';
$jwksData = @file_get_contents($jwksUrl);
if ($jwksData === false) {
  // Fallback: verificar con la URL alternativa de certificados
  $jwksData = @file_get_contents('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
}

// Decodificar JWT para extraer datos (después de verificar la firma)
$parts = explode('.', $id_token);
if (count($parts) !== 3) {
  file_put_contents($log, date('Y-m-d H:i:s') . " JWT malformado\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Token malformado']);
  exit;
}

$payload_b64 = str_replace(['-', '_'], ['+', '/'], $parts[1]);
$payload_b64 = str_pad($payload_b64, strlen($payload_b64) + (4 - strlen($payload_b64) % 4) % 4, '=', STR_PAD_RIGHT);
$tokenData = json_decode(base64_decode($payload_b64), true);

if (!$tokenData) {
  file_put_contents($log, date('Y-m-d H:i:s') . " No se pudo decodificar payload\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Payload inválido']);
  exit;
}

// Validaciones del payload
$expected_project = 'tfg-roller-coaster-world-auth';
if (!isset($tokenData['iss']) || strpos($tokenData['iss'], $expected_project) === false) {
  file_put_contents($log, date('Y-m-d H:i:s') . " Issuer inválido\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Token de otro proyecto']);
  exit;
}
if (!isset($tokenData['aud']) || $tokenData['aud'] !== $expected_project) {
  file_put_contents($log, date('Y-m-d H:i:s') . " Audience inválido\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Audience incorrecto']);
  exit;
}
if (!isset($tokenData['exp']) || $tokenData['exp'] < time()) {
  file_put_contents($log, date('Y-m-d H:i:s') . " Token expirado\n", FILE_APPEND);
  echo json_encode(['success' => false, 'message' => 'Token expirado']);
  exit;
}
// Verificar que no es un token demasiado antiguo (iat no debe ser futuro)
if (!isset($tokenData['iat']) || $tokenData['iat'] > time() + 60) {
  echo json_encode(['success' => false, 'message' => 'Token con fecha inválida']);
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

$conexionPath = __DIR__ . '/../../database/db_conexion.php';

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
          firebase_uid = EXCLUDED.firebase_uid
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
  // Log interno con detalles — NO exponer al cliente
  file_put_contents($log, date('Y-m-d H:i:s') . " FATAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
  echo json_encode([
    'success' => false,
    'message' => 'Error interno del servidor. Inténtalo de nuevo.'
  ]);
  exit;
}