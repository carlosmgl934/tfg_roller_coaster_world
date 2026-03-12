<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../database/db_conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o no recibidos']);
    exit;
}

// ── Decodificación del token JWT de Firebase ──────────────────────────────────
// Los tokens Firebase de email/password no son validables por oauth2.googleapis.com/tokeninfo.
// Decodificamos el payload del JWT directamente y comprobamos issuer, audience y expiración.
$id_token = $input['id_token'] ?? null;

if (!$id_token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Falta el id_token de Firebase']);
    exit;
}

$parts = explode('.', $id_token);
if (count($parts) !== 3) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token JWT malformado']);
    exit;
}

// Decodificar payload (base64url → JSON)
$payload_b64 = str_pad(strtr($parts[1], '-_', '+/'), strlen($parts[1]) + (4 - strlen($parts[1]) % 4) % 4, '=');
$tokenData = json_decode(base64_decode($payload_b64), true);

if (empty($tokenData)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No se pudo decodificar el token']);
    exit;
}

// ── Validar claims básicos ────────────────────────────────────────────────────
$expected_project = 'tfg-roller-coaster-world-auth';
$iss = $tokenData['iss'] ?? '';
$aud = $tokenData['aud'] ?? '';
$exp = $tokenData['exp'] ?? 0;

if (!str_contains($iss, $expected_project)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token de otro proyecto Firebase']);
    exit;
}
if ($aud !== $expected_project) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Audience del token no coincide']);
    exit;
}
if ($exp < time()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token expirado']);
    exit;
}

// ── Extraer datos del token ───────────────────────────────────────────────────
$firebase_uid = $tokenData['user_id'] ?? $tokenData['sub'] ?? null;
$email = $tokenData['email'] ?? null;
$username = $input['username'] ?? ($email ? explode('@', $email)[0] : 'Usuario_' . substr($firebase_uid, 0, 8));

if (!$firebase_uid || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token sin uid o email']);
    exit;
}

// ── Insertar o ignorar en Supabase ────────────────────────────────────────────
$db = new DBConexion();
$stmt = $db->prepare("
    INSERT INTO users (username, email, firebase_uid, rol)
    VALUES (:username, :email, :firebase_uid, 'user')
    ON CONFLICT (firebase_uid) DO UPDATE SET email = EXCLUDED.email
    RETURNING id
");

$stmt->execute([
    ':username' => $username,
    ':email' => $email,
    ':firebase_uid' => $firebase_uid,
]);

$newId = $stmt->fetchColumn();

if ($newId) {
    echo json_encode([
        'success' => true,
        'message' => 'Usuario guardado en Supabase',
        'user_id' => $newId,
    ]);
}
else {
    echo json_encode([
        'success' => true,
        'message' => 'Usuario ya existía en Supabase (login correcto)',
    ]);
}
?>