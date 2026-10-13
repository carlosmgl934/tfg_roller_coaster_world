<?php
$page_css = [
  'web/css/tops_personal.css?v=' . time(),
  'https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.css'
];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

// Si no está logueado, redirigir
if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}
?>

<main class="container-fluid px-lg-5 my-5">
  <div class="row mb-4">
    <div class="col-12">
      <h2 class="fw-black border-bottom pb-2 text-success text-center">
        <i class="fa-solid fa-list-ol me-2"></i><span data-i18n="top_personal.title">Mi Top Personal</span>
      </h2>
      <p class="text-center text-white-50 small fw-bold text-uppercase tracking-wider mt-2 mb-0"
        data-i18n="top_personal.subtitle">Gestiona y organiza tus
        rankings de montañas rusas y parques</p>
    </div>
  </div>

  <div class="tops-personal-container">

    <!-- Navegación por pestañas -->
    <div class="row g-0 mb-4" style="border: 1px solid rgba(255,255,255,0.1);">
      <ul class="nav nav-pills w-100 rcw-nav-pills" id="tops-tabs" role="tablist">
        <li class="nav-item col-6" role="presentation">
          <button class="nav-link w-100 active rounded-0 fw-bold" id="coasters-tab" data-bs-toggle="tab"
            data-bs-target="#coasters-pane" type="button" role="tab" style="color:#fff;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="16" height="16" class="me-2"
              style="vertical-align:middle;">
              <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" fill="none" stroke="currentColor"
                stroke-width="4" stroke-linecap="round" />
              <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" fill="none" stroke="currentColor"
                stroke-width="4" stroke-linecap="round" />
              <line x1="16" y1="42" x2="16" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
              <line x1="32" y1="20" x2="32" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
              <line x1="48" y1="24" x2="48" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
              <line x1="24" y1="28" x2="16" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                opacity="0.6" />
              <line x1="40" y1="16" x2="32" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                opacity="0.6" />
              <rect x="23" y="10" width="10" height="6" rx="2" fill="currentColor" />
              <circle cx="25" cy="18" r="2" fill="currentColor" />
              <circle cx="31" cy="18" r="2" fill="currentColor" />
              <rect x="11" y="24" width="10" height="6" rx="2" fill="currentColor" transform="rotate(-40 16 27)" />
            </svg><span data-i18n="top_personal.tab_coasters">COASTERS</span> <span id="tab-coasters-count"
              class="badge bg-success text-dark ms-1 d-none">0</span>
          </button>
        </li>
        <li class="nav-item col-6" role="presentation">
          <button class="nav-link w-100 rounded-0 fw-bold" id="parks-tab" data-bs-toggle="tab"
            data-bs-target="#parks-pane" type="button" role="tab" style="color:#fff;">
            <i class="fa-solid fa-map-location-dot me-2"></i><span data-i18n="top_personal.tab_parks">PARQUES</span>
            <span id="tab-parks-count" class="badge bg-success text-dark ms-1 d-none">0</span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Contenido principal -->
    <div class="tab-content mt-4" id="topsTabContent">

      <!-- Pestaña Coasters -->
      <div class="tab-pane fade show active" id="coasters-pane" role="tabpanel" tabindex="0">
        <div class="row g-4">

          <!-- IZQUIERDA: Filtros -->
          <aside class="col-12 col-lg-3 sidebar-filter order-1 order-lg-1" id="sidebar-filter-coasters">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 100px; z-index: 1;">
              <div
                class="card-header bg-success text-white rounded-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-filter me-2"></i><span
                    data-i18n="top_personal.filters_title">Filtros y Búsqueda</span></h5>
                <span class="tops-counter-pill"
                  style="background: rgba(0,0,0,0.2); border: none; color: #fff; padding: 0.25rem 0.6rem;">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="14" height="14"
                    style="vertical-align:-1px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-linecap="round">
                    <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" stroke-width="4" />
                    <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" stroke-width="4" />
                    <line x1="16" y1="42" x2="16" y2="60" stroke-width="3" />
                    <line x1="32" y1="20" x2="32" y2="60" stroke-width="3" />
                    <line x1="48" y1="24" x2="48" y2="60" stroke-width="3" />
                    <rect x="23" y="10" width="10" height="6" rx="2" fill="currentColor" stroke="none" />
                    <circle cx="25" cy="18" r="2" fill="currentColor" stroke="none" />
                    <circle cx="31" cy="18" r="2" fill="currentColor" stroke="none" />
                  </svg>
                  <span id="coasters-count" class="ms-1">0</span>
                </span>
              </div>
              <div class="card-body bg-dark border border-secondary border-top-0 p-0">
                <div class="tops-filters-bar">
                  <!-- Cabecera colapsable solo móvil -->
                  <button class="tops-filters-mobile-toggle" id="coasters-filters-toggle" aria-expanded="false"
                    aria-controls="coasters-filters-collapsible">
                    <span>
                      <i class="fa-solid fa-sliders me-2" style="color: var(--rcw-green);"></i>
                      <span data-i18n="top_personal.filters_toggle">Filtros y criterio</span>
                      <span class="tops-filters-active-badge d-none" id="coasters-filters-badge"></span>
                    </span>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                  </button>
                  <div class="tops-filters-collapsible" id="coasters-filters-collapsible">
                    <div class="tops-filters-grid">
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-arrow-down-short-wide"></i><span
                            data-i18n="top_personal.sort_label">Criterio</span></label>
                        <div class="tops-sort-row">
                          <select id="coasters-sort" class="tops-filter-select tops-sort-select">
                            <option value="rank" data-i18n="top_personal.sort_rank">Mi Ranking</option>
                            <option value="name" data-i18n="top_personal.sort_az">A-Z</option>
                            <option value="height" data-i18n="top_personal.sort_height">Altura</option>
                            <option value="speed" data-i18n="top_personal.sort_speed">Velocidad</option>
                            <option value="length" data-i18n="top_personal.sort_length">Longitud</option>
                            <option value="inversions" data-i18n="top_personal.sort_inversions">Inversiones</option>
                            <option value="year" data-i18n="top_personal.sort_year">Antigüedad</option>
                          </select>
                          <button id="coasters-sort-dir" class="tops-sort-dir-btn" title="Dirección" data-dir="asc">
                            <i class="fa-solid fa-caret-up"></i>
                          </button>
                        </div>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-tree-city"></i><span
                            data-i18n="top_personal.filter_park">Parque</span></label>
                        <select id="coasters-filter-park" class="tops-filter-select">
                          <option value="" data-i18n="top_personal.filter_all">Todos</option>
                        </select>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-earth-americas"></i><span
                            data-i18n="top_personal.filter_country">País</span></label>
                        <select id="coasters-filter-country" class="tops-filter-select">
                          <option value="" data-i18n="top_personal.filter_all">Todos</option>
                        </select>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-industry"></i><span
                            data-i18n="top_personal.filter_manufacturer">Fabricante</span></label>
                        <select id="coasters-filter-manufacter" class="tops-filter-select">
                          <option value="" data-i18n="top_personal.filter_all">Todos</option>
                        </select>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-gears"></i><span
                            data-i18n="top_personal.filter_model">Modelo</span></label>
                        <select id="coasters-filter-model" class="tops-filter-select">
                          <option value="" data-i18n="top_personal.filter_all">Todos</option>
                        </select>
                      </div>

                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-power-off"></i><span
                            data-i18n="top_personal.filter_status">Estado</span></label>
                        <div class="form-check form-switch mt-1">
                          <input class="form-check-input bg-dark border-secondary" type="checkbox"
                            id="coasters-filter-operating" style="cursor:pointer;">
                          <label class="form-check-label text-white-50 ms-1" for="coasters-filter-operating"
                            style="font-size:0.85rem;cursor:pointer;"
                            data-i18n="top_personal.filter_only_operating">Solo operativas</label>
                        </div>
                      </div>

                      <div class="tops-filter-item slider-item mt-2" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label">
                          <span class="d-flex align-items-center gap-1"><i class="fa-solid fa-ruler-vertical"></i><span
                              data-i18n="top_personal.slider_height">Altura (m)</span></span>
                          <span id="val-height" class="slider-val-badge"></span>
                        </label>
                        <div id="slider-height" class="mt-2 mx-1 mb-0"></div>
                      </div>

                      <div class="tops-filter-item slider-item mt-2" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label">
                          <span class="d-flex align-items-center gap-1"><i class="fa-solid fa-bolt"></i><span
                              data-i18n="top_personal.slider_speed">Velocidad (km/h)</span></span>
                          <span id="val-speed" class="slider-val-badge"></span>
                        </label>
                        <div id="slider-speed" class="mt-2 mx-1 mb-0"></div>
                      </div>

                      <div class="tops-filter-item slider-item mt-2" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label">
                          <span class="d-flex align-items-center gap-1"><i
                              class="fa-solid fa-ruler-horizontal"></i><span
                              data-i18n="top_personal.slider_length">Longitud (m)</span></span>
                          <span id="val-length" class="slider-val-badge"></span>
                        </label>
                        <div id="slider-length" class="mt-2 mx-1 mb-0"></div>
                      </div>

                      <div class="tops-filter-item slider-item mt-2" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label">
                          <span class="d-flex align-items-center gap-1"><i class="fa-solid fa-infinity"></i><span
                              data-i18n="top_personal.slider_inversions">Inversiones</span></span>
                          <span id="val-inversions" class="slider-val-badge"></span>
                        </label>
                        <div id="slider-inversions" class="mt-2 mx-1 mb-0"></div>
                      </div>

                      <div class="tops-filter-item slider-item mt-2" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label">
                          <span class="d-flex align-items-center gap-1"><i class="fa-regular fa-calendar"></i><span
                              data-i18n="top_personal.slider_year">Antigüedad (Año)</span></span>
                          <span id="val-year" class="slider-val-badge"></span>
                        </label>
                        <div id="slider-year" class="mt-2 mx-1 mb-0"></div>
                      </div>
                    </div>
                    <!-- Botón limpiar filtros coasters -->
                    <div class="tops-filters-clear-row" id="coasters-clear-row" style="display:none;">
                      <button id="btn-clear-coasters" class="tops-filters-clear-btn" type="button">
                        <i class="fa-solid fa-xmark"></i>
                        <span data-i18n="top_personal.clear_filters">Limpiar filtros</span>
                      </button>
                    </div>
                  </div><!-- /tops-filters-collapsible -->
                </div>
              </div>
            </div>
          </aside>

          <!-- Columna principal (Lista/Grid) -->
          <div class="col-12 col-lg-9 order-2 order-lg-2">
            <div
              class="d-flex justify-content-between align-items-center mb-3 bg-dark border border-secondary p-2 rounded-0 shadow-sm flex-wrap gap-2"
              style="border-top: 4px solid var(--rcw-green-neon, #00c853) !important;">
              <div class="d-flex align-items-center gap-2 ms-2">
                <i class="fa-solid fa-list-ol text-success fs-5"></i>
                <h4 class="mb-0 text-white text-uppercase" style="letter-spacing: 0.05em; font-weight: 900;"
                  data-i18n="top_personal.heading">MIS TOPS
                </h4>
              </div>
              <div class="d-flex align-items-center flex-wrap gap-2">
                <div class="btn-group me-lg-3">
                  <button class="btn btn-sm btn-outline-secondary" id="coasters-view-mini"
                    data-i18n-title="top_personal.view_compact" title="Vista Compacta"><i
                      class="fa-solid fa-bars"></i></button>
                  <button class="btn btn-sm btn-outline-secondary active" id="view-list"
                    data-i18n-title="top_personal.view_list" title="Vista Lista"><i
                      class="fa-solid fa-list"></i></button>
                  <button class="btn btn-sm btn-outline-secondary" id="view-grid"
                    data-i18n-title="top_personal.view_grid" title="Vista Cuadrícula"><i
                      class="fa-solid fa-grip"></i></button>
                </div>
                <div class="d-flex gap-2">
                  <a href="<?= Router::url('edit_top') ?>"
                    class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm" id="btn-mode-edit">
                    <i class="fa-solid fa-pen-to-square me-1"></i> <span data-i18n="top_personal.btn_edit_top">Editar
                      Top</span>
                  </a>
                </div>
              </div>
            </div>

            <div id="coasters-container" class="mt-3">
              <div class="card bg-dark border-secondary rounded-0 shadow-sm p-5 text-center w-100">
                <i class="fa-solid fa-spinner fa-spin fs-1 mb-3 opacity-50 text-muted"></i>
                <h5 class="text-white-50" data-i18n="top_personal.loading_coasters">Cargando coasters...</h5>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Pestaña Parques -->
      <div class="tab-pane fade" id="parks-pane" role="tabpanel" tabindex="0">
        <div class="row g-4">

          <!-- IZQUIERDA: Filtros -->
          <aside class="col-12 col-lg-3 sidebar-filter order-1 order-lg-1" id="sidebar-filter-parks">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 100px; z-index: 1;">
              <div
                class="card-header bg-success text-white rounded-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-filter me-2"></i><span
                    data-i18n="top_personal.filters_title">Filtros y Búsqueda</span></h5>
                <span class="tops-counter-pill"
                  style="background: rgba(0,0,0,0.2); border: none; color: #fff; padding: 0.25rem 0.6rem;">
                  <i class="fa-solid fa-map-location-dot me-1"></i><span id="parks-count">0</span>
                </span>
              </div>
              <div class="card-body bg-dark border border-secondary border-top-0 p-0">
                <div class="tops-filters-bar">
                  <button class="tops-filters-mobile-toggle" id="parks-filters-toggle" aria-expanded="false"
                    aria-controls="parks-filters-collapsible">
                    <span>
                      <i class="fa-solid fa-sliders me-2" style="color: var(--rcw-green);"></i>
                      <span data-i18n="top_personal.filters_toggle">Filtros y criterio</span>
                      <span class="tops-filters-active-badge d-none" id="parks-filters-badge"></span>
                    </span>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                  </button>
                  <div class="tops-filters-collapsible" id="parks-filters-collapsible">
                    <div class="tops-filters-grid" style="grid-template-columns: 1fr;">
                      <div class="tops-filter-item" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label"><i class="fa-solid fa-sort me-1"></i><span
                            data-i18n="top_personal.sort_order_label">Ordenar</span></label>
                        <div class="tops-sort-row">
                          <select id="parks-sort" class="tops-filter-select tops-sort-select">
                            <option value="rank" data-i18n="top_personal.sort_rank">Mi Ranking</option>
                            <option value="name" data-i18n="top_personal.sort_name_az">Nombre A-Z</option>
                            <option value="coasters" data-i18n="top_personal.sort_num_coasters">Nº Coasters</option>
                          </select>
                          <button id="parks-sort-dir" class="tops-sort-dir-btn" title="Dirección" data-dir="asc">
                            <i class="fa-solid fa-caret-up"></i>
                          </button>
                        </div>
                      </div>
                      <div class="tops-filter-item" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label"><i class="fa-solid fa-earth-europe me-1"></i><span
                            data-i18n="top_personal.filter_country">País</span></label>
                        <select id="parks-filter-country" class="tops-filter-select">
                          <option value="" data-i18n="top_personal.filter_all_countries">Todos los países</option>
                        </select>
                      </div>

                      <div class="tops-filter-item slider-item mt-2" style="grid-column: 1 / -1;">
                        <label class="tops-filter-label">
                          <span class="d-flex align-items-center gap-1"><i class="fa-solid fa-roller-coaster"></i><span
                              data-i18n="top_personal.slider_num_coasters">Nº Coasters</span></span>
                          <span id="val-parks-coasters" class="slider-val-badge"></span>
                        </label>
                        <div id="slider-parks-coasters" class="mt-2 mx-1 mb-0"></div>
                      </div>
                    </div>
                    <!-- Botón limpiar filtros parques -->
                    <div class="tops-filters-clear-row" id="parks-clear-row" style="display:none;">
                      <button id="btn-clear-parks" class="tops-filters-clear-btn" type="button">
                        <i class="fa-solid fa-xmark"></i>
                        <span data-i18n="top_personal.clear_filters">Limpiar filtros</span>
                      </button>
                    </div>
                  </div><!-- /parks-filters-collapsible -->
                </div>
              </div>
            </div>
          </aside>

          <!-- Columna principal (Lista/Grid) -->
          <div class="col-12 col-lg-9 order-2 order-lg-2">
            <div
              class="d-flex justify-content-between align-items-center mb-3 bg-dark border border-secondary p-2 rounded-0 shadow-sm flex-wrap gap-2"
              style="border-top: 4px solid var(--rcw-green-neon, #00c853) !important;">
              <div class="d-flex align-items-center gap-2 ms-2">
                <i class="fa-solid fa-list-ol text-success fs-5"></i>
                <h4 class="mb-0 text-white text-uppercase" style="letter-spacing: 0.05em; font-weight: 900;"
                  data-i18n="top_personal.heading">MIS TOPS
                </h4>
              </div>
              <div class="d-flex align-items-center flex-wrap gap-2">
                <div class="btn-group me-lg-3">
                  <button class="btn btn-sm btn-outline-secondary" id="parks-view-mini"
                    data-i18n-title="top_personal.view_compact" title="Vista Compacta"><i
                      class="fa-solid fa-bars"></i></button>
                  <button class="btn btn-sm btn-outline-secondary active" id="view-list-parks"
                    data-i18n-title="top_personal.view_list" title="Vista Lista"><i
                      class="fa-solid fa-list"></i></button>
                  <button class="btn btn-sm btn-outline-secondary" id="view-grid-parks"
                    data-i18n-title="top_personal.view_grid" title="Vista Cuadrícula"><i
                      class="fa-solid fa-grip"></i></button>
                </div>
                <div class="d-flex gap-2">
                  <a href="<?= Router::url('edit_top') ?>"
                    class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm" id="btn-mode-edit-parks">
                    <i class="fa-solid fa-pen-to-square me-1"></i> <span data-i18n="top_personal.btn_edit_top">Editar
                      Top</span>
                  </a>
                </div>
              </div>
            </div>

            <div id="parks-container" class="mt-3">
              <div class="card bg-dark border-secondary rounded-0 shadow-sm p-5 text-center w-100">
                <i class="fa-solid fa-spinner fa-spin fs-1 mb-3 opacity-50 text-muted"></i>
                <h5 class="text-white-50" data-i18n="top_personal.loading_parks">Cargando parques...</h5>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<style>
  /* Estilos dark-theme para noUiSlider */
  .noUi-target {
    background: #2a323a;
    border: 1px solid #3d4752;
    box-shadow: inset 0 1px 1px #111;
    height: 8px;
  }

  .noUi-connect {
    background: var(--rcw-green-neon, #00c853);
  }

  .noUi-horizontal .noUi-handle {
    width: 18px;
    height: 18px;
    right: -9px;
    top: -6px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid var(--rcw-green-neon, #00c853);
    box-shadow: 0 0 4px rgba(0, 0, 0, 0.5);
    cursor: grab;
  }

  .noUi-handle:after,
  .noUi-handle:before {
    display: none;
  }

  .noUi-active {
    cursor: grabbing !important;
  }

  /* Switch verde al activar */
  .form-switch .form-check-input:checked {
    background-color: var(--rcw-green-neon, #00c853) !important;
    border-color: var(--rcw-green-neon, #00c853) !important;
  }

  .form-switch .form-check-input:focus {
    box-shadow: 0 0 0 0.25rem rgba(0, 200, 83, 0.25) !important;
    border-color: var(--rcw-green-neon, #00c853) !important;
  }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/noUiSlider/15.7.1/nouislider.min.js"></script>
<script src="<?= Router::asset('web/js/users/top_personal.js') ?>?v=<?= time() ?>"></script>