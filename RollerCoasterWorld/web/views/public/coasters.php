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

    <!-- HERO: Imagen + Info principal -->
    <div class="row g-4 mb-5">

        <!-- Imagen -->
        <div class="col-12 col-lg-7">
            <img src="https://placehold.co/900x500" alt="Nombre coaster" class="img-fluid rounded shadow w-100"
                style="max-height: 450px; object-fit: cover;">
        </div>

        <!-- Info principal -->
        <div class="col-12 col-lg-5">
            <h1 class="fw-bold text-success" id="coaster-name">Cargando...</h1>

            <div class="d-flex align-items-center text-muted mb-3">
                <a href="#" class="text-muted text-decoration-none" id="park-link">
                    <i class="fa-solid fa-map-pin me-1"></i>
                    <span id="park-name">Cargando Parque...</span>
                </a>
                <span class="mx-1">•</span>
                <span id="coaster-country" class="fw-bold text-dark">País</span>
            </div>

            <hr>

            <div class="row g-3 mt-1">
                <div class="col-6">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body p-2">
                            <div class="text-muted small">Ranking Global</div>
                            <div class="fw-bold fs-4 text-success" id="global-ranking">N/A</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body p-2">
                            <div class="text-muted small">Puntuación</div>
                            <div class="fw-bold fs-4 text-success" id="coaster-score">N/A</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body p-2">
                            <div class="text-muted small">Tu ranking</div>
                            <div class="fw-bold fs-4 text-success" id="pesonal-ranking">N/A</div>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card text-center shadow-sm border-0">
                        <div class="card-body p-2">
                            <div class="text-muted small">Estado</div>
                            <div class="fw-bold fs-4 text-success" id="current-state">N/A</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button id="btn-ridden" class="btn btn-outline-success fw-bold flex-grow-1">
                    <i class="fa-regular fa-check me-2" id="coaster-ridden"></i>Montada
                </button>
                <button id="btn-share" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-share-nodes"></i>
                </button>
                <button id="btn-favorite" class="btn btn-outline-success">
                    <i class="fa-regular fa-star" id="fav-icon"></i>
                </button>
            </div>
        </div>

        <!-- ESTADÍSTICAS + FICHA TÉCNICA -->
        <div class="row g-4 mb-5">

            <!-- Estadísticas -->
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fa-solid fa-chart-bar me-2"></i>Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 text-center">
                            <div class="col-6">
                                <div class="text-muted small">Altura</div>
                                <div class="fw-bold fs-5" id="coaster-height">N/A</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Velocidad</div>
                                <div class="fw-bold fs-5" id="coaster-speed">N/A</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Longitud</div>
                                <div class="fw-bold fs-5" id="coaster-length">N/A</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Inversiones</div>
                                <div class="fw-bold fs-5" id="coaster-inversions">0</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ficha técnica -->
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fa-solid fa-clipboard-list me-2"></i>Ficha Técnica</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted">Fabricante</td>
                                    <td class="fw-bold" id="coaster-manufacter">Desconocido</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Modelo</td>
                                    <td class="fw-bold" id="coaster-model">Desconocido</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Año apertura</td>
                                    <td class="fw-bold" id="coaster-year">Desconocido</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Estado</td>
                                    <td class="fw-bold" id="current-state-table">N/A</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Parque</td>
                                    <td class="fw-bold">
                                        <a href="<?= $base_url ?>/web/views/public/park_detail.php?id=<?= $id ?>"
                                            id="park-name-table">
                                            Desconocido
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOTOS -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa-solid fa-images me-2"></i>Fotos <span
                                class="badge bg-white text-success ms-1">157</span></h5>
                        <button class="btn btn-sm btn-white border-white text-white">
                            <i class="fa-solid fa-upload me-1"></i>Subir foto
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php for ($i = 0; $i < 8; $i++): ?>
                                <div class="col-6 col-md-3">
                                    <img src="https://placehold.co/400x300" class="img-fluid rounded shadow-sm w-100"
                                        style="height: 180px; object-fit: cover;">
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="#" class="text-success fw-bold">Ver todas las fotos</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VALORACIONES -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fa-solid fa-star me-2"></i>Reseñas <span
                                class="badge bg-white text-success ms-1">3783</span></h5>
                        <div class="d-flex gap-2 align-items-center">
                            <select class="form-select form-select-sm">
                                <option>Ordenar por defecto</option>
                                <option>Más recientes</option>
                                <option>Mejor valoración</option>
                                <option>Peor valoración</option>
                            </select>
                            <button class="btn btn-sm btn-white border-white text-white text-nowrap">
                                <i class="fa-solid fa-pen me-1"></i>Enviar opinión
                            </button>
                        </div>
                    </div>
                    <div class="card-body" id="reviews-list">

                        <!-- Review ejemplo -->
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <img src="https://placehold.co/40x40" class="rounded-circle">
                                <strong>Nombre Usuario</strong>
                                <span class="text-warning">★★★★★</span>
                                <span class="text-muted small">hace 2 meses</span>
                            </div>
                            <p class="mb-0">Texto de la reseña aquí...</p>
                        </div>

                    </div>
                </div>
            </div>
        </div>

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>