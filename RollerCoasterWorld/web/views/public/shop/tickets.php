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

<!-- MODAL DE COMPRA (2 pasos) -->
<div class="modal fade" id="buy-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:600px;">
    <div class="modal-content rounded-0 border-0 shadow">

      <!-- Header -->
      <div class="modal-header bg-success text-white rounded-0 pb-2">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modal-park-name">Parque</h5>
          <small class="opacity-75" id="modal-park-country"></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <!-- Stepper indicator -->
      <div class="d-flex border-bottom" style="background:#1a1f2e;">
        <div class="step-tab flex-fill text-center py-2 active" id="step-tab-1" style="font-size:.8rem;cursor:default;">
          <span class="step-num me-1">1</span><span class="d-none d-sm-inline">Fecha y entradas</span>
        </div>
        <div class="step-tab flex-fill text-center py-2" id="step-tab-2" style="font-size:.8rem;cursor:default;">
          <span class="step-num me-1">2</span><span class="d-none d-sm-inline">Complementos</span>
        </div>
      </div>

      <!-- ── PASO 1 ── -->
      <div id="step-1" class="modal-body p-4">
        <!-- Fecha -->
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Fecha de visita</p>
        <div class="input-group shadow-sm mb-4">
          <span class="input-group-text bg-dark text-white border-secondary rounded-0"><i
              class="fa-regular fa-calendar"></i></span>
          <input type="text" id="modal-visit-date" class="form-control rounded-0 bg-dark text-white border-secondary"
            placeholder="Selecciona una fecha..." readonly style="cursor:pointer;">
        </div>

        <!-- Cantidad -->
        <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.05em;">Número de personas</p>
        <div class="input-group mb-1 shadow-sm">
          <button class="btn btn-outline-secondary rounded-0" id="qty-minus"><i class="fa-solid fa-minus"></i></button>
          <span
            class="input-group-text rounded-0 fw-bold fs-5 flex-grow-1 justify-content-center bg-dark text-white border-secondary"
            id="qty-display">1</span>
          <button class="btn btn-outline-secondary rounded-0" id="qty-plus"><i class="fa-solid fa-plus"></i></button>
        </div>
        <small class="text-muted d-block mb-4">Máximo 10 entradas por pedido</small>

        <!-- Precio base -->
        <div class="card rounded-0 border-success shadow-sm">
          <div class="card-body d-flex justify-content-between align-items-center py-2 px-3">
            <span class="text-muted">Entrada general <span id="step1-qty-label" class="text-white">× 1</span></span>
            <span class="fw-bold fs-5 text-success" id="step1-total">0.00 €</span>
          </div>
        </div>
      </div>

      <!-- ── PASO 2 ── -->
      <div id="step-2" class="modal-body p-4 d-none">
        <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:.05em;">Complementos
          opcionales</p>

        <div class="row g-3 mb-4" id="addons-grid">

          <!-- Pase Rápido -->
          <div class="col-6">
            <label class="addon-card card rounded-0 border-secondary h-100 p-3" id="addon-card-pase"
              style="cursor:pointer;">
              <input type="checkbox" class="addon-check d-none" id="addon-pase" data-key="pase_rapido">
              <div class="d-flex align-items-start gap-2">
                <i class="fa-solid fa-bolt text-warning mt-1" style="font-size:1.2rem;"></i>
                <div>
                  <div class="fw-bold text-white" style="font-size:.9rem;">Pase Rápido</div>
                  <div class="text-muted" style="font-size:.75rem;">Salta las colas en todas las atracciones</div>
                  <div class="text-warning fw-bold mt-1 addon-price" id="price-pase">0.00 €</div>
                </div>
              </div>
            </label>
          </div>

          <!-- PhotoPass -->
          <div class="col-6">
            <label class="addon-card card rounded-0 border-secondary h-100 p-3" id="addon-card-photo"
              style="cursor:pointer;">
              <input type="checkbox" class="addon-check d-none" id="addon-photo" data-key="photopass">
              <div class="d-flex align-items-start gap-2">
                <i class="fa-solid fa-camera text-info mt-1" style="font-size:1.2rem;"></i>
                <div>
                  <div class="fw-bold text-white" style="font-size:.9rem;">PhotoPass</div>
                  <div class="text-muted" style="font-size:.75rem;">Fotos en todas las atracciones totalmente gratis
                  </div>
                  <div class="text-info fw-bold mt-1 addon-price" id="price-photo">0.00 €</div>
                </div>
              </div>
            </label>
          </div>

          <!-- Buffet -->
          <div class="col-6">
            <label class="addon-card card rounded-0 border-secondary h-100 p-3" id="addon-card-buffet"
              style="cursor:pointer;">
              <input type="checkbox" class="addon-check d-none" id="addon-buffet" data-key="buffet">
              <div class="d-flex align-items-start gap-2">
                <i class="fa-solid fa-utensils text-success mt-1" style="font-size:1.2rem;"></i>
                <div>
                  <div class="fw-bold text-white" style="font-size:.9rem;">Buffet / Pulsera</div>
                  <div class="text-muted" style="font-size:.75rem;">Come gratis 1 vez por hora en el parque</div>
                  <div class="text-success fw-bold mt-1 addon-price" id="price-buffet">0.00 €</div>
                </div>
              </div>
            </label>
          </div>

          <!-- Parking -->
          <div class="col-6">
            <label class="addon-card card rounded-0 border-secondary h-100 p-3" id="addon-card-parking"
              style="cursor:pointer;">
              <input type="checkbox" class="addon-check d-none" id="addon-parking" data-key="parking">
              <div class="d-flex align-items-start gap-2">
                <i class="fa-solid fa-square-parking text-primary mt-1" style="font-size:1.2rem;"></i>
                <div>
                  <div class="fw-bold text-white" style="font-size:.9rem;">Parking</div>
                  <div class="text-muted" style="font-size:.75rem;">Aparcamiento reservado en el recinto</div>
                  <div class="text-primary fw-bold mt-1 addon-price" id="price-parking">0.00 €</div>
                </div>
              </div>
            </label>
          </div>

        </div>

        <!-- Resumen desglosado -->
        <div class="card rounded-0 border-0 shadow-sm" style="background:#1a1f2e;">
          <div class="card-body py-3 px-3">
            <div class="d-flex justify-content-between mb-1 small text-muted">
              <span>Entradas (<span id="s-qty">1</span> pers.)</span>
              <span id="s-base">0.00 €</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small addon-row-pase d-none">
              <span class="text-warning"><i class="fa-solid fa-bolt me-1"></i>Pase Rápido</span>
              <span class="text-warning" id="s-pase">0.00 €</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small addon-row-photo d-none">
              <span class="text-info"><i class="fa-solid fa-camera me-1"></i>PhotoPass</span>
              <span class="text-info" id="s-photo">0.00 €</span>
            </div>
            <div class="d-flex justify-content-between mb-1 small addon-row-buffet d-none">
              <span class="text-success"><i class="fa-solid fa-utensils me-1"></i>Buffet</span>
              <span class="text-success" id="s-buffet">0.00 €</span>
            </div>
            <div class="d-flex justify-content-between mb-2 small addon-row-parking d-none">
              <span class="text-primary"><i class="fa-solid fa-square-parking me-1"></i>Parking</span>
              <span class="text-primary" id="s-parking">0.00 €</span>
            </div>
            <hr class="my-2 border-secondary">
            <div class="d-flex justify-content-between fw-bold">
              <span class="text-white">Total</span>
              <span class="text-success fs-5" id="modal-total">0.00 €</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer contextual -->
      <div class="modal-footer rounded-0 justify-content-between">
        <!-- Paso 1 -->
        <div id="footer-step-1" class="d-flex w-100 justify-content-between">
          <button class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
          <?php if ($is_logged): ?>
            <button class="btn btn-success fw-bold rounded-0 px-4" id="btn-next-step">
              Siguiente <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
          <?php else: ?>
            <a href="<?= Router::url('login') ?>" class="btn btn-success fw-bold rounded-0 px-4">
              <i class="fa-solid fa-right-to-bracket me-2"></i>Inicia sesión para comprar
            </a>
          <?php endif; ?>
        </div>
        <!-- Paso 2 -->
        <div id="footer-step-2" class="d-flex w-100 justify-content-between d-none">
          <button class="btn btn-outline-secondary rounded-0" id="btn-prev-step">
            <i class="fa-solid fa-arrow-left me-2"></i>Atrás
          </button>
          <button class="btn btn-success fw-bold rounded-0 px-4" id="btn-add-cart">
            <i class="fa-solid fa-cart-plus me-2"></i>Añadir al carrito
          </button>
        </div>
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
  window.IS_LOGGED = <?= $is_logged ? 'true' : 'false' ?>;
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>