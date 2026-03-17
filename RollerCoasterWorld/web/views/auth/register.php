<?php
require_once __DIR__ . '/../partials/header.php';
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/login.css">

<main>
  <h2>Registrarse</h2>
  <form id="register-form">
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Contraseña" required>
    <button type="button" id="signUpWithEmail">
      Registrarse
    </button>
  </form>

  <div class="auth-providers">
    <button id="signInWithGoogle">
      Registrar con Google
    </button>
    <button id="signInWithFacebook">
      Registrar con Facebook
    </button>
  </div>

  <?php
  $loginUrl = $base_url . '/web/views/auth/login.php';
  if (isset($_GET['redirect'])) {
    $loginUrl .= '?redirect=' . urlencode($_GET['redirect']);
  }
  ?>
  <p style="text-align: center;">
    ¿Ya tienes cuenta? <a href="<?= $loginUrl ?>">Inicia sesión aquí</a>
  </p>
</main>



<?php
require_once __DIR__ . '/../partials/footer.php';
?>