<?php
require_once __DIR__ . '/utils/SessionManager.php';
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
$router->register('request_cancel', 'requestCancel', 'POST');
$router->register('mark_refunds_notified', 'markRefundsNotified', 'POST');
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

        // Buscar reembolsos no notificados
        $stmtRefunds = $db->prepare("SELECT price FROM pedidos WHERE user_id = :uid AND status = 'cancelado' AND reembolso_notificado = FALSE");
        $stmtRefunds->execute([':uid' => $userId]);
        $unnotified = $stmtRefunds->fetchAll(PDO::FETCH_COLUMN);

        Response::success([
            'data' => $orders,
            'unnotified_refunds' => $unnotified
        ]);
    } catch (Exception $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

/** Solicitar cancelación de un pedido */
function requestCancel() {
    $userId = getUserId();
    if (!$userId) Response::error('No autenticado.', 401);

    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) Response::error('ID inválido.', 400);

    $db = getDb();
    try {
        // Solo puede solicitar si es su pedido, está confirmado y no ha pasado la fecha
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET status = 'solicitada_cancelacion' 
            WHERE id = :id 
              AND user_id = :uid 
              AND status = 'confirmado'
              AND visit_date >= CURRENT_DATE
            RETURNING id
        ");
        $stmt->execute([':id' => $orderId, ':uid' => $userId]);
        
        if ($stmt->fetchColumn()) {
            Response::success(['message' => 'Solicitud enviada correctamente.']);
        } else {
            Response::error('No es posible solicitar la cancelación de este pedido.', 400);
        }
    } catch (Exception $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

/** Marcar reembolsos como notificados */
function markRefundsNotified() {
    $userId = getUserId();
    if (!$userId) Response::error('No autenticado.', 401);

    $db = getDb();
    try {
        $stmt = $db->prepare("UPDATE pedidos SET reembolso_notificado = TRUE WHERE user_id = :uid AND status = 'cancelado' AND reembolso_notificado = FALSE");
        $stmt->execute([':uid' => $userId]);
        Response::success(['message' => 'Notificaciones actualizadas.']);
    } catch (Exception $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}
