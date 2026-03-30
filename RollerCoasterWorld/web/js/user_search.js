$(document).ready(function () {
  const searchInput = $("#page-user-search");
  const resultsContainer = $("#page-user-search-results");
  let searchTimeout = null;

  if (searchInput.length) {
    searchInput.on("input", function () {
      const query = $(this).val().trim();
      
      clearTimeout(searchTimeout);

      if (query.length < 2) {
        if (query.length === 0) {
            resultsContainer.html('<div class="col-12 text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass mb-3 d-block fa-3x opacity-25"></i><h5>Empieza a escribir para buscar...</h5></div>');
        } else {
            resultsContainer.html('<div class="col-12 text-center text-muted py-5"><i class="fa-solid fa-keyboard mb-3 d-block fa-3x opacity-25"></i><h5>Escribe al menos 2 caracteres...</h5></div>');
        }
        return;
      }

      resultsContainer.html('<div class="col-12 text-center text-success py-5"><div class="spinner-border mb-3" role="status"></div><h5>Buscando en la comunidad...</h5></div>');

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
        resultsContainer.html(`
        <div class="text-center text-muted py-5 w-100">
          <i class="fa-solid fa-ghost mb-3 d-block fa-3x opacity-25"></i>
          <h5>No se ha encontrado a nadie</h5>
          <p class="small">Prueba con otro nombre o asegúrate de haberlo escrito bien.</p>
        </div>
      `);
      }
    } catch (e) {
      console.error(e);
      resultsContainer.html('<div class="col-12 text-center text-danger py-5"><i class="fa-solid fa-triangle-exclamation mb-3 d-block fa-3x"></i><h5>Error al buscar</h5></div>');
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
        actionHtml = `<button class="btn btn-outline-success rcw-add-friend-btn fw-bold px-4 rounded-0 shadow-sm" data-id="${user.id}" title="Añadir a mis amigos"><i class="fa-solid fa-user-plus me-1"></i> Añadir</button>`;
      } else if (user.friendship_status === 'pending_sent') {
        actionHtml = `<span class="badge bg-secondary p-2 px-3 fw-bold rounded-0 mx-auto"><i class="fa-solid fa-clock me-1"></i> Pendiente</span>`;
      } else if (user.friendship_status === 'pending_received') {
        actionHtml = `<button class="btn btn-success rcw-accept-friend-btn fw-bold px-3 rounded-0 shadow-sm" data-id="${user.id}" title="Aceptar"><i class="fa-solid fa-check me-1"></i> Aceptar</button>`;
      } else if (user.friendship_status === 'accepted') {
        actionHtml = `<span class="badge bg-success p-2 px-3 fw-bold rounded-0 mx-auto"><i class="fa-solid fa-user-check me-1"></i> Amigos</span>`;
      }

      let displayName = user.full_name ? user.full_name : user.username;
      let usernameHtml = user.full_name ? `<span class="text-muted fw-normal ms-1 fs-6">@${user.username}</span>` : '';
      
      let locationText = 'Coaster Enthusiast';
      if (user.city && user.country) {
          locationText = `${user.city}, ${user.country}`;
      } else if (user.country) {
          locationText = user.country;
      } else if (user.city) {
          locationText = user.city;
      }

      html += `
        <div class="card text-white border-0 border-start border-4 border-success shadow-sm rounded-0 w-100" style="transition: transform 0.2s; background-color: #21262d !important;">
          <div class="card-body d-flex align-items-center p-3">
             <img src="${avatarSrc}" alt="${user.username}" class="rounded-circle object-fit-cover shadow-sm me-4" style="width: 55px; height: 55px; border: 2px solid var(--bs-success);">
             <div class="flex-grow-1 min-w-0">
               <h5 class="fw-bold text-truncate mb-1"><a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${user.id}" class="text-white text-decoration-none stretched-link">${displayName}${usernameHtml}</a></h5>
               <small class="text-muted d-block text-truncate"><i class="fa-solid fa-location-dot text-success opacity-75 me-1"></i> ${locationText}</small>
             </div>
             <div class="ms-3 position-relative z-index-1" style="z-index: 2;">
               ${actionHtml}
             </div>
          </div>
        </div>
      `;
    });

    resultsContainer.html(html);

    // Bind actions
    bindNavFriendActions();
  }

  function bindNavFriendActions() {
    $(".rcw-add-friend-btn").off("click").on("click", async function (e) {
      e.preventDefault();
      e.stopPropagation(); // Avoid triggering the anchor wrapper
      const btn = $(this);
      const targetId = btn.data("id");
      const originalText = btn.html();
      btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');
      
      try {
        const res = await fetch(`${BASE_URL}/api/php/users.php?action=friend_request`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId })
        });
        const data = await res.json();
        if (data.success) {
          btn.replaceWith(`<span class="badge bg-secondary p-2 px-3 fw-bold rounded-0 mx-auto"><i class="fa-solid fa-clock me-1"></i> Pendiente</span>`);
        } else {
          alert("Error: " + (data.error || "Petición fallida"));
          btn.prop("disabled", false).html(originalText);
        }
      } catch (err) {
        btn.prop("disabled", false).html(originalText);
      }
    });

    $(".rcw-accept-friend-btn").off("click").on("click", async function (e) {
      e.preventDefault();
      e.stopPropagation();
      const btn = $(this);
      const targetId = btn.data("id");
      const originalText = btn.html();
      btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');
      
      try {
        const res = await fetch(`${BASE_URL}/api/php/users.php?action=accept_friend`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId })
        });
        const data = await res.json();
        if (data.success) {
          btn.replaceWith(`<span class="badge bg-success p-2 px-3 fw-bold rounded-0 mx-auto"><i class="fa-solid fa-user-check me-1"></i> Amigos</span>`);
        } else {
          alert("Error: " + (data.error || "Carga fallida"));
          btn.prop("disabled", false).html(originalText);
        }
      } catch (err) {
        btn.prop("disabled", false).html(originalText);
      }
    });
  }
});
