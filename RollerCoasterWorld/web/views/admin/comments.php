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
                <i class="fa-solid fa-comments me-3"></i>Moderación de Reseñas
            </h1>
            <span class="text-muted small" id="reviews-count-label"></span>
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

                    <!-- Tipo -->
                    <label class="form-label small text-muted fw-semibold mb-1">Filtrar por:</label>
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-type">
                            <option value="">Todas las reseñas</option>
                            <option value="coaster">Montañas Rusas</option>
                            <option value="park">Parques</option>
                        </select>
                    </div>

                    <!-- Ordenar -->
                    <label class="form-label small text-muted fw-semibold mb-1">Ordenar por:</label>
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" id="filter-sort">
                            <option value="recent">Más recientes</option>
                            <option value="oldest">Más antiguas</option>
                            <option value="best">Mejor valoración</option>
                            <option value="worst">Peor valoración</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0"
                            id="btn-reviews-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0"
                            id="btn-reviews-borrar">
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
                    <input type="text" id="review-search" class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar por usuario, atracción o contenido de reseña..." style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
                <button class="btn btn-outline-secondary rounded-0" onclick="loadReviews()" title="Recargar">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="reviews-count"></p>

            <!-- Lista de reseñas -->
            <div class="list-group shadow-sm rounded-0" id="admin-reviews-list"
                style="max-height: 700px; overflow-y: auto; overflow-x: hidden; background-color: transparent;">
                <div class="list-group-item text-center text-muted py-5" id="admin-reviews-loading">
                    <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando reseñas...
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-reviews-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>


<!-- ===================== MODAL CONFIRMAR ELIMINAR RESEÑA ===================== -->
<div class="modal fade" id="modal-delete-review" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-eraser me-2"></i>Borrar texto de reseña
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-1">¿Estás seguro de que quieres borrar el texto de esta reseña?</p>
                <div class="p-3 my-3 bg-dark border border-secondary rounded-1 text-muted fst-italic"
                    id="delete-review-text" style="max-height: 150px; overflow-y: auto;">
                    —
                </div>
                <p class="text-warning small mt-2 mb-0"><i class="fa-solid fa-circle-info me-1"></i> La puntuación
                    (estrellas) dada por el usuario se mantendrá, solo se eliminará el texto escrito.</p>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-review" data-id=""
                    data-type="">
                    <i class="fa-solid fa-eraser me-1"></i>Borrar texto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL ELIMINAR RESEÑA COMPLETA ===================== -->
<div class="modal fade" id="modal-destroy-review" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header border-0" style="background:#7f1d1d;">
                <h5 class="modal-title text-white">
                    <i class="fa-solid fa-trash me-2"></i>Eliminar reseña permanentemente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-1">Vas a <strong class="text-danger">eliminar permanentemente</strong> la reseña de <strong
                        id="destroy-review-username" class="text-white"></strong>.</p>
                <div class="p-3 my-3 bg-dark border border-danger rounded-1 text-muted fst-italic border-opacity-50"
                    id="destroy-review-text" style="max-height: 150px; overflow-y: auto;">—</div>
                <p class="text-danger small mt-2 mb-0">
                    <i class="fa-solid fa-circle-exclamation me-1"></i>
                    Esta acción <strong>no se puede deshacer</strong>. Se borrará la fila entera y la puntuación dejará
                    de contar para el ranking de la coaster/parque.
                </p>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-destroy-review" data-id=""
                    data-type="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar permanentemente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== TOAST CONTAINER ===================== -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="admin-toast" class="toast align-items-center text-white border-0 rounded-0" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="admin-toast-body"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="<?= Router::asset('web/js/admin/admin.js') ?>?v=<?= time() ?>"></script>
<script src="<?= Router::asset('web/js/admin/admin_reviews.js') ?>?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>