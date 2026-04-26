<?php
$page_css = ['web/css/admin.css'];
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<main class="container-fluid px-lg-5 pt-0 pb-5 mb-5">

    <!-- Cabecera -->
    <div class="row pt-4 mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-0">
                <i class="fa-solid fa-gauge-high me-3"></i>Panel de Control
            </h1>
            <!-- Selector de periodo -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="text-muted small">Periodo:</span>
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-success btn-sm rounded-0 dash-btn-toggle" data-period="day">Día</button>
                    <button class="btn btn-outline-success btn-sm rounded-0 dash-btn-toggle" data-period="week">Semana</button>
                    <button class="btn btn-success         btn-sm rounded-0 dash-btn-toggle" data-period="month">Mes</button>
                    <button class="btn btn-outline-success btn-sm rounded-0 dash-btn-toggle" data-period="custom">
                        <i class="fa-solid fa-calendar-days me-1"></i>Rango
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Fila de rango personalizado (oculta por defecto) -->
    <div class="row mb-4 d-none" id="custom-range-row">
        <div class="col-12">
            <div class="card border-0 rounded-0 px-4 py-3 d-flex flex-row align-items-center flex-wrap gap-3" style="background:#161b22;">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.04em;">
                    <i class="fa-solid fa-calendar-days me-1 text-success"></i>Desde
                </span>
                <input type="date" id="range-from" class="form-control form-control-sm rounded-0 border-success" style="max-width:160px; background:#0d1117; color:#e6edf3; border-width:2px; color-scheme:dark;">
                <span class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.04em;">Hasta</span>
                <input type="date" id="range-to"   class="form-control form-control-sm rounded-0 border-success" style="max-width:160px; background:#0d1117; color:#e6edf3; border-width:2px; color-scheme:dark;">
                <button class="btn btn-success btn-sm rounded-0 fw-bold" id="btn-apply-range">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Aplicar
                </button>
            </div>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-users fa-xl text-success mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Usuarios</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-users">--</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-bolt fa-xl text-warning mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Coasters</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-coasters">--</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-map-location-dot fa-xl text-info mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Parques</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-parks">--</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-star fa-xl text-primary mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Reseñas</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-reviews">--</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-images fa-xl text-danger mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Fotos</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-photos">--</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-comments fa-xl text-warning mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Posts Foro</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-forum-posts">--</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg flex-lg-fill">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-body text-center py-4">
                    <i class="fa-solid fa-suitcase-rolling fa-xl text-success mb-2"></i>
                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.04em;">Viajes Reserv.</div>
                    <div class="fw-bold fs-3 text-white" id="kpi-trips">--</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS GRID -->
    <div class="row g-4">

        <!-- Crecimiento de Usuarios (Line) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;">
                    <span class="fw-bold text-white">Crecimiento de Usuarios</span>
                    <i class="fa-solid fa-chart-line text-success"></i>
                </div>
                <div class="card-body px-4 pb-4" style="min-height:260px;">
                    <canvas id="chart-growth-users"></canvas>
                </div>
            </div>
        </div>

        <!-- Nuevas Reseñas (Bar) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;">
                    <span class="fw-bold text-white">Nuevas Reseñas</span>
                    <i class="fa-solid fa-comment-dots text-info"></i>
                </div>
                <div class="card-body px-4 pb-4" style="min-height:260px;">
                    <canvas id="chart-growth-reviews"></canvas>
                </div>
            </div>
        </div>

        <!-- Estado de Coasters (Doughnut) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;">
                    <span class="fw-bold text-white">Estado de Coasters</span>
                    <i class="fa-solid fa-circle-info text-warning"></i>
                </div>
                <div class="card-body px-4 pb-4 d-flex justify-content-center align-items-center" style="min-height:260px;">
                    <canvas id="chart-dist-status" style="max-height:220px;"></canvas>
                </div>
            </div>
        </div>

        <!-- Usuarios por País (Bar) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;">
                    <span class="fw-bold text-white">Usuarios por País</span>
                    <i class="fa-solid fa-globe text-primary"></i>
                </div>
                <div class="card-body px-4 pb-4" style="min-height:260px;">
                    <canvas id="chart-dist-country"></canvas>
                </div>
            </div>
        </div>

        <!-- Actividad en Foros (Bar) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;">
                    <span class="fw-bold text-white">Actividad en Foros</span>
                    <i class="fa-solid fa-comments text-warning"></i>
                </div>
                <div class="card-body px-4 pb-4" style="min-height:220px;">
                    <canvas id="chart-growth-forum"></canvas>
                </div>
            </div>
        </div>

        <!-- Nuevos Viajes (Line) -->
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-0 h-100" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;">
                    <span class="fw-bold text-white">Nuevos Viajes</span>
                    <i class="fa-solid fa-suitcase-rolling text-success"></i>
                </div>
                <div class="card-body px-4 pb-4" style="min-height:220px;">
                    <canvas id="chart-growth-trips"></canvas>
                </div>
            </div>
        </div>

    </div><!-- /row charts -->

    <!-- TABLA DE ÚLTIMOS VIAJES -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-0" style="background:#161b22;">
                <div class="card-header border-0 d-flex justify-content-between align-items-center py-3 px-4" style="background:#161b22;border-bottom:1px solid rgba(255,255,255,0.05)!important;">
                    <span class="fw-bold text-white"><i class="fa-solid fa-list-check me-2 text-success"></i>Últimos Viajes Reservados</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-dark table-hover mb-0" style="background:transparent;">
                        <thead>
                            <tr style="border-color:rgba(255,255,255,0.05);">
                                <th class="py-3 px-4 text-muted small text-uppercase" style="background:transparent;">ID</th>
                                <th class="py-3 px-4 text-muted small text-uppercase" style="background:transparent;">Título</th>
                                <th class="py-3 px-4 text-muted small text-uppercase" style="background:transparent;">Usuario</th>
                                <th class="py-3 px-4 text-muted small text-uppercase" style="background:transparent;">Parque</th>
                                <th class="py-3 px-4 text-muted small text-uppercase" style="background:transparent;">Fechas</th>
                                <th class="py-3 px-4 text-muted small text-uppercase" style="background:transparent;">Fecha Reserva</th>
                            </tr>
                        </thead>
                        <tbody id="table-recent-trips">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</main>

<!-- Configuración del API pasada al JS externo -->
<script>
    window.DASHBOARD_API = '<?= $base_url ?>/api/php/admin/get_stats.php';
</script>
<script src="<?= Router::asset('web/js/admin/dashboard.js') ?>?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
