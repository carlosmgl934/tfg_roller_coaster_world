<?php
require_once __DIR__ . '/../../partials/header.php';

// Perfil público de otro usuario — no requiere login
$user_id = $_GET['id'] ?? null;
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/profile.css">

<main>
    <h1>Perfil de Usuario</h1>
    <!-- TODO: bio, avatar, top personal, lista de amigos visibles -->
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/profile.js"></script>