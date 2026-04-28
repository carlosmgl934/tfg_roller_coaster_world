<?php
$page_css = ['web/css/coasters.css', 'web/css/tickets.css'];
require_once __DIR__ . '/../../partials/header.php';
if (!$is_logged) Router::redirect('login');
?>

<main class="container-fluid px-lg-5 my-5">

  <!-- Pantalla éxito (oculta al inicio) -->
  <div id="checkout-success" class="d-none">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
          <i class="fa-solid fa-circle-check me-2"></i>¡Pedido recibido!
        </h1>
      </div>
    </div>
    <div class="row justify-content-center">
      <div class="col-12 col-md-7">
        <div class="card shadow-sm border-0 rounded-0">
          <div class="card-header bg-success text-white rounded-0 text-center">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-ticket me-2"></i>Resumen del pedido</h5>
          </div>
          <div class="card-body text-center py-4">
            <p class="text-muted mb-2">Tu pedido ha sido registrado correctamente y está</p>
            <h4 class="text-warning fw-bold mb-3"><i class="fa-solid fa-hourglass-half me-2"></i>Pendiente de confirmación</h4>
            <div class="bg-dark border border-success rounded-0 py-3 px-4 mb-4 d-inline-block">
              <small class="text-muted d-block mb-1">Referencia del pedido</small>
              <span class="fw-bold fs-5 text-success font-monospace" id="success-order-ref">—</span>
            </div>
            <p class="text-muted small mb-4">Recibirás tu entrada digital en cuanto un administrador confirme el pedido.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
              <a href="<?= Router::url('orders') ?>" class="btn btn-success fw-bold rounded-0 shadow-sm px-4">
                <i class="fa-solid fa-ticket me-2"></i>Ver mis pedidos
              </a>
              <a href="<?= Router::url('tickets') ?>" class="btn btn-outline-secondary rounded-0 px-4">
                <i class="fa-solid fa-arrow-left me-2"></i>Seguir comprando
              </a>
            </div>
          </div>
          <div class="card-footer rounded-0 border-warning text-center" style="background:rgba(255,193,7,.08);border-color:rgba(255,193,7,.3)!important;">
            <small class="text-warning">
              <i class="fa-solid fa-credit-card me-1"></i>
              El pago con tarjeta estará disponible próximamente. Tu reserva ya está registrada.
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Formulario de checkout -->
  <div id="checkout-form-wrap">
    <div class="row mb-4">
      <div class="col-12">
        <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
          <i class="fa-solid fa-lock me-2"></i>Confirmar Pedido
        </h1>
      </div>
    </div>

    <div class="row g-4">

      <!-- Columna izquierda -->
      <div class="col-12 col-lg-7">

        <!-- Resumen del carrito -->
        <div class="card shadow-sm border-0 rounded-0 mb-4">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-list me-2"></i>Resumen de tu compra</h6>
          </div>
          <div class="card-body" id="checkout-items-list">
            <div class="text-center py-3 text-muted">
              <div class="spinner-border spinner-border-sm text-success me-2"></div> Cargando...
            </div>
          </div>
        </div>

        <!-- Datos personales -->
        <div class="card shadow-sm border-0 rounded-0">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-user me-2"></i>Datos del comprador</h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-sm-6">
                <label class="form-label text-muted small fw-semibold">Nombre completo</label>
                <input type="text" id="checkout-name" class="form-control shadow-sm rounded-0" placeholder="Tu nombre" required>
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label text-muted small fw-semibold">Email de contacto</label>
                <input type="email" id="checkout-email" class="form-control shadow-sm rounded-0"
                       value="<?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>" required>
              </div>
            </div>
          </div>
          <div class="card-footer rounded-0 border-warning" style="background:rgba(255,193,7,.08);border-color:rgba(255,193,7,.3)!important;">
            <small class="text-warning">
              <i class="fa-solid fa-triangle-exclamation me-1"></i>
              <strong>Pago simulado:</strong> Al confirmar, el pedido quedará pendiente de validación por el equipo.
            </small>
          </div>
        </div>
      </div>

      <!-- Columna derecha: total -->
      <div class="col-12 col-lg-5">
        <div class="card shadow-sm border-0 rounded-0" style="position:sticky;top:90px;">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-receipt me-2"></i>Total del pedido</h6>
          </div>
          <div class="card-body">
            <div id="checkout-breakdown" class="mb-3"></div>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
              <span>Total a pagar</span>
              <span class="text-success" id="checkout-grand-total">0.00 €</span>
            </div>
            <button class="btn btn-success w-100 fw-bold rounded-0 shadow-sm py-2 fs-5" data-bs-toggle="modal" data-bs-target="#modal-confirm-order">
              <i class="fa-solid fa-check-circle me-2"></i>Confirmar pedido
            </button>
            <a href="<?= Router::url('carrito') ?>" class="btn btn-outline-secondary w-100 rounded-0 mt-2">
              <i class="fa-solid fa-arrow-left me-1"></i>Volver al carrito
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

</main>

<!-- Modal Confirmar Pedido -->
<div class="modal fade" id="modal-confirm-order" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-0 border-0 shadow">
      <div class="modal-header bg-success text-white rounded-0">
        <h5 class="modal-title fw-bold mb-0">Confirmar Pedido</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        ¿Estás seguro de que deseas confirmar este pedido? Se generarán las entradas en tu cuenta.
      </div>
      <div class="modal-footer rounded-0">
        <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-bold rounded-0 px-4" id="btn-modal-confirm-order">Sí, confirmar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
window.TICKETS_API = '<?= Router::getBaseUrl() ?>/api/php/tickets.php';
window.ORDERS_URL  = '<?= Router::url('orders') ?>';
window.CARRITO_URL = '<?= Router::url('carrito') ?>';
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>
