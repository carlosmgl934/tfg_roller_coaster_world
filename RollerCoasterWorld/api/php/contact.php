<?php

require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/RateLimiter.php';
require_once __DIR__ . '/utils/SessionManager.php';

header('Content-Type: application/json');

// Rate Limiting: 5 envíos por IP cada 10 minutos
RateLimiter::check('contact_form', 5, 600);

if (
isset($_POST['name'], $_POST['email'], $_POST['reason'], $_POST['subject'], $_POST['message']) &&
trim($_POST['name']) !== '' &&
trim($_POST['email']) !== '' &&
trim($_POST['reason']) !== '' &&
trim($_POST['subject']) !== '' &&
trim($_POST['message']) !== ''
) {

    if (isset($_SESSION['firebase_uid'])) {
        $user_id = $_SESSION['user_id'] ?? null;
        $user_name = $_SESSION['user_name'] ?? trim($_POST['name']);
        $user_email = $_SESSION['user_email'] ?? trim($_POST['email']);
    }
    else {
        $user_id = null;
        $user_name = trim($_POST['name']);
        $user_email = trim($_POST['email']);
    }

    $reason      = trim($_POST['reason']);
    $subject     = substr(trim($_POST['subject']), 0, 200);
    $message     = trim($_POST['message']);
    $wants_reply = isset($_POST['wants_reply']);

    // Validar longitudes
    if (strlen($user_name) < 2 || strlen($user_name) > 100) {
        echo json_encode(['success' => false, 'error' => 'El nombre debe tener entre 2 y 100 caracteres']);
        exit;
    }
    if (strlen($subject) < 3) {
        echo json_encode(['success' => false, 'error' => 'El asunto debe tener al menos 3 caracteres']);
        exit;
    }

    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'El correo electrónico no es válido']);
        exit;
    }

    $valid_reasons = ['error', 'suggestion', 'report', 'info', 'other'];
    if (!in_array($reason, $valid_reasons, true)) {
        echo json_encode(['success' => false, 'error' => 'Selecciona una razón válida']);
        exit;
    }

    if (strlen($message) < 20) {
        echo json_encode(['success' => false, 'error' => 'El mensaje debe tener al menos 20 caracteres']);
        exit;
    }

    if (strlen($message) > 2000) {
        echo json_encode(['success' => false, 'error' => 'El mensaje no puede superar los 2000 caracteres']);
        exit;
    }

    try {
        $db = new DBConexion();
        $sql = "INSERT INTO contact_messages (user_id, user_name, user_email, reason, subject, user_message, is_read, created_at, wants_reply) VALUES (:user_id, :user_name, :user_email, :reason, :subject, :user_message, :is_read, :created_at, :wants_reply)";
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':user_id', $user_id, $user_id === null ?PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':user_name', $user_name, PDO::PARAM_STR);
        $stmt->bindValue(':user_email', $user_email, PDO::PARAM_STR);
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
        $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindValue(':user_message', $message, PDO::PARAM_STR);
        $stmt->bindValue(':is_read', false, PDO::PARAM_BOOL);
        $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':wants_reply', $wants_reply, PDO::PARAM_BOOL);

        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
        exit;

    }
    catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al enviar el mensaje']);
        exit;
    }

}
else {
    echo json_encode(['success' => false, 'error' => 'Por favor, completa todos los campos']);
    exit;
}
