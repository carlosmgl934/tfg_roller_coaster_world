<?php
$page_css = ['web/css/forums.css'];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$forumId = $_GET['forum_id'] ?? null;
$privacy = $_POST['privacy'] ?? 'public';
$is_logged = isset($_SESSION['user_id']);
?>
<main class="container py-5" style="max-width: 680px;" data-logged="<?= $is_logged ? 'true' : 'false' ?>"
    id="forum-main-container">

    <!-- Cabecera centrada -->
    <div class="text-center mb-4">
        <span class="forum-page-icon"><i class="fa-solid fa-comments"></i></span>
        <h2 class="fw-800 mt-3 mb-1" data-i18n="forums.config.create_title">Configuración del Foro</h2>
        <p class="text-muted small" data-i18n="forums.config.subtitle">Define el nombre, descripción y privacidad de tu
            nuevo foro.</p>
    </div>

    <div class="forum-form-card card">
        <div class="card-body p-4 p-md-5">
            <form id="forum-form" action="#">

                <input type="hidden" name="user_id" value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
                <input type="hidden" name="forum_id" value="<?= htmlspecialchars($forumId ?? '') ?>">

                <!-- SECCIÓN: Identidad -->
                <p class="form-section-title">Personaliza tu foro</p>

                <div class="mb-4">
                    <label for="title" class="form-label" data-i18n="forums.config.forum_title">Nombre del foro</label>
                    <input type="text" name="title" id="title" class="form-control form-control-lg"
                        data-i18n-placeholder="forums.config.title_placeholder"
                        placeholder="Ej: Mejores montañas rusas 2026" required>
                    <div id="title-error" class="invalid-feedback">El título debe tener al menos 5 caracteres.</div>
                </div>

                <div class="mb-5">
                    <label for="form_subject" class="form-label"
                        data-i18n="forums.config.description">Descripción</label>
                    <textarea name="form_subject" id="form_subject" class="form-control" rows="4" maxlength="255"
                        data-i18n-placeholder="forums.config.description_placeholder"
                        placeholder="Describe brevemente de qué trata tu foro..." required></textarea>
                </div>

                <!-- SECCIÓN: Visibilidad -->
                <p class="form-section-title">Visibilidad</p>

                <div class="mb-2">
                    <div class="privacy-toggle" role="group">
                        <input type="radio" name="privacy" value="public" id="privacy-public" class="privacy-radio"
                            <?= $privacy !== 'private' ? 'checked' : '' ?>>
                        <label for="privacy-public" class="privacy-btn">
                            <i class="fa-solid fa-earth-europe me-2"></i><span
                                data-i18n="forums.config.public">Público</span>
                        </label>

                        <input type="radio" name="privacy" value="private" id="privacy-private" class="privacy-radio"
                            <?= $privacy === 'private' ? 'checked' : '' ?>>
                        <label for="privacy-private" class="privacy-btn">
                            <i class="fa-solid fa-lock me-2"></i><span data-i18n="forums.config.private">Privado</span>
                        </label>
                    </div>
                    <p class="privacy-hint" id="privacy-hint">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <span
                            id="privacy-hint-text"><?= $privacy === 'private' ? 'Solo los colaboradores que designes pueden escribir, pero cualquiera puede leer el foro' : 'Cualquier usuario puede ver y escribir en el foro' ?></span>
                    </p>
                </div>

                <div class="mb-5"></div>

                <!-- SECCIÓN: Colaboradores -->
                <div id="collaborators-section">
                    <p class="form-section-title">Colaboradores</p>

                    <div class="mb-5">
                        <label class="form-label">
                            <span data-i18n="forums.config.collaborators_label">Selecciona colaboradores entre tus
                                amigos</span>
                            <span class="text-muted fw-normal ms-1">(opcional)</span>
                        </label>

                        <!-- Widget selector de amigos (custom, sin dependencias externas) -->
                        <div id="friend-picker" class="friend-picker">
                            <!-- Tags de amigos seleccionados -->
                            <div id="friend-tags" class="friend-tags">
                                <input type="text" id="friend-search-input" class="friend-search-input"
                                    placeholder="Busca un amigo..." autocomplete="off">
                            </div>
                            <!-- Dropdown con la lista -->
                            <div id="friend-dropdown" class="friend-dropdown" style="display:none;">
                                <div id="friend-list" class="friend-list">
                                    <!-- Amigos inyectados por JS -->
                                </div>
                                <div id="friend-empty" class="friend-empty" style="display:none;">Sin resultados</div>
                            </div>
                        </div>

                        <!-- Mensaje de aviso de límite -->
                        <div id="collab-limit-msg"
                            style="display:none; color:#ffd54f; font-size:0.85rem; margin-top:8px;">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Has alcanzado el máximo de 5
                            colaboradores
                        </div>

                        <!-- Inputs ocultos para enviar los ids seleccionados -->
                        <div id="collaborators-hidden"></div>
                    </div>
                </div>

                <!-- BOTÓN -->
                <div class="d-grid">
                    <div class="error-success-message" id="error-success-message"
                        style="display: none; margin-bottom: 15px; text-align: center; font-weight: 500;">
                        <p id="error-success-message-text" class="mb-0"></p>
                    </div>
                    <button type="button" id="forum-submit-btn" class="btn btn-forum-submit">
                        <i class="fa-solid fa-plus me-2"></i><span data-i18n="forums.config.btn_create">Crear
                            foro</span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL DE LOGIN -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    <p class="mb-4" style="font-size:1rem;">Para crear un foro necesitas estar registrado</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal"
                            data-i18n="common.cancel">Cancelar</button>
                        <a href="<?= $base_url ?>/web/views/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                            class="btn btn-success px-4" data-i18n="forums.config.go_login">Ir al Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
    window.BASE_URL = '<?= $base_url ?>';
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script src="<?= Router::asset('web/js/forums/forums.js') ?>"></script>