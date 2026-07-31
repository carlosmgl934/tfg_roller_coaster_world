$(document).ready(function () {
  // Inject small generic error modal for friends manager
  if (!$('#rcwFriendErrorModal').length) {
    const errorModalHtml = `
      <div class="modal fade" id="rcwFriendErrorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
          <div class="modal-content bg-dark text-white border border-danger border-opacity-50 rounded-0 shadow">
            <div class="modal-body text-center p-4">
              <i class="fa-solid fa-triangle-exclamation text-danger fs-1 mb-3"></i>
              <p class="mb-4" id="rcwFriendErrorModalMessage"></p>
              <button type="button" class="btn btn-outline-light btn-sm px-4 rounded-0" data-bs-dismiss="modal">OK</button>
            </div>
          </div>
        </div>
      </div>
    `;
    $('body').append(errorModalHtml);
  }

  function showFriendError(message) {
    $('#rcwFriendErrorModalMessage').text(message);
    const modal = new bootstrap.Modal(document.getElementById('rcwFriendErrorModal'));
    modal.show();
  }

  // Inject styles for hover effects
  if (!$('#rcw-friend-card-styles').length) {
    $('<style id="rcw-friend-card-styles">').text(`
      .rcw-btn-remove-friend {
        transition: background-color 0.2s ease, color 0.2s ease !important;
      }
      .rcw-btn-remove-friend:hover,
      .rcw-btn-remove-friend:focus-visible {
        background-color: var(--bs-danger, #dc3545) !important;
      }
      .rcw-btn-remove-friend:hover i,
      .rcw-btn-remove-friend:focus-visible i {
        color: #ffffff !important;
      }
      .rcw-btn-view-profile i {
        transition: color 0.2s ease;
      }
      .rcw-btn-view-profile:hover i,
      .rcw-btn-view-profile:focus-visible i {
        color: var(--bs-success, #198754) !important;
      }
    `).appendTo('head');
  }

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
        renderReceived(
          payload.data.received_requests,
          forumInvites,
          tripInvites,
        );
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
    const fallbackInitials = BASE_URL + "/web/img/avatars/default_avatar.svg";

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
    const totalCount =
      requests.length + forumInvites.length + tripInvites.length;
    requestsCount.text(totalCount);

    if (totalCount > 0) {
      requestsCount
        .removeClass("badge-profile-gray")
        .addClass("badge-profile-danger");
    } else {
      requestsCount
        .removeClass("badge-profile-danger")
        .addClass("badge-profile-gray");
    }

    if (totalCount === 0) {
      requestsList.html(
        '<div class="p-4 text-center text-muted small"><i class="fa-solid fa-box-open d-block fs-3 mb-2 opacity-25"></i><span data-i18n="users.friends.no_pending_requests">' + window.t('users.friends.no_pending_requests') + '</span></div>',
      );
      return;
    }

    let html = "";

    // ── Solicitudes de amistad ──────────────────────────────────
    requests.forEach((req) => {
      const avatarSrc = getAvatarUrl(
        req.profile_image,
        req.username,
        "ffc107",
        "000",
      );
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
      const avatarSrc = getAvatarUrl(
        inv.sender_image,
        inv.sender_username,
        "6d28d9",
        "fff",
      );

      // Compact short title for badge preview (max 40 chars)
      const shortTitle =
        inv.forum_title && inv.forum_title.length > 40
          ? inv.forum_title.substring(0, 40) + "…"
          : inv.forum_title || "—";

      // Data JSON encoded for modal
      const dataInv = JSON.stringify({
        invite_id: inv.invite_id,
        sender_username: inv.sender_username,
        forum_title: inv.forum_title,
        forum_description: inv.forum_description || "",
        member_count: inv.member_count || null,
        created_at: inv.created_at || null,
      }).replace(/'/g, "&apos;");

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
      const avatarSrc = getAvatarUrl(
        inv.inviter_image,
        inv.inviter_username,
        "10b981",
        "fff",
      );
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

      // Pluralization
      const creditAmount = friend.credits || 0;
      const creditLabelKey = creditAmount === 1 ? 'users.friends.credit_label' : 'users.friends.credits_label';
      const creditLabelFallback = creditAmount === 1 ? 'crédito' : 'créditos';

      let metaHtml = "";
      if (friend.city || friend.country) {
        let loc = [friend.city, friend.country].filter(Boolean).join(", ");
        metaHtml += `<div class="d-flex align-items-center gap-2 mb-2"><i class="fa-solid fa-location-dot text-secondary" style="width: 16px; text-align: center;"></i><span class="text-muted small text-truncate">${loc}</span></div>`;
      }
      if (friend.joined_at) {
        const date = new Date(friend.joined_at);
        const mes = new Intl.DateTimeFormat("es-ES", { month: "short" }).format(date);
        const anio = date.getFullYear();
        metaHtml += `<div class="d-flex align-items-center gap-2 mb-2"><i class="fa-regular fa-calendar text-secondary" style="width: 16px; text-align: center;"></i><span class="text-muted small"><span data-i18n="users.friends.member_since">${window.t('users.friends.member_since')}</span> ${mes} ${anio}</span></div>`;
      }
      if (friend.since) {
        const dateSince = new Date(friend.since);
        const mesSince = new Intl.DateTimeFormat("es-ES", { month: "short" }).format(dateSince);
        const anioSince = dateSince.getFullYear();
        metaHtml += `<div class="d-flex align-items-center gap-2 mb-2"><i class="fa-regular fa-handshake text-secondary" style="width: 16px; text-align: center;"></i><span class="text-muted small"><span data-i18n="users.friends.friends_since">${window.t('users.friends.friends_since')}</span> ${mesSince} ${anioSince}</span></div>`;
      }

      html += `
        <div class="col-12 col-md-6 col-xl-6 col-xxl-4 p-2">
          <div class="rcw-friend-card h-100 rounded-0 shadow-sm d-flex flex-column"
               style="background-color: var(--bs-dark, #212529); border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease; position: relative;"
               onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='var(--bs-success, #198754)'; this.style.boxShadow='0 .5rem 1rem rgba(0,0,0,.25)';"
               onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(255,255,255,0.05)'; this.style.boxShadow='0 .125rem .25rem rgba(0,0,0,.075)';">
            
            <!-- Header: Avatar + Nombre -->
            <div class="d-flex align-items-center gap-3 p-3" style="background-color: rgba(255,255,255,0.03);">
              <!-- Avatar -->
              <div class="position-relative flex-shrink-0">
                <div class="rounded-circle p-1" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
                  <img src="${avatarSrc}" alt="${friend.username}" class="rounded-circle object-fit-cover" style="width: 56px; height: 56px;" onerror="this.onerror=null; this.src=window.BASE_URL+'/web/img/avatars/default_avatar.svg'">
                </div>
                <div class="position-absolute bg-success border border-2 border-dark rounded-circle" style="width: 14px; height: 14px; bottom: 2px; right: 2px;" title="Conectado"></div>
              </div>
              
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                  <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}" class="text-white text-decoration-none fw-bold text-truncate" style="font-size: 1.15rem; font-family: var(--rcw-font-title); letter-spacing: -0.01em; max-width: 100%;">
                    ${friend.username}
                  </a>
                  <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-0 px-2 py-1" style="font-size: 0.6rem; letter-spacing: 0.05em;" data-i18n="users.friends.friend_badge">${window.t('users.friends.friend_badge')}</span>
                </div>
                <div class="text-muted font-monospace small" style="font-size: 0.75rem;">#${String(friend.id).padStart(6, "0")}</div>
              </div>
            </div>

            <!-- Divisor grueso verde -->
            <div style="height: 3px; background-color: var(--bs-success, #198754); opacity: 0.8;"></div>

            <!-- Metadatos (Cuerpo) -->
            <div class="d-flex flex-column flex-grow-1 p-3 pb-2" style="background-color: transparent;">
              ${metaHtml}
            </div>

            <!-- Divisor grueso verde -->
            <div style="height: 3px; background-color: var(--bs-success, #198754); opacity: 0.5;"></div>

            <!-- Footer: Toolbar de créditos y botones rectos -->
            <div class="p-0 d-flex align-items-stretch mt-auto" style="background-color: rgba(0,0,0,0.2);">
              <div class="d-flex align-items-center gap-2 px-3 py-2 flex-grow-1">
                <i class="fa-solid fa-ticket text-success opacity-75"></i>
                <span class="fw-bold text-white" style="font-size: 1.05rem;">${creditAmount}</span>
                <span class="text-muted small" data-i18n="${creditLabelKey}">${window.t(creditLabelKey) || creditLabelFallback}</span>
              </div>
              
              <div class="d-flex border-start border-secondary border-opacity-25">
                <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}" 
                   class="btn btn-dark rounded-0 border-0 d-flex align-items-center justify-content-center rcw-btn-view-profile"
                   style="width: 48px; background-color: transparent;" title="${window.t('users.friends.view_profile')}" data-i18n-attr="title">
                  <i class="fa-solid fa-eye text-secondary" style="font-size: 0.95rem;"></i>
                </a>
                <div style="width: 1px; background-color: rgba(255,255,255,0.05);"></div>
                <button class="btn btn-dark rounded-0 border-0 d-flex align-items-center justify-content-center rcw-trigger-remove rcw-btn-remove-friend"
                        style="width: 48px; background-color: rgba(220,53,69,0.05);" data-id="${friend.id}" data-name="${friend.username}" title="${window.t('users.friends.remove_title')}" data-i18n-attr="title">
                  <i class="fa-solid fa-user-xmark text-danger" style="font-size: 0.95rem; pointer-events: none;"></i>
                </button>
              </div>
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
        '<li class="list-group-item bg-transparent text-muted border-0 small" data-i18n="users.friends.no_pending_sent">' + window.t('users.friends.no_pending_sent') + '</li>',
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
                 <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${req.id}" class="small fw-bold text-muted text-decoration-none"><span data-i18n="users.friends.invite_sent_to">${window.t('users.friends.invite_sent_to')}</span> ${req.username}</a>
              </div>
              <button class="btn btn-sm btn-outline-danger py-1 px-3 rcw-action-btn ms-auto" data-action="cancel" data-id="${req.id}" title="${window.t('users.friends.cancel_send')}" data-i18n-attr="title"><i class="fa-solid fa-xmark me-1"></i> <span data-i18n="common.cancel">${window.t('common.cancel')}</span></button>
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

    const btn = $(this);
    const action = btn.data("action"); // 'accept' | 'decline'
    const inviteId = btn.data("invite-id");
    const endpoint =
      action === "accept" ? "accept_forum_invite" : "decline_forum_invite";

    const originalHtml = btn.html();
    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/users.php?action=${endpoint}`,
        {
          method: "POST",
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ invite_id: inviteId }),
        },
      );
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
    const rawData = card.length
      ? card.attr("data-inv")
      : $(this).attr("data-inv");

    let inv;
    try {
      inv = JSON.parse(rawData);
    } catch (err) {
      return;
    }

    // Populate modal fields
    $("#forumInviteModalSender").text("Invitación de " + inv.sender_username);
    $("#forumInviteModalTitle").text(inv.forum_title || "—");

    const descWrap = $("#forumInviteModalDescWrap");
    if (inv.forum_description && inv.forum_description.trim()) {
      $("#forumInviteModalDesc").text(inv.forum_description);
      descWrap.show();
    } else {
      descWrap.hide();
    }

    $("#forumInviteModalMembers").text(
      inv.member_count != null ? inv.member_count : "—",
    );
    if (inv.created_at) {
      const d = new Date(inv.created_at);
      $("#forumInviteModalCreated").text(
        d.toLocaleDateString("es-ES", {
          year: "numeric",
          month: "short",
          day: "numeric",
        }),
      );
    } else {
      $("#forumInviteModalCreated").text("—");
    }

    // Store invite_id on modal action buttons
    $("#forumInviteModalAcceptBtn").data("invite-id", inv.invite_id);
    $("#forumInviteModalDeclineBtn").data("invite-id", inv.invite_id);

    new bootstrap.Modal(document.getElementById("forumInviteInfoModal")).show();
  });

  // ── Botones de acción del modal de foro ──────────────────────
  $(document).on("click", ".rcw-forum-invite-modal-action", async function (e) {
    e.preventDefault();
    const btn = $(this);
    const action = btn.data("action"); // 'accept' | 'decline'
    const inviteId = btn.data("invite-id");
    const endpoint =
      action === "accept" ? "accept_forum_invite" : "decline_forum_invite";

    const originalHtml = btn.html();
    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/users.php?action=${endpoint}`,
        {
          method: "POST",
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ invite_id: inviteId }),
        },
      );
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(
          document.getElementById("forumInviteInfoModal"),
        ).hide();
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
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
            "Content-Type": "application/json",
          },
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
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
            "Content-Type": "application/json",
          },
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
        showFriendError(window.t('users.friends.error_request_failed') + (data.error ? ": " + data.error : ""));
      }
    } catch (err) {
      showFriendError(window.t('users.friends.error_connection'));
    }
    btn.prop("disabled", false).text("Eliminar");
  });

  // Trip invite actions (accept / decline)
  $(document).on("click", ".rcw-trip-invite-btn", async function (e) {
    e.preventDefault();
    e.stopPropagation();

    const btn = $(this);
    const action = btn.data("action");
    const inviteId = btn.data("invite-id");
    const accept = action === "accept";

    const originalHtml = btn.html();
    btn
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/trips.php?action=respond_invite`,
        {
          method: "POST",
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ invite_id: inviteId, accept: accept }),
        },
      );
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
