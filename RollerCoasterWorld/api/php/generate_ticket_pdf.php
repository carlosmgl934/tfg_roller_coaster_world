<?php
require_once __DIR__ . '/utils/SessionManager.php';
require_once __DIR__ . '/../database/db_conexion.php';

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    die('Dependencias no instaladas. Ejecuta: composer install');
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Auth ───────────────────────────────────────────────────
function getUserId(): ?int
{
    if (isset($_SESSION['user_id']))
        return (int) $_SESSION['user_id'];
    if (isset($_SESSION['firebase_uid'])) {
        $db = new DBConexion();
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

$userId  = getUserId();
$orderId = (int) ($_GET['order_id'] ?? 0);

if (!$userId || $orderId <= 0) {
    http_response_code(403);
    die('Acceso denegado.');
}

// ── Obtener pedido ─────────────────────────────────────────
$db   = new DBConexion();
$stmt = $db->prepare("
    SELECT p.*, pk.park_name, pk.park_country, pk.park_location, p.buyer_name, p.buyer_email
    FROM pedidos p
    JOIN parks pk ON p.park_id = pk.id
    WHERE p.id = :id AND p.user_id = :uid AND p.status = 'confirmado'
");
$stmt->execute([':id' => $orderId, ':uid' => $userId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    die('Entrada no encontrada o aún no confirmada.');
}

if ($order['visit_date'] < date('Y-m-d')) {
    http_response_code(403);
    die('Esta entrada ya no está disponible para descarga (fecha de visita pasada).');
}


// ── Generar HTML via helper compartido ────────────────────
require_once __DIR__ . '/utils/TicketHelper.php';
$html = buildTicketHtml($order, $orderId);

if (empty($html)) {
    http_response_code(500);
    die('Error generando el documento de entrada.');
}

// ── Generar PDF ────────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultPaperSize', 'A4');
$options->set('defaultPaperOrientation', 'portrait');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'entrada_RCW_' . $orderId . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);



