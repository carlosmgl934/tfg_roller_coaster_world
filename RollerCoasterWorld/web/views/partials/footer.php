<?php require_once __DIR__ . '/../../routes/Router.php'; ?>
<footer class="bg-dark text-white pt-5 pb-4 mt-auto">
  <div class="container text-center text-md-start">
    <div class="row text-center text-md-start">

      <!-- Columna 1: Legal -->
      <div class="col-md-4 col-lg-4 col-xl-4 mx-auto mb-4">
        <h5 class="text-uppercase fw-bold text-success mb-4">Comunidad & Legal</h5>
        <p>
          <a href="<?= Router::url('contact') ?>" class="text-white text-decoration-none" style="transition: color 0.3s;"
            onmouseover="this.style.color='#198754'" onmouseout="this.style.color='white'">Contácta con nosotros</a>
        </p>
        <p>
          <a href="<?= Router::url('privacy') ?>" class="text-white text-decoration-none" style="transition: color 0.3s;"
            onmouseover="this.style.color='#198754'" onmouseout="this.style.color='white'">Privacidad</a>
        </p>
      </div>

      <!-- Columna 2: Redes Sociales -->
      <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
        <h5 class="text-uppercase fw-bold text-success mb-4">Síguenos</h5>
        <div class="d-flex justify-content-center justify-content-md-start gap-4 fs-4">
          <a href="https://x.com/CarlosCoas61432?s=20" aria-label="Twitter / X" class="text-white"
            style="transition: color 0.3s;" onmouseover="this.style.color='#1da1f2'"
            onmouseout="this.style.color='white'"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="https://www.instagram.com/carloscoasters/" aria-label="Instagram" class="text-white"
            style="transition: color 0.3s;" onmouseover="this.style.color='#e1306c'"
            onmouseout="this.style.color='white'"><i class="fa-brands fa-instagram"></i></a>
          <a href="https://www.youtube.com/@CarlosCoasters" aria-label="Youtube" class="text-white"
            style="transition: color 0.3s;" onmouseover="this.style.color='#ff0000'"
            onmouseout="this.style.color='white'"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>

    </div>

    <hr class="mb-4">

    <!-- Fila Inferior: Copyright -->
    <div class="row align-items-center">
      <div class="col-md-7 col-lg-8">
        <p class="text-muted small mb-0">
          Base de datos propia creada con fines educativos y de entretenimiento.
        </p>
      </div>
      <div class="col-md-5 col-lg-4">
        <p class="text-md-end text-center mb-0 text-white-50">
          &copy; 2026 RollerCoaster World.<br>Todos los derechos reservados.
        </p>
      </div>
    </div>
  </div>
</footer>
  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
