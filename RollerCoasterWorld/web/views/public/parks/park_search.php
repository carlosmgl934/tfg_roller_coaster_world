<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css"> <!-- Reutilizamos el mismo CSS -->

<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">Base de Datos de Parques de Atracciones</h1>
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
                    <!-- País -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">País</label>
                        <select class="form-select" id="country-filter">
                            <option value="">Todos</option>
                            <!-- Se llenará dinámicamente con JS o desde BD -->
                        </select>
                    </div>

                    <!-- Ciudad / Ubicación -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Ubicación / Ciudad</label>
                        <input type="text" class="form-control" id="location-filter" placeholder="Ej: Orlando, Tokyo">
                    </div>

                    <!-- Año de apertura -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Año de apertura</label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control" id="opening-year-min" placeholder="Desde">
                            <input type="number" class="form-control" id="opening-year-max" placeholder="Hasta">
                        </div>
                    </div>

                    <!-- Número de montañas rusas -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Número de montañas rusas</label>
                        <div class="d-flex gap-2">
                            <input type="number" class="form-control" id="num-coaster-min" placeholder="Mín">
                            <input type="number" class="form-control" id="num-coaster-max" placeholder="Máx">
                        </div>
                    </div>

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

                    <!-- Botón limpiar filtros -->
                    <button class="btn btn-outline-light w-100" id="clear-filters">Limpiar filtros</button>
                </div>
            </div>
        </aside>

        <!-- DERECHA: Buscador + Listado -->
        <div class="col-12 col-lg-9">
            <!-- Buscador -->
            <div class="mb-4">
                <div class="position-relative">
                    <input type="text" id="park-search" class="form-control shadow-sm pe-5 border-success rounded-0" placeholder="Buscar por nombre de parque..." style="border-width: 2px;">
                    <i id="search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%); cursor: text;"></i>

                    <!-- Resultados flotantes -->
                    <div id="search-results" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1050; text-align: left; display: none; max-height: 400px; overflow-y: auto;"></div>
                </div>
            </div>

            <!-- Listado de parques -->
            <div id="park-list" class="row g-4">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Cargando parques...</span>
                    </div>
                    <p class="mt-3 text-muted">Cargando parques...</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script src="<?= $base_url ?>/web/js/parks.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>