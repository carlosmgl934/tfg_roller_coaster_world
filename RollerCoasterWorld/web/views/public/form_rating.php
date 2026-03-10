<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Debes iniciar sesión para calificar una montaña rusa';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . $base_url . '/web/views/public/login.php');
    exit;
} else {
    $user_id = $_SESSION['user_id'];
}

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    $_SESSION['message'] = 'No se ha encontrado la montaña rusa';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . $base_url . '/web/views/public/coaster_search.php');
    exit;
}
?>
<!-- Librerias para poder usar select múltiples-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css">

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">
            <div class="card section-card">
                <div class="card-header bg-success text-white d-flex align-items-center gap-2 py-3">
                    <i class="fa-solid fa-pen-nib ms-1"></i>
                    <h4 class="mb-0 fw-semibold text-white">Escribir Reseña</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    <form id="review-form" action="#">
                        <input type="hidden" name="coaster_id" value="<?= $id ?>">
                        <input type="hidden" name="user_id" value="<?= $user_id ?>">

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="note">Califica con estrellas:</label>
                            <div class="star-rating mt-2">
                                <?php for ($i = 10; $i >= 1; $i--):
                                    $value = $i / 2;
                                    $half = ($i % 2 !== 0);
                                    ?>
                                    <input type="radio" name="note" id="star<?= $i ?>" value="<?= $value ?>">
                                    <label for="star<?= $i ?>" class="<?= $half ? 'half' : 'full' ?>"
                                        title="<?= $value ?>"></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-success" for="pros-select"><i
                                    class="fa-solid fa-plus-circle me-1"></i> Principales Ventajas:</label>
                            <select name="pros[]" id="pros-select" multiple>
                                <option value="airtime">Airtime</option>
                                <option value="arnes">Arnés</option>
                                <option value="capacidad">Capacidad</option>
                                <option value="comodidad">Comodidad</option>
                                <option value="duracion">Duración</option>
                                <option value="hangtime">Hangtime</option>
                                <option value="intensidad">Intensidad</option>
                                <option value="inversiones">Inversiones</option>
                                <option value="launch">Launch</option>
                                <option value="caidas">Caídas</option>
                                <option value="suavidad">Suavidad</option>
                                <option value="recorrido">Layout</option>
                                <option value="tematizacion">Tematización</option>
                                <option value="velocidad">Velocidad</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-danger" for="contras-select"><i
                                    class="fa-solid fa-minus-circle me-1"></i> Mayores Contras:</label>
                            <select name="contras[]" id="contras-select" multiple>
                                <option value="airtime">Airtime</option>
                                <option value="arnes">Arnés</option>
                                <option value="capacidad">Capacidad</option>
                                <option value="comodidad">Comodidad</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="duracion_corta">Corta duración</option>
                                <option value="intensidad">Intensidad</option>
                                <option value="inversiones">Inversiones</option>
                                <option value="launch">Launch</option>
                                <option value="recorrido">Layout</option>
                                <option value="vibracion">Vibración</option>
                                <option value="dolorosa">Dolorosa</option>
                                <option value="decepcionante">Decepcionante</option>
                                <option value="tematizacion">Tematización</option>
                                <option value="velocidad_nula">Poca velocidad</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="review">Comenta tu opinión sobre la montaña rusa:
                            </label>
                            <textarea class="form-control" name="review" id="review" rows="5"
                                placeholder="Desarrolla aquí tu opinión en detalle..."></textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold rounded-1">Publicar Reseña <i
                                    class="fa-solid fa-paper-plane ms-2"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        new Choices('#pros-select', {
            removeItemButton: true,
            placeholderValue: 'Selecciona las ventajas...'
        });
        new Choices('#contras-select', {
            removeItemButton: true,
            placeholderValue: 'Selecciona las contras...'
        });
    </script>
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/coasters.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>