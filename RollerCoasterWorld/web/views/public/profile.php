<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid = $_SESSION['firebase_uid'];
?>

<main style="padding: 40px 20px; max-width: 800px; margin: 0 auto;">
  <h1>Mi Perfil</h1>
  <div style="background: #f8f9fa; padding: 30px; border-radius: 12px;">
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
    <p><strong>UID (Firebase):</strong> <?php echo htmlspecialchars($user_uid); ?></p>
    <p><strong>Estado:</strong> Logueado correctamente</p>
  </div>

  <!-- ── Cambiar contraseña ──────────────────────────────────────────── -->
  <div style="margin-top: 30px;">
    <button onclick="toggleFormPassword()"
      style="background:#3498db;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;">
      Cambiar contraseña
    </button>

    <div id="form-password"
      style="display:none; margin-top:16px; background:#f0f4ff; padding:20px; border-radius:10px; max-width:400px;">
      <label style="display:block;margin-bottom:6px;font-weight:bold;">Nueva contraseña</label>
      <input type="password" id="nueva-password" placeholder="Mínimo 6 caracteres"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-bottom:12px;box-sizing:border-box;">

      <label style="display:block;margin-bottom:6px;font-weight:bold;">Confirmar contraseña</label>
      <input type="password" id="confirmar-password" placeholder="Repite la contraseña"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:6px;margin-bottom:16px;box-sizing:border-box;">

      <button onclick="cambiarPassword()"
        style="background:#27ae60;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;margin-right:10px;">
        ✅ Guardar
      </button>
      <button onclick="toggleFormPassword()"
        style="background:#aaa;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;">
        Cancelar
      </button>
      <p id="msg-password" style="margin-top:10px;font-size:14px;"></p>
    </div>
  </div>

  <!-- ── Zona de peligro ────────────────────────────────────────────── -->
  <div style="margin-top:40px; border-top:2px solid #e74c3c; padding-top:20px;">
    <h3 style="color:#e74c3c;">Eliminar cuenta</h3>
    <p style="color:#555;">Esta acción es irreversible. Tu cuenta y tus datos se eliminarán permanentemente.</p>
    <button onclick="borrarCuenta()"
      style="background:#e74c3c;color:#fff;border:none;padding:10px 24px;border-radius:8px;cursor:pointer;font-weight:bold;">
      Eliminar mi cuenta
    </button>
  </div>

  <p style="margin-top: 30px;">
    <a href="<?= $base_url ?>/web/views/auth/logout.php" style="color: #e74c3c; font-weight: bold;">Cerrar sesión</a>
  </p>
</main>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>