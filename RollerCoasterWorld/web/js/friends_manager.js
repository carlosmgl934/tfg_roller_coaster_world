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
        renderReceived(payload.data.received_requests);
        renderFriends(payload.data.friends);
        renderSent(payload.data.sent_requests);
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

  function renderReceived(requests) {
    requestsList.empty();
    requestsCount.text(requests.length);

    if (requests.length > 0) {
      requestsCount.removeClass('badge-gray bg-secondary').addClass('bg-danger');
    } else {
      requestsCount.removeClass('bg-danger bg-secondary').addClass('badge-gray');
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

      html += `
        <div class="col-12 col-xl-6">
           <div class="card profile-card h-100 border-start border-3 border-success">
             <div class="card-body d-flex align-items-center p-3 px-4 position-relative">
               <img src="${avatarSrc}" class="rounded-circle object-fit-cover shadow-sm me-4 border border-1 border-white border-opacity-25" style="width: 65px; height: 65px;" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(friend.username)}&background=10b981&color=000'">
               <div class="flex-grow-1 min-w-0">
                 <h5 class="fw-bold text-truncate mb-1"><a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}" class="text-white text-decoration-none stretched-link">${friend.username}</a></h5>
                 <small class="text-success d-block fw-semibold opacity-75"><i class="fa-solid fa-user-check me-1"></i>Amigos</small>
               </div>
               <div class="position-absolute dropdown dropstart" style="top: 50%; right: 15px; transform: translateY(-50%); z-index: 2;">
                 <button class="btn btn-link text-muted pe-1 shadow-none" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical fa-lg"></i></button>
                 <ul class="dropdown-menu shadow border-rcw">
                    <li><a class="dropdown-item text-danger rcw-action-btn py-2" href="#" data-action="remove" data-id="${friend.id}"><i class="fa-solid fa-user-minus me-2"></i>Eliminar Amigo</a></li>
                 </ul>
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
});
