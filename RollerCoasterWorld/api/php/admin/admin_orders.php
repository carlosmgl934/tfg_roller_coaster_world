<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/ApiRouter.php';

header('Content-Type: application/json');

$db = null;
function getDb() {
    global $db;
    if ($db === null) $db = new DBConexion();
    return $db;
}

function requireAdmin() {
    if (!isset($_SESSION['firebase_uid'])) Response::error('No autenticado.', 401);
    $rol = $_SESSION['user_rol'] ?? $_SESSION['rol'] ?? '';
    if ($rol !== 'admin') Response::error('Acceso denegado.', 403);
}

$router = new ApiRouter('list');
$router->register('list',    'listAllOrders');
$router->register('confirm', 'confirmOrder', 'POST');
$router->register('cancel',  'cancelOrder',  'POST');
$router->register('pending_count', 'getPendingCount');
$router->dispatch();

// ──────────────────────────────────────────────────────────

/** Listar todos los pedidos con filtros */
function listAllOrders() {
    requireAdmin();
    $db = getDb();

    $where  = ['1=1'];
    $bind   = [];

    if (!empty($_GET['status'])) {
        $where[] = 'p.status = :status';
        $bind[':status'] = $_GET['status'];
    }
    if (!empty($_GET['park_id'])) {
        $where[] = 'p.park_id = :park_id';
        $bind[':park_id'] = (int)$_GET['park_id'];
    }
    if (!empty($_GET['visit_date'])) {
        $where[] = 'p.visit_date = :visit_date';
        $bind[':visit_date'] = $_GET['visit_date'];
    }

    $whereClause = implode(' AND ', $where);

    try {
        $stmt = $db->prepare("
            SELECT p.id, p.ticket_type, p.visit_date, p.quantity, p.unit_price, p.price,
                   p.status, p.created_at,
                   u.username, u.email,
                   pk.park_name, pk.park_country, pk.id AS park_id
            FROM pedidos p
            JOIN users  u  ON p.user_id  = u.id
            JOIN parks  pk ON p.park_id  = pk.id
            WHERE $whereClause
            ORDER BY p.created_at DESC
            LIMIT 500
        ");
        foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['data' => $orders]);
    } catch (Exception $e) {
        Response::error('Error: ' . $e->getMessage(), 500);
    }
}

/** Confirmar pedido */
function confirmOrder() {
    requireAdmin();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) Response::error('ID inválido.', 400);

    $db = getDb();
    try {
        $stmt = $db->prepare("UPDATE pedidos SET status = 'confirmado' WHERE id = :id AND status = 'pendiente' RETURNING id");
        $stmt->execute([':id' => $orderId]);
        if ($stmt->fetchColumn()) {
            Response::success(['message' => 'Pedido confirmado.']);
        } else {
            Response::error('Pedido no encontrado o ya procesado.', 404);
        }
    } catch (Exception $e) {
        Response::error('Error: ' . $e->getMessage(), 500);
    }
}

/** Cancelar pedido */
function cancelOrder() {
    requireAdmin();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) Response::error('ID inválido.', 400);

    $db = getDb();
    try {
        $stmt = $db->prepare("UPDATE pedidos SET status = 'cancelado' WHERE id = :id AND status = 'pendiente' RETURNING id");
        $stmt->execute([':id' => $orderId]);
        if ($stmt->fetchColumn()) {
            Response::success(['message' => 'Pedido cancelado.']);
        } else {
            Response::error('Pedido no encontrado o ya procesado.', 404);
        }
    } catch (Exception $e) {
        Response::error('Error: ' . $e->getMessage(), 500);
    }
}

/** Contar pedidos pendientes (para badge en admin) */
function getPendingCount() {
    requireAdmin();
    $db = getDb();
    $count = $db->query("SELECT COUNT(*) FROM pedidos WHERE status = 'pendiente'")->fetchColumn();
    Response::success(['count' => (int)$count]);
}
