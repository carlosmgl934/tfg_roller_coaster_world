$(document).ready(function () {
  const container = $("#friends-container");
  const loading = $("#friends-loading");

  const requestsList = $("#requests-list");
  const friendsList = $("#friends-list");
  const requestsCount = $("#requests-count");
  const friendsCount = $("#friends-count");

  const sentContainer = $("#sent-requests-container");
  const sentList = $("#sent-list");
  const sentCount = $("#sent-count");

  const searchInput = $("#search-friends-input");
  const sortSelect = $("#sort-friends-select");
  let allFriends = [];

  // Load friends on init
  fetchFriendsData();

  async function fetchFriendsData() {
    try {
      const res = await fetch(
        `${BASE_URL}/api/php/users.php?action=get_friends_data`,
      );
      const payload = await res.json();

      loading.hide();
      container.show();
      sentContainer.show();

      if (payload.success) {
        allFriends = payload.data.friends || [];
        renderReceived(payload.data.received_requests);
        renderSent(payload.data.sent_requests);
        filterAndRenderFriends();
      } else {
        alert(
          "Error cargando amistades: " + (payload.error || "Error desconocido"),
        );
      }
    } catch (e) {
      console.error(e);
      loading.html(
        '<div class="col-12 text-center py-5 text-danger">Error de conexión con el servidor.</div>',
      );
    }
  }

  // Make available globally so header_friends.js can call it
  window.fetchFriendsData = fetchFriendsData;

  // Helper: resuelve un profile_image problemático (sólo nombre vs relativo vs absoluto Supabase)
  function getAvatarUrl(
    profileImage,
    username,
    fallbackColor = "198754",
    fallbackText = "fff",
  ) {
    const fallbackInitials = `https://ui-avatars.com/api/?name=${encodeURIComponent(username)}&background=${fallbackColor}&color=${fallbackText}`;

    if (!profileImage) return fallbackInitials;

    // URL absoluta de Supabase u otro CDN → usar directamente
    if (
      profileImage.startsWith("http://") ||
      profileImage.startsWith("https://")
    ) {
      return profileImage;
    }

    // Ruta relativa que empieza por "/"
    if (profileImage.startsWith("/")) {
      // Si es una subida local (/web/img/uploads/...) solo existe en la máquina
      // de quien la subió → usar fallback de iniciales para evitar 404
      if (profileImage.includes("/web/img/uploads/")) {
        return fallbackInitials;
      }
      return BASE_URL + profileImage;
    }

    // Caso: guardado solo como nombre de archivo "123456_abcd.webp" → construir URL Supabase
    return (
      "https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/" +
      profileImage
    );
  }

  function filterAndRenderFriends() {
    const query = searchInput.val().toLowerCase().trim();
    const sortVal = sortSelect.val();

    let filtered = allFriends.filter((f) =>
      f.username.toLowerCase().includes(query)
    );

    filtered.sort((a, b) => {
      if (sortVal === "antiguedad_desc") {
        return new Date(b.since || 0) - new Date(a.since || 0);
      } else if (sortVal === "antiguedad_asc") {
        return new Date(a.since || 0) - new Date(b.since || 0);
      } else if (sortVal === "alfabetico_asc") {
        return a.username.localeCompare(b.username);
      } else if (sortVal === "alfabetico_desc") {
        return b.username.localeCompare(a.username);
      } else if (sortVal === "credits_desc") {
        return (b.credits || 0) - (a.credits || 0);
      }
      return 0;
    });

    renderFriends(filtered);
  }

  searchInput.on("input", filterAndRenderFriends);
  sortSelect.on("change", filterAndRenderFriends);

  function renderReceived(requests) {
    requestsList.empty();
    requestsCount.text(requests.length);

    if (requests.length > 0) {
      requestsCount.removeClass('badge-profile-gray').addClass('badge-profile-danger');
    } else {
      requestsCount.removeClass('badge-profile-danger').addClass('badge-profile-gray');
    }

    if (requests.length === 0) {
      requestsList.html(
        '<div class="p-4 text-center text-muted small"><i class="fa-solid fa-box-open d-block fs-3 mb-2 opacity-25"></i>No tienes solicitudes pendientes.</div>',
      );
      return;
    }

    let html = "";
    requests.forEach((req) => {
      const avatarSrc = getAvatarUrl(
        req.profile_image,
        req.username,
        "ffc107",
        "000",
      );

      html += `
        <div class="list-group-item bg-transparent py-3 px-4 border-bottom border-secondary border-opacity-25" style="border-left: 3px solid var(--rcw-green-neon) !important;">
          <div class="d-flex align-items-center">
            <img src="${avatarSrc}" alt="${req.username}" class="rounded-circle object-fit-cover me-4 shadow-sm border border-success border-opacity-50" style="width: 50px; height: 50px;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(req.username)}&background=020617&color=10b981'">
            <div class="flex-grow-1 min-w-0">
               <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${req.id}" class="text-white text-decoration-none fw-bold d-block text-truncate fs-5 mb-1">${req.username}</a>
               <small class="text-muted d-block"><i class="fa-solid fa-user-plus opacity-50 me-1"></i> Quiere ser tu amigo</small>
            </div>
            <div class="d-flex flex-column gap-2 ms-2 position-relative" style="z-index: 2;">
               <button class="btn btn-sm btn-success shadow-sm rcw-action-btn px-3 fw-bold" data-action="accept" data-id="${req.id}" title="Aceptar"><i class="fa-solid fa-check"></i></button>
               <button class="btn btn-sm btn-outline-danger shadow-sm rcw-action-btn px-3 fw-bold" data-action="reject" data-id="${req.id}" title="Rechazar"><i class="fa-solid fa-xmark"></i></button>
            </div>
          </div>
        </div>
      `;
    });
    requestsList.html(html);
  }

  function renderFriends(friends) {
    friendsList.empty();
    friendsCount.text(friends.length);

    if (friends.length === 0) {
      friendsList.html(
        '<div class="col-12 p-5 text-center text-muted"><i class="fa-solid fa-user-group d-block fa-3x mb-3 opacity-25"></i>Todavía no has hecho amigos.<br>¡Busca en la comunidad y conéctate!</div>',
      );
      return;
    }

    let html = "";
    friends.forEach((friend) => {
      const avatarSrc = getAvatarUrl(
        friend.profile_image,
        friend.username,
        "198754",
        "fff",
      );

      let details = [];
      if (friend.city || friend.country) {
          let loc = [friend.city, friend.country].filter(Boolean).join(", ");
          details.push(`<i class="fa-solid fa-location-dot text-success me-1"></i>${loc}`);
      }
      if (friend.joined_at) {
          const date = new Date(friend.joined_at);
          const mes = new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(date);
          const anio = date.getFullYear();
          details.push(`<i class="fa-regular fa-calendar text-info me-1"></i>Miembro desde ${mes} de ${anio}`);
      }
      if (friend.favorite_coaster) {
          details.push(`<i class="fa-solid fa-star text-warning me-1"></i>Top 1: ${friend.favorite_coaster}`);
      }
      let detailsHtml = details.length > 0 ? details.join('<span class="mx-2 opacity-25">&bull;</span>') : '<i class="fa-solid fa-user text-muted me-1"></i>Miembro RCW';

      html += `
        <div class="col-12">
          <div class="rcw-friend-row d-flex align-items-center gap-3 px-4 py-3"
               style="background-color: #1a222e;
                      border-bottom: 1px solid var(--rcw-border);
                      transition: background 0.2s;"
               onmouseover="this.style.background='#222b38'"
               onmouseout="this.style.background='#1a222e'">

            <!-- Avatar Redondo -->
            <div class="flex-shrink-0">
              <img src="${avatarSrc}"
                alt="${friend.username}"
                class="rounded-circle shadow-sm border border-success border-opacity-25 object-fit-cover"
                style="width: 52px; height: 52px;"
                onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(friend.username)}&background=198754&color=fff&size=128'">
            </div>

            <!-- Info del amigo -->
            <div class="flex-grow-1 min-w-0 py-1">
              <div class="d-flex align-items-baseline gap-2 mb-0">
                <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}"
                   class="text-white text-decoration-none fw-bold"
                   style="font-size: 1rem;">
                  ${friend.username}
                </a>
                <small class="text-success fw-bold" style="font-size: 0.65rem;"><i class="fa-solid fa-circle-check me-1"></i>Amigo</small>
                <small class="text-muted font-monospace ms-1" style="font-size: 0.7rem;">Nº ${String(friend.id).padStart(6, '0')}</small>
              </div>
              <div class="text-muted text-truncate mt-1" style="font-size: 0.75rem;">
                ${detailsHtml}
              </div>
            </div>

            <!-- Credits (horizontal: label + valor) -->
            <div class="flex-shrink-0 me-3 d-flex align-items-center gap-2">
              <span class="text-muted" style="font-size:0.65rem; text-transform:uppercase; letter-spacing:0.05em;">Credits</span>
              <span class="text-info fw-bold" style="font-size:1.1rem;">${friend.credits || 0}</span>
            </div>

            <!-- Btn Eliminar -->
            <div class="flex-shrink-0 rcw-trigger-remove"
                 data-id="${friend.id}" data-name="${friend.username}"
                 title="Eliminar amigo"
                 style="cursor:pointer; position:relative; z-index:10;">
              <button class="btn btn-sm btn-outline-danger rounded-circle d-flex align-items-center justify-content-center"
                      style="width:36px; height:36px; border-width: 2px;"
                      tabindex="-1">
                <i class="fa-solid fa-user-xmark" style="pointer-events: none;"></i>
              </button>
            </div>

          </div>
        </div>
      `;
    });
    friendsList.html(html);
  }


  function renderSent(sent) {
    sentList.empty();
    sentCount.text(sent.length);

    if (sent.length === 0) {
      sentList.html(
        '<li class="list-group-item bg-transparent text-muted border-0 small">No tienes invitaciones pendientes de aceptar por otros usuarios.</li>',
      );
      return;
    }

    let html = "";
    sent.forEach((req) => {
      const avatarSrc = getAvatarUrl(
        req.profile_image,
        req.username,
        "6c757d",
        "fff",
      );
      html += `
        <li class="list-group-item bg-transparent border-bottom border-secondary border-opacity-25 py-3">
           <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div class="d-flex align-items-center">
                 <img src="${avatarSrc}" class="rounded-circle me-3 border border-secondary border-opacity-50" style="width: 40px; height: 40px; object-fit: cover;">
                 <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${req.id}" class="small fw-bold text-muted text-decoration-none">Invitación enviada a ${req.username}</a>
              </div>
              <button class="btn btn-sm btn-outline-danger py-1 px-3 rcw-action-btn ms-auto" data-action="cancel" data-id="${req.id}" title="Cancelar envío"><i class="fa-solid fa-xmark me-1"></i> Cancelar</button>
           </div>
        </li>
      `;
    });
    sentList.html(html);
  }

  // Bind Actions (Delegation on document)
  $(document).on("click", ".rcw-action-btn", async function (e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = $(this);
    const action = btn.data("action"); // 'accept', 'reject', 'remove', 'cancel'
    const targetId = btn.data("id");

    const originalHtml = btn.html();
    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    let endpoint = "";
    if (action === "accept") endpoint = "accept_friend";
    else if (action === "reject" || action === "remove" || action === "cancel")
      endpoint = "reject_remove_friend";

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/users.php?action=${endpoint}`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId }),
        },
      );
      const data = await res.json();
      if (data.success) {
        // Recargar datos suavemente
        fetchFriendsData();
      } else {
        alert("Error: " + (data.error || "Acción fallida"));
        btn.prop("disabled", false).html(originalHtml);
      }
    } catch (err) {
      console.error(err);
      btn.prop("disabled", false).html(originalHtml);
    }
  });

  // Modal Remove Friend Logic
  $(document).on("click", ".rcw-trigger-remove", function(e) {
      e.stopPropagation();
      const id = $(this).data("id");
      const name = $(this).data("name");
      $("#removeFriendName").text(name);
      $("#confirmRemoveFriendBtn").data("id", id);
      const modal = new bootstrap.Modal(document.getElementById('removeFriendModal'));
      modal.show();
  });

  $("#confirmRemoveFriendBtn").on("click", async function() {
      const btn = $(this);
      const targetId = btn.data("id");
      const originalHtml = btn.text() || "Eliminando...";

      btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

      try {
        const res = await fetch(`${BASE_URL}/api/php/users.php?action=reject_remove_friend`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId }),
        });
        const data = await res.json();
        if (data.success) {
          bootstrap.Modal.getInstance(document.getElementById('removeFriendModal')).hide();
          fetchFriendsData();
        } else {
          alert("Error: " + (data.error || "Petición fallida"));
        }
      } catch (err) {
        alert("Error de conexión al eliminar amigo.");
      }
      btn.prop("disabled", false).text("Eliminar");
  });
});
