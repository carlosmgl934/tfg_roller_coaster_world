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
            <h1 class="display-6 fw-bold text-success mb-0">
                Gestión de Coasters
            </h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-danger fw-bold rounded-0 shadow-sm px-4 d-none" id="btn-bulk-delete">
                    <i class="fa-solid fa-trash-can me-2"></i>Eliminar (<span id="bulk-delete-count">0</span>)
                </button>
                <a href="#" class="btn btn-success fw-bold rounded-0 shadow-sm px-4" id="btn-add-coaster">
                    <i class="fa-solid fa-plus me-2"></i>Añadir coaster
                </a>
            </div>
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

                    <!-- Solo abiertas -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="filter-open-only">
                        <label class="form-check-label" for="filter-open-only">Sólo Operativas</label>
                    </div>

                    <!-- Fabricante -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-manufacter">
                            <option value="">Todos los fabricantes</option>
                            <option value="__null__">Desconocido</option>
                        </select>
                    </div>

                    <!-- País -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-country">
                            <option value="">Todos los países</option>
                            <option value="__null__">Desconocido</option>
                        </select>
                    </div>

                    <!-- Parque -->
                    <div class="mb-3 position-relative">
                        <input type="text" id="filter-park-search" class="form-control shadow-sm rounded-0" placeholder="Buscar parque...">
                        <input type="hidden" id="filter-park" value="">
                        <div id="filter-park-results" class="autocomplete-dropdown d-none" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; background: #161b22; border: 1px solid #30363d; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <!-- Año de apertura -->
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" id="filter-year">
                            <option value="">Año apertura (todos)</option>
                            <?php for ($i = date('Y') + 3; $i >= 1870; $i--): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Altura mínima -->
                    <div class="mb-3">
                        <label for="filter-height" class="form-label d-flex justify-content-between">
                            Altura mínima <span class="badge bg-success" id="height-val">0 m</span>
                        </label>
                        <input type="range" class="form-range" id="filter-height" min="0" max="200" value="0">
                    </div>

                    <!-- Velocidad mínima -->
                    <div class="mb-4">
                        <label for="filter-speed" class="form-label d-flex justify-content-between">
                            Velocidad mínima <span class="badge bg-success" id="speed-val">0 km/h</span>
                        </label>
                        <input type="range" class="form-range" id="filter-speed" min="0" max="300" value="0">
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-borrar">
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
                    <input type="text" id="admin-coaster-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar montaña rusa"
                        style="border-width: 2px;">
                    <i id="admin-search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="admin-coaster-count"></p>
            <div class="list-group shadow-sm rounded-0" id="admin-coaster-list">
                <div class="list-group-item text-center text-muted py-5" id="admin-coaster-loading">
                    <i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>
                    Las coasters se cargarán aquí.
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-coaster-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>

<!-- ===================== MODAL CONFIRMAR ELIMINAR ===================== -->
<div class="modal fade" id="modal-delete-coaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar coaster
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">¿Estás seguro de que quieres eliminar:</p>
                <p class="fw-bold text-danger mb-0" id="delete-coaster-name">—</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold"
                    id="confirm-delete-coaster" data-id="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL ELIMINAR SELECCIONADAS ===================== -->
<div class="modal fade" id="modal-bulk-delete-coaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar seleccionadas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">¿Estás seguro de que quieres eliminar las <span class="fw-bold text-danger" id="bulk-delete-coaster-count">0</span> montañas rusas seleccionadas?</p>
                <p class="text-muted small mt-2"><i class="fa-solid fa-circle-info me-1"></i>Esta acción es irreversible y borrará permanentemente todo su historial.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold"
                    id="confirm-bulk-delete-coaster">
                    <i class="fa-solid fa-trash-can me-1"></i>Eliminar Todo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL AÑADIR COASTER ===================== -->
<div class="modal fade" id="modal-add-coaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 1400px;">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <form id="add-coaster-form">
                <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0">Añadir nueva coaster</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
 
            <div class="modal-body p-5" style="background:#161b22;">
                <p class="text-muted small mb-4"><span class="text-danger fw-bold">*</span> Campo obligatorio</p>
                <div class="row g-5">
 
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-12 col-lg-7 pe-lg-4">
                        <div class="row g-4">
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Nombre de la atracción <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-ticket-simple"></i></span>
                                    <input type="text" id="add-coaster-name" class="form-control form-control-lg rounded-0"
                                        placeholder="Ej: Steel Vengeance"
                                        style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Fabricante <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-industry"></i></span>
                                        <input type="text" id="add-coaster-manufacturer" autocomplete="off" data-ac="manufacturer"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <button type="button" id="btn-new-manufacturer"
                                            class="btn btn-outline-success rounded-0 px-3 text-nowrap fw-semibold" style="border-width:2px;">
                                            <i class="fa-solid fa-plus me-1"></i>Añadir nuevo
                                        </button>
                                    </div>
                                    <div id="ac-dropdown-manufacturer" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Modelo <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-cogs"></i></span>
                                        <input type="text" id="add-coaster-model" autocomplete="off" data-ac="model"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <button type="button" id="btn-new-model"
                                            class="btn btn-outline-success rounded-0 px-3 text-nowrap fw-semibold" style="border-width:2px;">
                                            <i class="fa-solid fa-plus me-1"></i>Añadir nuevo
                                        </button>
                                    </div>
                                    <div id="ac-dropdown-model" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Parque <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-brands fa-fort-awesome"></i></span>
                                        <input type="text" id="add-coaster-park" autocomplete="off" data-ac="park"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <input type="hidden" id="add-coaster-park-id">
                                        <button type="button" id="btn-new-park"
                                            class="btn btn-outline-success rounded-0 px-3 text-nowrap fw-semibold" style="border-width:2px;">
                                            <i class="fa-solid fa-plus me-1"></i>Añadir nuevo
                                        </button>
                                    </div>
                                    <div id="ac-dropdown-park" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">País <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-earth-americas"></i></span>
                                        <input type="text" id="add-coaster-country" autocomplete="off" data-ac="country"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                    </div>
                                    <div id="ac-dropdown-country" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Estado Actual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-solid fa-traffic-light"></i></span>
                                    <select id="add-coaster-status" class="form-select rounded-0"
                                        style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <option value="Operating">Operating</option>
                                        <option value="Closed">Closed</option>
                                        <option value="SBNO">SBNO</option>
                                        <option value="Under construction">Under construction</option>
                                    </select>
                                </div>
                            </div>
 
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-semibold small text-uppercase text-muted mb-0">Año de apertura</label>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="unknown-year" onchange="document.getElementById('add-coaster-year').disabled = this.checked; if(this.checked) document.getElementById('add-coaster-year').value='';">
                                        <label class="form-check-label text-muted small" for="unknown-year">Desconocido</label>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;"><i class="fa-regular fa-calendar-days"></i></span>
                                    <input type="number" id="add-coaster-year" class="form-control rounded-0"
                                        placeholder="Ej: 2003" min="1800" max="2030"
                                        style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                </div>
                            </div>
 
                            <div class="col-12 mt-5">
                                <div class="d-flex align-items-center gap-3 mb-1">
                                    <div style="height:2px;flex:1;background:linear-gradient(90deg, transparent, #198754);"></div>
                                    <span class="text-success fw-bold text-uppercase small" style="letter-spacing: 1px;"><i class="fa-solid fa-ruler-combined me-2"></i>Estadísticas técnicas</span>
                                    <div style="height:2px;flex:1;background:linear-gradient(270deg, transparent, #198754);"></div>
                                </div>
                                <p class="text-muted text-center small mb-2">Introduce las estadísticas numéricas de la atracción</p>
                            </div>
 
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center transition-all" style="background:#0d1117; border: 2px solid #198754;">
                                    <i class="fa-solid fa-arrow-up-right-dots fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Altura</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="add-coaster-height" class="form-control rounded-0 text-center no-spinners" placeholder="0" min="0" step="0.01" autocomplete="off"
                                            style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none;">
                                        <span class="input-group-text rounded-0" style="background:#30363d; border:1px solid #30363d; color:#e6edf3;">m</span>
                                    </div>
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="unknown-height" onchange="document.getElementById('add-coaster-height').disabled = this.checked; if(this.checked) document.getElementById('add-coaster-height').value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="unknown-height">Desconocido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center" style="background:#0d1117; border: 2px solid #198754;">
                                    <i class="fa-solid fa-gauge-high fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Velocidad</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="add-coaster-speed" class="form-control rounded-0 text-center no-spinners" placeholder="0" min="0" step="0.01" autocomplete="off"
                                            style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none;">
                                        <span class="input-group-text rounded-0" style="background:#30363d; border:1px solid #30363d; color:#e6edf3;">km/h</span>
                                    </div>
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="unknown-speed" onchange="document.getElementById('add-coaster-speed').disabled = this.checked; if(this.checked) document.getElementById('add-coaster-speed').value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="unknown-speed">Desconocido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center" style="background:#0d1117; border: 2px solid #198754;">
                                    <i class="fa-solid fa-arrows-left-right fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Longitud</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="add-coaster-length" class="form-control rounded-0 text-center no-spinners" placeholder="0" min="0" step="0.01" autocomplete="off"
                                            style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none;">
                                        <span class="input-group-text rounded-0" style="background:#30363d; border:1px solid #30363d; color:#e6edf3;">m</span>
                                    </div>
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="unknown-length" onchange="document.getElementById('add-coaster-length').disabled = this.checked; if(this.checked) document.getElementById('add-coaster-length').value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="unknown-length">Desconocido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center" style="background:#0d1117; border: 2px solid #198754;">
                                    <i class="fa-solid fa-rotate-right fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Inversiones</label>
                                    <input type="number" id="add-coaster-inversions" class="form-control form-control-sm rounded-0 text-center no-spinners w-100 mx-auto mb-2" placeholder="0" min="0" autocomplete="off"
                                        style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none; max-width:80px;">
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="unknown-inversions" onchange="document.getElementById('add-coaster-inversions').disabled = this.checked; if(this.checked) document.getElementById('add-coaster-inversions').value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="unknown-inversions">Desconocido</label>
                                    </div>
                                </div>
                            </div>
 
                        </div>
                    </div>
 
                    <!-- COLUMNA DERECHA: Media -->
                    <div class="col-12 col-lg-5 d-flex flex-column" style="border-left: 1px solid rgba(255,255,255,0.05);">
                        <div class="d-lg-flex ps-lg-4 flex-column h-100 mt-4 mt-lg-0">
                            
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-photo-film text-success fs-5 me-2"></i>
                                <label class="form-label fw-bold text-uppercase text-white mb-0" style="letter-spacing: 0.5px;">Portada (Imagen Principal)</label>
                            </div>
     
                            <div id="add-coaster-dropzone"
                                onclick="document.getElementById('add-coaster-image').click()"
                                style="border:2px dashed #198754;background:rgba(25,135,84,0.05);cursor:pointer;
                                       padding:2rem 1.5rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:1rem;
                                       transition:all .2s ease;">
                                <i class="fa-solid fa-cloud-arrow-up text-success fs-1 mb-1"></i>
                                <div>
                                    <div class="fw-bold text-white mb-1" style="font-size:1.1rem;">Haz clic para buscar</div>
                                    <div class="text-muted small">o arrastra el archivo aquí</div>
                                </div>
                                <div class="badge bg-dark border border-secondary text-muted mt-2 fw-normal px-3 py-2">
                                    JPG, PNG, WebP, MP4, WebM <br> Recom.: 1280×720px (16:9)
                                </div>
                            </div>
                            <input type="file" id="add-coaster-image" accept="image/*,video/*" class="d-none">
                            <input type="hidden" id="add-coaster-image-url" value="">
     
                            <div id="add-coaster-preview" class="mt-4 flex-grow-1 position-relative"
                                style="min-height:280px;background:#0d1117;
                                       border:1px solid rgba(255,255,255,0.08);
                                       display:flex;align-items:center;justify-content:center;
                                       overflow:hidden;">
                                <div class="text-center text-muted">
                                    <i class="fa-regular fa-image fa-3x d-block mb-3" style="opacity:0.2;"></i>
                                    <span style="font-size:0.85rem; letter-spacing:1px; text-transform:uppercase;">Vista previa</span>
                                </div>
                            </div>
     
                            <div class="mt-3 d-flex justify-content-center pt-2">
                                <span class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-circle-info text-success me-1"></i> Optimiza los archivos multimedia antes de subir para no afectar al rendimiento</span>
                            </div>

                        </div>
                    </div>
 
                </div>
                
                <!-- Contenedor para mensajes de error o éxito -->
                <div id="add-coaster-messages" class="w-100 mt-4 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="add-coaster-error"><i class="fa-solid fa-circle-exclamation me-2"></i><span></span></div>
                    <div class="alert alert-success rounded-0 border-0 mb-0 d-none" id="add-coaster-success"><i class="fa-solid fa-circle-check me-2"></i><span></span></div>
                </div>
            </div>
 
            <div class="modal-footer border-0 px-5 pb-4 pt-2" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-0 fw-bold px-5" id="confirm-add-coaster">
                    <i class="fa-solid fa-plus me-2"></i>Añadir coaster
                </button>
            </div>
            </form>
 
        </div>
    </div>
</div>

<!-- ===================== MODAL NOTIFICACIÓN ===================== -->
<div class="modal fade" id="modal-notification" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <div class="modal-header border-0 py-3 px-4" id="modal-notification-header">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="modal-title fw-bold mb-0 text-white" id="modal-notification-title">Notificación</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center" style="background:#161b22;">
                <i id="modal-notification-icon" class="fa-solid fa-circle-info fs-1 mb-3"></i>
                <p class="mb-0 text-white fs-5" id="modal-notification-message"></p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0 justify-content-center" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL NOTIFICACIÓN ===================== -->
<div class="modal fade" id="modal-notification" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <div class="modal-header border-0 py-3 px-4 bg-success text-white" id="modal-notification-header">
                <h5 class="modal-title fw-bold mb-0" id="modal-notification-title">Notificación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4" style="background:#161b22;">
                <i class="fa-solid fa-circle-check text-success fs-1 mb-3" id="modal-notification-icon"></i>
                <p class="text-light mb-0" id="modal-notification-message">—</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL EDITAR COASTER ===================== -->
<div class="modal fade" id="modal-edit-coaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 1400px;">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <form id="edit-coaster-form">
                <input type="hidden" id="edit-coaster-id">
 
            <div class="modal-header text-white border-0 py-3 px-4" style="background:#10b981;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center bg-white bg-opacity-25 rounded-1 p-2" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-pen-to-square fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="letter-spacing: 0.5px;">Actualizar Datos de la Atracción</h5>
                        <div class="badge mt-1 rounded-pill" style="background-color: rgba(0,0,0,0.25); color: #d1fae5; font-size: 0.72rem; font-weight:700; letter-spacing: 1px; padding: 0.4em 0.8em; border: 1px solid rgba(255,255,255,0.2);">MODO DE EDICIÓN ACTIVO</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
 
            <div class="modal-body p-5" style="background:#161b22;">
                <p class="text-muted small mb-4"><span class="text-danger fw-bold">*</span> Campo obligatorio</p>
                <div class="row g-5">
 
                    <!-- COLUMNA IZQUIERDA -->
                    <div class="col-12 col-lg-7 pe-lg-4">
                        <div class="row g-4">
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Nombre de la atracción <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-solid fa-ticket-simple"></i></span>
                                    <input type="text" id="edit-coaster-name" class="form-control form-control-lg rounded-0"
                                        placeholder="Ej: Steel Vengeance"
                                        style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Fabricante <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-solid fa-industry"></i></span>
                                        <input type="text" id="edit-coaster-manufacturer" autocomplete="off" data-ac="manufacturer"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <button type="button" id="btn-edit-new-manufacturer"
                                            class="btn rounded-0 px-3 text-nowrap fw-semibold d-none" style="border:2px solid #10b981; color:#10b981; background:transparent;">
                                            <i class="fa-solid fa-plus me-1"></i>Añadir nuevo
                                        </button>
                                    </div>
                                    <div id="ac-dropdown-edit-manufacturer" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Modelo <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-solid fa-cogs"></i></span>
                                        <input type="text" id="edit-coaster-model" autocomplete="off" data-ac="model"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <button type="button" id="btn-edit-new-model"
                                            class="btn rounded-0 px-3 text-nowrap fw-semibold d-none" style="border:2px solid #10b981; color:#10b981; background:transparent;">
                                            <i class="fa-solid fa-plus me-1"></i>Añadir nuevo
                                        </button>
                                    </div>
                                    <div id="ac-dropdown-edit-model" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Parque <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-brands fa-fort-awesome"></i></span>
                                        <input type="text" id="edit-coaster-park" autocomplete="off" data-ac="park"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <input type="hidden" id="edit-coaster-park-id">
                                        <button type="button" id="btn-edit-new-park"
                                            class="btn rounded-0 px-3 text-nowrap fw-semibold d-none" style="border:2px solid #10b981; color:#10b981; background:transparent;">
                                            <i class="fa-solid fa-plus me-1"></i>Añadir nuevo
                                        </button>
                                    </div>
                                    <div id="ac-dropdown-edit-park" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">País <span class="text-danger">*</span></label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-solid fa-earth-americas"></i></span>
                                        <input type="text" id="edit-coaster-country" autocomplete="off" data-ac="country"
                                            class="form-control rounded-0" placeholder="Escribe para buscar..."
                                            style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                    </div>
                                    <div id="ac-dropdown-edit-country" class="ac-dropdown d-none"></div>
                                </div>
                            </div>
 
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Estado Actual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-solid fa-traffic-light"></i></span>
                                    <select id="edit-coaster-status" class="form-select rounded-0"
                                        style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                        <option value="Operating">Operating</option>
                                        <option value="Closed">Closed</option>
                                        <option value="SBNO">SBNO</option>
                                        <option value="Under construction">Under construction</option>
                                    </select>
                                </div>
                            </div>
 
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label class="form-label fw-semibold small text-uppercase text-muted mb-0">Año de apertura</label>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="edit-unknown-year" onchange="let el = document.getElementById('edit-coaster-year'); el.disabled = this.checked; el.placeholder = this.checked ? '' : 'Ej: 2003'; if(this.checked) el.value='';">
                                        <label class="form-check-label text-muted small" for="edit-unknown-year">Desconocido</label>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text rounded-0" style="background:#0d1117; border:2px solid #10b981; color:#10b981; border-right:none;"><i class="fa-regular fa-calendar-days"></i></span>
                                    <input type="number" id="edit-coaster-year" class="form-control rounded-0"
                                        placeholder="Ej: 2003" min="1800" max="2030"
                                        style="border-width:2px;border-color:#10b981;background:#0d1117;color:#e6edf3;height:46px; border-left:none; box-shadow:none;">
                                </div>
                            </div>
 
                            <div class="col-12 mt-5">
                                <div class="d-flex align-items-center gap-3 mb-1">
                                    <div style="height:2px;flex:1;background:linear-gradient(90deg, transparent, #10b981);"></div>
                                    <span class="fw-bold text-uppercase small" style="color:#10b981; letter-spacing: 1px;"><i class="fa-solid fa-ruler-combined me-2"></i>Estadísticas técnicas</span>
                                    <div style="height:2px;flex:1;background:linear-gradient(270deg, transparent, #10b981);"></div>
                                </div>
                                <p class="text-muted text-center small mb-2">Modifica las estadísticas numéricas de la atracción</p>
                            </div>
 
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center transition-all" style="background:#0d1117; border: 2px solid #10b981;">
                                    <i class="fa-solid fa-arrow-up-right-dots fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Altura</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="edit-coaster-height" class="form-control rounded-0 text-center no-spinners" placeholder="0" min="0" step="0.01" autocomplete="off"
                                            style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none;">
                                        <span class="input-group-text rounded-0" style="background:#30363d; border:1px solid #30363d; color:#e6edf3;">m</span>
                                    </div>
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="edit-unknown-height" onchange="let el = document.getElementById('edit-coaster-height'); el.disabled = this.checked; el.placeholder = this.checked ? '' : '0'; if(this.checked) el.value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="edit-unknown-height">Desconocido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center" style="background:#0d1117; border: 2px solid #10b981;">
                                    <i class="fa-solid fa-gauge-high fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Velocidad</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="edit-coaster-speed" class="form-control rounded-0 text-center no-spinners" placeholder="0" min="0" step="0.01" autocomplete="off"
                                            style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none;">
                                        <span class="input-group-text rounded-0" style="background:#30363d; border:1px solid #30363d; color:#e6edf3;">km/h</span>
                                    </div>
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="edit-unknown-speed" onchange="let el = document.getElementById('edit-coaster-speed'); el.disabled = this.checked; el.placeholder = this.checked ? '' : '0'; if(this.checked) el.value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="edit-unknown-speed">Desconocido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center" style="background:#0d1117; border: 2px solid #10b981;">
                                    <i class="fa-solid fa-arrows-left-right fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Longitud</label>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" id="edit-coaster-length" class="form-control rounded-0 text-center no-spinners" placeholder="0" min="0" step="0.01" autocomplete="off"
                                            style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none;">
                                        <span class="input-group-text rounded-0" style="background:#30363d; border:1px solid #30363d; color:#e6edf3;">m</span>
                                    </div>
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="edit-unknown-length" onchange="let el = document.getElementById('edit-coaster-length'); el.disabled = this.checked; el.placeholder = this.checked ? '' : '0'; if(this.checked) el.value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="edit-unknown-length">Desconocido</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <div class="p-3 text-center" style="background:#0d1117; border: 2px solid #10b981;">
                                    <i class="fa-solid fa-rotate-right fs-4 text-muted mb-2"></i>
                                    <label class="form-label fw-semibold small text-uppercase text-muted d-block mb-2">Inversiones</label>
                                    <input type="number" id="edit-coaster-inversions" class="form-control form-control-sm rounded-0 text-center no-spinners w-100 mx-auto mb-2" placeholder="0" min="0" autocomplete="off"
                                        style="background:#161b22; color:#e6edf3; border:1px solid #30363d; box-shadow:none; max-width:80px;">
                                    <div class="form-check form-switch d-flex justify-content-center align-items-center gap-2">
                                        <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="edit-unknown-inversions" onchange="let el = document.getElementById('edit-coaster-inversions'); el.disabled = this.checked; el.placeholder = this.checked ? '' : '0'; if(this.checked) el.value='';">
                                        <label class="form-check-label text-muted" style="font-size:0.72rem;" for="edit-unknown-inversions">Desconocido</label>
                                    </div>
                                </div>
                            </div>
 
                        </div>
                    </div>
 
                    <!-- COLUMNA DERECHA: Media -->
                    <div class="col-12 col-lg-5 d-flex flex-column" style="border-left: 1px solid rgba(255,255,255,0.05);">
                        <div class="d-lg-flex ps-lg-4 flex-column h-100 mt-4 mt-lg-0">
                            
                            <div class="d-flex align-items-center mb-3">
                                <i class="fa-solid fa-file-image fs-5 me-2" style="color:#10b981;"></i>
                                <label class="form-label fw-bolder text-uppercase text-white mb-0" style="letter-spacing: 1px; font-size:1.1rem;">ACTUALIZAR PORTADA</label>
                            </div>
     
                            <div id="edit-coaster-dropzone"
                                onclick="document.getElementById('edit-coaster-image').click()"
                                style="border:2px dashed #10b981;background:rgba(16,185,129,0.05);cursor:pointer;
                                       padding:2rem 1.5rem;display:flex;flex-direction:column;align-items:center;text-align:center;gap:1rem;
                                       transition:all .2s ease;">
                                <i class="fa-solid fa-cloud-arrow-up fs-1 mb-1" style="color:#10b981;"></i>
                                <div>
                                    <div class="fw-bold text-white mb-1" style="font-size:1.1rem;">Haz clic para buscar nueva imagen</div>
                                    <div class="text-muted small">o arrastra el archivo aquí</div>
                                </div>
                                <div class="badge border text-muted mt-2 fw-normal px-3 py-2" style="background:#0d1117; border-color:#30363d;">
                                    JPG, PNG, WebP, MP4, WebM <br> Recom.: 1280×720px (16:9)
                                </div>
                            </div>
                            <input type="file" id="edit-coaster-image" accept="image/*,video/*" class="d-none">
     
                            <div id="edit-coaster-preview" class="mt-4 flex-grow-1 position-relative"
                                style="min-height:280px;background:#0d1117;
                                       border:1px solid rgba(255,255,255,0.08);
                                       display:flex;align-items:center;justify-content:center;
                                       overflow:hidden;">
                                <div class="text-center text-muted">
                                    <i class="fa-regular fa-image fa-3x d-block mb-3" style="opacity:0.2;"></i>
                                    <span style="font-size:0.85rem; letter-spacing:1px; text-transform:uppercase;">Vista previa original / nueva</span>
                                </div>
                            </div>
     
                            <div class="mt-3 d-flex justify-content-center pt-2">
                                <span class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-circle-info me-1" style="color:#10b981;"></i> Optimiza los archivos multimedia antes de subir para no afectar al rendimiento</span>
                            </div>
 
                        </div>
                    </div>
 
                </div>
                
                <!-- Contenedor para mensajes de error o éxito -->
                <div id="edit-coaster-messages" class="w-100 mt-4 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="edit-coaster-error"><i class="fa-solid fa-circle-exclamation me-2"></i><span></span></div>
                    <div class="alert rounded-0 border-0 mb-0 d-none" id="edit-coaster-success" style="background-color: rgba(16,185,129,0.1); color: #10b981;"><i class="fa-solid fa-circle-check me-2"></i><span></span></div>
                </div>
            </div>
 
            <div class="modal-footer border-0 px-5 pb-4 pt-2" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn rounded-0 fw-bold px-5" id="confirm-edit-coaster" style="background:#10b981; color:white;">
                    <i class="fa-solid fa-arrows-rotate me-2"></i>Actualizar Atracción
                </button>
            </div>
            </form>
 
        </div>
    </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script src="<?= Router::asset('web/js/admin/admin.js') ?>"></script>