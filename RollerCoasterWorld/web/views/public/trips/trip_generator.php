<?php
$page_css = ['web/css/recommendations.css', 'web/css/trips.css', 'web/css/trip_generator.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    Router::redirect('login');
}
?>

<main class="tg-main">

    <!-- ══ HERO HEADER ════════════════════════════════════════════════════ -->
    <div class="tg-hero">
        <div class="tg-hero-glow"></div>
        <div class="tg-hero-inner">

            <div class="tg-hero-badge">
                <span class="ai-dot"></span>
                Motor IA Activo
            </div>

            <h1 class="tg-hero-title">
                Generador de Viajes
                <span class="tg-hero-title-accent">Inteligente</span>
            </h1>

            <p class="tg-hero-sub">
                La IA analiza tu historial, tus coasters favoritas y tu presupuesto
                para sugerirte los mejores destinos <strong>sin que tengas que rellenar nada</strong>.
            </p>

            <!-- Datos del perfil en pill chips -->
            <div class="tg-profile-chips" id="tg-profile-chips">
                <div class="tg-chip skeleton-chip"></div>
                <div class="tg-chip skeleton-chip"></div>
                <div class="tg-chip skeleton-chip"></div>
            </div>

        </div>
    </div>

    <div class="tg-wrapper">

        <!-- ══ CONTROLES ══════════════════════════════════════════════════ -->
        <div class="tg-controls-bar">
            <div class="tg-controls-left">
                <span class="tg-section-label">
                    <i class="fa-solid fa-wand-magic-sparkles me-2" style="color:var(--rcw-green-neon);"></i>
                    Especialmente para ti
                </span>
                <span class="rcw-recs-ai-badge">
                    <span class="ai-dot"></span> IA
                </span>
            </div>
            <div class="tg-controls-right">
                <span class="text-muted small d-none d-md-inline">
                    Basado en tu perfil · Caché 24h
                </span>
                <button class="rcw-recs-refresh-btn" id="recs-refresh-btn"
                        title="Regenerar recomendaciones">
                    <i class="fa-solid fa-rotate"></i>
                    <span class="d-none d-sm-inline">Regenerar</span>
                </button>
            </div>
        </div>

        <!-- ══ GRID RECOMENDACIONES (poblado por recommendations.js) ══════ -->
        <div class="rcw-recs-grid tg-recs-grid" id="recs-grid"></div>

        <!-- ══ SEPARADOR ══════════════════════════════════════════════════ -->
        <div class="tg-divider">
            <span class="tg-divider-label">
                <i class="fa-solid fa-circle-info me-2"></i>
                ¿Cómo funciona la IA?
            </span>
        </div>

        <!-- ══ CÓMO FUNCIONA ══════════════════════════════════════════════ -->
        <div class="tg-how-grid">

            <div class="tg-how-card">
                <div class="tg-how-icon" style="background:rgba(16,185,129,0.12);color:#10b981;">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h3 class="tg-how-title">1. Analiza tu perfil</h3>
                <p class="tg-how-desc">
                    Lee tu historial de coasters, parques visitados, fabricante favorito
                    y el gasto medio en tus reservas anteriores.
                </p>
            </div>

            <div class="tg-how-card">
                <div class="tg-how-icon" style="background:rgba(99,102,241,0.12);color:#818cf8;">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <h3 class="tg-how-title">2. Puntúa destinos</h3>
                <p class="tg-how-desc">
                    Compara tu perfil con todos los parques de la base de datos
                    usando un algoritmo de afinidad multicriteria.
                </p>
            </div>

            <div class="tg-how-card">
                <div class="tg-how-icon" style="background:rgba(251,146,60,0.12);color:#fb923c;">
                    <i class="fa-solid fa-compass"></i>
                </div>
                <h3 class="tg-how-title">3. Añade un comodín</h3>
                <p class="tg-how-desc">
                    Incluye un destino "Descubrimiento" fuera de tu zona de confort
                    para que explores parques que nunca habrías considerado.
                </p>
            </div>

            <div class="tg-how-card">
                <div class="tg-how-icon" style="background:rgba(34,211,238,0.12);color:#22d3ee;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h3 class="tg-how-title">4. Reserva y agenda</h3>
                <p class="tg-how-desc">
                    Con un clic, el carrito se pre-configura con hotel y entradas.
                    Al confirmar, el viaje se añade automáticamente a tu Agenda.
                </p>
            </div>

        </div>

        <!-- ══ ENLACE A MIS VIAJES ════════════════════════════════════════ -->
        <div class="tg-trips-link-banner">
            <div class="tg-trips-link-inner">
                <div>
                    <div class="tg-trips-link-title">
                        <i class="fa-solid fa-calendar-days me-2 text-success"></i>
                        Consulta tus viajes planificados
                    </div>
                    <p class="tg-trips-link-sub">
                        Todos los viajes que hayas reservado desde este generador aparecen en tu Agenda.
                    </p>
                </div>
                <a href="<?= Router::url('trips') ?>" class="tg-trips-link-btn">
                    <i class="fa-solid fa-arrow-right me-2"></i>
                    Ver mi Agenda
                </a>
            </div>
        </div>

    </div><!-- /tg-wrapper -->

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
                stats.credits   ? `<span class="tg-chip"><i class="fa-solid fa-train-tram me-1"></i>${stats.credits} coasters</span>` : '',
                stats.parks_visited ? `<span class="tg-chip"><i class="fa-solid fa-tree-city me-1"></i>${stats.parks_visited} parques visitados</span>` : '',
                user.location && user.location !== '—' ? `<span class="tg-chip"><i class="fa-solid fa-location-dot me-1"></i>${user.location}</span>` : '',
                stats.trips  ? `<span class="tg-chip"><i class="fa-solid fa-route me-1"></i>${stats.trips} viajes</span>` : '',
            ].filter(Boolean).join('') || '<span class="tg-chip text-muted">Completa tu perfil para mejores recomendaciones</span>';
        } catch (e) { /* silencioso */ }
    });
})();
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
