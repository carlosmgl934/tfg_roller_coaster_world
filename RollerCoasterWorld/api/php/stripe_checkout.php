<?php
/**
 * stripe_checkout.php — Pasarela de pago con Stripe (modo test)
 *
 * Acciones:
 *  POST action=create_session  → Crea una Stripe Checkout Session y devuelve la URL
 *  GET  action=verify_session  → Verifica el pago y crea el pedido en BD
 */

require_once __DIR__ . '/utils/SessionManager.php';
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
// Intentar encontrar vendor/autoload.php subiendo niveles
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
}
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die("Autoload no encontrado.");
}

define('CART_TTL', 15 * 60);

header('Content-Type: application/json');

// ── Leer .env (ruta flexible) ────────────────────────────────────────────────
$envFile = __DIR__ . '/../../.env';
if (!file_exists($envFile)) {
    $envFile = __DIR__ . '/../../../.env';
}
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
}

$stripeSecret = $env['STRIPE_SECRET_KEY'] ?? '';
if (empty($stripeSecret)) {
    Response::error('Stripe no configurado. Añade STRIPE_SECRET_KEY al .env', 500);
}

\Stripe\Stripe::setApiKey($stripeSecret);

// ── Router simple ─────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

match($action) {
    'create_session' => createCheckoutSession($env),
    'verify_session' => verifySession(),
    default          => Response::error('Acción no válida', 400),
};

// ─────────────────────────────────────────────────────────────────────────────

/**
 * Crea una Stripe Checkout Session con los ítems del carrito de sesión.
 * El carrito se serializa en metadata para recuperarlo en verify_session.
 */
function createCheckoutSession(array $env): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }
    if (!isset($_SESSION['firebase_uid'])) {
        Response::error('Debes iniciar sesión', 401);
    }

    // Comprobar expiración carrito
    if (isset($_SESSION['rcw_cart_ts']) && (time() - $_SESSION['rcw_cart_ts']) > CART_TTL) {
        $_SESSION['rcw_cart'] = [];
        unset($_SESSION['rcw_coupon'], $_SESSION['rcw_cart_ts']);
    }

    $cart = $_SESSION['rcw_cart'] ?? [];
    if (empty($cart)) {
        Response::error('El carrito está vacío', 400);
    }

    $buyerName  = trim($_POST['name']  ?? '');
    $buyerEmail = trim($_POST['email'] ?? '');

    if (!$buyerName || !$buyerEmail) {
        Response::error('Nombre y email son obligatorios', 400);
    }
    if (!filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
        Response::error('Email no válido', 400);
    }

    // Aplicar cupón si existe
    $coupon          = $_SESSION['rcw_coupon'] ?? null;
    $discountPercent = $coupon ? ($coupon['percent'] / 100) : 0;

    // Construir line_items para Stripe (precio en céntimos)
    $lineItems = [];
    foreach ($cart as $item) {
        $unitPrice   = (float)$item['unit_price'];
        $finalUnit   = round($unitPrice * (1 - $discountPercent), 2);
        $amountCents = (int)round($finalUnit * 100);

        $label  = $item['park_name'];
        $label .= ' — ' . ($item['ticket_type'] === 'pase_rapido' ? 'Pase Rápido' : 'Entrada General');
        $label .= ' (' . $item['visit_date'] . ')';

        $lineItems[] = [
            'price_data' => [
                'currency'     => 'eur',
                'unit_amount'  => $amountCents,
                'product_data' => ['name' => $label],
            ],
            'quantity' => (int)$item['quantity'],
        ];
    }

    // Serializar datos del comprador en metadata de la session
    $meta = [
        'buyer_name'  => $buyerName,
        'buyer_email' => $buyerEmail,
        'user_id'     => (string)getUserId(),
    ];

    // URL base del proyecto
    $baseUrl = getBaseUrl();
    $successUrl = $baseUrl . '/web/views/public/shop/checkout.php?payment=success&session_id={CHECKOUT_SESSION_ID}';
    $cancelUrl  = $baseUrl . '/web/views/public/shop/checkout.php?payment=cancel';

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'line_items'           => $lineItems,
            'customer_email'       => $buyerEmail,
            'metadata'             => $meta,
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
        ]);

        Response::success([
            'url'        => $session->url,
            'session_id' => $session->id,
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

/**
 * Verifica que el pago fue completado y crea el pedido en la BD.
 * Se llama desde el return URL (success_url).
 */
function verifySession(): void
{
    $sessionId = $_POST['session_id'] ?? $_GET['session_id'] ?? '';
    if (empty($sessionId)) {
        Response::error('session_id requerido', 400);
    }

    if (!isset($_SESSION['firebase_uid'])) {
        Response::error('Sesión expirada, vuelve a iniciar sesión', 401);
    }

    try {
        $session = \Stripe\Checkout\Session::retrieve($sessionId);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }

    if ($session->payment_status !== 'paid') {
        Response::error('El pago no se completó (status: ' . $session->payment_status . ')', 402);
    }

    // Evitar doble procesamiento: guardamos el session_id procesado
    if (isset($_SESSION['rcw_stripe_processed'][$sessionId])) {
        Response::success([
            'already_processed' => true,
            'order_ids'         => $_SESSION['rcw_stripe_processed'][$sessionId],
        ]);
    }

    // Recuperar metadatos
    $buyerName  = $session->metadata->buyer_name  ?? '';
    $buyerEmail = $session->metadata->buyer_email ?? $session->customer_email ?? '';

    // Crear pedido en BD (misma lógica que tickets.php::createOrder)
    $db = new DBConexion();
    $userId = getUserId();
    if (!$userId) {
        Response::error('Usuario no encontrado', 401);
    }

    $cart = $_SESSION['rcw_cart'] ?? [];
    if (empty($cart)) {
        // El carrito ya fue vaciado (recarga de página), devolver los IDs guardados
        Response::error('El carrito ya fue procesado', 409);
    }

    $coupon          = $_SESSION['rcw_coupon'] ?? null;
    $discountPercent = $coupon ? ($coupon['percent'] / 100) : 0;

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO pedidos
              (user_id, park_id, ticket_type, visit_date, quantity, unit_price, price,
               buyer_name, buyer_email, status, stripe_session_id,
               addon_pase_rapido, addon_photopass, addon_buffet, addon_parking,
               parking_price, addon_label)
            VALUES
              (:user_id, :park_id, :ticket_type, :visit_date, :quantity, :unit_price, :price,
               :buyer_name, :buyer_email, 'confirmado', :stripe_session_id,
               :addon_pase_rapido, :addon_photopass, :addon_buffet, :addon_parking,
               :parking_price, :addon_label)
            RETURNING id
        ");

        $createdOrders = [];
        foreach ($cart as $item) {
            $itemDiscount = round((float)$item['total'] * $discountPercent, 2);
            $finalPrice   = round((float)$item['total'] - $itemDiscount, 2);

            // Build addon label
            $addonParts = [];
            if (!empty($item['addon_pase_rapido'])) $addonParts[] = 'Pase Rápido';
            if (!empty($item['addon_photopass']))   $addonParts[] = 'PhotoPass';
            if (!empty($item['addon_buffet']))      $addonParts[] = 'Buffet';
            if (!empty($item['addon_parking']))     $addonParts[] = 'Parking';

            $stmt->execute([
                ':user_id'           => $userId,
                ':park_id'           => $item['park_id'],
                ':ticket_type'       => $item['ticket_type'],
                ':visit_date'        => $item['visit_date'],
                ':quantity'          => $item['quantity'],
                ':unit_price'        => $item['unit_price'],
                ':price'             => $finalPrice,
                ':buyer_name'        => $buyerName,
                ':buyer_email'       => $buyerEmail,
                ':stripe_session_id' => $sessionId,
                ':addon_pase_rapido' => !empty($item['addon_pase_rapido']) ? 1 : 0,
                ':addon_photopass'   => !empty($item['addon_photopass']) ? 1 : 0,
                ':addon_buffet'      => !empty($item['addon_buffet']) ? 1 : 0,
                ':addon_parking'     => !empty($item['addon_parking']) ? 1 : 0,
                ':parking_price'     => (float)($item['parking_price'] ?? 0),
                ':addon_label'       => implode(', ', $addonParts),
            ]);
            $createdOrders[] = $stmt->fetchColumn();
        }


        if ($coupon) {
            $stmtC = $db->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = :id");
            $stmtC->execute([':id' => $coupon['id']]);
        }

        $db->commit();

        // Vaciar carrito
        $_SESSION['rcw_cart'] = [];
        unset($_SESSION['rcw_coupon'], $_SESSION['rcw_cart_ts']);

        // Marcar como procesado para evitar duplicados en recarga
        if (!isset($_SESSION['rcw_stripe_processed'])) {
            $_SESSION['rcw_stripe_processed'] = [];
        }
        $_SESSION['rcw_stripe_processed'][$sessionId] = $createdOrders;

        // Enviar email con entradas (función definida abajo en este mismo archivo)
        sendTicketsByEmailStripe($buyerEmail, $buyerName, $createdOrders, $userId, $db);

        Response::success([
            'order_ids' => $createdOrders,
            'message'   => '¡Pago confirmado! Tus entradas están listas.',
        ]);

    } catch (\Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function getUserId(): ?int
{
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];

    if (isset($_SESSION['firebase_uid'])) {
        $db   = new DBConexion();
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int)$row['id'];
            return (int)$row['id'];
        }
    }
    return null;
}

function getBaseUrl(): string
{
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Si el script contiene /RollerCoasterWorld/, estamos en local (XAMPP)
    if (str_contains($script, '/RollerCoasterWorld/')) {
        $base = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $script) ?? '';
    } else {
        // En producción, si el dominio ya apunta a la raíz del proyecto
        $base = '';
    }
    
    return $proto . '://' . $host . $base;
}

/**
 * Versión standalone del envío de email para stripe_checkout.php
 * (evita hacer require de tickets.php que tiene su propio dispatcher)
 */
function sendTicketsByEmailStripe(string $email, string $name, array $orderIds, int $userId, DBConexion $db): void
{
    try {
        $envFile = __DIR__ . '/../../.env';
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../../../.env';
        }
        $env = [];
        if (file_exists($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v);
            }
        }

        $mailEnabled = strtolower($env['MAIL_ENABLED'] ?? 'false') === 'true';
        if (!$mailEnabled) {
            error_log('RCW Stripe: MAIL_ENABLED=false, email no enviado a ' . $email);
            return;
        }

        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (!file_exists($autoload)) {
            $autoload = __DIR__ . '/../../../vendor/autoload.php';
        }
        if (!file_exists($autoload)) return;
        require_once $autoload;

        // Cargar buildTicketHtml desde el helper compartido (evita ejecutar el router de tickets.php)
        require_once __DIR__ . '/utils/TicketHelper.php';

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
            if (!$order) continue;

            // Usar el template compartido (QR + márgenes + páginas por persona)
            $html = buildTicketHtml($order, (int) $orderId);
            if (empty($html)) continue;

            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfs[] = [
                'content'  => $dompdf->output(),
                'filename' => 'entrada_RCW_' . $orderId . '_' . date('Ymd') . '.pdf',
            ];
        }

        if (empty($pdfs)) return;

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $env['MAIL_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $env['MAIL_USER'] ?? '';
        $mail->Password   = $env['MAIL_PASS'] ?? '';
        $mail->SMTPSecure = strtolower($env['MAIL_SECURE'] ?? 'tls') === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port    = (int)($env['MAIL_PORT'] ?? 587);
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($env['MAIL_FROM'] ?? 'noreply@rollercoasterworld.com', $env['MAIL_FROM_NAME'] ?? 'RollerCoasterWorld');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);

        $n  = count($pdfs);
        $pl = $n > 1 ? 's' : '';
        $mail->Subject = 'Tus entradas RollerCoasterWorld — Pago confirmado';
        $mail->Body = <<<MAIL
<!DOCTYPE html><html lang="es"><body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px;">
<div style="max-width:580px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
  <div style="background:#1a6e2e;padding:24px 32px;">
    <h1 style="color:#fff;margin:0;font-size:22px;">&#127914; RollerCoasterWorld</h1>
    <p style="color:rgba(255,255,255,.85);margin:6px 0 0;font-size:13px;">The Ultimate Thrill Experience</p>
  </div>
  <div style="padding:28px 32px;">
    <h2 style="color:#1a1a1a;font-size:18px;margin:0 0 12px;">¡Hola, {$name}!</h2>
    <p style="color:#444;line-height:1.6;">Tu pago ha sido confirmado. Adjuntamos tu{$pl} entrada{$pl} en formato PDF.</p>
    <div style="background:#f0faf3;border:1px solid #b7dfc2;border-radius:6px;padding:16px 20px;margin:20px 0;">
      <p style="margin:0;color:#1a6e2e;font-weight:bold;">&#10003; {$n} entrada{$pl} adjunta{$pl}</p>
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
        $mail->AltBody = "Hola {$name}, pago confirmado. Adjuntamos tu{$pl} entrada{$pl}.";

        foreach ($pdfs as $pdf) {
            $mail->addStringAttachment($pdf['content'], $pdf['filename'], \PHPMailer\PHPMailer\PHPMailer::ENCODING_BASE64, 'application/pdf');
        }
        $mail->send();

    } catch (\Exception $e) {
        error_log('RCW Stripe sendEmail error: ' . $e->getMessage());
    }
}
