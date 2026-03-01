<?php
require_once 'structure/header.php';

// Protección: si no hay firebase_uid en sesión, redirige a login
if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ../firebase/auth/login.php');
    exit;
}

// Opcional: mostrar datos del usuario logueado
$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid   = $_SESSION['firebase_uid'];
?>

<main style="padding: 40px 20px; max-width: 1000px; margin: 0 auto; text-align: center;">
  <h1>Bienvenido a RollerCoaster World</h1>
  
  <div style="background: #e8f5e9; padding: 30px; border-radius: 12px; margin: 30px 0;">
    <h2 style="color: #2e7d32;">¡Has iniciado sesión correctamente!</h2>
    <p style="font-size: 1.2em; margin: 20px 0;">
      Ahora puedes acceder a todas las secciones privadas.
    </p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'No disponible'); ?></p>
    <p><strong>UID:</strong> <?php echo htmlspecialchars($_SESSION['firebase_uid'] ?? 'No disponible'); ?></p>
  </div>

  <div style="margin: 40px 0;">
    <a href="profile.php" style="margin: 10px; padding: 12px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 6px;">
      Ver mi perfil
    </a>
    <a href="coasters.php" style="margin: 10px; padding: 12px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 6px;">
      Montañas Rusas
    </a>
    <a href="parks.php" style="margin: 10px; padding: 12px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 6px;">
      Parques
    </a>
    <a href="carrito.php" style="margin: 10px; padding: 12px 30px; background: #1976d2; color: white; text-decoration: none; border-radius: 6px;">
      Carrito
    </a>
  </div>

  <p style="margin-top: 50px;">
    <a href="../firebase/auth/logout.php" style="color: #d32f2f; font-weight: bold; text-decoration: none;">
      Cerrar sesión
    </a>
  </p>
</main>

<?php
require_once __DIR__ . '/structure/footer.php';
?>