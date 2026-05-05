<?php
/**
 * Template: Entrada PDF — RollerCoasterWorld
 * Variables inyectadas desde el código que incluye este archivo:
 *   $orderId, $ticketCode, $qrImg, $typeLabel, $typeSub
 *   $visitDate, $visitDay, $createdAt, $addonsHtml, $order[]
 *
 * Genera UNA PÁGINA POR PERSONA usando page-break-after.
 */
$qty = max(1, (int)($order['quantity'] ?? 1));
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
/* ── Reset ─────────────────────────────────────────────── */
* { margin: 0; padding: 0; box-sizing: border-box; }

/* Márgenes de página + padding extra para que nada toque el borde */
@page { margin: 18mm 16mm; }
body {
    font-family: Arial, Helvetica, sans-serif;
    background: #fff;
    color: #1a1a1a;
    font-size: 11px;
    padding: 4mm 6mm;       /* padding extra sobre el @page margin */
}

/* ── Cabecera ───────────────────────────────────────────── */
.header {
    border-bottom: 3px solid #1a6e2e;
    padding-bottom: 10px;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.logo { font-size: 19px; font-weight: bold; color: #1a1a1a; }
.logo span { color: #1a6e2e; }
.logo-sub { font-size: 7px; letter-spacing: 3px; color: #888; text-transform: uppercase; margin-top: 2px; }
.header-right { text-align: right; color: #555; font-size: 9px; line-height: 1.8; }
.header-right strong { font-size: 10px; color: #1a1a1a; }

/* ── Número de persona ──────────────────────────────────── */
.person-label {
    background: #f0f7f0;
    border: 1px solid #c3dfc3;
    border-radius: 2px;
    padding: 4px 10px;
    font-size: 9px;
    color: #1a6e2e;
    font-weight: bold;
    letter-spacing: 1px;
    display: inline-block;
    margin-bottom: 10px;
}

/* ── Badge tipo ─────────────────────────────────────────── */
.type-badge {
    display: inline-block;
    background: #1a6e2e;
    color: white;
    font-size: 8px;
    font-weight: bold;
    letter-spacing: 2px;
    padding: 3px 10px;
    border-radius: 2px;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.type-badge.pase_rapido { background: #b8860b; }

/* ── Bloque parque ──────────────────────────────────────── */
.park-block { margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #e0e0e0; }
.park-name { font-size: 20px; font-weight: bold; color: #1a1a1a; margin-bottom: 2px; word-break: break-word; }
.park-meta { font-size: 9px; color: #777; letter-spacing: 1px; }

/* ── Tabla de detalles ──────────────────────────────────── */
.details-grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; }
.details-grid td {
    padding: 8px 12px;
    border: 1px solid #e8e8e8;
    vertical-align: top;
    background: #fafafa;
    word-break: break-word;
}
.detail-label { font-size: 7px; letter-spacing: 2px; color: #999; text-transform: uppercase; margin-bottom: 3px; }
.detail-value { font-size: 13px; font-weight: bold; color: #1a1a1a; }
.detail-value.green { color: #1a6e2e; font-size: 15px; }
.detail-sub { font-size: 8px; color: #aaa; margin-top: 2px; word-break: break-word; }

/* ── Add-ons ────────────────────────────────────────────── */
.addons-row {
    background: #f0f7f0;
    border: 1px solid #c3dfc3;
    border-radius: 2px;
    padding: 8px 12px;
    margin-bottom: 14px;
    font-size: 9px;
}
.addons-title { font-size: 7px; letter-spacing: 2px; color: #1a6e2e; text-transform: uppercase; margin-bottom: 4px; }
.addons-list { font-weight: bold; color: #1a1a1a; font-size: 10px; }

/* ── Sección QR ─────────────────────────────────────────── */
.code-section {
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 12px 10px;
    text-align: center;
    margin-bottom: 12px;
}
.code-label { font-size: 7px; letter-spacing: 3px; color: #999; text-transform: uppercase; margin-bottom: 6px; }
.ticket-code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    font-weight: bold;
    color: #1a1a1a;
    letter-spacing: 2px;
    margin-top: 8px;
    word-break: break-all;
}

/* ── Footer ─────────────────────────────────────────────── */
.footer {
    padding-top: 8px;
    border-top: 1px solid #e0e0e0;
    font-size: 8px;
    color: #aaa;
    display: flex;
    justify-content: space-between;
}
.footer-valid { color: #1a6e2e; font-weight: bold; font-size: 8px; }

/* ── Salto de página ────────────────────────────────────── */
.page-break { page-break-after: always; }
</style>
</head>
<body>

<?php for ($personNum = 1; $personNum <= $qty; $personNum++): ?>

<?php if ($personNum > 1): ?><div class="page-break"></div><?php endif; ?>

<!-- CABECERA -->
<div class="header">
    <div>
        <div class="logo">Roller<span>Coaster</span>World</div>
        <div class="logo-sub">The Ultimate Thrill Experience</div>
    </div>
    <div class="header-right">
        <strong>ENTRADA OFICIAL</strong><br>
        Emitida: <?= htmlspecialchars($createdAt) ?><br>
        Pedido #<strong><?= (int)$orderId ?></strong>
    </div>
</div>

<?php if ($qty > 1): ?>
<div class="person-label">
    PERSONA <?= $personNum ?> DE <?= $qty ?>
</div>
<?php endif; ?>

<!-- PARQUE + TIPO -->
<div class="park-block">
    <div class="type-badge <?= htmlspecialchars($order['ticket_type']) ?>"><?= htmlspecialchars($typeLabel) ?></div>
    <div class="park-name"><?= htmlspecialchars($order['park_name']) ?></div>
    <div class="park-meta"><?= htmlspecialchars($order['park_location'] ?? '') ?> &middot; <?= htmlspecialchars($order['park_country']) ?></div>
</div>

<!-- DETALLES -->
<table class="details-grid">
    <tr>
        <td style="width:50%">
            <div class="detail-label">Fecha de visita</div>
            <div class="detail-value"><?= htmlspecialchars($visitDate) ?></div>
            <div class="detail-sub"><?= htmlspecialchars($visitDay) ?></div>
        </td>
        <td style="width:50%">
            <div class="detail-label">Tipo de acceso</div>
            <div class="detail-value" style="font-size:12px;"><?= htmlspecialchars($typeLabel) ?></div>
            <div class="detail-sub"><?= htmlspecialchars($typeSub) ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="detail-label">Titular</div>
            <div class="detail-value" style="font-size:11px;"><?= htmlspecialchars($order['buyer_name']) ?></div>
            <div class="detail-sub"><?= htmlspecialchars($order['buyer_email']) ?></div>
        </td>
        <td>
            <div class="detail-label">Precio del pedido</div>
            <?php foreach ($breakdown as $i => $row): ?>
                <div style="display:flex;justify-content:space-between;align-items:baseline;
                            font-size:<?= $i === 0 ? '11px' : '9px' ?>;
                            color:<?= $i === 0 ? '#1a1a1a' : '#555' ?>;
                            font-weight:<?= $i === 0 ? 'bold' : 'normal' ?>;
                            padding:<?= $i === 0 ? '0 0 3px' : '0' ?>;
                            border-bottom:<?= $i === 0 ? '1px solid #e0e0e0;margin-bottom:4px' : 'none' ?>;">
                    <span><?= htmlspecialchars($row['label']) ?>
                        <?php if ($row['detail']): ?>
                            <span style="color:#999;font-size:8px;"> (<?= $row['detail'] ?>)</span>
                        <?php endif; ?>
                    </span>
                    <span style="font-family:'Courier New',monospace;">
                        <?= number_format($row['amount'], 2, ',', '.') ?> &euro;
                    </span>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:bold;
                        color:#1a6e2e;border-top:1px solid #c3dfc3;margin-top:5px;padding-top:4px;">
                <span>TOTAL</span>
                <span><?= number_format((float)$order['price'], 2, ',', '.') ?> &euro;</span>
            </div>
        </td>
    </tr>
</table>

<!-- QR + CÓDIGO -->
<div class="code-section">
    <div class="code-label">Escanea para verificar &middot; Presenta en taquilla</div>
    <?= $qrImg ?>
    <div class="ticket-code"><?= htmlspecialchars($ticketCode) ?></div>
</div>

<!-- FOOTER -->
<div class="footer">
    <div>&bull; Personal e intransferible &nbsp;&middot;&nbsp; rollercoasterworld.com</div>
    <div class="footer-valid">&#10003; ENTRADA CONFIRMADA</div>
</div>

<?php endfor; ?>

</body>
</html>
