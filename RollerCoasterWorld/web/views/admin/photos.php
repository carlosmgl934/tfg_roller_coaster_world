<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/admin.css">

<main>
    <h1>Aprobar Fotografías</h1>
    <!-- TODO: listado de fotos pendientes de aprobación, aceptar/rechazar -->
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/admin.js"></script>
