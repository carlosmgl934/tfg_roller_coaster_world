<?php
// Gestión de sesión centralizada
require_once __DIR__ . '/../../../api/php/utils/SessionManager.php';



// ── Cabeceras de seguridad HTTP ───────────────────────────────────────────────
if (!headers_sent()) {
  header("X-Content-Type-Options: nosniff");
  header("X-Frame-Options: SAMEORIGIN");
  header("Referrer-Policy: strict-origin-when-cross-origin");
  header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
  header("Cross-Origin-Opener-Policy: same-origin-allow-popups");
  header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' https://www.gstatic.com https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://js.stripe.com https://unpkg.com https://apis.google.com; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com; " .
    "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com; " .
    "img-src 'self' data: blob: https: https://*.openstreetmap.org https://*.supabase.co; " .
    "connect-src 'self' https://*.supabase.co https://*.googleapis.com https://securetoken.googleapis.com https://identitytoolkit.googleapis.com https://nominatim.openstreetmap.org https://firebaseapp.com https://www.gstatic.com https://unpkg.com https://cdn.jsdelivr.net; " .
    "frame-src https://js.stripe.com https://tfg-roller-coaster-world-auth.firebaseapp.com https://accounts.google.com; " .
    "frame-ancestors 'none';"
  );
}

// Asegurar que tenemos el user_id de la BD si hay sesión de Firebase
if (isset($_SESSION['firebase_uid']) && !isset($_SESSION['user_id'])) {
  try {
    require_once __DIR__ . '/../../../api/database/db_conexion.php';
    $db_h = new DBConexion();
    $stmt_h = $db_h->prepare("SELECT id, user_rol, username, profile_image FROM users WHERE firebase_uid = :uid LIMIT 1");
    $stmt_h->execute([':uid' => $_SESSION['firebase_uid']]);
    $user_h = $stmt_h->fetch(PDO::FETCH_ASSOC);
    if ($user_h) {
      $_SESSION['user_id'] = (int) $user_h['id'];
      $_SESSION['user_rol'] = $user_h['user_rol'];
      if (!isset($_SESSION['username']))
        $_SESSION['username'] = $user_h['username'];
      if (!isset($_SESSION['profile_image']))
        $_SESSION['profile_image'] = $user_h['profile_image'];
    }
  } catch (Exception $e) {
    // Ignorar errores de conexión aquí para no romper el sitio
  }
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
  Router::getRoutePath('park_ranking'),

  // Foros — solo búsqueda/listado público (sin login)
  Router::getRoutePath('forum_search'),

  // Páginas legales/info
  Router::getRoutePath('contact'),
  Router::getRoutePath('privacy'),
  Router::getRoutePath('notice'),

  // Tienda — catálogo público
  Router::getRoutePath('tickets'),
];

// Determina si el usuario está logueado
$is_logged = isset($_SESSION['firebase_uid']);
$is_admin = $is_logged && isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

// ── Idioma de la interfaz ─────────────────────────────────────────────────────
$_allowed_langs = ['es', 'en', 'fr', 'de'];
$_lang = $_COOKIE['rcw_lang'] ?? 'es';
if (!in_array($_lang, $_allowed_langs))
  $_lang = 'es';

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
<html lang="<?= $_lang ?>">

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

  <script>window.BASE_URL = '<?= $base_url ?>'; window.APP_LANG = '<?= $_lang ?>';</script>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

  <!-- Firebase auth init (global) -->
  <script src="<?= Router::asset('web/js/auth/auth.js') ?>"></script>

  <!-- Pre-carga de traducciones + CSRF fetch interceptor -->
  <!-- IMPORTANTE: Este bloque debe ir ANTES del motor i18n para que _RCW_LANG_CACHE esté disponible -->
  <script>
    // 1) Inyectar todos los JSONs de idioma directamente desde PHP (sin fetch, sin red)
    window._RCW_LANG_CACHE = {};
    <?php
    // __DIR__ = .../web/views/partials  →  ../../lang/ = .../web/lang/
    $lang_dir = __DIR__ . '/../../lang/';

    // Fallback: intentar resolver desde el DocumentRoot real
    $lang_dir_alt = __DIR__ . '/../../../web/lang/';

    // Tercer intento: desde el script actual del servidor (para producción con distinta estructura)
    $script_base = dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $lang_dir_alt2 = rtrim($script_base, '/') . '/web/lang/';

    // Elegir la primera ruta que contenga archivos
    $lang_dir_final = $lang_dir;
    if (!file_exists($lang_dir . 'es.json')) {
      if (file_exists($lang_dir_alt . 'es.json')) {
        $lang_dir_final = $lang_dir_alt;
      } elseif (file_exists($lang_dir_alt2 . 'es.json')) {
        $lang_dir_final = $lang_dir_alt2;
      }
    }

    $loaded = [];
    foreach (['es', 'en', 'fr', 'de'] as $l) {
      $file = $lang_dir_final . $l . '.json';
      if (file_exists($file)) {
        echo "window._RCW_LANG_CACHE['" . $l . "'] = " . file_get_contents($file) . ";\n  ";
        $loaded[] = $l;
      } else {
        error_log("[i18n] Archivo de idioma no encontrado en ninguna ruta: " . $file);
      }
    }

    // Log de diagnóstico para saber qué ve PHP en producción
    $diag = [
      'dir1' => $lang_dir,
      'dir2' => $lang_dir_alt,
      'dir3' => $lang_dir_alt2,
      'used' => $lang_dir_final,
      'es_found' => file_exists($lang_dir_final . 'es.json') ? 'YES' : 'NO',
    ];
    ?>
    console.log('[i18n] Caché PHP inyectada para idiomas:', <?= json_encode(implode(', ', $loaded) ?: 'NINGUNO') ?>,
      '| keys en _RCW_LANG_CACHE:', Object.keys(window._RCW_LANG_CACHE),
      '| PHP diag:', <?= json_encode($diag) ?>);

    // 2) Parchear el fetch: idiomas desde caché + CSRF para mutaciones
    (function () {
      var _origFetch = window.fetch;
      var csrfMeta = document.querySelector('meta[name="csrf-token"]');
      window.fetch = function (resource, config) {
        // --- Interceptor de idiomas: responder con JSON en caché ---
        if (typeof resource === 'string' && resource.indexOf('/web/lang/') !== -1) {
          var m = resource.match(/\/web\/lang\/([a-z]{2})\.json/);
          if (m && window._RCW_LANG_CACHE[m[1]]) {
            return Promise.resolve(new Response(JSON.stringify(window._RCW_LANG_CACHE[m[1]]), {
              status: 200,
              headers: { 'Content-Type': 'application/json' }
            }));
          }
          // Si la caché no tiene el idioma, loguear para diagnóstico
          console.warn('[i18n] Idioma no encontrado en caché, se hará fetch real:', resource);
        }
        // --- Interceptor CSRF: añadir cabecera en POST/PUT/DELETE ---
        var token = csrfMeta ? csrfMeta.getAttribute('content') : null;
        if (token && config && (config.method === 'POST' || config.method === 'PUT' || config.method === 'DELETE')) {
          var origin = window.location.origin;
          if (typeof resource === 'string' && (resource.startsWith('/') || resource.startsWith('.') || resource.indexOf(origin) === 0)) {
            config.headers = config.headers || {};
            if (config.headers instanceof Headers) {
              config.headers.append('X-CSRF-Token', token);
            } else {
              config.headers['X-CSRF-Token'] = token;
            }
          }
        }
        return _origFetch.apply(this, [resource, config]);
      };
    })();
  </script>

  <!-- i18n: motor de traducción multiidioma —— Va DESPUÉS de _RCW_LANG_CACHE -->
  <script>
    <?php
    $i18n_path = __DIR__ . '/../../../web/js/shared/i18n.js';
    if (file_exists($i18n_path)) {
      readfile($i18n_path);
    } else {
      // Fallback mínimo si el archivo no existe
      echo 'window.rcwI18n = null; window.t = function(k){ return k; };';
    }
    ?>
  </script>



  <!-- Funciones Nav para búsqueda de comunidad -->
  <?php if ($is_logged): ?>
    <script src="<?= Router::asset('web/js/social/header_friends.js') ?>"></script>
    <!-- HIDDEN-TFG-START — cart.js desactivado temporalmente -->
    <?php if (false): ?>
      <script src="<?= Router::asset('web/js/shop/cart.js') ?>?v=<?= time() ?>"></script>
    <?php endif; ?>
    <!-- HIDDEN-TFG-END -->
  <?php endif; ?>


  <!-- Design System globals (dark mode tokens + overrides) -->
  <link rel="stylesheet" href="<?= Router::asset('web/css/globals.css') ?>">
  <!-- Navbar + layout -->
  <link rel="stylesheet" href="<?= Router::asset('web/css/header.css') ?>">

  <!-- Page specific CSS -->
  <?php if (isset($page_css)): ?>
    <?php foreach ((array) $page_css as $css): ?>
      <?php if (strpos($css, 'http') === 0): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
      <?php else: ?>
        <link rel="stylesheet" href="<?= Router::asset($css) ?>">
      <?php endif; ?>
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- CSRF Token para peticiones Fetch/AJAX -->
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

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
              <i class="fa-solid fa-house me-1"></i> <span data-i18n="nav.home">Home</span>
            </a>
          </li>

          <!-- Coasters -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <span data-i18n="nav.coasters">Coasters</span>
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= Router::url('coaster_search') ?>"><i
                    class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> <span
                    data-i18n="nav.search">Buscar</span></a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('ranking') ?>"><i
                    class="fa-solid fa-earth-europe w-20px text-center me-2 text-success"></i> <span
                    data-i18n="nav.global_ranking">Ranking Global</span></a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('coaster_reviews') ?>"><i
                    class="fa-solid fa-star w-20px text-center me-2 text-warning"></i> <span
                    data-i18n="nav.global_reviews">Reseñas Globales</span></a></li>
              <li><a class="dropdown-item py-2" href="<?= Router::url('coaster_tops') ?>"><i
                    class="fa-solid fa-trophy w-20px text-center me-2 text-info"></i> <span
                    data-i18n="nav.user_tops">Tops Usuarios</span></a></li>
            </ul>
          </li>

          <!-- Parques -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <span data-i18n="nav.parks">Parques</span>
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('park_search') ?>"><i
                    class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> <span
                    data-i18n="nav.search_park">Buscar Parque</span></a>
              </li>
              <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('park_ranking') ?>"><i
                    class="fa-solid fa-earth-europe w-20px text-center me-2 text-success"></i> <span
                    data-i18n="nav.global_ranking">Ranking Global</span></a></li>
              <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('park_tops') ?>?filter=users"><i
                    class="fa-solid fa-trophy w-20px text-center me-2 text-info"></i> <span
                    data-i18n="nav.user_tops">Tops Usuarios</span></a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <!-- HIDDEN-TFG-START: enlace Entradas en menú Parques -->
              <?php if (false): ?>
                <li class="hidden-tfg"><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('tickets') ?>"><i
                      class="fa-solid fa-ticket w-20px text-center me-2 text-warning"></i> Entradas</a></li>
              <?php endif; ?>
              <!-- HIDDEN-TFG-END -->
            </ul>
          </li>

          <!-- Foros -->
          <li class="nav-item dropdown custom-dropdown">
            <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
              <i class="fa-solid fa-comments me-1"></i> <span data-i18n="nav.forums">Foros</span>
            </a>
            <ul class="dropdown-menu shadow border-0">
              <li><a class="dropdown-item py-2" href="<?= Router::url('forum_search') ?>"><i
                    class="fa-solid fa-users w-20px text-center me-2 text-primary"></i> <span
                    data-i18n="nav.all_forums">Todos los foros</span></a></li>
            </ul>
          </li>

          <!-- Viajes -->
          <?php if ($is_logged): ?>
            <li class="nav-item dropdown custom-dropdown">
              <a class="nav-link rcw-nav-pill dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fa-solid fa-suitcase-rolling me-1"></i> <span data-i18n="nav.trips">Viajes</span>
              </a>
              <ul class="dropdown-menu shadow border-0">
                <li><a class="dropdown-item py-2" href="<?= Router::url('trips') ?>"><i
                      class="fa-solid fa-calendar-days w-20px text-center me-2 text-success"></i> <span
                      data-i18n="nav.my_agenda">Mi Agenda</span></a></li>
                <!-- HIDDEN-TFG-START: Generador IA oculto para reforma -->
                <?php if (false): ?>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('trip_generator') ?>"><i
                        class="fa-solid fa-wand-magic-sparkles w-20px text-center me-2 text-danger"></i> <span
                        data-i18n="nav.ai_generator">Generador IA</span></a>
                  </li>
                <?php endif; ?>
                <!-- HIDDEN-TFG-END -->
              </ul>
            </li>
          <?php endif; ?>

          <!-- Comunidad / Usuarios -->
          <?php if ($is_logged): ?>
            <li class="nav-item dropdown custom-dropdown">
              <a class="nav-link rcw-nav-pill dropdown-toggle position-relative" href="#" role="button"
                data-bs-toggle="dropdown">
                <i class="fa-solid fa-users me-1"></i> <span data-i18n="nav.community">Comunidad</span>
                <span id="nav-comm-badge"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none"
                  style="font-size: 0.65rem; padding: 0.35em 0.5em;">0</span>
              </a>
              <ul class="dropdown-menu shadow border-0">
                <li><a class="dropdown-item py-2 fw-semibold" href="<?= Router::url('user_search') ?>"><i
                      class="fa-solid fa-magnifying-glass w-20px text-center me-2 text-primary"></i> <span
                      data-i18n="nav.search_users">Buscar usuarios</span></a>
                </li>
                <li><a class="dropdown-item py-2 fw-semibold d-flex align-items-center justify-content-between"
                    href="<?= Router::url('friends') ?>">
                    <span><i class="fa-solid fa-user-group w-20px text-center me-2 text-success"></i> <span
                        data-i18n="nav.friendships">Amistades</span></span>
                    <span id="nav-comm-inner-badge" class="badge rounded-pill bg-danger d-none"
                      style="font-size: 0.65rem;">0</span>
                  </a>
                </li>
              </ul>
            </li>
          <?php endif; ?>



        </ul>
        <!-- / Nav central -->

        <!-- Zona derecha: Idioma + Login/Registro o Avatar de perfil -->
        <div class="d-flex align-items-center gap-2 ms-xl-3">

          <!-- ── Selector de idioma (siempre visible) ── -->
          <div class="nav-item dropdown custom-dropdown" id="rcw-lang-dropdown-wrap">
            <button type="button" class="btn btn-sm d-flex align-items-center gap-1 rcw-lang-toggle"
              data-bs-toggle="dropdown" aria-expanded="false" aria-label="Seleccionar idioma"
              title="<?= ['es' => 'Español', 'en' => 'English', 'fr' => 'Français', 'de' => 'Deutsch'][$_lang] ?>"
              style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);color:var(--rcw-text-primary,#e5e7eb);border-radius:8px;padding:0.3rem 0.55rem;transition:background .18s;">
              <i class="fa-solid fa-globe" style="font-size:0.9rem;"></i>
              <span class="rcw-lang-toggle-label fw-semibold" style="font-size:0.8rem;letter-spacing:0.04em;">
                <?php
                $flagMap = ['es' => '🇪🇸', 'en' => '🇬🇧', 'fr' => '🇫🇷', 'de' => '🇩🇪'];
                echo $flagMap[$_lang] . ' ' . strtoupper($_lang);
                ?>
              </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rcw-lang-menu" style="min-width:160px;">
              <?php
              $langs = [
                'es' => ['flag' => '🇪🇸', 'name' => 'Español'],
                'en' => ['flag' => '🇬🇧', 'name' => 'English'],
                'fr' => ['flag' => '🇫🇷', 'name' => 'Français'],
                'de' => ['flag' => '🇩🇪', 'name' => 'Deutsch'],
              ];
              foreach ($langs as $code => $info): ?>
                <li>
                  <button type="button"
                    class="dropdown-item py-2 d-flex align-items-center gap-2 rcw-lang-option<?= $code === $_lang ? ' active fw-semibold' : '' ?>"
                    data-lang-option="<?= $code ?>" onclick="rcwSetLang('<?= $code ?>');" style="font-size:0.9rem;">
                    <span style="font-size:1.1rem;line-height:1;"><?= $info['flag'] ?></span>
                    <span><?= $info['name'] ?></span>
                    <?php if ($code === $_lang): ?>
                      <i class="fa-solid fa-check ms-auto text-success" style="font-size:0.75rem;"></i>
                    <?php endif; ?>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <!-- / Selector de idioma -->

          <script>
            function rcwSetLang(lang) {
              var d = new Date();
              d.setTime(d.getTime() + 365 * 24 * 60 * 60 * 1000);
              document.cookie = 'rcw_lang=' + encodeURIComponent(lang) + ';expires=' + d.toUTCString() + ';path=/;SameSite=Lax';
              localStorage.setItem('rcw_lang', lang);

              // Actualizar el botón toggle visualmente de inmediato
              var flagMap = { es: '🇪🇸', en: '🇬🇧', fr: '🇫🇷', de: '🇩🇪' };
              var label = document.querySelector('.rcw-lang-toggle-label');
              if (label) label.textContent = (flagMap[lang] || '') + ' ' + lang.toUpperCase();

              // Marcar opción activa en el dropdown
              document.querySelectorAll('.rcw-lang-option').forEach(function (btn) {
                var isActive = btn.getAttribute('data-lang-option') === lang;
                btn.classList.toggle('active', isActive);
                btn.classList.toggle('fw-semibold', isActive);
                var check = btn.querySelector('.fa-check');
                if (check) check.remove();
                if (isActive) {
                  var icon = document.createElement('i');
                  icon.className = 'fa-solid fa-check ms-auto text-success';
                  icon.style.fontSize = '0.75rem';
                  btn.appendChild(icon);
                }
              });

              // Traducir con i18n o recargar
              if (window.rcwI18n && typeof window.rcwI18n.setLang === 'function') {
                window.rcwI18n.setLang(lang);
              } else {
                window.location.href = window.location.href.split('#')[0];
              }
            }
          </script>

          <?php if ($is_logged): ?>

            <!-- Admin (solo admins) -->
            <?php if ($is_admin): ?>
              <div class="nav-item dropdown custom-dropdown">
                <a class="nav-link rcw-nav-pill rcw-nav-admin dropdown-toggle" href="#" role="button"
                  data-bs-toggle="dropdown">
                  <i class="fa-solid fa-gear me-1"></i> Admin
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_updater') ?>"><i
                        class="fa-solid fa-cloud-arrow-down w-20px text-center me-2 text-danger"></i> Updater RCDB</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_dashboard') ?>"><i
                        class="fa-solid fa-chart-line w-20px text-center me-2 text-primary"></i> Dashboard</a></li>
                  <li><a class="dropdown-item py-2" href="<?= Router::url('admin_users') ?>"><i
                        class="fa-solid fa-users w-20px text-center me-2 text-primary"></i> Usuarios</a></li>
                  <li>
                    <hr class="dropdown-divider">
                  </li>
                  <li><a class="dropdown-item py-2 d-flex align-items-center" href="<?= Router::url('admin_coasters') ?>">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="18" height="18"
                        class="text-success flex-shrink-0" style="margin-right: 0.5rem;">
                        <path d="M4 48 C 20 48, 24 16, 40 16 C 52 16, 56 32, 60 48" fill="none" stroke="currentColor"
                          stroke-width="4" stroke-linecap="round" />
                        <path d="M4 56 C 24 56, 28 24, 40 24 C 50 24, 54 38, 60 56" fill="none" stroke="currentColor"
                          stroke-width="4" stroke-linecap="round" />
                        <line x1="16" y1="42" x2="16" y2="60" stroke="currentColor" stroke-width="3"
                          stroke-linecap="round" />
                        <line x1="32" y1="20" x2="32" y2="60" stroke="currentColor" stroke-width="3"
                          stroke-linecap="round" />
                        <line x1="48" y1="24" x2="48" y2="60" stroke="currentColor" stroke-width="3"
                          stroke-linecap="round" />
                        <line x1="24" y1="28" x2="16" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                          opacity="0.6" />
                        <line x1="40" y1="16" x2="32" y2="60" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                          opacity="0.6" />
                        <rect x="23" y="10" width="10" height="6" rx="2" fill="currentColor" />
                        <circle cx="25" cy="18" r="2" fill="currentColor" />
                        <circle cx="31" cy="18" r="2" fill="currentColor" />
                        <rect x="11" y="24" width="10" height="6" rx="2" fill="currentColor"
                          transform="rotate(-40 16 27)" />
                      </svg>
                      Coasters</a></li>
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
                  <!-- HIDDEN-TFG-START: admin Pedidos y Cupones -->
                  <?php if (false): ?>
                    <li class="hidden-tfg"><a class="dropdown-item py-2" href="<?= Router::url('admin_orders') ?>"><i
                          class="fa-solid fa-box w-20px text-center me-2 text-info"></i> Pedidos</a></li>
                    <li class="hidden-tfg"><a class="dropdown-item py-2" href="<?= Router::url('admin_coupons') ?>"><i
                          class="fa-solid fa-ticket w-20px text-center me-2 text-warning"></i> Cupones</a></li>
                  <?php endif; ?>
                  <!-- HIDDEN-TFG-END -->
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
                  $finalImgUrl = '';
                  if (!empty($sessionImg)) {
                    if (strpos($sessionImg, 'http') === 0) {
                      $finalImgUrl = $sessionImg;
                    } elseif (strpos($sessionImg, '/') === 0) {
                      $finalImgUrl = $base_url . $sessionImg;
                    } else {
                      // Es un nombre de archivo subido a Supabase
                      $supabaseUrl = $_ENV['SUPABASE_URL'] ?? 'https://ubtoaaawqdneblyvbelr.supabase.co';
                      $finalImgUrl = rtrim($supabaseUrl, '/') . '/storage/v1/object/public/avatars/' . $sessionImg;
                    }
                  }
                  $showImg = !empty($finalImgUrl);
                  ?>
                  <?php if ($showImg): ?>
                    <img src="<?= htmlspecialchars($finalImgUrl) ?>" alt="Avatar"
                      style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                      onerror="this.style.display='none';this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100 w-100 text-secondary bg-dark\' style=\'border-radius:50%;\'><i class=\'fa-solid fa-user fs-5\'></i></div>'">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 w-100 text-secondary bg-dark"
                      style="border-radius:50%;"><i class="fa-solid fa-user fs-5"></i></div>
                  <?php endif; ?>
                </div>
                <span class="rcw-user-name d-none d-xl-inline"
                  id="header-username-display"><?= htmlspecialchars(ucfirst($user_display)) ?></span>
                <!-- HIDDEN-TFG-START: icono carrito flotante junto al avatar -->
                <?php if (false): ?>
                  <span class="position-relative d-none d-xl-inline-flex align-items-center ms-1 hidden-tfg"
                    id="cart-nav-icon-wrap" style="display:none!important;">
                    <i class="fa-solid fa-cart-shopping" style="font-size:.85rem;color:var(--rcw-text-muted);"></i>
                    <span class="cart-nav-badge position-absolute badge rounded-pill bg-success d-none"
                      style="font-size:.55rem;padding:.2em .45em;top:-6px;right:-8px;min-width:16px;line-height:1.2;">0</span>
                  </span>
                <?php endif; ?>
                <!-- HIDDEN-TFG-END -->
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                  <div class="rcw-dropdown-header px-3 py-2">
                    <div class="fw-semibold" id="header-dropdown-name" style="color: var(--rcw-text-primary);">

                      <?= htmlspecialchars(ucfirst($user_display)) ?>
                    </div>

                    <div class="small" style="color: var(--rcw-text-muted);">
                      <?= htmlspecialchars($_SESSION['user_email'] ?? '') ?>
                    </div>
                  </div>
                </li>
                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('profile') ?>"><i
                      class="fa-solid fa-id-card w-20px text-center me-2 text-secondary"></i> <span
                      data-i18n="nav.my_profile">Mi perfil</span></a></li>
                <li><a class="dropdown-item py-2" href="<?= Router::url('profile') ?>#tops"><i
                      class="fa-solid fa-list-ol w-20px text-center me-2 text-warning"></i> <span
                      data-i18n="nav.my_tops">Mis tops</span></a></li>
                <!-- HIDDEN-TFG-START: Carrito y Mis entradas en dropdown usuario -->
                <?php if (false): ?>
                  <li class="hidden-tfg"><a class="dropdown-item py-2" href="<?= Router::url('carrito') ?>"><i
                        class="fa-solid fa-cart-shopping w-20px text-center me-2 text-success"></i> Carrito
                      <span class="cart-nav-badge badge rounded-pill bg-danger ms-auto d-none"
                        style="font-size:.65rem;padding:.25em .5em;">0</span>
                    </a></li>
                  <li class="hidden-tfg"><a class="dropdown-item py-2" href="<?= Router::url('orders') ?>"><i
                        class="fa-solid fa-ticket w-20px text-center me-2 text-info"></i> Mis entradas</a></li>
                <?php endif; ?>
                <!-- HIDDEN-TFG-END -->
                <li>
                  <hr class="dropdown-divider">
                </li>

                <li>
                  <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item py-2 text-danger signOutBtn" href="#"><i
                      class="fa-solid fa-arrow-right-from-bracket w-20px text-center me-2"></i> <span
                      data-i18n="nav.logout">Cerrar sesión</span></a></li>
              </ul>
            </div>

          <?php else: ?>
            <!-- Login / Registro -->
            <a class="nav-link rcw-btn-login" href="<?= Router::url('login') ?>">
              <i class="fa-solid fa-right-to-bracket me-1"></i> <span data-i18n="nav.login">Login</span>
            </a>
            <a class="nav-link rcw-btn-register" href="<?= Router::url('register') ?>">
              <span data-i18n="nav.register">Registro</span>
            </a>

          <?php endif; ?>

        </div>
        <!-- / Zona derecha -->

      </div>
    </div>
  </nav>

  <script>
    $(document).ready(function () {
      const $mainMenu = $('#mainMenu');
      $mainMenu.on('show.bs.collapse', function () {
        $('body').addClass('navbar-open');
      });
      $mainMenu.on('hide.bs.collapse', function () {
        $('body').removeClass('navbar-open');
      });

      // Cerrar al hacer clic fuera
      $(document).on('click', function (event) {
        if ($mainMenu.hasClass('show') && !$(event.target).closest('.custom-navbar').length) {
          $mainMenu.collapse('hide');
        }
      });
    });
  </script>

  <script>
    // ── Modal Scroll Lock Fix (iOS + Android) ─────────────────────────────
    // En iOS Safari y Chrome móvil, overflow:hidden en body no bloquea
    // el scroll táctil. Aplicamos position:fixed + top inmediatamente en JS
    // (sin esperar a que Bootstrap añada modal-open) para eliminar el gap.
    // ─────────────────────────────────────────────────────────────────────
    (function () {
      // Aplicar en cualquier dispositivo con pantalla pequeña o táctil
      var isMobile = window.innerWidth < 992 || ('ontouchstart' in window);
      if (!isMobile) return;

      var scrollY = 0;

      // show.bs.modal → bloqueo inmediato antes de la animación de apertura
      document.addEventListener('show.bs.modal', function () {
        scrollY = window.scrollY || window.pageYOffset || 0;
        var body = document.body;
        body.style.overflow = 'hidden';
        body.style.position = 'fixed';
        body.style.top = '-' + scrollY + 'px';
        body.style.width = '100%';
        // NO ponemos touch-action:none en el body — bloquea gestos táctiles
        // tras cerrar; el overflow:hidden + position:fixed ya es suficiente
      }, true);

      // hidden.bs.modal → animación de cierre TERMINADA, restauramos scroll
      document.addEventListener('hidden.bs.modal', function () {
        var body = document.body;

        // Leer la posición guardada en el propio style.top del body
        // (más fiable que la variable en ciertos casos de modal anidados)
        var savedScrollY = Math.abs(parseInt(body.style.top || '0', 10)) || scrollY;

        // Quitar estilos de bloqueo
        body.style.overflow = '';
        body.style.position = '';
        body.style.top = '';
        body.style.width = '';

        // Restaurar scroll de forma INSTANTÁNEA:
        // - Desactivamos scroll-behavior:smooth en <html> temporalmente
        //   para que ni el browser ni el CSS animen el salto.
        // - Usamos scrollTo con behavior:'instant' como doble seguro.
        var html = document.documentElement;
        var prevBehavior = html.style.scrollBehavior;
        html.style.scrollBehavior = 'auto';
        body.style.scrollBehavior = 'auto';

        window.scrollTo({ top: savedScrollY, left: 0, behavior: 'instant' });

        // Restaurar el comportamiento de scroll original en el siguiente frame
        requestAnimationFrame(function () {
          html.style.scrollBehavior = prevBehavior || '';
          body.style.scrollBehavior = '';
        });
      }, true);
    })();
  </script>