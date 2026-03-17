<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/carrito.css">

<main>
    <h1>Confirmar Pedido</h1>
    <!-- TODO: resumen carrito, total, botón confirmar → genera PDF -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/carrito.js"></script>
