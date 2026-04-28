<?php
$page_css = ['web/css/trips.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    Router::redirect('login');
}
?>

<main class="trips-main">

    <div class="trips-header-bar">
        <div class="trips-header-inner">
            <div class="trips-header-title-group">
                <i class="fa-solid fa-calendar-days trips-header-icon"></i>
                <div>
                    <h1 class="trips-main-title">Mi Agenda de Parques</h1>
                    <p class="trips-main-sub">Tus viajes planificados y generados por la IA</p>
                </div>
            </div>
            <a href="<?= Router::url('home') ?>" class="trips-back-btn">
                <i class="fa-solid fa-arrow-left me-1"></i>
                <span class="d-none d-sm-inline">Volver al inicio</span>
            </a>
        </div>
    </div>

    <div class="trips-wrapper">

        <!-- MENSAJE DE ESTADO VACÍO (visible cuando no hay viajes, pero el calendario sigue) -->
        <div id="trips-empty-banner" class="alert alert-info d-none mb-4"
            style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);color:#fff;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <i class="fa-solid fa-map-location-dot me-2 text-success fs-5"></i>
                    <strong>No tienes ningún viaje reservado todavía.</strong>
                    <span class="text-muted ms-1">Deja que la IA te recomiende tu próxima aventura.</span>
                </div>
                <a href="<?= Router::url('trip_generator') ?>" class="btn btn-success btn-sm rounded-0 fw-bold">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>
                    Generador de Viajes
                </a>
            </div>
        </div>

        <!-- LOADER -->
        <div id="trips-loader" class="trips-loader-wrap text-center py-5">
            <div class="spinner-border text-success" style="width:2.5rem;height:2.5rem;" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3 small">Cargando tu agenda...</p>
        </div>

        <!-- CALENDARIO -->
        <div id="calendar-container" class="d-none">
            <div id="calendar"></div>
        </div>

    </div>

</main>

<!-- MODAL: Detalle de un viaje -->
<div class="modal fade" id="trip-detail-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0" style="background:#0d1117;">
            <div class="modal-header border-0 px-4 py-3"
                style="background:#161b22;border-bottom:1px solid rgba(16,185,129,0.2)!important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-success fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0 text-white" id="trip-modal-title">Detalle del viaje</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3" id="trip-modal-body">
                <!-- poblado por JS -->
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger rounded-0 px-4" id="trip-delete-btn">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar viaje
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Confirmar Eliminación -->
<div class="modal fade" id="delete-confirm-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0" style="background:#0d1117;">
            <div class="modal-header border-0 px-4 py-3"
                style="background:#161b22;border-bottom:1px solid rgba(239,68,68,0.2)!important;">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation text-danger fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0 text-white">Eliminar viaje</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <p class="text-white mb-0">¿Estás seguro de que deseas eliminar este viaje de tu agenda? Esta acción no
                    se puede deshacer.</p>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                    data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 px-4" id="confirm-delete-btn">
                    <i class="fa-solid fa-trash me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script>
    (function () {
        const BASE = window.BASE_URL;
        const API = BASE + '/api/php/trips.php';

        let allTrips = [];
        let activeModal = null;
        let calendar = null;

        async function loadTrips() {
            try {
                const resp = await fetch(API + '?action=list', { credentials: 'same-origin' });
                const json = await resp.json();

                document.getElementById('trips-loader').classList.add('d-none');
                document.getElementById('calendar-container').classList.remove('d-none');

                allTrips = json.data || [];

                if (allTrips.length === 0) {
                    document.getElementById('trips-empty-banner').classList.remove('d-none');
                } else {
                    document.getElementById('trips-empty-banner').classList.add('d-none');
                }

                initCalendar();

            } catch (e) {
                console.error('[Trips]', e);
                document.getElementById('trips-loader').classList.add('d-none');
                document.getElementById('trips-empty-banner').classList.remove('d-none');
                document.getElementById('calendar-container').classList.remove('d-none');
                initCalendar();
            }
        }

        function initCalendar() {
            const calendarEl = document.getElementById('calendar');

            // Mapear los viajes al formato de eventos de FullCalendar
            const events = allTrips.map(trip => {
                // FullCalendar end date is exclusive, so we add 1 day to end_date
                const endDate = new Date(trip.end_date);
                endDate.setDate(endDate.getDate() + 1);

                return {
                    id: trip.id,
                    title: trip.title,
                    start: trip.start_date,
                    end: endDate.toISOString().split('T')[0],
                    extendedProps: trip,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: '#10b981',
                    textColor: '#fff'
                };
            });

            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                themeSystem: 'standard',
                events: events,
                eventClick: function (info) {
                    openTripModal(info.event.extendedProps);
                },
                height: 'auto',
                firstDay: 1 // Lunes
            });

            calendar.render();
        }

        function openTripModal(trip) {
            const title = document.getElementById('trip-modal-title');
            const body = document.getElementById('trip-modal-body');
            const delBtn = document.getElementById('trip-delete-btn');
            if (!title || !body) return;

            title.textContent = trip.title;

            const start = formatDate(trip.start_date);
            const end = formatDate(trip.end_date);
            const days = daysBetween(trip.start_date, trip.end_date);

            body.innerHTML = `
        <div class="d-flex flex-wrap gap-3 mb-3">
            <div class="trip-modal-stat">
                <i class="fa-solid fa-calendar-days text-success me-2"></i>
                <strong>${start}</strong> → <strong>${end}</strong>
            </div>
            <div class="trip-modal-stat">
                <i class="fa-solid fa-sun text-warning me-2"></i>
                ${days} día${days !== 1 ? 's' : ''}
            </div>
            ${trip.parks_visited ? `
            <div class="trip-modal-stat">
                <i class="fa-solid fa-location-dot text-danger me-2"></i>
                ${escHtml(trip.parks_visited)}
            </div>` : ''}
        </div>

        <div class="alert" style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:8px;" role="alert">
            <i class="fa-solid fa-wand-magic-sparkles text-success me-2"></i>
            <span class="text-white">Este viaje fue generado automáticamente por la IA basándose en tu perfil.</span>
        </div>

        <p class="text-muted small mt-2">
            Puedes eliminar este viaje si ya no es relevante. Una vez eliminado no se puede recuperar.
        </p>`;

            delBtn.onclick = () => deleteTrip(trip.id);

            if (!activeModal) {
                activeModal = new bootstrap.Modal(document.getElementById('trip-detail-modal'));
            }
            activeModal.show();
        }

        let deleteModal = null;
        let tripToDelete = null;

        function deleteTrip(tripId) {
            tripToDelete = tripId;

            if (!deleteModal) {
                deleteModal = new bootstrap.Modal(document.getElementById('delete-confirm-modal'));

                document.getElementById('confirm-delete-btn').addEventListener('click', async () => {
                    if (!tripToDelete) return;

                    try {
                        const resp = await fetch(API + '?action=delete', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ trip_id: tripToDelete }),
                        });
                        const json = await resp.json();

                        if (json.success) {
                            deleteModal.hide();
                            activeModal && activeModal.hide();

                            allTrips = allTrips.filter(t => t.id != tripToDelete);

                            // Actualizar calendario
                            const event = calendar.getEventById(tripToDelete);
                            if (event) {
                                event.remove();
                            }

                            if (allTrips.length === 0) {
                                document.getElementById('trips-empty-banner').classList.remove('d-none');
                            }
                        }
                    } catch (e) {
                        console.error('[DeleteTrip]', e);
                    } finally {
                        tripToDelete = null;
                    }
                });
            }

            deleteModal.show();
        }

        // ── Helpers ────────────────────────────────────────────────────────
        function formatDate(str) {
            if (!str) return '—';
            const d = new Date(str);
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function daysBetween(a, b) {
            const diff = new Date(b) - new Date(a);
            return Math.max(1, Math.round(diff / 86400000)) + 1; // +1 para contar el día de inicio y fin inclusive
        }

        function escHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        document.addEventListener('DOMContentLoaded', loadTrips);
    })();
</script>