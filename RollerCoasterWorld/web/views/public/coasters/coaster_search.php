<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>
<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center" data-i18n="coasters.search.title">
                Base de Datos de Montañas Rusas
            </h1>
        </div>
    </div>
    <div class="row g-4">
        <!-- IZQUIERDA: Filtros -->
        <aside class="col-12 col-lg-3 sidebar-filter order-1 order-lg-1" id="sidebar-filter">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i><span
                            data-i18n="common.filters">Filtros</span></h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="status-filter">
                        <label class="form-check-label" for="status-filter" data-i18n="coasters.search.only_open">Sólo
                            Abiertas</label>
                    </div>
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="ridden-filter">
                        <label class="form-check-label" for="ridden-filter"
                            data-i18n="coasters.search.already_ridden">Ya probadas</label>
                    </div>
                    <div class="mb-3">
                        <label for="height-filter" class="form-label d-flex justify-content-between">
                            <span data-i18n="coasters.search.min_height">Altura mínima</span> <span
                                class="badge bg-success" id="height-val">0m</span>
                        </label>
                        <input type="range" class="form-range" id="height-filter" min="0" max="200" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="speed-filter" class="form-label d-flex justify-content-between">
                            <span data-i18n="coasters.search.min_speed">Velocidad mínima</span> <span
                                class="badge bg-success" id="speed-val">0km/h</span>
                        </label>
                        <input type="range" class="form-range" id="speed-filter" min="0" max="300" value="0">
                    </div>
                    <div class="mb-3">
                        <label for="length-filter" class="form-label d-flex justify-content-between">
                            <span data-i18n="coasters.search.min_length">Longitud mínima</span> <span
                                class="badge bg-success" id="length-val">0m</span>
                        </label>
                        <input type="range" class="form-range" id="length-filter" min="0" max="5000" value="0">
                    </div>
                    <div class="mb-4">
                        <label for="inversions-filter" class="form-label d-flex justify-content-between">
                            <span data-i18n="coasters.search.min_inversions">Inversiones mínimas</span> <span
                                class="badge bg-success" id="inversions-val">0</span>
                        </label>
                        <input type="range" class="form-range" id="inversions-filter" min="0" max="20" value="0">
                    </div>
                    <div class="mb-3 position-relative">
                        <input type="text" id="filter-park-search"
                            class="form-control shadow-sm rounded-0 border-success ac-input-select"
                            data-i18n-placeholder="coasters.search.park_placeholder" placeholder="Parque"
                            style="border-width: 1px; box-shadow: none; background-color: var(--rcw-bg-card-alt); color: var(--rcw-text-primary);">
                        <input type="hidden" id="park-filter" name="park_id" value="">
                        <div id="filter-park-results" class="ac-dropdown d-none"></div>
                    </div>
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="manufacter-filter">
                            <option value="" data-i18n="coasters.search.manufacturer">Fabricante</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="country-filter">
                            <option value="" data-i18n="common.country">País</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" name="year-select" id="year-select">
                            <option value="" data-i18n="coasters.search.opening_date">Fecha de Apertura</option>
                            <?php for ($i = date('Y') + 3; $i >= 1870; $i--) { ?>
                                <option value="<?= $i ?>">
                                    <?= $i ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-filtrar">
                            <i class="fa-solid fa-filter me-2"></i><span data-i18n="common.filter">Filtrar</span>
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-borrar">
                            <i class="fa-solid fa-eraser me-2"></i><span data-i18n="common.clear_filters">Limpiar
                                filtros</span>
                        </button>
                    </div>
                </div>
            </div>
        </aside>
        <!-- CENTRO: Lista -->
        <div class="col-12 col-lg-6 order-3 order-lg-2">
            <!-- Fila 1: Controles de ordenar (fila propia, ancho completo) -->
            <div class="d-flex justify-content-end align-items-center mb-2">
                <select class="form-select shadow-sm rounded-0 w-auto border-success me-2" id="sort-filter"
                    style="cursor: pointer; font-weight: 500;">
                    <option value="id" selected data-i18n="common.sort_default">Por defecto</option>
                    <option value="name" data-i18n="common.sort_name">Nombre</option>
                    <option value="height" data-i18n="coasters.search.sort_height">Altura</option>
                    <option value="speed" data-i18n="coasters.search.sort_speed">Velocidad</option>
                    <option value="year" data-i18n="coasters.search.sort_year">Año de apertura</option>
                </select>
                <button id="sort-direction-btn" class="btn btn-outline-success shadow-sm rounded-0 flex-shrink-0"
                    type="button" data-i18n-attr="title" data-i18n="common.change_order" title="Cambiar orden">
                    <i class="fa-solid fa-arrow-down-wide-short"></i>
                </button>
                <input type="hidden" id="sort-direction" value="DESC">
            </div>
            <!-- Fila 2: Contador de resultados -->
            <p class="mb-3 text-muted fw-bold" id="coaster-count"></p>
            <div class="list-group shadow-sm rounded-0" id="coaster-list" style="border-radius: 0;"></div>
            <div class="pagination mt-4 justify-content-center" id="pagination"></div>
        </div>
        <!-- DERECHA: Buscador -->
        <div class="col-12 col-lg-3 order-2 order-lg-3">
            <div class="sticky-top" style="top: 90px; z-index: 100;">
                <div class="position-relative">
                    <input type="text" id="coaster-search" name="coaster-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        data-i18n-placeholder="common.search_by_name" placeholder="Buscar por nombre..."
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
<script src="<?= Router::asset('web/js/coasters/coasters.js') ?>?v=<?= time() ?>"></script>