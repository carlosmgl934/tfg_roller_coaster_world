<?php
require_once __DIR__ . '/../partials/header.php';

if (!$is_logged || !$is_admin) {
    Router::redirect('login');
    exit;
}
?>

<link rel="stylesheet" href="<?= Router::asset('web/css/coasters.css') ?>">
<link rel="stylesheet" href="<?= Router::asset('web/css/admin.css') ?>">

<main class="container-fluid px-lg-5 my-5">

    <!-- Cabecera -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-0">
                Gestión de Parques
            </h1>
            <a href="#" class="btn btn-success fw-bold rounded-0 shadow-sm px-4" id="btn-add-park">
                <i class="fa-solid fa-plus me-2"></i>Añadir parque
            </a>
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

                    <!-- País -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-park-country">
                            <option value="">Todos los países</option>
                        </select>
                    </div>

                    <!-- Año de apertura -->
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" id="filter-park-year">
                            <option value="">Año apertura (todos)</option>
                            <?php for ($i = date('Y') + 3; $i >= 1850; $i--): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-park-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-park-borrar">
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
                    <input type="text" id="admin-park-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar parque"
                        style="border-width: 2px;">
                    <i id="admin-park-search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="admin-park-count"></p>
            <div class="list-group shadow-sm rounded-0" id="admin-park-list">
                <div class="list-group-item text-center text-muted py-5" id="admin-park-loading">
                    <i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>
                    Usa el buscador o activa un filtro para ver parques.
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-park-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>

<!-- ===================== MODAL CONFIRMAR ELIMINAR PARQUE ===================== -->
<div class="modal fade" id="modal-delete-park" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar parque
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">¿Estás seguro de que quieres eliminar:</p>
                <p class="fw-bold text-danger mb-0" id="delete-park-name">—</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold"
                    id="confirm-delete-park" data-id="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>
<!-- ===================== MODAL AÑADIR PARQUE ===================== -->
<div class="modal fade" id="modal-add-park" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 900px;">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">

            <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0">Añadir nuevo parque</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-5" style="background:#161b22;">
                <p class="text-muted small mb-4"><span class="text-danger fw-bold">*</span> Campo obligatorio</p>
                <div class="row g-4">

                    <!-- Nombre -->
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Nombre del parque <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-brands fa-fort-awesome"></i></span>
                            <input type="text" id="add-park-name" class="form-control form-control-lg rounded-0"
                                placeholder="Ej: Cedar Point"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                        </div>
                    </div>

                    <!-- País -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">País <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-earth-americas"></i></span>
                                <input type="text" id="add-park-country" autocomplete="off"
                                    class="form-control rounded-0" placeholder="Escribe para buscar..."
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                            </div>
                            <div id="ac-dropdown-park-country" class="ac-dropdown d-none"></div>
                        </div>
                    </div>

                    <!-- Localización -->
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Localización <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-location-dot"></i></span>
                            <input type="text" id="add-park-location" class="form-control rounded-0"
                                placeholder="Ej: Sandusky, Ohio"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                        </div>
                    </div>

                    <!-- Año de apertura -->
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-0">Año de apertura</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="unknown-park-year"
                                    onchange="document.getElementById('add-park-year').disabled = this.checked; if(this.checked) document.getElementById('add-park-year').value='';">
                                <label class="form-check-label text-muted small" for="unknown-park-year">Desconocido</label>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-regular fa-calendar-days"></i></span>
                            <input type="number" id="add-park-year" class="form-control rounded-0"
                                placeholder="Ej: 1964" min="1800" max="2035"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                        </div>
                    </div>

                    <!-- Montañas rusas vinculadas (desconocido) -->
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                            <i class="fa-solid fa-link me-1 text-success"></i>Vincular montañas rusas
                            <span class="badge bg-secondary fw-normal ms-2" id="park-coasters-badge">0 seleccionadas</span>
                        </label>
                        <p class="text-muted small mb-2">Selecciona las coasters que pertenecen a este parque. Las cantidades de coasters operativas y totales se calcularán automáticamente. (Solo se mostrarán las coasters cuyo parque es "Desconocido")</p>

                        <!-- Buscador de coasters desconocidas -->
                        <div style="overflow:hidden;">
                            <input type="text" id="add-park-coasters-search"
                                class="form-control rounded-0"
                                placeholder="Buscar coaster..."
                                style="border-width:2px;border-color:#30363d;background:#0d1117;color:#e6edf3;box-shadow:none;outline:0;">
                        </div>
                        <div style="height:0.5rem;"></div>

                        <div id="add-park-coasters-list"
                            style="max-height:220px; overflow-y:auto; overflow-x:hidden; background:#0d1117; border:2px solid #30363d; padding:0.5rem;">
                            <div class="text-center text-muted py-3" id="add-park-coasters-loading">
                                <div class="spinner-border spinner-border-sm text-success"></div> Cargando coasters...
                            </div>
                        </div>
                        <input type="hidden" id="add-park-coasters-ids" value="">
                    </div>

                </div>

                <!-- Mensajes -->
                <div id="add-park-messages" class="w-100 mt-4 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="add-park-error"><i class="fa-solid fa-circle-exclamation me-2"></i><span></span></div>
                    <div class="alert alert-success rounded-0 border-0 mb-0 d-none" id="add-park-success"><i class="fa-solid fa-circle-check me-2"></i><span></span></div>
                </div>
            </div>

            <div class="modal-footer border-0 px-5 pb-4 pt-2" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-0 fw-bold px-5" id="confirm-add-park">
                    <i class="fa-solid fa-plus me-2"></i>Añadir parque
                </button>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script src="<?= Router::asset('web/js/admin.js') ?>"></script>
