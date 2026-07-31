<?php
// api/php/logout.php - Finaliza la sesión de PHP
require_once __DIR__ . '/../utils/SessionManager.php';

// 1. Limpiar todas las variables de sesión en memoria
$_SESSION = [];

// 2. Borrar la cookie PHPSESSID del navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destruir la sesión en BD (llama al destroy() del DatabaseSessionHandler)
session_destroy();

// 4. CRÍTICO: Crear una sesión nueva vacía con ID regenerado para que el
//    próximo session_start() (en login/save_session) no reutilice el ID antiguo.
//    Esto evita que el navegador presente un PHPSESSID ya destruido.
session_start();
session_regenerate_id(true);
session_destroy();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Sesión cerrada correctamente']);
exit;
