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

  <!-- Scripts de auth -->
  <script src="<?= $base_url ?>/web/js/auth.js"></script>
  <script src="<?= $base_url ?>/web/js/auth-check.js"></script>
</head>

<body>
  <header>
    <nav>
      <ul>
        <li><a href="<?= $base_url ?>/web/views/index.php">Inicio</a></li>
        <li><a href="<?= $base_url ?>/web/views/profile.php">Mi perfil</a></li>
        <li><a href="<?= $base_url ?>/web/views/coasters.php">Montañas Rusas</a></li>
        <li><a href="<?= $base_url ?>/web/views/parks.php">Parques</a></li>
        <li><a href="<?= $base_url ?>/web/views/forums.php">Foros</a></li>
      </ul>
    </nav>

    <div class="user-actions">
      <?php if ($is_logged): ?>
        <span>Bienvenido</span>
        <a href="<?= $base_url ?>/web/views/profile.php">Perfil</a>
        <a href="<?= $base_url ?>/web/firebase/auth/logout.php">Cerrar sesión</a>
      <?php else: ?>
        <a href="<?= $base_url ?>/web/firebase/auth/login.php">Iniciar sesión</a>
        <a href="<?= $base_url ?>/web/firebase/auth/register.php">Registrarse</a>
      <?php endif; ?>
    </div>
  </header>