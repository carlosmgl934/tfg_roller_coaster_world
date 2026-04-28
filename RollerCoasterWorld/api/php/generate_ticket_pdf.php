<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';

$autoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoload)) {
  http_response_code(500);
  die('Dependencias no instaladas. Ejecuta: composer install');
}
require_once $autoload;

use Dompdf\Dompdf;
use Dompdf\Options;
use Picqer\Barcode\BarcodeGeneratorPNG;

// ── Auth ─────────────────────────────────────────────────
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

$userId = getUserId();
$orderId = (int) ($_GET['order_id'] ?? 0);

if (!$userId || $orderId <= 0) {
  http_response_code(403);
  die('Acceso denegado.');
}

// ── Obtener pedido ────────────────────────────────────────
$db = new DBConexion();
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

// ── Datos ─────────────────────────────────────────────────
$ticketCode = 'RCW-' . date('Y') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT)
  . '-' . strtoupper(substr(md5($orderId . 'rcw_secret'), 0, 6));

$typeLabel = $order['ticket_type'] === 'pase_rapido' ? 'PASE RÁPIDO' : 'ENTRADA GENERAL';
$typeSub = $order['ticket_type'] === 'pase_rapido'
  ? 'Acceso prioritario a todas las atracciones'
  : 'Acceso completo al parque';
$visitDate = date('d/m/Y', strtotime($order['visit_date']));
$dias = [
  'Monday' => 'LUNES',
  'Tuesday' => 'MARTES',
  'Wednesday' => 'MIÉRCOLES',
  'Thursday' => 'JUEVES',
  'Friday' => 'VIERNES',
  'Saturday' => 'SÁBADO',
  'Sunday' => 'DOMINGO'
];
$visitDay = $dias[(new DateTime($order['visit_date']))->format('l')] ?? '';
$createdAt = date('d/m/Y H:i', strtotime($order['created_at']));

// ── Código de barras PNG (base64) ─────────────────────────
$generator = new BarcodeGeneratorPNG();
$barcodeRaw = $generator->getBarcode($ticketCode, $generator::TYPE_CODE_128, 2, 60);
$barcodeB64 = base64_encode($barcodeRaw);
$barcodeImg = '<img src="data:image/png;base64,' . $barcodeB64 . '" style="width:260px;height:60px;display:block;margin:0 auto;">';

// ── HTML ──────────────────────────────────────────────────
$html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  @page { margin: 18mm 14mm; }

  body {
    font-family: Arial, Helvetica, sans-serif;
    background: #ffffff;
    color: #1a1a1a;
    font-size: 11px;
  }

  /* ── Cabecera ── */
  .header {
    border-bottom: 3px solid #1a6e2e;
    padding-bottom: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
  }
  .logo {
    font-size: 22px;
    font-weight: bold;
    color: #1a1a1a;
    letter-spacing: 0.5px;
  }
  .logo span { color: #1a6e2e; }
  .logo-sub {
    font-size: 8px;
    letter-spacing: 3px;
    color: #888;
    text-transform: uppercase;
    margin-top: 3px;
  }
  .header-right {
    text-align: right;
    color: #555;
    font-size: 9px;
    line-height: 1.6;
  }
  .header-right strong {
    font-size: 11px;
    color: #1a1a1a;
  }

  /* ── Badge tipo ── */
  .type-badge {
    display: inline-block;
    background: #1a6e2e;
    color: white;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 2px;
    padding: 4px 12px;
    border-radius: 2px;
    text-transform: uppercase;
    margin-bottom: 8px;
  }
  .type-badge.rapido { background: #b8860b; }

  /* ── Nombre del parque ── */
  .park-block {
    margin-bottom: 22px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
  }
  .park-name {
    font-size: 24px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 3px;
  }
  .park-meta {
    font-size: 10px;
    color: #777;
    letter-spacing: 1px;
  }

  /* ── Grid de detalles ── */
  .details-grid {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 22px;
  }
  .details-grid td {
    padding: 10px 14px;
    border: 1px solid #e8e8e8;
    vertical-align: top;
    width: 50%;
    background: #fafafa;
  }
  .details-grid td:nth-child(odd) {
    border-right: none;
  }
  .detail-label {
    font-size: 8px;
    letter-spacing: 2px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 4px;
  }
  .detail-value {
    font-size: 15px;
    font-weight: bold;
    color: #1a1a1a;
  }
  .detail-value.green { color: #1a6e2e; font-size: 18px; }
  .detail-sub {
    font-size: 9px;
    color: #aaa;
    margin-top: 2px;
  }

  /* ── Sección código ── */
  .code-section {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 18px;
    text-align: center;
    margin-bottom: 18px;
  }
  .code-label {
    font-size: 8px;
    letter-spacing: 3px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 8px;
  }
  .ticket-code {
    font-family: 'Courier New', monospace;
    font-size: 18px;
    font-weight: bold;
    color: #1a1a1a;
    letter-spacing: 3px;
    margin-bottom: 12px;
  }

  /* ── Línea de corte ── */
  .tear-line {
    border-top: 1px dashed #aaa;
    margin: 18px 0;
    position: relative;
    text-align: center;
  }
  .tear-label {
    display: inline-block;
    position: relative;
    top: -8px;
    background: white;
    padding: 0 10px;
    font-size: 8px;
    letter-spacing: 2px;
    color: #aaa;
    text-transform: uppercase;
  }

  /* ── Stub ── */
  .stub {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 3px;
    padding: 12px 16px;
  }
  .stub-table { width: 100%; border-collapse: collapse; }
  .stub-table td { vertical-align: middle; padding: 0; }
  .stub-park { font-weight: bold; font-size: 12px; color: #1a1a1a; }
  .stub-info { font-size: 9px; color: #888; margin-top: 2px; }
  .stub-right { text-align: right; }
  .stub-code {
    font-family: 'Courier New', monospace;
    font-size: 10px;
    color: #555;
  }
  .stub-type {
    display: inline-block;
    border: 1px solid #1a6e2e;
    color: #1a6e2e;
    font-size: 8px;
    letter-spacing: 2px;
    padding: 3px 8px;
    margin-bottom: 4px;
  }

  /* ── Footer ── */
  .footer {
    margin-top: 16px;
    padding-top: 10px;
    border-top: 1px solid #e0e0e0;
    font-size: 8px;
    color: #aaa;
    display: flex;
    justify-content: space-between;
  }
  .footer-valid {
    color: #1a6e2e;
    font-weight: bold;
    font-size: 9px;
    letter-spacing: 1px;
  }
</style>
</head>
<body>

  <!-- CABECERA -->
  <div class="header">
    <div>
      <div class="logo">Roller<span>Coaster</span>World</div>
      <div class="logo-sub">The Ultimate Thrill Experience</div>
    </div>
    <div class="header-right">
      <strong>ENTRADA OFICIAL</strong><br>
      Emitida el {$createdAt}<br>
      Pedido #{$orderId}
    </div>
  </div>

  <!-- PARQUE + TIPO -->
  <div class="park-block">
    <div class="type-badge {$order['ticket_type']}">{$typeLabel}</div>
    <div class="park-name">{$order['park_name']}</div>
    <div class="park-meta">{$order['park_location']} &middot; {$order['park_country']}</div>
  </div>

  <!-- DETALLES -->
  <table class="details-grid">
    <tr>
      <td>
        <div class="detail-label">Fecha de visita</div>
        <div class="detail-value">{$visitDate}</div>
        <div class="detail-sub">{$visitDay}</div>
      </td>
      <td>
        <div class="detail-label">Tipo de acceso</div>
        <div class="detail-value" style="font-size:13px;">{$typeLabel}</div>
        <div class="detail-sub">{$typeSub}</div>
      </td>
    </tr>
    <tr>
      <td>
        <div class="detail-label">Número de personas</div>
        <div class="detail-value">{$order['quantity']}</div>
        <div class="detail-sub">entrada(s) incluida(s)</div>
      </td>
      <td>
        <div class="detail-label">Total pagado</div>
        <div class="detail-value green">{$order['price']} &euro;</div>
        <div class="detail-sub">{$order['unit_price']} &euro; / persona</div>
      </td>
    </tr>
    <tr>
      <td>
        <div class="detail-label">Titular</div>
        <div class="detail-value" style="font-size:13px;">{$order['buyer_name']}</div>
        <div class="detail-sub">{$order['buyer_email']}</div>
      </td>
      <td>
        <div class="detail-label">Fecha de compra</div>
        <div class="detail-value" style="font-size:13px;">{$createdAt}</div>
        <div class="detail-sub">Pedido #{$orderId}</div>
      </td>
    </tr>
  </table>

  <!-- CÓDIGO + BARCODE -->
  <div class="code-section">
    <div class="code-label">Código de entrada</div>
    <div class="ticket-code">{$ticketCode}</div>
    {$barcodeImg}
  </div>

  <!-- LÍNEA DE CORTE -->
  <div class="tear-line">
    <span class="tear-label">&#9986; Línea de corte &middot; Conservar ambas partes</span>
  </div>

  <!-- STUB -->
  <div class="stub">
    <table class="stub-table">
      <tr>
        <td>
          <div class="stub-park">{$order['park_name']}</div>
          <div class="stub-info">{$visitDate} &nbsp;&middot;&nbsp; {$order['quantity']} entrada(s) &nbsp;&middot;&nbsp; {$order['buyer_name']}</div>
        </td>
        <td class="stub-right">
          <div class="stub-type">{$typeLabel}</div>
          <div class="stub-code">{$ticketCode}</div>
        </td>
      </tr>
    </table>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <div>
      &bull; Presenta esta entrada en la taquilla de acceso al parque<br>
      &bull; Entrada personal e intransferible &nbsp;&middot;&nbsp; rollercoasterworld.com
    </div>
    <div class="footer-valid">&#10003; ENTRADA CONFIRMADA</div>
  </div>

</body>
</html>
HTML;

// ── Generar PDF ───────────────────────────────────────────
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