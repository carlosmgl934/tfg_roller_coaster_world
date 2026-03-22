<?php
require_once __DIR__ . '/../partials/header.php';

if (!$is_logged || !$is_admin) {
    Router::redirect('login');
    exit;
}
?>

<link rel="stylesheet" href="<?= Router::asset('web/css/coasters.css') ?>">
<link rel="stylesheet" href="<?= Router::asset('web/css/admin.css') ?>">

<main class="container-fluid px-lg-5 my-5">

    <!-- Cabecera -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-0">
                Gestión de Coasters
            </h1>
            <a href="#" class="btn btn-success fw-bold rounded-0 shadow-sm px-4" id="btn-add-coaster">
                <i class="fa-solid fa-plus me-2"></i>Añadir coaster
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===================== IZQUIERDA: Filtros ===================== -->
        <aside class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">

                    <!-- Solo abiertas -->
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="filter-open-only">
                        <label class="form-check-label" for="filter-open-only">Sólo Operativas</label>
                    </div>

                    <!-- Fabricante -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-manufacter">
                            <option value="">Todos los fabricantes</option>
                            <option value="__null__">Desconocido</option>
                        </select>
                    </div>

                    <!-- País -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-country">
                            <option value="">Todos los países</option>
                            <option value="__null__">Desconocido</option>
                        </select>
                    </div>

                    <!-- Parque -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-park">
                            <option value="">Todos los parques</option>
                            <option value="__null__">Desconocido</option>
                        </select>
                    </div>

                    <!-- Año de apertura -->
                    <div class="mb-4">
                        <select class="form-select shadow-sm rounded-0" id="filter-year">
                            <option value="">Año apertura (todos)</option>
                            <?php for ($i = date('Y') + 3; $i >= 1870; $i--): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Altura mínima -->
                    <div class="mb-3">
                        <label for="filter-height" class="form-label d-flex justify-content-between">
                            Altura mínima <span class="badge bg-success" id="height-val">0 m</span>
                        </label>
                        <input type="range" class="form-range" id="filter-height" min="0" max="200" value="0">
                    </div>

                    <!-- Velocidad mínima -->
                    <div class="mb-4">
                        <label for="filter-speed" class="form-label d-flex justify-content-between">
                            Velocidad mínima <span class="badge bg-success" id="speed-val">0 km/h</span>
                        </label>
                        <input type="range" class="form-range" id="filter-speed" min="0" max="300" value="0">
                    </div>

                    <!-- Botones -->
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

        <!-- ===================== DERECHA: Lista ===================== -->
        <div class="col-12 col-lg-9">

            <!-- Barra de búsqueda -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <div class="flex-grow-1 position-relative">
                    <input type="text" id="admin-coaster-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar montaña rusa"
                        style="border-width: 2px;">
                    <i id="admin-search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="admin-coaster-count"></p>

            <!-- Lista de coasters — el JS rellena filas con este HTML de ejemplo: -->
            <!--
            <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
                <div class="flex-grow-1">
                    <h6 class="mb-0 fw-bold text-success">Nombre del coaster</h6>
                    <small class="text-muted">Fabricante • Parque • País • Año</small>
                </div>
                <div class="d-flex gap-2 ms-3">
                    <a href="..." class="btn btn-sm btn-outline-primary rounded-0"><i class="fa-solid fa-pen"></i> Editar</a>
                    <button class="btn btn-sm btn-outline-danger rounded-0 btn-delete-coaster" data-id="..." data-name="..."><i class="fa-solid fa-trash"></i> Eliminar</button>
                </div>
            </div>
            -->
            <div class="list-group shadow-sm rounded-0" id="admin-coaster-list">
                <div class="list-group-item text-center text-muted py-5" id="admin-coaster-loading">
                     <i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>
                    Las coasters se cargarán aquí.
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-coaster-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>

<!-- ===================== MODAL CONFIRMAR ELIMINAR ===================== -->
<div class="modal fade" id="modal-delete-coaster" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar coaster
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">¿Estás seguro de que quieres eliminar:</p>
                <p class="fw-bold text-danger mb-0" id="delete-coaster-name">—</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold"
                    id="confirm-delete-coaster" data-id="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>

<script src="<?= Router::asset('web/js/admin.js') ?>"></script>
