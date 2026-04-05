<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/ranking.css">

<main>
    <h1>Ranking Global de Montañas Rusas</h1>
    <!-- TODO: top global con filtros por país, parque, tipo, fabricante -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/coasters/ranking.js') ?>"></script>