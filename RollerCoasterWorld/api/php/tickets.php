<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/ApiRouter.php';
require_once __DIR__ . '/utils/TicketHelper.php';

define('CART_TTL', 15 * 60); // 15 minutos en segundos

/** Comprueba y expira el carrito si lleva más de 15 minutos inactivo */
function checkCartExpiry()
{
    if (isset($_SESSION['rcw_cart_ts'])) {
        if ((time() - $_SESSION['rcw_cart_ts']) > CART_TTL) {
            // Carrito expirado: vaciarlo
            $_SESSION['rcw_cart'] = [];
            unset($_SESSION['rcw_coupon']);
            unset($_SESSION['rcw_cart_ts']);
        }
    }
    // Renovar timestamp si hay items
    if (!empty($_SESSION['rcw_cart'])) {
        $_SESSION['rcw_cart_ts'] = time();
    }
}

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
$router->register('update_cart_item', 'updateCartItem', 'POST');
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

    // Comprobar expiración antes de añadir
    checkCartExpiry();

    $parkId = (int) ($_POST['park_id'] ?? 0);
    $parkName = trim($_POST['park_name'] ?? '');
    $parkImg = trim($_POST['park_img'] ?? '');
    $type = $_POST['ticket_type'] ?? 'entrada';
    $quantity = (int) ($_POST['quantity'] ?? 1);
    $unitPrice = (float) ($_POST['unit_price'] ?? 0);
    $visitDate = trim($_POST['visit_date'] ?? '');

    // Add-ons
    $addonPase = !empty($_POST['addon_pase_rapido']) ? true : false;
    $addonPhoto = !empty($_POST['addon_photopass']) ? true : false;
    $addonBuffet = !empty($_POST['addon_buffet']) ? true : false;
    $addonParking = !empty($_POST['addon_parking']) ? true : false;
    $parkingPrice = (float) ($_POST['parking_price'] ?? 0);

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

    $total = round($unitPrice * $quantity + $parkingPrice, 2);

    $_SESSION['rcw_cart'][] = [
        'park_id' => $parkId,
        'park_name' => $parkName,
        'park_img' => $parkImg,
        'ticket_type' => $type,
        'quantity' => $quantity,
        'unit_price' => $unitPrice,
        'total' => $total,
        'visit_date' => $visitDate,
        // Add-ons
        'addon_pase_rapido' => $addonPase,
        'addon_photopass' => $addonPhoto,
        'addon_buffet' => $addonBuffet,
        'addon_parking' => $addonParking,
        'parking_price' => $parkingPrice,
    ];

    // Actualizar timestamp del carrito
    $_SESSION['rcw_cart_ts'] = time();

    Response::success([
        'cart_count' => count($_SESSION['rcw_cart']),
        'message' => 'Añadido al carrito',
        'expires_in' => CART_TTL,
    ]);
}

/** Obtener carrito actual */
function getCart()
{
    // Comprobar expiración del carrito
    checkCartExpiry();

    $cart = $_SESSION['rcw_cart'] ?? [];
    $subtotal = array_sum(array_column($cart, 'total'));

    // Aplicar cupón si existe
    $coupon = $_SESSION['rcw_coupon'] ?? null;
    $discount = 0;
    if ($coupon && $subtotal > 0) {
        $discount = round($subtotal * ($coupon['percent'] / 100), 2);
    }
    $total = round($subtotal - $discount, 2);

    // Calcular tiempo restante
    $ts = $_SESSION['rcw_cart_ts'] ?? null;
    $expiresIn = null;
    if ($ts && !empty($cart)) {
        $remaining = CART_TTL - (time() - $ts);
        $expiresIn = max(0, $remaining);
    }

    Response::success([
        'items' => $cart,
        'count' => count($cart),
        'subtotal' => round($subtotal, 2),
        'discount' => $discount,
        'total' => $total,
        'coupon' => $coupon,
        'expires_in' => $expiresIn,
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

/** Actualizar cantidad de un item del carrito */
function updateCartItem()
{
    $index = (int) ($_POST['index'] ?? -1);
    $quantity = (int) ($_POST['quantity'] ?? -1);
    $cart = $_SESSION['rcw_cart'] ?? [];

    if ($index < 0 || $index >= count($cart)) {
        Response::error('Índice inválido.', 400);
    }
    if ($quantity < 1 || $quantity > 10) {
        Response::error('Cantidad fuera de rango (1-10).', 400);
    }

    $cart[$index]['quantity'] = $quantity;
    $cart[$index]['total'] = round((float) $cart[$index]['unit_price'] * $quantity, 2);
    $_SESSION['rcw_cart'] = $cart;

    $subtotal = array_sum(array_column($cart, 'total'));
    Response::success([
        'cart_count' => count($cart),
        'subtotal' => round($subtotal, 2),
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
              (user_id, park_id, ticket_type, visit_date, quantity, unit_price, price,
               buyer_name, buyer_email, status,
               addon_pase_rapido, addon_photopass, addon_buffet, addon_parking,
               parking_price, addon_label)
            VALUES
              (:user_id, :park_id, :ticket_type, :visit_date, :quantity, :unit_price, :price,
               :buyer_name, :buyer_email, 'confirmado',
               :addon_pase_rapido, :addon_photopass, :addon_buffet, :addon_parking,
               :parking_price, :addon_label)
            RETURNING id
        ");

        $coupon = $_SESSION['rcw_coupon'] ?? null;
        $discountPercent = $coupon ? ($coupon['percent'] / 100) : 0;

        foreach ($cart as $item) {
            $itemDiscount = round($item['total'] * $discountPercent, 2);
            $finalPrice = round($item['total'] - $itemDiscount, 2);

            // Build addon label for display
            $addonParts = [];
            if (!empty($item['addon_pase_rapido']))
                $addonParts[] = 'Pase Rápido';
            if (!empty($item['addon_photopass']))
                $addonParts[] = 'PhotoPass';
            if (!empty($item['addon_buffet']))
                $addonParts[] = 'Buffet';
            if (!empty($item['addon_parking']))
                $addonParts[] = 'Parking';

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
                ':addon_pase_rapido' => !empty($item['addon_pase_rapido']),
                ':addon_photopass' => !empty($item['addon_photopass']),
                ':addon_buffet' => !empty($item['addon_buffet']),
                ':addon_parking' => !empty($item['addon_parking']),
                ':parking_price' => (float) ($item['parking_price'] ?? 0),
                ':addon_label' => implode(', ', $addonParts),
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
        unset($_SESSION['rcw_cart_ts']);

        // ── Enviar entradas por email ─────────────────────────────────────────
        sendTicketsByEmail($buyerEmail, $buyerName, $createdOrders, $userId);

        Response::success([
            'order_ids' => $createdOrders,
            'message' => '¡Pedido confirmado! Ya puedes descargar tus entradas.',
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();
        Response::error('Error al crear el pedido: ' . $e->getMessage(), 500);
    }
}


/** Envía las entradas en PDF al email del comprador */
function sendTicketsByEmail(string $email, string $name, array $orderIds, int $userId): void
{
    try {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoload))
            return;
        require_once $autoload;

        // ── Leer config SMTP desde .env ──────────────────────────────────────
        $envFile = __DIR__ . '/../../.env';
        $env = [];
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '='))
                    continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }

        $mailEnabled = strtolower($env['MAIL_ENABLED'] ?? 'false') === 'true';
        if (!$mailEnabled) {
            // En local o sin configurar: omitir silenciosamente
            error_log('RCW: MAIL_ENABLED=false, email no enviado a ' . $email);
            return;
        }

        // ── Generar PDFs ──────────────────────────────────────────────────────
        $db = getDb();
        $pdfs = [];

        foreach ($orderIds as $orderId) {
            $stmt = $db->prepare("
                SELECT p.*, pk.park_name, pk.park_country, pk.park_location
                FROM pedidos p
                JOIN parks pk ON p.park_id = pk.id
                WHERE p.id = :id AND p.user_id = :uid
            ");
            $stmt->execute([':id' => $orderId, ':uid' => $userId]);
            $order = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$order)
                continue;

            $html = buildTicketHtml($order, (int) $orderId);
            if (empty($html))
                continue;

            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfs[] = [
                'content' => $dompdf->output(),
                'filename' => 'entrada_RCW_' . $orderId . '_' . date('Ymd') . '.pdf',
            ];
        }

        if (empty($pdfs))
            return;

        // ── Enviar con PHPMailer ──────────────────────────────────────────────
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $env['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $env['MAIL_USER'] ?? '';
        $mail->Password = $env['MAIL_PASS'] ?? '';
        $mail->SMTPSecure = strtolower($env['MAIL_SECURE'] ?? 'tls') === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($env['MAIL_PORT'] ?? 587);
        $mail->CharSet = 'UTF-8';

        $mail->setFrom(
            $env['MAIL_FROM'] ?? 'noreply@rollercoasterworld.com',
            $env['MAIL_FROM_NAME'] ?? 'RollerCoasterWorld'
        );
        $mail->addAddress($email, $name);
        $mail->addReplyTo($env['MAIL_FROM'] ?? 'noreply@rollercoasterworld.com');

        $mail->isHTML(true);
        $mail->Subject = 'Tus entradas RollerCoasterWorld';

        $countTickets = count($pdfs);
        $plural = $countTickets > 1 ? 's' : '';
        $mail->Body = <<<MAIL
<!DOCTYPE html><html lang="es"><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<div style="max-width:580px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
  <div style="background:#1a6e2e;padding:24px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">&#127914; RollerCoasterWorld</h1>
    <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px;">The Ultimate Thrill Experience</p>
  </div>
  <div style="padding:28px 32px;">
    <h2 style="color:#1a1a1a;font-size:18px;margin:0 0 12px;">¡Hola, {$name}!</h2>
    <p style="color:#444;line-height:1.6;">Tu pedido ha sido confirmado. Adjuntamos tu{$plural} entrada{$plural} en formato PDF.</p>
    <div style="background:#f0faf3;border:1px solid #b7dfc2;border-radius:6px;padding:16px 20px;margin:20px 0;">
      <p style="margin:0;color:#1a6e2e;font-weight:bold;">&#10003; {$countTickets} entrada{$plural} adjunta{$plural}</p>
    </div>
    <p style="color:#444;line-height:1.6;">También puedes descargar tus entradas desde tu cuenta en cualquier momento.</p>
    <p style="color:#888;font-size:12px;margin-top:20px;">Mensaje automático — no respondas a este correo.</p>
  </div>
  <div style="background:#f9f9f9;padding:16px 32px;border-top:1px solid #eee;">
    <p style="margin:0;color:#aaa;font-size:11px;text-align:center;">&copy; RollerCoasterWorld &bull; Entrada personal e intransferible</p>
  </div>
</div>
</body></html>
MAIL;
        $mail->AltBody = "Hola {$name}, adjuntamos tu{$plural} entrada{$plural}. Pedido confirmado en RollerCoasterWorld.";

        foreach ($pdfs as $pdf) {
            $mail->addStringAttachment(
                $pdf['content'],
                $pdf['filename'],
                \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64,
                'application/pdf'
            );
        }

        $mail->send();
    } catch (\Exception $e) {
        // Email no crítico: si falla, el pedido igual se confirma
        error_log('RCW sendTicketsByEmail error: ' . $e->getMessage());
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
