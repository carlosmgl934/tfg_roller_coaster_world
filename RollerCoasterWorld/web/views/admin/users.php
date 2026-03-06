<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<main>
    <h1>Gestión de Usuarios</h1>
    <!-- TODO: contenido de gestión de usuarios -->
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Protección cliente: redirige si no hay sesión Firebase activa -->
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>