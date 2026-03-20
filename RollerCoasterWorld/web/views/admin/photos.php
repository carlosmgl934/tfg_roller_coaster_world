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
        <span class="badge bg-success text-white px-3 py-2 fs-6 rounded-pill">
            <span id="pending-count">0</span> pendientes
        </span>
    </div>

    <!-- Filtros/Busqueda (Opcional visualmente) -->
    <div class="card bg-dark border-secondary mb-4">
        <div class="card-body p-3 d-flex gap-3 align-items-center">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-transparent border-secondary text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" class="form-control bg-transparent border-secondary text-white" placeholder="Buscar por usuario o parque..." id="search-pending">
            </div>
            <select class="form-select bg-transparent border-secondary text-white" style="max-width: 200px;" id="filter-type">
                <option value="all">Todas las fotos</option>
                <option value="coasters">Montañas Rusas</option>
                <option value="parks">Parques</option>
            </select>
            <button class="btn btn-outline-secondary ms-auto" id="btn-refresh">
                <i class="fa-solid fa-rotate-right me-1"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Grid de fotos pendientes -->
    <div class="row g-4" id="pending-photos-container">
        <!-- Renderizado dinámico de JS. Ejemplo de tarjeta para que te guíes en tu JS:
        
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card border-secondary bg-dark h-100 overflow-hidden shadow-sm hover-elevate">
                <div class="position-relative">
                    <img src="URL_IMAGEN" class="card-img-top" style="height:220px; object-fit:cover;" alt="Foto">
                    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2"><i class="fa-solid fa-clock me-1"></i>Pendiente</span>
                </div>
                <div class="card-body">
                    <h5 class="card-title text-success fw-bold text-truncate">UserCaption o Nombre</h5>
                    <p class="card-text text-muted mb-1 small"><i class="fa-solid fa-user me-1"></i> Subido por: <strong>@username</strong></p>
                    <p class="card-text text-muted mb-3 small"><i class="fa-solid fa-map-pin me-1"></i> Destino: <strong>Red Force</strong></p>
                    
                    <div class="d-flex gap-2 mt-auto">
                        <button class="btn btn-success flex-grow-1 btn-approve" data-id="ID_FOTO">
                            <i class="fa-solid fa-check me-1"></i> Aprobar
                        </button>
                        <button class="btn btn-outline-danger flex-grow-1 btn-reject" data-id="ID_FOTO">
                            <i class="fa-solid fa-xmark me-1"></i> Rechazar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        -->
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

</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="<?= $base_url ?>/web/js/admin.js"></script>
