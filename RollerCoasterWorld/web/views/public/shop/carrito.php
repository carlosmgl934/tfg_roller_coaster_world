<?php
$page_css = ['web/css/coasters.css', 'web/css/tickets.css'];
require_once __DIR__ . '/../../partials/header.php';
if (!$is_logged) Router::redirect('login');
?>

<main class="container-fluid px-lg-5 my-5">

  <div class="row mb-4">
    <div class="col-12">
      <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
        <i class="fa-solid fa-cart-shopping me-2"></i>Mi Carrito
      </h1>
    </div>
  </div>

  <!-- Carrito vacío -->
  <div id="cart-empty" class="text-center py-5 d-none">
    <i class="fa-solid fa-cart-shopping fa-4x text-secondary mb-4 d-block opacity-50"></i>
    <h4 class="fw-bold">Tu carrito está vacío</h4>
    <p class="text-muted mb-4">Explora los parques disponibles y añade tus entradas</p>
    <a href="<?= Router::url('tickets') ?>" class="btn btn-success fw-bold rounded-0 px-5 shadow-sm">
      <i class="fa-solid fa-ticket me-2"></i>Ver entradas disponibles
    </a>
  </div>

  <!-- Carrito con items -->
  <div id="cart-content">
    <div class="row g-4">

      <!-- Tabla de items -->
      <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0 rounded-0">
          <div class="card-header bg-success text-white rounded-0 d-flex align-items-center gap-2">
            <i class="fa-solid fa-list"></i>
            <span class="fw-semibold">Artículos del carrito</span>
            <span class="badge bg-dark ms-auto" id="cart-item-count">0</span>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-dark table-hover mb-0 align-middle">
                <thead class="bg-success text-white">
                  <tr>
                    <th class="ps-3">Parque</th>
                    <th>Tipo</th>
                    <th>Fecha visita</th>
                    <th class="text-center">Uds.</th>
                    <th class="text-end">Precio/ud.</th>
                    <th class="text-end pe-3">Subtotal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="cart-tbody">
                  <tr><td colspan="7" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando...
                  </td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="mt-3 d-flex justify-content-between">
          <a href="<?= Router::url('tickets') ?>" class="btn btn-outline-secondary rounded-0 shadow-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Seguir comprando
          </a>
          <button class="btn btn-outline-danger rounded-0 shadow-sm" id="btn-clear-cart">
            <i class="fa-solid fa-trash me-1"></i>Vaciar carrito
          </button>
        </div>
      </div>

      <!-- Resumen -->
      <div class="col-12 col-lg-4">
        <div class="card shadow-sm border-0 rounded-0" style="position:sticky;top:90px;">
          <div class="card-header bg-success text-white rounded-0">
            <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-receipt me-2"></i>Resumen del pedido</h6>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2 text-muted">
              <span>Subtotal</span>
              <span id="summary-subtotal">0.00 €</span>
            </div>
            <div class="d-flex justify-content-between mb-3 text-muted">
              <span>Gastos de gestión</span>
              <span class="text-success fw-semibold">Gratis</span>
            </div>
            <div id="summary-discount-row" class="d-flex justify-content-between mb-3 text-success d-none">
              <span id="summary-coupon-label">Cupón</span>
              <span class="fw-semibold" id="summary-discount">-0.00 €</span>
            </div>
            <hr>
            <!-- Formulario Cupón -->
            <div class="mb-3">
              <div class="input-group input-group-sm">
                <input type="text" id="coupon-input" class="form-control rounded-0 border-secondary bg-dark text-white" placeholder="Código de descuento">
                <button class="btn btn-outline-success rounded-0" id="btn-apply-coupon">Aplicar</button>
              </div>
              <div id="coupon-feedback" class="small mt-2 d-none"></div>
              <button class="btn btn-link text-danger btn-sm p-0 text-decoration-none mt-1 d-none" id="btn-remove-coupon">
                <i class="fa-solid fa-trash-can me-1"></i>Quitar cupón
              </button>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-4 fw-bold fs-5">
              <span>Total</span>
              <span class="text-success" id="summary-total">0.00 €</span>
            </div>
            <a href="<?= Router::url('checkout') ?>" class="btn btn-success w-100 fw-bold rounded-0 shadow-sm py-2" id="btn-checkout">
              <i class="fa-solid fa-lock me-2"></i>Proceder al pago
            </a>
          </div>
          <div class="card-footer rounded-0 border-warning" style="background:rgba(255,193,7,.08);border-color:rgba(255,193,7,.3)!important;">
            <small class="text-warning">
              <i class="fa-solid fa-clock me-1"></i>
              Pago con tarjeta disponible próximamente. El pedido quedará pendiente de confirmación.
            </small>
          </div>
        </div>
      </div>

    </div>
  </div>

</main>

<!-- Modal Vaciar Carrito -->
<div class="modal fade" id="modal-clear-cart" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-0 border-0 shadow">
      <div class="modal-header bg-danger text-white rounded-0">
        <h5 class="modal-title fw-bold mb-0">Vaciar Carrito</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        ¿Estás seguro de que deseas eliminar todas las entradas de tu carrito? Esta acción no se puede deshacer.
      </div>
      <div class="modal-footer rounded-0">
        <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger fw-bold rounded-0 px-4" id="btn-confirm-clear-cart">Sí, vaciar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
window.TICKETS_API  = '<?= Router::getBaseUrl() ?>/api/php/tickets.php';
window.CHECKOUT_URL = '<?= Router::url('checkout') ?>';
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>
