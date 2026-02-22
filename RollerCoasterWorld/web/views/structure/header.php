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

  <!-- Tu script de auth (ruta absoluta relativa a la raíz del proyecto) -->
  <script src="/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/js/auth.js"></script>

  <!-- Otros CSS/JS que tengas -->
</head>

<body>
  <header>
    <nav>
      <ul>
        <li><a href="index.php">Inicio</a></li>
        <li><a href="profile.php">Mi perfil</a></li>
        <li><a href="coasters.php">Montañas Rusas</a></li>
        <li><a href="parks.php">Parques</a></li>
        <li><a href="forums.php">Foros</a></li>
      </ul>
    </nav>

    <div class="user-actions">
      <?php if ($is_logged): ?>
        <span>Bienvenido</span>
        <a href="profile.php">Perfil</a>
        <a href="../firebase/auth/logout.php">Cerrar sesión</a>
      <?php else: ?>
        <a href="../firebase/auth/login.php">Iniciar sesión</a>
        <a href="../firebase/auth/register.php">Registrarse</a>
      <?php endif; ?>
    </div>
  </header>

  <!-- El resto de tu body (main, etc.) se añade en cada página -->