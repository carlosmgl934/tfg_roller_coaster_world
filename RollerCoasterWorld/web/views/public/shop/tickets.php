<?php
$page_css = [
    'web/css/coasters.css',
    'web/css/tickets.css',
    'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
    'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css'
];
require_once __DIR__ . '/../../partials/header.php';
$base_url = Router::getBaseUrl();
?>

<main class="container-fluid px-lg-5 my-5">

  <!-- H1 igual que el resto de la web -->
  <div class="row mb-4">
    <div class="col-12">
      <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
        <i class="fa-solid fa-ticket me-2"></i>Entradas de Parques
      </h1>
      <p class="text-center text-muted mt-2">Compra tus entradas directamente desde RollerCoasterWorld</p>
    </div>
  </div>

  <div class="row g-4">

    <!-- IZQUIERDA: Filtro búsqueda -->
    <aside class="col-12 col-lg-3">
      <div class="card shadow-sm border-0 sticky-top rounded-0" style="top:90px;z-index:1;">
        <div class="card-header bg-success text-white rounded-0">
          <h5 class="mb-0"><i class="fa-solid fa-magnifying-glass me-2"></i>Buscar</h5>
        </div>
        <div class="card-body">
          <input type="text" id="tickets-search" class="form-control shadow-sm rounded-0 border-success"
                 placeholder="Nombre del parque..." style="border-width:2px;">
          <div class="mt-3 text-muted small">
            <span id="tickets-count">0</span> parques disponibles
          </div>
        </div>
      </div>
    </aside>

    <!-- DERECHA: Grid de parques -->
    <div class="col-12 col-lg-9">
      <div class="row g-3" id="tickets-grid">
        <div class="col-12 text-center py-5 text-muted" id="tickets-loading">
          <div class="spinner-border text-success" role="status"></div>
          <p class="mt-3">Cargando parques con entradas disponibles...</p>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- MODAL DE COMPRA -->
<div class="modal fade" id="buy-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
    <div class="modal-content rounded-0 border-0 shadow">
      <div class="modal-header bg-success text-white rounded-0">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modal-park-name">Parque</h5>
          <small class="opacity-75" id="modal-park-country"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">

        <!-- Tipo de entrada -->
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Tipo de entrada</p>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <button class="type-btn btn btn-outline-success w-100 rounded-0 active py-2" data-type="entrada" id="btn-type-entrada">
              <i class="fa-solid fa-ticket d-block fs-4 mb-1"></i>
              <span class="fw-bold d-block">Entrada General</span>
              <small class="text-muted d-block" id="price-label-entrada"></small>
            </button>
          </div>
          <div class="col-6">
            <button class="type-btn btn btn-outline-success w-100 rounded-0 py-2" data-type="pase_rapido" id="btn-type-pase">
              <i class="fa-solid fa-bolt d-block fs-4 mb-1"></i>
              <span class="fw-bold d-block">Pase Rápido</span>
              <small class="text-muted d-block" id="price-label-pase"></small>
            </button>
          </div>
        </div>

        <!-- Fecha -->
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Fecha de visita</p>
        <div class="input-group shadow-sm mb-3">
          <span class="input-group-text bg-dark text-white border-secondary rounded-0"><i class="fa-regular fa-calendar"></i></span>
          <input type="text" id="modal-visit-date" class="form-control rounded-0 bg-dark text-white border-secondary" placeholder="Selecciona una fecha..." readonly style="cursor:pointer;">
        </div>

        <!-- Cantidad -->
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Número de personas</p>
        <div class="input-group mb-1 shadow-sm">
          <button class="btn btn-outline-secondary rounded-0" id="qty-minus"><i class="fa-solid fa-minus"></i></button>
          <span class="input-group-text rounded-0 fw-bold fs-5 flex-grow-1 justify-content-center bg-dark text-white border-secondary" id="qty-display">1</span>
          <button class="btn btn-outline-secondary rounded-0" id="qty-plus"><i class="fa-solid fa-plus"></i></button>
        </div>
        <small class="text-muted d-block mb-3">Máximo 10 entradas por pedido</small>

        <!-- Total -->
        <div class="card rounded-0 border-success shadow-sm">
          <div class="card-body d-flex justify-content-between align-items-center py-2 px-3">
            <span class="text-muted">Total estimado</span>
            <span class="fw-bold fs-5 text-success" id="modal-total">0.00 €</span>
          </div>
        </div>
      </div>
      <div class="modal-footer rounded-0">
        <button class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
        <?php if ($is_logged): ?>
          <button class="btn btn-success fw-bold rounded-0 px-4" id="btn-add-cart">
            <i class="fa-solid fa-cart-plus me-2"></i>Añadir al carrito
          </button>
        <?php else: ?>
          <a href="<?= Router::url('login') ?>" class="btn btn-success fw-bold rounded-0 px-4">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Inicia sesión para comprar
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">
  <div id="cart-toast" class="toast align-items-center text-white bg-success border-0 rounded-0" role="alert">
    <div class="d-flex">
      <div class="toast-body fw-semibold">
        <i class="fa-solid fa-check-circle me-2"></i>
        <span id="cart-toast-msg">Añadido al carrito</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script>
window.TICKETS_API = '<?= Router::getBaseUrl() ?>/api/php/tickets.php';
window.CARRITO_URL = '<?= Router::url('carrito') ?>';
window.IS_LOGGED   = <?= $is_logged ? 'true' : 'false' ?>;
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>
