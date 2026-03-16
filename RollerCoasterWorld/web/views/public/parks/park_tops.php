<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$isLoggedIn = isset($_SESSION['firebase_uid']);
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css"> <!-- Reutilizamos el mismo CSS -->

<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold text-success">Tops de Parques de Atracciones</h1>
            <p class="lead text-muted mt-3">Los parques mejor valorados por la comunidad</p>
        </div>
    </div>

    <!-- Filtros rápidos -->
    <div class="row justify-content-center mb-5">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <select class="form-select w-auto" id="top-type">
                    <option value="global">Top Global</option>
                    <?php if ($isLoggedIn): ?>
                        <option value="personal">Mi Top Personal</option>
                    <?php endif; ?>
                    <option value="rating">Mejor Rating</option>
                    <option value="coasters">Más Coasters</option>
                    <option value="newest">Más Recientes</option>
                </select>

                <select class="form-select w-auto" id="top-country">
                    <option value="">Todos los países</option>
                    <!-- Se llenará dinámicamente con JS -->
                </select>

                <button class="btn btn-outline-light" id="refresh-tops">Actualizar</button>
            </div>
        </div>
    </div>

    <!-- Podio (Top 3) -->
    <div class="row justify-content-center mb-5" id="top-podium">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-success" role="status"></div>
            <p class="mt-3 text-muted">Cargando tops...</p>
        </div>
    </div>

    <!-- Listado completo de ranking -->
    <div class="row g-4" id="tops-list">
        <!-- Rankings cargados dinámicamente -->
    </div>
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script src="<?= $base_url ?>/web/js/parks.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>