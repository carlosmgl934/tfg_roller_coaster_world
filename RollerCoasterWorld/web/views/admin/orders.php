<?php
$page_css = ['web/css/admin.css', 'web/css/tickets.css'];
require_once __DIR__ . '/../partials/header.php';
if (!$is_logged || !$is_admin)
  Router::redirect('home');
?>

<main class="container-fluid px-4 my-4">

  <!-- Cabecera -->
  <div class="row pt-4 mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 border-bottom pb-3">
      <div>
        <h1 class="display-6 fw-bold text-success mb-1">
          <i class="fa-solid fa-box me-2"></i>Gestión de Pedidos
        </h1>
        <p class="text-muted mb-0">Confirma o cancela los pedidos de entradas de los usuarios</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <div id="refund-alert-wrap" class="d-none">
          <div class="bg-danger text-white p-2 shadow-sm text-center"
            style="border-radius: 4px; min-width: 220px; line-height: 1.2;">
            <div class="fw-bold" style="font-size: 0.85rem;">
              <i class="fa-solid fa-circle-exclamation me-1"></i>
              <span id="admin-refunds-text">¡1 devolución pendiente!</span>
            </div>
            <div
              style="font-size: 0.6rem; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px;">
              Cancela su entrada para hacer el reembolso
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="card shadow-sm border-0 rounded-0 mb-4">
    <div class="card-header bg-success text-white rounded-0">
      <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-filter me-2"></i>Filtros</h6>
    </div>
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-sm-auto">
          <label class="form-label small text-muted fw-semibold mb-1">Estado</label>
          <select id="filter-status" class="form-select shadow-sm rounded-0">
            <option value="">Todos</option>
            <option value="confirmado" selected>Confirmados</option>
            <option value="solicitada_cancelacion">Solicitudes de cancelación</option>
            <option value="cancelado">Cancelados</option>
          </select>
        </div>
        <div class="col-12 col-sm-auto">
          <label class="form-label small text-muted fw-semibold mb-1">Fecha de visita</label>
          <input type="date" id="filter-date" class="form-control shadow-sm rounded-0">
        </div>
        <div class="col-12 col-sm-auto ms-sm-auto d-flex gap-2">
          <button class="btn btn-outline-secondary rounded-0 shadow-sm" id="btn-clear-filters">
            <i class="fa-solid fa-eraser me-1"></i>Limpiar
          </button>
          <button class="btn btn-outline-success rounded-0 shadow-sm" id="btn-refresh-orders">
            <i class="fa-solid fa-rotate-right me-1"></i>Actualizar
          </button>
        </div>
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
              <th class="ps-3">#</th>
              <th>Usuario</th>
              <th>Parque</th>
              <th>Tipo</th>
              <th>Fecha visita</th>
              <th class="text-center">Uds.</th>
              <th class="text-end">Total</th>
              <th>Estado</th>
              <th>Fecha pedido</th>
              <th class="text-center pe-3">Acciones</th>
            </tr>
          </thead>
          <tbody id="admin-orders-tbody">
            <tr>
              <td colspan="10" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando pedidos...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div id="admin-orders-empty" class="d-none text-center py-5">
    <i class="fa-solid fa-box-open fa-3x text-secondary d-block mb-3 opacity-50"></i>
    <h5 class="text-muted fw-normal">No hay pedidos con los filtros seleccionados</h5>
  </div>

</main>

<!-- Modal Confirmar Pedido -->
<div class="modal fade" id="modal-confirm-order-admin" tabindex="-1" aria-labelledby="modalConfirmAdminLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border border-success rounded-0">
      <div class="modal-header border-bottom border-secondary">
        <h5 class="modal-title text-white fw-bold" id="modalConfirmAdminLabel">
          <i class="fa-solid fa-circle-check text-success me-2"></i>Confirmar pedido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-muted">
        ¿Estás seguro de que quieres <strong class="text-success">confirmar</strong> este pedido?<br>
        <small class="text-secondary">El usuario recibirá acceso a descargar su entrada en PDF.</small>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success rounded-0 fw-bold" id="btn-do-confirm-order">
          <i class="fa-solid fa-check me-1"></i>Sí, confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Cancelar Pedido -->
<div class="modal fade" id="modal-cancel-order-admin" tabindex="-1" aria-labelledby="modalCancelAdminLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border border-danger rounded-0">
      <div class="modal-header border-bottom border-secondary">
        <h5 class="modal-title text-white fw-bold" id="modalCancelAdminLabel">
          <i class="fa-solid fa-circle-xmark text-danger me-2"></i>Cancelar pedido
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-muted">
        ¿Estás seguro de que quieres <strong class="text-danger">cancelar</strong> este pedido?<br>
        <small class="text-secondary">Esta acción no se puede deshacer.</small>
      </div>
      <div class="modal-footer border-top border-secondary">
        <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Volver</button>
        <button type="button" class="btn btn-danger rounded-0 fw-bold" id="btn-do-cancel-order">
          <i class="fa-solid fa-xmark me-1"></i>Sí, cancelar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Toast para mensajes (Admin) -->
<div class="toast-container position-fixed top-0 start-50 translate-middle-x p-3"
  style="z-index: 2000; margin-top: 20px;">
  <div id="cart-toast" class="toast align-items-center text-white border-0 rounded-0 shadow-lg" role="alert"
    aria-live="assertive" aria-atomic="true" style="min-width: 350px;">
    <div class="d-flex align-items-center p-3">
      <div class="toast-body flex-grow-1 text-center fw-medium" style="font-size: 0.95rem;">
        <i class="fa-solid fa-circle-info me-2" id="cart-toast-icon-tag"></i>
        <span id="cart-toast-msg"></span>
      </div>
      <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script>
  window.ADMIN_ORDERS_API = '<?= Router::getBaseUrl() ?>/api/php/admin/admin_orders.php';
</script>
<script src="<?= Router::asset('web/js/shop/tickets.js') ?>?v=<?= time() ?>"></script>