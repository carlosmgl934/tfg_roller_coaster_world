$(document).ready(function () {
  const searchInput = $("#nav-user-search");
  const resultsContainer = $("#nav-user-search-results");
  let searchTimeout = null;

  if (searchInput.length) {
    searchInput.on("input", function () {
      const query = $(this).val().trim();
      
      clearTimeout(searchTimeout);

      if (query.length < 2) {
        resultsContainer.html('<div class="text-center text-muted small py-3"><i class="fa-solid fa-magnifying-glass mb-2 d-block fa-2x opacity-25"></i>Escribe al menos 2 letras...</div>');
        return;
      }

      resultsContainer.html('<div class="text-center text-success small py-3"><div class="spinner-border spinner-border-sm mb-2"></div><br>Buscando...</div>');

      searchTimeout = setTimeout(() => {
        fetchUsers(query);
      }, 400); // 400ms debounce
    });
  }

  async function fetchUsers(query) {
    try {
      const res = await fetch(`${BASE_URL}/api/php/users.php?action=search_users&q=${encodeURIComponent(query)}`);
      const payload = await res.json();

      if (payload.success && payload.data.length > 0) {
        renderNavSearchResults(payload.data);
      } else {
        resultsContainer.html('<div class="text-center text-muted small py-3">No se encontraron usuarios.</div>');
      }
    } catch (e) {
      console.error(e);
      resultsContainer.html('<div class="text-center text-danger small py-3">Error al buscar.</div>');
    }
  }

  function renderNavSearchResults(users) {
    resultsContainer.empty();
    let html = '';

    users.forEach(user => {
      let actionHtml = '';
      
      let avatarSrc = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.username)}&background=198754&color=fff`;
      if (user.profile_image) {
        const img = user.profile_image;
        if (img.startsWith('http://') || img.startsWith('https://')) {
          avatarSrc = img; // URL absoluta (Supabase u otro CDN)
        } else if (img.startsWith('/')) {
          // Ruta local de uploads → solo existe en quien la subió → usar iniciales
          if (!img.includes('/web/img/uploads/')) {
            avatarSrc = window.BASE_URL + img;
          }
        } else {
          avatarSrc = 'https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/' + img;
        }
      }

      if (user.friendship_status === 'none') {
        actionHtml = `<button class="btn btn-sm btn-outline-success border-0 px-2 rcw-add-friend-btn" data-id="${user.id}" title="Añadir a mis amigos"><i class="fa-solid fa-user-plus"></i></button>`;
      } else if (user.friendship_status === 'pending_sent') {
        actionHtml = `<span class="badge bg-secondary opacity-75 fw-normal" style="font-size: 0.70rem;">Pendiente</span>`;
      } else if (user.friendship_status === 'pending_received') {
        actionHtml = `<button class="btn btn-sm btn-success px-2 rcw-accept-friend-btn" data-id="${user.id}" title="Aceptar"><i class="fa-solid fa-check"></i></button>`;
      } else if (user.friendship_status === 'accepted') {
        actionHtml = `<span class="badge bg-success opacity-75 fw-normal" style="font-size: 0.70rem;"><i class="fa-solid fa-user-check me-1"></i>Amigos</span>`;
      }

      html += `
        <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${user.id}" class="text-decoration-none rounded px-2 py-2 d-flex align-items-center justify-content-between list-group-item-action" style="transition: background 0.2s;">
          <div class="d-flex align-items-center flex-grow-1 min-w-0" style="pointer-events: none;">
            <img src="${avatarSrc}" alt="${user.username}" class="rounded-circle object-fit-cover me-2 shadow-sm" style="width: 30px; height: 30px;">
            <span class="text-truncate text-white fw-medium lh-1" style="font-size: 0.90rem;">${user.username}</span>
          </div>
          <div class="ms-2 ms-auto" style="pointer-events: auto;">
            ${actionHtml}
          </div>
        </a>
      `;
    });

    resultsContainer.html(html);

    // Bind actions
    bindNavFriendActions();
  }

  // Actualizar el Badge del menú Comunidad
  async function updateCommBadge() {
    const badge = $("#nav-comm-badge");
    if (!badge.length) return;
    
    try {
        const res = await fetch(`${BASE_URL}/api/php/users.php?action=get_friends_data`);
        const data = await res.json();
        if (data.success) {
            const count = (data.data.received_requests || []).length
                        + (data.data.forum_invitations || []).length;
            if (count > 0) {
                badge.text(count).removeClass("d-none");
            } else {
                badge.addClass("d-none");
            }
        }
    } catch (e) { /* ignore silently */ }
  }

  // Cargar contador al inicio
  updateCommBadge();
  // Refrescar cada 60s
  setInterval(updateCommBadge, 60000);

  function bindNavFriendActions() {
    $(".rcw-add-friend-btn").off("click").on("click", async function (e) {
      e.preventDefault();
      e.stopPropagation(); // Avoid triggering the anchor wrapper
      const btn = $(this);
      const targetId = btn.data("id");
      btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');
      
      try {
        const res = await fetch(`${BASE_URL}/api/php/users.php?action=friend_request`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId })
        });
        const data = await res.json();
        if (data.success) {
          btn.replaceWith(`<span class="badge bg-secondary opacity-75 fw-normal" style="font-size: 0.70rem;">Pendiente</span>`);
        } else {
          alert("Error: " + (data.error || "Petición fallida"));
          btn.prop("disabled", false).html('<i class="fa-solid fa-user-plus"></i>');
        }
      } catch (err) {
        btn.prop("disabled", false).html('<i class="fa-solid fa-user-plus"></i>');
      }
    });

    $(".rcw-accept-friend-btn").off("click").on("click", async function (e) {
      e.preventDefault();
      e.stopPropagation();
      const btn = $(this);
      const targetId = btn.data("id");
      btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');
      
      try {
        const res = await fetch(`${BASE_URL}/api/php/users.php?action=accept_friend`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId })
        });
        const data = await res.json();
        if (data.success) {
          btn.replaceWith(`<span class="badge bg-success opacity-75 fw-normal" style="font-size: 0.70rem;"><i class="fa-solid fa-user-check me-1"></i>Amigos</span>`);
          
          // Opcional: recargar vista de page `friends` si estamos en ella
          if (typeof fetchFriendsData === "function") fetchFriendsData();
        } else {
          alert("Error: " + (data.error || "Carga fallida"));
          btn.prop("disabled", false).html('<i class="fa-solid fa-check"></i>');
        }
      } catch (err) {
        btn.prop("disabled", false).html('<i class="fa-solid fa-check"></i>');
      }
    });
  }
});
