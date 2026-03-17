<?php
// Calcular $base_url aquí (igual que header.php) para poder redirigir antes de emitir HTML
$base_url = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $_SERVER['SCRIPT_NAME']);

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: ' . $base_url . '/web/views/public/parks/park_search.php');
    exit;
}

require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css"> <!-- Reutilizamos el mismo CSS -->
<!-- CropperJS para recortar imágenes al subir fotos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<main class="container-fluid px-lg-5 my-5" data-logged="<?= $is_logged ? 'true' : 'false' ?>">
    <!-- HERO -->
    <div class="row g-4 mb-4 align-items-start">
        <!-- Imagen principal del parque -->
        <div class="col-12 col-lg-7">
            <div class="hero-img-wrapper">
                <img src="https://placehold.co/900x500" alt="Parque" id="park-hero-img">
                <div class="img-overlay"></div>
            </div>
        </div>

        <!-- Info principal del parque -->
        <div class="col-12 col-lg-5">
            <h1 class="fw-bold text-success mb-1" id="park-name" style="font-size:2.5rem;">Cargando...</h1>

            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="badge bg-success fs-5" id="park-rating">★ Cargando</span>
                <span class="text-muted fs-5" id="park-location">Cargando...</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Apertura</small>
                        <strong class="fs-4" id="opening-year">Cargando...</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">País</small>
                        <strong class="fs-4" id="park-country">Cargando...</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Coasters</small>
                        <strong class="fs-4" id="num-coaster">Cargando...</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Operativas</small>
                        <strong class="fs-4" id="operating-coasters">Cargando...</strong>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="p-3 bg-dark rounded text-center">
                        <small class="text-muted d-block">Entrada</small>
                        <strong class="fs-4" id="precio-entrada">Cargando...</strong>
                    </div>
                </div>
            </div>

            <p class="lead mb-4" id="park-description">Cargando descripción...</p>

            <div class="d-flex gap-3">
                <a href="#" class="btn btn-outline-light rounded-0 px-4" id="btn-website">
                    <i class="fa-solid fa-globe me-2"></i>Sitio web oficial
                </a>
                <button class="btn btn-outline-light rounded-0 px-4" id="btn-map">
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
                <!-- Fotos cargadas dinámicamente -->
            </div>
            <div class="text-center mt-4">
                <button class="btn btn-success rounded-0" id="btn-upload-photo"
                  <?= $is_logged ? '' : 'data-requires-login="true"' ?>>
                    <i class="fa-solid fa-camera me-2"></i>Subir foto del parque
                </button>
            </div>
        </div>
    </div>

    <!-- Sección reseñas -->
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">Reseñas del parque</h3>
                <div class="d-flex gap-3">
                    <select class="form-select w-auto" id="reviews-sort">
                        <option value="newest">Más recientes</option>
                        <option value="best">Mejor valoración</option>
                        <option value="worst">Peor valoración</option>
                    </select>
                    <a class="btn btn-success rounded-0 px-4" id="btn-write-review"
                      <?php if ($is_logged): ?>
                        href="<?= $base_url ?>/web/views/public/parks/form_park_rating.php?id=<?= $id ?>"
                      <?php else: ?>
                        href="#" data-bs-toggle="modal" data-bs-target="#loginModal"
                      <?php endif; ?>>
                        <i class="fa-solid fa-pen me-2"></i>Escribir reseña
                    </a>
                </div>
            </div>

            <div class="card bg-dark border-0 shadow-sm">
                <div class="card-body" id="reviews-list">
                    <div class="text-center text-muted py-5">
                        <i class="fa-regular fa-comment-dots fa-3x mb-3 d-block"></i>
                        Aún no hay reseñas para este parque
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal para subir foto -->
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

<?php
$login_msg = 'Para subir fotos o escribir reseñas necesitas iniciar sesión.';
require_once __DIR__ . '/../../partials/login_modal.php';
?>

<?php require_once __DIR__ . '/..//../partials/footer.php';?>

<script src="<?= $base_url ?>/web/js/parks.js"></script>

<!-- CropperJS para recortar imágenes -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>