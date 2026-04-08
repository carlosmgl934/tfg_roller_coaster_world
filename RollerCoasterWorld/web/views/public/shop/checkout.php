<?php
$page_css = ['web/css/carrito.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>
<main>
    <h1>Confirmar Pedido</h1>
    <!-- TODO: resumen carrito, total, botón confirmar → genera PDF -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/shop/carrito.js') ?>"></script>
