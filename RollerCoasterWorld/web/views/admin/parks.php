<?php
$page_css = ['web/css/coasters.css', 'web/css/admin.css'];
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
            <h1 class="display-6 fw-bold text-success mb-0">Gestión de Parques</h1>
            <a href="#" class="btn btn-success fw-bold rounded-0 shadow-sm px-4" id="btn-add-park">
                <i class="fa-solid fa-plus me-2"></i>Añadir parque
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- Filtros -->
        <aside class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top:90px;z-index:1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-park-country">
                            <option value="">Todos los países</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" id="filter-park-year">
                            <option value="">Año apertura (todos)</option>
                            <?php for ($i = date('Y') + 3; $i >= 1850; $i--): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-park-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0"
                            id="btn-park-borrar">
                            <i class="fa-solid fa-eraser me-2"></i>Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Lista -->
        <div class="col-12 col-lg-9">
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <div class="flex-grow-1 position-relative">
                    <input type="text" id="admin-park-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0" placeholder="Buscar parque"
                        style="border-width:2px;">
                    <i id="admin-park-search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right:14px;top:50%;transform:translateY(-50%);cursor:default;"></i>
                </div>
            </div>
            <p class="text-muted fw-semibold mb-2 small" id="admin-park-count"></p>
            <div class="list-group shadow-sm rounded-0" id="admin-park-list">
                <div class="list-group-item text-center text-muted py-5">
                    <i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>
                    Usa el buscador o activa un filtro para ver parques.
                </div>
            </div>
            <div class="d-flex justify-content-center mt-4" id="admin-park-pagination"></div>
        </div>

    </div>
</main>

<!-- ===== MODAL ELIMINAR ===== -->
<div class="modal fade" id="modal-delete-park" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar parque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">¿Estás seguro de que quieres eliminar:</p>
                <p class="fw-bold text-danger mb-0" id="delete-park-name">—</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-park" data-id="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL AÑADIR ===== -->
<div class="modal fade" id="modal-add-park" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:900px;">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-bold mb-0">Añadir nuevo parque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5" style="background:#161b22;">
                <p class="text-muted small mb-4"><span class="text-danger fw-bold">*</span> Campo obligatorio</p>
                <div class="row g-4">

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Nombre del parque
                            <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success"
                                style="background:#0d1117;border-width:2px;color:#198754;border-right:none;"><i
                                    class="fa-brands fa-fort-awesome"></i></span>
                            <input type="text" id="add-park-name" class="form-control form-control-lg rounded-0"
                                placeholder="Ej: Cedar Point"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">País <span
                                class="text-danger">*</span></label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success"
                                    style="background:#0d1117;border-width:2px;color:#198754;border-right:none;"><i
                                        class="fa-solid fa-earth-americas"></i></span>
                                <input type="text" id="add-park-country" autocomplete="off"
                                    class="form-control rounded-0" placeholder="Escribe para buscar..."
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                            </div>
                            <div id="ac-dropdown-park-country" class="ac-dropdown d-none"></div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Localización <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success"
                                style="background:#0d1117;border-width:2px;color:#198754;border-right:none;"><i
                                    class="fa-solid fa-location-dot"></i></span>
                            <input type="text" id="add-park-location" class="form-control rounded-0"
                                placeholder="Ej: Sandusky, Ohio"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-0">Año de
                                apertura</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input admin-toggle" type="checkbox" role="switch"
                                    id="unknown-park-year"
                                    onchange="document.getElementById('add-park-year').disabled=this.checked;if(this.checked)document.getElementById('add-park-year').value='';">
                                <label class="form-check-label text-muted small"
                                    for="unknown-park-year">Desconocido</label>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success"
                                style="background:#0d1117;border-width:2px;color:#198754;border-right:none;"><i
                                    class="fa-regular fa-calendar-days"></i></span>
                            <input type="number" id="add-park-year" class="form-control rounded-0"
                                placeholder="Ej: 1964" min="1800" max="2035"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Web oficial</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success"
                                style="background:#0d1117;border-width:2px;color:#198754;border-right:none;"><i
                                    class="fa-solid fa-globe"></i></span>
                            <input type="url" id="add-park-website" class="form-control rounded-0"
                                placeholder="https://www.cedarpoint.com"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Precio entrada
                            (€)</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-success"
                                style="background:#0d1117;border-width:2px;color:#198754;border-right:none;"><i
                                    class="fa-solid fa-euro-sign"></i></span>
                            <input type="number" id="add-park-price" class="form-control rounded-0"
                                placeholder="Ej: 49.99" min="0" step="0.01"
                                style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Imagen del
                            parque</label>
                        <div id="add-park-dropzone" onclick="document.getElementById('add-park-image').click()" style="border:2px dashed #198754;background:rgba(25,135,84,0.05);cursor:pointer;
                                   padding:1rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:0.5rem;
                                   transition:all .2s ease;height:46px;justify-content:center;overflow:hidden;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-cloud-arrow-up text-success"></i>
                                <span class="text-white small fw-bold" id="add-park-dropzone-text">Subir imagen</span>
                            </div>
                        </div>
                        <input type="file" id="add-park-image" accept="image/*" class="d-none">
                        <div id="add-park-preview-container" class="mt-2 d-none">
                            <img id="add-park-preview" src="" class="img-thumbnail bg-dark border-secondary"
                                style="max-height:100px;">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                            <i class="fa-solid fa-link me-1 text-success"></i>Vincular montañas rusas
                            <span class="badge bg-secondary fw-normal ms-2" id="park-coasters-badge">0
                                seleccionadas</span>
                        </label>
                        <p class="text-muted small mb-2">Coasters sin parque asignado ("Desconocido") que puedes asignar
                            a este parque.</p>
                        <div style="overflow:hidden;">
                            <input type="text" id="add-park-coasters-search" class="form-control rounded-0"
                                placeholder="Buscar coaster..."
                                style="border-width:2px;border-color:#30363d;background:#0d1117;color:#e6edf3;box-shadow:none;outline:0;">
                        </div>
                        <div style="height:0.5rem;"></div>
                        <div id="add-park-coasters-list"
                            style="max-height:220px;overflow-y:auto;overflow-x:hidden;background:#0d1117;border:2px solid #30363d;padding:0.5rem;">
                            <div class="text-center text-muted py-3">
                                <div class="spinner-border spinner-border-sm text-success"></div> Cargando coasters...
                            </div>
                        </div>
                        <input type="hidden" id="add-park-coasters-ids" value="">
                    </div>

                </div>

                <div id="add-park-messages" class="w-100 mt-4 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="add-park-error"><i
                            class="fa-solid fa-circle-exclamation me-2"></i><span></span></div>
                    <div class="alert alert-success rounded-0 border-0 mb-0 d-none" id="add-park-success"><i
                            class="fa-solid fa-circle-check me-2"></i><span></span></div>
                </div>
            </div>
            <div class="modal-footer border-0 px-5 pb-4 pt-2" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success rounded-0 fw-bold px-5" id="confirm-add-park">
                    <i class="fa-solid fa-plus me-2"></i>Añadir parque
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL EDITAR ===== -->
<div class="modal fade" id="modal-edit-park" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:860px;">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <div class="modal-header bg-primary text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <h5 class="modal-title fw-bold mb-0">Editar parque</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-5" style="background:#161b22;">
                <input type="hidden" id="edit-park-id">
                <div class="row g-4">

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Nombre del parque
                            <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-primary"
                                style="background:#0d1117;border-width:2px;color:#0d6efd;border-right:none;"><i
                                    class="fa-brands fa-fort-awesome"></i></span>
                            <input type="text" id="edit-park-name" class="form-control form-control-lg rounded-0"
                                placeholder="Ej: Cedar Point"
                                style="border-width:2px;border-color:#0d6efd;background:#0d1117;color:#e6edf3;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">País <span
                                class="text-danger">*</span></label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-primary"
                                    style="background:#0d1117;border-width:2px;color:#0d6efd;border-right:none;"><i
                                        class="fa-solid fa-earth-americas"></i></span>
                                <input type="text" id="edit-park-country" autocomplete="off"
                                    class="form-control rounded-0" placeholder="Escribe para buscar..."
                                    style="border-width:2px;border-color:#0d6efd;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                            </div>
                            <div id="ac-dropdown-edit-park-country" class="ac-dropdown d-none"></div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Localización <span
                                class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-primary"
                                style="background:#0d1117;border-width:2px;color:#0d6efd;border-right:none;"><i
                                    class="fa-solid fa-location-dot"></i></span>
                            <input type="text" id="edit-park-location" class="form-control rounded-0"
                                placeholder="Ej: Sandusky, Ohio"
                                style="border-width:2px;border-color:#0d6efd;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-0">Año de
                                apertura</label>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input admin-toggle" type="checkbox" role="switch"
                                    id="unknown-edit-park-year"
                                    onchange="document.getElementById('edit-park-year').disabled=this.checked;if(this.checked)document.getElementById('edit-park-year').value='';">
                                <label class="form-check-label text-muted small"
                                    for="unknown-edit-park-year">Desconocido</label>
                            </div>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-primary"
                                style="background:#0d1117;border-width:2px;color:#0d6efd;border-right:none;"><i
                                    class="fa-regular fa-calendar-days"></i></span>
                            <input type="number" id="edit-park-year" class="form-control rounded-0"
                                placeholder="Ej: 1964" min="1800" max="2035"
                                style="border-width:2px;border-color:#0d6efd;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Web oficial</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-primary"
                                style="background:#0d1117;border-width:2px;color:#0d6efd;border-right:none;"><i
                                    class="fa-solid fa-globe"></i></span>
                            <input type="url" id="edit-park-website" class="form-control rounded-0"
                                placeholder="https://www.cedarpoint.com"
                                style="border-width:2px;border-color:#0d6efd;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Precio entrada
                            (€)</label>
                        <div class="input-group">
                            <span class="input-group-text rounded-0 border-primary"
                                style="background:#0d1117;border-width:2px;color:#0d6efd;border-right:none;"><i
                                    class="fa-solid fa-euro-sign"></i></span>
                            <input type="number" id="edit-park-price" class="form-control rounded-0"
                                placeholder="Ej: 49.99" min="0" step="0.01"
                                style="border-width:2px;border-color:#0d6efd;background:#0d1117;color:#e6edf3;height:46px;border-left:none;box-shadow:none;">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Imagen del
                            parque</label>
                        <div id="edit-park-dropzone" onclick="document.getElementById('edit-park-image').click()" style="border:2px dashed #0d6efd;background:rgba(13,110,253,0.05);cursor:pointer;
                                   padding:1rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:0.5rem;
                                   transition:all .2s ease;height:46px;justify-content:center;overflow:hidden;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-cloud-arrow-up text-primary"></i>
                                <span class="text-white small fw-bold" id="edit-park-dropzone-text">Cambiar
                                    imagen</span>
                            </div>
                        </div>
                        <input type="file" id="edit-park-image" accept="image/*" class="d-none">
                        <div id="edit-park-preview-container" class="mt-2">
                            <img id="edit-park-preview" src="" class="img-thumbnail bg-dark border-secondary"
                                style="max-height:100px;display:none;">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                            <i class="fa-solid fa-link me-1 text-primary"></i>Montañas rusas del parque
                            <span class="badge bg-secondary fw-normal ms-2" id="edit-park-coasters-badge">0
                                asignadas</span>
                        </label>
                        <p class="text-muted small mb-2">Las marcadas están asignadas a este parque. Desmarca para
                            moverlas a "Desconocido". También puedes añadir coasters sin parque asignado.</p>
                        <div style="overflow:hidden;">
                            <input type="text" id="edit-park-coasters-search" class="form-control rounded-0"
                                placeholder="Buscar coaster..."
                                style="border-width:2px;border-color:#30363d;background:#0d1117;color:#e6edf3;box-shadow:none;outline:0;">
                        </div>
                        <div style="height:0.5rem;"></div>
                        <div id="edit-park-coasters-list"
                            style="max-height:220px;overflow-y:auto;overflow-x:hidden;background:#0d1117;border:2px solid #30363d;padding:0.5rem;">
                            <div class="text-center text-muted py-3">
                                <div class="spinner-border spinner-border-sm text-primary"></div> Cargando...
                            </div>
                        </div>
                        <input type="hidden" id="edit-park-coasters-ids" value="">
                    </div>

                </div>

                <div id="edit-park-messages" class="w-100 mt-4 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="edit-park-error"><i
                            class="fa-solid fa-circle-exclamation me-2"></i><span></span></div>
                    <div class="alert alert-success rounded-0 border-0 mb-0 d-none" id="edit-park-success"><i
                            class="fa-solid fa-circle-check me-2"></i><span></span></div>
                </div>
            </div>
            <div class="modal-footer border-0 px-5 pb-4 pt-2" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-0 fw-bold px-5" id="confirm-edit-park">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script src="<?= Router::asset('web/js/admin/admin.js') ?>"></script>