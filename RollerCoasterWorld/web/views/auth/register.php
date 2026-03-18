<?php
require_once __DIR__ . '/../partials/header.php';
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/login.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<main class="login-page">

  <!-- Panel izquierdo: decorativo -->
  <div class="login-brand">
    <div class="brand-inner">
      <div class="brand-logo">
        <i class="fa-solid fa-train-tram"></i>
      </div>
      <h1 class="brand-title">RollerCoaster<br><span>World</span></h1>
      <p class="brand-sub">Únete a la mayor comunidad<br>de entusiastas de las montañas rusas.</p>
      <div class="brand-stats">
        <div class="stat-pill"><i class="fa-solid fa-bolt"></i> +12.000 coasters</div>
        <div class="stat-pill"><i class="fa-solid fa-tree-city"></i> +700 parques</div>
        <div class="stat-pill"><i class="fa-solid fa-users"></i> Comunidad activa</div>
      </div>
    </div>
  </div>

  <!-- Panel derecho: formulario -->
  <div class="login-form-panel">
    <div class="login-card">

      <h2 class="login-title">Crea tu cuenta</h2>
      <p class="login-subtitle">Regístrate gratis para acceder a todas las posibilidades que te ofrece RollerCoaster World</p>

      <!-- Formulario -->
      <form id="register-form" autocomplete="on">
        <div class="form-group mb-3">
          <label for="username"><i class="fa-solid fa-user me-2"></i>Nombre de usuario</label>
          <input type="text" id="username" placeholder="Tu usuario (ej. coasterfan02)" required autocomplete="username">
          <div id="username-feedback" class="mt-1 small fw-medium" style="display:none;"></div>
        </div>
        <div class="form-group mb-3">
          <label for="email"><i class="fa-regular fa-envelope me-2"></i>Correo electrónico</label>
          <input type="email" id="email" placeholder="tu@email.com" required autocomplete="email">
        </div>
        <div class="form-group mb-4">
          <label for="password"><i class="fa-solid fa-lock me-2"></i>Contraseña</label>
          <div class="password-wrapper">
            <input type="password" id="password" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
            <button type="button" class="toggle-password" onclick="togglePass()">
              <i class="fa-regular fa-eye" id="eye-icon"></i>
            </button>
          </div>
          <div class="password-requirements-box mt-3 p-3 rounded" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
            <p class="mb-2 fw-bold text-secondary" style="font-size: 0.85rem;"><i class="fa-solid fa-shield-halved me-2"></i>Requisitos de seguridad:</p>
            <ul id="password-reqs" class="list-unstyled small text-muted mb-0" style="margin-left: 5px;">
              <li id="req-length" class="mb-2"><i class="fa-solid fa-xmark text-danger me-2"></i>Mínimo 6 caracteres</li>
              <li id="req-upper" class="mb-2"><i class="fa-solid fa-xmark text-danger me-2"></i>Al menos 1 mayúscula (A-Z)</li>
              <li id="req-lower" class="mb-2"><i class="fa-solid fa-xmark text-danger me-2"></i>Al menos 1 minúscula (a-z)</li>
              <li id="req-number" class="mb-0"><i class="fa-solid fa-xmark text-danger me-2"></i>Al menos 1 número (0-9)</li>
            </ul>
          </div>
        </div>
        <button type="button" id="signUpWithEmail" class="btn-login">
          <i class="fa-solid fa-user-plus me-2"></i>Crear cuenta
        </button>
      </form>

      <div class="divider"><span>o regístrate con</span></div>

      <!-- Proveedores sociales -->
      <div class="social-btns">
        <button id="signInWithGoogle" class="btn-social btn-google">
          <svg width="18" height="18" viewBox="0 0 48 48" class="me-2">
            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
          </svg>
          Google
        </button>
        <button id="signInWithFacebook" class="btn-social btn-facebook">
          <i class="fa-brands fa-facebook me-2"></i>Facebook
        </button>
      </div>

      <p class="register-link">
        ¿Ya tienes cuenta?
        <?php
        $loginUrl = $base_url . '/web/views/auth/login.php';
        if (isset($_GET['redirect'])) {
          $loginUrl .= '?redirect=' . urlencode($_GET['redirect']);
        }
        ?>
        <a href="<?= $loginUrl ?>">Inicia sesión</a>
      </p>

    </div>
  </div>

</main>

<script>
function togglePass() {
  const input = document.getElementById('password');
  const icon = document.getElementById('eye-icon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'fa-regular fa-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'fa-regular fa-eye';
  }
}
</script>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>