<?php
require_once __DIR__ . '/../partials/header.php';
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/login.css">

<main>
  <h2>Iniciar Sesión</h2>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'review'): ?>
    <div class="alert alert-warning text-center mx-auto mb-4" style="max-width: 400px;" role="alert">
      Tienes que iniciar sesión para escribir una reseña
    </div>
  <?php endif; ?>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
    <div class="alert alert-success text-center mx-auto mb-4" style="max-width: 450px;" role="alert">
      <i class="fa-solid fa-circle-check me-2"></i>
      <strong>¡Cuenta creada con éxito!</strong> Ahora inicia sesión para continuar
    </div>
  <?php endif; ?>

  <form id="login-form">
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Contraseña" required>
    <button type="button" id="signInWithEmail">
      Iniciar Sesión
    </button>
  </form>

  <div class="auth-providers">
    <button id="signInWithGoogle">
      Iniciar con Google
    </button>
    <button id="signInWithFacebook">
      Iniciar con Facebook
    </button>
  </div>

  <?php
  $registerUrl = $base_url . '/web/views/auth/register.php';
  if (isset($_GET['redirect'])) {
    $registerUrl .= '?redirect=' . urlencode($_GET['redirect']);
  }
  ?>
  <p style="text-align: center;">
    ¿No tienes cuenta? <a href="<?= $registerUrl ?>">Regístrate aquí</a>
  </p>

</main>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>