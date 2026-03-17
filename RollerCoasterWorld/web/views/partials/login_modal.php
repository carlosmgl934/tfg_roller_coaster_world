<?php
$login_msg = $login_msg ?? 'Para realizar esta acción necesitas iniciar sesión.';
?>

<div class="modal fade" id="loginModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">

      <!-- Cabecera verde -->
      <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #198754, #146c43);">
        <i class="fa-solid fa-lock mb-2" style="font-size:2rem;"></i>
        <h5 class="fw-bold mb-0">Acceso restringido</h5>
      </div>

      <div class="modal-body text-center px-4 py-4">
        <p class="text-muted mb-4"><?= htmlspecialchars($login_msg) ?></p>
        <div class="d-grid gap-2">
          <a href="<?= $base_url ?>/web/views/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
             class="btn btn-success rounded-pill fw-bold py-2">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Iniciar sesión
          </a>
          <a href="<?= $base_url ?>/web/views/auth/register.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
             class="btn btn-outline-success rounded-pill py-2">
            <i class="fa-solid fa-user-plus me-2"></i>Crear cuenta
          </a>
          <button class="btn btn-link text-muted" id="loginModal-cancel">
            Cancelar
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  (function () {
    var modalEl = document.getElementById('loginModal');
    var cancelBtn = document.getElementById('loginModal-cancel');

    function closeModal() {
      var instance = bootstrap.Modal.getInstance(modalEl);
      if (instance) instance.hide();
    }

    function cleanup() {
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
      document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    modalEl.addEventListener('hide.bs.modal', function() {
      if (document.activeElement) document.activeElement.blur();
    });
    modalEl.addEventListener('hidden.bs.modal', cleanup);
  })();
</script>