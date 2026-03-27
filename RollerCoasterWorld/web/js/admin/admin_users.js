/* Admin Users Management JS */
$(document).ready(function () {
  let currentPage = 1;
  let searchTerm = "";

  // Initial load
  loadUsers();

  // Search logic (debounced)
  let searchTimer;
  $("#user-search").on("input", function () {
    clearTimeout(searchTimer);
    searchTerm = $(this).val();
    searchTimer = setTimeout(() => {
      currentPage = 1;
      loadUsers();
    }, 500);
  });

  // Load Users from API
  function loadUsers() {
    const tableBody = $("#users-table-body");
    tableBody.html('<tr><td colspan="6" class="text-center py-5"><i class="fa-solid fa-spinner fa-spin me-2"></i>Cargando usuarios...</td></tr>');

    $.ajax({
      url: `${window.BASE_URL}/api/php/admin/gestion_users.php?action=list&page=${currentPage}&search=${encodeURIComponent(searchTerm)}`,
      method: "GET",
      success: function (response) {
        if (response.success) {
          renderUsers(response.users);
          renderPagination(response.total, response.limit);
        } else {
          showAlert("Error al cargar usuarios: " + response.error);
        }
      },
      error: function () {
        showAlert("Error de conexión con la API.");
      }
    });
  }

  function renderUsers(users) {
    const tableBody = $("#users-table-body");
    tableBody.empty();

    if (users.length === 0) {
      tableBody.html('<tr><td colspan="6" class="text-center py-5 text-muted">No se encontraron usuarios.</td></tr>');
      return;
    }

    users.forEach(user => {
      const initials = getInitials(user.username);
      const avatarHtml = user.profile_image 
        ? `<img src="${user.profile_image}" class="user-avatar-small" alt="Avatar">`
        : `<div class="user-avatar-small">${initials}</div>`;
      
      const roleBadge = user.rol === 'admin' 
        ? '<span class="badge-role badge-role-admin">Admin</span>'
        : '<span class="badge-role badge-role-user">User</span>';

      const dateStr = new Date(user.created_at).toLocaleDateString();

      const row = `
        <tr class="user-row">
          <td>
            <div class="user-info-cell">
              ${avatarHtml}
              <div class="user-meta">
                <span class="user-username">${user.username}</span>
                <span class="user-email">${user.email}</span>
              </div>
            </div>
          </td>
          <td>${user.full_name || '—'}</td>
          <td>${user.country || '—'}</td>
          <td>${roleBadge}</td>
          <td class="text-muted small">${dateStr}</td>
          <td class="text-end">
            <button class="btn-action btn-edit me-1" onclick="openEditModal(${JSON.stringify(user).replace(/"/g, '&quot;')})" title="Editar">
              <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button class="btn-action btn-delete" onclick="deleteUser(${user.id})" title="Eliminar">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
      tableBody.append(row);
    });
  }

  function renderPagination(total, limit) {
    const totalPages = Math.ceil(total / limit);
    const pagination = $("#admin-pagination-list");
    pagination.empty();

    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const activeClass = i === currentPage ? 'active' : '';
        pagination.append(`
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }

    pagination.find(".page-link").on("click", function(e) {
        e.preventDefault();
        currentPage = parseInt($(this).data("page"));
        loadUsers();
    });
  }

  // Global functions for actions
  window.openEditModal = function(user) {
    $("#edit-user-id").val(user.id);
    $("#edit-username").val(user.username);
    $("#edit-email").val(user.email);
    $("#edit-fullname").val(user.full_name || '');
    $("#edit-birthdate").val(user.birthdate || '');
    $("#edit-gender").val(user.gender || 'Otro');
    $("#edit-city").val(user.city || '');
    $("#edit-country").val(user.country || '');
    $("#edit-rol").val(user.rol);

    new bootstrap.Modal($("#editUserModal")).show();
  };

  $("#btn-save-user").on("click", function() {
    const userData = {
      id: $("#edit-user-id").val(),
      username: $("#edit-username").val(),
      email: $("#edit-email").val(),
      full_name: $("#edit-fullname").val(),
      birthdate: $("#edit-birthdate").val(),
      gender: $("#edit-gender").val(),
      city: $("#edit-city").val(),
      country: $("#edit-country").val(),
      rol: $("#edit-rol").val()
    };

    $.ajax({
      url: `${window.BASE_URL}/api/php/admin/gestion_users.php?action=update`,
      method: "POST",
      data: JSON.stringify(userData),
      contentType: "application/json",
      success: function(response) {
        if (response.success) {
          bootstrap.Modal.getInstance($("#editUserModal")).hide();
          loadUsers();
          showAlert("Usuario actualizado correctamente.");
        } else {
          showAlert(response.error);
        }
      }
    });
  });

  window.deleteUser = function(id) {
    showConfirm("¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.", function() {
      $.ajax({
        url: `${window.BASE_URL}/api/php/admin/gestion_users.php?action=delete`,
        method: "POST",
        data: JSON.stringify({ id: id }),
        contentType: "application/json",
        success: function(response) {
          if (response.success) {
            loadUsers();
            showAlert("Usuario eliminado correctamente.");
          } else {
            showAlert(response.error);
          }
        }
      });
    });
  };

  // Helper: Initials
  function getInitials(name) {
    return name.substring(0, 2).toUpperCase();
  }
});
