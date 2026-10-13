<?php
$page_css = [
  'web/css/profile.css?v=' . time(),
  'web/css/coasters.css',
  'web/css/trips.css',
  'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
  'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
  'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css',
];
require_once __DIR__ . '/../../partials/header.php';
require_once __DIR__ . '/../../partials/modals/trip_modals.php';
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
              <p class="text-muted small mb-0 text-truncate">

                <?php echo htmlspecialchars($user_email); ?>
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Menú Lateral -->
      <div class="card profile-card">
        <div class="list-group list-group-flush profile-menu" id="sidebar-menu">
          <a href="#" id="menu-profile" class="list-group-item list-group-item-action py-3 active fw-medium"><i
              class="fa-solid fa-user me-2 w-20px text-center"></i><span data-i18n="profile.menu.my_profile">Mi
              Perfil</span></a>
          <a href="#" id="menu-config" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-gear me-2 w-20px text-center"></i><span
              data-i18n="profile.menu.settings">Configuración</span></a>
          <a href="#" id="menu-tops" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-list-ol me-2 w-20px text-center"></i><span data-i18n="profile.menu.my_tops">Mis
              tops</span></a>
          <a href="#" id="menu-photos" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-camera me-2 w-20px text-center"></i><span data-i18n="profile.menu.my_photos">Mis
              fotos</span></a>
          <a href="#" id="menu-reviews" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-pen-to-square me-2 w-20px text-center"></i><span
              data-i18n="profile.menu.my_reviews">Mis reseñas</span></a>
          <a href="#" id="menu-friends" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-users me-2 w-20px text-center"></i><span data-i18n="profile.menu.my_friends">Mis
              amigos</span></a>
          <a href="#" id="menu-trips" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-suitcase-rolling me-2 w-20px text-center"></i><span
              data-i18n="profile.menu.my_trips">Mis viajes</span></a>
          <a href="#" id="menu-map" class="list-group-item list-group-item-action py-3"><i
              class="fa-solid fa-map-pin me-2 w-20px text-center"></i><span data-i18n="profile.menu.my_map">Mi
              mapa</span></a>

          <a href="#" class="list-group-item list-group-item-action text-danger mt-1 py-3 border-top signOutBtn"><i
              class="fa-solid fa-arrow-right-from-bracket me-2 w-20px text-center"></i><span
              data-i18n="profile.menu.sign_out">Cerrar sesión</span></a>
        </div>
      </div>
    </div>

    <!-- Columna Derecha: Contenido Dinámico - Mi perfil -->
    <div class="col-lg-8 col-md-7" id="section-profile-content">

      <!-- Sección: Información del Usuario -->
      <div class="card profile-card mb-4 content-section" id="section-info">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-regular fa-id-card fs-5"></i>
          <h5 class="fw-bold mb-0" data-i18n="profile.info.general_data">Datos Generales</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4 mb-5">
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-regular fa-id-badge"></i><span
                    data-i18n="profile.info.full_name">Nombre Completo</span></p>
                <p class="data-value text-truncate" id="full-name">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-solid fa-at"></i><span data-i18n="profile.info.username">Nombre de
                    usuario</span></p>
                <p class="data-value text-truncate" id="username">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-regular fa-envelope"></i><span data-i18n="profile.info.email">Correo
                    electrónico</span></p>
                <p class="data-value text-truncate" id="email" title="<?php echo htmlspecialchars($user_email); ?>">—
                </p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-regular fa-calendar"></i><span
                    data-i18n="profile.info.birth_date">Fecha de nacimiento</span></p>
                <p class="data-value" id="birth-date">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-solid fa-venus-mars"></i><span
                    data-i18n="profile.info.gender">Género</span></p>
                <p class="data-value" id="gender">—</p>
              </div>
            </div>
            <div class="col-sm-6 col-md-4">
              <div class="data-box rounded-0">
                <p class="data-label"><i class="fa-solid fa-location-dot"></i><span
                    data-i18n="profile.info.location">Ubicación</span></p>
                <p class="data-value text-truncate" id="location">—</p>
              </div>
            </div>
          </div>

          <h5 class="fw-bold mb-3 border-bottom pb-2 text-success"><i class="fa-solid fa-heart me-2"></i><span
              data-i18n="profile.info.favorites">Favoritos</span></h5>
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
              <h5 class="fw-bold mb-0" data-i18n="profile.stats.general">Estadísticas Generales</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-bolt me-2 text-success"></i><span
                      data-i18n="profile.stats.coasters">Montañas rusas</span></span>
                  <span class="badge badge-profile fs-6" id="coasters-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-map-location-dot me-2 text-success"></i><span
                      data-i18n="profile.stats.parks">Parques visitados</span></span>
                  <span class="badge badge-profile fs-6" id="parks-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-earth-americas me-2 text-success"></i><span
                      data-i18n="profile.stats.countries">Países</span></span>
                  <span class="badge badge-profile fs-6" id="countries-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-star me-2 text-warning"></i><span
                      data-i18n="profile.stats.total_reviews">Valoraciones totales</span></span>
                  <span class="badge badge-profile fs-6" id="reviews-count">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-3 border-top">
                  <span class="text-muted fw-medium"><i class="fa-solid fa-trophy me-2 text-warning"></i><span
                      data-i18n="profile.stats.ranking">Ranking</span></span>
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
              <h5 class="fw-bold mb-0" data-i18n="profile.stats.technical">Estadísticas Técnicas</h5>
            </div>
            <div class="card-body">
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted" data-i18n="profile.stats.most_visited">País más visitado</span>
                  <span class="fw-bold text-end text-dark ms-3 text-wrap" id="main-country"
                    style="max-width: 60%;">—</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted" data-i18n="profile.stats.fav_manufacturer">Fabricante favorito</span>
                  <span class="fw-bold text-end ms-3 text-dark text-wrap" id="main-manufacturer"
                    style="max-width: 60%;">—</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted" data-i18n="profile.stats.total_manufacturers">Fabricantes totales</span>
                  <span class="fw-bold text-end ms-3 text-dark" id="total-manufacturers">0</span>
                </li>
                <li
                  class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2 border-top mt-2 pt-3">
                  <span class="text-muted" data-i18n="profile.stats.total_height">Altura total superada</span>
                  <span class="fw-bold text-end ms-3 text-dark fs-5"><span id="total-height">0</span><small
                      class="text-muted fs-6">
                      m</small></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted" data-i18n="profile.stats.total_inversions">Inversiones totales</span>
                  <span class="fw-bold text-end ms-3 text-dark fs-5" id="total-investments">0</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted" data-i18n="profile.stats.fastest">Más rápida</span>
                  <span class="fw-bold text-end ms-3 text-dark text-wrap" id="fastest-coaster"
                    style="max-width: 60%;">—</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 py-2">
                  <span class="text-muted" data-i18n="profile.stats.longest">Más larga</span>
                  <span class="fw-bold text-end ms-3 text-dark text-wrap" id="longest-coaster"
                    style="max-width: 60%;">—</span>
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
        <div class="card-header rounded-0 pt-3 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-user-pen fs-5"></i>
            <h5 class="fw-bold mb-0" data-i18n="profile.config.personal_data">Datos Personales</h5>
          </div>
          <div class="profile-photo">
            <button id="change-avatar-btn" class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-camera me-2"></i><span data-i18n="profile.config.edit_photo">Editar foto de
                perfil</span>
            </button>
            <input type="file" id="avatar-input" accept="image/*" class="d-none">
          </div>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <label for="config-user-name" class="form-label"><i class="fa-regular fa-id-badge me-2"></i><span
                  data-i18n="profile.config.full_name">Nombre completo</span></label>
              <input type="text" id="config-user-name" class="form-control rounded-0">
            </div>
            <div class="col-md-6">
              <label for="config-user-username" class="form-label"><i class="fa-solid fa-at me-2"></i><span
                  data-i18n="profile.config.username">Nombre de usuario</span></label>
              <input type="text" id="config-user-username" class="form-control rounded-0">
            </div>
            <div class="col-md-6">
              <label for="config-user-email" class="form-label"><i class="fa-regular fa-envelope me-2"></i><span
                  data-i18n="profile.config.email">Correo Electrónico</span></label>
              <input type="email" id="config-user-email" class="form-control rounded-0"
                value="<?= htmlspecialchars($user_email) ?>" disabled>
            </div>
            <div class="col-md-6">
              <label for="config-user-birthdate" class="form-label"><i class="fa-regular fa-calendar me-2"></i><span
                  data-i18n="profile.config.birth_date">Fecha de nacimiento</span></label>
              <div class="position-relative">
                <input type="text" id="config-user-birthdate" class="form-control rounded-0" placeholder="DD/MM/AAAA"
                  autocomplete="off" readonly>
                <i class="fa-regular fa-calendar position-absolute top-50 end-0 translate-middle-y me-3 text-muted"
                  style="pointer-events:none;"></i>
              </div>
            </div>
            <div class="col-md-4">
              <label for="config-user-gender" class="form-label"><i class="fa-solid fa-venus-mars me-2"></i><span
                  data-i18n="profile.config.gender">Género</span></label>
              <select id="config-user-gender" class="form-select rounded-0">
                <option value="" data-i18n="profile.config.select_gender">Seleccionar género</option>
                <option value="Masculino" data-i18n="profile.config.male">Masculino</option>
                <option value="Femenino" data-i18n="profile.config.female">Femenino</option>
                <option value="Otro" data-i18n="profile.config.other">Otro</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="config-user-city" class="form-label"><i class="fa-solid fa-building me-2"></i><span
                  data-i18n="profile.config.city">Ciudad</span></label>
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
              <label for="config-user-country" class="form-label"><i class="fa-solid fa-earth-americas me-2"></i><span
                  data-i18n="profile.config.country">País</span></label>
              <input type="text" id="config-user-country" class="form-control rounded-0" placeholder="País" disabled>
            </div>
          </div>
        </div>
      </div>

      <!-- Card: Preferencias -->
      <div class="card profile-card mb-4 content-section" id="section-config-prefs">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-heart fs-5 text-danger"></i>
          <h5 class="fw-bold mb-0" data-i18n="profile.config.fav_attractions">Atracciones favoritas</h5>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <label for="top-coaster-user" class="form-label"><i class="fa-solid fa-star text-warning me-2"></i><span
                  data-i18n="profile.config.fav_coaster">Coaster Favorita</span></label>
              <div class="position-relative">
                <input type="text" id="top-coaster-user" class="form-control rounded-0" placeholder="Desconocida"
                  disabled>
                <span id="top-coaster-loading"
                  class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted small d-none"
                  style="z-index: 5;">
                  <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
              </div>
              <div class="form-text mt-2 ms-1 fw-medium text-muted"><i class="fa-solid fa-circle-info me-1"></i><span
                  data-i18n="profile.config.fav_coaster_hint">Se configura automáticamente desde tus tops</span></div>
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
                <span data-i18n="profile.config.home_park_hint">Busca tu parque más cercano</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="guardar-config-btn d-flex justify-content-end align-items-center mt-4 mb-5">
        <h3 id="msg-guardar-config" class="text-success mb-0 me-4 d-none fw-bold" style="font-size: 1.1rem;"></h3>
        <button type="button" id="guardar-config-btn" class="btn btn-success btn-lg rounded-0 px-5 fw-bold shadow-sm">
          <i class="fa-solid fa-floppy-disk me-2"></i><span data-i18n="profile.config.save">Guardar configuración</span>
        </button>
      </div>

      <!-- Sección: Seguridad (Contraseña) -->
      <div class="card profile-card mb-4 content-section" id="section-security">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-shield-halved fs-5"></i>
          <h5 class="fw-bold mb-0" data-i18n="profile.security.title">Seguridad de la cuenta</h5>
        </div>
        <div class="card-body p-4">
          <div class="d-flex align-items-center mb-4">
            <i class="fa-brands fa-google text-muted fs-4 me-3"></i>
            <div>
              <p class="mb-0 fw-bold">ID de Firebase</p>

              <p class="text-muted mb-0 small font-monospace">
                <?php echo htmlspecialchars($user_uid); ?>
              </p>
            </div>
          </div>

          <button id="toggleFormPassword" class="btn btn-outline-success rounded-0 mb-3 px-4 fw-medium">
            <i class="fa-solid fa-key me-2"></i><span data-i18n="profile.security.change_password">Cambiar
              contraseña</span>
          </button>

          <div id="form-password" class="bg-rcw-card p-4 rounded-0 border-0 mb-3 border-rcw"
            style="display: none; border-width: 2px;">
            <div class="row g-4 mt-1">
              <div class="col-12 mt-0">
                <label for="old-password" class="form-label"><i class="fa-solid fa-key me-2"></i><span
                    data-i18n="profile.security.current_password">Contraseña actual</span></label>
                <input type="password" id="old-password" class="form-control rounded-0"
                  placeholder="Tu contraseña actual">
              </div>
              <div class="col-md-6">
                <label for="nueva-password" class="form-label"><i class="fa-solid fa-lock me-2"></i><span
                    data-i18n="profile.security.new_password">Nueva contraseña</span></label>
                <input type="password" id="nueva-password" class="form-control rounded-0"
                  placeholder="Mínimo 6 caracteres">
                <div class="form-text ms-1 mt-2 text-muted"><i class="fa-solid fa-circle-exclamation me-1"></i> Mínimo 6
                  caracteres</div>
              </div>
              <div class="col-md-6">
                <label for="confirmar-password" class="form-label"><i class="fa-solid fa-lock-open me-2"></i><span
                    data-i18n="profile.security.confirm_password">Confirmar contraseña</span></label>
                <input type="password" id="confirmar-password" class="form-control rounded-0"
                  placeholder="Repite la contraseña">
              </div>
              <div class="col-12 mt-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center"><button id="cambiarPassword"
                    class="btn btn-success me-2 px-4 fw-bold shadow-sm rounded-0"><i
                      class="fa-solid fa-check me-2"></i><span data-i18n="profile.security.save_changes">Guardar
                      cambios</span></button><button id="btn-cancelar-password"
                    class="btn btn-outline-secondary px-4 fw-bold rounded-0 border-rcw text-rcw"
                    data-i18n="common.cancel">Cancelar</button></div>
                <a href="#" id="forgotPasswordBtn" class="text-success small fw-medium text-decoration-none"><i
                    class="fa-solid fa-envelope me-1"></i><span data-i18n="profile.security.forgot_password">¿Has
                    olvidado tu contraseña?</span></a>
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
              <h5 class="text-danger fw-bold mb-1"><i class="fa-solid fa-triangle-exclamation me-2"></i><span
                  data-i18n="profile.security.danger_zone">Zona de peligro</span></h5>
              <p class="text-danger opacity-75 mb-0 small fw-medium" data-i18n="profile.security.danger_desc">Esta
                acción es irreversible y eliminará todos tus datos permanentemente.</p>
            </div>
            <button id="borrarCuenta" class="btn btn-danger text-nowrap fw-bold shadow-sm px-4 rounded-0">
              <i class="fa-solid fa-user-xmark me-2"></i><span data-i18n="profile.security.delete_account">Eliminar
                cuenta</span>
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
        <div class="card-header rounded-0 pt-3 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-ol fs-5"></i>
            <h5 class="fw-bold mb-0" data-i18n="profile.tops.title">Mis Tops</h5>
          </div>
          <div class="d-flex flex-wrap gap-2" id="tops-header-actions">
            <a href="<?= Router::url('top_personal') ?>" id="btn-tops-full-view"
              class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-expand me-1"></i><span data-i18n="profile.tops.full_view">Ver Top Completo</span>
            </a>
            <a href="<?= Router::url('edit_top') ?>" id="btn-tops-edit"
              class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-pen-to-square me-1"></i><span data-i18n="profile.tops.edit">Editar Top</span>
            </a>
          </div>
          <div class="d-none" id="tops-back-btn-wrap">
            <button id="btn-tops-back" class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold shadow-sm">
              <i class="fa-solid fa-arrow-left me-1"></i><span data-i18n="common.back">Volver</span>
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
                <div id="top-coasters-preview-list"
                  class="list-group list-group-flush bg-transparent tops-preview-scroll">
                  <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin fs-4"></i></div>
                </div>
              </div>

              <!-- MODO VISTA COMPLETA -->
              <div id="coasters-mode-full" class="d-none">
                <div class="tops-filters-bar mb-3">
                  <!-- Cabecera colapsable solo móvil -->
                  <button class="tops-filters-mobile-toggle" id="coasters-filters-toggle" aria-expanded="false"
                    aria-controls="coasters-filters-collapsible">
                    <span>
                      <i class="fa-solid fa-sliders me-2" style="color: var(--rcw-green);"></i>
                      Filtros y criterio
                      <span class="tops-filters-active-badge d-none" id="coasters-filters-badge"></span>
                    </span>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                  </button>
                  <div class="tops-filters-collapsible" id="coasters-filters-collapsible">
                    <div class="tops-filters-grid">
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i
                            class="fa-solid fa-arrow-down-short-wide"></i>Criterio</label>
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
                          <button id="coasters-sort-dir" class="tops-sort-dir-btn" title="Dirección" data-dir="asc">
                            <i class="fa-solid fa-caret-up"></i>
                          </button>
                        </div>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-tree-city"></i>Parque</label>
                        <select id="coasters-filter-park" class="tops-filter-select">
                          <option value="">Todos</option>
                        </select>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-earth-americas"></i>País</label>
                        <select id="coasters-filter-country" class="tops-filter-select">
                          <option value="">Todos</option>
                        </select>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-industry"></i>Fabricante</label>
                        <select id="coasters-filter-manufacter" class="tops-filter-select">
                          <option value="">Todos</option>
                        </select>
                      </div>
                      <div class="tops-filter-item">
                        <label class="tops-filter-label"><i class="fa-solid fa-gears"></i>Modelo</label>
                        <select id="coasters-filter-model" class="tops-filter-select">
                          <option value="">Todos</option>
                        </select>
                      </div>
                    </div>
                  </div><!-- /tops-filters-collapsible -->
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
                      <button id="coasters-view-mini" class="btn btn-outline-secondary rounded-0" title="Vista compacta"
                        aria-label="Vista mini compacta"><i class="fa-solid fa-bars"></i></button>
                      <button id="coasters-view-list" class="btn btn-success rounded-0" title="Lista"
                        aria-label="Vista lista"><i class="fa-solid fa-list"></i></button>
                      <button id="coasters-view-grid" class="btn btn-outline-secondary rounded-0" title="Cuadrícula"
                        aria-label="Vista cuadrícula"><i class="fa-solid fa-grip"></i></button>
                    </div>
                  </div>
                </div>
                <div class="tops-full-grid-wrapper ps-2 pe-3">
                  <div id="top-coasters-full-container" class="row g-3"></div>
                </div>
              </div>

              <!-- MODO EDICIÓN -->
              <div id="coasters-mode-edit" class="d-none">
                <div class="d-flex justify-content-end mb-3">
                  <button class="btn btn-sm btn-success fw-bold rounded-0 shadow-sm" id="btn-save-coasters-top">
                    <i class="fa-solid fa-floppy-disk me-2"></i><span data-i18n="profile.tops.save_changes">Guardar
                      Cambios</span>
                  </button>
                </div>
                <div class="mb-3">
                  <label for="top-coasters-search" class="form-label fw-medium text-uppercase small text-muted">
                    <i class="fa-solid fa-magnifying-glass me-2"></i><span data-i18n="profile.tops.add_coaster">Añadir
                      montaña rusa</span>
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
                <div id="top-parks-preview-list" class="list-group list-group-flush bg-transparent tops-preview-scroll">
                  <div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin fs-4"></i></div>
                </div>
              </div>

              <!-- MODO VISTA COMPLETA -->
              <div id="parks-mode-full" class="d-none">
                <div class="tops-filters-bar mb-3">
                  <!-- Cabecera colapsable solo móvil -->
                  <button class="tops-filters-mobile-toggle" id="parks-filters-toggle" aria-expanded="false"
                    aria-controls="parks-filters-collapsible">
                    <span>
                      <i class="fa-solid fa-sliders me-2" style="color: var(--rcw-green);"></i>
                      Filtros y criterio
                      <span class="tops-filters-active-badge d-none" id="parks-filters-badge"></span>
                    </span>
                    <i class="fa-solid fa-chevron-down toggle-arrow"></i>
                  </button>
                  <div class="tops-filters-collapsible" id="parks-filters-collapsible">
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
                  </div><!-- /parks-filters-collapsible -->
                  <div class="tops-filters-footer">
                    <span class="tops-counter-pill">
                      <i class="fa-solid fa-map-location-dot me-1"></i><span id="parks-full-count">0</span> parques
                    </span>
                    <div class="btn-group btn-group-sm ms-auto">
                      <button id="parks-view-mini" class="btn btn-outline-secondary rounded-0" title="Vista compacta"
                        aria-label="Vista mini compacta"><i class="fa-solid fa-bars"></i></button>
                      <button id="parks-view-list" class="btn btn-success rounded-0" title="Lista"
                        aria-label="Vista lista"><i class="fa-solid fa-list"></i></button>
                      <button id="parks-view-grid" class="btn btn-outline-secondary rounded-0" title="Cuadrícula"
                        aria-label="Vista cuadrícula"><i class="fa-solid fa-grip"></i></button>
                    </div>
                  </div>
                </div>
                <div class="tops-full-grid-wrapper ps-2 pe-3">
                  <div id="top-parks-full-container" class="row g-3"></div>
                </div>
              </div>

              <!-- MODO EDICIÓN -->
              <div id="parks-mode-edit" class="d-none">
                <div class="d-flex justify-content-end mb-3">
                  <button class="btn btn-sm btn-success fw-bold rounded-0 shadow-sm" id="btn-save-parks-top">
                    <i class="fa-solid fa-floppy-disk me-2"></i><span data-i18n="profile.tops.save_changes">Guardar
                      Cambios</span>
                  </button>
                </div>
                <div class="mb-3">
                  <label for="top-parks-search" class="form-label fw-medium text-uppercase small text-muted">
                    <i class="fa-solid fa-magnifying-glass me-2"></i><span data-i18n="profile.tops.add_park">Añadir
                      parque</span>
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
          <h5 class="fw-bold mb-0" data-i18n="profile.tops.my_stats">Mis Estadísticas</h5>
        </div>
        <div class="card-body p-3">
          <div class="row g-4">
            <!-- Países -->
            <div class="col-md-6">
              <div class="tops-legend-title">
                <i class="fa-solid fa-earth-europe"></i>
                <span data-i18n="profile.tops.by_country">Coasters por País</span>
              </div>
              <div id="tops-legend-countries" class="tops-legend-list">
                <div class="text-center text-muted small py-3"><i class="fa-solid fa-spinner fa-spin"></i></div>
              </div>
            </div>
            <!-- Fabricantes -->
            <div class="col-md-6">
              <div class="tops-legend-title">
                <i class="fa-solid fa-industry"></i>
                <span data-i18n="profile.tops.by_manufacturer">Coasters por Fabricante</span>
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
        <div class="card-header rounded-0 pt-3 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-pen-to-square fs-5"></i>
            <h5 class="fw-bold mb-0" data-i18n="profile.reviews.title">Mis Reseñas</h5>
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

    <!-- Sección Mis Viajes -->
    <div class="col-lg-8 col-md-7 d-none" id="section-trips-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-suitcase-rolling fs-5"></i>
            <h5 class="fw-bold mb-0" data-i18n="profile.trips.title">Mis Viajes</h5>
          </div>

          <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="trips-view-toggle" id="tv-list" value="list" checked>
            <label class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold" for="tv-list"><i
                class="fa-solid fa-list me-1"></i><span data-i18n="profile.trips.view_trips">Viajes</span></label>
            <input type="radio" class="btn-check" name="trips-view-toggle" id="tv-stats" value="stats">
            <label class="btn btn-sm btn-outline-success rounded-0 px-3 fw-bold" for="tv-stats"><i
                class="fa-solid fa-chart-pie me-1"></i><span
                data-i18n="profile.trips.view_stats">Estadísticas</span></label>
          </div>

          <a href="<?= Router::url('trips') ?>" class="btn btn-sm btn-success rounded-0 px-3 fw-bold shadow-sm">
            <i class="fa-solid fa-calendar-days me-1"></i><span data-i18n="profile.trips.go_agenda">Ir a Agenda</span>
          </a>
        </div>
        <div class="card-body p-4">

          <!-- Contenedor Vista Viajes -->
          <div id="trips-view-list">
            <div id="trips-grid" class="trips-grid-scrollable"
              style="display:flex; flex-direction:column; align-items:center;">
              <div class="text-center py-4 text-muted small">Cargando viajes...</div>
            </div>
          </div>

          <!-- Contenedor Vista Estadísticas -->
          <div id="trips-view-stats" class="d-none">
            <!-- Estadísticas de Viajes (Unificadas) -->
            <div class="d-flex align-items-center gap-3 mb-2">
              <div class="bg-success bg-opacity-10 p-2 rounded-2 d-flex align-items-center justify-content-center"
                style="width: 40px; height: 40px; flex-shrink: 0;">
                <i class="fa-solid fa-chart-line fs-5 text-success"></i>
              </div>
              <h5 class="fw-bold mb-0 d-flex align-items-center gap-2"
                style="font-size: 1.1rem; flex-wrap: nowrap; white-space: nowrap;">
                Estadísticas de
                <select id="rank-type-select" class="rcw-stats-select">
                  <option value="coasters">Coasters</option>
                  <option value="parks">Parques</option>
                </select>
              </h5>
            </div>
            <div class="mb-3">
              <span class="stats-trip-badge shadow-sm">
                <i class="fa-solid fa-suitcase text-success"></i> <span id="rank-trip-count">0 viajes</span>
              </span>
            </div>
            <div class="d-flex overflow-x-auto gap-1 pb-1 mb-3" id="rank-filter-btns" style="scrollbar-width: none;">
              <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                data-period="week">Semana</button>
              <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                data-period="month">Mes</button>
              <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-success active flex-shrink-0"
                data-period="year">Año</button>
              <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                data-period="custom">Personalizado</button>
              <button class="btn btn-sm rounded-0 rank-period-btn btn-outline-secondary flex-shrink-0"
                data-period="all">Siempre</button>
            </div>
            <div
              class="d-flex align-items-center flex-wrap gap-2 pt-2 pb-3 mb-3 border-bottom border-secondary border-opacity-25">
              <div class="d-flex align-items-center gap-2 flex-shrink-0" id="rank-nav-container">
                <button class="btn btn-sm btn-outline-secondary rounded-0" id="rank-prev-btn" title="Anterior"><i
                    class="fa-solid fa-chevron-left"></i></button>
                <span id="rank-nav-label" class="fw-bold text-center" style="min-width: 80px;">2026</span>
                <button class="btn btn-sm btn-outline-secondary rounded-0" id="rank-next-btn" title="Siguiente"><i
                    class="fa-solid fa-chevron-right"></i></button>
              </div>
              <div class="d-flex flex-column gap-1" id="rank-dates-container">
                <div class="d-flex align-items-center gap-2">
                  <small class="text-muted" style="width: 40px; white-space: nowrap;">Desde:</small>
                  <input type="text" class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white"
                    id="rank-start-date" style="flex: 1;" placeholder="dd/mm/aaaa" readonly>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <small class="text-muted" style="width: 40px; white-space: nowrap;">Hasta:</small>
                  <input type="text" class="form-control form-control-sm rounded-0 bg-dark border-secondary text-white"
                    id="rank-end-date" style="flex: 1;" placeholder="dd/mm/aaaa" readonly>
                </div>
              </div>
            </div>
            <div id="ranking-container" class="mt-4">
              <div class="text-center py-4"><span
                  class="spinner-border text-success spinner-border-sm me-2"></span>Cargando estadísticas...</div>
            </div>
          </div> <!-- Fin Contenedor Vista Estadísticas -->
        </div>
      </div>
    </div>

    <!-- Sección Mi Mapa -->
    <div class="col-lg-8 col-md-7 d-none" id="section-map-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-map-location-dot fs-5"></i>
            <h5 class="fw-bold mb-0" data-i18n="profile.map.title">Mi Mapa</h5>
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
            <p class="text-muted" data-i18n="profile.map.empty">Añade montañas rusas a tu top para ver los parques en el
              mapa.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8 col-md-7 d-none" id="section-friends-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-users fs-5"></i>
            <h5 class="fw-bold mb-0" data-i18n="profile.friends.title">Mis Amigos</h5>
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
          <div id="friends-list" style="max-height: 520px; overflow-y: auto; padding-right: 4px;"></div>
          <div id="friends-pagination" class="d-flex justify-content-center gap-2 mt-3"></div>
        </div>
      </div>
    </div>

    <!-- SECCIÓN: MIS FOTOS -->
    <div class="col-lg-8 col-md-7 d-none" id="section-photos-content">
      <div class="card profile-card mb-4">
        <div class="card-header rounded-0 pt-3 pb-3 d-flex align-items-center gap-2">
          <i class="fa-solid fa-camera fs-5"></i>
          <h5 class="fw-bold mb-0" data-i18n="profile.photos.title">Mi Galería de Fotos</h5>
        </div>
        <div class="card-body p-4">
          <div class="tops-preview-scroll">
            <div class="row g-3" id="photos-grid-container">
              <!-- Inyectado via JS -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODAL ESTADÍSTICAS AMPLIADAS -->
    <style>
      /* ── Modal base ─────────────────────────────────────────────────── */
      #statsExpandedModal .modal-content {
        background: #0d1117 !important;
        border: 1px solid rgba(16, 185, 129, 0.18) !important;
        border-radius: 0 !important;
        box-shadow: 0 32px 80px -12px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(16, 185, 129, 0.12) !important;
      }

      #statsExpandedModal * {
        border-radius: 0 !important;
      }

      /* ── Header ─────────────────────────────────────────────────────── */
      #statsExpandedModal .sm-header {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(13, 17, 23, 0) 60%);
        border-bottom: 1px solid rgba(16, 185, 129, 0.25) !important;
        padding: 1.5rem 1.75rem 1.25rem;
      }

      #statsExpandedModal .sm-icon-box {
        width: 52px;
        height: 52px;
        background: linear-gradient(135deg, #10b981, #059669);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.35);
      }

      /* ── Body ───────────────────────────────────────────────────────── */
      #statsExpandedModal .modal-body {
        background: #0d1117;
        padding: 1.75rem;
      }

      /* ── Section separators ─────────────────────────────────────────── */
      #statsExpandedModal .sm-section {
        margin-bottom: 2.25rem;
      }

      #statsExpandedModal .sm-section-title {
        font-size: 0.7rem;
        letter-spacing: 2px;
        font-weight: 800;
        color: #10b981;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1.1rem;
        padding-bottom: 0.6rem;
        border-bottom: 1px solid rgba(16, 185, 129, 0.15);
      }

      #statsExpandedModal .sm-section-title i {
        font-size: 0.85rem;
      }

      /* ── Big stat cards ─────────────────────────────────────────────── */
      #statsExpandedModal .sm-big-card {
        background: rgba(22, 27, 34, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 1.25rem 0.75rem;
        text-align: center;
        transition: transform .2s, border-color .2s, box-shadow .2s;
        position: relative;
        overflow: hidden;
      }

      #statsExpandedModal .sm-big-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.04), transparent 60%);
        pointer-events: none;
      }

      #statsExpandedModal .sm-big-card:hover {
        transform: translateY(-3px);
        border-color: rgba(16, 185, 129, 0.3);
        box-shadow: 0 8px 24px -8px rgba(16, 185, 129, 0.2);
      }

      #statsExpandedModal .sm-big-card .sm-val {
        font-size: 2.2rem;
        font-weight: 900;
        color: #fff;
        line-height: 1;
        letter-spacing: -1px;
      }

      #statsExpandedModal .sm-big-card .sm-val.sm-val-md {
        font-size: 1.6rem;
      }

      #statsExpandedModal .sm-big-card .sm-label {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 1.2px;
        color: rgba(255, 255, 255, 0.4);
        text-transform: uppercase;
        margin-top: 0.4rem;
      }

      #statsExpandedModal .sm-big-card .sm-accent-dot {
        width: 28px;
        height: 3px;
        background: #10b981;
        margin: 0.55rem auto 0;
        opacity: 0.6;
      }

      /* ── Wide bar card ──────────────────────────────────────────────── */
      #statsExpandedModal .sm-bar-card {
        background: rgba(22, 27, 34, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
      }

      #statsExpandedModal .sm-bar-card .sm-bar-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        color: rgba(255, 255, 255, 0.65);
        text-transform: uppercase;
        white-space: nowrap;
      }

      #statsExpandedModal .sm-bar-card .sm-bar-track {
        flex: 1;
        height: 8px;
        background: rgba(255, 255, 255, 0.07);
        position: relative;
        overflow: hidden;
      }

      #statsExpandedModal .sm-bar-card .sm-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #059669, #10b981, #34d399);
        background-size: 200% 100%;
        animation: sm-bar-shimmer 2.5s linear infinite;
        transition: width 0.9s cubic-bezier(.4, 0, .2, 1);
      }

      @keyframes sm-bar-shimmer {
        0% {
          background-position: 200% 0;
        }

        100% {
          background-position: -200% 0;
        }
      }

      #statsExpandedModal .sm-bar-card .sm-bar-pct {
        font-size: 1.1rem;
        font-weight: 900;
        color: #10b981;
        min-width: 44px;
        text-align: right;
      }

      /* ── Favorite metric cards ──────────────────────────────────────── */
      #statsExpandedModal .sm-fav-card {
        background: rgba(22, 27, 34, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: border-color .2s, box-shadow .2s;
      }

      #statsExpandedModal .sm-fav-card:hover {
        border-color: rgba(16, 185, 129, 0.3);
        box-shadow: 0 4px 16px -6px rgba(16, 185, 129, 0.2);
      }

      #statsExpandedModal .sm-fav-icon {
        width: 44px;
        height: 44px;
        flex-shrink: 0;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #10b981;
      }

      #statsExpandedModal .sm-fav-tag {
        font-size: 0.6rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        color: #10b981;
        text-transform: uppercase;
        margin-bottom: 0.2rem;
      }

      #statsExpandedModal .sm-fav-name {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
      }

      #statsExpandedModal .sm-fav-sub {
        font-size: 0.78rem;
        color: rgba(255, 255, 255, 0.45);
      }

      /* ── Record cards ───────────────────────────────────────────────── */
      #statsExpandedModal .sm-rec-card {
        background: rgba(22, 27, 34, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 1.1rem 0.75rem;
        text-align: center;
        transition: transform .2s, border-color .2s;
        position: relative;
      }

      #statsExpandedModal .sm-rec-card:hover {
        transform: translateY(-3px);
        border-color: rgba(16, 185, 129, 0.25);
      }

      #statsExpandedModal .sm-rec-icon {
        width: 38px;
        height: 38px;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #10b981;
        font-size: 0.95rem;
        margin-bottom: 0.75rem;
      }

      #statsExpandedModal .sm-rec-val {
        font-size: 1.45rem;
        font-weight: 900;
        color: #fff;
        line-height: 1.1;
      }

      #statsExpandedModal .sm-rec-name {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.4);
        margin-top: 0.25rem;
      }

      #statsExpandedModal .sm-rec-label {
        font-size: 0.6rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        color: #10b981;
        text-transform: uppercase;
        margin-top: 0.6rem;
      }

      /* ── List rows ──────────────────────────────────────────────────── */
      #statsExpandedModal .sm-list-wrap {
        background: rgba(22, 27, 34, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.05);
      }

      #statsExpandedModal .sm-list-head {
        padding: 0.75rem 1rem;
        background: rgba(0, 0, 0, 0.25);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        color: rgba(255, 255, 255, 0.6);
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }
    </style>

    <div class="modal fade" id="statsExpandedModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content overflow-hidden">

          <!-- HEADER -->
          <div class="modal-header border-0 sm-header">
            <div class="d-flex align-items-center gap-3">
              <div class="sm-icon-box">
                <i class="fa-solid fa-chart-pie text-white fs-5"></i>
              </div>
              <div>
                <h5 class="modal-title fw-bolder text-white mb-0" style="font-size:1.35rem;letter-spacing:0.3px;">
                  Estadísticas Ampliadas
                </h5>
                <p class="mb-0" style="font-size:0.82rem;color:rgba(255,255,255,0.45);">
                  Resumen completo de tu trayectoria
                </p>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white opacity-50 ms-auto" data-bs-dismiss="modal"
              aria-label="Cerrar"></button>
          </div>

          <div class="modal-body">

            <!-- ① TOTALES Y ACUMULADOS -->
            <div class="sm-section">
              <div class="sm-section-title">
                <i class="fa-solid fa-calculator"></i>Totales y Acumulados
              </div>

              <!-- Fila principal: 4 grandes -->
              <div class="row g-2 mb-2">
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val" id="modal-stat-total-coasters">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Coaster Credits</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val" id="modal-stat-total-parks">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Parques Visitados</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val" id="modal-stat-total-countries">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Países Visitados</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val" id="modal-stat-total-continents">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Continentes</div>
                  </div>
                </div>
              </div>

              <!-- Fila secundaria: 4 medianas -->
              <div class="row g-2 mb-2">
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val sm-val-md" id="modal-stat-total-manufacturers-count">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Fabricantes Distintos</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val sm-val-md" id="modal-stat-total-length">0 <span
                        style="font-size:1rem;font-weight:600;color:rgba(255,255,255,0.5);">km</span></div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Longitud Total</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val sm-val-md" id="modal-stat-total-inversions">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Inversiones Totales</div>
                  </div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="sm-big-card h-100">
                    <div class="sm-val sm-val-md" id="modal-stat-extinct">0</div>
                    <div class="sm-accent-dot"></div>
                    <div class="sm-label">Coasters Extintas</div>
                  </div>
                </div>
              </div>

              <!-- Barra inversiones -->
              <div class="sm-bar-card">
                <div class="sm-bar-label"><i class="fa-solid fa-rotate-right me-2" style="color:#10b981;"></i>Con
                  inversiones vs sin inversiones</div>
                <div class="sm-bar-track">
                  <div id="modal-stat-inv-bar" class="sm-bar-fill" style="width:0%;"></div>
                </div>
                <div class="sm-bar-pct" id="modal-stat-inv-pct">0%</div>
              </div>
            </div>

            <!-- ② FAVORITOS Y TIPOLOGÍAS -->
            <div class="sm-section">
              <div class="sm-section-title">
                <i class="fa-solid fa-star"></i>Favoritos y Tipologías
              </div>
              <div class="row g-2">
                <div class="col-12 col-md-6">
                  <div class="sm-fav-card h-100">
                    <div class="sm-fav-icon"><i class="fa-solid fa-house"></i></div>
                    <div style="min-width:0;">
                      <div class="sm-fav-tag">Tu parque con más credits</div>
                      <div class="sm-fav-name text-truncate" id="fav-home-park">...</div>
                      <div class="sm-fav-sub" id="fav-home-park-count">0 credits</div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="sm-fav-card h-100">
                    <div class="sm-fav-icon"><i class="fa-solid fa-industry"></i></div>
                    <div style="min-width:0;">
                      <div class="sm-fav-tag">Fabricante Favorito</div>
                      <div class="sm-fav-name text-truncate" id="fav-manufacturer">...</div>
                      <div class="sm-fav-sub" id="fav-manufacturer-count">0% de tus credits</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ③ RÉCORDS PERSONALES -->
            <div class="sm-section">
              <div class="sm-section-title">
                <i class="fa-solid fa-trophy"></i>Récords Personales
              </div>
              <div class="row g-2">
                <div class="col-6 col-md-4">
                  <div class="sm-rec-card h-100">
                    <div class="sm-rec-icon"><i class="fa-solid fa-arrow-up-long"></i></div>
                    <div class="sm-rec-val" id="max-stat-height"><i
                        class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i></div>
                    <div class="sm-rec-name text-truncate" id="max-stat-height-name">...</div>
                    <div class="sm-rec-label">Más Alta</div>
                  </div>
                </div>
                <div class="col-6 col-md-4">
                  <div class="sm-rec-card h-100">
                    <div class="sm-rec-icon"><i class="fa-solid fa-gauge-high"></i></div>
                    <div class="sm-rec-val" id="max-stat-speed"><i
                        class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i></div>
                    <div class="sm-rec-name text-truncate" id="max-stat-speed-name">...</div>
                    <div class="sm-rec-label">Más Rápida</div>
                  </div>
                </div>
                <div class="col-6 col-md-4">
                  <div class="sm-rec-card h-100">
                    <div class="sm-rec-icon"><i class="fa-solid fa-ruler-horizontal"></i></div>
                    <div class="sm-rec-val" id="max-stat-length"><i
                        class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i></div>
                    <div class="sm-rec-name text-truncate" id="max-stat-length-name">...</div>
                    <div class="sm-rec-label">Más Larga</div>
                  </div>
                </div>
                <div class="col-6 col-md-4">
                  <div class="sm-rec-card h-100">
                    <div class="sm-rec-icon"><i class="fa-solid fa-rotate-right"></i></div>
                    <div class="sm-rec-val" id="max-stat-inversions"><i
                        class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i></div>
                    <div class="sm-rec-name text-truncate" id="max-stat-inversions-name">...</div>
                    <div class="sm-rec-label">Más Inversiones</div>
                  </div>
                </div>
                <div class="col-6 col-md-4">
                  <div class="sm-rec-card h-100">
                    <div class="sm-rec-icon"><i class="fa-solid fa-hourglass-start"></i></div>
                    <div class="sm-rec-val" id="max-stat-oldest"><i
                        class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i></div>
                    <div class="sm-rec-name text-truncate" id="max-stat-oldest-name">...</div>
                    <div class="sm-rec-label">Más Antigua</div>
                  </div>
                </div>
                <div class="col-6 col-md-4">
                  <div class="sm-rec-card h-100">
                    <div class="sm-rec-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <div class="sm-rec-val" id="max-stat-newest"><i
                        class="fa-solid fa-spinner fa-spin fs-6 text-muted"></i></div>
                    <div class="sm-rec-name text-truncate" id="max-stat-newest-name">...</div>
                    <div class="sm-rec-label">Más Nueva</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ④ DESGLOSES -->
            <div class="sm-section" style="margin-bottom:0;">
              <div class="sm-section-title">
                <i class="fa-solid fa-list-ul"></i>Desgloses Totales
              </div>
              <div class="row g-2">
                <div class="col-lg-6">
                  <div class="sm-list-wrap h-100">
                    <div class="sm-list-head">
                      <i class="fa-solid fa-earth-europe" style="color:#10b981;"></i>Por País
                    </div>
                    <div id="modal-list-countries" class="p-2" style="max-height:260px;overflow-y:auto;"></div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="sm-list-wrap h-100">
                    <div class="sm-list-head">
                      <i class="fa-solid fa-industry" style="color:#10b981;"></i>Por Fabricante
                    </div>
                    <div id="modal-list-manufacturers" class="p-2" style="max-height:260px;overflow-y:auto;"></div>
                  </div>
                </div>
              </div>
            </div>

          </div><!-- /modal-body -->
        </div>
      </div>
    </div><!-- /statsExpandedModal -->

  </div>
</main>
<!-- MODAL FOTO -->
<div class="modal fade" id="ig-lightbox-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
    <div class="modal-content bg-dark text-white border-secondary rounded-0 overflow-visible position-relative">
      <button id="ig-modal-prev"
        class="btn text-white position-absolute top-50 translate-middle-y rounded-circle px-3 py-2"
        style="z-index: 1055; left: -60px; font-size: 1.5rem; background: rgba(0,0,0,0.5);"><i
          class="fa-solid fa-chevron-left"></i></button>
      <button id="ig-modal-next"
        class="btn text-white position-absolute top-50 translate-middle-y rounded-circle px-3 py-2"
        style="z-index: 1055; right: -60px; font-size: 1.5rem; background: rgba(0,0,0,0.5);"><i
          class="fa-solid fa-chevron-right"></i></button>
      <div class="modal-header border-secondary d-flex align-items-center py-2 px-3">
        <img id="ig-modal-avatar" src="" alt="Avatar" class="rounded-circle me-2"
          style="width:32px; height:32px; object-fit:cover;">
        <div id="ig-modal-avatar-fallback"
          class="d-flex d-none align-items-center justify-content-center text-secondary bg-dark rounded-circle me-2"
          style="width:32px;height:32px;"><i class="fa-solid fa-user"></i></div>
        <span id="ig-modal-username" class="fw-bold fs-6"></span>
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="position:relative;">
        <img id="ig-modal-img" src="" alt="Foto" class="w-100" style="aspect-ratio: 1/1; object-fit:cover;">
        <!-- Flechas móvil — solo visibles en pantallas pequeñas via CSS -->
        <button class="ig-mobile-nav ig-mobile-nav-prev" id="ig-mob-prev" aria-label="Anterior">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="ig-mobile-nav ig-mobile-nav-next" id="ig-mob-next" aria-label="Siguiente">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
      <div class="modal-footer border-secondary flex-column align-items-start py-3 px-3">
        <div class="w-100">
          <span id="ig-modal-caption-user" class="fw-bold text-success me-2"></span>
          <span id="ig-modal-caption" class="text-light"></span>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="<?= Router::asset('web/js/components/trip_modals.js') ?>"></script>
<script src="<?= Router::asset('web/js/users/map.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="<?= Router::asset('web/js/users/profile.js') ?>?v=<?= time() ?>"></script>