<?php
$page_css = ['web/css/coasters.css', 'web/css/admin.css'];
require_once __DIR__ . '/../partials/header.php';

if (!$is_logged || !$is_admin) {
    Router::redirect('login');
    exit;
}
?>

<main class="container-fluid px-lg-5 pt-0 pb-5 mb-5">

    <!-- ══ CABECERA ════════════════════════════════════════════════════ -->
    <div class="row pt-4 mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center border-bottom pb-3 flex-wrap gap-2">
            <h1 class="display-6 fw-bold text-success mb-0">
                <i class="fa-solid fa-comments me-3"></i>Gestión de Foros
            </h1>
            <span class="text-muted small" id="forums-count-label"></span>
        </div>
    </div>

    <div class="row g-4">

        <!-- ══ SIDEBAR FILTROS ══════════════════════════════════════════ -->
        <aside class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top rounded-0" style="top: 90px; z-index: 1;">
                <div class="card-header bg-success text-white rounded-0">
                    <h5 class="mb-0"><i class="fa-solid fa-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">

                    <!-- Privacidad -->
                    <div class="mb-3">
                        <select class="form-select shadow-sm rounded-0" id="filter-privacy">
                            <option value="">Todos (público + privado)</option>
                            <option value="public">Solo públicos</option>
                            <option value="private">Solo privados</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold shadow-sm rounded-0" id="btn-forums-filtrar">
                            <i class="fa-solid fa-filter me-2"></i>Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary shadow-sm rounded-0" id="btn-forums-borrar">
                            <i class="fa-solid fa-eraser me-2"></i>Limpiar filtros
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── Stats rápidas ─────────────────────────────────────── -->
            <div class="card rounded-0 border-0 shadow-sm mt-3">
                <div class="card-header bg-success text-white rounded-0">
                    <h6 class="mb-0"><i class="fa-solid fa-chart-bar me-2"></i>Resumen</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush rounded-0" id="forums-stats-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center rounded-0">
                            <span class="small text-muted">Total foros</span>
                            <span class="badge bg-success rounded-pill" id="stat-total">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center rounded-0">
                            <span class="small text-muted">Públicos</span>
                            <span class="badge bg-primary rounded-pill" id="stat-public">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center rounded-0">
                            <span class="small text-muted">Privados</span>
                            <span class="badge bg-secondary rounded-pill" id="stat-private">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center rounded-0">
                            <span class="small text-muted">Total mensajes</span>
                            <span class="badge bg-info rounded-pill" id="stat-msgs">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center rounded-0">
                            <span class="small text-muted">Mensajes ocultos</span>
                            <span class="badge bg-warning text-dark rounded-pill" id="stat-hidden">—</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center rounded-0">
                            <span class="small text-muted">Usuarios baneados</span>
                            <span class="badge bg-danger rounded-pill" id="stat-bans">—</span>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- ══ COLUMNA PRINCIPAL ═════════════════════════════════════════ -->
        <div class="col-12 col-lg-9">

            <!-- Barra de búsqueda -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <div class="flex-grow-1 position-relative">
                    <input type="text" id="forums-search"
                           class="form-control shadow-sm pe-5 border-success rounded-0"
                           placeholder="Buscar foro por título o asunto..."
                           style="border-width: 2px;">
                    <i class="fa-solid fa-magnifying-glass text-muted position-absolute"
                       style="right: 14px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                <button class="btn btn-outline-secondary rounded-0" onclick="loadForums()" title="Recargar">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </div>

            <!-- Contador -->
            <p class="text-muted fw-semibold mb-2 small" id="forums-count"></p>

            <!-- Lista de foros -->
            <div class="list-group shadow-sm rounded-0" id="admin-forums-list">
                <div class="list-group-item text-center text-muted py-5" id="admin-forums-loading">
                    <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando foros...
                </div>
            </div>

        </div><!-- /col principal -->

    </div><!-- /row -->

</main>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL: DETALLE DE FORO (mensajes + colaboradores + baneados)
══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modal-forum-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-0 border-0 shadow-lg" style="background:#161b22;">

            <div class="modal-header bg-success text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-comments fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0" id="forum-detail-title">Foro</h5>
                    <span class="badge ms-2" id="forum-detail-privacy-badge"></span>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0" style="background:#161b22;">

                <!-- Nav tabs -->
                <ul class="nav nav-tabs border-bottom px-3 pt-2" id="forum-detail-tabs"
                    style="border-color:#30363d; background:#0d1117;">
                    <li class="nav-item">
                        <button class="nav-link active fw-semibold" data-tab="messages"
                            style="color:#e6edf3; background:#1c2128; border-color:#30363d #30363d #1c2128; border-radius:0;">
                            <i class="fa-solid fa-message me-1"></i>
                            Mensajes <span class="badge bg-success ms-1" id="tab-badge-msgs">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" data-tab="collaborators"
                            style="color:#94a3b8; border-radius:0;">
                            <i class="fa-solid fa-user-check me-1"></i>
                            Colaboradores <span class="badge bg-primary ms-1" id="tab-badge-collabs">0</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold" data-tab="banned"
                            style="color:#94a3b8; border-radius:0;">
                            <i class="fa-solid fa-ban me-1"></i>
                            Baneados <span class="badge bg-danger ms-1" id="tab-badge-bans">0</span>
                        </button>
                    </li>
                </ul>

                <div class="p-3" style="max-height: 55vh; overflow-y: auto;">

                    <!-- TAB: MENSAJES -->
                    <div id="tab-messages">
                        <!-- Filtro rápido -->
                        <div class="d-flex gap-2 mb-3">
                            <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                                <input class="form-check-input admin-toggle" type="checkbox" role="switch" id="filter-show-hidden">
                                <label class="form-check-label small text-muted" for="filter-show-hidden">Mostrar solo ocultos</label>
                            </div>
                        </div>
                        <div id="messages-list">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando mensajes...
                            </div>
                        </div>
                    </div>

                    <!-- TAB: COLABORADORES -->
                    <div id="tab-collaborators" class="d-none">
                        <div id="collaborators-list">
                            <div class="text-center text-muted py-4">
                                <i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando colaboradores...
                            </div>
                        </div>
                    </div>

                    <!-- TAB: BANEADOS -->
                    <div id="tab-banned" class="d-none">

                        <!-- Participantes del foro → banear -->
                        <div class="mb-3">
                            <p class="small fw-bold text-muted text-uppercase mb-2" style="letter-spacing:.05em;">
                                <i class="fa-solid fa-users me-1"></i>Participantes activos
                            </p>
                            <div id="participants-list">
                                <div class="text-center text-muted py-3 small">
                                    <i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando...
                                </div>
                            </div>
                        </div>

                        <!-- Ya baneados -->
                        <div>
                            <p class="small fw-bold text-muted text-uppercase mb-2" style="letter-spacing:.05em;">
                                <i class="fa-solid fa-ban me-1 text-danger"></i>Usuarios baneados
                            </p>
                            <div id="banned-list">
                                <div class="text-center text-muted py-3 small">
                                    <i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando...
                                </div>
                            </div>
                        </div>

                    </div>

                </div><!-- /scroll area -->

            </div><!-- /modal-body -->

            <div class="modal-footer border-0 px-4 pb-4 pt-2 d-flex justify-content-between" style="background:#0d1117;">
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="btn-delete-forum-from-detail">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar foro completo
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cerrar</button>
            </div>

        </div>
    </div>
</div>

<!-- ══ MODAL: CONFIRMAR ELIMINAR FORO ════════════════════════════════ -->
<div class="modal fade" id="modal-delete-forum" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar foro
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-1">¿Estás seguro de que quieres eliminar el foro:</p>
                <p class="fw-bold text-danger mb-0" id="delete-forum-title">—</p>
                <p class="text-muted small mt-2 mb-0">Esta acción eliminará también <strong>todos los mensajes, colaboradores y baneados</strong> de este foro. No se puede deshacer.</p>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-forum">
                    <i class="fa-solid fa-trash me-1"></i>Sí, eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: BANEAR AL CREADOR (elimina el foro) ════════════════ -->
<div class="modal fade" id="modal-ban-owner" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header border-0 py-3 px-4"
                 style="background: linear-gradient(135deg, #b91c1c, #7f1d1d);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-crown fs-5 text-warning"></i>
                    <h5 class="modal-title fw-bold mb-0 text-white">Banear al creador del foro</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-4" style="background:#161b22; color:#e6edf3;">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <i class="fa-solid fa-triangle-exclamation fa-2x text-warning flex-shrink-0 mt-1"></i>
                    <div>
                        <p class="mb-1 fw-semibold">
                            <span id="ban-owner-username" class="text-warning">—</span>
                            es el <strong>creador</strong> de este foro.
                        </p>
                        <p class="text-muted small mb-0">
                            Banear al creador provocará la <strong class="text-danger">eliminación completa del foro</strong>
                            incluyendo todos sus mensajes, colaboradores y baneados.<br>
                            Esta acción <strong>no se puede deshacer</strong>.
                        </p>
                    </div>
                </div>
                <div class="alert rounded-0 border-0 mb-0 py-2 px-3"
                     style="background:rgba(185,28,28,.15); border-left:3px solid #b91c1c !important;">
                    <p class="small mb-0 text-danger fw-semibold">
                        <i class="fa-solid fa-fire me-1"></i>
                        Foro: <span id="ban-owner-forum-title">—</span>
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold px-4" id="confirm-ban-owner">
                    <i class="fa-solid fa-trash me-1"></i>Sí, eliminar foro
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: CONFIRMAR ELIMINAR MENSAJE ═════════════════════════════ -->
<div class="modal fade" id="modal-delete-message" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar mensaje
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-1">¿Eliminar este mensaje permanentemente?</p>
                <blockquote class="blockquote text-muted small border-start border-danger ps-3 mt-2 mb-0" id="delete-msg-preview">—</blockquote>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger rounded-0 fw-bold" id="confirm-delete-msg">
                    <i class="fa-solid fa-trash me-1"></i>Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: CONFIRMAR DESBANEAR ════════════════════════════════════ -->
<div class="modal fade" id="modal-unban" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-user-check me-2"></i>Desbanear usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-0">¿Desbanear a <strong id="unban-username">—</strong> de este foro?</p>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning rounded-0 fw-bold text-dark" id="confirm-unban">
                    <i class="fa-solid fa-user-check me-1"></i>Desbanear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL: CONFIRMAR QUITAR COLABORADOR ═══════════════════════════ -->
<div class="modal fade" id="modal-remove-collab" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-0 border-0 shadow" style="background:#161b22;">
            <div class="modal-header bg-warning text-dark border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-user-minus me-2"></i>Quitar colaborador
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background:#161b22; color:#e6edf3;">
                <p class="mb-0">¿Quitar a <strong id="remove-collab-username">—</strong> como colaborador de este foro?</p>
            </div>
            <div class="modal-footer border-0" style="background:#161b22;">
                <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning rounded-0 fw-bold text-dark" id="confirm-remove-collab">
                    <i class="fa-solid fa-user-minus me-1"></i>Quitar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= Router::asset('web/js/admin/admin.js') ?>?v=<?= time() ?>"></script>
<script src="<?= Router::asset('web/js/admin/admin_forums.js') ?>?v=<?= time() ?>"></script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
