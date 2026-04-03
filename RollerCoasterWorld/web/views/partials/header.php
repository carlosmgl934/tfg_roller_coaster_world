<?php
// Inicia sesión (si no está ya iniciada)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Cargar el Router definitivo
require_once __DIR__ . '/../../routes/Router.php';

// Obtener URL base
$base_url = Router::getBaseUrl();

// Páginas públicas que no requieren login
$public_pages = [
  Router::getRoutePath('login'),
  Router::getRoutePath('register'),
  Router::getRoutePath('home'),
  Router::getRoutePath('index'),

  // Coasters — acceso público (sin login)
  Router::getRoutePath('coasters'),
  Router::getRoutePath('coaster_search'),
  Router::getRoutePath('coaster_detail'),
  Router::getRoutePath('coaster_reviews'),
  Router::getRoutePath('coaster_tops'),
  Router::getRoutePath('ranking'),

  // Parques — acceso público (sin login)
  Router::getRoutePath('parks'),
  Router::getRoutePath('park_search'),
  Router::getRoutePath('park_detail'),
  Router::getRoutePath('park_tops'),
  Router::getRoutePath('park_reviews'),

  // Foros — solo búsqueda/listado público (sin login)
  Router::getRoutePath('forum_search'),

  // Páginas legales/info
  Router::getRoutePath('contact'),
  Router::getRoutePath('privacy'),
  Router::getRoutePath('notice'),
];

// Determina si el usuario está logueado
$is_logged = isset($_SESSION['firebase_uid']);
$is_admin = $is_logged && isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

// Obtener iniciales del nombre de usuario para el avatar
$user_display = '';
$user_initials = '?';
if ($is_logged) {
  // Intentar obtener el username desde la sesión o BD
  if (isset($_SESSION['username'])) {
    $user_display = $_SESSION['username'];
  } elseif (isset($_SESSION['user_email'])) {
    $user_display = explode('@', $_SESSION['user_email'])[0];
  }
  if ($user_display) {
    $parts = preg_split('/[\s_\-]+/', trim($user_display));
    $user_initials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1)
      $user_initials .= strtoupper(substr($parts[1], 0, 1));
  }
}

// Comprobar si la página actual es pública o privada
$current_script = $_SERVER['SCRIPT_NAME'];
$is_public = false;

foreach ($public_pages as $page) {
  if (!empty($page) && strpos($current_script, $page) !== false) {
    $is_public = true;
    break;
  }
}

// Redirigir a login si no está logueado y la página es privada
if (!$is_logged && !$is_public) {
  Router::redirect('login');
}

// Evitar que el navegador guarde en caché páginas protegidas
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Google Fonts: Inter + Outfit -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap"
    rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>RollerCoaster World</title>

  <!-- Firebase SDK - versión COMPAT -->
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-storage-compat.js"></script>

  <script>window.BASE_URL = '<?= $base_url ?>';</script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- Firebase auth init (global) -->
  <script src="<?= Router::asset('web/js/auth.js') ?>"></script>

  <!-- Funciones Nav para búsqueda de comunidad -->
  <?php if ($is_logged): ?>
    <script src="<?= Router::asset('web/js/header_friends.js') ?>"></script>
  <?php endif; ?>

  <!-- Design System globals (dark mode tokens + overrides) -->
  <link rel="stylesheet" href="<?= Router::asset('web/css/globals.css') ?>">
  <!-- Navbar + layout -->
  <link rel="stylesheet" href="<?= Router::asset('web/css/header.css') ?>">

</head>

<body>

  <nav class="navbar navbar-expand-xl custom-navbar sticky-top">
    <div class="container-fluid px-3 px-xl-4 pe-3 pe-xl-4">

      <!-- Brand / Logo -->
      <a class="navbar-brand rcw-brand me-3 me-xl-4" href="<?= Router::url('home') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="28" height="28" class="rcw-brand-icon">
          <!-- Pistas -->
          <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" fill="none" stroke="currentColor" stroke-width="4"
            stroke-linecap="round" />
          <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" fill="none" stroke="currentColor" stroke-width="4"
            stroke-linecap="round" />
          <!-- Soportes -->
          <line x1="16" y1="42" x2="16" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
          <line x1="32" y1="20" x2="32" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
          <line x1="48" y1="24" x2="48" y2="60" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
          <line x1="24" y1="28" x2="16" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            opacity="0.6" />
          <line x1="40" y1="16" x2="32" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            opacity="0.6" />
          <!-- Vagón -->
          <rect x="23" y="10" width="10" height="6" rx="2" fill="currentColor" />
          <circle cx="25" cy="18" r="2" fill="currentColor" />
          <circle cx="31" cy="18" r="2" fill="currentColor" />
          <!-- Vagón 2 -->
          <rect x="11" y="24" width="10" height="6" rx="2" fill="currentColor" transform="rotate(-40 16 27)" />
        </svg>
        <span class="rcw-brand-text">RollerCoaster<span class="rcw-brand-accent">World</span></span>
      </a>

      <!-- Botón hamburguesa para móviles -->
      <button class="navbar-toggler border-0 text-white ms-auto" type="button" data-bs-toggle="collapse"
        data-bs-target="#mainMenu">
        <i class="fa-solid fa-bars fs-4"></i>
      </button>

      <!-- Contenido del menú -->
      <div class="collapse navbar-collapse" id="mainMenu">

        <!-- Nav central -->
        <ul class="navbar-nav mx-auto mb-2 mb-xl-0 gap-1 gap-xl-2 fw-semibold align-items-xl-center">

          <!-- Home  -->
          <li class="nav-item">
            <a class="nav-link rcw-nav-pill" href="<?= Router::url('home') ?>">
              <i class="fa-solid fa-house me-1"></i> Home
            </a>
          </li>

          <!-- Coasters -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              Coasters
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= Router::url('coaster_search') ?>"><i
                    class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> Buscar</a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('ranking') ?>"><i
                    class="fa-solid fa-earth-europe w-20px text-center me-2 text-success"></i> Ranking Global</a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('coaster_reviews') ?>"><i
                    class="fa-solid fa-star w-20px text-center me-2 text-warning"></i> Reseñas Globales</a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('coaster_tops') ?>"><i
                    class="fa-solid fa-trophy w-20px text-center me-2 text-info"></i> Tops Usuarios</a></li>
            </ul>
          </li>

          <!-- Parques -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              Parques
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('park_search') ?>"><i
                    class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> Buscar Parque</a>
              </li>
              <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('park_tops') ?>?filter=global"><i
                    class="fa-solid fa-earth-europe w-20px text-center me-2 text-success"></i> Ranking Global</a></li>
              <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('park_tops') ?>?filter=users"><i
                    class="fa-solid fa-users w-20px text-center me-2 text-info"></i> Top Usuarios</a></li>
            </ul>
          </li>

          <!-- Foros -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="fa-solid fa-comments me-1"></i> Foros
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= Router::url('forum_search') ?>"><i
                    class="fa-solid fa-users w-20px text-center me-2 text-primary"></i> Todos los foros</a></li>
            </ul>
          </li>

          <!-- Viajes -->
          <?php if ($is_logged): ?>
            <li class="nav-item dropdown custom-dropdown">
              <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-suitcase-rolling me-1"></i> Viajes
              </a>
              <ul class="dropdown-menu shadow border-0">
                <li><a class="dropdown-item py-2" href="<?= Router::url('trips') ?>"><i
                      class="fa-solid fa-suitcase w-20px text-center me-2 text-warning"></i> Mis viajes</a></li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('trip_generator') ?>"><i
                      class="fa-solid fa-wand-magic-sparkles w-20px text-center me-2 text-danger"></i> Generador de
                    viajes</a></li>
              </ul>
            </li>
          <?php endif; ?>

          <!-- Comunidad / Usuarios -->
          <?php if ($is_logged): ?>
            <li class="nav-item dropdown custom-dropdown">
              <a class="nav-link rcw-nav-pill dropdown-toggle position-relative" href="#" role="button"
                data-bs-toggle="dropdown">
                <i class="fa-solid fa-users me-1"></i> Comunidad
                <span id="nav-comm-badge"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                  style="font-size: 0.65rem; padding: 0.35em 0.5em;">0</span>
              </a>
              <ul class="dropdown-menu shadow border-0">
                <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('user_search') ?>"><i
                      class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> Buscar usuarios</a>
                </li>
                <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('friends') ?>"><i 
                      class="fa-solid fa-user-group w-20px text-center me-2 text-success"></i> Amistades</a>
                </li>
              </ul>
            </li>
          <?php endif; ?>

          <!-- Botón CTA: Mi agenda de Parques (solo logueados) -->
          <?php if ($is_logged): ?>
            <li class="nav-item">
              <a class="nav-link rcw-btn-agenda" href="<?= Router::url('trips') ?>">
                <i class="fa-solid fa-calendar-days me-1"></i> Mi agenda de Parques
              </a>
            </li>
          <?php endif; ?>

        </ul>
        <!-- / Nav central -->

        <!-- Zona derecha: Login/Registro o Avatar de perfil -->
        <div class="d-flex align-items-center gap-2 ms-xl-3">

          <?php if ($is_logged): ?>

            <!-- Admin (solo admins) -->
            <?php if ($is_admin): ?>
              <div class="nav-item dropdown custom-dropdown">
                <a class="nav-link rcw-nav-pill rcw-nav-admin dropdown-toggle" href="#" role="button"
                  data-bs-toggle="dropdown">
                  <i class="fa-solid fa-gear me-1"></i> Admin
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_dashboard') ?>"><i
                        class="fa-solid fa-chart-line w-20px text-center me-2 text-primary"></i> Dashboard</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_users') ?>"><i
                        class="fa-solid fa-users w-20px text-center me-2 text-primary"></i> Usuarios</a></li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_coasters') ?>"><i
                        class="fa-solid fa-train-tram w-20px text-center me-2 text-success"></i> Coasters</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_parks') ?>"><i
                        class="fa-solid fa-tree-city w-20px text-center me-2 text-success"></i> Parques</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_forums') ?>"><i
                        class="fa-solid fa-comments w-20px text-center me-2 text-success"></i> Foros</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_news') ?>"><i
                        class="fa-solid fa-newspaper w-20px text-center me-2 text-success"></i> Noticias</a></li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_messages') ?>"><i
                        class="fa-solid fa-envelope w-20px text-center me-2 text-warning"></i> Mensajes</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_photos') ?>"><i
                        class="fa-solid fa-image w-20px text-center me-2 text-info"></i> Fotos</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_comments') ?>"><i
                        class="fa-solid fa-comment w-20px text-center me-2 text-secondary"></i> Comentarios</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_orders') ?>"><i
                        class="fa-solid fa-box w-20px text-center me-2 text-info"></i> Pedidos</a></li>
                </ul>
              </div>
            <?php endif; ?>

            <!-- Avatar + Perfil dropdown -->
            <div class="nav-item dropdown custom-dropdown">
              <a class="d-flex align-items-center gap-2 rcw-user-trigger text-decoration-none dropdown-toggle" href="#"
                role="button" data-bs-toggle="dropdown">
                <div class="rcw-user-avatar" id="header-avatar">
                  <?php
                  $sessionImg = $_SESSION['profile_image'] ?? '';
                  // Si es una ruta de upload local (/web/img/uploads/...) solo existe
                  // en la máquina que la subió → no mostrar imagen rota, usar iniciales
                  $isLocalUpload = !empty($sessionImg) && strpos($sessionImg, '/web/img/uploads/') !== false;
                  $showImg = !empty($sessionImg) && !$isLocalUpload;
                  ?>
                  <?php if ($showImg): ?>
                    <img src="<?= htmlspecialchars($sessionImg) ?>" alt="Avatar"
                      style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                      onerror="this.style.display='none';this.parentElement.innerHTML='<span><?= htmlspecialchars($user_initials) ?></span>'">
                  <?php else: ?>
                    <span><?= htmlspecialchars($user_initials) ?></span>
                  <?php endif; ?>
                </div>
                <span class="rcw-user-name d-none d-xl-inline"
                  id="header-username-display"><?= htmlspecialchars(ucfirst($user_display)) ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                  <div class="rcw-dropdown-header px-3 py-2">
                    <div class="fw-semibold" id="header-dropdown-name" style="color: var(--rcw-text-primary);">
                    
                      <?= htmlspecialchars(ucfirst($user_display)) ?></div>

                                        <div class="small" style="color: var(--rcw-text-muted);">
                      <?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></div>
                  </div>
                </li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('profile') ?>"><i
                      class="fa-solid fa-id-card w-20px text-center me-2 text-secondary"></i> Mi perfil</a></li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('profile') ?>#tops"><i
                      class="fa-solid fa-list-ol w-20px text-center me-2 text-warning"></i> Mis tops</a></li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('carrito') ?>"><i
                      class="fa-solid fa-cart-shopping w-20px text-center me-2 text-success"></i> Carrito</a></li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('orders') ?>"><i
                      class="fa-solid fa-box w-20px text-center me-2 text-info"></i> Mis pedidos</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item py-2 text-danger signOutBtn" href="#"><i
                      class="fa-solid fa-arrow-right-from-bracket w-20px text-center me-2"></i> Cerrar sesión</a></li>
                </ul>
              </div>

        <?php else: ?>
            <!-- Login / Registro -->
            <a class="nav-link rcw-btn-login" href="<?= Router::url('login') ?>">
              <i class="fa-solid fa-right-to-bracket me-1"></i> Login
            </a>
            <a class="nav-link rcw-btn-register" href="<?= Router::url('register') ?>">
                Registro
              </a>

          <?php endif; ?>

        </div>
        <!-- / Zona derecha -->

      </div>
    </div>
  </nav>