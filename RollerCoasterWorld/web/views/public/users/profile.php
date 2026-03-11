<?php
require_once __DIR__ . '/../../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid = $_SESSION['firebase_uid'];
?>
<link rel="stylesheet" href="<?= $base_url ?>/web/css/profile.css">

<main class="container-fluid px-4 px-xl-5 my-5" style="max-width: 1400px;">
  <div class="row g-4">
    <!-- Columna Izquierda: Perfil y Menú Lateral -->
    <div class="col-lg-3 col-md-4">
      <!-- Tarjeta de Perfil Principal -->
      <div class="card profile-card text-center mb-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center mb-0">
            <div class="position-relative d-inline-block me-3">
              <?php
              $display_name = "Usuario";
              if ($user_email !== 'Desconocido') {
                $parts = explode('@', $user_email);
                $display_name = $parts[0];
              }
              $initial = strtoupper(substr($display_name, 0, 1) ?: '?');
              ?>
              <div class="avatar-circle rounded-circle shadow-sm" style="width: 80px; height: 80px; font-size: 36px;">
                <?php echo $initial; ?>
              </div>
            </div>
            <div class="text-start overflow-hidden">
              <h5 class="card-title fw-bold mb-1 text-truncate" id="profile-display-name">
                <?php echo htmlspecialchars($display_name); ?>
              </h5>
              <p class="text-muted small mb-0 text-truncate"><?php echo htmlspecialchars($user_email); ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Menú Lateral -->
      <div class="card profile-card">
        <div class="list-group list-group-flush profile-menu" id="profile-menu">
          <a href="#" class="list-group-item list-group-item-action py-3 active fw-medium"><i
              class="fa-solid fa-user me-2 w-20px text-center"></i> Mi Perfil</a>
          <a href="#" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-gear me-2 w-20px text-center"></i> Configuración</a>
          <a href="#" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-list-ol me-2 w-20px text-center"></i> Mis tops</a>
          <a href="#" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-pen-to-square me-2 w-20px text-center"></i> Mis reseñas</a>
          <a href="#" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-users me-2 w-20px text-center"></i> Mis amigos</a>
          <a href="#" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-map-pin me-2 w-20px text-center"></i> Mi mapa</a>
          <a href="<?= $base_url ?>/web/views/auth/logout.php"
            class="list-group-item list-group-item-action text-danger mt-1 py-3 border-top"><i
              class="fa-solid fa-arrow-right-from-bracket me-2 w-20px text-center"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>

    <!-- Columna Derecha: Contenido Dinámico - Mi perfil -->
    <div class="col-lg-9 col-md-8">

      <!-- Sección: Información del Usuario -->
      <div class="card profile-card mb-4 content-section" id="section-info">
        <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-regular fa-id-card fs-5"></i>
          <h5 class="fw-bold mb-0">Datos Generales</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4 mb-5">
            <div class="col-sm-6 col-lg-4">
              <p class="text-muted mb-1 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Nombre Completo
              </p>
              <p class="fw-medium fs-5 text-dark mb-0" id="full-name">—</p>
            </div>
            <div class="col-sm-6 col-lg-4">
              <p class="text-muted mb-1 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Nombre de
                usuario</p>
              <p class="fw-medium fs-5 text-dark mb-0" id="username">—</p>
            </div>
            <div class="col-sm-6 col-lg-4">
              <p class="text-muted mb-1 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Correo
                electrónico</p>
              <p class="fw-medium fs-5 text-dark mb-0 text-truncate" id="email"
                title="<?php echo htmlspecialchars($user_email); ?>">—</p>
            </div>
            <div class="col-sm-6 col-lg-4">
              <p class="text-muted mb-1 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Fecha de
                nacimiento</p>
              <p class="fw-medium fs-5 text-dark mb-0" id="birth-date">—</p>
            </div>
            <div class="col-sm-6 col-lg-4">
              <p class="text-muted mb-1 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Género</p>
              <p class="fw-medium fs-5 text-dark mb-0" id="gender">—</p>
            </div>
            <div class="col-sm-6 col-lg-4">
              <p class="text-muted mb-1 small text-uppercase fw-semibold" style="letter-spacing: 0.5px;">Ubicación</p>
              <p class="fw-medium fs-5 text-dark mb-0" id="location">—</p>
            </div>
          </div>

          <h5 class="fw-bold mb-3 border-bottom pb-2 text-success">Favoritos</h5>
          <div class="row g-3">
            <div class="col-sm-4">
              <div class="p-3 fav-box text-center">
                <i class="fa-solid fa-star text-success mb-2 fs-3"></i>
                <p class="text-muted mb-1 small fw-semibold">Top Coaster</p>
                <p class="fw-bold mb-0 text-truncate fs-6 text-dark" id="favorite-coaster">—</p>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 fav-box text-center">
                <i class="fa-solid fa-map-pin text-success mb-2 fs-3"></i>
                <p class="text-muted mb-1 small fw-semibold">Top Park</p>
                <p class="fw-bold mb-0 text-truncate fs-6 text-dark" id="favorite-park">—</p>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 fav-box text-center">
                <i class="fa-solid fa-house text-success mb-2 fs-3"></i>
                <p class="text-muted mb-1 small fw-semibold">Home Park</p>
                <p class="fw-bold mb-0 text-truncate fs-6 text-dark" id="home-park">—</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sección: Estadísticas Generales -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <div class="card profile-card h-100">
            <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
              <i class="fa-solid fa-ticket scale-icon fs-5"></i>
              <h5 class="fw-bold mb-0">Estadísticas Generales</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-bolt me-2 text-success"></i>Montañas
                    rusas</span>
                  <span class="badge badge-profile fs-6" id="coasters-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i
                      class="fa-solid fa-map-location-dot me-2 text-success"></i>Parques visitados</span>
                  <span class="badge badge-profile fs-6" id="parks-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i
                      class="fa-solid fa-earth-americas me-2 text-success"></i>Países</span>
                  <span class="badge badge-profile fs-6" id="countries-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-star me-2 text-warning"></i>Valoraciones
                    totales</span>
                  <span class="badge badge-profile fs-6" id="reviews-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-trophy me-2 text-warning"></i>Ranking</span>
                  <span class="badge badge-profile fs-6" id="user-ranking">—</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card profile-card h-100">
            <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
              <i class="fa-solid fa-chart-pie fs-5"></i>
              <h5 class="fw-bold mb-0">Estadísticas Técnicas</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted">País más visitado</span>
                  <span class="fw-bold text-end text-dark" id="main-country">—</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted">Fabricante favorito</span>
                  <span class="fw-bold text-end text-truncate ms-3 text-dark" id="main-manufacturer"
                    style="max-width: 150px;">—</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted">Fabricantes totales</span>
                  <span class="fw-bold text-end text-dark" id="total-manufacturers">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 border-top mt-2 pt-3">
                  <span class="text-muted">Altura total superada</span>
                  <span class="fw-bold text-dark fs-5"><span id="total-height">0</span><small
                      class="text-muted fs-6">m</small></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted">Inversiones totales</span>
                  <span class="fw-bold text-dark fs-5" id="total-investments">0</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted">Más rápida</span>
                  <span class="fw-bold text-end text-truncate ms-3 text-dark" id="fastest-coaster"
                    style="max-width: 150px;">—</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted">Más larga</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Columna Derecha: Contenido Dinámico - Configuración -->
    <div class="col-lg-9 col-md-8">

      <!-- Card: Datos Personales -->
      <div class="card profile-card mb-4 content-section" id="section-config-personal">
        <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-user-pen fs-5"></i>
          <h5 class="fw-bold mb-0">Datos Personales</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <label for="config-user-name" class="form-label fw-semibold text-dark small mb-1">Nombre completo</label>
              <input type="text" id="config-user-name" class="form-control form-control-lg fs-6 square-box"
                placeholder="Ingresa tu nombre y apellidos">
            </div>
            <div class="col-md-6">
              <label for="config-user-username" class="form-label fw-semibold text-dark small mb-1">Nombre de
                usuario</label>
              <input type="text" id="config-user-username" class="form-control form-control-lg fs-6 square-box"
                placeholder="Ingresa tu nombre de usuario en la web">
            </div>
            <div class="col-md-6">
              <label for="config-user-email" class="form-label fw-semibold text-dark small mb-1">Correo
                Electrónico</label>
              <input type="email" id="config-user-email" class="form-control form-control-lg fs-6 square-box bg-light"
                disabled>
            </div>
            <div class="col-md-6">
              <label for="config-user-birthdate" class="form-label fw-semibold text-dark small mb-1">Fecha de
                nacimiento</label>
              <input type="date" id="config-user-birthdate" class="form-control form-control-lg fs-6 square-box">
            </div>
            <div class="col-md-4">
              <label for="config-user-gender" class="form-label fw-semibold text-dark small mb-1">Género</label>
              <select id="config-user-gender" class="form-select form-select-lg fs-6 square-box">
                <option value="">Seleccionar género</option>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="config-user-city" class="form-label fw-semibold text-dark small mb-1">Ciudad</label>
              <div class="position-relative">
                <input type="text" id="config-user-city" class="form-control form-control-lg fs-6 square-box"
                  placeholder="Ej: Madrid, Barcelona, París">
                <span id="city-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted small d-none">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
              </div>
            </div>
            <div class="col-md-4">
              <label for="config-user-country" class="form-label fw-semibold text-dark small mb-1">País</label>
              <input type="text" id="config-user-country" class="form-control form-control-lg fs-6 square-box"
                placeholder="Se rellena automáticamente al escribir tu ciudad">
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Preferencias -->
      <div class="card profile-card mb-4 content-section" id="section-config-prefs">
        <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-heart fs-5 text-danger"></i>
          <h5 class="fw-bold mb-0">Atracciones favoritas</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <label for="top-coaster-user" class="form-label fw-semibold text-dark small mb-1">Coaster
                Favorita</label>
              <div class="position-relative">
                <input type="text" id="top-coaster-user" class="form-control form-control-lg fs-6 square-box bg-light"
                  placeholder="Se configura desde tus tops" disabled>
                <span id="top-coaster-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted small d-none">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
              </div>
            </div>
            <div class="col-md-6">
              <label for="home-park-user" class="form-label fw-semibold text-dark small mb-1">Home Park</label>
              <div class="position-relative">
                <input type="text" id="home-park-user" class="form-control form-control-lg fs-6 square-box"
                  placeholder="Busca tu home park..." autocomplete="off">
                <span id="home-park-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted small d-none">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
                <ul id="home-park-dropdown" class="list-group position-absolute w-100 shadow-sm z-3 d-none"
                  style="max-height: 200px; overflow-y: auto; top: 100%; left: 0;">
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="guardar-config-btn">
        <button type="button" id="guardar-config-btn" class="btn btn-success btn-lg square-box px-5 fw-semibold">
          <i class="fa-solid fa-floppy-disk me-2"></i>Guardar configuración
        </button>
      </div>

      <!-- Sección: Seguridad (Contraseña) -->
      <div class="card profile-card mb-4 content-section" id="section-security">
        <div class="card-header pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-shield-halved fs-5"></i>
          <h5 class="fw-bold mb-0">Seguridad de la cuenta</h5>
        </div>
        <div class="card-body p-4">
          <div class="d-flex align-items-center mb-4">
            <i class="fa-brands fa-google text-muted fs-4 me-3"></i>
            <div>
              <p class="mb-0 fw-bold">ID de Firebase</p>
              <p class="text-muted mb-0 small font-monospace"><?php echo htmlspecialchars($user_uid); ?></p>
            </div>
          </div>

          <button id="toggleFormPassword" class="btn btn-outline-success square-box mb-3 px-4 fw-medium">
            <i class="fa-solid fa-key me-2"></i>Cambiar contraseña
          </button>

          <div id="form-password" class="bg-light p-4 square-box mb-3"
            style="display: none; border: 1px solid #e9ecef;">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold text-dark small">Nueva contraseña</label>
                <input type="password" id="nueva-password" class="form-control form-control-lg fs-6 square-box"
                  placeholder="Mínimo 6 caracteres">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold text-dark small">Confirmar contraseña</label>
                <input type="password" id="confirmar-password" class="form-control form-control-lg fs-6 square-box"
                  placeholder="Repita la contraseña">
              </div>
              <div class="col-12 mt-4 d-flex align-items-center">
                <button id="cambiarPassword" class="btn btn-success me-2 px-4 fw-medium shadow-sm square-box"><i
                    class="fa-solid fa-check me-2"></i>Guardar cambios</button>
                <button id="btn-cancelar-password"
                  class="btn btn-light border px-4 fw-medium square-box">Cancelar</button>
              </div>
              <div class="col-12 mt-2">
                <p id="msg-password" class="mb-0 fw-medium small"></p>
              </div>
            </div>
          </div>

          <hr class="my-4" style="border-color: #dee2e6;">

          <div
            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center bg-danger bg-opacity-10 p-4 danger-zone-box border border-danger border-opacity-25">
            <div class="mb-3 mb-md-0">
              <h5 class="text-danger fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i>Zona de peligro
              </h5>
              <p class="text-danger opacity-75 mb-0 small fw-medium">Esta acción es irreversible y eliminará todos tus
                datos permanentemente.</p>
            </div>
            <button id="borrarCuenta" class="btn btn-danger text-nowrap fw-bold shadow-sm px-4 square-box">
              <i class="fa-solid fa-user-xmark me-2"></i>Eliminar cuenta
            </button>
          </div>

        </div>
      </div>

    </div>
  </div>
</main>

<script src="<?= $base_url ?>/web/js/auth-check.js"></script>
<script src="<?= $base_url ?>/web/js/profile.js"></script>



<?php
require_once __DIR__ . '/../../partials/footer.php';
?>