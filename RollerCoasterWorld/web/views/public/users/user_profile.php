<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

// Perfil público de otro usuario — no requiere login
$user_id = $_GET['id'] ?? null;
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/profile.css">

<main class="container-fluid px-lg-5 my-5" id="profile-content" style="display: none;">
    <!-- Hero Section / Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card profile-card overflow-hidden border-0 shadow-lg">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 bg-dark d-flex align-items-center justify-content-center py-5 border-end border-success border-opacity-10 position-relative">
                            <div class="text-center">
                                <div class="avatar-circle mb-3" id="user-avatar">?</div>
                                <h3 class="fw-bold text-white mb-1" id="user-username">---</h3>
                                <p class="text-success small fw-bold mb-3" id="user-location">
                                    <i class="fa-solid fa-location-dot me-1"></i> <span>Ubicación desconocida</span>
                                </p>
                                <div id="friendship-action-container">
                                    <!-- Botón de amistad inyectado via JS -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8 bg-dark bg-opacity-50 p-4 p-lg-5">
                            <div class="row g-4">
                                <div class="col-6 col-sm-3 text-center border-end border-secondary border-opacity-25">
                                    <div class="h3 fw-bold text-success mb-0" id="stat-coasters">0</div>
                                    <div class="small text-muted text-uppercase fw-bold ls-1">Coasters</div>
                                </div>
                                <div class="col-6 col-sm-3 text-center border-end border-secondary border-opacity-25">
                                    <div class="h3 fw-bold text-success mb-0" id="stat-parks">0</div>
                                    <div class="small text-muted text-uppercase fw-bold ls-1">Parques</div>
                                </div>
                                <div class="col-12 col-sm-6 ps-md-4">
                                    <div class="mb-3">
                                        <label class="form-label text-white opacity-50 small mb-1">Coaster Favorita</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-star text-warning me-2"></i>
                                            <span class="fw-bold text-white" id="user-fav-coaster">---</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label text-white opacity-50 small mb-1">Parque Home</label>
                                        <div class="d-flex align-items-center">
                                            <i class="fa-solid fa-house-chimney text-success me-2"></i>
                                            <span class="fw-bold text-white" id="user-home-park">---</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Sections -->
    <div class="row g-4">
        <!-- Tops Section -->
        <div class="col-12 col-lg-8">
            <div class="card bg-dark text-white border-0 shadow-sm profile-card">
                <div class="card-header bg-transparent border-bottom border-success border-opacity-25 py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-trophy me-2 text-warning"></i>Top 5 Parques Favoritos</h5>
                </div>
                <div class="card-body p-4">
                    <div id="user-tops-container" class="row g-3">
                        <!-- Tops inyectados via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Recent Activity or Friends -->
        <div class="col-12 col-lg-4">
            <div class="card bg-dark text-white border-0 shadow-sm profile-card">
                <div class="card-header bg-transparent border-bottom border-success border-opacity-25 py-3">
                    <h5 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Info Comunidad</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">Miembro desde: <span class="text-white fw-bold" id="user-joined">---</span></p>
                    <hr class="border-secondary opacity-25">
                    <div class="d-grid gap-2">
                        <a href="<?= Router::url('trip_generator') ?>" class="btn btn-outline-success btn-sm py-2">
                            <i class="fa-solid fa-wand-magic-sparkles me-2"></i>¡Planear viaje con este usuario!
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div id="profile-loading" class="text-center py-5">
    <div class="spinner-border text-success" role="status"></div>
    <p class="mt-3 text-muted fw-bold">Cargando perfil de aventurero...</p>
</div>

<script src="<?= $base_url ?>/web/js/user_profile.js"></script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/profile.js"></script>