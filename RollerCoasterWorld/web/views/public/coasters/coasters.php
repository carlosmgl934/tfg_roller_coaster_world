<?php
require_once __DIR__ . '/../../../routes/Router.php';
Router::init();

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    Router::redirect('coaster_search');
    exit;
}

$page_css = ['web/css/coasters.css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
?>

<!-- CropperJS para recortar imágenes al subir fotos -->
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
                        <div class="text-muted small mb-1">Puntuación <span class="text-success opacity-75 ms-1" style="font-size: 0.65rem;">(<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>)</span></div>
                        <div class="ranking-num text-success"><span id="coaster-score">—</span> <i class="fa-solid fa-star ms-1" style="font-size: 1.25rem; vertical-align: baseline;"></i></div>
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
                <?php if (isset($is_admin) && $is_admin): ?>
                <a href="<?= Router::url('admin_coasters') ?>?edit_coaster=<?= $id ?>" class="btn btn-outline-primary" style="border-radius:0; padding:10px 14px;" title="Editar Coaster (Admin)">
                    <i class="fa-solid fa-pen-to-square fs-5"></i>
                </a>
                <?php endif; ?>
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
                <div class="card-body p-3">
                    <div class="row g-3 h-100">
                        <div class="col-6">
                            <div class="premium-stat-box d-flex flex-column align-items-center justify-content-center text-center">
                                <i class="fa-solid fa-arrows-up-to-line premium-stat-icon"></i>
                                <div class="premium-stat-label">Altura</div>
                                <div class="premium-stat-value" id="coaster-height">N/A</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="premium-stat-box d-flex flex-column align-items-center justify-content-center text-center">
                                <i class="fa-solid fa-gauge-simple-high premium-stat-icon"></i>
                                <div class="premium-stat-label">Velocidad</div>
                                <div class="premium-stat-value" id="coaster-speed">N/A</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="premium-stat-box d-flex flex-column align-items-center justify-content-center text-center">
                                <i class="fa-solid fa-ruler-horizontal premium-stat-icon"></i>
                                <div class="premium-stat-label">Longitud</div>
                                <div class="premium-stat-value" id="coaster-length">N/A</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="premium-stat-box d-flex flex-column align-items-center justify-content-center text-center">
                                <i class="fa-solid fa-rotate-right premium-stat-icon"></i>
                                <div class="premium-stat-label">Inversiones</div>
                                <div class="premium-stat-value" id="coaster-inversions">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="section-card card h-100 border-0 ficha-card-bg">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clipboard-list"></i>
                    <span class="fw-semibold">Ficha Técnica</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0 ficha-table-premium text-white">
                        <tbody>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label w-45"><i class="fa-solid fa-industry me-2 opacity-50"></i>Fabricante</td>
                                <td class="fw-semibold text-end align-middle text-white" id="coaster-manufacter">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-solid fa-puzzle-piece me-2 opacity-50"></i>Modelo</td>
                                <td class="fw-semibold text-end align-middle text-white" id="coaster-model">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-regular fa-calendar-days me-2 opacity-50"></i>Año apertura</td>
                                <td class="fw-semibold text-end align-middle text-white" id="coaster-year">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase align-middle ficha-table-label"><i class="fa-solid fa-traffic-light me-2 opacity-50"></i>Estado</td>
                                <td class="fw-semibold text-end align-middle text-white" id="current-state-table">—</td>
                            </tr>
                            <tr>
                                <td class="text-uppercase text-muted align-middle ficha-table-label"><i class="fa-solid fa-tree-city me-2 opacity-50"></i>Parque</td>
                                <td class="fw-bold text-end align-middle">
                                    <a href="<?= $base_url ?>/web/views/public/parks/parks.php?id=<?= $id ?>"
                                        class="text-success text-decoration-none border-bottom border-success border-2 pb-1 hover-fx" id="park-name-table">—</a>
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
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-images"></i>
                            <span class="fw-semibold">Fotos</span>
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
    <!-- MODAL DE NOTIFICACIÓN -->
    <div class="modal fade" id="notify-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content bg-dark border-secondary">
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

    <!-- IG LIGHTBOX MODAL -->
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
                    <div class="d-flex align-items-center w-100 mb-2">
                        <button id="ig-modal-like-btn" class="btn btn-link text-white p-0 me-3 fs-3 text-decoration-none" style="transition: transform 0.2s;" title="Me gusta">
                            <i class="fa-regular fa-heart"></i>
                        </button>
                    </div>
                    <div class="fw-bold mb-2 pb-2 w-100 border-bottom border-secondary" id="ig-modal-likes" style="font-size: 0.95rem;">0 me gusta</div>
                    <div class="w-100 mt-1" style="font-size: 0.95rem;">
                        <span id="ig-modal-caption-user" class="fw-bold text-success me-2"></span>
                        <span id="ig-modal-caption" class="text-light"></span>
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
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-star"></i>
                            <span class="fw-semibold">Reseñas</span>
                        </div>
                        <span class="badge badge-dark-green rounded-pill px-2 py-1 shadow-sm fs-6" id="reviews-count">0</span>
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

<!-- MODAL EDITAR RESEÑA (coaster) -->
<div class="modal fade" id="edit-review-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-secondary text-white">
      <div class="modal-header bg-success">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Editar reseña</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-review-id">
        <!-- Estrellas -->
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold">Puntuación</label>
          <div class="star-rating edit-star-rating-container" style="font-size: 2rem;">
            <?php for ($i = 10; $i >= 1; $i--):
              $value = $i / 2;
              $half = ($i % 2 !== 0);
            ?>
              <input type="radio" name="edit_note" id="estar<?= $i ?>" value="<?= $value ?>">
              <label for="estar<?= $i ?>" class="<?= $half ? 'half' : 'full' ?>" title="<?= $value ?>"></label>
            <?php endfor; ?>
          </div>
          <input type="hidden" id="edit-review-note" value="0">
        </div>
        <!-- Texto -->
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold">Reseña (opcional)</label>
          <textarea class="form-control bg-dark text-white border-secondary rounded-0" id="edit-review-text" rows="4" placeholder="Escribe tu opinión..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success rounded-0 fw-bold px-4" id="save-edit-review-btn">
          <i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios
        </button>
      </div>
    </div>
  </div>
</div>

<?php
$login_msg = 'Para subir fotos o escribir reseñas necesitas iniciar sesión.';
require_once __DIR__ . '/../../partials/login_modal.php';
?>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script>
  window.CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null' ?>;
  window.CURRENT_USERNAME = <?= isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null' ?>;
</script>
<script src="<?= Router::asset('web/js/coasters/coasters.js') ?>?v=<?= time() ?>"></script>

<!-- CropperJS para recortar imágenes al subir fotos -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>