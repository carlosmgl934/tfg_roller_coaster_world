<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';;
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
            <h1 class="display-6 fw-bold text-success">Reseñas de Parques de Atracciones</h1>
            <p class="lead text-muted mt-3">Opiniones reales de la comunidad sobre los mejores parques del mundo</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Filtros laterales -->
        <aside class="col-12 col-lg-3 sidebar-filter">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <!-- Rating mínimo -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Rating mínimo</label>
                        <select class="form-select" id="rating-filter">
                            <option value="">Todos</option>
                            <option value="4.5">4.5+</option>
                            <option value="4">4+</option>
                            <option value="3.5">3.5+</option>
                            <option value="3">3+</option>
                        </select>
                    </div>

                    <!-- País -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">País</label>
                        <select class="form-select" id="country-filter">
                            <option value="">Todos</option>
                            <!-- Se llenará dinámicamente con JS -->
                        </select>
                    </div>

                    <!-- Ordenación -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ordenar por</label>
                        <select class="form-select" id="sort-reviews">
                            <option value="newest">Más recientes</option>
                            <option value="best">Mejor valoración</option>
                            <option value="worst">Peor valoración</option>
                            <option value="most_helpful">Más útiles</option>
                        </select>
                    </div>

                    <!-- Buscar reseña -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Buscar reseña</label>
                        <input type="text" class="form-control" id="review-search" placeholder="Palabras clave...">
                    </div>

                    <button class="btn btn-outline-light w-100" id="clear-review-filters">Limpiar filtros</button>
                </div>
            </div>
        </aside>

        <!-- Listado de reseñas -->
        <div class="col-12 col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h3 class="fw-bold mb-0">Reseñas recientes</h3>
                <div class="d-flex gap-3">
                    <select class="form-select w-auto" id="sort-reviews-top">
                        <option value="newest">Más recientes</option>
                        <option value="best">Mejor valoración</option>
                        <option value="worst">Peor valoración</option>
                    </select>
                </div>
            </div>

            <div id="reviews-list" class="row g-4">
                <!-- Reseñas cargadas dinámicamente -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-3 text-muted">Cargando reseñas...</p>
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
