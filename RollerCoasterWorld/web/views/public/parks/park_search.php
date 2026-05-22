<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>

<!-- Reutilizamos el mismo CSS -->
<main class="container-fluid px-lg-5 my-5">
  <div class="row mb-4">
    <div class="col-12">
      <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">
        Base de Datos de Parques de Atracciones
      </h1>
    </div>
  </div>

  <div class="row g-4">
    <!-- IZQUIERDA: Filtros -->
    <aside class="col-12 col-lg-3 sidebar-filter order-1 order-lg-1" id="sidebar-filter">
      <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
        <div class="card-header bg-success text-white rounded-0">
          <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
          <!-- País -->
          <div class="mb-3">
            <select id="country-filter" class="form-select shadow-sm rounded-0">
              <option value="Todos">País</option>
            </select>
          </div>

          <!-- Ubicación / Ciudad -->
          <div class="mb-3">
            <input type="text" id="location-filter" class="form-control shadow-sm rounded-0" placeholder="Ciudad">
          </div>

          <!-- Año de apertura mínima -->
          <div class="mb-3">
            <label for="opening-year-min" class="form-label d-flex justify-content-between">
              Año de apertura (Desde) <span class="badge bg-success" id="year-val">1800</span>
            </label>
            <input type="range" class="form-range" id="opening-year-min" min="1800" max="<?= date('Y') + 5 ?>"
              value="1800">
          </div>

          <!-- Número de montañas rusas mínimo -->
          <div class="mb-4">
            <label for="num-coaster-min" class="form-label d-flex justify-content-between">
              Nº montañas rusas mínimo <span class="badge bg-success" id="coasters-val">0</span>
            </label>
            <input type="range" class="form-range" id="num-coaster-min" min="0" max="50" value="0">
          </div>


          <!-- Botones limpiar y filtrar -->
          <div class="d-grid gap-2">
            <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-filtrar">
              <i class="fa-solid fa-filter me-2"></i>Filtrar
            </button>
            <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="clear-filters">
              <i class="fa-solid fa-eraser me-2"></i>Limpiar filtros
            </button>
          </div>
        </div>
      </div>
    </aside>

    <!-- CENTRO: Lista -->
    <div class="col-12 col-lg-6 order-3 order-lg-2">
      <!-- Fila 1: Controles de ordenar (fila propia, ancho completo) -->
      <div class="d-flex justify-content-end align-items-center mb-2">
        <select id="sort-filter" class="form-select shadow-sm rounded-0 w-auto border-success me-2"
          style="cursor: pointer; font-weight: 500;">
          <option value="coasters" selected>Cantidad de montañas rusas</option>
          <option value="name">Nombre</option>
          <option value="stars">Valoración</option>
          <option value="year">Año de apertura</option>
        </select>
        <button id="sort-direction-btn" class="btn btn-outline-success shadow-sm rounded-0 flex-shrink-0" type="button"
          title="Cambiar orden">
          <i class="fa-solid fa-arrow-down-wide-short"></i>
        </button>
        <input type="hidden" id="sort-direction" value="DESC">
      </div>
      <!-- Fila 2: Contador de resultados -->
      <p class="mb-3 text-muted fw-bold" id="park-count"></p>

      <div id="park-list" class="list-group shadow-sm rounded-0" style="border-radius: 0;">
        <div class="list-group-item text-center py-5 border-0">
          <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Cargando parques...</span>
          </div>
          <p class="mt-3 text-muted">Cargando parques...</p>
        </div>
      </div>

      <div id="park-pagination" class="pagination mt-4 justify-content-center"></div>
    </div>

    <!-- DERECHA: Buscador -->
    <div class="col-12 col-lg-3 order-2 order-lg-3">
      <div class="sticky-top" style="top: 90px; z-index: 100;">
        <div class="position-relative mb-3">
          <input type="text" id="park-search" name="park-search"
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

<script src="<?= Router::asset('web/js/parks/parks.js') ?>"></script>