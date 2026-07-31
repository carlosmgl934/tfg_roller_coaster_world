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
                <i class="fa-solid fa-calendar-days text-success me-2"></i><span data-i18n="trips.title">Mi
                    Agenda</span>
            </h1>
            <p class="text-muted small mb-0" data-i18n="trips.subtitle">Tu diario personal de parques, viajes y montañas
                rusas</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success btn-sm rounded-0 fw-bold" onclick="openCreateTripModal()">
                <i class="fa-solid fa-plus me-1"></i><span data-i18n="trips.new_trip">Nuevo Viaje</span>
            </button>
        </div>
    </div>

    <!-- ══ INVITACIONES PENDIENTES ══════════════════════════════ -->
    <div id="invites-container"></div>

    <!-- ══ PRÓXIMOS VIAJES ═══════════════════════════════════════ -->
    <div id="upcoming-trips-section" class="mb-5 d-none">
        <h4 class="fw-bold mb-3" style="color:var(--rcw-text-primary); font-family:var(--rcw-font-title)">
            <i class="fa-solid fa-clock-rotate-left text-success me-2"></i><span data-i18n="trips.upcoming">Próximos
                viajes</span>
        </h4>
        <div id="upcoming-trips-grid" class="row g-3 flex-nowrap overflow-x-auto pb-3" style="scrollbar-width: thin;">
        </div>
    </div>

    <!-- ══ WIDGET HOY ═══════════════════════════════════════════ -->
    <div id="today-widget-container" class="mb-4"></div>

    <!-- ══ CALENDARIO ═══════════════════════════════════════════ -->
    <div class="card shadow-sm rounded-0 border-top-only mb-5">
        <div
            class="card-header bg-success text-white rounded-0 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-1 gap-md-2">
            <h5 class="mb-0"><i class="fa-solid fa-calendar-days me-2"></i><span
                    data-i18n="trips.calendar">Calendario</span></h5>
            <small class="opacity-75" data-i18n="trips.calendar_hint">Haz clic en un día para ver detalles o registrar
                actividad</small>
        </div>
        <div class="card-body p-3">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- ══ MIS VIAJES ═══════════════════════════════════════════ -->
    <div class="card shadow-sm rounded-0 border-top-only mb-5">
        <div
            class="card-header bg-success text-white rounded-0 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-1 gap-md-2">
            <h5 class="mb-0"><i class="fa-solid fa-suitcase-rolling me-2"></i><span data-i18n="trips.my_trips">Mis
                    Viajes</span></h5>
            <small class="opacity-75" data-i18n="trips.my_trips_hint">Tus viajes planificados y pasados</small>
        </div>
        <div class="card-body p-4">
            <div id="trips-grid" class="trips-grid">
                <div class="text-center py-4 text-muted small" data-i18n="common.loading">Cargando viajes...</div>
            </div>
        </div>
    </div>

    <!-- ══ ESTADÍSTICAS Y RANKING ══════════════════════════════ -->
    <div class="card shadow-sm rounded-0 border-top-only mb-5">
        <div class="card-header bg-success text-white rounded-0 d-flex flex-column gap-3">
            <div class="d-flex align-items-center gap-2 flex-wrap gap-3">
                <h5 class="mb-0 d-flex align-items-center gap-2" style="flex-wrap: nowrap; white-space: nowrap;">
                    <i class="fa-solid fa-chart-line me-2"></i><span data-i18n="trips.stats_of">Estadísticas de</span>
                    <select id="rank-type-select"
                        class="form-select form-select-sm ms-1 d-inline-block w-auto shadow-none fw-bold border-0 bg-transparent text-white rcw-stats-inline"
                        style="cursor: pointer;">
                        <option value="coasters" class="text-dark" data-i18n="common.coasters">Coasters</option>
                        <option value="parks" class="text-dark" data-i18n="common.parks">Parques</option>
                    </select>
                </h5>
            </div>
            <div class="mt-1">
                <span class="badge bg-dark px-3 py-2 border border-secondary border-opacity-50">
                    <i class="fa-solid fa-suitcase me-1 text-success"></i> <span id="rank-trip-count">0 viajes</span>
                </span>
            </div>
            <div class="d-flex overflow-x-auto gap-1 pb-1" id="rank-filter-btns" style="scrollbar-width: none;">
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="week" data-i18n="trips.period_week">Semana</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="month" data-i18n="trips.period_month">Mes</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-light text-success active flex-shrink-0"
                    data-period="year" data-i18n="trips.period_year">Año</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="custom" data-i18n="trips.period_custom">Personalizado</button>
                <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-light border-opacity-50 flex-shrink-0"
                    data-period="all" data-i18n="trips.period_all">Siempre</button>
            </div>
            <div
                class="d-flex align-items-center flex-wrap gap-2 gap-3 mt-1 pt-2 border-top border-light border-opacity-25">
                <div class="d-flex align-items-center gap-2 flex-shrink-0" id="rank-nav-container">
                    <button class="btn btn-sm btn-outline-light border-opacity-50 rounded-0" id="rank-prev-btn"
                        title="Anterior" data-i18n="trips.prev" data-i18n-attr="title"><i
                            class="fa-solid fa-chevron-left"></i></button>
                    <span id="rank-nav-label" class="fw-bold text-center" style="min-width: 80px;">2026</span>
                    <button class="btn btn-sm btn-outline-light border-opacity-50 rounded-0" id="rank-next-btn"
                        title="Siguiente" data-i18n="trips.next" data-i18n-attr="title"><i
                            class="fa-solid fa-chevron-right"></i></button>
                </div>
                <div class="d-flex flex-column gap-1" id="rank-dates-container">
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-light opacity-75" style="width: 40px; white-space: nowrap;"
                            data-i18n="trips.from">Desde:</small>
                        <input type="date"
                            class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white p-1"
                            id="rank-start-date" style="flex: 1; font-size: 0.75rem;" placeholder="dd/mm/aaaa">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-light opacity-75" style="width: 40px; white-space: nowrap;"
                            data-i18n="trips.to">Hasta:</small>
                        <input type="date"
                            class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white p-1"
                            id="rank-end-date" style="flex: 1; font-size: 0.75rem;" placeholder="dd/mm/aaaa">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0" id="ranking-container">
            <div class="text-center py-4 text-muted small" data-i18n="trips.loading_stats">Cargando estadísticas...
            </div>
        </div>
    </div>

</main>





<!-- ══ TOAST NOTIFICATION ════════════════════════════════════════ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="rcw-toast" class="toast rounded-0 border-0 shadow-lg" role="alert" aria-live="assertive"
        data-bs-autohide="true" data-bs-delay="3000">
        <div class="toast-header rounded-0 border-0" id="toast-header" style="background:var(--rcw-bg-card)">
            <span id="toast-icon" class="me-2"></span>
            <strong class="me-auto" id="toast-title" data-i18n="common.warning">Aviso</strong>
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