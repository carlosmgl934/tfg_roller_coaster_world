<?php
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

$park_id     = $_GET['id'] ?? null;
$forumId     = $_GET['forum_id'] ?? null;
$privacy     = $_POST['privacy'] ?? 'public';
$friends     = $friends ?? [];
$hiddenStyle = ($privacy !== 'private') ? 'style="display:none"' : '';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="<?= $base_url ?>/web/css/forums.css">

<main class="container py-5" style="max-width: 680px;">

    <!-- Cabecera centrada -->
    <div class="text-center mb-4">
        <span class="forum-page-icon"><i class="fa-solid fa-comments"></i></span>
        <h2 class="fw-800 mt-3 mb-1">Configuración del Foro</h2>
        <p class="text-muted small">Define el nombre, descripción y privacidad de tu nuevo foro.</p>
    </div>

    <div class="forum-form-card card">
        <div class="card-body p-4 p-md-5">
            <form id="forum-form" action="#">

                <input type="hidden" name="user_id"  value="<?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>">
                <input type="hidden" name="forum_id" value="<?= htmlspecialchars($forumId ?? '') ?>">

                <!-- SECCIÓN: Identidad -->
                <p class="form-section-title">Personaliza tu foro</p>

                <div class="mb-4">
                    <label for="name" class="form-label">Nombre del foro</label>
                    <input type="text" name="name" id="name"
                           class="form-control form-control-lg"
                           placeholder="Ej: Mejores montañas rusas 2026" required>
                </div>

                <div class="mb-5">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea name="description" id="description"
                              class="form-control" rows="4"
                              placeholder="Describe brevemente de qué trata tu foro..." required></textarea>
                </div>

                <!-- SECCIÓN: Visibilidad -->
                <p class="form-section-title">Visibilidad</p>

                <div class="mb-2">
                    <div class="privacy-toggle" role="group">
                        <input type="radio" name="privacy" value="public"  id="privacy-public"  class="privacy-radio" <?= $privacy !== 'private' ? 'checked' : '' ?>>
                        <label for="privacy-public" class="privacy-btn">
                            <i class="fa-solid fa-earth-europe me-2"></i>Público
                        </label>

                        <input type="radio" name="privacy" value="private" id="privacy-private" class="privacy-radio" <?= $privacy === 'private' ? 'checked' : '' ?>>
                        <label for="privacy-private" class="privacy-btn">
                            <i class="fa-solid fa-lock me-2"></i>Privado
                        </label>
                    </div>
                    <p class="privacy-hint" id="privacy-hint">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        <span id="privacy-hint-text"><?= $privacy === 'private' ? 'Solo los colaboradores que designes pueden escribir, pero cualquiera puede leer el foro' : 'Cualquier usuario puede ver y escribir en el foro' ?></span>
                    </p>
                </div>

                <div class="mb-5"></div>

                <!-- SECCIÓN: Colaboradores (solo si privado) -->
                <div id="collaborators-section" <?= $hiddenStyle ?>>
                    <p class="form-section-title">Colaboradores</p>

                    <div class="mb-5">
                        <label for="collaborators" class="form-label">
                            Selecciona colaboradores
                            <span class="text-muted fw-normal ms-1">(opcional)</span>
                        </label>
                        <select name="collaborators[]" id="collaborators" class="form-select" multiple>
                            <?php foreach ($friends as $friend): ?>
                                <option value="<?= htmlspecialchars($friend['id']) ?>">
                                    <?= htmlspecialchars($friend['username']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- BOTÓN -->
                <div class="d-grid">
                    <button type="button" id="forum-submit-btn" class="btn btn-forum-submit">
                        <i class="fa-solid fa-plus me-2"></i>Crear foro
                    </button>
                    <div class="error-sucess-message" id="error-sucess-message">
                        <p id="error-sucess-message-text"></p>
                    </div>
                </div>

            </form>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('collaborators');
    if (el) {
        new Choices(el, {
            removeItemButton: true,
            searchEnabled: true,
            searchPlaceholderValue: 'Buscar colaborador...',
            placeholderValue: 'Selecciona colaboradores',
            noResultsText: 'Sin resultados',
            noChoicesText: 'No hay más opciones',
            itemSelectText: '',
            shouldSort: true,
        });
    }
});
</script>