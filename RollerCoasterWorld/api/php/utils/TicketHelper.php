<?php
/**
 * TicketHelper.php — Función compartida de generación de HTML para entradas PDF.
 * Incluida por: tickets.php, stripe_checkout.php, generate_ticket_pdf.php
 */

/**
 * Genera el HTML completo de la entrada (1 página por persona) para un pedido.
 * Usa el template en api/php/templates/ticket_pdf.php
 */
function buildTicketHtml(array $order, int $orderId): string
{
    $autoload = __DIR__ . '/../../../vendor/autoload.php';
    if (!file_exists($autoload))
        return '';
    require_once $autoload;

    $ticketCode = 'RCW-' . date('Y') . '-' . str_pad($orderId, 6, '0', STR_PAD_LEFT)
        . '-' . strtoupper(substr(md5($orderId . 'rcw_secret'), 0, 6));

    $typeLabel = $order['ticket_type'] === 'pase_rapido' ? 'PASE RÁPIDO' : 'ENTRADA GENERAL';
    $typeSub   = $order['ticket_type'] === 'pase_rapido'
        ? 'Acceso prioritario a todas las atracciones'
        : 'Acceso completo al parque';
    $visitDate = date('d/m/Y', strtotime($order['visit_date']));
    $dias = [
        'Monday'    => 'LUNES',   'Tuesday'  => 'MARTES',
        'Wednesday' => 'MIÉRCOLES', 'Thursday' => 'JUEVES',
        'Friday'    => 'VIERNES', 'Saturday' => 'SÁBADO', 'Sunday' => 'DOMINGO',
    ];
    $visitDay  = $dias[(new DateTime($order['visit_date']))->format('l')] ?? '';
    $createdAt = date('d/m/Y H:i', strtotime($order['created_at']));

    // QR code (endroid/qr-code v6)
    $qrImg = '';
    try {
        $qrData = json_encode([
            'code'  => $ticketCode,
            'park'  => $order['park_name'],
            'date'  => $order['visit_date'],
            'order' => $orderId,
        ]);
        $qrCode = new \Endroid\QrCode\QrCode(
            data:                 $qrData,
            encoding:             new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
            errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::High,
            size:                 160,
            margin:               6,
            roundBlockSizeMode:   \Endroid\QrCode\RoundBlockSizeMode::Margin,
            foregroundColor:      new \Endroid\QrCode\Color\Color(26, 110, 46),
            backgroundColor:      new \Endroid\QrCode\Color\Color(255, 255, 255)
        );
        $qrResult = (new \Endroid\QrCode\Writer\PngWriter())->write($qrCode);
        $qrImg = '<img src="' . $qrResult->getDataUri() . '" style="width:100px;height:100px;display:block;margin:0 auto 6px;">';
    } catch (\Exception $e) {
        try {
            $generator  = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcodeRaw = $generator->getBarcode($ticketCode, $generator::TYPE_CODE_128, 2, 50);
            $qrImg = '<img src="data:image/png;base64,' . base64_encode($barcodeRaw) . '" style="width:220px;height:50px;display:block;margin:0 auto 6px;">';
        } catch (\Exception $e2) {}
    }

    // Add-ons: etiquetas SIN emoji (dompdf no los renderiza)
    $addonLines = [];
    if (!empty($order['addon_pase_rapido'])) $addonLines[] = '[RAPIDO] Pase Rapido';
    if (!empty($order['addon_photopass']))   $addonLines[] = '[PHOTO] PhotoPass';
    if (!empty($order['addon_buffet']))      $addonLines[] = '[BUFFET] Buffet/Pulsera';
    if (!empty($order['addon_parking']))     $addonLines[] = '[P] Parking';
    $addonsHtml = implode(' &nbsp;&middot;&nbsp; ', $addonLines);

    // Desglose de precios
    $qty_      = max(1, (int)($order['quantity'] ?? 1));
    $paseOn    = !empty($order['addon_pase_rapido']);
    $photoOn   = !empty($order['addon_photopass']);
    $buffetOn  = !empty($order['addon_buffet']);
    $parkingOn = !empty($order['addon_parking']);
    $factor    = 1.0
        + ($paseOn   ? 0.50 : 0)
        + ($photoOn  ? 0.30 : 0)
        + ($buffetOn ? 0.20 : 0);
    $baseEach  = ($factor > 0) ? round((float)$order['unit_price'] / $factor, 2) : (float)$order['unit_price'];
    $parkAmt   = (float)($order['parking_price'] ?? 0);

    $breakdown = [];
    $breakdown[] = [
        'label'  => 'Entrada base (' . $qty_ . ' persona' . ($qty_ > 1 ? 's' : '') . ')',
        'detail' => number_format($baseEach, 2, ',', '.') . ' &euro; &times; ' . $qty_,
        'amount' => $baseEach * $qty_,
    ];
    if ($paseOn)   $breakdown[] = ['label' => 'Pase Rapido (+50%)',    'detail' => '', 'amount' => round($baseEach * $qty_ * 0.50, 2)];
    if ($photoOn)  $breakdown[] = ['label' => 'PhotoPass (+30%)',       'detail' => '', 'amount' => round($baseEach * $qty_ * 0.30, 2)];
    if ($buffetOn) $breakdown[] = ['label' => 'Buffet / Pulsera (+20%)', 'detail' => '', 'amount' => round($baseEach * $qty_ * 0.20, 2)];
    if ($parkingOn && $parkAmt > 0) $breakdown[] = ['label' => 'Parking (precio fijo)', 'detail' => '', 'amount' => $parkAmt];

    ob_start();
    include __DIR__ . '/../templates/ticket_pdf.php';
    return ob_get_clean();
}
