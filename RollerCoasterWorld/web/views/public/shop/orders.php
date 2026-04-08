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
    <h1>Mis Pedidos</h1>
    <!-- TODO: historial de pedidos, descargar PDF de entradas -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/shop/carrito.js') ?>"></script>
