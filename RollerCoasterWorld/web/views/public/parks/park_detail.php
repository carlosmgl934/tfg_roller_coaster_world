<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */



$park_id = $_GET['id'] ?? null;
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/parks.css">

<main>
    <h1>Ficha de Parque</h1>
    <!-- TODO: datos del parque, fotos, mapa Leaflet, comentarios -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/parks.js"></script>
<script src="<?= $base_url ?>/web/js/map.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>