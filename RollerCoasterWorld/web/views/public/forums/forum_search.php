<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */



$park_id = $_GET['id'] ?? null;
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/forums.css">

<main class="container my-5">
    <div class="row mb-4">
        <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-3 mb-md-0">
                <i class="fa-solid fa-comments me-2"></i>Búsqueda de Foros
            </h1>
            <a href="<?= $base_url ?>/web/views/public/forums/forum_config.php" class="btn btn-success fw-bold shadow-sm rounded-0 px-4" id="create-forum-btn">
                <i class="fa-solid fa-plus me-2"></i>Crear Foro
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-success border-success text-white rounded-0" id="search-addon" style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" id="forum-search-input" class="form-control border-success rounded-0" placeholder="Buscar título o descripción del foro..." aria-label="Buscar foros" aria-describedby="search-addon" style="border-width: 2px;">
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- CENTRO: Foros -->
        <div class="col-12 col-lg-10">
            <div class="list-group shadow-sm rounded-0" id="forum-list" style="border-radius: 0;"></div>

            <div class="pagination mt-4 justify-content-center" id="pagination"></div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/forums.js"></script>