<?php
$page_css = ['web/css/coasters.css'];
require_once __DIR__ . '/../../partials/header.php';
// if (!$is_logged) { Router::redirect('login'); } // Descomentar si es privado
?>
<main class="container-fluid px-lg-5 my-5 min-vh-100">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom border-light border-opacity-50 pb-2 text-success">
                <i class="fa-solid fa-ranking-star me-2"></i> Tops de la Comunidad
            </h1>
            <p class="text-muted text-uppercase fw-bold mt-3" style="letter-spacing: 0.1em; font-size: 0.85rem;">
                Descubre los ránkings de otros enthusiasts
            </p>
        </div>
    </div>

    <!-- Controles de búsqueda y filtrado -->
    <div class="row justify-content-center mb-5">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center bg-dark p-3 border border-secondary border-opacity-25" style="border-radius: 0;">
                
                <!-- Buscador -->
                <div class="flex-grow-1 position-relative" style="min-width: 200px;">
                    <input type="text" id="top-search" class="form-control bg-transparent text-white border-success rounded-0 ps-3 pe-5 py-2 shadow-sm" placeholder="Buscar usuario..." style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass position-absolute text-muted" style="right: 14px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                
                <!-- Ordenar por -->
                <select id="sort-select" class="form-select bg-transparent text-white border-success rounded-0 py-2 shadow-sm w-auto" style="border-width: 2px;">
                    <option value="date_desc" style="background: #212529;" selected>Última modificación</option>
                    <option value="credits_desc" style="background: #212529;">Mayor nº credits</option>
                    <option value="alpha_asc" style="background: #212529;">Orden alfabético</option>
                </select>

                <!-- Filtro Amigos -->
                <div class="form-check form-switch fs-5 d-flex align-items-center ms-md-3">
                    <input class="form-check-input rounded-0 bg-transparent border-success focus-ring focus-ring-success mt-0" type="checkbox" role="switch" id="filterFriends" style="width: 2.5em; height: 1.2em; border-width: 2px; cursor: pointer;">
                    <label class="form-check-label ms-2 text-white" for="filterFriends" style="font-size: 0.95rem; cursor: pointer;"><i class="fa-solid fa-user-group text-success me-1"></i> Solo amigos</label>
                </div>

            </div>
        </div>
    </div>

    <!-- Grid de Tops — rellenado dinámicamente por coaster_tops.js -->
    <div class="row g-4 justify-content-center" id="tops-grid">
        <div class="col-12 text-center py-5" id="tops-loading">
            <div class="spinner-border text-success" role="status"></div>
            <p class="mt-3 text-muted">Cargando tops...</p>
        </div>
    </div>
</main>

<?php 
require_once __DIR__ . '/../../partials/login_modal.php';
require_once __DIR__ . '/../../partials/footer.php'; 
?>
<script>
    window.BASE_URL = '<?= $base_url ?>';
    window.IS_LOGGED_IN = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
</script>
<script src="<?= Router::asset('web/js/coasters/coaster_tops.js') ?>"></script>
