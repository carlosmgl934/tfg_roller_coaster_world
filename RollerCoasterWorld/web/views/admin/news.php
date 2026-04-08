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
                Gestión de Noticias
            </h1>
            <button class="btn btn-success fw-bold rounded-0 shadow-sm px-4" id="btn-add-news">
                <i class="fa-solid fa-plus me-2"></i>Añadir noticia
            </button>
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
                    <!-- Categoría / Tag -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-news-tag">
                            <option value="">Todas las categorías</option>
                            <option value="Destacado">Destacado</option>
                            <option value="Comunidad">Comunidad</option>
                            <option value="Actualización">Actualización</option>
                        </select>
                    </div>

                    <!-- Estado destacada -->
                    <div class="mb-4">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="filter-news-featured">
                            <label class="form-check-label text-muted small" for="filter-news-featured">Solo destacadas</label>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-news-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-news-borrar">
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
                    <input type="text" id="admin-news-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar noticia por título..."
                        style="border-width: 2px;">
                    <i id="admin-news-search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="admin-news-count"></p>
            <div class="list-group shadow-sm rounded-0" id="admin-news-list">
                <div class="list-group-item text-center text-muted py-5" id="admin-news-loading">
                    <i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>
                    Usa el buscador o activa un filtro para ver noticias.
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-news-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>

<!-- ===================== MODAL ELIMINAR NOTICIA ===================== -->
<div class="modal fade" id="modal-delete-news" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar noticia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">¿Estás seguro de que quieres eliminar esta noticia?</p>
                <p class="fw-bold text-danger mb-0" id="delete-news-title">—</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-news" data-id="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL AÑADIR/EDITAR NOTICIA ===================== -->
<div class="modal fade" id="modal-news-form" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">
            <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <h5 class="modal-title fw-bold mb-0" id="modal-news-title-header">Añadir Noticia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4" style="background:#161b22;">
                <input type="hidden" id="news-form-id">
                
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Título <span class="text-danger">*</span></label>
                        <input type="text" id="news-form-title" class="form-control rounded-0" style="background:#0d1117;color:#e6edf3;border:1px solid #30363d;" placeholder="Ej: Nueva montaña rusa anunciada">
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Etiqueta (Tag)</label>
                        <select id="news-form-tag" class="form-select rounded-0" style="background:#0d1117;color:#e6edf3;border:1px solid #30363d;">
                            <option value="Destacado">Destacado</option>
                            <option value="Comunidad">Comunidad</option>
                            <option value="Actualización">Actualización</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-uppercase text-muted">URL Externa (opcional)</label>
                        <input type="text" id="news-form-link" class="form-control rounded-0" style="background:#0d1117;color:#e6edf3;border:1px solid #30363d;" placeholder="Ej: https://...">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Descripción <span class="text-danger">*</span></label>
                        <textarea id="news-form-desc" class="form-control rounded-0" rows="4" style="background:#0d1117;color:#e6edf3;border:1px solid #30363d;" placeholder="Cuerpo de la noticia..."></textarea>
                    </div>
                    
                    <div class="col-12 col-md-8">
                        <label class="form-label fw-semibold small text-uppercase text-muted">Imagen de la Noticia</label>
                        <div class="input-group">
                            <input type="file" id="news-form-file" class="form-control rounded-0" style="background:#0d1117;color:#e6edf3;border:1px solid #30363d;" accept="image/*">
                            <input type="hidden" id="news-form-image">
                        </div>
                        <div id="news-form-image-preview" class="mt-2 d-none">
                            <span class="text-muted small italic">Imagen actual: <span id="news-image-path-text" class="text-success"></span></span>
                        </div>
                    </div>

                    <div class="col-12 col-md-4 d-flex align-items-center">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="news-form-featured">
                            <label class="form-check-label text-muted fw-bold" for="news-form-featured" style="color:#e6edf3!important;">Destacada Grande</label>
                        </div>
                    </div>
                </div>

                <div id="news-form-messages" class="w-100 mt-3 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="news-form-error"></div>
                </div>
            </div>

            <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success rounded-0 fw-bold" id="btn-save-news">Guardar noticia</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= Router::asset('web/js/admin/admin.js') ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
