<?php
require_once __DIR__ . '/../../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">

<main>
    <h1>Tops de Montañas Rusas</h1>
    <!-- TODO: tops globales de usuarios, top personal vs top global, podio -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>