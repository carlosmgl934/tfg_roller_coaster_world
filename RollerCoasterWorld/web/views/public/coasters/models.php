<?php
require_once __DIR__ . '/../../../routes/Router.php';
Router::init();

$model = $_GET['model'] ?? '';

$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
/** @var bool $is_logged */
?>

<main class="container-fluid px-lg-5 my-5" data-logged="<?= $is_logged ? 'true' : 'false' ?>">
    <!-- Cabecera -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold border-bottom border-secondary pb-2 text-success text-center">
                Modelos de Montañas Rusas
            </h1>
        </div>
    </div>

    <!-- Contenedor principal 3 columnas -->
    <div class="row g-4">
        <!-- IZQUIERDA: Lista de Modelos -->
        <aside class="col-12 col-lg-3 order-1 order-lg-1" id="sidebar-models">
            <div class="card bg-dark shadow-sm border-secondary sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-list me-2"></i>Todos los Modelos</h5>
                </div>
                <div class="card-body p-3">
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0 border-success bg-dark text-white"
                            id="model-manufacter-filter">
                            <option value="">Cualquier Fabricante</option>
                        </select>
                    </div>
                    <!-- Lista con scroll -->
                    <div class="list-group list-group-flush rounded-0" id="models-list"
                        style="max-height: 60vh; overflow-y: auto;">
                        <div class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin"></i> Cargando
                            modelos...</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- CENTRO: Coasters del Modelo Seleccionado -->
        <div class="col-12 col-lg-6 order-3 order-lg-2">
            <div class="d-flex align-items-center gap-3 mb-2">
                <i class="fa-solid fa-puzzle-piece text-success" style="font-size: 2.5rem;"></i>
                <h2 class="fw-bold text-white mb-0" style="font-size:2rem; letter-spacing: -1px;"
                    id="current-model-title">
                    <?= htmlspecialchars($model ?: 'Selecciona un modelo') ?>
                </h2>
            </div>
            <p class="text-secondary fs-6 mb-3 border-bottom border-secondary pb-2" id="current-model-subtitle">
                <?= $model ? 'Descubre todas las montañas rusas de este modelo.' : 'Elige un modelo de la lista para ver sus montañas rusas.' ?>
            </p>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted fw-bold" id="coaster-count">0 resultados</span>
                <div class="d-flex align-items-center gap-2">
                    <select
                        class="form-select form-select-sm shadow-sm rounded-0 w-auto border-success bg-dark text-white"
                        id="sort-filter" style="cursor: pointer; font-weight: 500;">
                        <option value="id" selected>Por defecto</option>
                        <option value="name">Nombre</option>
                        <option value="height">Altura</option>
                        <option value="speed">Velocidad</option>
                        <option value="year">Año de apertura</option>
                    </select>
                    <button id="sort-direction-btn"
                        class="btn btn-sm btn-outline-success shadow-sm rounded-0 flex-shrink-0" type="button"
                        title="Cambiar orden">
                        <i class="fa-solid fa-arrow-down-wide-short"></i>
                    </button>
                    <input type="hidden" id="sort-direction" value="DESC">
                </div>
            </div>

            <div class="list-group shadow-sm rounded-0" id="coaster-list" style="border-radius: 0; min-height: 300px;">
                <!-- Cargado por JS -->
            </div>
            <div class="pagination mt-4 justify-content-center" id="pagination"></div>
        </div>

        <!-- DERECHA: Buscador de Modelos -->
        <div class="col-12 col-lg-3 order-2 order-lg-3">
            <div class="sticky-top" style="top: 90px; z-index: 100;">
                <div class="position-relative">
                    <input type="text" id="model-search" name="model-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0 bg-dark text-white"
                        placeholder="Buscar modelo..." style="border-width: 2px;">
                    <i id="search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 15px; top: 50%; transform: translateY(-50%); cursor: text;"></i>
                    <!-- Resultados Flotantes -->
                    <div id="search-results" class="list-group position-absolute w-100 shadow-lg mt-1 rounded-0"
                        style="z-index: 1050; text-align: left; display: none; max-height: 400px; overflow-y: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
    window.INITIAL_MODEL = <?= json_encode($model) ?>;
</script>
<script src="<?= Router::asset('web/js/coasters/models.js') ?>?v=<?= time() ?>"></script>