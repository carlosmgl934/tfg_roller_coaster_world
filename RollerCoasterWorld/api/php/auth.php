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

// ── Verificación del token JWT de Firebase ────────────────────────────────────
$id_token = $input['id_token'] ?? null;

if (!$id_token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Falta el id_token de Firebase']);
    exit;
}

// Llamar a la API pública de Google para verificar el token
$verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$response = @file_get_contents($verify_url);

if ($response === false) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No se pudo verificar el token con Google']);
    exit;
}

$tokenData = json_decode($response, true);

// Comprobar que el token pertenece a nuestro proyecto Firebase
$expected_audience = 'tfg-roller-coaster-world-auth';   // ← tu projectId de Firebase
$aud = $tokenData['aud'] ?? $tokenData['azp'] ?? '';

if (empty($tokenData) || !str_contains($aud, $expected_audience)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Token inválido o no pertenece a este proyecto']);
    exit;
}

// ── Extraer datos del token (ya verificados por Google) ───────────────────────
$firebase_uid = $tokenData['sub'];           // uid real — no viene del cliente
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
    ON CONFLICT (firebase_uid) DO NOTHING
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
} else {
    // Ya existía — igual es un éxito (ON CONFLICT DO NOTHING)
    echo json_encode([
        'success' => true,
        'message' => 'Usuario ya existía en Supabase (login correcto)',
    ]);
}
?>