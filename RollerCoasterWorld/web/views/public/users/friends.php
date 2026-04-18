<?php
$page_css = ['web/css/profile.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */
/** @var bool $is_logged */

// Solo logueados
if (!$is_logged) {
    Router::redirect('login');
}
?>
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

                    <div style="max-height: 350px; overflow-y: auto; overflow-x: hidden;" class="rcw-custom-scrollbar pe-2">
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

    <!-- Modal Info Foro (invitación colaborador) -->
    <div class="modal fade" id="forumInviteInfoModal" tabindex="-1" aria-labelledby="forumInviteInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content" style="background: #0d1117; border: 1px solid #a78bfa; border-radius: 0;">
          <!-- Header morado -->
          <div class="modal-header border-0 px-4 pt-4 pb-2" style="border-bottom: 1px solid rgba(167,139,250,0.2) !important;">
            <div class="d-flex align-items-center gap-3 w-100">
              <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                   style="width:42px; height:42px; background:rgba(109,40,217,0.25); border:1px solid #a78bfa;">
                <i class="fa-solid fa-comments" style="color:#a78bfa; font-size:1.1rem;"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <h5 class="modal-title fw-bold mb-0 text-white" id="forumInviteInfoModalLabel">
                  Invitación a colaborar
                </h5>
                <small style="color:#a78bfa;" id="forumInviteModalSender"></small>
              </div>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
          </div>

          <!-- Body -->
          <div class="modal-body px-4 py-3">
            <!-- Título del foro -->
            <div class="mb-3">
              <label class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.7rem; letter-spacing:0.06em;">
                <i class="fa-solid fa-lock me-1" style="color:#a78bfa;"></i> Foro privado
              </label>
              <div class="fw-bold text-white" id="forumInviteModalTitle" style="font-size:1.05rem; word-break:break-word;"></div>
            </div>

            <!-- Descripción -->
            <div class="mb-3" id="forumInviteModalDescWrap">
              <label class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.7rem; letter-spacing:0.06em;">
                <i class="fa-solid fa-align-left me-1" style="color:#a78bfa;"></i> Descripción
              </label>
              <p class="text-secondary mb-0 small" id="forumInviteModalDesc"></p>
            </div>

            <!-- Detalles extras (members, created) -->
            <div class="d-flex gap-3 mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.06);">
              <div class="text-center flex-fill">
                <div class="fw-bold text-white" id="forumInviteModalMembers">—</div>
                <small class="text-muted" style="font-size:0.7rem;">Miembros</small>
              </div>
              <div class="text-center flex-fill">
                <div class="fw-bold text-white" id="forumInviteModalCreated">—</div>
                <small class="text-muted" style="font-size:0.7rem;">Creación</small>
              </div>
            </div>
          </div>

          <!-- Footer con acciones -->
          <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2" style="border-top:1px solid rgba(167,139,250,0.15) !important;">
            <button type="button" class="btn btn-outline-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn px-4 fw-bold flex-fill rcw-forum-invite-modal-action"
                    style="background:#7c3aed; color:#fff; border:none;"
                    data-action="decline" id="forumInviteModalDeclineBtn">
              <i class="fa-solid fa-xmark me-1"></i> Rechazar
            </button>
            <button type="button" class="btn px-4 fw-bold flex-fill rcw-forum-invite-modal-action"
                    style="background:#16a34a; color:#fff; border:none;"
                    data-action="accept" id="forumInviteModalAcceptBtn">
              <i class="fa-solid fa-check me-1"></i> Aceptar
            </button>
          </div>
        </div>
      </div>
    </div>
</main>

<script src="<?= Router::asset('web/js/social/friends_manager.js') ?>?v=<?= time() ?>"></script>



<?php require_once __DIR__ . '/../../partials/footer.php'; ?>