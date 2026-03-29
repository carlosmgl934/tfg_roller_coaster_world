<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/admin_dashboard.css">
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<main class="dash-container">
    <header class="dash-header">
        <div>
            <h1 class="dash-title">Panel de Control</h1>
            <p style="color:rgba(255,255,255,0.5);">Estadísticas generales y crecimiento de la plataforma</p>
        </div>
        <div class="dash-controls">
            <span class="me-2" style="font-size:0.8rem; opacity:0.6;">Periodo global de crecimiento:</span>
            <button class="dash-btn-toggle" data-period="day">Día</button>
            <button class="dash-btn-toggle" data-period="week">Semana</button>
            <button class="dash-btn-toggle active" data-period="month">Mes</button>
        </div>
    </header>

    <!-- KPI CARDS -->
    <section class="dash-kpi-grid">
        <div class="dash-kpi-card">
            <div class="dash-kpi-label">Usuarios Totales</div>
            <div class="dash-kpi-val" id="kpi-users">--</div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-label">Montañas Rusas</div>
            <div class="dash-kpi-val" id="kpi-coasters">--</div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-label">Parques</div>
            <div class="dash-kpi-val" id="kpi-parks">--</div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-label">Reseñas</div>
            <div class="dash-kpi-val" id="kpi-reviews">--</div>
        </div>
        <div class="dash-kpi-card">
            <div class="dash-kpi-label">Fotos</div>
            <div class="dash-kpi-val" id="kpi-photos">--</div>
        </div>
    </section>

    <!-- CHARTS GRID -->
    <section class="dash-charts-grid">
        <!-- Growth Chart (Line) -->
        <div class="dash-chart-card">
            <header class="dash-chart-header">
                <span class="dash-chart-title">Crecimiento de Usuarios</span>
                <i class="fa-solid fa-chart-line text-success"></i>
            </header>
            <div class="chart-wrapper">
                <canvas id="chart-growth-users"></canvas>
            </div>
        </div>

        <!-- Reviews Growth (Bar) -->
        <div class="dash-chart-card">
            <header class="dash-chart-header">
                <span class="dash-chart-title">Nuevas Reseñas</span>
                <i class="fa-solid fa-comment-dots text-info"></i>
            </header>
            <div class="chart-wrapper">
                <canvas id="chart-growth-reviews"></canvas>
            </div>
        </div>

        <!-- Coaster Status (Doughnut) -->
        <div class="dash-chart-card">
            <header class="dash-chart-header">
                <span class="dash-chart-title">Estado de Coasters</span>
                <i class="fa-solid fa-circle-info text-warning"></i>
            </header>
            <div class="chart-wrapper">
                <canvas id="chart-dist-status"></canvas>
            </div>
        </div>

        <!-- Countries (Bar) -->
        <div class="dash-chart-card">
            <header class="dash-chart-header">
                <span class="dash-chart-title">Usuarios por País</span>
                <i class="fa-solid fa-globe text-primary"></i>
            </header>
            <div class="chart-wrapper">
                <canvas id="chart-dist-country"></canvas>
            </div>
        </div>
    </section>
</main>

<!-- Configuración del API pasada al JS externo -->
<script>
    window.DASHBOARD_API = '<?= $base_url ?>/api/php/admin/get_stats.php';
</script>
<script src="<?= $base_url ?>/web/js/admin/dashboard.js"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
