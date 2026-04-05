<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">

<main>
    <h1>Reseñas de Montañas Rusas</h1>
    <!-- TODO: listado de reseñas recientes de todos los coasters, con filtros -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/coasters/coasters.js') ?>"></script>
