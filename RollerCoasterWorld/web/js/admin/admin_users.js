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
                style="width:38px;height:38px;background:#198754;font-size:.85rem;letter-spacing:.5px;">
                ${initials}
            </div>`;
}

// Llamada desde onerror de <img>
window.buildInitialsAvatar = function (initials) {
    const div = document.createElement('div');
    div.className = 'd-flex align-items-center justify-content-center rounded-circle fw-bold text-white flex-shrink-0';
    div.style.cssText = 'width:38px;height:38px;background:#198754;font-size:.85rem;letter-spacing:.5px;';
    div.textContent = initials;
    return div;
};

// ─── Render tabla ─────────────────────────────────────────────────────────────
function _renderUsersTable(users) {
    const tbody = $('#users-table-body');
    tbody.empty();

    if (!users || users.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-users-slash fa-2x d-block mb-3" style="opacity:.3;"></i>
                    No se encontraron usuarios con ese criterio.
                </td>
            </tr>
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
                    style="width:38px;height:38px;object-fit:cover;border:2px solid #198754;"
                    onerror="this.replaceWith(buildInitialsAvatar('${safeInits}'))">`
            : _initialsDiv(initials);

        const roleBadge = user.rol === 'admin'
            ? `<span class="badge text-uppercase fw-semibold" style="background:rgba(239,68,68,.18);color:#ef4444;border:1px solid rgba(239,68,68,.35);letter-spacing:.5px;">Admin</span>`
            : `<span class="badge text-uppercase fw-semibold" style="background:rgba(59,130,246,.18);color:#60a5fa;border:1px solid rgba(59,130,246,.35);letter-spacing:.5px;">User</span>`;

        const dateStr = user.created_at
            ? new Date(user.created_at).toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' })
            : '—';

        const userJson = JSON.stringify(user).replace(/"/g, '&quot;');

        tbody.append(`
            <tr style="border-color:#30363d;">
                <td class="py-3 px-4" style="border-color:#30363d;">
                    <div class="d-flex align-items-center gap-3">
                        ${avatarHtml}
                        <div>
                            <div class="fw-semibold text-white" style="font-size:.9rem;">${_escUser(user.username)}</div>
                            <div class="text-muted" style="font-size:.78rem;">${_escUser(user.email)}</div>
                        </div>
                    </div>
                </td>
                <td class="py-3 px-4 text-muted" style="border-color:#30363d;font-size:.88rem;">${_escUser(user.full_name || '—')}</td>
                <td class="py-3 px-4 text-muted d-none d-md-table-cell" style="border-color:#30363d;font-size:.88rem;">${_escUser(user.country || '—')}</td>
                <td class="py-3 px-4" style="border-color:#30363d;">${roleBadge}</td>
                <td class="py-3 px-4 text-muted d-none d-lg-table-cell" style="border-color:#30363d;font-size:.82rem;">${dateStr}</td>
                <td class="py-3 px-4 text-end" style="border-color:#30363d;">
                    <button class="btn btn-sm rounded-0 me-1 admin-action-btn" title="Editar"
                        onclick='openEditModal(${userJson})'>
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-sm rounded-0 admin-action-btn admin-action-del" title="Eliminar"
                        onclick="openDeleteModal(${user.id}, '${_escUser(user.username)}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
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
    const tbody = $('#users-table-body');
    tbody.html(`
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando usuarios...
            </td>
        </tr>
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
                tbody.html(`<tr><td colspan="6" class="text-center py-5 text-danger">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>${res.error || 'Error al cargar usuarios'}
                </td></tr>`);
            }
        },
        error() {
            tbody.html(`<tr><td colspan="6" class="text-center py-5 text-danger">
                <i class="fa-solid fa-wifi me-2"></i>Error de conexión con la API
            </td></tr>`);
        }
    });
};

// ─── Modal editar ─────────────────────────────────────────────────────────────
window.openEditModal = function (user) {
    $('#edit-user-id').val(user.id);
    $('#edit-username').val(user.username || '');
    $('#edit-email').val(user.email || '');
    $('#edit-fullname').val(user.full_name || '');
    $('#edit-birthdate').val(user.birthdate || '');
    $('#edit-gender').val(user.gender || 'Otro');
    $('#edit-city').val(user.city || '');
    $('#edit-country').val(user.country || '');
    $('#edit-rol').val(user.rol || 'user');
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
                id:        $('#edit-user-id').val(),
                username:  $('#edit-username').val().trim(),
                email:     $('#edit-email').val().trim(),
                full_name: $('#edit-fullname').val().trim(),
                birthdate: $('#edit-birthdate').val(),
                gender:    $('#edit-gender').val(),
                city:      $('#edit-city').val().trim(),
                country:   $('#edit-country').val().trim(),
                rol:       $('#edit-rol').val()
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
                    _showUserAlert('Usuario eliminado correctamente.');
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
            alert(msg);
        }
    }
});
