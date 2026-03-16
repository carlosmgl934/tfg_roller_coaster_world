<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: ' . $base_url . '/web/views/public/parks/park_search.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<main class="container-fluid px-lg-5 my-5" data-logged="<?= $is_logged ? 'true' : 'false' ?>">
    <div class="row g-4 mb-5">
        <!-- Hero imagen -->
        <div class="col-12 col-lg-7">
            <div class="hero-img-wrapper position-relative">
                <img src="https://placehold.co/1200x600" alt="Parque" id="park-hero-img" class="img-fluid rounded">
                <div class="img-overlay"></div>
            </div>
        </div>

        <!-- Info principal -->
        <div class="col-12 col-lg-5">
            <h1 class="fw-bold text-success mb-3" id="park-name">Cargando...</h1>

            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="badge bg-success fs-4" id="park-rating">★ Cargando</span>
                <span class="text-muted fs-5" id="park-location">Cargando ubicación...</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Apertura</small>
                        <strong class="fs-4" id="opening-year">—</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">País</small>
                        <strong class="fs-4" id="park-country">—</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Coasters</small>
                        <strong class="fs-4" id="num-coaster">—</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Operativas</small>
                        <strong class="fs-4" id="operating-coasters">—</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Entrada</small>
                        <strong class="fs-4" id="precio-entrada">—</strong>
                    </div>
                </div>
            </div>

            <p class="lead mb-4" id="park-description">Cargando descripción...</p>

            <div class="d-flex gap-3 flex-wrap">
                <a href="#" class="btn btn-outline-light rounded-0 px-4 py-2" id="btn-website">
                    <i class="fa-solid fa-globe me-2"></i>Sitio web oficial
                </a>
                <button class="btn btn-outline-light rounded-0 px-4 py-2" id="btn-map">
                    <i class="fa-solid fa-map-marker-alt me-2"></i>Ver en mapa
                </button>
            </div>
        </div>
    </div>

    <!-- Galería de fotos -->
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="fw-bold mb-3">Fotos del parque</h3>
            <div class="row g-3" id="park-gallery">
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-3 text-muted">Cargando fotos...</p>
                </div>
            </div>
            <div class="text-center mt-4">
                <button class="btn btn-success rounded-0 px-4" id="btn-upload-photo">
                    <i class="fa-solid fa-camera me-2"></i>Subir foto del parque
                </button>
            </div>
        </div>
    </div>

    <!-- Reseñas -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h3 class="fw-bold mb-0">Reseñas del parque</h3>
                <div class="d-flex gap-3">
                    <select class="form-select w-auto" id="reviews-sort">
                        <option value="newest">Más recientes</option>
                        <option value="best">Mejor valoración</option>
                        <option value="worst">Peor valoración</option>
                    </select>
                    <a class="btn btn-success rounded-0 px-4" id="btn-write-review" href="<?= $base_url ?>/web/views/public/parks/form_park_rating.php?id=<?= $id ?>">
                        <i class="fa-solid fa-pen me-2"></i>Escribir reseña
                    </a>
                </div>
            </div>

            <div class="card bg-dark border-0 shadow-sm">
                <div class="card-body p-4" id="reviews-list">
                    <div class="text-center text-muted py-5">
                        <i class="fa-regular fa-comment-dots fa-3x mb-3 d-block"></i>
                        Aún no hay reseñas para este parque
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal subida foto -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1" aria-labelledby="uploadPhotoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="uploadPhotoModalLabel">Subir foto del parque</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="crop-container mb-3" style="display:none;">
                    <img id="cropper-image" style="max-width:100%;">
                </div>
                <input type="file" id="photo-upload" accept="image/*" class="form-control mb-3">
                <div class="text-center">
                    <button class="btn btn-success" id="crop-save-btn" style="display:none;">Guardar foto recortada</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Login -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <p class="mb-4" style="font-size:1rem;">Para escribir una reseña necesitas estar registrado</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <a href="<?= $base_url ?>/web/views/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-success px-4">Ir al Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php';?>

<script src="<?= $base_url ?>/web/js/parks.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>