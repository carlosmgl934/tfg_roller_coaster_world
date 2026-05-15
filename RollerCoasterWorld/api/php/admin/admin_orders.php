<?php
require_once __DIR__ . '/../utils/SessionManager.php';
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
$router->register('cancellations_count', 'getPendingCancellations');
$router->dispatch();

// ──────────────────────────────────────────────────────────

/** Listar todos los pedidos con filtros */
function listAllOrders() {
    requireAdmin();
    runMonthlyCleanup();
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
                   p.addon_pase_rapido, p.addon_photopass, p.addon_buffet, p.addon_parking,
                   p.addon_label, p.parking_price,
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
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
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
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

/** Cancelar pedido */
function cancelOrder() {
    requireAdmin();
    $orderId = (int)($_POST['order_id'] ?? 0);
    if ($orderId <= 0) Response::error('ID inválido.', 400);

    $db = getDb();
    try {
        $stmt = $db->prepare("UPDATE pedidos SET status = 'cancelado' WHERE id = :id AND status IN ('pendiente', 'confirmado', 'solicitada_cancelacion') RETURNING id");
        $stmt->execute([':id' => $orderId]);
        if ($stmt->fetchColumn()) {
            Response::success(['message' => 'Pedido cancelado.']);
        } else {
            Response::error('Pedido no encontrado o ya procesado.', 404);
        }
    } catch (Exception $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

/** Contar pedidos pendientes (para badge en admin) */
function getPendingCount() {
    requireAdmin();
    $db = getDb();
    $count = $db->query("SELECT COUNT(*) FROM pedidos WHERE status = 'pendiente'")->fetchColumn();
    Response::success(['count' => (int)$count]);
}

/** Limpieza automática mensual de pedidos antiguos */
function runMonthlyCleanup() {
    // 1. Solo ejecutar si hoy es día 15 o posterior
    if ((int)date('d') < 15) return;

    $db = getDb();
    
    // 2. Obtener el último mes limpiado
    $stmt = $db->prepare("SELECT valor FROM app_config WHERE clave = 'last_cleanup_month'");
    $stmt->execute();
    $lastCleanup = $stmt->fetchColumn();

    // 3. Calcular el mes anterior al actual (formato YYYY-MM)
    $targetMonth = date('Y-m', strtotime('first day of last month'));

    // 4. Si ya se limpió este mes, salir
    if ($lastCleanup === $targetMonth) return;

    try {
        $db->beginTransaction();

        // 5. Borrar registros: confirmados/cancelados cuya visita sea anterior al mes actual
        $stmtDelete = $db->prepare("
            DELETE FROM pedidos 
            WHERE status IN ('confirmado', 'cancelado') 
              AND visit_date < DATE_TRUNC('month', CURRENT_DATE)
        ");
        $stmtDelete->execute();

        // 6. Actualizar el flag en la configuración
        $stmtUpdate = $db->prepare("
            INSERT INTO app_config (clave, valor) VALUES ('last_cleanup_month', :val)
            ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor
        ");
        $stmtUpdate->execute([':val' => $targetMonth]);

        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
    }
}

/** Contar solicitudes de cancelación pendientes */
function getPendingCancellations() {
    requireAdmin();
    $db = getDb();
    $count = $db->query("SELECT COUNT(*) FROM pedidos WHERE status = 'solicitada_cancelacion'")->fetchColumn();
    Response::success(['count' => (int)$count]);
}
