<?php
require_once __DIR__ . '/../partials/header.php';
?>

<main>
  <h2>Registrarse</h2>
  <form id="register-form">
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Contraseña" required>
    <button type="button"
      onclick="signUpWithEmail(document.getElementById('email').value, document.getElementById('password').value)">
      Registrar con Email
    </button>
  </form>
  <button onclick="signInWithGoogle()">Registrar con Google</button>
  <button onclick="signInWithFacebook()">Registrar con Facebook</button>
</main>



<?php
require_once __DIR__ . '/../partials/footer.php';
?>