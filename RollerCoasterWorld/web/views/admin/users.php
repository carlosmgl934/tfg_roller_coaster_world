<?php
require_once __DIR__ . '/../partials/header.php';

if (!isset($_SESSION['firebase_uid']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ' . $base_url . '/web/views/auth/login.php');
    exit;
}
?>

<link rel="stylesheet" href="<?= Router::asset('web/css/admin_users.css') ?>">

<div class="admin-users-container container py-5">
    <div class="row mb-4 animate__animated animate__fadeIn">
        <div class="col-12">
            <h1 class="display-5 fw-bold text-white mb-2">Gestión de Usuarios</h1>
            <p class="text-muted">Administra los roles y datos de los entusiastas de la plataforma.</p>
        </div>
    </div>

    <!-- Admin Card -->
    <div class="admin-card animate__animated animate__fadeInUp">
        <div class="admin-card-header">
            <div class="search-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="user-search" class="form-control" placeholder="Buscar por nombre, email o usuario...">
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" onclick="location.reload()">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>País</th>
                        <th>Rol</th>
                        <th>Registro</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <!-- Dinámico vía JS -->
                </tbody>
            </table>
        </div>

        <nav class="admin-pagination">
            <ul class="pagination pagination-sm mb-0" id="admin-pagination-list">
                <!-- Dinámico vía JS -->
            </ul>
        </nav>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content custom-admin-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen me-2"></i>Editar Usuario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-user-form">
                    <input type="hidden" id="edit-user-id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="custom-form-label">Username</label>
                            <input type="text" id="edit-username" class="form-control custom-form-input" required>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-form-label">Email</label>
                            <input type="email" id="edit-email" class="form-control custom-form-input" required>
                        </div>
                        <div class="col-12">
                            <label class="custom-form-label">Nombre Completo</label>
                            <input type="text" id="edit-fullname" class="form-control custom-form-input">
                        </div>
                        <div class="col-md-6">
                            <label class="custom-form-label">Fecha de Nacimiento</label>
                            <input type="date" id="edit-birthdate" class="form-control custom-form-input">
                        </div>
                        <div class="col-md-6">
                            <label class="custom-form-label">Género</label>
                            <select id="edit-gender" class="form-select custom-form-input">
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="custom-form-label">Ciudad</label>
                            <input type="text" id="edit-city" class="form-control custom-form-input">
                        </div>
                        <div class="col-md-6">
                            <label class="custom-form-label">País</label>
                            <input type="text" id="edit-country" class="form-control custom-form-input">
                        </div>
                        <div class="col-md-6">
                            <label class="custom-form-label">Rol del Usuario</label>
                            <select id="edit-rol" class="form-select custom-form-input">
                                <option value="user">Usuario (User)</option>
                                <option value="admin">Administrador (Admin)</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="btn-save-user">
                    Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= Router::asset('web/js/admin/admin_users.js') ?>"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
