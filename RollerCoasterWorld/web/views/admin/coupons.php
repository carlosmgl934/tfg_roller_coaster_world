<?php
$page_css = ['web/css/admin.css'];
require_once __DIR__ . '/../partials/header.php';
// HIDDEN-TFG-START: Bloquear acceso directo a la vista de tienda
Router::redirect('home');
// HIDDEN-TFG-END
if (!$is_logged || !$is_admin)
  Router::redirect('home');
?>

<main class="container-fluid px-4 my-4">
  <!-- Cabecera -->
  <div class="row pt-4 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 border-bottom pb-3">
      <div>
        <h1 class="display-6 fw-bold text-success mb-1">
          <i class="fa-solid fa-ticket me-2"></i>Gestión de Cupones
        </h1>
        <p class="text-muted mb-0">Crea y administra códigos de descuento para las compras de entradas</p>
      </div>
      <div>
        <button class="btn btn-success fw-bold rounded-0 shadow-sm" data-bs-toggle="modal"
          data-bs-target="#modal-coupon">
          <i class="fa-solid fa-plus me-1"></i>Nuevo Cupón
        </button>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card shadow-sm border-0 rounded-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-dark table-hover align-middle mb-0">
          <thead class="bg-success text-white">
            <tr>
              <th class="ps-3">Código</th>
              <th>Descripción</th>
              <th>Descuento</th>
              <th>Usos</th>
              <th>Límite</th>
              <th>Expiración</th>
              <th>Estado</th>
              <th class="text-center pe-3">Acciones</th>
            </tr>
          </thead>
          <tbody id="admin-coupons-tbody">
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando cupones...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="admin-coupons-empty" class="d-none text-center py-5">
    <i class="fa-solid fa-tag fa-3x text-secondary d-block mb-3 opacity-50"></i>
    <h5 class="text-muted fw-normal">No hay cupones creados</h5>
  </div>
</main>

<!-- Modal Crear/Editar Cupón -->
<div class="modal fade" id="modal-coupon" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-0 border-0 shadow">
      <div class="modal-header bg-success text-white rounded-0">
        <h5 class="modal-title fw-bold mb-0">Nuevo Cupón</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <form id="form-coupon">
          <div class="mb-3">
            <label class="form-label text-muted small fw-semibold">Código *</label>
            <input type="text" id="coupon-code" class="form-control rounded-0" placeholder="Ej: VERANO2026" required>
            <small class="text-muted">Solo letras y números, sin espacios.</small>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted small fw-semibold">Descripción</label>
            <input type="text" id="coupon-desc" class="form-control rounded-0" placeholder="Ej: Descuento de verano"
              required>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted small fw-semibold">Descuento (%) *</label>
            <input type="number" id="coupon-value" class="form-control rounded-0" min="1" max="100" placeholder="15"
              required>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label text-muted small fw-semibold">Límite de usos</label>
              <input type="number" id="coupon-max-uses" class="form-control rounded-0" placeholder="Ilimitado">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small fw-semibold">Fecha expiración</label>
              <input type="date" id="coupon-expires" class="form-control rounded-0">
            </div>
          </div>
          <div class="mt-3 form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="coupon-active" checked>
            <label class="form-check-label text-muted small fw-semibold" for="coupon-active">Cupón activo
              inmediatamente</label>
          </div>
        </form>
      </div>
      <div class="modal-footer rounded-0">
        <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-bold rounded-0 px-4" id="btn-save-coupon">Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script>
  window.ADMIN_COUPONS_API = '<?= Router::getBaseUrl() ?>/api/php/admin/admin_coupons.php';
</script>
<script src="<?= Router::asset('web/js/admin/admin_coupons.js') ?>?v=<?= time() ?>"></script>