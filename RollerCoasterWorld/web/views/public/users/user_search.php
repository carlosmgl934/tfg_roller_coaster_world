<?php
require_once __DIR__ . '/../../partials/header.php';
// Solo logueados (opcional basado en requerimientos, pero la sección Comunidad está restringida a logueados
// en header.php, así que aseguramos aquí)
if (!$is_logged) {
    Router::redirect('login');
}
?>

<main class="container-fluid px-lg-5 my-5 min-vh-100">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom border-light border-opacity-50 pb-2 text-success">
                <i class="fa-solid fa-users me-2"></i> Buscar Usuarios
            </h1>
            <p class="text-muted text-uppercase fw-bold mt-3" style="letter-spacing: 0.1em; font-size: 0.85rem;">Encuentra a otros enthusiasts para ver sus tops, viajes y convertirte en su amigo.</p>
        </div>
    </div>

    <!-- Buscador -->
    <div class="row justify-content-center mb-5">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="input-group input-group-lg shadow-sm border border-secondary border-opacity-25 rounded-0 overflow-hidden bg-dark">
                <span class="input-group-text bg-success border-0 text-white px-4 rounded-0">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="page-user-search"
                    class="form-control text-white bg-transparent border-0 shadow-none ps-3 rounded-0"
                    placeholder="Buscar por nombre de usuario..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Contenedor Resultados -->
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div id="page-user-search-results" class="d-flex flex-column gap-3 pe-2" style="max-height: 60vh; overflow-y: auto; overflow-x: hidden;">
                <div class="text-center text-muted py-5 w-100">
                    <i class="fa-solid fa-magnifying-glass mb-3 d-block fa-3x opacity-25"></i>
                    <h5>Empieza a escribir para buscar...</h5>
                    <p class="small">Escribe al menos 2 letras</p>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="<?= $base_url ?>/web/js/user_search.js"></script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>