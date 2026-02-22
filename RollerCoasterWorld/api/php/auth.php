<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log para depurar
file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - POST recibido: " . file_get_contents('php://input') . "\n", FILE_APPEND);

// Ruta corregida al db_conexion.php
require_once __DIR__ . '/../database/db_conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - Datos inválidos o no recibidos\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos o no recibidos']);
    exit;
}

$firebase_uid = $input['firebase_uid'] ?? null;
$email = $input['email'] ?? null;
$username = $input['username'] ?? 'Usuario_' . substr(md5($firebase_uid ?? ''), 0, 8);

file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - Datos: uid=$firebase_uid, email=$email, username=$username\n", FILE_APPEND);

if (!$firebase_uid || !$email) {
    file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - Faltan datos\n", FILE_APPEND);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan firebase_uid o email']);
    exit;
}

$db = new DBConexion();

file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - Conexión creada\n", FILE_APPEND);

$stmt = $db->prepare("
    INSERT INTO users (username, email, firebase_uid, rol)
    VALUES (:username, :email, :firebase_uid, 'user')
    ON CONFLICT (firebase_uid) DO NOTHING
    RETURNING id
");

$result = $stmt->execute([
    ':username'     => $username,
    ':email'        => $email,
    ':firebase_uid' => $firebase_uid
]);

file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - Execute: " . ($result ? 'OK' : 'FAIL') . "\n", FILE_APPEND);

$newId = $stmt->fetchColumn();

if ($newId) {
    file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - Insertado ID: $newId\n", FILE_APPEND);
    echo json_encode([
        'success' => true,
        'message' => 'Usuario guardado en Supabase',
        'user_id' => $newId
    ]);
} else {
    file_put_contents(__DIR__ . '/auth_log.txt', date('Y-m-d H:i:s') . " - No insertado (ya existe o error)\n", FILE_APPEND);
    echo json_encode([
        'success' => false,
        'message' => 'Usuario ya existe en Supabase o error en insert'
    ]);
}
?>