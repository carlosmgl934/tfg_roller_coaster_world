<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid = $_SESSION['firebase_uid'];
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/profile.css">

<main class="perfil-container">
  <h1>Mi Perfil</h1>
  <div class="perfil-info-card">
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
    <p><strong>UID (Firebase):</strong> <?php echo htmlspecialchars($user_uid); ?></p>
    <p><strong>Estado:</strong> Logueado correctamente</p>
  </div>

  <div class="password-section">
    <button id="btn-toggle-password" class="btn-blue">
      Cambiar contraseña
    </button>

    <div id="form-password" class="password-form-box">
      <label class="input-label">Nueva contraseña</label>
      <input type="password" id="nueva-password" placeholder="Mínimo 6 caracteres" class="input-field">

      <label class="input-label">Confirmar contraseña</label>
      <input type="password" id="confirmar-password" placeholder="Repite la contraseña" class="input-field">

      <div class="button-group">
        <button id="cambiarPassword" class="btn-green">
          ✅ Guardar
        </button>
        <button id="btn-cancelar-password" class="btn-gray">
          Cancelar
        </button>
      </div>
      <p id="msg-password"></p>
    </div>
  </div>

  <div class="danger-zone">
    <h3 class="danger-title">Eliminar cuenta</h3>
    <p class="danger-desc">Esta acción es irreversible. Tu cuenta y tus datos se eliminarán permanentemente.</p>
    <button id="borrarCuenta" class="btn-red-bold">
      Eliminar mi cuenta
    </button>
  </div>

  <p class="logout-section">
    <a href="<?= $base_url ?>/web/views/auth/logout.php" class="logout-link">Cerrar sesión</a>
  </p>
</main>

<script src="<?= $base_url ?>/web/js/auth-check.js"></script>
<script src="<?= $base_url ?>/web/js/profile.js"></script>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>