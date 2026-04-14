<?php
$page_css = ['web/css/forums.css'];
require_once __DIR__ . '/../../partials/header.php';
?>
<main class="container my-5">
    <div class="row mb-4">
        <div
            class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-3 mb-md-0">
                <i class="fa-solid fa-comments me-2"></i>Búsqueda de Foros
            </h1>
            <?php if ($is_logged): ?>
                <a href="<?= $base_url ?>/web/views/public/forums/forum_config.php"
                    class="btn btn-success fw-bold shadow-sm rounded-0 px-4" id="create-forum-btn">
                    <i class="fa-solid fa-plus me-2"></i>Crear Foro
                </a>
            <?php else: ?>
                <button class="btn btn-success fw-bold shadow-sm rounded-0 px-4" id="create-forum-btn"
                    data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fa-solid fa-plus me-2"></i>Crear Foro
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-lg-8 mx-auto d-flex gap-2">
            <div class="input-group shadow-sm flex-grow-1">
                <span class="input-group-text bg-success border-success text-white rounded-0" id="search-addon"
                    style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="forum-search-input" class="form-control border-success rounded-0"
                    placeholder="Buscar título o descripción del foro..." aria-label="Buscar foros"
                    aria-describedby="search-addon" style="border-width: 2px;">
            </div>
            <?php if ($is_logged): ?>
                <button type="button" class="btn btn-outline-success shadow-sm rounded-0 fw-bold d-flex align-items-center text-nowrap" id="filter-mine-btn" data-mine="false">
                    <i class="fa-solid fa-filter me-sm-2"></i> <span class="d-none d-sm-inline">Mis Foros</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- CENTRO: Foros -->
        <div class="col-12 col-lg-10">
            <div class="forum-list-container" id="forum-list"></div>

            <div class="pagination mt-4 justify-content-center" id="pagination"></div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<?php
$login_msg = 'Para crear o participar en foros necesitas iniciar sesión.';
require_once __DIR__ . '/../../partials/login_modal.php';
?>
<script>
window.BASE_URL = '<?= $base_url ?>';
window.IS_LOGGED_IN = <?= $is_logged ? 'true' : 'false' ?>;
</script>
<script src="<?= Router::asset('web/js/forums/forums.js') ?>"></script>