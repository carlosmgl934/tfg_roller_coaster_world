<?php
// Inicia sesión (si no está ya iniciada)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Determina si el usuario está logueado
$is_logged = isset($_SESSION['firebase_uid']);
?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>RollerCoaster World</title>

  <!-- Firebase SDK - versión COMPAT (necesaria para proyectos PHP sin módulos) -->
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js"></script>

  <!-- Base URL calculada automáticamente (funciona en cualquier servidor) -->
  <?php
  $base_url = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $_SERVER['SCRIPT_NAME']);
  ?>
  <script>window.BASE_URL = '<?= $base_url ?>';</script>

  <!-- Firebase auth init (global) -->
  <script src="<?= $base_url ?>/web/js/auth.js"></script>
</head>

<body>
  <header>
    <nav>
      <ul>
        <!-- Públicas -->
        <li><a href="<?= $base_url ?>/web/views/public/index.php">Home</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/ranking.php">Ranking</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/coasters.php">Montañas Rusas</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/coaster_detail.php">Ficha Coaster</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/parks.php">Parques</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/park_detail.php">Ficha Parque</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/forums.php">Foros</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/trips.php">Viajes</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/contact.php">Contacto</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/notice.php">Avisos</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/privacy.php">Privacidad</a></li>

        <!-- Social y perfil -->
        <li><a href="<?= $base_url ?>/web/views/public/profile.php">Mi perfil</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/friends.php">Amigos</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/user_profile.php">Perfil Usuario</a></li>

        <!-- E-commerce -->
        <li><a href="<?= $base_url ?>/web/views/public/carrito.php">Carrito</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/checkout.php">Checkout</a></li>
        <li><a href="<?= $base_url ?>/web/views/public/orders.php">Mis Pedidos</a></li>

        <!-- Admin -->
        <li><a href="<?= $base_url ?>/web/views/admin/admin.php">Admin</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/dashboard.php">Dashboard</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/users.php">Usuarios (Admin)</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/coasters.php">Coasters (Admin)</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/parks.php">Parques (Admin)</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/messages.php">Mensajes (Admin)</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/photos.php">Fotos (Admin)</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/comments.php">Comentarios (Admin)</a></li>
        <li><a href="<?= $base_url ?>/web/views/admin/orders.php">Pedidos (Admin)</a></li>

        <!-- Auth -->
        <li><a href="<?= $base_url ?>/web/views/auth/login.php">Login</a></li>
        <li><a href="<?= $base_url ?>/web/views/auth/register.php">Registro</a></li>
        <li><a href="<?= $base_url ?>/web/views/auth/logout.php">Logout</a></li>
      </ul>
    </nav>

    <div class="user-actions">
      <?php if ($is_logged): ?>
        <span>Bienvenido</span>
        <a href="<?= $base_url ?>/web/views/public/profile.php">Perfil</a>
        <a href="<?= $base_url ?>/web/views/auth/logout.php">Cerrar sesión</a>
      <?php else: ?>
        <a href="<?= $base_url ?>/web/views/auth/login.php">Iniciar sesión</a>
        <a href="<?= $base_url ?>/web/views/auth/register.php">Registrarse</a>
      <?php endif; ?>
    </div>
  </header>