<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/ApiRouter.php';

header('Content-Type: application/json');

$db = null;
function getDb() {
    global $db;
    if ($db === null) $db = new DBConexion();
    return $db;
}

function getUserId(): ?int {
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($_SESSION['firebase_uid'])) {
        $db = getDb();
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) { $_SESSION['user_id'] = (int)$row['id']; return (int)$row['id']; }
    }
    return null;
}

$router = new ApiRouter('my_orders');
$router->register('my_orders', 'getMyOrders');
$router->dispatch();

// ──────────────────────────────────────────────────────────

/** Pedidos del usuario actual */
function getMyOrders() {
    $userId = getUserId();
    if (!$userId) Response::error('No autenticado.', 401);

    $db = getDb();
    try {
        $stmt = $db->prepare("
            SELECT p.id, p.ticket_type, p.visit_date, p.quantity, p.unit_price, p.price,
                   p.status, p.created_at,
                   pk.park_name, pk.park_country, pk.imagen_url, pk.id AS park_id
            FROM pedidos p
            JOIN parks pk ON p.park_id = pk.id
            WHERE p.user_id = :uid
            ORDER BY p.visit_date DESC, p.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['data' => $orders]);
    } catch (Exception $e) {
        Response::error('Error al obtener pedidos: ' . $e->getMessage(), 500);
    }
}
