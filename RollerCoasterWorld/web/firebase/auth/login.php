<?php
require_once '../../../web/views/structure/header.php';
?>

<main>
  <h2>Iniciar Sesión</h2>
  <form id="login-form">
    <input type="email" id="email" placeholder="Email" required>
    <input type="password" id="password" placeholder="Contraseña" required>
    <button type="button" onclick="signInWithEmail(document.getElementById('email').value, document.getElementById('password').value)">Login con Email</button>
  </form>
  <button onclick="signInWithGoogle()">Login con Google</button>
  <button onclick="signInWithFacebook()">Login con Facebook</button>
</main>



<?php
require_once '../../../web/views/structure/footer.php';
?>