<?php
require_once __DIR__ . '/../partials/header.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: ' . $base_url . '/web/views/public/coaster_search.php');
    exit;
}
?>
<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">

<main class="container-fluid px-lg-5 my-5">

    <!-- HERO -->
    <div class="row g-4 mb-4 align-items-start">

        <!-- Imagen -->
        <div class="col-12 col-lg-7">
            <div class="hero-img-wrapper">
                <img src="https://placehold.co/900x500" alt="Coaster" id="coaster-hero-img">
                <div class="img-overlay"></div>
            </div>
        </div>

        <!-- Info principal -->
        <div class="col-12 col-lg-5">
            <h1 class="fw-bold text-success mb-1" id="coaster-name" style="font-size:2rem;">Cargando...</h1>

            <div class="d-flex align-items-center gap-2 mb-3">
                <a href="#" class="text-muted text-decoration-none d-flex align-items-center gap-1" id="park-link">
                    <i class="fa-solid fa-map-pin text-success" style="font-size:.85rem;"></i>
                    <span id="park-name" style="font-size:.95rem;">Cargando...</span>
                </a>
                <span class="text-muted">•</span>
                <span id="coaster-country" class="fw-semibold text-dark" style="font-size:.95rem;">País</span>
                <span id="status-badge" class="status-badge status-default ms-1">N/A</span>
            </div>

            <hr class="my-3">

            <!-- Stats cards 2x2 -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="stat-card card text-center p-2">
                        <div class="text-muted small mb-1">Ranking Global</div>
                        <div class="ranking-num text-success" id="global-ranking">—</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card card text-center p-2">
                        <div class="text-muted small mb-1">Puntuación</div>
                        <div class="ranking-num text-success" id="coaster-score">—</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card card text-center p-2">
                        <div class="text-muted small mb-1">Tu ranking</div>
                        <div class="ranking-num text-success" id="pesonal-ranking">—</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card card text-center p-2">
                        <div class="text-muted small mb-1">Estado</div>
                        <div class="ranking-num text-success" id="current-state">N/A</div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="d-flex gap-2">
                <button id="btn-ridden"
                    class="btn btn-outline-secondary fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2"
                    style="border-radius:0; padding: 10px; transition: all 0.3s ease;">
                    <i class="fa-solid fa-xmark fs-5" id="coaster-ridden"></i>
                    <span>No montada</span>
                </button>
                <button id="btn-favorite" class="btn btn-outline-warning" style="border-radius:0; padding:10px 14px;"
                    title="Favorito">
                    <i class="fa-regular fa-star fs-5" id="fav-icon"></i>
                </button>
                <button id="btn-share" class="btn btn-outline-secondary" style="border-radius:0; padding:10px 14px;"
                    title="Compartir">
                    <i class="fa-solid fa-share-nodes fs-5"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ESTADÍSTICAS + FICHA TÉCNICA -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="section-card card h-100">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span class="fw-semibold">Estadísticas</span>
                </div>
                <div class="card-body p-0 d-flex flex-column">
                    <div class="row g-0 text-center flex-grow-1">
                        <div class="col-6 stat-block border-end border-bottom border-success border-opacity-25">
                            <div class="stat-label">Altura</div>
                            <div class="stat-value" id="coaster-height">N/A</div>
                        </div>
                        <div class="col-6 stat-block border-bottom border-success border-opacity-25">
                            <div class="stat-label">Velocidad</div>
                            <div class="stat-value" id="coaster-speed">N/A</div>
                        </div>
                        <div class="col-6 stat-block border-end border-success border-opacity-25">
                            <div class="stat-label">Longitud</div>
                            <div class="stat-value" id="coaster-length">N/A</div>
                        </div>
                        <div class="col-6 stat-block">
                            <div class="stat-label">Inversiones</div>
                            <div class="stat-value" id="coaster-inversions">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="section-card card h-100">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span class="fw-semibold">Ficha Técnica</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            <tr class="border-bottom">
                                <td class="text-muted ps-4 py-3" style="width:45%">Fabricante</td>
                                <td class="fw-semibold py-3" id="coaster-manufacter">—</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted ps-4 py-3">Modelo</td>
                                <td class="fw-semibold py-3" id="coaster-model">—</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted ps-4 py-3">Año apertura</td>
                                <td class="fw-semibold py-3" id="coaster-year">—</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted ps-4 py-3">Estado</td>
                                <td class="fw-semibold py-3" id="current-state-table">—</td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-4 py-3">Parque</td>
                                <td class="py-3">
                                    <a href="<?= $base_url ?>/web/views/public/park_detail.php?id=<?= $id ?>"
                                        class="text-success fw-semibold text-decoration-none" id="park-name-table">—</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- FOTOS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="section-card card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-images"></i>
                        <span class="fw-semibold">Fotos</span>
                        <span class="badge bg-white text-success" id="photos-count">0</span>
                    </div>
                    <button class="btn btn-sm btn-outline-light rounded-0 px-3">
                        <i class="fa-solid fa-upload me-1"></i>Subir foto
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2" id="photos-grid">
                        <!-- Las fotos se cargan dinámicamente -->
                    </div>
                    <div class="text-center mt-3">
                        <a href="#" class="text-success fw-semibold text-decoration-none">
                            Ver todas las fotos <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VALORACIONES -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="section-card card">
                <div
                    class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-star"></i>
                        <span class="fw-semibold">Reseñas</span>
                        <span class="badge bg-white text-success" id="reviews-count">0</span>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm rounded-0" id="reviews-order" style="width:auto;">
                            <option value="default">Más relevantes</option>
                            <option value="recent">Más recientes</option>
                            <option value="best">Mejor valoración</option>
                            <option value="worst">Peor valoración</option>
                        </select>
                        <button class="btn btn-sm btn-outline-light rounded-0 px-3" id="btn-write-review">
                            <i class="fa-solid fa-pen me-1"></i>Escribir reseña
                        </button>
                    </div>
                </div>
                <div class="card-body" id="reviews-list">
                    <div class="text-center text-muted py-4">
                        <i class="fa-regular fa-comment-dots fs-2 mb-2 d-block"></i>
                        Aún no hay reseñas
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>