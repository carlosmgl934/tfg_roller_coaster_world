<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: ' . $base_url . '/web/views/public/coasters/coaster_search.php');
    exit;
}
?>
<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">
<!-- CropperJS para recortar imágenes al subir fotos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<main class="container-fluid px-lg-5 my-5" data-logged="<?= $is_logged ? 'true' : 'false' ?>">

    <!-- HERO -->
    <div class="row g-4 mb-4 align-items-start">

        <!-- Imagen -->
        <div class="col-12 col-lg-7">
            <div class="hero-img-wrapper">
                <img src="https://placehold.co/900x500" alt="Coaster" id="coaster-hero-img" referrerpolicy="no-referrer">
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
                            <tr class="ficha-row">
                                <td class="ficha-label">Fabricante</td>
                                <td class="ficha-value" id="coaster-manufacter">—</td>
                            </tr>
                            <tr class="ficha-row ficha-row-alt">
                                <td class="ficha-label">Modelo</td>
                                <td class="ficha-value" id="coaster-model">—</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Año apertura</td>
                                <td class="ficha-value" id="coaster-year">—</td>
                            </tr>
                            <tr class="ficha-row ficha-row-alt">
                                <td class="ficha-label">Estado</td>
                                <td class="ficha-value" id="current-state-table">—</td>
                            </tr>
                            <tr class="ficha-row">
                                <td class="ficha-label">Parque</td>
                                <td class="ficha-value">
                                    <a href="<?= $base_url ?>/web/views/public/parks/park_detail.php?id=<?= $id ?>"
                                        class="text-success fw-bold text-decoration-none" id="park-name-table">—</a>
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
                    <button class="btn btn-sm btn-outline-light rounded-0 px-3"
                        <?php if ($is_logged): ?>
                          id="upload-photo" data-bs-toggle="modal" data-bs-target="#upload-photo-modal"
                        <?php else: ?>
                          id="upload-photo" data-bs-toggle="modal" data-bs-target="#loginModal"
                        <?php endif; ?>>
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

    <!-- MODAL PARA SUBIR FOTO -->
    <div class="modal fade" id="upload-photo-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="upload-photo-title">Subir foto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="upload-photo-form">
                        <div class="mb-3">
                            <label for="photo" class="form-label">Selecciona una foto</label>
                            <input class="form-control" type="file" id="photo" accept="image/*" required>
                        </div>
                        <div id="crop-container" class="mb-3" style="display:none; overflow:hidden; max-height:320px;">
                            <img id="crop-preview" style="max-width:100%; display:block;">
                        </div>
                        <div class="mb-3">
                            <label for="photo-caption" class="form-label">Descripción (opcional)</label>
                            <textarea class="form-control" id="photo-caption" rows="2"
                                placeholder="¿De qué va esta foto?"></textarea>
                        </div>
                        <button type="button" class="btn btn-success w-100" id="upload-photo-btn">
                            Subir foto <i class="fa-solid fa-upload ms-1"></i>
                        </button>
                    </form>
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
                        <a class="btn btn-sm btn-outline-light rounded-0 px-3" id="btn-write-review"
                            <?php if ($is_logged): ?>
                              href="<?= $base_url ?>/web/views/public/coasters/form_rating.php?id=<?= $id ?>"
                            <?php else: ?>
                              href="#" data-bs-toggle="modal" data-bs-target="#loginModal"
                            <?php endif; ?>>
                            <i class="fa-solid fa-pen me-1"></i>Escribir reseña
                        </a>
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

<?php
$login_msg = 'Para subir fotos o escribir reseñas necesitas iniciar sesión.';
require_once __DIR__ . '/../../partials/login_modal.php';
?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>

<!-- CropperJS para recortar imágenes al subir fotos -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>