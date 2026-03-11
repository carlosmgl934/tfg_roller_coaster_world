<?php
require_once __DIR__ . '/../partials/header.php';
/** @var string $base_url */

// Protección: si no hay firebase_uid en sesión, redirige a login
if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

// Mostrar datos del usuario logueado
$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid = $_SESSION['firebase_uid'];
?>

<main style="padding: 40px 20px; max-width: 1000px; margin: 0 auto; text-align: center;">
  <h1>Bienvenido a RollerCoaster World</h1>

  <div style="background: #e8f5e9; padding: 30px; border-radius: 12px; margin: 30px 0;">
    <h2 style="color: #2e7d32;">¡Has iniciado sesión correctamente!</h2>
    <p style="font-size: 1.2em; margin: 20px 0;">
      Ahora puedes acceder a todas las secciones privadas.
    </p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
    <p><strong>UID:</strong> <?php echo htmlspecialchars($user_uid); ?></p>
  </div>

  <p style="margin-top: 50px;">
    <a href="<?= $base_url ?>/web/views/auth/logout.php"
      style="color: #d32f2f; font-weight: bold; text-decoration: none;">
      Cerrar sesión
    </a>
  </p>
</main>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>