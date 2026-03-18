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
  Router::getRoutePath('contact'),
  Router::getRoutePath('privacy'),
  Router::getRoutePath('notice'),
  // Parques
  Router::getRoutePath('park_search'),
  Router::getRoutePath('park_tops'),
  Router::getRoutePath('parks'),
  Router::getRoutePath('park_detail'),
  Router::getRoutePath('park_reviews'),
  // Coasters
  Router::getRoutePath('coaster_search'),
  Router::getRoutePath('coasters'),
  Router::getRoutePath('coaster_reviews'),
  Router::getRoutePath('coaster_tops'),
  Router::getRoutePath('coaster_detail'),
  // Foros
  Router::getRoutePath('forum_search'),
  Router::getRoutePath('forums'),
  // Ranking
  Router::getRoutePath('ranking'),
];

// Determina si el usuario está logueado
$is_logged = isset($_SESSION['firebase_uid']);

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

  <!-- Estilos globales y sticky footer -->
  <link rel="stylesheet" href="<?= Router::asset('web/css/header.css') ?>">  

</head>

<body>

  <nav class="navbar navbar-expand-lg custom-navbar sticky-top shadow-sm">
    <div class="container-fluid px-4">

      <!-- Botón hamburguesa para móviles -->
      <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse"
        data-bs-target="#mainMenu">
        <i class="fa-solid fa-bars fs-3"></i>
      </button>

      <!-- Contenido del menú -->
      <div class="collapse navbar-collapse" id="mainMenu">
        <!-- mx-auto centra el menú, nav-pills da un estilo de botones interactivos -->
        <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-2 gap-lg-4 fw-semibold">

          <!-- Home  -->
          <li class="nav-item">
            <a class="nav-link px-3 rounded text-white" href="<?= Router::url('home') ?>">
              <i class="fa-solid fa-house me-1"></i> Home
            </a>
          </li>

          <!-- Coasters -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
              data-bs-toggle="dropdown">
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
            <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
              data-bs-toggle="dropdown">
              Parques
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= Router::url('park_search') ?>"><i
                    class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> Buscar</a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('park_tops') ?>"><i
                    class="fa-solid fa-trophy w-20px text-center me-2 text-info"></i> Tops Usuarios</a></li>
            </ul>
          </li>

          <!-- Foros -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
              data-bs-toggle="dropdown">
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
              <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
                data-bs-toggle="dropdown">
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

          <!-- Perfil / Login -->
          <?php if ($is_logged): ?>
            <li class="nav-item dropdown custom-dropdown">
              <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
                data-bs-toggle="dropdown">
                <i class="fa-solid fa-user me-1"></i> Perfil
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item py-2" href="<?= Router::url('profile') ?>"><i
                      class="fa-solid fa-id-card w-20px text-center me-2 text-secondary"></i> Mi perfil</a></li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('friends') ?>"><i
                      class="fa-solid fa-user-group w-20px text-center me-2 text-primary"></i> Usuarios</a></li>
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
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link px-3 rounded text-white" href="<?= Router::url('login') ?>"><i
                  class="fa-solid fa-right-to-bracket me-1"></i> Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link px-3 rounded bg-white text-success fw-bold ms-lg-2"
                href="<?= Router::url('register') ?>">Registro</a>
            </li>
          <?php endif; ?>

          <!-- Admin -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
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
          </li>

        </ul>
      </div>
    </div>
  </nav>
