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
      const res = await fetch(`${BASE_URL}/api/php/users.php?action=get_friends_data`);
      const payload = await res.json();
      
      loading.hide();
      container.show();
      sentContainer.show();
      
      if (payload.success) {
        renderReceived(payload.data.received_requests);
        renderFriends(payload.data.friends);
        renderSent(payload.data.sent_requests);
      } else {
        alert("Error cargando amistades: " + (payload.error || "Error desconocido"));
      }
    } catch (e) {
      console.error(e);
      loading.html('<div class="col-12 text-center py-5 text-danger">Error de conexión con el servidor.</div>');
    }
  }

  // Make available globally so header_friends.js can call it
  window.fetchFriendsData = fetchFriendsData;

  function renderReceived(requests) {
    requestsList.empty();
    requestsCount.text(requests.length);
    
    if (requests.length === 0) {
      requestsList.html('<div class="p-4 text-center text-muted small"><i class="fa-solid fa-box-open d-block fs-3 mb-2 opacity-25"></i>No tienes solicitudes pendientes.</div>');
      return;
    }

    let html = '';
    requests.forEach(req => {
      const avatarSrc = req.profile_image ? (req.profile_image.startsWith('/') ? BASE_URL + req.profile_image : req.profile_image) : `https://ui-avatars.com/api/?name=${encodeURIComponent(req.username)}&background=ffc107&color=000`;
      
      html += `
        <div class="list-group-item bg-dark text-white border-warning border-opacity-10 py-3" style="transition: background 0.2s;">
          <div class="d-flex align-items-center">
            <img src="${avatarSrc}" alt="${req.username}" class="rounded-circle object-fit-cover me-3 border border-warning shadow-sm" style="width: 45px; height: 45px;">
            <div class="flex-grow-1 min-w-0">
               <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${req.id}" class="text-white text-decoration-none fw-bold d-block text-truncate">${req.username}</a>
               <small class="text-muted d-block">Quiere conectarse</small>
            </div>
            <div class="d-flex flex-column gap-2 ms-2">
               <button class="btn btn-sm btn-success shadow-sm rcw-action-btn" data-action="accept" data-id="${req.id}" title="Aceptar"><i class="fa-solid fa-check"></i></button>
               <button class="btn btn-sm btn-outline-danger shadow-sm border-0 rcw-action-btn" data-action="reject" data-id="${req.id}" title="Rechazar"><i class="fa-solid fa-xmark"></i></button>
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
      friendsList.html('<div class="col-12 p-5 text-center text-muted"><i class="fa-solid fa-user-group d-block fa-3x mb-3 opacity-25"></i>Todavía no has hecho amigos.<br>¡Busca en la comunidad y conéctate!</div>');
      return;
    }

    let html = '';
    friends.forEach(friend => {
      const avatarSrc = friend.profile_image ? (friend.profile_image.startsWith('/') ? BASE_URL + friend.profile_image : friend.profile_image) : `https://ui-avatars.com/api/?name=${encodeURIComponent(friend.username)}&background=198754&color=fff`;
      
      html += `
        <div class="col-12 col-md-6 col-lg-4">
           <div class="card h-100 bg-transparent border-success border-opacity-25 shadow-sm text-white" style="transition: transform 0.2s;">
             <div class="card-body d-flex align-items-center p-3 position-relative">
               <img src="${avatarSrc}" class="rounded-circle object-fit-cover shadow me-3" style="width: 60px; height: 60px; border: 2px solid var(--rcw-green);">
               <div class="flex-grow-1 min-w-0">
                 <h6 class="mb-1 text-truncate"><a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}" class="text-white text-decoration-none fw-bold stretched-link">${friend.username}</a></h6>
                 <small class="text-muted d-block"><i class="fa-solid fa-calendar-check me-1"></i>Amigos</small>
               </div>
               <div class="position-absolute dropdown dropstart" style="top: 10px; right: 10px; z-index: 2;">
                 <button class="btn btn-sm btn-link text-muted pe-1 shadow-none" type="button" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                 <ul class="dropdown-menu dropdown-menu-dark shadow border-0">
                    <li><a class="dropdown-item text-danger rcw-action-btn" href="#" data-action="remove" data-id="${friend.id}"><i class="fa-solid fa-user-minus me-2"></i>Eliminar Amigo</a></li>
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
      sentList.html('<li class="list-group-item bg-transparent text-muted border-0 small">No tienes invitaciones pendientes de aceptar por otros usuarios.</li>');
      return;
    }

    let html = '';
    sent.forEach(req => {
      const avatarSrc = req.profile_image ? (req.profile_image.startsWith('/') ? BASE_URL + req.profile_image : req.profile_image) : `https://ui-avatars.com/api/?name=${encodeURIComponent(req.username)}&background=6c757d&color=fff`;
      html += `
        <li class="list-group-item bg-transparent text-white border-bottom border-light border-opacity-10 py-2">
           <div class="d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                 <img src="${avatarSrc}" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                 <span class="small fw-semibold text-muted">Invitación enviada a ${req.username}</span>
              </div>
              <button class="btn btn-sm btn-outline-danger border-0 py-0 px-2 rcw-action-btn" data-action="cancel" data-id="${req.id}" title="Cancelar envío"><i class="fa-solid fa-xmark"></i> Cancelar</button>
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
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

    let endpoint = "";
    if (action === "accept") endpoint = "accept_friend";
    else if (action === "reject" || action === "remove" || action === "cancel") endpoint = "reject_remove_friend";

    try {
      const res = await fetch(`${BASE_URL}/api/php/users.php?action=${endpoint}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ target_id: targetId })
      });
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
