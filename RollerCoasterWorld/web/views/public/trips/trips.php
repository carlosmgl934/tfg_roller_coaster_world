<?php
$page_css = ['web/css/trips.css'];
require_once __DIR__ . '/../../partials/header.php';
if (!isset($_SESSION['firebase_uid'])) {
    Router::redirect('login');
}
?>

<main class="container my-5">

    <!-- ══ CABECERA ═════════════════════════════════════════════ -->
    <div
        class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-3 border-bottom border-success">
        <div>
            <h1 class="fw-bold mb-1" style="color:var(--rcw-text-primary);font-family:var(--rcw-font-title)">
                <i class="fa-solid fa-calendar-days text-success me-2"></i>Mi Agenda
            </h1>
            <p class="text-muted small mb-0">Tu diario personal de parques, viajes y montañas rusas</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success btn-sm rounded-0 fw-bold" onclick="openCreateTripModal()">
                <i class="fa-solid fa-plus me-1"></i>Nuevo Viaje
            </button>
            <a href="<?= Router::url('trip_generator') ?>" class="btn btn-outline-success btn-sm rounded-0 fw-bold">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generador IA
            </a>
        </div>
    </div>

    <!-- ══ INVITACIONES PENDIENTES ══════════════════════════════ -->
    <div id="invites-container"></div>

    <!-- ══ PRÓXIMOS VIAJES ═══════════════════════════════════════ -->
    <div id="upcoming-trips-section" class="mb-5 d-none">
        <h4 class="fw-bold mb-3" style="color:var(--rcw-text-primary); font-family:var(--rcw-font-title)">
            <i class="fa-solid fa-clock-rotate-left text-success me-2"></i>Próximos viajes
        </h4>
        <div id="upcoming-trips-grid" class="row g-3 flex-nowrap overflow-x-auto pb-3" style="scrollbar-width: thin;">
        </div>
    </div>

    <!-- ══ WIDGET HOY ═══════════════════════════════════════════ -->
    <div id="today-widget-container" class="mb-4"></div>

    <!-- ══ CALENDARIO ═══════════════════════════════════════════ -->
    <div class="card shadow-sm rounded-0 border-top-only mb-5">
        <div class="card-header bg-success text-white rounded-0 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-1 gap-md-2">
            <h5 class="mb-0"><i class="fa-solid fa-calendar-days me-2"></i>Calendario</h5>
            <small class="opacity-75">Haz clic en un día para ver detalles o registrar actividad</small>
        </div>
        <div class="card-body p-3">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- ══ MIS VIAJES ═══════════════════════════════════════════ -->
    <div class="card shadow-sm rounded-0 border-top-only mb-5">
        <div class="card-header bg-success text-white rounded-0 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-1 gap-md-2">
            <h5 class="mb-0"><i class="fa-solid fa-suitcase-rolling me-2"></i>Mis Viajes</h5>
            <small class="opacity-75">Tus viajes planificados y pasados</small>
        </div>
        <div class="card-body p-4">
            <div id="trips-grid" class="trips-grid">
                <div class="text-center py-4 text-muted small">Cargando viajes...</div>
            </div>
        </div>
    </div>

    <!-- ══ ESTADÍSTICAS Y RANKING ══════════════════════════════ -->
    <div class="card shadow-sm rounded-0 border-top-only mb-5">
        <div class="card-header bg-success text-white rounded-0 d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-chart-line me-2"></i>Estadísticas de
                    <select id="rank-type-select"
                        class="form-select form-select-sm ms-1 d-inline-block w-auto shadow-none fw-bold border-0 bg-transparent text-white"
                        style="cursor: pointer;">
                        <option value="coasters" class="text-dark">Coasters</option>
                        <option value="parks" class="text-dark">Parques</option>
                    </select>
                </h5>
                <span class="badge bg-dark px-3 py-2 border border-secondary border-opacity-50">
                    <i class="fa-solid fa-suitcase me-1 text-success"></i> <span id="rank-trip-count">0 viajes</span>
                </span>
            </div>
            <div class="d-flex overflow-x-auto gap-1 pb-1" id="rank-filter-btns" style="scrollbar-width: none;">
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="week">Semana</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="month">Mes</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-light text-success active flex-shrink-0"
                    data-period="year">Año</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="custom">Personalizado</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="all">Siempre</button>
            </div>
            <div
                class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mt-1 pt-2 border-top border-light border-opacity-25">
                <div class="d-flex align-items-center gap-2" id="rank-nav-container">
                    <button class="btn btn-sm btn-outline-light border-opacity-50 rounded-0" id="rank-prev-btn"
                        title="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                    <span id="rank-nav-label" class="fw-bold text-center" style="min-width: 80px;">2026</span>
                    <button class="btn btn-sm btn-outline-light border-opacity-50 rounded-0" id="rank-next-btn"
                        title="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                    <div class="d-flex align-items-center gap-1">
                        <small class="text-light opacity-75">Desde:</small>
                        <input type="date"
                            class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white p-1"
                            id="rank-start-date" style="max-width: 115px; font-size: 0.75rem;" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="col-6 col-md-auto d-flex align-items-center gap-1">
                        <small class="text-light opacity-75">Hasta:</small>
                        <input type="date"
                            class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white p-1"
                            id="rank-end-date" style="max-width: 115px; font-size: 0.75rem;" placeholder="dd/mm/aaaa">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0" id="ranking-container">
            <div class="text-center py-4 text-muted small">Cargando estadísticas...</div>
        </div>
    </div>

</main>





<!-- ══ TOAST NOTIFICATION ════════════════════════════════════════ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="rcw-toast" class="toast rounded-0 border-0 shadow-lg" role="alert" aria-live="assertive"
        data-bs-autohide="true" data-bs-delay="3000">
        <div class="toast-header rounded-0 border-0" id="toast-header" style="background:var(--rcw-bg-card)">
            <span id="toast-icon" class="me-2"></span>
            <strong class="me-auto" id="toast-title">Aviso</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toast-body" style="background:var(--rcw-bg-card-alt);color:var(--rcw-text-primary)">
        </div>
    </div>
</div>

<!-- ══ FLATPIKCR (Estilo para calendarios) ════════════════════════ -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/trips/trips.js') ?>?v=<?= time() ?>"></script>