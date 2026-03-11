<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */



$park_id = $_GET['id'] ?? null;
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/forums.css">

<main>
    <h1>Búsqueda de Foros</h1>

</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>