<?php
require_once __DIR__ . '/../partials/header.php';
/** @var string $base_url */
/** @var bool $is_logged */

// Obtener datos básicos si está logueado
$user_email = $_SESSION['user_email'] ?? 'Invitado';
$user_uid = $_SESSION['firebase_uid'] ?? null;
$display_name = "Usuario";
if ($user_email !== 'Invitado' && $user_email !== 'Desconocido') {
    $parts = explode('@', $user_email);
    $display_name = $parts[0];
}
?>

<main class="index-container">
    <?php if ($is_logged): ?>
        <!-- VISTA DE USUARIO LOGUEADO: Dashboard Personalizado -->
        <section class="dashboard-hero py-5">
            <div class="container py-4">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <h1 class="display-4 fw-bold text-dark mb-3">Hola, <span class="text-success"><?php echo htmlspecialchars($display_name); ?></span></h1>
                        <p class="lead text-muted mb-4">Es un placer verte de nuevo. ¿Qué aventura tenemos planeada para hoy?</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= Router::url('profile') ?>" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm">
                                <i class="fa-solid fa-id-card me-2"></i> Mi Perfil
                            </a>
                            <a href="<?= Router::url('trips') ?>" class="btn btn-outline-success px-4 py-2 rounded-pill fw-bold">
                                <i class="fa-solid fa-suitcase-rolling me-2"></i> Mis Viajes
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="dashboard-card card border-0 shadow-sm p-4 text-center bg-white" style="border-radius: 20px;">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-4">
                                        <h3 class="fw-bold mb-1 text-success">12</h3>
                                        <p class="small text-muted mb-0">Coasters</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-4">
                                        <h3 class="fw-bold mb-1 text-primary">5</h3>
                                        <p class="small text-muted mb-0">Parques</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-4">
                                        <h3 class="fw-bold mb-1 text-warning">8</h3>
                                        <p class="small text-muted mb-0">Reseñas</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-4">
                                        <h3 class="fw-bold mb-1 text-info">#42</h3>
                                        <p class="small text-muted mb-0">Ranking</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="quick-access py-5 bg-light">
            <div class="container py-4">
                <h2 class="fw-bold mb-5 text-center">Acceso Rápido</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm transition-all hover-translate-y" style="border-radius: 20px;">
                            <div class="card-body p-4 text-center">
                                <div class="icon-circle mb-3 mx-auto bg-primary bg-opacity-10 text-primary">
                                    <i class="fa-solid fa-magnifying-glass fs-4"></i>
                                </div>
                                <h5 class="fw-bold">Buscar Coasters</h5>
                                <p class="text-muted small">Explora nuestra base de datos con miles de montañas rusas.</p>
                                <a href="<?= Router::url('coaster_search') ?>" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm transition-all hover-translate-y" style="border-radius: 20px;">
                            <div class="card-body p-4 text-center">
                                <div class="icon-circle mb-3 mx-auto bg-warning bg-opacity-10 text-warning">
                                    <i class="fa-solid fa-trophy fs-4"></i>
                                </div>
                                <h5 class="fw-bold">Tops Globales</h5>
                                <p class="text-muted small">Mira cuáles son las favoritas de la comunidad actualmente.</p>
                                <a href="<?= Router::url('coaster_tops') ?>" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm transition-all hover-translate-y" style="border-radius: 20px;">
                            <div class="card-body p-4 text-center">
                                <div class="icon-circle mb-3 mx-auto bg-danger bg-opacity-10 text-danger">
                                    <i class="fa-solid fa-comments fs-4"></i>
                                </div>
                                <h5 class="fw-bold">Comunidad</h5>
                                <p class="text-muted small">Únete a las discusiones en nuestros foros especializados.</p>
                                <a href="<?= Router::url('forum_search') ?>" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <?php else: ?>
        <!-- ======================== VISTA GUEST ======================== -->

        <!-- HERO -->
        <section class="landing-hero d-flex align-items-center" style="
            min-height: 100vh;
            background: linear-gradient(rgba(2,6,23,0.55), rgba(2,6,23,0.72)), url('<?= $base_url ?>/web/img/taron_phanta.jpeg');
            background-size: cover;
            background-position: center 25%;
        ">
            <div class="container text-center py-5">
                <span class="landing-badge mb-4 d-inline-block">
                    <i class="fa-solid fa-circle-dot me-1 text-success"></i> La comunidad de coaster enthusiasts
                </span>
                <h1 class="display-2 fw-bold mb-3 landing-title text-white">Descubre tu próxima <span class="text-neon d-block">aventura</span></h1>
                <p class="lead mb-5 mx-auto text-light" style="max-width: 640px; opacity: .9;">La comunidad definitiva para amantes de las montañas rusas. Registra tus experiencias, comparte tus tops y planifica tus viajes.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <a href="<?= Router::url('register') ?>" class="btn btn-success btn-lg px-5 py-3 rounded-0 fw-bold shadow">
                        <i class="fa-solid fa-rocket me-2"></i>Comienza tu aventura
                    </a>
                    <a href="<?= Router::url('coaster_search') ?>" class="btn btn-outline-light btn-lg px-5 py-3 rounded-0 fw-bold">
                        <i class="fa-solid fa-magnifying-glass me-2"></i>Explorar Coasters
                    </a>
                </div>
                <!-- Mini stats -->
                <div class="d-flex flex-wrap justify-content-center gap-5 mt-5 pt-4 landing-stats-bar">
                    <div>
                        <div class="fw-bold fs-3 text-neon">+20.000</div>
                        <div class="small text-light opacity-75">Montañas rusas</div>
                    </div>
                    <div class="landing-stat-sep"></div>
                    <div>
                        <div class="fw-bold fs-3 text-neon">+2.500</div>
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
                <div class="row g-4 justify-content-center">
                    <?php
                    $features = [
                        ['icon'=>'fa-database',         'title'=>'Base de datos completa',   'desc'=>'Más de 20.000 coasters indexados de todo el mundo con datos técnicos completos.'],
                        ['icon'=>'fa-list-ol',          'title'=>'Tops personalizados',      'desc'=>'Crea y ordena tu ranking personal. Compáralo con el de la comunidad.'],
                        ['icon'=>'fa-suitcase-rolling', 'title'=>'Gestor de viajes',         'desc'=>'Planifica tus rutas por parques, calcula distancias y gestiona tus trips.'],
                        ['icon'=>'fa-camera',           'title'=>'Galería de fotos',         'desc'=>'Sube fotos de tus visitas y descubre las de otros usuarios.'],
                        ['icon'=>'fa-comments',         'title'=>'Foros especializados',     'desc'=>'Debate sobre coasters, parques, noticias y novedades del sector.'],
                        ['icon'=>'fa-trophy',           'title'=>'Ranking global',           'desc'=>'Compite con otros enthusiasts y escala en el ranking de credits.'],
                    ];
                    foreach ($features as $f): ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="landing-feature-card h-100 p-4 text-center rounded-0">
                            <i class="fa-solid <?= $f['icon'] ?> fa-2x text-neon mb-3"></i>
                            <h5 class="fw-bold mb-2 text-white"><?= $f['title'] ?></h5>
                            <p class="text-muted small mb-0"><?= $f['desc'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Acceso gratuito</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Sin tarjeta de crédito</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Datos actualizados</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Comunidad activa</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Rankings en tiempo real</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Planificador de viajes</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="<?= Router::url('register') ?>" class="btn btn-success btn-lg px-5 py-3 rounded-0 fw-bold shadow">
                        <i class="fa-solid fa-rocket me-2"></i>Crear cuenta gratis
                    </a>
                    <a href="<?= Router::url('login') ?>" class="btn btn-outline-light btn-lg px-5 py-3 rounded-0 fw-bold">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Ya tengo cuenta
                    </a>
                </div>
                <p class="text-muted small mt-4">¿Necesitas ayuda? <a href="<?= Router::url('contact') ?>" class="text-success text-decoration-none">Contáctanos</a></p>
            </div>
        </section>

    <?php endif; ?>
</main>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/home.css">

<?php
require_once __DIR__ . '/../partials/footer.php';
?>