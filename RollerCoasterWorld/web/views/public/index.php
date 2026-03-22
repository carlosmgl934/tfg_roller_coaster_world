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
        <!-- VISTA DE GUEST: Landing Page Premium -->
        <section class="hero-section text-white py-5 d-flex align-items-center" style="min-height: 70vh; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?= $base_url ?>/web/img/hero-bg.png'); background-size: cover; background-position: center; border-radius: 0 0 50px 50px;">
            <div class="container text-center py-5">
                <h1 class="display-3 fw-bold mb-3 landing-title">Descubre tu próxima <span class="text-success">aventura</span></h1>
                <p class="lead mb-5 mx-auto" style="max-width: 700px;">La comunidad definitiva para amantes de las montañas rusas. Registra tus experiencias, comparte tus tops y planifica tus viajes.</p>
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                    <a href="<?= Router::url('register') ?>" class="btn btn-success btn-lg px-5 py-3 rounded-pill fw-bold shadow transition-all hover-scale">
                        Comienza ahora
                    </a>
                    <a href="<?= Router::url('coaster_search') ?>" class="btn btn-outline-light btn-lg px-5 py-3 rounded-pill fw-bold transition-all">
                        Explorar Coasters
                    </a>
                </div>
            </div>
        </section>

        <section class="invitation-section py-5">
            <div class="container py-5 text-center">
                <h2 class="fw-bold mb-4">Lo que te estás perdiendo...</h2>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="p-4 rounded-4 bg-white shadow-sm h-100">
                            <i class="fa-solid fa-list-ol text-success fs-1 mb-3"></i>
                            <h5 class="fw-bold">Tops Personalizados</h5>
                            <p class="text-muted">Crea tu propio ranking de montañas rusas y compártelo con el mundo.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 rounded-4 bg-white shadow-sm h-100">
                            <i class="fa-solid fa-map-location-dot text-success fs-1 mb-3"></i>
                            <h5 class="fw-bold">Gestor de Viajes</h5>
                            <p class="text-muted">Planifica rutas por los mejores parques de Europa con nuestro generador inteligente.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-4 rounded-4 bg-white shadow-sm h-100">
                            <i class="fa-solid fa-users text-success fs-1 mb-3"></i>
                            <h5 class="fw-bold">Conecta</h5>
                            <p class="text-muted">Haz amigos, mira sus estadísticas y compite por ser el que más coasters ha montado.</p>
                        </div>
                    </div>
                </div>
                
                <?php 
                $invitation_title = "¿Listo para unirte?";
                $invitation_text = "Crea tu cuenta hoy mismo y empieza a registrar tus experiencias en los mejores parques del mundo.";
                require __DIR__ . '/../partials/login_invitation.php'; 
                ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<style>
.index-container {
    overflow-x: hidden;
}
.landing-title {
    font-family: 'Outfit', sans-serif;
    letter-spacing: -1px;
}
.icon-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.hover-translate-y {
    transition: transform 0.3s ease;
}
.hover-translate-y:hover {
    transform: translateY(-10px);
}
.hover-scale:hover {
    transform: scale(1.05);
}
.transition-all {
    transition: all 0.3s ease;
}
.rounded-4 {
    border-radius: 1rem !important;
}
</style>

<?php
require_once __DIR__ . '/../partials/footer.php';
?>