<?php
require_once __DIR__ . '/../../../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
    $id = intval($_GET['id'] ?? 0);
    $redirect_url = urlencode($base_url . '/web/views/public/parks/form_park_rating.php?id=' . $id);
    header('Location: ' . $base_url . '/web/views/auth/login.php?redirect=' . $redirect_url . '&msg=review');
    exit;
} else {
    $user_id = $_SESSION['firebase_uid'];
}

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header('Location: ' . $base_url . '/web/views/public/parks/park_search.php');
    exit;
}
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<link rel="stylesheet" href="<?= $base_url ?>/web/css/coasters.css"> <!-- Reutilizamos el mismo CSS -->

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-7">
            <div class="card section-card bg-dark border-0 shadow-sm">
                <div class="card-header bg-success text-white text-center py-3">
                    <h4 class="mb-0">Escribe tu reseña del parque</h4>
                </div>
                <div class="card-body p-4">
                    <form id="park-review-form">
                        <input type="hidden" name="park_id" value="<?= $id ?>">

                        <!-- Rating numérico + estrellas -->
                        <div class="mb-4">
                            <label class="form-label fw-bold d-block">¿Qué nota le das al parque?</label>
                            <div class="rating-stars d-flex gap-2 mb-2">
                                <input type="radio" name="rating" value="1" id="star1" required>
                                <label for="star1">★</label>
                                <input type="radio" name="rating" value="2" id="star2">
                                <label for="star2">★</label>
                                <input type="radio" name="rating" value="3" id="star3">
                                <label for="star3">★</label>
                                <input type="radio" name="rating" value="4" id="star4">
                                <label for="star4">★</label>
                                <input type="radio" name="rating" value="5" id="star5">
                                <label for="star5">★</label>
                            </div>
                            <input type="number" class="form-control" id="rating-num" name="rating_num" min="1" max="5" step="0.1" placeholder="O escribe tu nota exacta (ej: 4.7)">
                        </div>

                        <!-- Pros y contras -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">¿Qué te gustó y qué no? (selecciona varios)</label>
                            <select class="form-select" name="pros_contras[]" id="pros-contras" multiple>
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
                                <!-- Puedes añadir más opciones según lo que quieras -->
                            </select>
                        </div>

                        <!-- Comentario largo -->
                        <div class="mb-4">
                            <label class="form-label fw-bold" for="review">Comenta tu opinión sobre el parque:</label>
                            <textarea class="form-control" name="review" id="review" rows="6" placeholder="Desarrolla aquí tu opinión en detalle..."></textarea>
                        </div>

                        <!-- Subida de foto opcional -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Añade una foto del parque (opcional)</label>
                            <input type="file" class="form-control" id="photo-upload" accept="image/*">
                            <div class="crop-container mt-3" style="display:none;">
                                <img id="cropper-image" style="max-width:100%;">
                            </div>
                            <button type="button" class="btn btn-outline-success mt-2" id="crop-save-btn" style="display:none;">Guardar foto recortada</button>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold rounded-1">Publicar Reseña <i class="fa-solid fa-paper-plane ms-2"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../../../partials/footer.php'; ?>

<script src="<?= $base_url ?>/web/js/parks.js"></script>
<script src="<?= $base_url ?>/web/js/auth-check.js"></script>
<!-- Choices.js para select múltiple -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<!-- CropperJS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>

<script>
// Inicializar Choices.js para pros/contras múltiple
const choices = new Choices('#pros-contras', {
  removeItemButton: true,
  placeholderValue: 'Selecciona pros y contras',
  noResultsText: 'No se encontraron resultados',
});

// Subida de foto con Cropper (igual que en coasters)
let cropper;
$("#photo-upload").on("change", function (e) {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      $("#cropper-image").attr("src", e.target.result);
      $(".crop-container").show();
      $("#crop-save-btn").show();

      if (cropper) cropper.destroy();
      cropper = new Cropper($("#cropper-image")[0], {
        aspectRatio: 16 / 9,
        viewMode: 1,
        autoCropArea: 0.8,
      });
    };
    reader.readAsDataURL(file);
  }
});

$("#crop-save-btn").click(function () {
  if (cropper) {
    cropper.getCroppedCanvas().toBlob(function (blob) {
      const formData = new FormData();
      formData.append('photo', blob, 'park-photo.jpg');
      formData.append('park_id', <?= $id ?>);

      $.ajax({
        url: '/api/parks/upload-photo',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (data) {
          if (data.success) {
            alert("¡Foto enviada! Esperando aprobación");
            $("#uploadPhotoModal").modal("hide");
            $("#photo-upload").val("");
            $(".crop-container").hide();
            $("#crop-save-btn").hide();
            if (cropper) cropper.destroy();
          } else {
            alert("Error: " + (data.error || "Desconocido"));
          }
        },
        error: function () {
          alert("Error al subir la foto");
        }
      });
    }, 'image/jpeg', 0.85);
  }
});

// Enviar reseña
$("#park-review-form").submit(function (e) {
  e.preventDefault();

  const formData = $(this).serializeArray();
  const rating = $("input[name='rating']:checked").val();
  if (!rating) {
    alert("Selecciona una valoración");
    return;
  }

  // ... resto del envío AJAX similar a coasters
});
</script>