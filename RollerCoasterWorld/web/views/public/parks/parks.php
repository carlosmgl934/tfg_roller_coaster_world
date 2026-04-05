<?php
require_once __DIR__ . '/../../../routes/Router.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    Router::redirect('park_search');
}

require_once __DIR__ . '/../../partials/header.php';
?>

<!-- Estilos base de coasters para consistencia visual -->
<link rel="stylesheet" href="<?= Router::asset('web/css/coasters.css') ?>">
<!-- Estilos específicos de parques -->
<link rel="stylesheet" href="<?= Router::asset('web/css/parks.css') ?>">
<!-- CropperJS para recortar imágenes al subir fotos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<main class="container-fluid px-lg-5 my-5" data-logged="<?= $is_logged ? 'true' : 'false' ?>">

    <!-- HERO SECTION (Mirroring Coaster Detail) -->
    <div class="row g-4 mb-4 align-items-start">
        <!-- Imagen -->
        <div class="col-12 col-lg-7">
            <div class="hero-img-wrapper">
                <img src="https://placehold.co/900x500" alt="Parque" id="park-hero-img" referrerpolicy="no-referrer">
                <div class="img-overlay"></div>
            </div>
        </div>

        <!-- Info principal -->
        <div class="col-12 col-lg-5">
            <h1 class="fw-bold text-success mb-1" id="park-name" style="font-size:2rem;">Cargando...</h1>

            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="text-muted d-flex align-items-center gap-1">
                    <i class="fa-solid fa-map-pin text-success" style="font-size:.85rem;"></i>
                    <span id="park-location-header" style="font-size:.95rem;">Cargando...</span>
                </span>
                <span class="text-muted">•</span>
                <span id="park-country-header" class="fw-semibold text-dark" style="font-size:.95rem;">País</span>
            </div>

            <hr class="my-3">

            <!-- Stats cards 2x2 (Consistency with coasters) -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="stat-card card text-center p-2 h-100 d-flex flex-column justify-content-center">
                        <div class="text-muted small mb-1">Ranking Parques</div>
                        <div class="ranking-num text-success" id="global-ranking">—</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card card text-center p-2 h-100 d-flex flex-column justify-content-center">
                        <div class="text-muted small mb-1">Puntuación</div>
                        <div class="ranking-num text-success" id="park-score">—</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card card text-center p-2 h-100 d-flex flex-column justify-content-center">
                        <div class="text-muted small mb-1">Coasters Operativas</div>
                        <div class="ranking-num text-success" id="operating-coasters-val">—</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card card text-center p-2 h-100 d-flex flex-column justify-content-center">
                        <div class="text-muted small mb-1">Estado</div>
                        <div class="ranking-num text-success" id="current-state" style="font-size: 1.2rem;">Abierto</div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="d-flex gap-2">
                <a href="#" target="_blank" id="btn-website" class="btn btn-outline-success fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="border-radius:0; padding: 10px;">
                    <i class="fa-solid fa-globe fs-5"></i>
                    <span>Web Oficial</span>
                </a>
                <button id="btn-map" class="btn btn-outline-secondary" style="border-radius:0; padding:10px 14px;" title="Ver en mapa">
                    <i class="fa-solid fa-location-arrow fs-5"></i>
                </button>
                <button id="btn-share" class="btn btn-outline-secondary" style="border-radius:0; padding:10px 14px;" title="Compartir">
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
                    <i class="fa-solid fa-chart-simple"></i>
                    <span class="fw-semibold text-white">Estadísticas Rápidas</span>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 h-100">
                        <div class="col-6">
                            <div class="stat-block text-center border-bottom border-end">
                                <span class="stat-label">Año Apertura</span>
                                <span class="stat-value text-success" id="opening-year-val">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-block text-center border-bottom">
                                <span class="stat-label">Nº Coasters</span>
                                <span class="stat-value text-success fw-bold" id="stat-num-coasters">—</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-block text-center border-end">
                                <span class="stat-label">Reseñas Totales</span>
                                <span class="stat-value text-success" id="reviews-count-val">0</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-block text-center">
                                <span class="stat-label">Entrada (Desde)</span>
                                <span class="stat-value text-success" id="entry-price-val">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="section-card card h-100 border-0 ficha-card-bg">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                    <i class="fa-solid fa-folder-open"></i>
                    <span class="fw-semibold text-white">Ficha del Parque</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0 ficha-table-premium text-white">
                        <tbody>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label w-45"><i class="fa-solid fa-location-dot me-2 opacity-50"></i>Localización</td>
                                <td class="fw-semibold text-end align-middle text-white" id="park-location-table">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-solid fa-flag me-2 opacity-50"></i>País</td>
                                <td class="fw-semibold text-end align-middle text-white" id="park-country-table">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-solid fa-calendar-check me-2 opacity-50"></i>Inaugurado en</td>
                                <td class="fw-semibold text-end align-middle text-white" id="park-year-table">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-solid fa-euro-sign me-2 opacity-50"></i>Precio orientativo</td>
                                <td class="fw-semibold text-end align-middle text-white" id="park-price-table">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-solid fa-compass me-2 opacity-50"></i>Coordenadas</td>
                                <td class="fw-semibold text-end align-middle text-white" id="park-coords-table">N/A</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN: MONTAÑAS RUSAS OPERATIVAS (Requested) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="section-card card">
                <div class="card-header bg-success text-white d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-train-tram"></i>
                        <span class="fw-semibold text-white">Montañas Rusas del Parque</span>
                    </div>
                    <span class="badge badge-dark-green rounded-pill px-3 py-1 shadow-sm fs-6" id="operating-count-badge">0</span>
                </div>
                <div class="card-body p-3 pt-4">
                    <div class="row g-2" id="park-coasters-grid">
                        <!-- Se cargan aquí -->
                        <div class="col-12 text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-success me-2"></div>
                            Buscando atracciones operativas...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- FOTOS -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="section-card card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-images"></i>
                            <span class="fw-semibold text-white">Fotos del Parque</span>
                        </div>
                        <span class="badge badge-dark-green rounded-pill px-2 py-1 shadow-sm fs-6" id="photos-count">0</span>
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
                        <div class="col-12 text-center py-4 text-muted">
                            <i class="fa-regular fa-image fa-2x d-block mb-2"></i>
                            Aún no hay fotos de este parque
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA SUBIR FOTO -->
    <div class="modal fade" id="upload-photo-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22; color:#e6edf3;">
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title" id="upload-photo-title">Subir foto del parque</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="upload-photo-form">
                        <div class="mb-3">
                            <label for="photo" class="form-label fw-bold small text-uppercase text-muted">Selecciona una foto</label>
                            <input class="form-control rounded-0 border-success" style="background:#0d1117; color:#e6edf3;" type="file" id="photo" accept="image/*" required>
                        </div>
                        <div id="crop-container" class="mb-3 border border-secondary" style="display:none; overflow:hidden; max-height:400px; background:#000;">
                            <img id="crop-preview" style="max-width:100%; display:block;">
                        </div>
                        <div class="mb-3">
                            <label for="photo-caption" class="form-label fw-bold small text-uppercase text-muted">Descripción (opcional)</label>
                            <textarea class="form-control rounded-0 border-secondary" style="background:#0d1117; color:#e6edf3;" id="photo-caption" rows="2"
                                placeholder="¿Qué vemos en esta foto?"></textarea>
                        </div>
                        <button type="button" class="btn btn-success w-100 rounded-0 fw-bold py-2" id="upload-photo-btn">
                            SUBIR FOTO <i class="fa-solid fa-upload ms-1"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE NOTIFICACIÓN -->
    <div class="modal fade" id="notify-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary rounded-0">
                <div class="modal-header border-secondary pb-0" id="notify-modal-header">
                    <h6 class="modal-title fw-bold" id="notify-modal-title"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted mb-0 small" id="notify-modal-body"></p>
                </div>
                <div class="modal-footer border-secondary pt-0">
                    <button type="button" class="btn btn-success btn-sm rounded-0 px-4" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- IG LIGHTBOX MODAL (Para previsualizar fotos) -->
    <div class="modal fade" id="ig-lightbox-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content bg-dark text-white border-secondary rounded-0 overflow-hidden">
                <div class="modal-header border-secondary d-flex align-items-center py-2 px-3">
                    <img id="ig-modal-avatar" src="" alt="Avatar" class="rounded-circle me-2" style="width:32px; height:32px; object-fit:cover;">
                    <span id="ig-modal-username" class="fw-bold fs-6"></span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 position-relative">
                    <button id="ig-modal-prev" class="btn text-white position-absolute start-0 top-50 translate-middle-y bg-dark bg-opacity-50 border-0 rounded-end px-3 py-2" style="z-index: 10; display: none;"><i class="fa-solid fa-chevron-left"></i></button>
                    <button id="ig-modal-next" class="btn text-white position-absolute end-0 top-50 translate-middle-y bg-dark bg-opacity-50 border-0 rounded-start px-3 py-2" style="z-index: 10; display: none;"><i class="fa-solid fa-chevron-right"></i></button>
                    <img id="ig-modal-img" src="" alt="Foto" class="w-100" style="aspect-ratio: 1/1; object-fit:cover;">
                </div>
                <div class="modal-footer border-secondary flex-column align-items-start py-3 px-3">
                    <div class="fw-bold mb-2 pb-2 w-100 border-bottom border-secondary" id="ig-modal-likes" style="font-size: 0.95rem;">0 me gusta</div>
                    <div class="w-100 mt-1" style="font-size: 0.95rem;">
                        <span id="ig-modal-caption-user" class="fw-bold text-success me-2"></span>
                        <span id="ig-modal-caption" class="text-light"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RESEÑAS -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="section-card card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-star"></i>
                            <span class="fw-semibold text-white">Experiencias y Opiniones</span>
                        </div>
                        <span class="badge badge-dark-green rounded-pill px-2 py-1 shadow-sm fs-6" id="reviews-header-count">0</span>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm rounded-0 bg-dark text-white border-secondary" id="reviews-sort" style="width:auto;">
                            <option value="newest">Más recientes</option>
                            <option value="best">Mejor valorados</option>
                            <option value="worst">Peor valorados</option>
                        </select>
                        <a class="btn btn-sm btn-outline-light rounded-0 px-3" id="btn-write-review"
                            <?php if ($is_logged): ?>
                              href="<?= Router::url('form_park_rating') ?>?id=<?= $id ?>"
                            <?php else: ?>
                              href="#" data-bs-toggle="modal" data-bs-target="#loginModal"
                            <?php endif; ?>>
                            <i class="fa-solid fa-pen me-1"></i>Escribir reseña
                        </a>
                    </div>
                </div>
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


<?php
$login_msg = 'Para subir fotos o escribir reseñas necesitas iniciar sesión.';
require_once __DIR__ . '/../../partials/login_modal.php';
?>

<?php require_once __DIR__ . '/../../partials/footer.php';?>

<script src="<?= Router::asset('web/js/parks/parks.js') ?>"></script>

<!-- CropperJS para recortar imágenes -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
</main>
