<?php
/** @var string $base_url */
/** @var string $invitation_title (Optional) */
/** @var string $invitation_text (Optional) */

$title = $invitation_title ?? '¡Únete a la comunidad!';
$text = $invitation_text ?? 'Inicia sesión o regístrate para desbloquear todas las funciones de RollerCoaster World, como tus propios tops, gestión de viajes y más.';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg overflow-hidden guest-invitation-card" style="border-radius: 20px; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="invitation-icon-wrapper mb-3 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 80px; height: 80px; background: #10b981; color: white; border-radius: 50%; font-size: 32px;">
                            <i class="fa-solid fa-lock-open"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($title); ?></h2>
                        <p class="text-muted lead mb-4">
                            <?php echo htmlspecialchars($text); ?>
                        </p>
                    </div>
                    
                    <div class="d-grid gap-3 d-sm-flex justify-content-sm-center">
                        <a href="<?= Router::url('login') ?>" class="btn btn-success btn-lg px-5 py-3 fw-bold rounded-pill shadow-sm transition-all hover-scale">
                            <i class="fa-solid fa-right-to-bracket me-2"></i> Iniciar Sesión
                        </a>
                        <a href="<?= Router::url('register') ?>" class="btn btn-outline-success btn-lg px-5 py-3 fw-bold rounded-pill shadow-sm transition-all hover-scale">
                            Regístrate gratis
                        </a>
                    </div>
                    
                    <div class="mt-4 pt-4 border-top">
                        <p class="small text-muted mb-0">
                            ¿Necesitas ayuda? <a href="<?= Router::url('contact') ?>" class="text-success text-decoration-none fw-semibold">Contáctanos</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.guest-invitation-card {
    border: 1px solid rgba(16, 185, 129, 0.1) !important;
}
.invitation-icon-wrapper {
    animation: float 3s ease-in-out infinite;
}
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}
.hover-scale:hover {
    transform: scale(1.05);
}
.transition-all {
    transition: all 0.3s ease;
}
</style>
