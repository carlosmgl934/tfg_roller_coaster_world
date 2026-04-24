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
            resultsContainer.html('<div class="text-center text-muted py-5"><i class="fa-solid fa-magnifying-glass mb-3 d-block fa-3x opacity-25"></i><h5>Empieza a escribir para buscar...</h5><p class="small">Escribe al menos 2 letras</p></div>');
        } else {
            resultsContainer.html('<div class="text-center text-muted py-5"><i class="fa-solid fa-keyboard mb-3 d-block fa-3x opacity-25"></i><h5>Escribe al menos 2 caracteres...</h5></div>');
        }
        return;
      }

      resultsContainer.html('<div class="text-center text-success py-5"><div class="spinner-border mb-3" role="status"></div><h5>Buscando en la comunidad...</h5></div>');

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
          <div class="text-center text-muted py-5">
            <i class="fa-solid fa-ghost mb-3 d-block fa-3x opacity-25"></i>
            <h5>No se ha encontrado a nadie</h5>
            <p class="small">Prueba con otro nombre o asegúrate de haberlo escrito bien.</p>
          </div>
        `);
      }
    } catch (e) {
      console.error(e);
      resultsContainer.html('<div class="text-center text-danger py-5"><i class="fa-solid fa-triangle-exclamation mb-3 d-block fa-3x"></i><h5>Error al buscar</h5></div>');
    }
  }

  function renderNavSearchResults(users) {
    resultsContainer.empty();
    let html = '';

    users.forEach(user => {
      let actionHtml = '';
      let statusClass = '';
      let avatarBorderColor = 'var(--bs-success)';

      if (user.friendship_status === 'none') {
        actionHtml = `<button class="btn btn-success rcw-add-friend-btn fw-bold px-3 py-2 shadow-sm" 
                        data-id="${user.id}" title="Añadir a mis amigos"
                        style="border-radius: 20px; font-size: 0.85rem; white-space:nowrap;">
                        <i class="fa-solid fa-user-plus me-1"></i> Añadir
                      </button>`;
      } else if (user.friendship_status === 'pending_sent') {
        avatarBorderColor = '#ffc107';
        actionHtml = `<span class="badge fw-semibold px-3 py-2" 
                        style="background: rgba(255,193,7,0.15); border: 1px solid rgba(255,193,7,0.4); color: #ffc107; border-radius: 20px; font-size: 0.8rem; white-space:nowrap;">
                        <i class="fa-solid fa-clock me-1"></i> Pendiente
                      </span>`;
      } else if (user.friendship_status === 'pending_received') {
        avatarBorderColor = '#0dcaf0';
        actionHtml = `<button class="btn rcw-accept-friend-btn fw-bold px-3 py-2 shadow-sm" 
                        data-id="${user.id}" title="Aceptar"
                        style="background: rgba(13,202,240,0.15); border: 1px solid rgba(13,202,240,0.4); color: #0dcaf0; border-radius: 20px; font-size: 0.85rem; white-space:nowrap;">
                        <i class="fa-solid fa-check me-1"></i> Aceptar
                      </button>`;
      } else if (user.friendship_status === 'accepted') {
        avatarBorderColor = '#198754';
        actionHtml = `<span class="badge fw-semibold px-3 py-2" 
                        style="background: rgba(25,135,84,0.2); border: 1px solid rgba(25,135,84,0.5); color: #2bde8e; border-radius: 20px; font-size: 0.8rem; white-space:nowrap;">
                        <i class="fa-solid fa-user-check me-1"></i> Amigos
                      </span>`;
      }
      
      let avatarHtml = `<div class="d-flex align-items-center justify-content-center text-secondary bg-dark rounded-circle flex-shrink-0" style="width: 52px; height: 52px; border: 2px solid ${avatarBorderColor};"><i class="fa-solid fa-user fs-4"></i></div>`;
      if (user.profile_image) {
        let avatarSrc = user.profile_image;
        if (avatarSrc.startsWith('http://') || avatarSrc.startsWith('https://')) {
          // keep as is
        } else if (avatarSrc.startsWith('/')) {
          if (!avatarSrc.includes('/web/img/uploads/')) {
            avatarSrc = window.BASE_URL + avatarSrc;
          }
        } else {
          avatarSrc = 'https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/' + avatarSrc;
        }

        if (avatarSrc && avatarSrc !== user.profile_image) {
            // Reassign properly
        }
        
        // Ensure image fallback
        avatarHtml = `<img src="${avatarSrc}" alt="${user.username}" class="rounded-circle object-fit-cover flex-shrink-0" style="width: 52px; height: 52px; border: 2px solid ${avatarBorderColor};" onerror="this.outerHTML='<div class=\\'d-flex align-items-center justify-content-center text-secondary bg-dark rounded-circle flex-shrink-0\\' style=\\'width: 52px; height: 52px; border: 2px solid ${avatarBorderColor};\\'><i class=\\'fa-solid fa-user fs-4\\'></i></div>'">`;
      }

      let displayName = user.full_name ? user.full_name : user.username;
      let usernameHtml = user.full_name ? `<span class="fw-normal ms-1" style="color:#6c8a7a; font-size: 0.85rem;">@${user.username}</span>` : '';
      
      let locationText = 'Coaster Enthusiast';
      let locationIconClass = 'fa-star';
      let locationColor = '#2bde8e';
      if (user.city && user.country) {
          locationText = `${user.city}, ${user.country}`;
          locationIconClass = 'fa-location-dot';
          locationColor = 'var(--bs-success)';
      } else if (user.country) {
          locationText = user.country;
          locationIconClass = 'fa-location-dot';
          locationColor = 'var(--bs-success)';
      } else if (user.city) {
          locationText = user.city;
          locationIconClass = 'fa-location-dot';
          locationColor = 'var(--bs-success)';
      }

      html += `
        <div class="rcw-user-card position-relative w-100" 
             style="border-bottom: 1px solid #30363d; transition: background 0.2s;"
             onmouseover="this.style.background='#1c2330'"
             onmouseout="this.style.background=''">
          
          <div class="d-flex align-items-center gap-3 px-4 py-3">
            
            <!-- Avatar -->
            ${avatarHtml}

            <!-- Info -->
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex align-items-baseline gap-1 mb-1 flex-wrap">
                <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${user.id}" 
                   class="text-white fw-bold text-decoration-none stretched-link" 
                   style="font-size: 1rem;">
                  ${displayName}
                </a>
                ${usernameHtml}
              </div>
              <small style="color: #6c8a7a; font-size: 0.78rem;">
                <i class="fa-solid ${locationIconClass} me-1" style="color: ${locationColor};"></i>${locationText}
              </small>
            </div>

            <!-- Action -->
            <div class="flex-shrink-0 position-relative" style="z-index: 2;">
              ${actionHtml}
            </div>

          </div>
        </div>
      `;
    });

    resultsContainer.html(html);
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
