<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/ApiRouter.php';

header('Content-Type: application/json');

$db = null;
function getDb()
{
    global $db;
    if ($db === null)
        $db = new DBConexion();
    return $db;
}

function getUserId(): ?int
{
    if (isset($_SESSION['user_id']))
        return (int) $_SESSION['user_id'];
    if (isset($_SESSION['firebase_uid'])) {
        $db = getDb();
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int) $row['id'];
            return (int) $row['id'];
        }
    }
    return null;
}

$router = new ApiRouter('list_parks');
$router->register('list_parks', 'listParksWithPrice');
$router->register('add_to_cart', 'addToCart', 'POST');
$router->register('get_cart', 'getCart');
$router->register('remove_from_cart', 'removeFromCart', 'POST');
$router->register('clear_cart', 'clearCart', 'POST');
$router->register('create_order', 'createOrder', 'POST');
$router->register('apply_coupon', 'applyCoupon', 'POST');
$router->register('remove_coupon', 'removeCoupon', 'POST');
$router->register('validate_coupon', 'validateCoupon');
$router->dispatch();

// ─────────────────────────────────────────────────────────────

/** Parques con precio de entrada habilitado */
function listParksWithPrice()
{
    $db = getDb();
    try {
        $stmt = $db->query("
            SELECT id, park_name, park_location, park_country, imagen_url, precio_entrada
            FROM parks
            WHERE precio_entrada IS NOT NULL AND precio_entrada > 0
            ORDER BY park_name ASC
        ");
        $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['parks' => $parks]);
    } catch (Exception $e) {
        Response::error('Error al obtener parques: ' . $e->getMessage(), 500);
    }
}

/** Añadir item al carrito (sesión) */
function addToCart()
{
    if (!isset($_SESSION['firebase_uid'])) {
        Response::error('Debes iniciar sesión para añadir al carrito.', 401);
    }

    $parkId = (int) ($_POST['park_id'] ?? 0);
    $parkName = trim($_POST['park_name'] ?? '');
    $parkImg = trim($_POST['park_img'] ?? '');
    $type = $_POST['ticket_type'] ?? 'entrada';
    $quantity = (int) ($_POST['quantity'] ?? 1);
    $unitPrice = (float) ($_POST['unit_price'] ?? 0);
    $visitDate = trim($_POST['visit_date'] ?? '');

    if ($parkId <= 0 || $quantity < 1 || $quantity > 10 || $unitPrice <= 0 || empty($visitDate)) {
        Response::error('Datos inválidos.', 400);
    }
    if (!in_array($type, ['entrada', 'pase_rapido'])) {
        Response::error('Tipo de entrada inválido.', 400);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $visitDate) || $visitDate < date('Y-m-d')) {
        Response::error('Fecha de visita inválida.', 400);
    }

    if (!isset($_SESSION['rcw_cart']))
        $_SESSION['rcw_cart'] = [];

    $_SESSION['rcw_cart'][] = [
        'park_id' => $parkId,
        'park_name' => $parkName,
        'park_img' => $parkImg,
        'ticket_type' => $type,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total' => round($unitPrice * $quantity, 2),
        'visit_date' => $visitDate,
    ];

    Response::success([
        'cart_count' => count($_SESSION['rcw_cart']),
        'message' => 'Añadido al carrito',
    ]);
}

/** Obtener carrito actual */
function getCart()
{
    $cart = $_SESSION['rcw_cart'] ?? [];
    $subtotal = array_sum(array_column($cart, 'total'));

    // Aplicar cupón si existe
    $coupon = $_SESSION['rcw_coupon'] ?? null;
    $discount = 0;
    if ($coupon && $subtotal > 0) {
        $discount = round($subtotal * ($coupon['percent'] / 100), 2);
    }
    $total = round($subtotal - $discount, 2);

    Response::success([
        'items' => $cart,
        'count' => count($cart),
        'subtotal' => round($subtotal, 2),
        'discount' => $discount,
        'total' => $total,
        'coupon' => $coupon,
    ]);
}

/** Eliminar item por índice */
function removeFromCart()
{
    $index = (int) ($_POST['index'] ?? -1);
    $cart = $_SESSION['rcw_cart'] ?? [];

    if ($index < 0 || $index >= count($cart)) {
        Response::error('Índice inválido.', 400);
    }

    array_splice($cart, $index, 1);
    $_SESSION['rcw_cart'] = array_values($cart);

    $total = array_sum(array_column($_SESSION['rcw_cart'], 'total'));
    Response::success([
        'cart_count' => count($_SESSION['rcw_cart']),
        'total' => round($total, 2),
    ]);
}

/** Vaciar carrito */
function clearCart()
{
    $_SESSION['rcw_cart'] = [];
    Response::success(['message' => 'Carrito vaciado']);
}

/** Crear pedido en BD y vaciar carrito */
function createOrder()
{
    $userId = getUserId();
    if (!$userId)
        Response::error('Debes iniciar sesión.', 401);

    $cart = $_SESSION['rcw_cart'] ?? [];
    if (empty($cart))
        Response::error('El carrito está vacío.', 400);

    // ── Leer nombre y email del body ──────────────────────
    $buyerName = trim($_POST['name'] ?? '');
    $buyerEmail = trim($_POST['email'] ?? '');

    if (!$buyerName || !$buyerEmail) {
        Response::error('Nombre y email son obligatorios.', 400);
    }
    if (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        Response::error('El email no tiene un formato válido.', 400);
    }

    $db = getDb();
    $createdOrders = [];

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO pedidos
              (user_id, park_id, ticket_type, visit_date, quantity, unit_price, price, buyer_name, buyer_email, status)
            VALUES
              (:user_id, :park_id, :ticket_type, :visit_date, :quantity, :unit_price, :price, :buyer_name, :buyer_email, 'pendiente')
            RETURNING id
        ");

        $coupon = $_SESSION['rcw_coupon'] ?? null;
        $discountPercent = $coupon ? ($coupon['percent'] / 100) : 0;

        foreach ($cart as $item) {
            $itemDiscount = round($item['total'] * $discountPercent, 2);
            $finalPrice = round($item['total'] - $itemDiscount, 2);

            $stmt->execute([
                ':user_id' => $userId,
                ':park_id' => $item['park_id'],
                ':ticket_type' => $item['ticket_type'],
                ':visit_date' => $item['visit_date'],
                ':quantity' => $item['quantity'],
                ':unit_price' => $item['unit_price'],
                ':price' => $finalPrice,
                ':buyer_name' => $buyerName,
                ':buyer_email' => $buyerEmail,
            ]);
            $createdOrders[] = $stmt->fetchColumn();
        }

        if ($coupon) {
            $stmtCoupon = $db->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = :id");
            $stmtCoupon->execute([':id' => $coupon['id']]);
        }

        $db->commit();
        $_SESSION['rcw_cart'] = [];
        unset($_SESSION['rcw_coupon']);

        Response::success([
            'order_ids' => $createdOrders,
            'message' => 'Pedido creado correctamente. Pendiente de confirmación.',
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();
        Response::error('Error al crear el pedido: ' . $e->getMessage(), 500);
    }
}

/** Aplicar cupón a la sesión */
function applyCoupon()
{
    $code = trim($_POST['code'] ?? '');
    if (empty($code))
        Response::error('Código de cupón vacío');

    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code AND active = true AND (expires_at IS NULL OR expires_at >= CURRENT_DATE)");
    $stmt->execute([':code' => $code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon) {
        Response::error('Cupón no válido o expirado');
    }

    if ($coupon['max_uses'] !== null && $coupon['uses_count'] >= $coupon['max_uses']) {
        Response::error('Este cupón ya ha alcanzado su límite de usos');
    }

    $_SESSION['rcw_coupon'] = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'percent' => (float) $coupon['discount_value'],
        'description' => $coupon['description'],
    ];

    Response::success([
        'message' => 'Cupón aplicado',
        'discount_percent' => (float) $coupon['discount_value'],
        'description' => $coupon['description'],
    ]);
}

/** Quitar cupón de la sesión */
function removeCoupon()
{
    unset($_SESSION['rcw_coupon']);
    Response::success(['message' => 'Cupón eliminado']);
}

/** Validar cupón (solo lectura) */
function validateCoupon()
{
    $code = trim($_GET['code'] ?? '');
    if (empty($code))
        Response::error('Código vacío');

    $db = getDb();
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = :code AND active = true AND (expires_at IS NULL OR expires_at >= CURRENT_DATE)");
    $stmt->execute([':code' => $code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$coupon)
        Response::error('Cupón inválido o expirado');
    if ($coupon['max_uses'] !== null && $coupon['uses_count'] >= $coupon['max_uses']) {
        Response::error('Límite de usos alcanzado');
    }

    Response::success([
        'code' => $coupon['code'],
        'percent' => (float) $coupon['discount_value'],
        'description' => $coupon['description']
    ]);
}
