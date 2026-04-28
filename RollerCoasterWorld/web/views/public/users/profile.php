<?php
$page_css = [
  'web/css/profile.css',
  'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
  'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
];
require_once __DIR__ . '/../../partials/header.php';
/** @var string $base_url */

if (!isset($_SESSION['firebase_uid'])) {
  header('Location: ' . $base_url . '/web/views/auth/login.php');
  exit;
}

$user_email = $_SESSION['user_email'] ?? 'Desconocido';
$user_uid = $_SESSION['firebase_uid'];
?>
<main class="container-fluid px-4 px-xl-5 my-5" style="max-width: 1400px;">
  <div class="row g-4">
    <!-- Columna Izquierda: Perfil y Menú Lateral -->
    <div class="col-lg-4 col-md-5">
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
              <div class="avatar-circle shadow-sm" style="width: 80px; height: 80px; font-size: 36px;">
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
        <div class="list-group list-group-flush profile-menu" id="sidebar-menu">
          <a href="#" id="menu-profile" class="list-group-item list-group-item-action py-3 active fw-medium"><i
              class="fa-solid fa-user me-2 w-20px text-center"></i> Mi Perfil</a>
          <a href="#" id="menu-config" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-gear me-2 w-20px text-center"></i> Configuración</a>
          <a href="#" id="menu-tops" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-list-ol me-2 w-20px text-center"></i> Mis tops</a>
          <a href="#" id="menu-reviews" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-pen-to-square me-2 w-20px text-center"></i> Mis reseñas</a>
          <a href="#" id="menu-friends" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-users me-2 w-20px text-center"></i> Mis amigos</a>
          <a href="#" id="menu-map" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-map-pin me-2 w-20px text-center"></i> Mi mapa</a>
          <a href="#" class="list-group-item list-group-item-action text-danger mt-1 py-3 border-top signOutBtn"><i
              class="fa-solid fa-arrow-right-from-bracket me-2 w-20px text-center"></i> Cerrar sesión</a>
        </div>
      </div>
    </div>

    <!-- Columna Derecha: Contenido Dinámico - Mi perfil -->
    <div class="col-lg-8 col-md-7" id="section-profile-content">

      <!-- Sección: Información del Usuario -->
      <div class="card profile-card mb-4 content-section" id="section-info">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-regular fa-id-card fs-5"></i>
          <h5 class="fw-bold mb-0">Datos Generales</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4 mb-5">
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-regular fa-id-badge"></i>Nombre Completo</p>
                <p class="data-value text-truncate" id="full-name">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-solid fa-at"></i>Nombre de usuario</p>
                <p class="data-value text-truncate" id="username">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-regular fa-envelope"></i>Correo electrónico</p>
                <p class="data-value text-truncate" id="email" title="<?php echo htmlspecialchars($user_email); ?>">—
                </p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-regular fa-calendar"></i>Fecha de nacimiento</p>
                <p class="data-value" id="birth-date">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-solid fa-venus-mars"></i>Género</p>
                <p class="data-value" id="gender">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-solid fa-location-dot"></i>Ubicación</p>
                <p class="data-value text-truncate" id="location">—</p>
              </div>
            </div>
          </div>

          <h5 class="fw-bold mb-3 border-bottom pb-2 text-success"><i class="fa-solid fa-heart me-2"></i>Favoritos</h5>
          <div class="row g-3">
            <div class="col-sm-4">
              <div class="p-3 fav-box text-center rounded-0">
                <div class="fav-icon"><i class="fa-solid fa-star"></i></div>
                <p class="text-muted mb-1 small fw-bold text-uppercase"
                  style="letter-spacing: 0.5px; font-size: 0.75rem;">Top Coaster</p>
                <p class="fw-bold mb-0 text-truncate text-dark" style="font-size: 1.05rem;" id="favorite-coaster">—</p>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 fav-box text-center rounded-0">
                <div class="fav-icon"><i class="fa-solid fa-map-pin"></i></div>
                <p class="text-muted mb-1 small fw-bold text-uppercase"
                  style="letter-spacing: 0.5px; font-size: 0.75rem;">Top Park</p>
                <p class="fw-bold mb-0 text-truncate text-dark" style="font-size: 1.05rem;" id="favorite-park">—</p>
              </div>
            </div>
            <div class="col-sm-4">
              <div class="p-3 fav-box text-center rounded-0">
                <div class="fav-icon"><i class="fa-solid fa-house"></i></div>
                <p class="text-muted mb-1 small fw-bold text-uppercase"
                  style="letter-spacing: 0.5px; font-size: 0.75rem;">Home Park</p>
                <p class="fw-bold mb-0 text-truncate text-dark" style="font-size: 1.05rem;" id="home-park">—</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sección: Estadísticas Generales -->
      <div class="row g-4 mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
          <div class="card profile-card h-100">
            <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
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
            <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
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
                  <span class="fw-bold text-dark fs-5"><span id="total-height">0</span><small class="text-muted fs-6">
                      m</small></span>
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
                  <span class="fw-bold text-end text-truncate ms-3 text-dark" id="longest-coaster"
                    style="max-width: 150px;">—</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Columna Derecha: Contenido Dinámico - Configuración -->
    <div class="col-lg-8 col-md-7 d-none" id="section-config-content">

      <!-- Card: Datos Personales -->
      <div class="card profile-card mb-4 content-section" id="section-config-personal">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-pen fs-5"></i>
            <h5 class="fw-bold mb-0">Datos Personales</h5>
          </div>
          <div class="profile-photo">
            <button id="change-avatar-btn" class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-camera me-2"></i>Editar foto de perfil
            </button>
            <input type="file" id="avatar-input" accept="image/*" class="d-none">
          </div>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <label for="config-user-name" class="form-label"><i class="fa-regular fa-id-badge me-2"></i>Nombre
                completo</label>
              <input type="text" id="config-user-name" class="form-control rounded-0">
            </div>
            <div class="col-md-6">
              <label for="config-user-username" class="form-label"><i class="fa-solid fa-at me-2"></i>Nombre de
                usuario</label>
              <input type="text" id="config-user-username" class="form-control rounded-0">
            </div>
            <div class="col-md-6">
              <label for="config-user-email" class="form-label"><i class="fa-regular fa-envelope me-2"></i>Correo
                Electrónico</label>
              <input type="email" id="config-user-email" class="form-control rounded-0"
                value="<?= htmlspecialchars($user_email) ?>" disabled>
            </div>
            <div class="col-md-6">
              <label for="config-user-birthdate" class="form-label"><i class="fa-regular fa-calendar me-2"></i>Fecha de
                nacimiento</label>
              <div class="position-relative">
                <input type="text" id="config-user-birthdate" class="form-control rounded-0" placeholder="DD/MM/AAAA"
                  autocomplete="off" readonly>
                <i class="fa-regular fa-calendar position-absolute top-50 end-0 translate-middle-y me-3 text-muted"
                  style="pointer-events:none;"></i>
              </div>
            </div>
            <div class="col-md-4">
              <label for="config-user-gender" class="form-label"><i
                  class="fa-solid fa-venus-mars me-2"></i>Género</label>
              <select id="config-user-gender" class="form-select rounded-0">
                <option value="">Seleccionar género</option>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
                <option value="Otro">Otro</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="config-user-city" class="form-label"><i class="fa-solid fa-building me-2"></i>Ciudad</label>
              <div class="position-relative">
                <input type="text" id="config-user-city" class="form-control rounded-0" placeholder="Ej: Madrid">
                <span id="city-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted small d-none"
                  style="z-index: 5;">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
              </div>
            </div>
            <div class="col-md-4">
              <label for="config-user-country" class="form-label"><i
                  class="fa-solid fa-earth-americas me-2"></i>País</label>
              <input type="text" id="config-user-country" class="form-control rounded-0" placeholder="País" disabled>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Preferencias -->
      <div class="card profile-card mb-4 content-section" id="section-config-prefs">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-heart fs-5 text-danger"></i>
          <h5 class="fw-bold mb-0">Atracciones favoritas</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <label for="top-coaster-user" class="form-label"><i class="fa-solid fa-star text-warning me-2"></i>Coaster
                Favorita</label>
              <div class="position-relative">
                <input type="text" id="top-coaster-user" class="form-control rounded-0" placeholder="Desconocida"
                  disabled>
                <span id="top-coaster-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted small d-none"
                  style="z-index: 5;">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
              </div>
              <div class="form-text mt-2 ms-1 fw-medium text-muted"><i class="fa-solid fa-circle-info me-1"></i> Se
                configura automáticamente desde tus tops</div>
            </div>
            <div class="col-md-6">
              <label for="home-park-user" class="form-label"><i class="fa-solid fa-house text-success me-2"></i>Home
                Park</label>
              <div class="position-relative">
                <input type="text" id="home-park-user" class="form-control rounded-0" placeholder="Busca tu home park"
                  autocomplete="off">
                <span id="home-park-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-4 text-muted small d-none"
                  style="z-index: 5;">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
                <ul id="home-park-dropdown" class="list-group position-absolute w-100 shadow-sm d-none"
                  style="max-height: 200px; overflow-y: auto; top: 100%; left: 0; z-index: 1050; border: 1px solid #10b981; border-top: none;">
                </ul>
              </div>
              <div class="form-text mt-2 ms-1 fw-medium text-muted"><i class="fa-solid fa-magnifying-glass me-1"></i>
                Busca tu parque más cercano</div>
            </div>
          </div>
        </div>
      </div>

      <div class="guardar-config-btn d-flex justify-content-end align-items-center mt-4 mb-5">
        <h3 id="msg-guardar-config" class="text-success mb-0 me-4 d-none fw-bold" style="font-size: 1.1rem;"></h3>
        <button type="button" id="guardar-config-btn" class="btn btn-success btn-lg rounded-0 px-5 fw-bold shadow-sm">
          <i class="fa-solid fa-floppy-disk me-2"></i>Guardar configuración
        </button>
      </div>

      <!-- Sección: Seguridad (Contraseña) -->
      <div class="card profile-card mb-4 content-section" id="section-security">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
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

          <button id="toggleFormPassword" class="btn btn-outline-success rounded-0 mb-3 px-4 fw-medium">
            <i class="fa-solid fa-key me-2"></i>Cambiar contraseña
          </button>

          <div id="form-password" class="bg-rcw-card p-4 rounded-0 border-0 mb-3 border-rcw"
            style="display: none; border-width: 2px;">
            <div class="row g-4 mt-1">
              <div class="col-12 mt-0">
                <label for="old-password" class="form-label"><i class="fa-solid fa-key me-2"></i>Contraseña
                  actual</label>
                <input type="password" id="old-password" class="form-control rounded-0"
                  placeholder="Tu contraseña actual">
              </div>
              <div class="col-md-6">
                <label for="nueva-password" class="form-label"><i class="fa-solid fa-lock me-2"></i>Nueva
                  contraseña</label>
                <input type="password" id="nueva-password" class="form-control rounded-0"
                  placeholder="Mínimo 6 caracteres">
                <div class="form-text ms-1 mt-2 text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i> Mínimo 6
                  caracteres</div>
              </div>
              <div class="col-md-6">
                <label for="confirmar-password" class="form-label"><i class="fa-solid fa-lock-open me-2"></i>Confirmar
                  contraseña</label>
                <input type="password" id="confirmar-password" class="form-control rounded-0"
                  placeholder="Repite la contraseña">
              </div>
              <div class="col-12 mt-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center"><button id="cambiarPassword"
                    class="btn btn-success me-2 px-4 fw-bold shadow-sm rounded-0"><i
                      class="fa-solid fa-check me-2"></i>Guardar cambios</button><button id="btn-cancelar-password"
                    class="btn btn-outline-secondary px-4 fw-bold rounded-0 border-rcw text-rcw">Cancelar</button></div>
                <a href="#" id="forgotPasswordBtn" class="text-success small fw-medium text-decoration-none"><i
                    class="fa-solid fa-envelope me-1"></i>¿Has olvidado tu contraseña?</a>
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
            <button id="borrarCuenta" class="btn btn-danger text-nowrap fw-bold shadow-sm px-4 rounded-0">
              <i class="fa-solid fa-user-xmark me-2"></i>Eliminar cuenta
            </button>
          </div>

        </div>
      </div>

    </div>

    <!-- Columna Derecha: Contenido Dinámico - Mis Tops -->
    <div class="col-lg-8 col-md-7 d-none" id="section-tops-content">

      <!-- ── Card principal de tops (anchura completa) ─── -->
      <div class="card profile-card mb-4 content-section" id="section-tops">

        <!-- Card Header -->
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-ol fs-5"></i>
            <h5 class="fw-bold mb-0">Mis Tops</h5>
          </div>
          <div class="d-flex gap-2" id="tops-header-actions">
            <button id="btn-tops-full-view" class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-expand me-1"></i>Ver Top Completo
            </button>
            <button id="btn-tops-edit" class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-pen-to-square me-1"></i>Editar Top
            </button>
          </div>
          <div class="d-none" id="tops-back-btn-wrap">
            <button id="btn-tops-back" class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-arrow-left me-1"></i>Volver
            </button>
          </div>
        </div>

        <div class="card-body p-4">

          <!-- Tabs -->
          <ul class="nav nav-pills nav-fill mb-4 gap-2" id="tops-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active rounded-0 fw-bold" id="top-coasters-tab" data-bs-toggle="pill"
                data-bs-target="#top-coasters-pane" type="button" role="tab" aria-controls="top-coasters-pane"
                aria-selected="true" style="color:#fff;">
                <i class="fa-solid fa-ticket me-2"></i>Top Coasters <span id="tab-coasters-count"
                  class="badge bg-success text-dark ms-1 d-none">0</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link rounded-0 fw-bold" id="top-parks-tab" data-bs-toggle="pill"
                data-bs-target="#top-parks-pane" type="button" role="tab" aria-controls="top-parks-pane"
                aria-selected="false" style="color:#fff;">
                <i class="fa-solid fa-map-location-dot me-2"></i>Top Parques <span id="tab-parks-count"
                  class="badge bg-success text-dark ms-1 d-none">0</span>
              </button>
            </li>
          </ul>

          <div class="tab-content" id="tops-tabContent">

            <!-- PESTAÑA COASTERS -->
            <div class="tab-pane fade show active" id="top-coasters-pane" role="tabpanel" tabindex="0">

              <!-- MODO PREVIEW -->
              <div id="coasters-mode-preview">
                <div id="top-coasters-preview-list" class="tops-preview-scroll">
                  <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin fs-4"></i></div>
                </div>
              </div>

              <!-- MODO VISTA COMPLETA -->
              <div id="coasters-mode-full" class="d-none">
                <div class="tops-filters-bar mb-3">
                  <div class="tops-filters-grid">
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-sort me-1"></i>Ordenar</label>
                      <div class="tops-sort-row">
                        <select id="coasters-sort" class="tops-filter-select tops-sort-select">
                          <option value="rank">Mi Ranking</option>
                          <option value="name">A-Z</option>
                          <option value="height">Altura</option>
                          <option value="speed">Velocidad</option>
                          <option value="length">Longitud</option>
                          <option value="inversions">Inversiones</option>
                          <option value="year">Antigüedad</option>
                        </select>
                        <button id="coasters-sort-dir" class="tops-sort-dir-btn" title="Cambiar dirección"
                          data-dir="desc">
                          <i class="fa-solid fa-arrow-down-wide-short"></i>
                        </button>
                      </div>
                    </div>
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-map-pin me-1"></i>Parque</label>
                      <select id="coasters-filter-park" class="tops-filter-select">
                        <option value="">Todos los parques</option>
                      </select>
                    </div>
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-earth-europe me-1"></i>País</label>
                      <select id="coasters-filter-country" class="tops-filter-select">
                        <option value="">Todos los países</option>
                      </select>
                    </div>
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-industry me-1"></i>Fabricante</label>
                      <select id="coasters-filter-manufacter" class="tops-filter-select">
                        <option value="">Todos los fabricantes</option>
                      </select>
                    </div>
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-gears me-1"></i>Modelo</label>
                      <select id="coasters-filter-model" class="tops-filter-select">
                        <option value="">Todos los modelos</option>
                      </select>
                    </div>
                  </div>
                  <div class="tops-filters-footer">
                    <span class="tops-counter-pill">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="14" height="14"
                        style="vertical-align:-1px;flex-shrink:0;" fill="none" stroke="currentColor"
                        stroke-linecap="round">
                        <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" stroke-width="4" />
                        <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" stroke-width="4" />
                        <line x1="16" y1="42" x2="16" y2="60" stroke-width="3" />
                        <line x1="32" y1="20" x2="32" y2="60" stroke-width="3" />
                        <line x1="48" y1="24" x2="48" y2="60" stroke-width="3" />
                        <rect x="23" y="10" width="10" height="6" rx="2" fill="currentColor" stroke="none" />
                        <circle cx="25" cy="18" r="2" fill="currentColor" stroke="none" />
                        <circle cx="31" cy="18" r="2" fill="currentColor" stroke="none" />
                      </svg><span id="coasters-full-count">0</span> <span id="coasters-full-label">coasters</span>
                    </span>
                    <div class="btn-group btn-group-sm ms-auto">
                      <button id="coasters-view-list" class="btn btn-success rounded-0" title="Lista"><i
                          class="fa-solid fa-list"></i></button>
                      <button id="coasters-view-grid" class="btn btn-outline-secondary rounded-0" title="Cuadrícula"><i
                          class="fa-solid fa-grip"></i></button>
                    </div>
                  </div>
                </div>
                <div id="top-coasters-full-container" class="row g-3"></div>
              </div>

              <!-- MODO EDICIÓN -->
              <div id="coasters-mode-edit" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold mb-0"><i class="fa-solid fa-pen me-2"></i>Editar Top Coasters</h6>
                  <button class="btn btn-sm btn-success fw-bold rounded-0 shadow-sm" id="btn-save-coasters-top">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios
                  </button>
                </div>
                <div class="mb-3">
                  <label for="top-coasters-search" class="form-label fw-medium text-uppercase small text-muted">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Añadir montaña rusa
                  </label>
                  <div class="position-relative">
                    <input type="text" id="top-coasters-search"
                      class="form-control bg-dark text-white border-secondary rounded-0 pe-5"
                      placeholder="Escribe el nombre..." autocomplete="off">
                    <i class="fa-solid fa-magnifying-glass text-muted position-absolute top-50 end-0 translate-middle-y me-3"
                      id="top-coasters-search-icon" style="transition:color .2s;"></i>
                    <ul id="top-coasters-dropdown" class="list-group position-absolute w-100 shadow-sm d-none"
                      style="max-height:250px;overflow-y:auto;z-index:1050;border-top:none;top:100%;left:0;"></ul>
                  </div>
                </div>
                <div
                  class="alert alert-success bg-success bg-opacity-10 border-success text-success fw-medium small rounded-0 mb-2">
                  <i class="fa-solid fa-grip-lines me-2"></i>Arrastra para reordenar · <i
                    class="fa-solid fa-trash ms-2 me-1 text-danger"></i>para eliminar
                </div>
                <div id="top-coasters-list-edit" style="min-height:80px;"></div>
              </div>

            </div><!-- /top-coasters-pane -->

            <!-- PESTAÑA PARQUES -->
            <div class="tab-pane fade" id="top-parks-pane" role="tabpanel" tabindex="0">

              <!-- MODO PREVIEW -->
              <div id="parks-mode-preview">
                <div id="top-parks-preview-list" class="tops-preview-scroll">
                  <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin fs-4"></i></div>
                </div>
              </div>

              <!-- MODO VISTA COMPLETA -->
              <div id="parks-mode-full" class="d-none">
                <div class="tops-filters-bar mb-3">
                  <div class="tops-filters-grid tops-filters-grid--2col">
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-sort me-1"></i>Ordenar</label>
                      <select id="parks-sort" class="tops-filter-select">
                        <option value="rank">Mi Ranking</option>
                        <option value="name">Nombre A-Z</option>
                        <option value="coasters">Nº Coasters ↓</option>
                      </select>
                    </div>
                    <div class="tops-filter-item">
                      <label class="tops-filter-label"><i class="fa-solid fa-earth-europe me-1"></i>País</label>
                      <select id="parks-filter-country" class="tops-filter-select">
                        <option value="">Todos los países</option>
                      </select>
                    </div>
                  </div>
                  <div class="tops-filters-footer">
                    <span class="tops-counter-pill">
                      <i class="fa-solid fa-map-location-dot me-1"></i><span id="parks-full-count">0</span> parques
                    </span>
                    <div class="btn-group btn-group-sm ms-auto">
                      <button id="parks-view-list" class="btn btn-success rounded-0" title="Lista"><i
                          class="fa-solid fa-list"></i></button>
                      <button id="parks-view-grid" class="btn btn-outline-secondary rounded-0" title="Cuadrícula"><i
                          class="fa-solid fa-grip"></i></button>
                    </div>
                  </div>
                </div>
                <div id="top-parks-full-container" class="row g-3"></div>
              </div>

              <!-- MODO EDICIÓN -->
              <div id="parks-mode-edit" class="d-none">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold mb-0"><i class="fa-solid fa-pen me-2"></i>Editar Top Parques</h6>
                  <button class="btn btn-sm btn-success fw-bold rounded-0 shadow-sm" id="btn-save-parks-top">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios
                  </button>
                </div>
                <div class="mb-3">
                  <label for="top-parks-search" class="form-label fw-medium text-uppercase small text-muted">
                    <i class="fa-solid fa-magnifying-glass me-2"></i>Añadir parque
                  </label>
                  <div class="position-relative">
                    <input type="text" id="top-parks-search"
                      class="form-control bg-dark text-white border-secondary rounded-0 pe-5"
                      placeholder="Escribe el nombre del parque..." autocomplete="off">
                    <i class="fa-solid fa-magnifying-glass text-muted position-absolute top-50 end-0 translate-middle-y me-3"
                      id="top-parks-search-icon" style="transition:color .2s;"></i>
                    <ul id="top-parks-dropdown" class="list-group position-absolute w-100 shadow-sm d-none"
                      style="max-height:250px;overflow-y:auto;z-index:1050;border-top:none;top:100%;left:0;"></ul>
                  </div>
                </div>
                <div
                  class="alert alert-success bg-success bg-opacity-10 border-success text-success fw-medium small rounded-0 mb-2">
                  <i class="fa-solid fa-grip-lines me-2"></i>Arrastra para reordenar · <i
                    class="fa-solid fa-trash ms-2 me-1 text-danger"></i>para eliminar
                </div>
                <div id="top-parks-list-edit" style="min-height:80px;"></div>
              </div>

            </div><!-- /top-parks-pane -->

          </div><!-- /tab-content -->
        </div><!-- /card-body -->
      </div><!-- /card principal tops -->

      <!-- ── Leyenda de estadísticas (debajo, 2 columnas dentro) ─ -->
      <div class="card profile-card mb-4" id="tops-stats-sidebar"
        style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;"
        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 15px rgba(0,0,0,0.3)';"
        onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-chart-bar fs-5"></i>
          <h5 class="fw-bold mb-0">Mis Estadísticas</h5>
        </div>
        <div class="card-body p-3">
          <div class="row g-4">
            <!-- Países -->
            <div class="col-md-6">
              <div class="tops-legend-title">
                <i class="fa-solid fa-earth-europe"></i>
                Coasters por País
              </div>
              <div id="tops-legend-countries" class="tops-legend-list">
                <div class="text-center text-muted small py-3"><i class="fa-solid fa-spinner fa-spin"></i></div>
              </div>
            </div>
            <!-- Fabricantes -->
            <div class="col-md-6">
              <div class="tops-legend-title">
                <i class="fa-solid fa-industry"></i>
                Coasters por Fabricante
              </div>
              <div id="tops-legend-manufacturers" class="tops-legend-list">
                <div class="text-center text-muted small py-3"><i class="fa-solid fa-spinner fa-spin"></i></div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /stats sidebar -->

    </div><!-- /section-tops-content -->

    <div class="col-lg-8 col-md-7 d-none" id="section-reviews-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-pen-to-square fs-5"></i>
            <h5 class="fw-bold mb-0">Mis Reseñas</h5>
          </div>
          <span class="tops-counter-pill" id="reviews-total-pill" style="display:none;">
            <i class="fa-solid fa-pen-to-square me-1"></i>
            <span id="reviews-total-count">0</span> reseñas
          </span>
        </div>
        <div class="card-body p-3">
          <!-- Filtros -->
          <div class="d-flex align-items-end gap-3 mb-3">
            <div style="flex:1;min-width:120px;">
              <label class="tops-filter-label"><i class="fa-solid fa-filter me-1"></i>Tipo</label>
              <select id="reviews-type-filter" class="tops-filter-select w-100">
                <option value="">Todas</option>
                <option value="coaster">Coasters</option>
                <option value="park">Parques</option>
              </select>
            </div>
            <div style="flex:1;min-width:120px;">
              <label class="tops-filter-label"><i class="fa-solid fa-sort me-1"></i>Ordenar</label>
              <select id="reviews-sort" class="tops-filter-select w-100">
                <option value="date_desc">Más recientes</option>
                <option value="date_asc">Más antiguas</option>
                <option value="rating_desc">Mejor valoradas</option>
                <option value="rating_asc">Peor valoradas</option>
              </select>
            </div>
          </div>
          <!-- Lista y paginación -->
          <div id="reviews-list"></div>
          <div id="reviews-pagination" class="d-flex justify-content-center gap-2 mt-3"></div>
        </div>
      </div>
    </div>

    <!-- Sección Mi Mapa -->
    <div class="col-lg-8 col-md-7 d-none" id="section-map-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-map-location-dot fs-5"></i>
            <h5 class="fw-bold mb-0">Mi Mapa</h5>
          </div>
          <span class="tops-counter-pill" id="map-parks-pill" style="display:none;">
            <i class="fa-solid fa-map-pin me-1"></i>
            <span id="map-parks-count">0</span> parques
          </span>
        </div><!-- /card-header -->
        <div class="card-body p-0 position-relative">
          <!-- Barra de progreso geocoding -->
          <div id="map-geocoding-bar" class="d-none"
            style="padding: 12px 16px; background: rgba(16,185,129,0.08); border-bottom: 1px solid rgba(16,185,129,0.15);">
            <div class="d-flex align-items-center gap-3">
              <i class="fa-solid fa-spinner fa-spin text-success"></i>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between mb-1">
                  <small class="text-muted fw-medium" id="map-geocoding-status">Localizando parques...</small>
                  <small class="text-success fw-bold" id="map-geocoding-progress">0 / 0</small>
                </div>
                <div class="progress" style="height: 4px; background: rgba(255,255,255,0.1);">
                  <div class="progress-bar bg-success" id="map-geocoding-progressbar"
                    style="width:0%; transition: width 0.4s;"></div>
                </div>
              </div>
            </div>
          </div>
          <!-- Contenedor del mapa -->
          <div id="profile-map"></div>
          <!-- Empty state si no hay parques -->
          <div id="map-empty-state" class="d-none text-center py-5">
            <i class="fa-solid fa-map-location-dot fs-1 mb-3 opacity-25 d-block"></i>
            <p class="text-muted">Añade montañas rusas a tu top para ver los parques en el mapa.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8 col-md-7 d-none" id="section-friends-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-users fs-5"></i>
            <h5 class="fw-bold mb-0">Mis Amigos</h5>
          </div>
          <span class="tops-counter-pill" id="friends-total-pill" style="display:none;">
            <i class="fa-solid fa-users me-1"></i>
            <span id="friends-total-count">0</span> amigos
          </span>
        </div>
        <div class="card-body p-3">
          <!-- Filtros -->
          <div class="d-flex align-items-end gap-3 mb-3">
            <div style="flex:1;min-width:120px;">
              <label class="tops-filter-label"><i class="fa-solid fa-search me-1"></i>Buscar amigos:</label>
              <input type="text" id="friends-search" placeholder="Escribe el nombre del usuario..."
                class="tops-filter-select w-100">
            </div>
            <div style="flex:1;min-width:120px;">
              <label class="tops-filter-label"><i class="fa-solid fa-sort me-1"></i>Ordenar</label>
              <select id="friends-sort" class="tops-filter-select w-100">
                <option value="date_desc">Más recientes</option>
                <option value="date_asc">Más antiguos</option>
                <option value="credits_desc">Mayor nº de credits</option>
                <option value="name_asc">Orden alfabético</option>
              </select>
            </div>
          </div>
          <!-- Lista y paginación -->
          <div id="friends-list"></div>
          <div id="friends-pagination" class="d-flex justify-content-center gap-2 mt-3"></div>
        </div>
      </div>
    </div>

    <!-- MODAL ESTADÍSTICAS AMPLIADAS -->
    <style>
      /* Fix para el bug visual de Chrome con backdrop-filter cortado en los bordes */
      #statsExpandedModal::before {
        content: "";
        position: fixed;
        top: -50px;
        left: -50px;
        right: -50px;
        bottom: -50px;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        z-index: -1;
        pointer-events: none;
      }
    </style>
    <div class="modal fade" id="statsExpandedModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-0"
          style="background: #0d1117; box-shadow: 0 0 60px rgba(0,0,0,0.8), 0 0 0 1px rgba(16,185,129,0.15);">

          <!-- HEADER -->
          <div class="modal-header border-0 px-4 py-3"
            style="background: linear-gradient(135deg, rgba(16,185,129,0.12) 0%, rgba(0,0,0,0) 70%); border-bottom: 1px solid rgba(16,185,129,0.2) !important;">
            <div class="d-flex align-items-center gap-3">
              <div class="d-flex align-items-center justify-content-center rounded-0"
                style="width:40px;height:40px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);">
                <i class="fa-solid fa-chart-line text-success fs-5"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bold text-white mb-0" style="letter-spacing:0.5px;">Estadísticas
                  Ampliadas
                </h5>
                <p class="text-muted mb-0" style="font-size:0.75rem;">Resumen completo de tu actividad como
                  enthusiast
                </p>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white opacity-50" data-bs-dismiss="modal"
              aria-label="Close"></button>
          </div>

          <div class="modal-body p-0">

            <!-- HERO STATS -->
            <div class="row g-0" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
              <div class="col-4 text-center py-4 px-3 position-relative"
                style="border-right: 1px solid rgba(255,255,255,0.06);">
                <div class="mb-2" style="font-size:2rem;line-height:1;">
                  <i class="fa-solid fa-ticket"
                    style="color:rgba(16,185,129,0.7);filter:drop-shadow(0 0 8px rgba(16,185,129,0.4));"></i>
                </div>
                <div class="fw-black text-white" id="modal-stat-total-coasters"
                  style="font-size:2.8rem;line-height:1;font-weight:900;letter-spacing:-1px;">0</div>
                <div class="text-uppercase fw-bold mt-1"
                  style="font-size:0.65rem;letter-spacing:2px;color:rgba(255,255,255,0.35);">Coaster Credits
                </div>
              </div>
              <div class="col-4 text-center py-4 px-3 position-relative"
                style="border-right: 1px solid rgba(255,255,255,0.06);">
                <div class="mb-2" style="font-size:2rem;line-height:1;">
                  <i class="fa-solid fa-map-location-dot"
                    style="color:rgba(16,185,129,0.7);filter:drop-shadow(0 0 8px rgba(16,185,129,0.4));"></i>
                </div>
                <div class="fw-black text-white" id="modal-stat-total-parks"
                  style="font-size:2.8rem;line-height:1;font-weight:900;letter-spacing:-1px;">0</div>
                <div class="text-uppercase fw-bold mt-1"
                  style="font-size:0.65rem;letter-spacing:2px;color:rgba(255,255,255,0.35);">Parques Visitados
                </div>
              </div>
              <div class="col-4 text-center py-4 px-3">
                <div class="mb-2" style="font-size:2rem;line-height:1;">
                  <i class="fa-solid fa-earth-americas"
                    style="color:rgba(16,185,129,0.7);filter:drop-shadow(0 0 8px rgba(16,185,129,0.4));"></i>
                </div>
                <div class="fw-black text-white" id="modal-stat-total-countries"
                  style="font-size:2.8rem;line-height:1;font-weight:900;letter-spacing:-1px;">0</div>
                <div class="text-uppercase fw-bold mt-1"
                  style="font-size:0.65rem;letter-spacing:2px;color:rgba(255,255,255,0.35);">Países Visitados
                </div>
              </div>
            </div>

            <!-- LISTAS PAÍS / FABRICANTE -->
            <div class="row g-0" style="border-bottom: 1px solid rgba(255,255,255,0.06);">

              <!-- Países -->
              <div class="col-lg-6" style="border-right: 1px solid rgba(255,255,255,0.06);">
                <div class="px-4 pt-4 pb-2 d-flex align-items-center gap-2">
                  <i class="fa-solid fa-earth-europe text-success" style="font-size:0.9rem;"></i>
                  <span class="fw-bold text-white"
                    style="font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;">Coasters por
                    País</span>
                </div>
                <div id="modal-list-countries" style="max-height:280px;overflow-y:auto;">
                  <!-- Dinámico -->
                </div>
              </div>

              <!-- Fabricantes -->
              <div class="col-lg-6">
                <div class="px-4 pt-4 pb-2 d-flex align-items-center gap-2">
                  <i class="fa-solid fa-industry text-success" style="font-size:0.9rem;"></i>
                  <span class="fw-bold text-white"
                    style="font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;">Coasters por
                    Fabricante</span>
                </div>
                <div id="modal-list-manufacturers" style="max-height:280px;overflow-y:auto;">
                  <!-- Dinámico -->
                </div>
              </div>

            </div>

            <!-- MÉTRICAS TOP COASTERS -->
            <div class="px-4 pt-4 pb-2 d-flex align-items-center gap-2">
              <i class="fa-solid fa-bolt text-success" style="font-size:0.9rem;"></i>
              <span class="fw-bold text-white"
                style="font-size:0.8rem;letter-spacing:1px;text-transform:uppercase;">Métricas de las Coasters
                Top</span>
            </div>
            <div class="row g-0 px-3 pb-4">

              <!-- Más alta -->
              <div class="col-6 col-md p-2">
                <div class="h-100 text-center p-3 rounded-0"
                  style="background:rgba(16,185,129,0.05);border:1px solid rgba(16,185,129,0.15);transition:background .2s;"
                  onmouseover="this.style.background='rgba(16,185,129,0.1)'"
                  onmouseout="this.style.background='rgba(16,185,129,0.05)'">
                  <div class="mb-2"><i class="fa-solid fa-arrow-up-long text-success"></i></div>
                  <div class="fw-bold text-white mb-1" id="max-stat-height" style="font-size:1.4rem;line-height:1.1;">
                    <i class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i>
                  </div>
                  <div class="small text-muted text-truncate" id="max-stat-height-name" style="font-size:0.72rem;">...
                  </div>
                  <div class="text-uppercase mt-2"
                    style="font-size:0.6rem;letter-spacing:1.5px;color:rgba(255,255,255,0.25);">Más Alta</div>
                </div>
              </div>

              <!-- Más rápida -->
              <div class="col-6 col-md p-2">
                <div class="h-100 text-center p-3 rounded-0"
                  style="background:rgba(245,158,11,0.05);border:1px solid rgba(245,158,11,0.15);transition:background .2s;"
                  onmouseover="this.style.background='rgba(245,158,11,0.1)'"
                  onmouseout="this.style.background='rgba(245,158,11,0.05)'">
                  <div class="mb-2"><i class="fa-solid fa-gauge-high" style="color:#f59e0b;"></i></div>
                  <div class="fw-bold text-white mb-1" id="max-stat-speed" style="font-size:1.4rem;line-height:1.1;">
                    <i class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i>
                  </div>
                  <div class="small text-muted text-truncate" id="max-stat-speed-name" style="font-size:0.72rem;">...
                  </div>
                  <div class="text-uppercase mt-2"
                    style="font-size:0.6rem;letter-spacing:1.5px;color:rgba(255,255,255,0.25);">Más Rápida
                  </div>
                </div>
              </div>

              <!-- Más larga -->
              <div class="col-6 col-md p-2">
                <div class="h-100 text-center p-3 rounded-0"
                  style="background:rgba(59,130,246,0.05);border:1px solid rgba(59,130,246,0.15);transition:background .2s;"
                  onmouseover="this.style.background='rgba(59,130,246,0.1)'"
                  onmouseout="this.style.background='rgba(59,130,246,0.05)'">
                  <div class="mb-2"><i class="fa-solid fa-ruler-horizontal" style="color:#3b82f6;"></i></div>
                  <div class="fw-bold text-white mb-1" id="max-stat-length" style="font-size:1.4rem;line-height:1.1;">
                    <i class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i>
                  </div>
                  <div class="small text-muted text-truncate" id="max-stat-length-name" style="font-size:0.72rem;">...
                  </div>
                  <div class="text-uppercase mt-2"
                    style="font-size:0.6rem;letter-spacing:1.5px;color:rgba(255,255,255,0.25);">Más Larga
                  </div>
                </div>
              </div>

              <!-- Más inversiones -->
              <div class="col-6 col-md p-2">
                <div class="h-100 text-center p-3 rounded-0"
                  style="background:rgba(168,85,247,0.05);border:1px solid rgba(168,85,247,0.15);transition:background .2s;"
                  onmouseover="this.style.background='rgba(168,85,247,0.1)'"
                  onmouseout="this.style.background='rgba(168,85,247,0.05)'">
                  <div class="mb-2"><i class="fa-solid fa-rotate-right" style="color:#a855f7;"></i></div>
                  <div class="fw-bold text-white mb-1" id="max-stat-inversions"
                    style="font-size:1.4rem;line-height:1.1;">
                    <i class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i>
                  </div>
                  <div class="small text-muted text-truncate" id="max-stat-inversions-name" style="font-size:0.72rem;">
                    ...</div>
                  <div class="text-uppercase mt-2"
                    style="font-size:0.6rem;letter-spacing:1.5px;color:rgba(255,255,255,0.25);">Más Invers.
                  </div>
                </div>
              </div>

              <!-- Más antigua -->
              <div class="col-6 col-md p-2">
                <div class="h-100 text-center p-3 rounded-0"
                  style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.15);transition:background .2s;"
                  onmouseover="this.style.background='rgba(239,68,68,0.1)'"
                  onmouseout="this.style.background='rgba(239,68,68,0.05)'">
                  <div class="mb-2"><i class="fa-solid fa-hourglass-start" style="color:#ef4444;"></i></div>
                  <div class="fw-bold text-white mb-1" id="max-stat-year" style="font-size:1.4rem;line-height:1.1;">
                    <i class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i>
                  </div>
                  <div class="small text-muted text-truncate" id="max-stat-year-name" style="font-size:0.72rem;">...
                  </div>
                  <div class="text-uppercase mt-2"
                    style="font-size:0.6rem;letter-spacing:1.5px;color:rgba(255,255,255,0.25);">Más Antigua
                  </div>
                </div>
              </div>

            </div>


          </div><!-- /modal-body -->
        </div>
      </div>
    </div>

  </div>
</main>



<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="<?= Router::asset('web/js/users/profile.js') ?>"></script>




<?php
require_once __DIR__ . '/../../partials/footer.php';
?>