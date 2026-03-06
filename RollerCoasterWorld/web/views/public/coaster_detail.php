<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}

$coaster_id = $_GET['id'] ?? null;
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">

<main>
    <h1>Ficha de Montaña Rusa</h1>
    <!-- TODO: datos del coaster, valoraciones, videos -->
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>