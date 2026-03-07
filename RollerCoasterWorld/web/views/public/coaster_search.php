<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">
<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success text-center">Base de Datos de Montañas Rusas
            </h1>
        </div>
    </div>

    <div class="row g-4">
        <!-- IZQUIERDA: Filtros -->
        <aside class="col-12 col-lg-3 sidebar-filter" id="sidebar-filter">
            <div class="card shadow-sm border-0 sticky-top" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Los filtros irán aquí...</p>
                    <!-- Aquí meteremos los checks de marca, tipo, año, etc -->
                </div>
            </div>
        </aside>

        <!-- CENTRO: Lista -->
        <div class="col-12 col-lg-6">
            <!-- Quitamos 'coaster-list' genérico y ponemos 'list-group' puro -->
            <div class="list-group shadow-sm" id="coaster-list" style="border-radius: 8px;"></div>

            <div class="pagination mt-4 justify-content-center" id="pagination"></div>
        </div>

        <!-- DERECHA: Buscador -->
        <div class="col-12 col-lg-3">
            <div class="sticky-top" style="top: 90px; z-index: 100;">
                <div class="position-relative">
                    <input type="text" id="coaster-search" name="coaster-search"
                        class="form-control shadow-sm pe-5 border-success" placeholder="Buscar por nombre..."
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


<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>