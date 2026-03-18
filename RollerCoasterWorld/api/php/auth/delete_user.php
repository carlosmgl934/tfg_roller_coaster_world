<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../database/db_conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['firebase_uid'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta firebase_uid']);
    exit;
}

$firebase_uid = $input['firebase_uid'];

try {
    $db = new DBConexion();
    $stmt = $db->prepare("DELETE FROM users WHERE firebase_uid = :firebase_uid");
    $stmt->execute([':firebase_uid' => $firebase_uid]);

    $deleted = $stmt->rowCount();
    echo json_encode([
        'success' => true,
        'message' => $deleted > 0 ? 'Usuario eliminado de Supabase' : 'Usuario no encontrado',
        'rows' => $deleted,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error BD: ' . $e->getMessage()]);
}
?>