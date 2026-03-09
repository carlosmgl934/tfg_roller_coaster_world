<?php
// Inicia sesión (si no está ya iniciada)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Determina si el usuario está logueado
$is_logged = isset($_SESSION['firebase_uid']);
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

  <!-- Firebase SDK - versión COMPAT (necesaria para proyectos PHP sin módulos) -->
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-auth-compat.js"></script>

  <!-- Base URL calculada automáticamente (funciona en cualquier servidor) -->
  <?php
  $base_url = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $_SERVER['SCRIPT_NAME']);
  ?>
  <script>window.BASE_URL = '<?= $base_url ?>';</script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- Firebase auth init (global) -->
  <script src="<?= $base_url ?>/web/js/auth.js"></script>

</head>

<body>
  <link rel="stylesheet" href="<?= $base_url ?>/web/css/header.css">
  </header>

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
            <a class="nav-link px-3 rounded text-white" href="<?= $base_url ?>/web/views/public/index.php">
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
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/coaster_search.php"><i
                    class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> Buscar</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/ranking.php"><i
                    class="fa-solid fa-earth-europe w-20px text-center me-2 text-success"></i> Ranking Global</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/coaster_reviews.php"><i
                    class="fa-solid fa-star w-20px text-center me-2 text-warning"></i> Reseñas Globales</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/coaster_tops.php"><i
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
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/parks.php"><i
                    class="fa-solid fa-list w-20px text-center me-2"></i> Listado</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/park_detail.php"><i
                    class="fa-solid fa-circle-info w-20px text-center me-2"></i> Ficha Parque</a></li>
            </ul>
          </li>

          <!-- Foros -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
              data-bs-toggle="dropdown">
              <i class="fa-solid fa-comments me-1"></i> Foros
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/forums.php"><i
                    class="fa-solid fa-users w-20px text-center me-2"></i> Todos los foros</a></li>
            </ul>
          </li>

          <!-- Perfil / Login -->
          <?php if ($is_logged): ?>
            <li class="nav-item dropdown custom-dropdown">
              <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
                data-bs-toggle="dropdown">
                <i class="fa-solid fa-user me-1"></i> Perfil
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/profile.php"><i
                      class="fa-solid fa-id-card w-20px text-center me-2"></i> Mi perfil</a></li>
                <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/friends.php"><i
                      class="fa-solid fa-user-group w-20px text-center me-2"></i> Amigos</a></li>
                <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/trips.php"><i
                      class="fa-solid fa-suitcase w-20px text-center me-2"></i> Mis viajes</a></li>
                <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/carrito.php"><i
                      class="fa-solid fa-cart-shopping w-20px text-center me-2"></i> Carrito</a></li>
                <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/public/orders.php"><i
                      class="fa-solid fa-box w-20px text-center me-2"></i> Mis pedidos</a></li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item py-2 text-danger" href="<?= $base_url ?>/web/views/auth/logout.php"><i
                      class="fa-solid fa-arrow-right-from-bracket w-20px text-center me-2"></i> Cerrar sesión</a></li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link px-3 rounded text-white" href="<?= $base_url ?>/web/views/auth/login.php"><i
                  class="fa-solid fa-right-to-bracket me-1"></i> Login</a>
            </li>
            <li class="nav-item">
              <a class="nav-link px-3 rounded bg-white text-success fw-bold ms-lg-2"
                href="<?= $base_url ?>/web/views/auth/register.php">Registro</a>
            </li>
          <?php endif; ?>

          <!-- Admin -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link px-3 rounded text-white dropdown-toggle" href="#" role="button"
              data-bs-toggle="dropdown">
              <i class="fa-solid fa-gear me-1"></i> Admin
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/dashboard.php"><i
                    class="fa-solid fa-chart-line w-20px text-center me-2 text-danger"></i> Dashboard</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/users.php"><i
                    class="fa-solid fa-users w-20px text-center me-2"></i> Usuarios</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/coasters.php"><i
                    class="fa-solid fa-train-tram w-20px text-center me-2"></i> Coasters</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/parks.php"><i
                    class="fa-solid fa-tree-city w-20px text-center me-2"></i> Parques</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/messages.php"><i
                    class="fa-solid fa-envelope w-20px text-center me-2"></i> Mensajes</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/photos.php"><i
                    class="fa-solid fa-image w-20px text-center me-2"></i> Fotos</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/comments.php"><i
                    class="fa-solid fa-comment w-20px text-center me-2"></i> Comentarios</a></li>
              <li><a class="dropdown-item py-2" href="<?= $base_url ?>/web/views/admin/orders.php"><i
                    class="fa-solid fa-box w-20px text-center me-2"></i> Pedidos</a></li>
            </ul>
          </li>

        </ul>
      </div>
    </div>
  </nav>

  <!-- Añade el JS completo de Bootstrap para que los dropdowns funcionen -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </header>