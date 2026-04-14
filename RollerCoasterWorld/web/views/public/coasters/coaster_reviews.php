<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>
<main class="container my-5">
    <!-- HERO HEADER -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-center align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-0">
                Reseñas de la Comunidad
            </h1>
        </div>
    </div>

    <!-- BARRA DE CONTROLES (Buscador + Ordenación) -->
    <div class="row justify-content-center mb-4">
        <div class="col-12 col-xl-10">
            <div class="row g-3">
                <!-- BUSCADOR -->
                <div class="col-12 col-lg-6">
                    <div class="search-container h-100">
                        <label for="review_search" class="form-label fw-bold d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-magnifying-glass"></i> Búsqueda de reseñas
                        </label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-success border-success text-white rounded-0"
                                id="search-addon" style="border-width: 2px;">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" name="review_search" id="review_search"
                                class="form-control border-success rounded-0"
                                placeholder="Busca reseñas por Usuario o por Montaña Rusa..." autocomplete="off"
                                style="border-width: 2px;">
                        </div>
                        <p class="text-danger mt-2 mb-0" id="text-rev" style="font-size: 0.9rem; font-weight: 500;"></p>
                    </div>
                </div>

                <!-- ORDENACIÓN Y FILTROS -->
                <div class="col-12 col-lg-6">
                    <div class="search-container h-100">
                        <label for="review_sort" class="form-label fw-bold d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-filter"></i> Ordenación y filtros
                        </label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-success border-success text-white rounded-0"
                                id="sort-addon" style="border-width: 2px;">
                                <i class="fa-solid fa-sort"></i>
                            </span>

                            <select class="form-select border-success rounded-0" id="review_sort"
                                style="border-width: 2px; cursor: pointer;">
                                <option value="date" selected>Antigüedad</option>
                                <option value="rating">Valoración (Nota)</option>
                            </select>

                            <button class="btn btn-outline-success border-success rounded-0" type="button"
                                id="btn_sort_order" data-order="desc" style="border-width: 2px;" title="Cambiar orden">
                                <i class="fa-solid fa-arrow-down-short-wide" id="icon_sort_order"></i>
                            </button>

                            <input type="checkbox" class="btn-check" id="btn_friends_only" autocomplete="off">
                            <label class="btn btn-outline-success border-success rounded-0" for="btn_friends_only"
                                style="border-width: 2px;">
                                <i class="fa-solid fa-user-group"></i> Solo amigos
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENEDOR DE RESEÑAS -->
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 mx-auto">
            <div class="reviews-container mt-2" id="reviews-container">
                <!-- Se inyectarán desde reviews.js mediante createReviewCard() -->
            </div>
        </div>
    </div>

    <!-- PAGINACIÓN -->
    <div class="pagination mt-5 justify-content-center" id="pagination"></div>
</main>

<?php 
require_once __DIR__ . '/../../partials/login_modal.php';
require_once __DIR__ . '/../../partials/footer.php'; 
?>
<script>
    window.BASE_URL = '<?= $base_url ?>';
    window.IS_LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<script src="<?= Router::asset('web/js/coasters/reviews.js') ?>"></script>