<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
;
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>

<!-- Reutilizamos el mismo CSS -->
<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold text-success" data-i18n="parks.reviews.title">Reseñas de Parques de Atracciones
            </h1>
            <p class="lead text-muted mt-3" data-i18n="parks.reviews.subtitle">Opiniones reales de la comunidad sobre
                los mejores parques del mundo</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Filtros laterales -->
        <aside class="col-12 col-lg-3 sidebar-filter">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i><span
                            data-i18n="parks.reviews.filters">Filtros</span></h5>
                </div>
                <div class="card-body">
                    <!-- Rating mínimo -->
                    <div class="mb-3">
                        <label class="form-label fw-bold" data-i18n="parks.reviews.min_rating">Rating mínimo</label>
                        <select class="form-select" id="rating-filter">
                            <option value="" data-i18n="parks.reviews.all">Todos</option>
                            <option value="4.5">4.5+</option>
                            <option value="4">4+</option>
                            <option value="3.5">3.5+</option>
                            <option value="3">3+</option>
                        </select>
                    </div>

                    <!-- País -->
                    <div class="mb-3">
                        <label class="form-label fw-bold" data-i18n="parks.reviews.country">País</label>
                        <select class="form-select" id="country-filter">
                            <option value="" data-i18n="parks.reviews.all">Todos</option>
                            <!-- Se llenará dinámicamente con JS -->
                        </select>
                    </div>

                    <!-- Ordenación -->
                    <div class="mb-3">
                        <label class="form-label fw-bold" data-i18n="parks.reviews.sort_by">Ordenar por</label>
                        <select class="form-select" id="sort-reviews">
                            <option value="newest" data-i18n="parks.reviews.newest">Más recientes</option>
                            <option value="best" data-i18n="parks.reviews.best">Mejor valoración</option>
                            <option value="worst" data-i18n="parks.reviews.worst">Peor valoración</option>
                            <option value="most_helpful" data-i18n="parks.reviews.most_helpful">Más útiles</option>
                        </select>
                    </div>

                    <!-- Buscar reseña -->
                    <div class="mb-3">
                        <label class="form-label fw-bold" data-i18n="parks.reviews.search_label">Buscar reseña</label>
                        <input type="text" class="form-control" id="review-search"
                            data-i18n-placeholder="parks.reviews.search_placeholder" placeholder="Palabras clave...">
                    </div>

                    <button class="btn btn-outline-light w-100" id="clear-review-filters"
                        data-i18n="parks.reviews.clear_filters">Limpiar filtros</button>
                </div>
            </div>
        </aside>

        <!-- Listado de reseñas -->
        <div class="col-12 col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h3 class="fw-bold mb-0" data-i18n="parks.reviews.recent_reviews">Reseñas recientes</h3>
                <div class="d-flex gap-3">
                    <select class="form-select w-auto" id="sort-reviews-top">
                        <option value="newest" data-i18n="parks.reviews.newest">Más recientes</option>
                        <option value="best" data-i18n="parks.reviews.best">Mejor valoración</option>
                        <option value="worst" data-i18n="parks.reviews.worst">Peor valoración</option>
                    </select>
                </div>
            </div>

            <div id="reviews-list" class="row g-4">
                <!-- Reseñas cargadas dinámicamente -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-3 text-muted" data-i18n="parks.reviews.loading">Cargando reseñas...</p>
                </div>
            </div>

            <!-- Paginación (opcional, se puede añadir con JS) -->
            <div class="text-center mt-5" id="pagination">
                <!-- Paginación dinámica -->
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script src="<?= Router::asset('web/js/parks/parks.js') ?>"></script>