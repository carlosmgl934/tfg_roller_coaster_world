<?php
require_once __DIR__ . '/../partials/header.php';

if (!$is_logged || !$is_admin) {
    Router::redirect('login');
    exit;
}
?>

<link rel="stylesheet" href="<?= Router::asset('web/css/coasters.css') ?>">
<link rel="stylesheet" href="<?= Router::asset('web/css/admin.css') ?>">

<main class="container-fluid px-lg-5 my-5">

    <!-- Cabecera -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-3">
            <h1 class="display-6 fw-bold text-success mb-0">
                <i class="fa-solid fa-users me-3"></i>Gestión de Usuarios
            </h1>
            <span class="text-muted small" id="users-count-label"></span>
        </div>
    </div>

    <div class="row g-4">

        <!-- ===================== IZQUIERDA: Filtros ===================== -->
        <aside class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">

                    <!-- Rol -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-rol">
                            <option value="">Todos los roles</option>
                            <option value="admin">Administrador</option>
                            <option value="user">Usuario</option>
                        </select>
                    </div>

                    <!-- País -->
                    <div class="mb-4">
                        <input type="text" class="form-control shadow-sm rounded-0" id="filter-country" placeholder="Filtrar por país...">
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-users-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-users-borrar">
                            <i class="fa-solid fa-eraser me-2"></i>Limpiar filtros
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ===================== DERECHA: Lista ===================== -->
        <div class="col-12 col-lg-9">

            <!-- Barra de búsqueda -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <div class="flex-grow-1 position-relative">
                    <input type="text" id="user-search"
                        class="form-control shadow-sm pe-5 border-success rounded-0"
                        placeholder="Buscar por nombre, email o usuario..."
                        style="border-width: 2px;">
                    <i id="user-search-icon" class="fa-solid fa-magnifying-glass text-muted position-absolute"
                        style="right: 14px; top: 50%; transform: translateY(-50%); cursor: default;"></i>
                </div>
                <button class="btn btn-outline-secondary rounded-0" onclick="loadUsers()" title="Recargar">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="users-count"></p>

            <!-- Lista de usuarios -->
            <div class="list-group shadow-sm rounded-0" id="admin-users-list"
                 style="max-height: 600px; overflow-y: auto; overflow-x: hidden;">
                <div class="list-group-item text-center text-muted py-5" id="admin-users-loading">
                    <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando usuarios...
                </div>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-center mt-4" id="admin-users-pagination"></div>

        </div><!-- /col lista -->

    </div><!-- /row -->

</main>


<!-- ===================== MODAL EDITAR USUARIO ===================== -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">

            <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-user-pen fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0">Editar Usuario</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-5" style="background:#161b22;">
                <form id="edit-user-form">
                    <input type="hidden" id="edit-user-id">
                    <div class="row g-4">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Username</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-at"></i>
                                </span>
                                <input type="text" id="edit-username" class="form-control rounded-0" required
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Email</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <input type="email" id="edit-email" class="form-control rounded-0" required
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Nombre Completo</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-user"></i>
                                </span>
                                <input type="text" id="edit-fullname" class="form-control rounded-0"
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Fecha de Nacimiento</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-regular fa-calendar-days"></i>
                                </span>
                                <input type="date" id="edit-birthdate" class="form-control rounded-0"
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none; color-scheme:dark;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Género</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-venus-mars"></i>
                                </span>
                                <select id="edit-gender" class="form-select rounded-0"
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                                    <option value="Masculino">Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Ciudad</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-city"></i>
                                </span>
                                <input type="text" id="edit-city" class="form-control rounded-0"
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">País</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-earth-americas"></i>
                                </span>
                                <input type="text" id="edit-country" class="form-control rounded-0"
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">Rol del Usuario</label>
                            <div class="input-group">
                                <span class="input-group-text rounded-0 border-success" style="background:#0d1117; border-width:2px; color:#198754; border-right:none;">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </span>
                                <select id="edit-rol" class="form-select rounded-0"
                                    style="border-width:2px;border-color:#198754;background:#0d1117;color:#e6edf3; border-left:none; box-shadow:none;">
                                    <option value="user">Usuario (User)</option>
                                    <option value="admin">Administrador (Admin)</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </form>

                <!-- Mensajes -->
                <div id="edit-user-messages" class="w-100 mt-4 d-none">
                    <div class="alert alert-danger rounded-0 border-0 mb-0 d-none" id="edit-user-error">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><span></span>
                    </div>
                    <div class="alert alert-success rounded-0 border-0 mb-0 d-none" id="edit-user-success">
                        <i class="fa-solid fa-circle-check me-2"></i><span></span>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 px-5 pb-4 pt-2" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0 px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-0 fw-bold px-5" id="btn-save-user">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ===================== MODAL CONFIRMAR ELIMINAR USUARIO ===================== -->
<div class="modal fade" id="modal-delete-user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-1">¿Estás seguro de que quieres eliminar a:</p>
                <p class="fw-bold text-danger mb-0" id="delete-user-name">—</p>
                <p class="text-muted small mt-2 mb-0">Esta acción no se puede deshacer y eliminará todos sus datos.</p>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-user" data-id="">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar usuario
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= Router::asset('web/js/admin/admin.js') ?>?v=<?= time() ?>"></script>
<script src="<?= Router::asset('web/js/admin/admin_users.js') ?>?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
