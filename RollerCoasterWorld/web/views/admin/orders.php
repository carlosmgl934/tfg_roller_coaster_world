<?php
$page_css = ['web/css/admin.css'];
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>
<main>
    <h1>Historial de Todos los Pedidos</h1>
    <!-- TODO: listado de todos los pedidos de todos los usuarios, filtros por estado/fecha/usuario -->
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/admin/admin.js') ?>"></script>
