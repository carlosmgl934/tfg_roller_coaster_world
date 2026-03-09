<?php
require_once __DIR__ . '/../partials/header.php';
?>

<main>
  <h2>Registrarse</h2>
  <form id="register-form">
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Contraseña" required>
    <button type="button" id="signUpWithEmail">
      Registrar con Email
    </button>
  </form>
  <button id="signInWithGoogle">Registrar con Google</button>
  <button id="signInWithFacebook()">Registrar con Facebook</button>
</main>



<?php
require_once __DIR__ . '/../partials/footer.php';
?>