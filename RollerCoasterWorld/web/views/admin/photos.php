<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid'])) {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= $base_url ?>/web/css/admin.css">

<main class="container-fluid px-lg-5 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-white fw-bold"><i class="fa-solid fa-images text-success me-2"></i>Aprobar Fotografías</h1>
            <p class="text-muted mb-0">Gestión de imágenes subidas por los usuarios pendientes de moderación.</p>
        </div>
        <span class="badge bg-success text-white px-3 py-2 fs-6 rounded-0">
            <span id="pending-count">0</span> pendientes
        </span>
    </div>

    <!-- Filtros/Busqueda (Opcional visualmente) -->
    <div class="card bg-dark border-secondary mb-4 rounded-0">
        <div class="card-body p-3 d-flex gap-3 align-items-center">
            <div class="input-group" style="max-width: 350px;">
                <span class="input-group-text bg-transparent border-secondary text-muted rounded-0"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" class="form-control bg-transparent border-secondary text-white rounded-0" placeholder="Buscar por usuario o montaña rusa..." id="search-pending">
            </div>
            
            <button class="btn btn-outline-secondary ms-auto rounded-0" id="btn-refresh">
                <i class="fa-solid fa-rotate-right me-1"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Grid de fotos pendientes -->
    <div class="row g-4" id="pending-photos-container">
        <!-- Renderizado dinámico de JS -->
        <div class="col-12 text-center py-5" id="loading-spinner">
            <div class="spinner-border text-success" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="text-muted mt-3">Cargando fotografías pendientes...</p>
        </div>

        <!-- Estado vacío (sin fotos pendientes) -->
        <div class="col-12 text-center py-5 d-none" id="empty-state">
            <div class="mb-3">
                <i class="fa-regular fa-folder-open" style="font-size: 3.5rem; color: #4b5563;"></i>
            </div>
            <h4 class="text-white fw-bold">No hay fotos pendientes</h4>
            <p class="text-muted">¡Todo al día! Actualmente no hay ninguna fotografía esperando aprobación.</p>
        </div>
    </div>

    <!-- LIGHTBOX MODAL — Ver foto completa -->
    <div class="modal fade" id="lightbox-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="modal-body p-0 text-center position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" style="z-index:10;" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img id="lightbox-img" src="" alt="Foto completa" class="img-fluid w-100" style="max-height:90vh; object-fit:contain;">
                </div>
            </div>
        </div>
    </div>

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/admin/admin.js"></script>
