<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/db_conexion.php';

$username = $_GET['username'] ?? ($_POST['username'] ?? '');
$username = trim($username);

if (empty($username)) {
    echo json_encode(['available' => false, 'error' => 'Nombre de usuario vacío']);
    exit;
}

try {
    $db = new DBConexion();
    $stmt = $db->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo json_encode(['available' => false, 'error' => 'Este nombre de usuario ya está en uso']);
    } else {
        echo json_encode(['available' => true]);
    }
} catch (Throwable $e) {
    echo json_encode(['available' => false, 'error' => 'Error de base de datos']);
}
