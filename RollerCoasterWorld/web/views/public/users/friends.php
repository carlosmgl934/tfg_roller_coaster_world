<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/profile.css">

<main>
    <h1>Mis Amigos</h1>
    <!-- TODO: lista de amigos, solicitudes pendientes, buscar usuarios -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/profile.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>