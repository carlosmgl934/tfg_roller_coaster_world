<?php
require_once 'structure/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ../firebase/auth/login.php');
    exit;
}

$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid   = $_SESSION['firebase_uid'];
?>

<main style="padding: 40px 20px; max-width: 800px; margin: 0 auto;">
  <h1>Mi Perfil</h1>
  <div style="background: #f8f9fa; padding: 30px; border-radius: 12px;">
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
    <p><strong>UID (Firebase):</strong> <?php echo htmlspecialchars($user_uid); ?></p>
    <p><strong>Estado:</strong> Logueado correctamente</p>
  </div>

  <p style="margin-top: 30px;">
    <a href="../firebase/auth/logout.php" style="color: #e74c3c; font-weight: bold;">Cerrar sesión</a>
  </p>
</main>

<?php
require_once '../structure/footer.php';
?>