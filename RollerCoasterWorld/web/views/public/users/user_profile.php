<?php
$page_css = ['web/css/profile.css', 'web/css/trips.css'];
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/modals/trip_modals.php';
/** @var string $base_url */

// Perfil público de otro usuario — no requiere login
$user_id = $_GET['id'] ?? null;
?>
<main class="container-fluid px-3 px-lg-5 my-5" id="profile-content" style="display:none;">

    <!-- Título Principal -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success">
                <i class="fa-solid fa-user me-2"></i> Perfil de Viajero
            </h1>
        </div>
    </div>

    <!-- Layout en 2 Columnas Estilo Profile -->
    <div class="row g-4 mb-4">

        <!-- Columna Izquierda: Perfil y Menú Lateral -->
        <div class="col-lg-4 col-md-5">
            <!-- Tarjeta de Perfil Principal -->
            <div class="card profile-card text-center mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-0">
                        <div class="position-relative d-inline-block me-3">
                            <div class="avatar-circle shadow-sm" id="user-avatar"
                                style="width: 80px; height: 80px; font-size: 36px;">?</div>
                        </div>
                        <div class="text-start overflow-hidden">
                            <h5 class="card-title fw-bold mb-1 text-truncate text-success" id="user-username">---</h5>
                            <p class="text-muted small mb-0 text-truncate" id="user-location"><i
                                    class="fa-solid fa-location-dot me-1"></i><span>Ubicación desconocida</span></p>
                            <p class="text-muted small mt-1 mb-0"><i class="fa-regular fa-calendar me-1"></i> <span
                                    id="user-joined">---</span></p>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                        <div id="friendship-action-container" class="mb-2"></div>
                    </div>
                </div>
            </div>

            <!-- Menú Lateral -->
            <div class="card profile-card mb-4">
                <div class="list-group list-group-flush profile-menu" id="sidebar-menu">
                    <a href="#" id="menu-profile"
                        class="list-group-item list-group-item-action py-3 active fw-medium"><i
                            class="fa-solid fa-user me-2 w-20px text-center"></i> Perfil</a>
                    <a href="#" id="menu-tops" class="list-group-item list-group-item-action py-3"><i
                            class="fa-solid fa-list-ol me-2 w-20px text-center"></i> Sus tops</a>
                    <a href="#" id="menu-photos" class="list-group-item list-group-item-action py-3"><i
                            class="fa-solid fa-images me-2 w-20px text-center"></i> Sus fotos</a>
                    <a href="#" id="menu-reviews" class="list-group-item list-group-item-action py-3"><i
                            class="fa-solid fa-star-half-stroke me-2 w-20px text-center"></i> Sus Reseñas</a>
                    <a href="#" id="menu-friends" class="list-group-item list-group-item-action py-3"><i
                            class="fa-solid fa-user-group me-2 w-20px text-center"></i> Sus Amigos</a>
                    <a href="#" id="menu-trips" class="list-group-item list-group-item-action py-3"><i
                            class="fa-solid fa-suitcase-rolling me-2 w-20px text-center"></i> Sus Viajes</a>
                    <a href="#" id="menu-ranking" class="list-group-item list-group-item-action py-3"><i
                            class="fa-solid fa-chart-line me-2 w-20px text-center"></i> Sus Estadísticas</a>
                    <a href="<?= Router::url('trip_generator') ?>"
                        class="list-group-item list-group-item-action text-success mt-1 py-3 border-top fw-bold"><i
                            class="fa-solid fa-wand-magic-sparkles me-2 w-20px text-center"></i> Organizar un viaje
                        juntos</a>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Contenido Dinámico -->
        <div class="col-lg-8 col-md-7" id="section-profile-content">

            <!-- TAB 1: Información del Usuario (Stats, Favoritos) -->
            <div class="content-section" id="section-info">
                <!-- Favoritos -->
                <div class="card profile-card mb-4">
                    <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-heart fs-5 text-success"></i>
                        <h5 class="fw-bold mb-0">Favoritos</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <div class="p-3 fav-box text-center square-box h-100">
                                    <div class="fav-icon text-success mb-2"><i class="fa-solid fa-star fs-3"></i></div>
                                    <p class="text-muted mb-1 small fw-bold text-uppercase"
                                        style="letter-spacing: 0.5px; font-size: 0.75rem;">Coaster Favorita</p>
                                    <p class="fw-bold mb-0 text-truncate text-light fs-5" id="user-fav-coaster">—</p>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 fav-box text-center square-box h-100">
                                    <div class="fav-icon text-success mb-2"><i class="fa-solid fa-map-pin fs-3"></i>
                                    </div>
                                    <p class="text-muted mb-1 small fw-bold text-uppercase"
                                        style="letter-spacing: 0.5px; font-size: 0.75rem;">Parque Favorito</p>
                                    <p class="fw-bold mb-0 text-truncate text-light fs-5" id="user-top-park">—</p>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 fav-box text-center square-box h-100">
                                    <div class="fav-icon text-success mb-2"><i class="fa-solid fa-house fs-3"></i></div>
                                    <p class="text-muted mb-1 small fw-bold text-uppercase"
                                        style="letter-spacing: 0.5px; font-size: 0.75rem;">Home Park</p>
                                    <p class="fw-bold mb-0 text-truncate text-light fs-5" id="user-home-park">—</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Generales -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="card profile-card h-100">
                            <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-ticket fs-5 text-warning"></i>
                                <h5 class="fw-bold mb-0">Estadísticas Generales</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush bg-transparent">
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 bg-transparent">
                                        <span class="text-muted fw-medium"><i
                                                class="fa-solid fa-bolt me-2 text-success"></i>Montañas rusas</span>
                                        <span class="badge badge-profile fs-6 bg-success" id="stat-coasters">0</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top border-secondary border-opacity-25 bg-transparent">
                                        <span class="text-muted fw-medium"><i
                                                class="fa-solid fa-map-location-dot me-2 text-success"></i>Parques
                                            visitados</span>
                                        <span class="badge badge-profile fs-6 bg-success" id="stat-parks">0</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top border-secondary border-opacity-25 bg-transparent">
                                        <span class="text-muted fw-medium"><i
                                                class="fa-solid fa-earth-americas me-2 text-success"></i>Países</span>
                                        <span class="badge badge-profile fs-6 bg-success" id="stat-countries">0</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top border-secondary border-opacity-25 bg-transparent">
                                        <span class="text-muted fw-medium"><i
                                                class="fa-solid fa-user-group me-2 text-info"></i>Amigos</span>
                                        <span class="badge badge-profile fs-6 bg-success" id="stat-friends">0</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top border-secondary border-opacity-25 bg-transparent">
                                        <span class="text-muted fw-medium"><i
                                                class="fa-solid fa-camera-retro me-2 text-primary"></i>Fotos
                                            compartidas</span>
                                        <span class="badge badge-profile fs-6 bg-success" id="stat-photos">0</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Técnicas -->
                    <div class="col-md-6">
                        <div class="card profile-card h-100">
                            <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
                                <i class="fa-solid fa-chart-pie fs-5 text-info"></i>
                                <h5 class="fw-bold mb-0">Estadísticas Técnicas</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush bg-transparent">
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 bg-transparent">
                                        <span class="text-muted">País más visitado</span>
                                        <span class="fw-bold text-end text-wrap ms-3" id="stat-tech-country"
                                            style="max-width: 60%;">—</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 bg-transparent">
                                        <span class="text-muted">Fabricante favorito</span>
                                        <span class="fw-bold text-end text-wrap ms-3" id="stat-tech-manufacturer"
                                            style="max-width: 60%;">—</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 border-bottom border-secondary border-opacity-25 bg-transparent mb-2">
                                        <span class="text-muted">Fabricantes totales</span>
                                        <span class="fw-bold text-end ms-3" id="stat-tech-total-manu">0</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 bg-transparent mt-2">
                                        <span class="text-muted">Altura total superada</span>
                                        <span class="fw-bold text-end ms-3"><span id="stat-tech-height">0</span>
                                            m</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 bg-transparent">
                                        <span class="text-muted">Inversiones totales</span>
                                        <span class="fw-bold text-end ms-3" id="stat-tech-inversions">0</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 bg-transparent">
                                        <span class="text-muted">Más rápida</span>
                                        <span class="fw-bold text-end text-wrap ms-3" id="stat-tech-speed"
                                            style="max-width: 60%;">—</span>
                                    </li>
                                    <li
                                        class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 bg-transparent">
                                        <span class="text-muted">Más larga</span>
                                        <span class="fw-bold text-end text-wrap ms-3" id="stat-tech-length"
                                            style="max-width: 60%;">—</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Sus Tops -->
            <div class="content-section" id="section-tops" style="display:none;">
                <div class="card profile-card mb-4">
                    <div class="card-header pt-3 pb-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-ranking-star fs-5 text-warning"></i>
                            <h5 class="fw-bold mb-0">Ranking Personal</h5>
                        </div>
                        <select id="tops-type-selector"
                            class="form-select w-auto fw-bold rcw-stats-select">
                            <option value="coasters">Tops Coasters</option>
                            <option value="parks">Tops Parques</option>
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush bg-transparent tops-preview-scroll"
                            id="tops-list-container">
                            <!-- Inyectado via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Sus Fotos -->
            <div class="content-section" id="section-photos" style="display:none;">
                <div class="card profile-card mb-4">
                    <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-camera fs-5 text-primary"></i>
                        <h5 class="fw-bold mb-0">Galería de Fotos</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="tops-preview-scroll">
                            <div class="row g-3" id="photos-grid-container">
                                <!-- Inyectado via JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Mis Amigos -->
            <div class="content-section" id="section-friends" style="display:none;">
                <div class="card profile-card mb-4">
                    <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-group fs-5 text-success"></i>
                        <h5 class="fw-bold mb-0">Amigos de <span id="friends-section-username">este usuario</span></h5>
                        <span class="badge badge-profile ms-auto" id="friends-section-count">0</span>
                    </div>
                    <div class="card-body p-0">
                        <div id="friends-list-container">
                            <!-- Inyectado via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 5: Sus Reseñas -->
            <div class="content-section" id="section-reviews" style="display:none;">
                <div class="card profile-card mb-4">
                    <div
                        class="card-header pt-3 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-star-half-stroke fs-5 text-warning"></i>
                            <h5 class="fw-bold mb-0">Reseñas de este usuario</h5>
                        </div>
                        <select id="reviews-sort-selector"
                            class="form-select w-auto fw-bold bg-dark text-white border-success border-opacity-50 rounded-0"
                            style="font-size: 0.9rem;">
                            <option value="newest">Más recientes</option>
                            <option value="oldest">Más antiguas</option>
                            <option value="highest">Mejor valoradas</option>
                            <option value="lowest">Peor valoradas</option>
                        </select>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush bg-transparent tops-preview-scroll"
                            id="reviews-list-container">
                            <!-- Inyectado via JS -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6: Viajes -->
            <div class="content-section" id="section-trips" style="display:none;">
                <div class="card profile-card mb-4">
                    <div class="card-header pt-3 pb-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-suitcase-rolling fs-5 text-success"></i>
                            <h5 class="fw-bold mb-0">Viajes de este usuario</h5>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div id="trips-grid" class="trips-grid-scrollable">
                            <div class="text-center py-4 text-muted small">Cargando viajes...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 7: Ranking / Estadísticas de Viajes -->
            <div class="content-section" id="section-ranking" style="display:none;">
                <div class="card profile-card mb-4">
                    <div class="card-header pt-3 pb-3 d-flex flex-column gap-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-chart-line fs-5 text-success"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-0" style="font-size: 1.1rem;">Estadísticas de</h5>
                                    <div class="stats-type-wrapper" style="cursor: pointer; margin-top: 2px; width: fit-content;">
                                        <select id="rank-type-select" class="rcw-stats-select">
                                            <option value="coasters">Coasters</option>
                                            <option value="parks">Parques</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="stats-trip-badge shadow-sm">
                                    <i class="fa-solid fa-suitcase text-success"></i> <span id="rank-trip-count">0 viajes</span>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex overflow-x-auto gap-1 pb-1" id="rank-filter-btns"
                            style="scrollbar-width: none;">
                            <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                                data-period="week">Semana</button>
                            <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                                data-period="month">Mes</button>
                            <button
                                class="btn btn-sm rounded-0 rank-period-btn btn-outline-success active flex-shrink-0"
                                data-period="year">Año</button>
                            <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                                data-period="custom">Personalizado</button>
                            <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                                data-period="all">Siempre</button>
                        </div>
                        <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-1 pt-2 border-top border-secondary">
                            <div class="d-flex align-items-center gap-2" id="rank-nav-container">
                                <button class="btn btn-sm btn-outline-secondary rounded-0" id="rank-prev-btn"
                                    title="Anterior"><i class="fa-solid fa-chevron-left"></i></button>
                                <span id="rank-nav-label" class="fw-bold text-center"
                                    style="min-width: 100px;">2026</span>
                                <button class="btn btn-sm btn-outline-secondary rounded-0" id="rank-next-btn"
                                    title="Siguiente"><i class="fa-solid fa-chevron-right"></i></button>
                            </div>
                            <div class="d-flex align-items-center gap-2 ms-auto">
                                <small class="text-muted">Desde:</small>
                                <input type="date"
                                    class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white"
                                    id="rank-start-date" style="max-width: 120px;">
                                <small class="text-muted">Hasta:</small>
                                <input type="date"
                                    class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white"
                                    id="rank-end-date" style="max-width: 120px;">
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0" id="ranking-container">
                        <div class="text-center py-4 text-muted small">Cargando estadísticas...</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal Confirmar Eliminar Amigo (desde perfil público) -->
    <div class="modal fade" id="removeProfileFriendModal" tabindex="-1" aria-labelledby="removeProfileFriendModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
            <div class="modal-content" style="background: #141e2a; border: 1px solid rgba(255,255,255,0.08);">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-danger"></i>
                        <h6 class="modal-title fw-bold text-white mb-0" id="removeProfileFriendModalLabel">Eliminar
                            Amigo</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <p class="text-muted mb-1">¿Seguro que quieres eliminar de tus amigos a <strong class="text-white"
                            id="removeProfileFriendName"></strong>?</p>
                    <small class="text-muted opacity-75">Esta acción no se puede deshacer.</small>
                </div>
                <div class="modal-footer border-0 pt-0 gap-2">
                    <button type="button" class="btn btn-sm btn-dark px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-danger px-4 fw-bold"
                        id="confirmRemoveProfileFriendBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ig-lightbox-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
            <div class="modal-content bg-dark text-white border-secondary rounded-0 overflow-visible position-relative">
                <button id="ig-modal-prev"
                    class="btn text-white position-absolute top-50 translate-middle-y rounded-circle px-3 py-2"
                    style="z-index: 1055; left: -60px; font-size: 1.5rem; background: rgba(0,0,0,0.5);"><i
                        class="fa-solid fa-chevron-left"></i></button>
                <button id="ig-modal-next"
                    class="btn text-white position-absolute top-50 translate-middle-y rounded-circle px-3 py-2"
                    style="z-index: 1055; right: -60px; font-size: 1.5rem; background: rgba(0,0,0,0.5);"><i
                        class="fa-solid fa-chevron-right"></i></button>
                <div class="modal-header border-secondary d-flex align-items-center py-2 px-3">
                    <img id="ig-modal-avatar" src="" alt="Avatar" class="rounded-circle me-2"
                        style="width:32px; height:32px; object-fit:cover;">
                    <div id="ig-modal-avatar-fallback"
                        class="d-flex align-items-center justify-content-center text-secondary bg-dark rounded-circle me-2"
                        style="width:32px;height:32px;display:none !important;"><i class="fa-solid fa-user"></i></div>
                    <span id="ig-modal-username" class="fw-bold fs-6"></span>
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <img id="ig-modal-img" src="" alt="Foto" class="w-100" style="aspect-ratio: 1/1; object-fit:cover;">
                </div>
                <!-- Removemos los likes, dejamos solo el caption si es necesario o eliminamos todo el footer si solo quiere la foto -->
                <div class="modal-footer border-secondary flex-column align-items-start py-3 px-3">
                    <div class="w-100">
                        <span id="ig-modal-caption-user" class="fw-bold text-success me-2"></span>
                        <span id="ig-modal-caption" class="text-light"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

</main>

<!-- Loading -->
<div id="profile-loading" class="text-center py-5">
    <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;"></div>
    <p class="mt-3 text-muted fw-bold text-uppercase" style="letter-spacing: 0.05em;">Cargando perfil...</p>
</div>

<script src="<?= Router::asset('web/js/components/trip_modals.js') ?>?v=<?= time() ?>"></script>
<script src="<?= Router::asset('web/js/users/user_profile.js') ?>?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>