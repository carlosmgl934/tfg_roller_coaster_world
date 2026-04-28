<?php
$page_css = ['web/css/recommendations.css', 'web/css/trip_generator.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    Router::redirect('login');
}
?>

<main class="container-fluid px-lg-5 my-5">

    <!-- ══ CABECERA DE PÁGINA ═══════════════════════════════════════ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-start align-items-sm-center justify-content-between flex-wrap gap-2 border-bottom pb-3">
                <div>
                    <h1 class="display-6 fw-bold text-success mb-1">
                        <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Generador de Viajes
                        <span class="tg-hero-badge ms-2">
                            <span class="ai-dot"></span> Motor IA
                        </span>
                    </h1>
                    <p class="text-muted small mb-0">
                        La IA analiza tu perfil para sugerirte los mejores destinos <strong class="text-secondary">sin que tengas que rellenar nada</strong>.
                    </p>
                </div>
                <!-- Chips de perfil -->
                <div class="d-flex flex-wrap gap-2 align-items-center" id="tg-profile-chips">
                    <span class="tg-chip skeleton-chip"></span>
                    <span class="tg-chip skeleton-chip"></span>
                    <span class="tg-chip skeleton-chip"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ CONTROLES + GRID DE RECOMENDACIONES ═══════════════════════ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="h5 fw-bold mb-0">
                    <i class="fa-solid fa-wand-magic-sparkles me-2 text-success"></i>Especialmente para ti
                    <span class="rcw-recs-ai-badge ms-2">
                        <span class="ai-dot"></span> IA
                    </span>
                </h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small d-none d-md-inline">Basado en tu perfil · Caché 24h</span>
                    <button class="rcw-recs-refresh-btn" id="recs-refresh-btn" title="Regenerar recomendaciones">
                        <i class="fa-solid fa-rotate"></i>
                        <span class="d-none d-sm-inline">Regenerar</span>
                    </button>
                </div>
            </div>
            <!-- Grid de recomendaciones (poblado por recommendations.js) -->
            <div class="rcw-recs-grid tg-recs-grid" id="recs-grid"></div>
        </div>
    </div>

    <!-- ══ CÓMO FUNCIONA LA IA ════════════════════════════════════════ -->
    <div class="row mb-4">
        <div class="col-12">

            <div class="card rounded-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0">
                        <i class="fa-solid fa-circle-info me-2"></i>¿Cómo funciona la IA?
                    </h5>
                </div>
                <div class="card-body p-3">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">

                        <div class="col">
                            <div class="card h-100 rounded-0 border-start border-success border-3">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="tg-how-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                                        <i class="fa-solid fa-database"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">1. Analiza tu perfil</h6>
                                    <p class="small text-muted mb-0">
                                        Lee tu historial de coasters, parques visitados, fabricante favorito
                                        y el gasto medio en tus reservas anteriores.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card h-100 rounded-0 border-start border-primary border-3">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="tg-how-icon" style="background:rgba(99,102,241,0.12);color:#818cf8;">
                                        <i class="fa-solid fa-brain"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">2. Puntúa destinos</h6>
                                    <p class="small text-muted mb-0">
                                        Compara tu perfil con todos los parques de la base de datos
                                        usando un algoritmo de afinidad multicriteria.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card h-100 rounded-0 border-start border-warning border-3">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="tg-how-icon" style="background:rgba(251,146,60,0.12);color:#fb923c;">
                                        <i class="fa-solid fa-compass"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">3. Añade un comodín</h6>
                                    <p class="small text-muted mb-0">
                                        Incluye un destino "Descubrimiento" fuera de tu zona de confort
                                        para que explores parques que nunca habrías considerado.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card h-100 rounded-0 border-start border-info border-3">
                                <div class="card-body d-flex flex-column gap-2">
                                    <div class="tg-how-icon" style="background:rgba(34,211,238,0.12);color:#22d3ee;">
                                        <i class="fa-solid fa-calendar-check"></i>
                                    </div>
                                    <h6 class="fw-bold mb-0">4. Reserva y agenda</h6>
                                    <p class="small text-muted mb-0">
                                        Con un clic, el carrito se pre-configura con hotel y entradas.
                                        Al confirmar, el viaje se añade automáticamente a tu Agenda.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ══ BANNER AGENDA ══════════════════════════════════════════════ -->
    <div class="row">
        <div class="col-12">
            <div class="card rounded-0 shadow-sm border-success">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h5 class="fw-bold mb-1">
                                <i class="fa-solid fa-calendar-days me-2 text-success"></i>Consulta tus viajes planificados
                            </h5>
                            <p class="small text-muted mb-0">
                                Todos los viajes que hayas reservado desde este generador aparecen en tu Agenda.
                            </p>
                        </div>
                        <a href="<?= Router::url('trips') ?>" class="btn btn-success rounded-0 fw-bold px-4">
                            <i class="fa-solid fa-arrow-right me-2"></i>Ver mi Agenda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="<?= Router::asset('web/js/shared/recommendations.js') ?>" defer></script>
<script>
/* Carga el perfil resumido para mostrar los chips en el hero */
(function () {
    document.addEventListener('DOMContentLoaded', async function () {
        try {
            const resp = await fetch(window.BASE_URL + '/api/php/index.php?action=dashboard',
                { credentials: 'same-origin' });
            const json = await resp.json();
            if (!json.success) return;

            const chips = document.getElementById('tg-profile-chips');
            if (!chips) return;

            const stats = json.data?.stats || {};
            const user  = json.data?.user  || {};
            chips.innerHTML = [
                stats.credits       ? `<span class="tg-chip"><i class="fa-solid fa-train-tram me-1"></i>${stats.credits} coasters</span>` : '',
                stats.parks_visited ? `<span class="tg-chip"><i class="fa-solid fa-tree-city me-1"></i>${stats.parks_visited} parques visitados</span>` : '',
                user.location && user.location !== '—' ? `<span class="tg-chip"><i class="fa-solid fa-location-dot me-1"></i>${user.location}</span>` : '',
                stats.trips         ? `<span class="tg-chip"><i class="fa-solid fa-route me-1"></i>${stats.trips} viajes</span>` : '',
            ].filter(Boolean).join('') || '<span class="tg-chip text-muted">Completa tu perfil para mejores recomendaciones</span>';
        } catch (e) { /* silencioso */ }
    });
})();
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
