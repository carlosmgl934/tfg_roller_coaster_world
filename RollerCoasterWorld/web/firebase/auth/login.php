<?php
require_once '../../../web/views/structure/header.php';
?>

<main style="padding: 40px 20px; max-width: 600px; margin: 0 auto;">
  <h2>Iniciar Sesión</h2>

  <form id="login-form" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 30px;">
    <input type="email" id="email" placeholder="Email" required style="padding: 12px; font-size: 1em;">
    <input type="password" id="password" placeholder="Contraseña" required style="padding: 12px; font-size: 1em;">
    <button type="button" onclick="signInWithEmail(document.getElementById('email').value, document.getElementById('password').value)" style="padding: 12px; background: #2ecc71; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em;">
      Iniciar sesión con Email
    </button>
  </form>

  <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 30px;">
    <button onclick="signInWithGoogle()" style="padding: 12px; background: #4285F4; color: white; border: none; border-radius: 6px; cursor: pointer;">
      Iniciar con Google
    </button>
    <button onclick="signInWithFacebook()" style="padding: 12px; background: #3b5998; color: white; border: none; border-radius: 6px; cursor: pointer;">
      Iniciar con Facebook
    </button>
  </div>

  <p style="text-align: center;">
    ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
  </p>

  <!-- BOTÓN PROVISIONAL DE CERRAR SESIÓN (para pruebas rápidas) -->
  <div style="margin-top: 40px; text-align: center;">
    <button onclick="signOut()" style="padding: 12px 30px; background: #e74c3c; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 1.1em;">
      Cerrar sesión (prueba)
    </button>
    <p style="font-size: 0.9em; color: #777; margin-top: 10px;">
      (Botón provisional para probar logout desde esta misma página)
    </p>
  </div>
</main>

<?php
require_once '../../../web/views/structure/footer.php';
?>