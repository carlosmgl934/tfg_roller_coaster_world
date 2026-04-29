<?php
$page_css = ['web/css/coasters.css', 'web/css/tickets.css'];
require_once __DIR__ . '/../../partials/header.php';
if (!$is_logged)
  Router::redirect('login');
$pdfBase = Router::getBaseUrl() . '/api/php/generate_ticket_pdf.php';
?>

<main class="container-fluid px-lg-5 my-5">

  <div class="row mb-4">
    <div class="col-12">
      <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
        <i class="fa-solid fa-ticket me-2"></i>Mis Entradas
      </h1>
    </div>
  </div>

  <!-- Tabs -->
  <div class="d-flex justify-content-center gap-2 mb-4">
    <button class="btn btn-success fw-semibold rounded-0 shadow-sm px-4" id="tab-activas" data-tab="activas">
      <i class="fa-solid fa-ticket me-1"></i> Activas
      <span class="badge bg-dark ms-1" id="count-activas">0</span>
    </button>
    <button class="btn btn-outline-secondary fw-semibold rounded-0 px-4" id="tab-pasadas" data-tab="pasadas">
      <i class="fa-regular fa-clock me-1"></i> Pasadas
      <span class="badge bg-secondary ms-1" id="count-pasadas">0</span>
    </button>
  </div>

  <!-- Loading -->
  <div id="orders-loading" class="text-center py-5">
    <div class="spinner-border text-success" role="status"></div>
    <p class="mt-3 text-muted">Cargando tus entradas...</p>
  </div>

  <!-- Listas -->
  <div id="orders-activas" class="d-none"></div>
  <div id="orders-pasadas" class="d-none"></div>

  <!-- Empty state -->
  <div id="orders-empty" class="d-none text-center py-5">
    <i class="fa-solid fa-ticket fa-4x text-secondary d-block mb-3 opacity-50"></i>
    <h4 class="fw-bold">No tienes entradas en esta sección</h4>
    <a href="<?= Router::url('tickets') ?>" class="btn btn-success fw-bold rounded-0 shadow-sm px-5 mt-3">
      <i class="fa-solid fa-ticket me-2"></i>Comprar entradas
    </a>
  </div>

</main>

<!-- Modal de Confirmación para Cancelación -->
<div class="modal fade" id="refundConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="background-color: #1a1a2e; color: #fff; border-radius: 0;">
      <div class="modal-header border-bottom border-secondary">
        <h5 class="modal-title fw-bold text-danger">
          <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirmar Solicitud
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-4 text-center">
        <p class="fs-5 mb-0">¿Estás seguro de que quieres solicitar la devolución de esta entrada?</p>
        <p class="small text-muted mt-2">Un administrador revisará tu solicitud a la mayor brevedad posible.</p>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger rounded-0 px-4 fw-bold" id="btn-confirm-refund-modal">Confirmar Solicitud</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Notificación de Reembolso -->
<div class="modal fade" id="refundNoticeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg text-white" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 0;">
      <div class="modal-body p-4 text-center">
        <div class="mb-3">
          <i class="fa-solid fa-circle-check text-success fa-4x pulse-animation"></i>
        </div>
        <h3 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif;">¡Reembolso Procesado!</h3>
        <p class="fs-5 mb-1" id="refund-notice-msg"></p>
        <p class="text-muted small">El dinero estará disponible en tu cuenta en un plazo de 1 a 4 días hábiles dependiendo de tu entidad bancaria.</p>
        <button type="button" class="btn btn-success fw-bold rounded-0 px-5 mt-3 shadow-sm" data-bs-dismiss="modal" id="btn-close-refund-notice">
          Entendido
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast para mensajes (Diseño mejorado y centrado) -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 2000; margin-top: 85px;">
  <div id="cart-toast" class="toast align-items-center text-white border-0 rounded-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 350px;">
    <div class="d-flex align-items-center p-3">
      <div class="toast-body flex-grow-1 text-center fw-medium" style="font-size: 0.95rem;">
        <i class="fa-solid fa-circle-info me-2" id="cart-toast-icon-tag"></i>
        <span id="cart-toast-msg"></span>
      </div>
      <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
  window.ORDERS_API = '<?= Router::getBaseUrl() ?>/api/php/orders.php';
  window.PDF_BASE = '<?= $pdfBase ?>';
  window.TICKETS_URL = '<?= Router::url('tickets') ?>';
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>