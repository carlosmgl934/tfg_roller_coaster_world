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
        const forumInvites = payload.data.forum_invitations || [];
        const tripInvites = payload.data.trip_invitations || [];
        renderReceived(payload.data.received_requests, forumInvites, tripInvites);
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
    const fallbackInitials = BASE_URL + '/web/img/avatars/default_avatar.svg';

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
      f.username.toLowerCase().includes(query),
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

  function renderReceived(requests, forumInvites, tripInvites) {
    requestsList.empty();
    forumInvites = forumInvites || [];
    tripInvites = tripInvites || [];
    const totalCount = requests.length + forumInvites.length + tripInvites.length;
    requestsCount.text(totalCount);

    if (totalCount > 0) {
      requestsCount.removeClass("badge-profile-gray").addClass("badge-profile-danger");
    } else {
      requestsCount.removeClass("badge-profile-danger").addClass("badge-profile-gray");
    }

    if (totalCount === 0) {
      requestsList.html(
        '<div class="p-4 text-center text-muted small"><i class="fa-solid fa-box-open d-block fs-3 mb-2 opacity-25"></i>No tienes solicitudes pendientes.</div>',
      );
      return;
    }

    let html = "";

    // ── Solicitudes de amistad ──────────────────────────────────
    requests.forEach((req) => {
      const avatarSrc = getAvatarUrl(req.profile_image, req.username, "ffc107", "000");
      html += `
        <div class="list-group-item bg-transparent py-3 px-4 border-bottom border-secondary border-opacity-25"
             style="border-left: 3px solid var(--rcw-green-neon) !important;">
          <div class="d-flex align-items-center">
            <img src="${avatarSrc}" alt="${req.username}"
                 class="rounded-circle object-fit-cover me-4 shadow-sm border border-success border-opacity-50"
                 style="width: 50px; height: 50px;"
                 onerror="this.src=window.BASE_URL+'/web/img/avatars/default_avatar.svg'">
            <div class="flex-grow-1 min-w-0">
               <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${req.id}"
                  class="text-white text-decoration-none fw-bold d-block text-truncate fs-5 mb-1">${req.username}</a>
               <small class="text-muted d-block"><i class="fa-solid fa-user-plus opacity-50 me-1"></i> Quiere ser tu amigo</small>
            </div>
            <div class="d-flex flex-column gap-2 ms-2" style="z-index:2;">
               <button class="btn btn-sm btn-success shadow-sm rcw-action-btn px-3 fw-bold"
                       data-action="accept" data-id="${req.id}" title="Aceptar">
                 <i class="fa-solid fa-check"></i>
               </button>
               <button class="btn btn-sm btn-outline-danger shadow-sm rcw-action-btn px-3 fw-bold"
                       data-action="reject" data-id="${req.id}" title="Rechazar">
                 <i class="fa-solid fa-xmark"></i>
               </button>
            </div>
          </div>
        </div>`;
    });

    // ── Invitaciones de colaboración de foro ─────────────────────
    forumInvites.forEach((inv) => {
      const avatarSrc = getAvatarUrl(inv.sender_image, inv.sender_username, "6d28d9", "fff");

      // Compact short title for badge preview (max 40 chars)
      const shortTitle = inv.forum_title && inv.forum_title.length > 40
        ? inv.forum_title.substring(0, 40) + '…'
        : (inv.forum_title || '—');

      // Data JSON encoded for modal
      const dataInv = JSON.stringify({
        invite_id: inv.invite_id,
        sender_username: inv.sender_username,
        forum_title: inv.forum_title,
        forum_description: inv.forum_description || '',
        member_count: inv.member_count || null,
        created_at: inv.created_at || null
      }).replace(/'/g, '&apos;');

      html += `
        <div class="list-group-item bg-transparent py-3 px-4 border-bottom border-secondary border-opacity-25 rcw-forum-invite-info-btn"
             style="border-left: 3px solid #a78bfa !important; cursor:pointer; transition: background 0.2s;"
             onmouseover="this.style.background='rgba(109,40,217,0.07)'"
             onmouseout="this.style.background=''"
             data-inv='${dataInv}'>
          <div class="d-flex align-items-center">
            <img src="${avatarSrc}" alt="${inv.sender_username}"
                 class="rounded-circle object-fit-cover me-4 shadow-sm flex-shrink-0"
                 style="width: 50px; height: 50px; border: 2px solid #a78bfa;"
                 onerror="this.src=window.BASE_URL+'/web/img/avatars/default_avatar.svg'">
            <div class="flex-grow-1 min-w-0">
               <span class="text-white fw-bold d-block text-truncate fs-5 mb-1">${inv.sender_username}</span>
               <small class="d-block mb-1" style="color:#a78bfa;">
                 <i class="fa-solid fa-comments me-1"></i>
                 Te invita a colaborar en
               </small>
               <span class="badge text-white fw-semibold px-2 py-1" style="background:rgba(109,40,217,0.35); border:1px solid #a78bfa; white-space:normal; line-height:1.3; max-width:280px; text-align:left; display:inline-block;">
                 <i class="fa-solid fa-lock me-1" style="font-size:0.7em;"></i>${shortTitle}
               </span>
            </div>
            <div class="d-flex flex-column gap-2 ms-3 flex-shrink-0" style="z-index:2;">
               <button class="btn btn-sm shadow-sm rcw-forum-invite-btn rcw-forum-accept-btn px-3 fw-bold"
                       data-action="accept" data-invite-id="${inv.invite_id}" title="Aceptar">
                 <i class="fa-solid fa-check"></i>
               </button>
               <button class="btn btn-sm btn-outline-secondary shadow-sm rcw-forum-invite-btn rcw-forum-decline-btn px-3 fw-bold"
                       data-action="decline" data-invite-id="${inv.invite_id}" title="Rechazar">
                 <i class="fa-solid fa-xmark"></i>
               </button>
            </div>
          </div>
        </div>`;
    });

    // ── Invitaciones de viajes ──────────────────────────────────
    tripInvites.forEach((inv) => {
      const avatarSrc = getAvatarUrl(inv.inviter_image, inv.inviter_username, "10b981", "fff");
      html += `
        <div class="list-group-item bg-transparent py-3 px-4 border-bottom border-secondary border-opacity-25"
             style="border-left: 3px solid #10b981 !important;">
          <div class="d-flex align-items-center">
            <img src="${avatarSrc}" alt="${inv.inviter_username}"
                 class="rounded-circle object-fit-cover me-4 shadow-sm flex-shrink-0"
                 style="width: 50px; height: 50px; border: 2px solid #10b981;"
                 onerror="this.src=window.BASE_URL+'/web/img/avatars/default_avatar.svg'">
            <div class="flex-grow-1 min-w-0">
               <span class="text-white fw-bold d-block text-truncate fs-5 mb-1">${inv.inviter_username}</span>
               <small class="d-block mb-1 text-success">
                 <i class="fa-solid fa-suitcase-rolling me-1"></i>
                 Te invita al viaje
               </small>
               <span class="badge text-white fw-semibold px-2 py-1" style="background:rgba(16,185,129,0.15); border:1px solid #10b981; white-space:normal; line-height:1.3; max-width:280px; text-align:left; display:inline-block;">
                 "${esc(inv.trip_title)}"
               </span>
            </div>
            <div class="d-flex flex-column gap-2 ms-3 flex-shrink-0" style="z-index:2;">
               <button class="btn btn-sm btn-success shadow-sm rcw-trip-invite-btn px-3 fw-bold"
                       data-action="accept" data-invite-id="${inv.invite_id}" title="Aceptar">
                 <i class="fa-solid fa-check"></i>
               </button>
               <button class="btn btn-sm btn-outline-danger shadow-sm rcw-trip-invite-btn px-3 fw-bold"
                       data-action="decline" data-invite-id="${inv.invite_id}" title="Rechazar">
                 <i class="fa-solid fa-xmark"></i>
               </button>
            </div>
          </div>
        </div>`;
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
        details.push(
          `<i class="fa-solid fa-location-dot text-success me-1"></i>${loc}`,
        );
      }
      if (friend.joined_at) {
        const date = new Date(friend.joined_at);
        const mes = new Intl.DateTimeFormat("es-ES", { month: "long" }).format(
          date,
        );
        const anio = date.getFullYear();
        details.push(
          `<i class="fa-regular fa-calendar text-info me-1"></i>Miembro desde ${mes} de ${anio}`,
        );
      }
      if (friend.since) {
        const dateSince = new Date(friend.since);
        const mesSince = new Intl.DateTimeFormat("es-ES", { month: "long" }).format(
          dateSince,
        );
        const anioSince = dateSince.getFullYear();
        details.push(
          `<i class="fa-solid fa-handshake text-success opacity-75 me-1"></i>Amigos desde ${mesSince} de ${anioSince}`,
        );
      }
      details.push(
        `<span class="text-warning fw-bold">${friend.credits || 0}</span> credits`,
      );

      let detailsHtml =
        details.length > 0
          ? `<div class="d-flex flex-wrap gap-2 align-items-center">${details.join('<span class="d-none d-sm-inline opacity-25">•</span>')}</div>`
          : '<i class="fa-solid fa-user text-muted me-1"></i>Miembro RCW';

      html += `
        <div class="col-12">
          <div class="rcw-friend-row d-flex align-items-center gap-3 px-3 px-md-4 py-3"
               style="background-color: #1a222e; border-bottom: 1px solid var(--rcw-border); transition: background 0.2s;"
               onmouseover="this.style.background='#222b38'"
               onmouseout="this.style.background='#1a222e'">

            <!-- Avatar -->
            <div class="flex-shrink-0">
              <img src="${avatarSrc}"
                alt="${friend.username}"
                class="rounded-circle shadow-sm border border-success border-opacity-25 object-fit-cover"
                style="width: 52px; height: 52px;"
                onerror="this.onerror=null; this.src=window.BASE_URL+'/web/img/avatars/default_avatar.svg'">
            </div>

            <!-- Info -->
            <div class="flex-grow-1 min-w-0 py-1">
              <div class="d-flex align-items-baseline flex-wrap gap-2 mb-0">
                <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}"
                   class="text-white text-decoration-none fw-bold"
                   style="font-size: 1rem; font-family: var(--rcw-font-title);">
                  ${friend.username}
                </a>
                <small class="text-success fw-bold d-flex align-items-center gap-1" style="font-size: 0.65rem;">
                   <i class="fa-solid fa-circle-check"></i> AMIGO
                </small>
                <small class="text-muted font-monospace ms-1" style="font-size: 0.7rem;">Nº ${String(friend.id).padStart(6, "0")}</small>
              </div>
              <div class="text-muted mt-1" style="font-size: 0.75rem;">
                ${detailsHtml}
              </div>
            </div>

            <!-- Btn Eliminar -->
            <div class="flex-shrink-0 ms-auto rcw-trigger-remove"
                 data-id="${friend.id}" data-name="${friend.username}"
                 title="Eliminar amigo"
                 style="cursor:pointer; z-index:10;">
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

  // Forum invite actions (accept / decline)
  $(document).on("click", ".rcw-forum-invite-btn", async function (e) {
    e.preventDefault();
    e.stopPropagation();

    const btn       = $(this);
    const action    = btn.data("action");     // 'accept' | 'decline'
    const inviteId  = btn.data("invite-id");
    const endpoint  = action === "accept" ? "accept_forum_invite" : "decline_forum_invite";

    const originalHtml = btn.html();
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res  = await fetch(`${BASE_URL}/api/php/users.php?action=${endpoint}`, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ invite_id: inviteId }),
      });
      const data = await res.json();
      if (data.success) {
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

  // ── Abrir modal info foro al pulsar la tarjeta ───────────────
  $(document).on("click", ".rcw-forum-invite-info-btn", function (e) {
    // Ignore if an action button inside was clicked
    if ($(e.target).closest(".rcw-forum-invite-btn").length) return;

    e.preventDefault();
    e.stopPropagation();

    // Get data from the card div (might be on this or an ancestor)
    const card = $(this).closest("[data-inv]");
    const rawData = card.length ? card.attr("data-inv") : $(this).attr("data-inv");

    let inv;
    try { inv = JSON.parse(rawData); } catch(err) { return; }

    // Populate modal fields
    $("#forumInviteModalSender").text("Invitación de " + inv.sender_username);
    $("#forumInviteModalTitle").text(inv.forum_title || '—');

    const descWrap = $("#forumInviteModalDescWrap");
    if (inv.forum_description && inv.forum_description.trim()) {
      $("#forumInviteModalDesc").text(inv.forum_description);
      descWrap.show();
    } else {
      descWrap.hide();
    }

    $("#forumInviteModalMembers").text(inv.member_count != null ? inv.member_count : '—');
    if (inv.created_at) {
      const d = new Date(inv.created_at);
      $("#forumInviteModalCreated").text(d.toLocaleDateString('es-ES', {year:'numeric', month:'short', day:'numeric'}));
    } else {
      $("#forumInviteModalCreated").text('—');
    }

    // Store invite_id on modal action buttons
    $("#forumInviteModalAcceptBtn").data('invite-id', inv.invite_id);
    $("#forumInviteModalDeclineBtn").data('invite-id', inv.invite_id);

    new bootstrap.Modal(document.getElementById('forumInviteInfoModal')).show();
  });

  // ── Botones de acción del modal de foro ──────────────────────
  $(document).on("click", ".rcw-forum-invite-modal-action", async function (e) {
    e.preventDefault();
    const btn      = $(this);
    const action   = btn.data("action");   // 'accept' | 'decline'
    const inviteId = btn.data("invite-id");
    const endpoint = action === "accept" ? "accept_forum_invite" : "decline_forum_invite";

    const originalHtml = btn.html();
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res  = await fetch(`${BASE_URL}/api/php/users.php?action=${endpoint}`, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ invite_id: inviteId }),
      });
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('forumInviteInfoModal')).hide();
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
  $(document).on("click", ".rcw-trigger-remove", function (e) {
    e.stopPropagation();
    const id = $(this).data("id");
    const name = $(this).data("name");
    $("#removeFriendName").text(name);
    $("#confirmRemoveFriendBtn").data("id", id);
    const modal = new bootstrap.Modal(
      document.getElementById("removeFriendModal"),
    );
    modal.show();
  });

  $("#confirmRemoveFriendBtn").on("click", async function () {
    const btn = $(this);
    const targetId = btn.data("id");
    const originalHtml = btn.text() || "Eliminando...";

    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/users.php?action=reject_remove_friend`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ target_id: targetId }),
        },
      );
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(
          document.getElementById("removeFriendModal"),
        ).hide();
        fetchFriendsData();
      } else {
        alert("Error: " + (data.error || "Petición fallida"));
      }
    } catch (err) {
      alert("Error de conexión al eliminar amigo.");
    }
    btn.prop("disabled", false).text("Eliminar");
  });

  // Trip invite actions (accept / decline)
  $(document).on("click", ".rcw-trip-invite-btn", async function (e) {
    e.preventDefault();
    e.stopPropagation();

    const btn      = $(this);
    const action   = btn.data("action");
    const inviteId = btn.data("invite-id");
    const accept   = action === "accept";

    const originalHtml = btn.html();
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res = await fetch(`${BASE_URL}/api/php/trips.php?action=respond_invite`, {
        method:  "POST",
        headers: { "Content-Type": "application/json" },
        body:    JSON.stringify({ invite_id: inviteId, accept: accept }),
      });
      const data = await res.json();
      if (data.success) {
        fetchFriendsData();
        // Also refresh header badge if needed
        if (typeof updateCommBadge === "function") updateCommBadge();
      } else {
        alert("Error: " + (data.error || "Acción fallida"));
        btn.prop("disabled", false).html(originalHtml);
      }
    } catch (err) {
      console.error(err);
      btn.prop("disabled", false).html(originalHtml);
    }
  });
});

// Helpers globales para escape de strings (por si no están definidos)
function esc(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function escJS(str) {
  if (!str) return "";
  return String(str)
    .replace(/\\/g, "\\\\")
    .replace(/'/g, "\\'")
    .replace(/"/g, '\\"')
    .replace(/\n/g, "\\n")
    .replace(/\r/g, "\\r");
}
