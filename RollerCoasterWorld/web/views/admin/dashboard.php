<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<main>
    <h1>Dashboard</h1>
    <!-- TODO: contenido del dashboard -->
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Protección cliente: redirige si no hay sesión Firebase activa -->
