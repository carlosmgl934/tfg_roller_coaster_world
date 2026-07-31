<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>
<main class="container-fluid px-lg-5 my-5 min-vh-100">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom border-light border-opacity-50 pb-2 text-success">
                <i class="fa-solid fa-earth-europe me-2"></i> <span data-i18n="coasters.ranking.title">Ranking Global de
                    Montañas Rusas</span>
            </h1>
            <p class="text-muted text-uppercase fw-bold mt-3" style="letter-spacing: 0.1em; font-size: 0.85rem;"
                data-i18n="coasters.ranking.subtitle">
                Las mejores atracciones valoradas por la comunidad
            </p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="list-group shadow-sm" id="coaster-list" style="border-radius: 0;">
                <div class="text-center py-5">
                    <div class="spinner-border text-success mb-3" role="status"></div>
                    <p class="text-muted" data-i18n="coasters.ranking.loading">Cargando el ranking global...</p>
                </div>
            </div>
            <div class="d-flex justify-content-center mt-4" id="pagination"></div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/coasters/ranking.js') ?>?v=<?= time() ?>"></script>