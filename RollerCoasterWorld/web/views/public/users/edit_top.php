<?php
$page_css = [
  'web/css/profile.css?v=' . time(),
  'web/css/edit_top.css?v=' . time(),
];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

// Proteger la vista: requiere sesión activa
if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}
?>

<main class="container-fluid px-3 px-lg-5 my-4 edit-top-wrapper">

  <!-- ── Barra Superior Fija (Acciones y Guardado) ─── -->
  <div class="edit-top-sticky-header p-3 mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= Router::url('top_personal') ?>" id="btn-back-to-tops"
        class="btn btn-sm btn-outline-secondary rounded-0 px-3 fw-bold shadow-sm" title="Volver a mis tops">
        <i class="fa-solid fa-arrow-left me-2"></i><span data-i18n="edit_top.btn_back">Volver</span>
      </a>
      <div>
        <h4 class="fw-bold mb-0 text-white d-flex align-items-center gap-2" style="font-family: var(--rcw-font-title);">
          <i class="fa-solid fa-pen-to-square text-success"></i>
          <span data-i18n="edit_top.title">Editar Mi Top Personal</span>
        </h4>
        <small class="text-muted d-block" id="current-count-display" data-i18n="edit_top.subtitle">Organiza y reordena
          tus favoritos</small>
      </div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <!-- Indicador de cambios pendientes -->
      <span id="unsaved-indicator" class="unsaved-badge d-none">
        <span class="pulse-dot"></span>
        <span data-i18n="edit_top.unsaved">Cambios sin guardar</span>
      </span>

      <!-- Botón de guardado -->
      <button id="btn-save-top" class="btn btn-sm btn-success rounded-0 px-4 py-2 fw-bold shadow-sm">
        <i class="fa-solid fa-floppy-disk me-2"></i><span data-i18n="edit_top.btn_save">Guardar Cambios</span>
      </button>
    </div>
  </div>

  <!-- ── Pestañas Coasters / Parques ─── -->
  <ul class="nav nav-pills nav-fill mb-4 gap-2 edit-top-tabs" id="edit-tabs-nav" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active d-flex align-items-center justify-content-center" id="top-coasters-tab"
        type="button">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="18" height="18" class="me-2"
          style="vertical-align:-2px;flex-shrink:0;" fill="none" stroke="currentColor" stroke-linecap="round">
          <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" stroke-width="4" />
          <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" stroke-width="4" />
          <line x1="16" y1="42" x2="16" y2="60" stroke-width="3" />
          <line x1="32" y1="20" x2="32" y2="60" stroke-width="3" />
          <line x1="48" y1="24" x2="48" y2="60" stroke-width="3" />
          <rect x="23" y="10" width="10" height="6" rx="2" fill="currentColor" stroke="none" />
          <circle cx="25" cy="18" r="2" fill="currentColor" stroke="none" />
          <circle cx="31" cy="18" r="2" fill="currentColor" stroke="none" />
        </svg>
        <span data-i18n="edit_top.tab_coasters">Top Coasters</span>
        <span id="coasters-count-badge" class="badge ms-2 rounded-0">0</span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link d-flex align-items-center justify-content-center" id="top-parks-tab" type="button">
        <i class="fa-solid fa-map-location-dot me-2"></i>
        <span data-i18n="edit_top.tab_parks">Top Parques</span>
        <span id="parks-count-badge" class="badge ms-2 rounded-0">0</span>
      </button>
    </li>
  </ul>

  <!-- ── Barra de Herramientas (Buscador Añadir + Filtro en Lista) ─── -->
  <div class="edit-top-toolbar mb-4 shadow-sm">
    <div class="row g-3 align-items-center">

      <!-- Izquierda: Buscador para AÑADIR -->
      <div class="col-12 col-md-7" id="search-add-wrapper">
        <label for="search-add-input" class="form-label text-uppercase fw-bold text-muted"
          style="font-size: 0.72rem; letter-spacing: 0.05em;">
          <i class="fa-solid fa-plus-circle me-1 text-success"></i><span data-i18n="edit_top.search_add_label">Añadir
            elemento al ranking</span>
        </label>
        <div class="position-relative search-input-group">
          <input type="text" id="search-add-input" class="form-control"
            data-i18n-placeholder="edit_top.search_add_placeholder"
            placeholder="Buscar montaña rusa para añadir al top..." autocomplete="off">
          <div
            class="search-input-actions position-absolute top-50 end-0 translate-middle-y me-3 d-flex align-items-center gap-2">
            <button type="button" id="btn-restore-search" class="btn-restore-search disabled text-muted opacity-25"
              title="No hay búsquedas anteriores para restaurar" aria-label="Restaurar última búsqueda">
              <i class="fa-solid fa-rotate-right" id="restore-search-icon"></i>
            </button>
            <i class="fa-solid fa-magnifying-glass text-muted" id="search-add-icon" title="Buscar"></i>
          </div>

          <div id="autocomplete-dropdown" class="autocomplete-dropdown position-absolute w-100 d-none"></div>
        </div>
        <p class="mb-0 mt-1 text-muted" style="font-size:0.7rem; opacity:0.65;">
          <span data-i18n="edit_top.hint_restore">↺ recupera lo último que escribiste en el buscador</span>
        </p>
      </div>

      <!-- Derecha: Filtro rápido dentro de la lista actual y herramientas -->
      <div class="col-12 col-md-5">
        <label for="filter-current-input" class="form-label text-uppercase fw-bold text-muted"
          style="font-size: 0.72rem; letter-spacing: 0.05em;">
          <i class="fa-solid fa-filter me-1 text-info"></i><span data-i18n="edit_top.filter_label">Buscar en tu top
            actual</span>
        </label>
        <div class="d-flex gap-2">
          <div class="position-relative flex-grow-1 search-input-group">
            <input type="text" id="filter-current-input" class="form-control"
              data-i18n-placeholder="edit_top.filter_placeholder" placeholder="Filtrar por nombre..."
              autocomplete="off">
          </div>
          <button id="btn-reverse-order" class="btn btn-outline-secondary rounded-0 px-3 fw-bold"
            data-i18n-title="edit_top.btn_reverse" title="Invertir orden del top">
            <i class="fa-solid fa-arrow-down-up-across-line"></i>
          </button>
        </div>
      </div>

    </div>

    <!-- Indicación de uso -->
    <div
      class="mt-3 pt-2 border-top border-secondary border-opacity-25 d-flex flex-wrap align-items-center justify-content-between gap-2 text-muted small">
      <div>
        <i class="fa-solid fa-grip-lines text-success me-1"></i>
        <span data-i18n="edit_top.hint_drag">Arrastra las tarjetas para reordenar tu ranking libremente.</span>
      </div>
      <div>
        <i class="fa-solid fa-arrows-up-down text-info me-1"></i>
        <span data-i18n="edit_top.hint_position">O pulsa en el selector de posición para saltar a un puesto
          directo.</span>
      </div>
    </div>
  </div>

  <!-- ── Contenedor de la Lista Arrastrable ─── -->
  <div id="edit-top-list-container" class="edit-top-list">
    <!-- Inyectado dinámicamente vía TopEditor en edit_top.js -->
  </div>

</main>

<!-- ── Modal Confirmación RCW ─── -->
<div class="modal fade" id="rcw-confirm-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
    <div class="modal-content"
      style="background: #141e2a; border: 1px solid rgba(255, 255, 255, 0.12); box-shadow: 0 16px 40px rgba(0,0,0,0.85);">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation text-warning" id="rcw-confirm-icon"
            style="font-size: 1.25rem;"></i>
          <h5 class="modal-title fw-bold text-white mb-0" id="rcw-confirm-title"
            style="font-family: var(--rcw-font-title);" data-i18n="edit_top.confirm_title">Confirmación</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-3">
        <p class="text-white-50 mb-1" id="rcw-confirm-message" data-i18n="edit_top.confirm_message">¿Estás seguro de
          realizar esta acción?</p>
        <small class="text-muted opacity-75 d-block" id="rcw-confirm-subtext"></small>
      </div>
      <div class="modal-footer border-0 pt-0 gap-2">
        <button type="button" class="btn btn-sm btn-dark rounded-0 px-4 fw-bold" data-bs-dismiss="modal"
          id="rcw-confirm-btn-cancel" data-i18n="edit_top.btn_cancel">Cancelar</button>
        <button type="button" class="btn btn-sm btn-danger rounded-0 px-4 fw-bold" id="rcw-confirm-btn-ok"
          data-i18n="edit_top.btn_confirm">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Alerta/Notificación RCW ─── -->
<div class="modal fade" id="rcw-alert-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
    <div class="modal-content"
      style="background: #141e2a; border: 1px solid rgba(255, 255, 255, 0.12); box-shadow: 0 16px 40px rgba(0,0,0,0.85);">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-circle-info text-info" id="rcw-alert-icon" style="font-size: 1.25rem;"></i>
          <h5 class="modal-title fw-bold text-white mb-0" id="rcw-alert-title"
            style="font-family: var(--rcw-font-title);" data-i18n="edit_top.alert_title">Aviso</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-3">
        <p class="text-white-50 mb-0" id="rcw-alert-message" data-i18n="edit_top.alert_message">Mensaje del sistema</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-sm btn-success rounded-0 px-4 fw-bold" data-bs-dismiss="modal"
          id="rcw-alert-btn-ok" data-i18n="edit_top.btn_understood">Entendido</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Seleccionar Posición al Añadir ─── -->
<div class="modal fade" id="rcw-add-position-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
    <div class="modal-content"
      style="background: #141e2a; border: 1px solid rgba(255, 255, 255, 0.12); box-shadow: 0 16px 40px rgba(0,0,0,0.85);">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-circle-plus text-success" style="font-size: 1.25rem;"></i>
          <h5 class="modal-title fw-bold text-white mb-0" style="font-family: var(--rcw-font-title);"
            data-i18n="edit_top.add_modal_title">Añadir al Ranking
          </h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-3">
        <!-- Preview del elemento seleccionado -->
        <div class="d-flex align-items-center gap-3 p-2 mb-3"
          style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.08);">
          <img id="modal-add-preview-img" src="" alt=""
            style="width: 52px; height: 52px; object-fit: cover; border: 1px solid rgba(255,255,255,0.15);"
            onerror="this.src='https://www.themeparkexperts.com/wp-content/uploads/2019/07/themepark_cedarpoint_montanarusa.jpg'">
          <div class="min-w-0 flex-grow-1">
            <div class="text-white fw-bold text-truncate" id="modal-add-preview-title" style="font-size: 0.95rem;">
            </div>
            <div class="text-muted small text-truncate" id="modal-add-preview-sub" style="font-size: 0.78rem;"></div>
          </div>
        </div>

        <p class="text-white-50 small mb-3" data-i18n="edit_top.add_modal_question">¿En qué posición deseas colocar este
          elemento?</p>

        <!-- Opciones Rápidas: Principio / Final -->
        <div class="row g-2 mb-3">
          <div class="col-6">
            <button type="button"
              class="btn btn-outline-success w-100 py-2 d-flex flex-column align-items-center justify-content-center gap-1 rounded-0 btn-modal-quick-pos"
              id="btn-modal-add-first">
              <i class="fa-solid fa-angles-up fs-5"></i>
              <span class="fw-bold small" data-i18n="edit_top.add_modal_first">Al Principio</span>
              <span class="badge bg-success text-dark py-1 px-2" style="font-size: 0.7rem; font-weight: 800;">Puesto
                #1</span>
            </button>
          </div>
          <div class="col-6">
            <button type="button"
              class="btn btn-outline-warning w-100 py-2 d-flex flex-column align-items-center justify-content-center gap-1 rounded-0 btn-modal-quick-pos"
              id="btn-modal-add-last">
              <i class="fa-solid fa-angles-down fs-5"></i>
              <span class="fw-bold small" data-i18n="edit_top.add_modal_last">Al Final</span>
              <span class="badge bg-warning text-dark py-1 px-2" id="badge-modal-add-last"
                style="font-size: 0.7rem; font-weight: 800;">Puesto #N</span>
            </button>
          </div>
        </div>

        <!-- O en una posición directa -->
        <div class="p-3" style="background: #0d1217; border: 1px solid rgba(255, 255, 255, 0.08);">
          <label for="modal-add-pos-input" class="form-label text-uppercase fw-bold text-muted mb-2"
            style="font-size: 0.72rem; letter-spacing: 0.05em;">
            <i class="fa-solid fa-arrows-up-down text-info me-1"></i><span data-i18n="edit_top.add_modal_custom_label">O
              elige una posición directa:</span>
          </label>
          <div class="input-group">
            <span class="input-group-text bg-dark text-muted border-secondary fw-bold rounded-0">#</span>
            <input type="number" id="modal-add-pos-input" min="1"
              class="form-control bg-dark text-white border-secondary text-center fw-bold rounded-0"
              style="font-size: 1.1rem;">
            <button type="button" class="btn btn-success fw-bold px-4 rounded-0" id="btn-modal-add-custom"
              data-i18n="edit_top.btn_insert">
              Insertar
            </button>
          </div>
          <small class="text-muted d-block mt-2" id="modal-add-pos-helper" style="font-size: 0.75rem;">Introduce un
            puesto entre 1 y N</small>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-sm btn-dark rounded-0 px-4 fw-bold" data-bs-dismiss="modal"
          data-i18n="edit_top.btn_cancel">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<!-- SortableJS para Drag & Drop nativo y fluido -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="<?= Router::asset('web/js/users/edit_top.js') ?>?v=<?= time() ?>"></script>