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

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
  window.ORDERS_API = '<?= Router::getBaseUrl() ?>/api/php/orders.php';
  window.PDF_BASE = '<?= $pdfBase ?>';
  window.TICKETS_URL = '<?= Router::url('tickets') ?>';
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>