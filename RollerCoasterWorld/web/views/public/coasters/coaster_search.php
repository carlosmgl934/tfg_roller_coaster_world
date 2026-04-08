<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>
<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">Base de Datos de Montañas Rusas
            </h1>
        </div>
    </div>
    <div class="row g-4">
        <!-- IZQUIERDA: Filtros -->
        <aside class="col-12 col-lg-3 sidebar-filter" id="sidebar-filter">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="status-filter">
                        <label class="form-check-label" for="status-filter">Sólo Abiertas</label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="ridden-filter">
                        <label class="form-check-label" for="ridden-filter">Ya montadas</label>
                    </div>
                    <div class="mb-3">
                        <label for="height-filter" class="form-label d-flex justify-content-between">
                            Altura mínima <span class="badge bg-success" id="height-val">0m</span>
                        </label>
                        <input type="range" class="form-range" id="height-filter" min="0" max="200" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="speed-filter" class="form-label d-flex justify-content-between">
                            Velocidad mínima <span class="badge bg-success" id="speed-val">0km/h</span>
                        </label>
                        <input type="range" class="form-range" id="speed-filter" min="0" max="300" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="length-filter" class="form-label d-flex justify-content-between">
                            Longitud mínima <span class="badge bg-success" id="length-val">0m</span>
                        </label>
                        <input type="range" class="form-range" id="length-filter" min="0" max="5000" value="0">
                    </div>
                    <div class="mb-4">
                        <label for="inversions-filter" class="form-label d-flex justify-content-between">
                            Inversiones mínimas <span class="badge bg-success" id="inversions-val">0</span>
                        </label>
                        <input type="range" class="form-range" id="inversions-filter" min="0" max="20" value="0">
                    </div>
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="manufacter-filter">
                            <option value="">Fabricante</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="country-filter">
                            <option value="">País</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" name="year-select" id="year-select">
                            <option value="">Fecha de Apertura</option>
                            <?php for ($i = date('Y') + 3; $i >= 1870; $i--) { ?>
                                <option value="<?= $i ?>">
                                    <?= $i ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
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
        <!-- CENTRO: Lista -->
        <div class="col-12 col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <p class="mb-0 text-muted fw-bold" id="coaster-count"></p>
                <div class="d-flex align-items-center">
                    <select class="form-select shadow-sm rounded-0 w-auto border-success me-2" id="sort-filter" style="cursor: pointer; font-weight: 500;">
                        <option value="id" selected>Por defecto</option>
                        <option value="name">Nombre</option>
                        <option value="height">Altura</option>
                        <option value="speed">Velocidad</option>
                        <option value="year">Año de apertura</option>
                    </select>
                    <button id="sort-direction-btn" class="btn btn-outline-success shadow-sm rounded-0" type="button" title="Cambiar orden">
                         <i class="fa-solid fa-arrow-down-wide-short"></i>
                    </button>
                    <input type="hidden" id="sort-direction" value="DESC">
                </div>
            </div>
            <div class="list-group shadow-sm rounded-0" id="coaster-list" style="border-radius: 0;"></div>
            <div class="pagination mt-4 justify-content-center" id="pagination"></div>
        </div>
        <!-- DERECHA: Buscador -->
        <div class="col-12 col-lg-3">
            <div class="sticky-top" style="top: 90px; z-index: 100;">
                <div class="position-relative">
                    <input type="text" id="coaster-search" name="coaster-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0" placeholder="Buscar por nombre..."
                        style="border-width: 2px;">
                    <i id="search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 15px; top: 50%; transform: translateY(-50%); cursor: text;"></i>
                    <!-- Resultados Flotantes -->
                    <div id="search-results" class="list-group position-absolute w-100 shadow-lg mt-1"
                        style="z-index: 1050; text-align: left; display: none; max-height: 400px; overflow-y: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/coasters/coasters.js') ?>"></script>