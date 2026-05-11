<?php require_once __DIR__ . '/../../routes/Router.php'; ?>
<footer class="bg-dark text-white pt-5 pb-4 mt-auto">
  <div class="container text-center text-md-start">
    <div class="row mt-3 text-center text-md-start">

      <!-- Columna 1: Brand -->
      <div class="col-md-3 col-lg-4 col-xl-3 mx-auto mb-4">
        <h5 class="text-uppercase fw-bold text-success mb-4 flex align-items-center">
          <i class="fa-solid fa-roller-coaster me-2"></i>RollerCoaster World
        </h5>
        <p class="text-white-50 text-wrap" style="font-size: 0.9rem; line-height: 1.6;">
          La base de datos hispanohablante definitiva. Explora montañas rusas, descubre parques temáticos y comparte tus
          tops con la comunidad.
        </p>
      </div>

      <!-- Columna 2: Explora -->
      <div class="col-md-2 col-lg-2 col-xl-2 mx-auto mb-4">
        <h6 class="text-uppercase fw-bold text-success mb-4">Explora</h6>
        <p>
          <a href="<?= Router::url('coaster_search') ?>" class="text-white text-decoration-none hover-success"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">Montañas Rusas</a>
        </p>
        <p>
          <a href="<?= Router::url('park_search') ?>" class="text-white text-decoration-none hover-success"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">Parques Temáticos</a>
        </p>
        <p>
          <a href="<?= Router::url('coaster_tops') ?>" class="text-white text-decoration-none hover-success"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">Tops de la Comunidad</a>
        </p>
        <p>
          <a href="<?= Router::url('forums') ?>" class="text-white text-decoration-none hover-success"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">Foros</a>
        </p>
      </div>

      <!-- Columna 3: Soporte / Legal -->
      <div class="col-md-3 col-lg-2 col-xl-2 mx-auto mb-4">
        <h6 class="text-uppercase fw-bold text-success mb-4">Soporte</h6>
        <p>
          <a href="<?= Router::url('contact') ?>" class="text-white text-decoration-none"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">Contacto</a>
        </p>
        <p>
          <a href="<?= Router::url('privacy') ?>" class="text-white text-decoration-none"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">Privacidad</a>
        </p>
      </div>

      <!-- Columna 4: Contacto & Redes Sociales -->
      <div class="col-md-4 col-lg-3 col-xl-3 mx-auto mb-md-0 mb-4">
        <h6 class="text-uppercase fw-bold text-success mb-4">Conecta</h6>
        <p class="mb-3">
          <a href="mailto:tfgrollercoaster@gmail.com"
            class="text-white text-decoration-none d-flex align-items-center justify-content-center justify-content-md-start"
            style="transition: color 0.3s;" onmouseover="this.style.color='#198754'"
            onmouseout="this.style.color='white'">
            <i class="fa-solid fa-envelope me-2"></i>tfgrollercoaster@gmail.com
          </a>
        </p>
        <div class="d-flex justify-content-center justify-content-md-start gap-4 fs-4 mt-4">
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
          &copy; <?= date("Y") ?> RollerCoaster World&trade; &reg;.<br>Todos los derechos reservados.
        </p>
      </div>
    </div>
  </div>
</footer>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<!-- Global Trip Modals -->
<?php require_once __DIR__ . '/modals/trip_modals.php'; ?>
<script src="<?= Router::asset('web/js/components/trip_modals.js') ?>?v=<?= time() ?>"></script>
</body>

</html>