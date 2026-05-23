<?php
require_once __DIR__ . '/../../../routes/Router.php';

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    Router::redirect('park_search');
}

$page_css = ['web/css/coasters.css', 'web/css/parks.css', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css'];
require_once __DIR__ . '/../../partials/header.php';
?>

<!-- Estilos base de coasters para consistencia visual -->

<!-- Estilos específicos de parques -->

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
                        <div class="text-muted small mb-1">Puntuación <span class="text-success opacity-75 ms-1" style="font-size: 0.65rem;">(<i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>)</span></div>
                        <div class="ranking-num text-success"><span id="park-score">—</span> <i class="fa-solid fa-star ms-1" style="font-size: 1.25rem; vertical-align: baseline;"></i></div>
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
            <div class="d-flex flex-wrap gap-2" id="park-action-buttons">
                <!-- Fila 1: Comprar (se inserta por JS) + Web Oficial -->
                <a href="#" target="_blank" id="btn-website" class="btn btn-outline-success fw-bold flex-grow-1 d-flex align-items-center justify-content-center gap-2" style="border-radius:0; padding: 10px; min-width: 130px;">
                    <i class="fa-solid fa-globe fs-5"></i>
                    <span>Web Oficial</span>
                </a>
                <!-- Fila 2: iconos secundarios -->
                <div class="d-flex gap-2 flex-shrink-0">
                    <button id="btn-map" class="btn btn-outline-secondary" style="border-radius:0; padding:10px 14px;" title="Ver en mapa">
                        <i class="fa-solid fa-location-arrow fs-5"></i>
                    </button>
                    <button id="btn-share" class="btn btn-outline-secondary" style="border-radius:0; padding:10px 14px;" title="Compartir">
                        <i class="fa-solid fa-share-nodes fs-5"></i>
                    </button>
                    <?php if (isset($is_admin) && $is_admin): ?>
                    <a href="<?= Router::url('admin_parks') ?>?edit_park=<?= $id ?>"
                       class="btn btn-outline-primary" style="border-radius:0; padding:10px 14px;" title="Editar parque (Admin)">
                        <i class="fa-solid fa-pen-to-square fs-5"></i>
                    </a>
                    <?php endif; ?>
                </div>
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

<div class="modal fade" id="edit-review-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark border-secondary text-white">
      <div class="modal-header bg-success">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Editar reseña</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-review-id">
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold">Puntuación</label>
          <div class="star-rating edit-star-rating-container" style="font-size: 2rem;">
            <?php for ($i = 10; $i >= 1; $i--):
              $value = $i / 2;
              $half = ($i % 2 !== 0);
            ?>
              <input type="radio" name="edit_note" id="pestar<?= $i ?>" value="<?= $value ?>">
              <label for="pestar<?= $i ?>" class="<?= $half ? 'half' : 'full' ?>" title="<?= $value ?>"></label>
            <?php endfor; ?>
          </div>
          <input type="hidden" id="edit-review-note" value="0">
        </div>
        <div class="mb-3">
          <label class="form-label text-muted small fw-semibold">Reseña (opcional)</label>
          <textarea class="form-control bg-dark text-white border-secondary rounded-0" id="edit-review-text" rows="4" placeholder="Escribe tu opinión..."></textarea>
        </div>
        <!-- Pros -->
        <div class="mb-3 wrapper-pros">
            <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-plus-circle text-success me-1"></i> Ventajas</label>
            <select id="edit-pros-select" multiple>
                <option value="limpieza">Limpieza</option>
                <option value="personal">Personal / atención</option>
                <option value="comida">Comida y restaurantes</option>
                <option value="tematizacion">Tematización / ambiente</option>
                <option value="precio">Relación calidad-precio</option>
                <option value="colas">Gestión de colas</option>
                <option value="atracciones">Variedad de atracciones</option>
                <option value="mantenimiento">Mantenimiento de instalaciones</option>
                <option value="accesibilidad">Accesibilidad (discapacitados)</option>
                <option value="entretenimiento">Shows y entretenimiento</option>
                <option value="tiendas">Tiendas y merchandising</option>
            </select>
        </div>
        <!-- Contras -->
        <div class="mb-3 wrapper-contras">
            <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-minus-circle text-danger me-1"></i> Contras</label>
            <select id="edit-contras-select" multiple>
                <option value="suciedad">Suciedad</option>
                <option value="personal">Mal personal / atención</option>
                <option value="comida">Mala comida / precios abusivos</option>
                <option value="tematizacion">Poca tematización</option>
                <option value="precio">Mala relación calidad-precio</option>
                <option value="colas">Largas colas / mala gestión</option>
                <option value="pocas_atracciones">Pocas atracciones</option>
                <option value="mantenimiento">Mal mantenimiento</option>
                <option value="accesibilidad">Poca accesibilidad</option>
                <option value="entretenimiento">Falta de entretenimiento</option>
                <option value="masificacion">Masificación</option>
            </select>
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

<?php require_once __DIR__ . '/../../partials/footer.php';?>

<script>
  window.CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null' ?>;
  window.CURRENT_USERNAME = <?= isset($_SESSION['username']) ? json_encode($_SESSION['username']) : 'null' ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="<?= Router::asset('web/js/parks/parks.js') ?>?v=<?= time() ?>"></script>
