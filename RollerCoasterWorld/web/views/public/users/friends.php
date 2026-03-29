<?php
require_once __DIR__ . '/../../partials/header.php';
// Solo logueados
if (!$is_logged) {
    Router::redirect('login');
}
?>

<main class="container-fluid px-lg-5 my-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h1 class="display-6 fw-bold border-bottom pb-2 text-success">
                <i class="fa-solid fa-user-group me-2"></i> Mis Amistades
            </h1>
            <p class="text-muted">Gestiona tus peticiones y descubre los tops y viajes de tus amigos.</p>
        </div>
    </div>

    <div class="row g-4 justify-content-center" id="friends-container" style="display: none;">
        
        <!-- Peticiones Pendientes -->
        <div class="col-12 col-lg-4">
            <div class="card bg-dark text-white shadow border-warning border-opacity-50 h-100">
                <div class="card-header bg-transparent border-bottom border-warning border-opacity-25 pb-3 pt-4">
                    <h5 class="mb-0 fw-bold text-warning"><i class="fa-solid fa-bell me-2"></i>Solicitudes <span class="badge bg-warning text-dark ms-2 rounded-pill shadow-sm" id="requests-count">0</span></h5>
                </div>
                <div class="card-body p-0">
                    <div id="requests-list" class="list-group list-group-flush">
                        <!-- Peticiones inyectadas via JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Mis Amigos -->
        <div class="col-12 col-lg-8">
            <div class="card bg-dark text-white shadow border-success border-opacity-50 h-100">
                <div class="card-header bg-transparent border-bottom border-success border-opacity-25 pb-3 pt-4 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa-solid fa-users me-2"></i>Lista de Amigos</h5>
                    <span class="badge bg-success shadow-sm fs-6" id="friends-count">0</span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3" id="friends-list">
                        <!-- Amigos inyectados via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Peticiones enviadas (Opcional, miniatura) -->
    <div class="row mt-5 justify-content-center" id="sent-requests-container" style="display: none;">
        <div class="col-12 col-lg-12">
            <div class="accordion accordion-flush" id="accordionSent">
                <div class="accordion-item bg-dark text-white border-0 shadow-sm rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed bg-dark text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSent">
                            <i class="fa-solid fa-paper-plane text-muted me-2"></i> Solicitudes enviadas (<span id="sent-count">0</span>)
                        </button>
                    </h2>
                    <div id="collapseSent" class="accordion-collapse collapse" data-bs-parent="#accordionSent">
                        <div class="accordion-body p-3">
                            <ul class="list-group list-group-flush" id="sent-list">
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="friends-loading">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-success" role="status"></div>
            <p class="mt-3 text-muted">Cargando amistades...</p>
        </div>
    </div>
</main>

<script src="<?= $base_url ?>/web/js/friends_manager.js"></script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
