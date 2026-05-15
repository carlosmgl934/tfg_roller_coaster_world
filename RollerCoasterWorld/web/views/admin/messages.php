<?php
$page_css = ['web/css/admin.css'];
require_once __DIR__ . '/../partials/header.php';

if (!$is_logged || !$is_admin) {
    Router::redirect('login');
    exit;
}
?>
<main class="container-fluid px-lg-5 pt-0 pb-5 mb-5">

    <!-- Cabecera -->
    <div class="row pt-4 mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-0">
                Gestión de Mensajes
            </h1>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===================== IZQUIERDA: Filtros ===================== -->
        <aside class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <!-- Estado -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Estado</label>
                        <select class="form-select shadow-sm rounded-0" id="filter-msg-status">
                            <option value="">Todos los mensajes</option>
                            <option value="unread" selected>No leídos</option>
                            <option value="read">Leídos</option>
                        </select>
                    </div>

                    <!-- Motivo -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Motivo</label>
                        <select class="form-select shadow-sm rounded-0" id="filter-msg-reason">
                            <option value="">Cualquier motivo</option>
                            <option value="error">Error/Bug</option>
                            <option value="suggestion">Sugerencia</option>
                            <option value="report">Reporte</option>
                            <option value="info">Información</option>
                            <option value="other">Otro</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-msg-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-msg-borrar">
                            <i class="fa-solid fa-eraser me-2"></i>Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ===================== DERECHA: Lista ===================== -->
        <div class="col-12 col-lg-9">

            <!-- Barra de búsqueda -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <div class="flex-grow-1 position-relative">
                    <input type="text" id="admin-msg-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar por usuario, email o asunto..." style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="admin-msg-count"></p>

            <div class="list-group shadow-sm rounded-0" id="admin-msg-list">
                <div class="list-group-item text-center text-muted py-5" id="admin-msg-loading">
                    <i class="fa-solid fa-circle-notch fa-spin fa-2x mb-2 d-block text-success"></i>
                    Cargando mensajes...
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-msg-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>

<!-- ===================== MODAL VER DETALLES ===================== -->
<div class="modal fade" id="modal-msg-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-envelope-open-text me-2"></i>Detalle del Mensaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-3 p-md-4 text-light">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-start mb-4 border-bottom pb-3 border-secondary gap-3">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h4 class="fw-bold mb-0 flex-grow-1 fs-5 fs-md-4" id="msg-detail-subject">Asunto</h4>
                            <span class="badge rounded-pill d-md-none" id="msg-detail-badge-mobile">Motivo</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-x-3 gap-y-2 text-muted small mt-2 mt-md-0">
                            <span id="msg-detail-user" class="me-2"><i
                                    class="fa-solid fa-user me-1 text-success opacity-75"></i>Usuario</span>
                            <span id="msg-detail-email" class="me-2"><i
                                    class="fa-solid fa-envelope me-1 text-success opacity-75"></i>Email</span>
                            <span id="msg-detail-date"><i
                                    class="fa-solid fa-calendar me-1 text-success opacity-75"></i>Fecha</span>
                        </div>
                    </div>
                    <span class="badge rounded-pill d-none d-md-inline-block" id="msg-detail-badge">Motivo</span>
                </div>

                <div class="mb-4">
                    <label class="text-uppercase text-muted small fw-bold mb-2 tracking-wider">Mensaje:</label>
                    <div class="p-3 rounded shadow-inner"
                        style="background:#0d1117; border: 1px solid #30363d; white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;"
                        id="msg-detail-body">
                        Cuerpo del mensaje...
                    </div>
                </div>

                <div id="msg-reply-alert" class="alert alert-warning border-0 rounded-1 d-none small">
                    <i class="fa-solid fa-reply me-2"></i>El usuario ha solicitado una respuesta a su correo.
                </div>
            </div>

            <div class="modal-footer border-0 px-3 px-md-4 pb-4 pt-0 d-flex flex-wrap justify-content-center justify-content-md-end gap-2"
                style="background:#161b22;">
                <button type="button"
                    class="btn btn-outline-secondary rounded-0 me-md-auto w-100 w-md-auto order-last order-md-first"
                    data-bs-dismiss="modal">Cerrar</button>

                <button type="button" class="btn btn-outline-success rounded-0 fw-bold flex-grow-1 flex-md-grow-0"
                    id="btn-toggle-read">
                    <i class="fa-solid fa-envelope-open me-2"></i>Marcar Leído
                </button>
                <button type="button" class="btn btn-success rounded-0 fw-bold flex-grow-1 flex-md-grow-0"
                    id="btn-reply-msg">
                    <i class="fa-solid fa-paper-plane me-2"></i>Responder
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold w-100 w-md-auto"
                    id="btn-delete-msg-prompt">
                    <i class="fa-solid fa-trash me-2"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL CONFIRMAR ELIMINACIÓN ===================== -->
<div class="modal fade" id="modal-delete-msg" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar Mensaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 text-dark">¿Estás seguro de que quieres eliminar este mensaje permanentemente? Esta
                    acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-msg">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= Router::asset('web/js/admin/messages.js') ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>