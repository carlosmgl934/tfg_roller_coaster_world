<?php
// Configuración de cookies de sesión segura
session_set_cookie_params([
    'lifetime' => 86400, // 24 horas
    'path' => '/',
    'domain' => '', // Dominio actual
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // Solo HTTPS si está disponible
    'httponly' => true,  // No accesible desde JavaScript (protección XSS)
    'samesite' => 'Lax' // Protección CSRF básica a nivel de cookie
]);

session_start();

// Generar token CSRF al iniciar sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validar token CSRF para todas las peticiones POST/PUT/DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    
    // Archivos excluidos de la validación CSRF
    $excluded = [
        'stripe_webhook.php',
        'auth.php',
        'save_session.php'
    ];
    
    $currentFile = basename($_SERVER['SCRIPT_FILENAME']);
    
    if (!in_array($currentFile, $excluded, true)) {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? '';
        
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido o expirado.']);
            exit;
        }
    }
}
