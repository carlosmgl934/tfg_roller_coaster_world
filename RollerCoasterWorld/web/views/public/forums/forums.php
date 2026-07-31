<?php
$page_css = ['web/css/forums.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$forumId = (int) ($_GET['id'] ?? 0);
if (!$forumId) {
    Router::redirect('forum_search');
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_rol'] ?? 'user';
$isAdmin = $userRole === 'admin';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foro — RollerCoaster World</title>
    <meta name="description" content="Chat del foro en RollerCoaster World">
</head>



<div class="forum-chat-wrapper" data-forum-id="<?= $forumId ?>" data-user-id="<?= htmlspecialchars($userId ?? '') ?>"
    data-is-admin="<?= $isAdmin ? 'true' : 'false' ?>" id="forum-chat-wrapper">

    <!-- ── HEADER DEL FORO ─────────────────────────────────────── -->
    <div class="forum-chat-header" id="forum-chat-header">
        <a href="<?= Router::url('forum_search') ?>" class="forum-back-btn flex-shrink-0" title="Volver a foros">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="forum-header-avatar flex-shrink-0" id="forum-header-avatar">
            <i class="fa-solid fa-comments"></i>
        </div>
        <div class="forum-header-title" id="forum-header-title">Cargando...</div>
        <div class="forum-header-right flex-shrink-0" id="forum-header-right">
            <!-- Botón panel owner (se inyecta por JS) -->
        </div>
    </div>

    <!-- ── ÁREA DE MENSAJES ────────────────────────────────────── -->
    <div class="forum-messages-area" id="forum-messages-area">
        <div id="forum-header-sub" class="text-center mb-4" style="color: #888; font-size: 0.85rem; padding: 0 10px;">
        </div>
        <div class="forum-loading" id="forum-loading">
            <span class="forum-spinner"></span> <span data-i18n="forums.thread.loading">Cargando mensajes...</span>
        </div>
        <div id="forum-messages-list"></div>
    </div>

    <!-- ── ESTADO: BANEADO / SIN PERMISO ──────────────────────── -->
    <div class="forum-banned-notice d-none" id="forum-banned-notice">
        <i class="fa-solid fa-ban fa-2x mb-2"></i>
        <p class="mb-0" data-i18n="forums.thread.banned">Estás baneado de este foro.</p>
    </div>

    <!-- ── INPUT ÁREA ─────────────────────────────────────────── -->
    <div class="forum-input-area" id="forum-input-area">
        <!-- ── REPLY PREVIEW ──────────────────────────────────────── -->
        <div class="forum-reply-preview d-none" id="forum-reply-preview">
            <div class="reply-preview-inner">
                <div class="reply-preview-bar"></div>
                <div class="reply-preview-text">
                    <span class="reply-preview-name" id="reply-preview-name"></span>
                    <span class="reply-preview-content" id="reply-preview-content"></span>
                </div>
                <button class="reply-preview-close" id="reply-preview-close" title="Cancelar respuesta">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- Attachment preview -->
        <div class="attach-preview d-none" id="attach-preview">
            <div class="attach-preview-inner">
                <img id="attach-preview-img" src="" alt="Preview" class="d-none">
                <span class="attach-file-icon d-none"><i class="fa-solid fa-file"></i></span>
                <span id="attach-preview-name" class="attach-preview-name"></span>
                <button id="attach-remove-btn" class="attach-remove-btn" title="Quitar adjunto">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="forum-input-row">
            <!-- Clip adjuntar -->
            <label class="forum-attach-btn" for="forum-file-input" title="Adjuntar imagen/archivo">
                <i class="fa-solid fa-paperclip"></i>
            </label>
            <input type="file" id="forum-file-input" accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar" class="d-none">

            <!-- Textarea -->
            <textarea id="forum-msg-input" class="forum-msg-textarea" placeholder="Escribe un mensaje..." rows="1"
                maxlength="2000"></textarea>

            <!-- Enviar -->
            <button id="forum-send-btn" class="forum-send-btn" title="Enviar mensaje">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </div>

        <!-- Rate-limit countdown -->
        <div class="forum-ratelimit d-none" id="forum-ratelimit">
            <i class="fa-solid fa-clock me-1"></i>
            Podrás enviar otro mensaje en <strong id="forum-ratelimit-seconds">0</strong>s
        </div>
    </div>
</div>

<!-- ── PANEL DE MODERACIÓN (owner/admin) ───────────────────── -->
<div class="modal fade" id="moderationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-shield-halved text-neon me-2"></i>Panel de Moderación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <!-- Lista de colaboradores -->
                <p class="form-section-title">Colaboradores del foro</p>

                <div class="mb-3 d-flex gap-2" id="invite-collab-wrapper" style="display: none !important;">
                    <select id="invite-collab-select" class="form-select bg-dark text-white border-secondary">
                        <option value="">Cargando amigos...</option>
                    </select>
                    <button class="btn btn-success text-white fw-bold px-3 d-flex align-items-center gap-1"
                        id="invite-collab-btn">
                        <i class="fa-solid fa-paper-plane"></i> <span class="d-none d-sm-inline">Invitar</span>
                    </button>
                </div>

                <div id="collaborators-list-container" class="mb-4">
                    <p class="text-muted small">Cargando...</p>
                </div>

                <!-- Lista de participantes -->
                <p class="form-section-title">Participantes del foro</p>
                <div id="participants-list-container" class="mb-4">
                    <p class="text-muted small">Cargando...</p>
                </div>
                <!-- Lista de baneados -->
                <p class="form-section-title">Usuarios baneados</p>
                <div id="banned-list-container">
                    <p class="text-muted small">Cargando...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL CONFIRMAR BORRAR MENSAJE ──────────────────────── -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="fa-solid fa-triangle-exclamation fa-2x text-warning mb-3"></i>
                <p>¿Borrar este mensaje?</p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger px-4" id="confirm-delete-btn">Borrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL CONFIRMAR BANEAR USUARIO ──────────────────── -->
<div class="modal fade" id="banModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="fa-solid fa-gavel fa-2x text-danger mb-3"></i>
                <p>¿Banear a <strong id="ban-user-name"></strong> de este foro?<br><small class="text-muted">No podrá
                        ver ni escribir mensajes.</small></p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-outline-secondary px-4 rounded-0 fw-bold border-1"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger px-4 rounded-0 fw-bold border-1" id="confirm-ban-btn">Banear</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL CONFIRMAR EXPULSAR COLABORADOR ──────────────────────── -->
<div class="modal fade" id="removeCollabModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="fa-solid fa-user-minus fa-2x text-warning mb-3"></i>
                <p>¿Expulsar a <strong id="remove-collab-name"></strong> de los colaboradores?</p>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger px-4" id="confirm-remove-collab-btn">Expulsar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL CONFIRMAR ELIMINAR FORO ────────────────────── -->
<div class="modal fade" id="deleteForumModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="fa-solid fa-trash-can fa-2x text-danger mb-3"></i>
                <p class="fw-bold mb-1">¿Eliminar este foro?</p>
                <small class="text-muted d-block mb-3">Se borrarán permanentemente todos los mensajes, colaboradores y
                    datos del foro. Esta acción no se puede deshacer.</small>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-outline-secondary px-4 rounded-0 fw-bold border-1"
                        data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-danger px-4 rounded-0 fw-bold border-1"
                        id="confirm-delete-forum-btn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL INFO FORO ────────────────────────────────────── -->
<div class="modal fade" id="forumInfoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content" style="background: #0d1117; border: 1px solid #198754; border-radius: 0;">
            <!-- Header -->
            <div class="modal-header border-0 px-4 pt-4 pb-2"
                style="border-bottom: 1px solid rgba(25,135,84,0.2) !important;">
                <div class="d-flex align-items-center gap-3 w-100">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width:42px; height:42px; background:rgba(25,135,84,0.25); border:1px solid #198754;">
                        <i class="fa-solid fa-circle-info" style="color:#198754; font-size:1.1rem;"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="modal-title fw-bold mb-0 text-white">Información del foro</h5>
                        <small style="color:#198754;" id="forumInfoModalAuthor"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body px-4 py-3">
                <!-- Título del foro -->
                <div class="mb-3">
                    <label class="text-muted text-uppercase fw-bold mb-1"
                        style="font-size:0.7rem; letter-spacing:0.06em;">
                        <i id="forumInfoModalPrivacyIcon" class="fa-solid fa-lock me-1" style="color:#198754;"></i>
                        <span id="forumInfoModalPrivacyText">Foro privado</span>
                    </label>
                    <div class="fw-bold text-white" id="forumInfoModalTitle"
                        style="font-size:1.05rem; word-break:break-word;"></div>
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label class="text-muted text-uppercase fw-bold mb-1"
                        style="font-size:0.7rem; letter-spacing:0.06em;">
                        <i class="fa-solid fa-align-left me-1" style="color:#198754;"></i> Descripción / Asunto
                    </label>
                    <p class="text-secondary mb-0 small" id="forumInfoModalDesc"></p>
                </div>

                <!-- Colaboradores (inyectado por JS si es privado) -->
                <div id="forumInfoModalCollabs" class="mb-3" style="display: none;">
                    <label class="text-muted text-uppercase fw-bold mb-1"
                        style="font-size:0.7rem; letter-spacing:0.06em;">
                        <i class="fa-solid fa-users me-1" style="color:#198754;"></i> Colaboradores
                    </label>
                    <div id="forumInfoModalCollabsList" class="d-flex flex-wrap gap-2 mt-1"></div>
                </div>



                <!-- Detalles extras (members, created) -->
                <div class="d-flex gap-3 mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.06);">
                    <div class="text-center flex-fill">
                        <div class="fw-bold text-white" id="forumInfoModalMembers">—</div>
                        <small class="text-muted" style="font-size:0.7rem;">Miembros</small>
                    </div>
                    <div class="text-center flex-fill">
                        <div class="fw-bold text-white" id="forumInfoModalCreated">—</div>
                        <small class="text-muted" style="font-size:0.7rem;">Creación</small>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 px-4 pb-4 pt-2 gap-2"
                style="border-top:1px solid rgba(25,135,84,0.15) !important;">
                <button type="button" class="btn btn-outline-success w-100 fw-bold"
                    data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ── JS ──────────────────────────────────────────────────── -->
<script>
    window.FORUM_ID = <?= $forumId ?>;
    window.CURRENT_USER = <?= json_encode($userId) ?>;
    window.IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
    <?php require_once __DIR__ . '/../../../../api/database/db_conexion.php'; ?>
    window.SUPABASE_URL = '<?= $_ENV['SUPABASE_URL'] ?? '' ?>';
    window.SUPABASE_KEY = '<?= $_ENV['SUPABASE_ANON_KEY'] ?? '' ?>';
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<style>
    /* Ocultar el footer general solo en esta vista para dejar espacio al chat fixed */
    footer {
        display: none !important;
    }

    /* Bloquear el scroll global para que funcione exactamente como una app nativa */
    body {
        overflow: hidden !important;
        height: 100dvh;
        width: 100vw;
    }
</style>

<script src="<?= Router::asset('web/js/forums/forum_chat.js') ?>?v=<?= time() ?>"></script>