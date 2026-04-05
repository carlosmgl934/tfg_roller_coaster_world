<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
/** @var bool $is_logged */

// Solo logueados
if (!$is_logged) {
    Router::redirect('login');
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/profile.css">

<main class="container-fluid px-3 px-lg-5 my-5">

    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success">
                <i class="fa-solid fa-user-group me-2"></i> Gestionar Amistades
            </h1>
            <p class="text-muted text-uppercase fw-bold mt-3" style="letter-spacing: 0.1em; font-size: 0.85rem;">
                Gestiona tus peticiones y descubre los tops de tus amigos
            </p>
        </div>
    </div>

    <!-- Contenedor principal de amistades -->
    <div class="row g-4" id="friends-container" style="display: none;">

        <!-- Peticiones Pendientes (Izquierda) -->
        <div class="col-12 col-lg-4">
            <div class="card profile-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bell text-warning"></i>
                        <h5 class="mb-0 fw-bold">Solicitudes</h5>
                    </div>
                    <span class="badge badge-profile-gray" id="requests-count">0</span>
                </div>
                <!-- Body sin padding para que la list-group llene la tarjeta -->
                <div class="card-body p-0">
                    <div id="requests-list" class="list-group list-group-flush border-0">
                        <!-- Peticiones inyectadas via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis Amigos (Derecha) -->
        <div class="col-12 col-lg-8">
            <div class="card profile-card h-100">
                <div
                    class="card-header d-flex justify-content-between align-items-center py-3 border-success border-opacity-25">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-users text-success"></i>
                        <h5 class="mb-0 fw-bold">Lista de Amigos</h5>
                    </div>
                    <span class="badge badge-profile" id="friends-count">0</span>
                </div>
                <div class="card-body p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-md-6 mb-3 mb-md-0">
                            <div class="input-group">
                                <input type="text" class="form-control square-box border-success" id="search-friends-input" placeholder="Buscar amigo..." style="background-color: var(--rcw-bg-input);">
                                <span class="input-group-text bg-success text-white border-success rounded-0 px-3"><i class="fa-solid fa-magnifying-glass"></i></span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 text-md-end text-start">
                            <label for="sort-friends-select" class="visually-hidden">Ordenar por</label>
                            <select class="form-select square-box d-inline-block w-auto" id="sort-friends-select">
                                <option value="antiguedad_desc">Más recientes</option>
                                <option value="antiguedad_asc">Más antiguos</option>
                                <option value="alfabetico_asc">Alfabéticamente (A-Z)</option>
                                <option value="alfabetico_desc">Alfabéticamente (Z-A)</option>
                                <option value="credits_desc">Número de credits</option>
                            </select>
                        </div>
                    </div>

                    <div style="max-height: 480px; overflow-y: auto; overflow-x: hidden;" class="rcw-custom-scrollbar pe-2">
                        <div class="row g-0" id="friends-list">
                            <!-- Amigos inyectados via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peticiones enviadas -->
    <div class="row mt-4" id="sent-requests-container" style="display: none;">
        <div class="col-12">
            <div class="card profile-card">
                <div class="accordion accordion-flush" id="accordionSent">
                    <div class="accordion-item bg-transparent border-0">
                        <h2 class="accordion-header">
                            <button
                                class="accordion-button collapsed bg-transparent text-white shadow-none px-4 py-3 fw-bold"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseSent">
                                <i class="fa-solid fa-paper-plane text-muted me-2"></i>
                                Solicitudes enviadas pendientes (<span id="sent-count"
                                    class="text-success ms-1">0</span>)
                            </button>
                        </h2>
                        <div id="collapseSent" class="accordion-collapse collapse" data-bs-parent="#accordionSent">
                            <div class="accordion-body p-0 border-top border-secondary border-opacity-25">
                                <ul class="list-group list-group-flush border-0" id="sent-list">
                                    <!-- Enviadas inyectadas via JS -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="row" id="friends-loading">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;"></div>
            <p class="mt-3 text-muted fw-bold text-uppercase" style="letter-spacing: 0.05em;">Cargando red de
                aventureros...</p>
        </div>
    </div>

    <!-- Modal Eliminar Amigo -->
    <div class="modal fade" id="removeFriendModal" tabindex="-1" aria-labelledby="removeFriendModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-bottom border-secondary border-opacity-25 pb-3">
            <h5 class="modal-title fw-bold text-danger" id="removeFriendModalLabel"><i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar Amigo</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-4">
            ¿Estás seguro de que deseas eliminar de tus amigos a <strong id="removeFriendName" class="text-white"></strong>?<br>
            <span class="text-muted small">Esta acción no se puede deshacer.</span>
          </div>
          <div class="modal-footer border-top border-secondary border-opacity-25 pt-3">
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-danger px-4 fw-bold" id="confirmRemoveFriendBtn">Eliminar</button>
          </div>
        </div>
      </div>
    </div>
</main>

<script src="<?= Router::asset('web/js/social/friends_manager.js') ?>"></script>

<!-- Add some quick inline styles to handle list-group inside dark cards properly if not fully covered globally -->
<style>
    /* Ajustes para listas dentro de las nuevas profile-cards para mantener estética oscura */
    #requests-list .list-group-item,
    #sent-list .list-group-item {
        background-color: transparent;
        border-color: var(--rcw-border);
        transition: background-color 0.2s ease;
    }

    #requests-list .list-group-item:hover,
    #sent-list .list-group-item:hover {
        background-color: var(--rcw-bg-hover);
    }

    .accordion-button:not(.collapsed)::after {
        filter: brightness(0) invert(1);
    }

    .accordion-button::after {
        filter: brightness(0) invert(0.7);
    }
</style>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>