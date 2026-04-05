<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$isLoggedIn = isset($_SESSION['firebase_uid']);
?>

<?php
$filter = rtrim($_GET['filter'] ?? 'global', '/');
$isGlobal = ($filter === 'global');

$pageTitle = $isGlobal ? 'Ranking Global de Parques' : 'Tops de Parques de Usuarios';
$pageSubtitle = $isGlobal ? 'Los mejores parques del mundo según valoraciones' : 'Descubre los parques favoritos de la comunidad y tus amigos';
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css"> <!-- Reutilizamos el mismo CSS -->

<main class="container-fluid px-lg-5 my-5">
    <input type="hidden" id="initial-filter" value="<?= htmlspecialchars($filter) ?>">
    
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold text-success"><?= $pageTitle ?></h1>
            <p class="lead text-muted mt-3"><?= $pageSubtitle ?></p>
        </div>
    </div>

    <!-- Filtros rápidos -->
    <?php if (!$isGlobal): ?>
    <div class="row justify-content-center mb-5">
        <div class="col-12 text-center">
            <div class="d-inline-flex gap-3 align-items-center bg-dark p-3 rounded-4 shadow-lg border border-success border-opacity-25">
                <label for="top-type" class="text-white fw-bold mb-0 ms-2"><i class="fa-solid fa-ranking-star text-warning me-2"></i>Ver Ranking:</label>
                <select class="form-select w-auto fw-semibold border-0 shadow-none bg-success text-white" id="top-type" style="cursor: pointer; min-width: 250px;">
                    <option value="users" class="bg-dark text-white" <?= $filter === 'users' ? 'selected' : '' ?>>👥 Top de Usuarios (Comunidad)</option>
                    <?php if ($isLoggedIn): ?>
                        <option value="friends" class="bg-dark text-white" <?= $filter === 'friends' ? 'selected' : '' ?>>🤝 Tops de tus Amigos</option>
                    <?php endif; ?>
                </select>
                <div class="spinner-border spinner-border-sm text-success ms-2 d-none" role="status" id="top-loading-spinner"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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

<script src="<?= Router::asset('web/js/parks/park_tops.js') ?>"></script>
