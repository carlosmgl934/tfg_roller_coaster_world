<?php
require_once 'structure/header.php';

// Protección básica con sesión (si usas sesión PHP)
session_start();
if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ../../firebase/auth/login.php');
  exit;
}

// Opcional: verificación extra con Firebase (más segura, requiere Firebase PHP SDK)
?>
<main>
    <h1>Bienvenido a RollerCoaster World</h1>
    <p>La mejor base de datos de montañas rusas</p>
</main>

<?php
require_once 'structure/footer.php';
?>