/* Admin Users Management JS — todo en scope global para evitar problemas de scope */

// ─── Estado ────────────────────────────────────────────────────────────────────
let _usersPage  = 1;
let _userSearch = '';
let _userRol    = '';
let _userCnt    = '';

// ─── Helpers ──────────────────────────────────────────────────────────────────
function _escUser(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function _initialsDiv(initials) {
    return `<div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0"
                style="width:42px;height:42px;background:#198754;font-size:.85rem;letter-spacing:.5px;">
                ${initials}
            </div>`;
}

// Llamada desde onerror de <img>
window.buildInitialsAvatar = function (initials) {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0';
    div.style.cssText = 'width:42px;height:42px;background:#198754;font-size:.85rem;letter-spacing:.5px;';
    div.textContent = initials;
    return div;
};

// ─── Render list-group ────────────────────────────────────────────────────────
function _renderUsersTable(users) {
    const $list = $('#admin-users-list');
    $list.empty();

    if (!users || users.length === 0) {
        $list.html(`
            <div class="list-group-item text-center py-5 text-muted" style="background:#161b22;">
                <i class="fa-solid fa-users-slash fa-2x d-block mb-3" style="opacity:.3;"></i>
                No se encontraron usuarios con ese criterio.
            </div>
        `);
        return;
    }

    users.forEach(user => {
        const initials  = (user.username || '?').substring(0, 2).toUpperCase();
        const safeInits = _escUser(initials);

        const avatarHtml = user.profile_image
            ? `<img src="${_escUser(user.profile_image)}"
                    alt="${safeInits}"
                    class="rounded-circle flex-shrink-0"
                    style="width:42px;height:42px;object-fit:cover;border:2px solid #198754;"
                    onerror="this.replaceWith(buildInitialsAvatar('${safeInits}'))">`
            : _initialsDiv(initials);

        const roleBadge = user.rol === 'admin'
            ? `<span class="badge text-uppercase fw-semibold" style="background:rgba(239,68,68,.18);color:#ef4444;border:1px solid rgba(239,68,68,.35);letter-spacing:.5px;">Admin</span>`
            : `<span class="badge text-uppercase fw-semibold" style="background:rgba(59,130,246,.18);color:#60a5fa;border:1px solid rgba(59,130,246,.35);letter-spacing:.5px;">User</span>`;

        const loc = [user.city, user.country].filter(Boolean).join(', ') || null;
        const dateStr = user.created_at
            ? new Date(user.created_at).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' })
            : null;

        const meta = [
            loc     ? `<i class="fa-solid fa-location-dot me-1 text-success opacity-75"></i>${_escUser(loc)}` : null,
            dateStr ? `<i class="fa-regular fa-calendar me-1 opacity-50"></i>${dateStr}` : null,
            user.email ? `<i class="fa-solid fa-envelope me-1 opacity-50"></i>${_escUser(user.email)}` : null,
        ].filter(Boolean).join('<span class="mx-2 opacity-25">&bull;</span>');

        const userJson = JSON.stringify(user).replace(/"/g, '&quot;');

        $list.append(`
            <div class="list-group-item list-group-item-action border-0 border-bottom px-4 py-3"
                 style="background:#161b22; border-color:#30363d !important; cursor:default;"
                 onmouseover="this.style.background='#1c2330'" onmouseout="this.style.background='#161b22'">
                <div class="d-flex align-items-center gap-3">
                    ${avatarHtml}
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-semibold text-white" style="font-size:.95rem;">${_escUser(user.username)}</span>
                            ${roleBadge}
                            <span class="text-muted font-monospace" style="font-size:.7rem;">Nº ${String(user.id).padStart(6,'0')}</span>
                        </div>
                        <div class="text-muted text-truncate mt-1" style="font-size:.78rem;">${meta || '—'}</div>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button class="btn btn-sm rounded-0 admin-action-btn" title="Editar"
                            onclick='openEditModal(${userJson})'>
                            <i class="fa-solid fa-pen-to-square"></i> Editar
                        </button>
                        <button class="btn btn-sm rounded-0 admin-action-btn admin-action-del" title="Eliminar"
                            onclick="openDeleteModal(${user.id}, '${_escUser(user.username)}')">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        `);
    });
}

// ─── Render paginación ────────────────────────────────────────────────────────
function _renderUsersPagination(total, limit) {
    const totalPages = Math.ceil(total / limit);
    const $nav = $('#admin-users-pagination');
    $nav.empty();
    if (totalPages <= 1) return;

    let html = '<ul class="pagination pagination-sm mb-0">';
    for (let i = 1; i <= totalPages; i++) {
        const active = i === _usersPage;
        html += `<li class="page-item ${active ? 'active' : ''}">
            <a class="page-link rounded-0" href="#" data-page="${i}"
               style="${active ? 'background:#198754;border-color:#198754;' : 'background:#161b22;border-color:#30363d;color:#94a3b8;'}">${i}</a>
        </li>`;
    }
    html += '</ul>';
    $nav.html(html);

    $nav.find('.page-link').on('click', function (e) {
        e.preventDefault();
        _usersPage = parseInt($(this).data('page'));
        loadUsers();
    });
}

// ─── Función de carga principal (global) ──────────────────────────────────────
window.loadUsers = function () {
    const $list = $('#admin-users-list');
    $list.html(`
        <div class="list-group-item text-center py-5 text-muted" style="background:#161b22;">
            <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando usuarios...
        </div>
    `);

    $.ajax({
        url: `${window.BASE_URL}/api/php/admin/gestion_users.php?action=list` +
             `&page=${_usersPage}` +
             `&search=${encodeURIComponent(_userSearch)}` +
             `&rol=${encodeURIComponent(_userRol)}` +
             `&country=${encodeURIComponent(_userCnt)}`,
        method: 'GET',
        success(res) {
            if (res.success) {
                _renderUsersTable(res.users);
                _renderUsersPagination(res.total, res.limit);
                const n = res.total;
                $('#users-count').text(n === 1 ? '1 usuario encontrado' : `${n.toLocaleString()} usuarios encontrados`);
            } else {
                $list.html(`<div class="list-group-item text-center py-5 text-danger" style="background:#161b22;">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>${res.error || 'Error al cargar usuarios'}
                </div>`);
            }
        },
        error() {
            $list.html(`<div class="list-group-item text-center py-5 text-danger" style="background:#161b22;">
                <i class="fa-solid fa-wifi me-2"></i>Error de conexión con la API
            </div>`);
        }
    });
};

// ─── Modal editar ─────────────────────────────────────────────────────────────
window.openEditModal = function (user) {
    $('#edit-user-id').val(user.id);
    $('#edit-username').val(user.username || '');
    $('#edit-email').val(user.email    || '');
    $('#edit-rol').val(user.rol        || 'user');

    // ── Franja de info de solo lectura ──
    const initials = (user.username || '?').substring(0, 2).toUpperCase();
    const avatarWrap = document.getElementById('edit-user-avatar-wrap');
    if (user.profile_image) {
        avatarWrap.innerHTML = `<img src="${_escUser(user.profile_image)}"
            class="rounded-circle" style="width:48px;height:48px;object-fit:cover;border:2px solid #198754;"
            onerror="this.replaceWith(buildInitialsAvatar('${initials}'))">` ;
    } else {
        avatarWrap.innerHTML = '';
        avatarWrap.appendChild(buildInitialsAvatar(initials));
    }

    // Nombre y métadatos
    document.getElementById('edit-user-display-name').textContent =
        user.full_name ? `${user.full_name} (@${user.username})` : `@${user.username}`;
    const loc = [user.city, user.country].filter(Boolean).join(', ');
    const joined = user.created_at
        ? 'Miembro desde ' + new Date(user.created_at).toLocaleDateString('es-ES', { year:'numeric', month:'short' })
        : '';
    document.getElementById('edit-user-meta').textContent =
        [loc, joined].filter(Boolean).join(' · ');

    $('#edit-user-messages').addClass('d-none');
    $('#edit-user-error, #edit-user-success').addClass('d-none');
    new bootstrap.Modal($('#editUserModal')[0]).show();
};

// ─── Modal eliminar ───────────────────────────────────────────────────────────
window.openDeleteModal = function (id, username) {
    $('#delete-user-name').text(username);
    $('#confirm-delete-user').data('id', id);
    new bootstrap.Modal($('#modal-delete-user')[0]).show();
};

// ─── Document ready: event listeners ─────────────────────────────────────────
$(document).ready(function () {

    // Carga inicial
    loadUsers();

    // Búsqueda debounced
    let searchTimer;
    $('#user-search').on('input', function () {
        clearTimeout(searchTimer);
        _userSearch = $(this).val().trim();
        searchTimer = setTimeout(() => { _usersPage = 1; loadUsers(); }, 450);
    });

    // Filtrar
    $('#btn-users-filtrar').on('click', function () {
        _userRol   = $('#filter-rol').val();
        _userCnt   = $('#filter-country').val().trim();
        _usersPage = 1;
        loadUsers();
    });

    // Enter en país aplica filtro
    $('#filter-country').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btn-users-filtrar').trigger('click');
    });

    // Limpiar filtros
    $('#btn-users-borrar').on('click', function () {
        $('#filter-rol').val('');
        $('#filter-country').val('');
        $('#user-search').val('');
        _userSearch = ''; _userRol = ''; _userCnt = '';
        _usersPage  = 1;
        loadUsers();
    });

    // Guardar cambios usuario
    $('#btn-save-user').on('click', function () {
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...');

        $.ajax({
            url:         `${window.BASE_URL}/api/php/admin/gestion_users.php?action=update`,
            method:      'POST',
            data:        JSON.stringify({
                id:       $('#edit-user-id').val(),
                username: $('#edit-username').val().trim(),
                rol:      $('#edit-rol').val()
            }),
            contentType: 'application/json',
            success(res) {
                if (res.success) {
                    bootstrap.Modal.getInstance($('#editUserModal')[0]).hide();
                    loadUsers();
                    _showUserAlert('Usuario actualizado correctamente.');
                } else {
                    _showUserInlineError('#edit-user-error', res.error || 'Error desconocido');
                    $('#edit-user-messages').removeClass('d-none');
                }
            },
            error() {
                _showUserInlineError('#edit-user-error', 'Error de conexión.');
                $('#edit-user-messages').removeClass('d-none');
            },
            complete() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
            }
        });
    });

    // Confirmar eliminación
    $('#confirm-delete-user').on('click', function () {
        const id   = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Eliminando...');

        $.ajax({
            url:         `${window.BASE_URL}/api/php/admin/gestion_users.php?action=delete`,
            method:      'POST',
            data:        JSON.stringify({ id }),
            contentType: 'application/json',
            success(res) {
                bootstrap.Modal.getInstance($('#modal-delete-user')[0]).hide();
                if (res.success) {
                    loadUsers();

                    let msg = 'Usuario eliminado correctamente.';
                    if (res.supabase_warn) msg += `\n⚠️ ${res.supabase_warn}`;
                    _showUserAlert(msg);
                } else {
                    _showUserAlert(res.error || 'Error al eliminar.');
                }
            },
            error() { _showUserAlert('Error de conexión.'); },
            complete() {
                $btn.prop('disabled', false).html('<i class="fa-solid fa-trash me-1"></i>Eliminar usuario');
            }
        });
    });

    function _showUserInlineError(selector, msg) {
        $(selector).find('span').text(msg);
        $(selector).removeClass('d-none');
    }

    function _showUserAlert(msg) {
        if (typeof window.showModalNotification === 'function') {
            window.showModalNotification(msg);
        } else {
            const modalId = 'alertModal-' + Date.now();
            const isError = msg.toLowerCase().includes('error') || msg.toLowerCase().includes('ya está en uso');
            const icon = isError ? '<i class="fa-solid fa-circle-exclamation text-danger fs-3"></i>' : '<i class="fa-solid fa-circle-check text-success fs-3"></i>';
            const title = isError ? 'Error' : 'Notificación';
            
            const html = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content" style="background:#161b22; border:1px solid #30363d;">
                            <div class="modal-header border-0 pb-0 justify-content-center mt-3">
                                ${icon}
                            </div>
                            <div class="modal-body text-center text-white pb-4 pt-3">
                                <h6 class="mb-2">${title}</h6>
                                <p class="text-muted small mb-0" style="white-space: pre-wrap;">${msg}</p>
                            </div>
                            <div class="modal-footer border-0 p-0 justify-content-center pb-3">
                                <button type="button" class="btn btn-sm btn-secondary w-75" data-bs-dismiss="modal" style="background:#21262d; border:1px solid #30363d; color:#c9d1d9;">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(html);
            const $modal = $('#' + modalId);
            const modalInstance = new bootstrap.Modal($modal[0]);
            $modal.on('hidden.bs.modal', function() {
                $modal.remove();
            });
            modalInstance.show();
        }
    }
});
