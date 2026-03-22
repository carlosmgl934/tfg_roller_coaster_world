<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../routes/Router.php';
$base_url = Router::getBaseUrl();

if (!isset($_SESSION['firebase_uid'])) {
    $id = intval($_GET['id'] ?? 0);
    $redirect_url = urlencode(Router::url('form_park_rating') . '?id=' . $id);
    header('Location: ' . Router::url('login') . '?redirect=' . $redirect_url . '&msg=review');
    exit;
} else {
    $user_id = $_SESSION['firebase_uid'];
}

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    Router::redirect('park_search');
}

require_once __DIR__ . '/../../partials/header.php';
?>
<!-- Librerias para poder usar select múltiples-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="<?= Router::asset('web/css/coasters.css') ?>">

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
                        <input type="hidden" name="park_id" value="<?= $id ?>">
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

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-danger" for="contras-select"><i
                                    class="fa-solid fa-minus-circle me-1"></i> Mayores Contras:</label>
                            <select name="contras[]" id="contras-select" multiple>
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

                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="review">Comenta tu opinión sobre el parque:
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
</main>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="<?= Router::asset('web/js/parks.js') ?>"></script>
