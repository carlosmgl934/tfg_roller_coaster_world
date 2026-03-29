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

// ── Estadísticas Globales con Caché (10 min) ──────────────────────────────────
require_once __DIR__ . '/../../../api/database/db_conexion.php';
require_once __DIR__ . '/../../../api/utils/cache_helper.php';

$stat_users = '—';
$stat_coasters = '—';
$stat_reviews = '—';
$stat_photos = '—';
$stat_parks = '—';

try {
    $db = new DBConexion();

    $stats = CacheHelper::get('global_stats', 600, function () use ($db) {
        $sql = "SELECT 
                  (SELECT COUNT(*) FROM users) as users,
                  (SELECT COUNT(*) FROM coasters) as coasters,
                  (SELECT COUNT(*) FROM coaster_ratings) as cr,
                  (SELECT COUNT(*) FROM park_ratings) as pr,
                  (SELECT COUNT(*) FROM coaster_photos) as photos,
                  (SELECT COUNT(*) FROM parks) as parks";
        $stmt = $db->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'users' => $row['users'],
            'coasters' => $row['coasters'],
            'reviews' => (int) $row['cr'] + (int) $row['pr'],
            'photos' => $row['photos'],
            'parks' => $row['parks']
        ];
    });

    $stat_users = $stats['users'];
    $stat_coasters = $stats['coasters'];
    $stat_reviews = $stats['reviews'];
    $stat_photos = $stats['photos'];
    $stat_parks = $stats['parks'];

} catch (Exception $e) {
    error_log("Error cargando stats en index.php: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/home.css">


<main class="index-container">
    <?php if ($is_logged): ?>
        <!-- COMIENZO VISTAS MOCKUP (DASHBOARD) -->
        <?php
        $user_credits = 0;
        $user_reviews = 0;
        $user_tops = 0;
        $user_trips = 0;
        $user_friends = 0;
        $user_photos = 0;
        $user_location = '—';
        $fav_coaster = 'Ninguna configurada';

        try {
            // ── Fetch the user's integer id from firebase_uid ─────────────
            $stmt = $db->prepare(
                "SELECT id, username, city, country, favorite_coaster, profile_image FROM users WHERE firebase_uid = ?"
            );
            $stmt->execute([$user_uid]);
            $user_row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_row) {
                $user_db_id = (int) $user_row['id'];
                $display_name = $user_row['username'] ?: $display_name;
                $profile_image = $user_row['profile_image'] ?? null;

                // Location from users table
                if ($user_row['city'] && $user_row['country']) {
                    $user_location = $user_row['city'] . ', ' . $user_row['country'];
                } elseif ($user_row['country']) {
                    $user_location = $user_row['country'];
                } elseif ($user_row['city']) {
                    $user_location = $user_row['city'];
                }

                // Favourite coaster from users table
                if ($user_row['favorite_coaster']) {
                    $fav_coaster = $user_row['favorite_coaster'];
                }

                // ── Per-user stats (Consolidated into 1 query) ────────
                $user_stats_sql = "SELECT 
                    (SELECT COUNT(*) FROM user_credits WHERE user_id = :uid) as credits,
                    (SELECT COUNT(*) FROM coaster_ratings WHERE user_id = :uid) as reviews,
                    (SELECT COUNT(*) FROM user_park_credits WHERE user_id = :uid) as parks,
                    (SELECT COUNT(*) FROM trips WHERE user_id = :uid) as trips,
                    (SELECT COUNT(*) FROM coaster_photos WHERE user_id = :uid) as photos,
                    (SELECT COUNT(*) FROM friendship WHERE estado_solicitud = 'ACEPTADA' AND (solicitante_id = :uid OR solicitada_id = :uid)) as friends";

                $stmt_stats = $db->prepare($user_stats_sql);
                $stmt_stats->execute(['uid' => $user_db_id]);
                $u_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

                $user_credits = (int) ($u_stats['credits'] ?? 0);
                $user_reviews = (int) ($u_stats['reviews'] ?? 0);
                $user_tops = (int) ($u_stats['parks'] ?? 0);
                $user_trips = (int) ($u_stats['trips'] ?? 0);
                $user_friends = (int) ($u_stats['friends'] ?? 0);
                $user_photos = (int) ($u_stats['photos'] ?? 0);
            }

        } catch (Exception $e) {
            // Silencioso en producción o log errors para debugging
            error_log("Error fetching stats Dashboard: " . $e->getMessage());
        }

        // Avatar Initial
        $initial = strtoupper(substr($display_name, 0, 1) ?: '?');
        $profile_image = $profile_image ?? null;
        ?>

        <!-- HERO CAROUSEL -->
        <div class="home-hero">
            <div class="home-hero-slide active" style="background-image:url('<?= $base_url ?>/web/img/taron_phanta.webp')">
            </div>
            <!-- Add more slides later if needed -->

            <div class="home-hero-content">
                <div class="home-hero-tag animate d1"><i class="fa-solid fa-circle-notch fa-spin"
                        style="font-size:0.6rem;"></i> Tu Panel Personal</div>
                <h1 class="home-hero-title animate d2">Bienvenido,<br><span
                        style="color:var(--rcw-green)"><?= htmlspecialchars($display_name) ?></span></h1>
                <p class="home-hero-sub animate d3">Bienvenido de nuevo a la comunidad de amantes de las montañas rusas.
                    Sigue explorando y aumentando tus créditos.</p>
                <div class="home-hero-btns animate d3">
                    <a href="<?= Router::url('coaster_search') ?>" class="btn-green-rcw"><i
                            class="fa-solid fa-magnifying-glass"></i> Buscar coasters</a>
                    <a href="<?= Router::url('forum_search') ?>" class="btn-ghost-rcw"><i class="fa-solid fa-users"></i>
                        Foros</a>
                </div>
            </div>

            <!-- Optional: Carousel dots -->
            <div class="home-hero-dots">
                <div class="home-hero-dot active"></div>
            </div>
        </div>

        <!-- STATS BAR -->
        <div class="home-stats-bar">
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-users"><?= number_format((float) $stat_users) ?></div>
                <div class="home-stat-label">Usuarios</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-coasters"><?= number_format((float) $stat_coasters) ?></div>
                <div class="home-stat-label">Montañas Rusas</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-reviews"><?= number_format((float) $stat_reviews) ?></div>
                <div class="home-stat-label">Reseñas</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-photos"><?= number_format((float) $stat_photos) ?></div>
                <div class="home-stat-label">Fotos</div>
            </div>
            <div class="home-stat-divider"></div>
            <div class="home-stat-item">
                <div class="home-stat-num" id="cnt-parks"><?= number_format((float) $stat_parks) ?></div>
                <div class="home-stat-label">Parques</div>
            </div>
        </div>

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
                <a href="<?= Router::url('coaster_tops') ?>" class="home-qlink">
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

        <!-- PERFIL Y NOTICIAS -->
        <div class="home-section" style="padding-top:0;">
            <div class="row g-4">

                <!-- PERFIL MINI -->
                <div class="col-12 col-lg-4 d-flex flex-column">
                    <div class="home-section-title" style="margin-bottom:1.2rem;">Tu Perfil</div>
                    <div class="home-profile-mini flex-grow-1">
                        <div class="home-profile-mini-header">
                            <?php if ($profile_image): ?>
                                <div class="home-profile-mini-avatar"
                                    style="background-image:url('<?= htmlspecialchars($profile_image) ?>');background-size:cover;background-position:center;">
                                </div>
                            <?php else: ?>
                                <div
                                    class="home-profile-mini-avatar d-flex justify-content-center align-items-center fw-bold fs-4 bg-success text-white">
                                    <?= $initial ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div class="home-profile-mini-name"><?= htmlspecialchars($display_name) ?></div>
                                <div class="home-profile-mini-role"><i
                                        class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($user_location) ?>
                                </div>
                            </div>
                        </div>
                        <div class="home-profile-mini-stats">
                            <div class="home-profile-mini-stat">
                                <div class="home-profile-mini-stat-num"><?= $user_credits ?: 0 ?></div>
                                <div class="home-profile-mini-stat-label">Coaster Count</div>
                            </div>
                            <div class="home-profile-mini-stat">
                                <div class="home-profile-mini-stat-num"><?= $user_reviews ?: 0 ?></div>
                                <div class="home-profile-mini-stat-label">Reseñas</div>
                            </div>
                            <div class="home-profile-mini-stat">
                                <div class="home-profile-mini-stat-num"><?= $user_tops ?: 0 ?></div>
                                <div class="home-profile-mini-stat-label">Parques</div>
                            </div>
                            <div class="home-profile-mini-stat" style="border-bottom:none;">
                                <div class="home-profile-mini-stat-num"><?= $user_trips ?: 0 ?></div>
                                <div class="home-profile-mini-stat-label">Viajes</div>
                            </div>
                            <div class="home-profile-mini-stat" style="border-bottom:none;">
                                <div class="home-profile-mini-stat-num"><?= $user_friends ?: 0 ?></div>
                                <div class="home-profile-mini-stat-label">Amigos</div>
                            </div>
                            <div class="home-profile-mini-stat" style="border-bottom:none;">
                                <div class="home-profile-mini-stat-num"><?= $user_photos ?: 0 ?></div>
                                <div class="home-profile-mini-stat-label">Fotos</div>
                            </div>
                        </div>
                        <div class="home-favorite-coaster">
                            <i class="fa-solid fa-star" style="color:var(--rcw-green);"></i>
                            <span>Favorita: <strong class="text-truncate ms-1"
                                    style="max-width: 140px; display: inline-block; vertical-align: bottom;"><?= htmlspecialchars($fav_coaster) ?></strong></span>
                        </div>
                        <div class="home-profile-mini-footer">
                            <a href="<?= Router::url('profile') ?>" class="home-btn-mini"><i
                                    class="fa-solid fa-user me-1"></i>Perfil</a>
                            <a href="<?= Router::url('profile') ?>#config" class="home-btn-mini"><i
                                    class="fa-solid fa-gear me-1"></i>Config</a>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN DE NOTICIAS (Dinámica) -->
                <?php
                try {
                    $news_stmt = $db->query("SELECT * FROM news ORDER BY is_featured DESC, created_at DESC LIMIT 3");
                    $all_news = $news_stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $all_news = [];
                    error_log("Error fetching news: " . $e->getMessage());
                }

                $featured_news = $all_news[0] ?? null;
                $small_news = array_slice($all_news, 1);
                ?>

                <div class="col-12 col-lg-8 d-flex flex-column">
                    <div class="home-section-title" style="margin-bottom:1.2rem;">Últimas Novedades</div>
                    <div class="home-news-grid flex-grow-1">
                        <?php if ($featured_news):
                            $has_img = !empty($featured_news['image_url']);
                            $img_src = $has_img ? (str_starts_with($featured_news['image_url'], 'http') ? $featured_news['image_url'] : $base_url . $featured_news['image_url']) : '';
                            ?>
                            <a href="<?= $featured_news['external_link'] ?: '#' ?>"
                                class="home-news-card big <?= !$has_img ? 'no-photo' : '' ?>">
                                <?php if ($has_img): ?>
                                    <img src="<?= $img_src ?>" class="home-news-img" alt="" loading="lazy">
                                <?php endif; ?>
                                <div class="home-news-body">
                                    <div class="home-news-tag">
                                        <i
                                            class="fa-solid <?= $featured_news['tag'] === 'Destacado' ? 'fa-bolt' : 'fa-info-circle' ?> me-1"></i>
                                        <?= htmlspecialchars($featured_news['tag'] ?: 'Novedad') ?>
                                    </div>
                                    <div class="home-news-title"><?= htmlspecialchars($featured_news['title']) ?></div>
                                    <div class="home-news-desc"><?= htmlspecialchars($featured_news['description']) ?></div>
                                    <div class="home-news-date"><i
                                            class="fa-regular fa-clock me-1"></i><?= date('d/m/Y', strtotime($featured_news['created_at'])) ?>
                                    </div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <div class="home-news-small-col">
                            <?php foreach ($small_news as $news_item):
                                $has_img_s = !empty($news_item['image_url']);
                                $img_src_s = $has_img_s ? (str_starts_with($news_item['image_url'], 'http') ? $news_item['image_url'] : $base_url . $news_item['image_url']) : '';
                                ?>
                                <a href="<?= $news_item['external_link'] ?: '#' ?>"
                                    class="home-news-card small <?= !$has_img_s ? 'no-photo' : '' ?>">
                                    <?php if ($has_img_s): ?>
                                        <img src="<?= $img_src_s ?>" class="home-news-img" alt="" loading="lazy">
                                    <?php endif; ?>
                                    <div class="home-news-body d-flex flex-column justify-content-center">
                                        <div class="home-news-tag <?= $news_item['tag'] === 'Viajes' ? 'text-warning' : '' ?>">
                                            <?= htmlspecialchars($news_item['tag'] ?: 'Info') ?>
                                        </div>
                                        <div class="home-news-title"><?= htmlspecialchars($news_item['title']) ?></div>
                                        <div class="home-news-desc"><?= htmlspecialchars($news_item['description']) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>

                            <?php if (count($all_news) === 0): ?>
                                <div class="text-center text-muted p-5 bg-dark border rounded-0 w-100">
                                    <i class="fa-solid fa-newspaper fa-3x mb-3 opacity-25"></i>
                                    <p>No hay novedades publicadas por el momento.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <script src="<?= $base_url ?>/web/js/home.js"></script>
        <!-- FIN VISTAS MOCKUP (DASHBOARD) -->

    <?php else: ?>
        <!-- ======================== VISTA GUEST ======================== -->

        <!-- HERO -->
        <section class="landing-hero d-flex align-items-center" style="
            min-height: 100vh;
            background: linear-gradient(rgba(2,6,23,0.55), rgba(2,6,23,0.72)), url('<?= $base_url ?>/web/img/taron_phanta.webp');
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
                        <div class="fw-bold fs-3 text-neon">
                            +<?= is_numeric($stat_coasters) ? number_format((float) $stat_coasters) : '10.000' ?></div>
                        <div class="small text-light opacity-75">Montañas rusas</div>
                    </div>
                    <div class="landing-stat-sep"></div>
                    <div>
                        <div class="fw-bold fs-3 text-neon">
                            +<?= is_numeric($stat_parks) ? number_format((float) $stat_parks) : '2.500' ?></div>
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
                        ['icon' => 'fa-database', 'title' => 'Base de datos completa', 'desc' => 'Más de ' . (is_numeric($stat_coasters) ? number_format((float) $stat_coasters) : '10.000') . ' coasters indexados de todo el mundo con datos técnicos completos.'],
                        ['icon' => 'fa-list-ol', 'title' => 'Tops personalizados', 'desc' => 'Crea y ordena tu ranking personal. Compáralo con el de la comunidad.'],
                        ['icon' => 'fa-suitcase-rolling', 'title' => 'Gestor de viajes', 'desc' => 'Planifica tus rutas por parques, calcula distancias y gestiona tus trips.'],
                        ['icon' => 'fa-camera', 'title' => 'Galería de fotos', 'desc' => 'Sube fotos de tus visitas y descubre las de otros usuarios.'],
                        ['icon' => 'fa-comments', 'title' => 'Foros especializados', 'desc' => 'Debate sobre coasters, parques, noticias y novedades del sector.'],
                        ['icon' => 'fa-trophy', 'title' => 'Ranking global', 'desc' => 'Compite con otros enthusiasts y escala en el ranking de credits.'],
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
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Sin tarjeta de
                                crédito</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Datos
                                actualizados</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Comunidad activa</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Rankings en tiempo
                                real</span>
                        </div>
                    </div>
                    <div class="col-sm-4 col-lg-3">
                        <div class="landing-sell-point">
                            <i class="fa-solid fa-check text-neon me-2"></i><span class="text-light">Planificador de
                                viajes</span>
                        </div>
                    </div>
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


<?php
require_once __DIR__ . '/../partials/footer.php';
?>