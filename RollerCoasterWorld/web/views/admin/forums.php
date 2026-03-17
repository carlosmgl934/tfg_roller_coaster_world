<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<main>
    <h1>Gestión de Foros</h1>
    <!-- TODO: contenido de gestión de foros -->
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<!-- Protección cliente: redirige si no hay sesión Firebase activa -->

