<?php
$page_css = ['web/css/home.css', 'web/css/recommendations.css'];
require_once __DIR__ . '/../partials/header.php';
/** @var string $base_url */
/** @var bool   $is_logged */
$is_admin = !empty($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
?>
<main class="index-container">
    <?php if ($is_logged): ?>
        <!-- ======================== VISTA DASHBOARD ======================== -->

        <!-- HERO CARRUSEL — full-width -->
        <div class="home-hero" id="home-hero-carousel">

            <!-- Slide 1: (imagen configurable desde admin) -->
            <div class="home-hero-slide active" data-default-img=""
                data-title="Bienvenido,&lt;br&gt;&lt;span style='color:var(--rcw-green)' id='hero-username'&gt;…&lt;/span&gt;"
                data-sub="Bienvenido de nuevo a la comunidad de amantes de las montañas rusas. Sigue explorando y aumentando tus créditos."
                data-tag="Tu Panel Personal" data-btn1-url="<?= Router::url('coaster_search') ?>"
                data-btn1-label="Buscar coasters" data-btn1-icon="fa-magnifying-glass"
                data-btn2-url="<?= Router::url('forum_search') ?>" data-btn2-label="Foros" data-btn2-icon="fa-users">
            </div>

            <!-- Slide 2: Voltron (Europa-Park) -->
            <div class="home-hero-slide"
                style="background-image:url('<?= $base_url ?>/web/img/voltron_ep_opt.jpg'); background-position: center 35%;"
                data-default-img="<?= $base_url ?>/web/img/voltron_ep_opt.jpg"
                data-title="Bienvenido,&lt;br&gt;&lt;span style='color:var(--rcw-green)' id='hero-username'&gt;…&lt;/span&gt;"
                data-sub="Bienvenido de nuevo a la comunidad de amantes de las montañas rusas. Sigue explorando y aumentando tus créditos."
                data-tag="Tu Panel Personal" data-btn1-url="<?= Router::url('coaster_search') ?>"
                data-btn1-label="Buscar coasters" data-btn1-icon="fa-magnifying-glass"
                data-btn2-url="<?= Router::url('forum_search') ?>" data-btn2-label="Foros" data-btn2-icon="fa-users">
            </div>

            <!-- Slide 3: Batman BGCE (Warner) -->
            <div class="home-hero-slide"
                style="background-image:url('<?= $base_url ?>/web/img/bgce_warner_opt.jpg'); background-position: center 25%;"
                data-default-img="<?= $base_url ?>/web/img/bgce_warner_opt.jpg"
                data-title="Bienvenido,&lt;br&gt;&lt;span style='color:var(--rcw-green)' id='hero-username'&gt;…&lt;/span&gt;"
                data-sub="Bienvenido de nuevo a la comunidad de amantes de las montañas rusas. Sigue explorando y aumentando tus créditos."
                data-tag="Tu Panel Personal" data-btn1-url="<?= Router::url('coaster_search') ?>"
                data-btn1-label="Buscar coasters" data-btn1-icon="fa-magnifying-glass"
                data-btn2-url="<?= Router::url('forum_search') ?>" data-btn2-label="Foros" data-btn2-icon="fa-users">
            </div>

            <!-- Slide 4: (imagen configurable desde admin) -->
            <div class="home-hero-slide" data-default-img=""
                data-title="Bienvenido,&lt;br&gt;&lt;span style='color:var(--rcw-green)' id='hero-username'&gt;…&lt;/span&gt;"
                data-sub="Bienvenido de nuevo a la comunidad de amantes de las montañas rusas. Sigue explorando y aumentando tus créditos."
                data-tag="Tu Panel Personal" data-btn1-url="<?= Router::url('coaster_search') ?>"
                data-btn1-label="Buscar coasters" data-btn1-icon="fa-magnifying-glass"
                data-btn2-url="<?= Router::url('forum_search') ?>" data-btn2-label="Foros" data-btn2-icon="fa-users">
            </div>

            <!-- Contenido dinámico (actualizado por JS) -->
            <div class="home-hero-content" id="home-hero-content">
                <div class="home-hero-tag animate d1" id="hero-tag">
                    <i class="fa-solid fa-circle-notch fa-spin" style="font-size:0.6rem;"></i>
                    <span id="hero-tag-text">Tu Panel Personal</span>
                </div>
                <h1 class="home-hero-title animate d2" id="hero-title">
                    Bienvenido,<br>
                    <span style="color:var(--rcw-green)" id="hero-username">…</span>
                </h1>
                <p class="home-hero-sub animate d3" id="hero-sub">Bienvenido de nuevo a la comunidad de amantes de las
                    montañas rusas. Sigue explorando y aumentando tus créditos.</p>
                <div class="home-hero-btns animate d3" id="hero-btns">
                    <a href="<?= Router::url('coaster_search') ?>" class="btn-green-rcw" id="hero-btn1"><i
                            class="fa-solid fa-magnifying-glass"></i> Buscar coasters</a>
                    <a href="<?= Router::url('forum_search') ?>" class="btn-ghost-rcw" id="hero-btn2"><i
                            class="fa-solid fa-users"></i> Foros</a>
                </div>
            </div>

            <!-- Dots -->
            <div class="home-hero-dots" id="home-hero-dots">
                <div class="home-hero-dot active" data-idx="0"></div>
                <div class="home-hero-dot" data-idx="1"></div>
                <div class="home-hero-dot" data-idx="2"></div>
                <div class="home-hero-dot" data-idx="3"></div>
            </div>

            <?php if ($is_admin): ?>
                <!-- Botón admin: editar imágenes del carrusel -->
                <button id="carousel-edit-btn" class="carousel-admin-edit-btn" title="Editar imágenes del carrusel">
                    <i class="fa-solid fa-pencil"></i>
                </button>
            <?php endif; ?>


        </div>

        <!-- STATS BAR — full-width -->
        <div class="home-stats-bar">
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-users">—</div>
                <div class="home-stat-label">Usuarios</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-coasters">—</div>
                <div class="home-stat-label">Coasters</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-reviews">—</div>
                <div class="home-stat-label">Reseñas</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-photos">—</div>
                <div class="home-stat-label">Fotos</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-parks">—</div>
                <div class="home-stat-label">Parques</div>
            </div>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="home-wrapper">

            <!-- ACCESOS RÁPIDOS -->
            <div class="home-section">
                <div class="home-section-title">Explorar</div>
                <div class="home-section-sub">Todo lo que necesitas a un clic de distancia</div>
                <div class="home-qlink-grid">
                    <a href="<?= Router::url('coaster_search') ?>" class="home-qlink">
                        <i class="fa-solid fa-train-tram home-qlink-icon"></i>
                        <div class="home-qlink-name">Buscar Coasters</div>
                        <div class="home-qlink-desc">Explora miles de montañas rusas con nuestros filtros avanzados</div>
                    </a>
                    <a href="<?= Router::url('ranking') ?>" class="home-qlink">
                        <i class="fa-solid fa-list-ol home-qlink-icon"></i>
                        <div class="home-qlink-name">Tops Globales</div>
                        <div class="home-qlink-desc">Descubre las mejores coasters valoradas por nuestra comunidad</div>
                    </a>
                    <a href="<?= Router::url('forums') ?>" class="home-qlink">
                        <i class="fa-solid fa-comments home-qlink-icon"></i>
                        <div class="home-qlink-name">Foros</div>
                        <div class="home-qlink-desc">Unéte a debates, entérate de noticias y novedades</div>
                    </a>
                    <a href="<?= Router::url('trips') ?>" class="home-qlink">
                        <i class="fa-solid fa-route home-qlink-icon"></i>
                        <div class="home-qlink-name">Mis Viajes</div>
                        <div class="home-qlink-desc">Planifica y comparte tus rutas a diferentes parques</div>
                    </a>
                </div>
            </div>

            <!-- ========== ESPECIALMENTE PARA TI (IA) ========== -->
            <div class="home-section rcw-recs-section" id="recs-section">

                <div class="rcw-recs-header">
                    <div class="rcw-recs-title-group">
                        <div class="home-section-title" style="margin-bottom:0;">Especialmente para ti</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small d-none d-sm-inline">
                            Basado en tu perfil y actividad
                        </span>
                        <button class="rcw-recs-refresh-btn" id="recs-refresh-btn" title="Actualizar recomendaciones">
                            <i class="fa-solid fa-rotate"></i>
                            <span class="d-none d-sm-inline">Actualizar</span>
                        </button>
                    </div>
                </div>

                <!-- Grid de 3 cards — poblado por recommendations.js -->
                <div class="rcw-recs-grid" id="recs-grid"></div>

            </div><!-- /recs-section -->

            <!-- PERFIL Y NOTICIAS -->
            <div class="home-section" style="padding-top:0;">
                <div class="row g-4">

                    <!-- PERFIL MINI -->
                    <div class="col-12 col-lg-4 d-flex flex-column">
                        <div class="home-section-title" style="margin-bottom:1.2rem;">Tu Perfil</div>
                        <div class="home-profile-mini flex-grow-1">
                            <div class="home-profile-mini-header">
                                <!-- Avatar inyectado por JS -->
                                <div id="profile-avatar-wrap"></div>
                                <div>
                                    <div class="home-profile-mini-name" id="profile-name">—</div>
                                    <div class="home-profile-mini-role"><i class="fa-solid fa-location-dot me-1"></i><span
                                            id="profile-location">—</span></div>
                                </div>
                            </div>
                            <div class="home-profile-mini-stats">
                                <div class="home-profile-mini-stat">
                                    <div class="home-profile-mini-stat-num" id="stat-credits">0</div>
                                    <div class="home-profile-mini-stat-label">Coaster Count</div>
                                </div>
                                <div class="home-profile-mini-stat">
                                    <div class="home-profile-mini-stat-num" id="stat-reviews">0</div>
                                    <div class="home-profile-mini-stat-label">Reseñas</div>
                                </div>
                                <div class="home-profile-mini-stat">
                                    <div class="home-profile-mini-stat-num" id="stat-parks">0</div>
                                    <div class="home-profile-mini-stat-label">Parques</div>
                                </div>
                                <div class="home-profile-mini-stat" style="border-bottom:none;">
                                    <div class="home-profile-mini-stat-num" id="stat-trips">0</div>
                                    <div class="home-profile-mini-stat-label">Viajes</div>
                                </div>
                                <div class="home-profile-mini-stat" style="border-bottom:none;">
                                    <div class="home-profile-mini-stat-num" id="stat-friends">0</div>
                                    <div class="home-profile-mini-stat-label">Amigos</div>
                                </div>
                                <div class="home-profile-mini-stat" style="border-bottom:none;">
                                    <div class="home-profile-mini-stat-num" id="stat-photos">0</div>
                                    <div class="home-profile-mini-stat-label">Fotos</div>
                                </div>
                            </div>
                            <div class="home-favorite-coaster">
                                <i class="fa-solid fa-star" style="color:var(--rcw-green);"></i>
                                <span>Favorita: <strong class="text-truncate ms-1" id="fav-coaster"
                                        style="max-width:140px;display:inline-block;vertical-align:bottom;">—</strong></span>
                            </div>
                            <div class="home-profile-mini-footer flex-column" style="gap: 0.5rem; padding: 1rem 1.2rem;">
                                <a href="<?= Router::url('profile') ?>#tops"
                                    class="btn btn-success rounded-0 w-100 fw-bold d-flex justify-content-center align-items-center mb-1"
                                    style="padding: 0.6rem; letter-spacing:0.5px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);">
                                    <i class="fa-solid fa-ranking-star me-2"></i> Mis Tops
                                </a>
                                <div class="d-flex w-100" style="gap: 0.5rem;">
                                    <a href="<?= Router::url('profile') ?>" class="home-btn-mini flex-fill"><i
                                            class="fa-solid fa-user me-2"></i>Perfil</a>
                                    <a href="<?= Router::url('profile') ?>#config" class="home-btn-mini flex-fill"><i
                                            class="fa-solid fa-gear me-2"></i>Config</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NOTICIAS -->
                    <div class="col-12 col-lg-8 d-flex flex-column">
                        <div class="home-section-title" style="margin-bottom:1.2rem;">Últimas Novedades</div>
                        <div class="home-news-grid flex-grow-1" id="news-grid">
                            <!-- Skeleton loader -->
                            <div class="home-news-card big no-photo d-flex align-items-center justify-content-center"
                                style="min-height:180px;">
                                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div><!-- /home-wrapper -->

        <!-- Modal Noticias -->
        <div class="modal fade" id="news-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content text-start border-0 rounded-0 shadow-lg"
                    style="background:#161b22; color:#e6edf3;">
                    <div class="modal-header border-bottom border-success px-4 py-3" style="background:#0d1117;">
                        <h5 class="modal-title fw-bold" id="news-modal-title">Título de la Noticia</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <img id="news-modal-img" src="" class="img-fluid w-100 d-none"
                            style="max-height: 480px; object-fit: cover;">
                        <div class="p-4">
                            <div class="d-flex align-items-center mb-3">
                                <span id="news-modal-tag"
                                    class="badge bg-success rounded-0 me-3 text-uppercase shadow-sm"></span>
                                <span id="news-modal-date" class="text-muted small"><i
                                        class="fa-regular fa-clock me-1"></i>...</span>
                            </div>
                            <p id="news-modal-desc" class="fs-6"
                                style="line-height: 1.6; white-space: pre-wrap; margin-bottom: 0;"></p>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background:#161b22;">
                        <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                            data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        </div><!-- FIN VISTA DASHBOARD -->

        <?php if ($is_admin): ?>
            <!-- ===== MODAL: GESTIONAR IMÁGENES DEL CARRUSEL (solo admin) ===== -->
            <div class="modal fade" id="carousel-admin-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">

                        <div class="modal-header border-0 py-3 px-4" style="background:#0d1117;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-images text-success fs-5"></i>
                                <h5 class="modal-title fw-bold mb-0 text-white">Imágenes del carrusel</h5>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body px-4 py-4" style="background:#161b22;">

                            <!-- Vista: 4 slots -->
                            <div id="carousel-slots-view">
                                <p class="text-muted small mb-3">
                                    <i class="fa-solid fa-circle-info text-success me-1"></i>
                                    Sube hasta 4 imágenes para el carrusel. La imagen se recortará al ratio del carrusel
                                    automáticamente. Se guardan en Supabase.
                                </p>
                                <div class="row g-3" id="carousel-slots-grid">
                                    <!-- Renderizado por JS -->
                                </div>
                            </div>

                            <!-- Vista: editor de recorte -->
                            <div id="carousel-cropper-view" class="d-none">
                                <div class="d-flex align-items-center mb-3 gap-2">
                                    <button class="btn btn-sm btn-outline-secondary rounded-0" id="carousel-crop-cancel">
                                        <i class="fa-solid fa-arrow-left me-1"></i>Volver
                                    </button>
                                    <span class="text-muted small">Ajusta el área de recorte — el ratio se fija al del
                                        carrusel</span>
                                </div>
                                <div style="max-height:420px;background:#000;overflow:hidden;">
                                    <img id="carousel-crop-img" src="" style="display:block;max-width:100%;">
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button class="btn btn-outline-secondary rounded-0 px-4"
                                        id="carousel-crop-cancel2">Cancelar</button>
                                    <button class="btn btn-success rounded-0 fw-bold px-5" id="carousel-crop-confirm">
                                        <i class="fa-solid fa-crop me-2"></i>Aplicar y guardar
                                    </button>
                                </div>
                            </div>

                            <!-- Progreso de subida -->
                            <div id="carousel-upload-progress" class="d-none mt-3">
                                <div class="d-flex align-items-center gap-2 text-success">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    <span class="fw-semibold small">Subiendo imagen a Supabase…</span>
                                </div>
                            </div>

                        </div>

                        <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background:#161b22;">
                            <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                                data-bs-dismiss="modal">Cerrar</button>
                        </div>

                    </div>
                </div>
            </div>
            <!-- Input file oculto para seleccionar imagen -->
            <input type="file" id="carousel-file-input" accept="image/*" class="d-none">
        <?php endif; ?>

    <?php else: ?>
        <!-- ======================== VISTA GUEST ======================== -->

        <!-- HERO -->
        <section class="landing-hero d-flex align-items-center" style="
            min-height: 100vh;
            background: linear-gradient(rgba(2,6,23,0.55), rgba(2,6,23,0.72)), url('<?= $base_url ?>/web/img/taron_phanta_opt.jpg');
            background-size: cover;
            background-position: center 25%;
        ">
            <div class="container text-center py-5">
                <span class="landing-badge mb-4 d-inline-block">
                    <i class="fa-solid fa-circle-dot me-1 text-success"></i> La comunidad de coaster enthusiasts
                </span>
                <h1 class="display-2 fw-bold mb-3 landing-title text-white">Descubre tu próxima <span
                        class="text-neon d-block">aventura</span></h1>
                <p class="lead mb-5 mx-auto text-light" style="max-width: 640px; opacity: .9;">La comunidad definitiva para
                    amantes de las montañas rusas. Registra tus experiencias, comparte tus tops y planifica tus viajes.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <a href="<?= Router::url('register') ?>"
                        class="btn btn-success btn-lg px-5 py-3 rounded-0 fw-bold shadow">
                        <i class="fa-solid fa-rocket me-2"></i>Comienza tu aventura
                    </a>
                    <a href="<?= Router::url('coaster_search') ?>"
                        class="btn btn-outline-light btn-lg px-5 py-3 rounded-0 fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>Explorar Coasters
                    </a>
                </div>
                <!-- Mini stats -->
                <div class="d-flex flex-wrap justify-content-center gap-5 mt-5 pt-4 landing-stats-bar">
                    <div>
                        <div class="fw-bold fs-3 text-neon" id="landing-cnt-coasters"></div>
                        <div class="small text-light opacity-75">Montañas rusas</div>
                    </div>
                    <div class="landing-stat-sep"></div>
                    <div>
                        <div class="fw-bold fs-3 text-neon" id="landing-cnt-parks"></div>
                        <div class="small text-light opacity-75">Parques</div>
                    </div>
                    <div class="landing-stat-sep"></div>
                    <div>
                        <div class="fw-bold fs-3 text-neon">100% gratis</div>
                        <div class="small text-light opacity-75">Para siempre</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="py-6 position-relative">
            <div class="container py-5">
                <div class="text-center mb-5">
                    <h2 class="display-6 fw-bold text-white">Todo lo que necesitas</h2>
                    <p class="text-muted mt-2">Una plataforma construida por entusiastas, para entusiastas</p>
                </div>
                <div class="row g-4 justify-content-center" id="features-grid">
                    <!-- Renderizado por index.js una vez cargadas las stats -->
                </div>
            </div>
        </section>

        <!-- CTA FINAL -->
        <section class="landing-cta-section py-5">
            <div class="container py-5 text-center">
                <div class="landing-cta-glow mx-auto mb-5"></div>
                <h2 class="display-5 fw-bold mb-3 text-white">¿Listo para unirte?</h2>
                <p class="text-muted lead mb-5 mx-auto" style="max-width: 560px;">
                    Una cuenta. Acceso a toda la base de datos de coasters del mundo, tus tops, tus viajes y la comunidad.
                </p>
                <!-- Puntos de venta -->
                <div class="row g-3 justify-content-center mb-5">
                    <?php
                    $sellPoints = [
                        'Acceso gratuito',
                        'Sin tarjeta de crédito',
                        'Datos actualizados',
                        'Comunidad activa',
                        'Rankings en tiempo real',
                        'Planificador de viajes',
                    ];
                    foreach ($sellPoints as $sp): ?>
                        <div class="col-sm-4 col-lg-3">
                            <div class="landing-sell-point">
                                <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light"><?= $sp ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= Router::url('register') ?>"
                        class="btn btn-success btn-lg px-5 py-3 rounded-0 fw-bold shadow">
                        <i class="fa-solid fa-rocket me-2"></i>Crear cuenta gratis
                    </a>
                    <a href="<?= Router::url('login') ?>" class="btn btn-outline-light btn-lg px-5 py-3 rounded-0 fw-bold">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Ya tengo cuenta
                    </a>
                </div>
                <p class="text-muted small mt-4">¿Necesitas ayuda? <a href="<?= Router::url('contact') ?>"
                        class="text-success text-decoration-none">Contáctanos</a></p>
            </div>
        </section>

    <?php endif; ?>
</main>


<script src="<?= Router::asset('web/js/shared/index.js') ?>" defer></script>
<script src="<?= Router::asset('web/js/shared/recommendations.js') ?>" defer></script>
<?php if ($is_admin): ?>
    <script src="<?= Router::asset('web/js/shared/carousel_admin.js') ?>" defer></script>
<?php endif; ?>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>