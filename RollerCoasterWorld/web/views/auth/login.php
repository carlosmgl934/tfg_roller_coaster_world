<?php
require_once __DIR__ . '/../partials/header.php';
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/login.css">

<main>
  <h2>Iniciar Sesión</h2>

  <form id="login-form">
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Contraseña" required>
    <button type="button" onclick="signInWithEmail()">
      Iniciar Sesión
    </button>
  </form>

  <div class="auth-providers">
    <button onclick="signInWithGoogle()">
      Iniciar con Google
    </button>
    <button onclick="signInWithFacebook()">
      Iniciar con Facebook
    </button>
  </div>

  <p style="text-align: center;">
    ¿No tienes cuenta? <a href="<?= $base_url ?>/web/views/auth/register.php">Regístrate aquí</a>
  </p>

  <!-- BOTÓN PROVISIONAL DE CERRAR SESIÓN (para pruebas rápidas) -->
  <div style="margin-top: 40px; text-align: center;">
    <button onclick="signOut()"
      style="padding: 12px 30px; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em;">
      Cerrar sesión (prueba)
    </button>
    <p style="font-size: 0.9em; color: #777; margin-top: 10px;">
      (Botón provisional para probar logout desde esta misma página)
    </p>
  </div>
</main>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>