$(document).ready(function () {
  const urlParams = new URLSearchParams(window.location.search);
  const userId = urlParams.get("id");

  // ── Helper: resuelve profile_image de forma robusta ──────────
  function resolveAvatarUrl(profileImage, username) {
    const initials = username.substring(0, 2).toUpperCase();
    if (!profileImage) return { type: "initials", initials };
    if (
      profileImage.startsWith("http://") ||
      profileImage.startsWith("https://")
    )
      return { type: "image", src: profileImage };
    if (profileImage.startsWith("/")) {
      if (profileImage.includes("/web/img/uploads/"))
        return { type: "initials", initials };
      return { type: "image", src: BASE_URL + profileImage };
    }
    return {
      type: "image",
      src:
        "https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/" +
        profileImage,
    };
  }

  const profileContent = $("#profile-content");
  const loading = $("#profile-loading");

  if (!userId) {
    loading.html(
      '<div class="alert alert-warning">No se ha especificado un ID de usuario.</div>',
    );
    return;
  }

  fetchProfileData(userId);

  async function fetchProfileData(id) {
    try {
      const res = await fetch(
        `${BASE_URL}/api/php/users.php?action=get_public_profile&id=${id}`,
      );
      const data = await res.json();
      if (data.success) {
        renderProfile(data.data);
        loading.hide();
        profileContent.fadeIn();
      } else {
        loading.html(
          `<div class="alert alert-danger">${data.error || "Perfil no disponible"}</div>`,
        );
      }
    } catch (e) {
      console.error(e);
      loading.html(
        '<div class="alert alert-danger">Error conectando con el servidor.</div>',
      );
    }
  }

  function renderProfile(data) {
    const user = data.user;
    const stats = data.stats;
    const topParks = data.top_parks;
    const topCoasters = data.top_coasters || [];
    const userPhotos = data.photos || [];
    const techStats = data.tech_stats;
    const fStatus = data.friendship_status;

    // Nombre y ubicación
    $("#user-username").text(user.username);
    const loc =
      [user.city, user.country].filter(Boolean).join(", ") ||
      "Ubicación desconocida";
    $("#user-location span").text(loc);

    // Avatar
    const avatarDiv = $("#user-avatar");
    const resolved = resolveAvatarUrl(user.profile_image, user.username);
    if (resolved.type === "image") {
      avatarDiv.html(
        `<img src="${resolved.src}" class="w-100 h-100 object-fit-cover rounded-circle shadow"
              onerror="$(this).parent().text('${user.username.substring(0, 2).toUpperCase()}')">`,
      );
    } else {
      avatarDiv.text(resolved.initials);
    }

    // Stats generales
    $("#stat-coasters").text(stats.coasters || 0);
    $("#stat-parks").text(stats.parks || 0);
    $("#stat-countries").text(stats.countries || 0);
    $("#stat-reviews").text(stats.reviews || 0);
    $("#stat-friends").text(stats.friends || 0);
    $("#stat-photos").text(stats.photos || 0);

    // Favoritos
    $("#user-fav-coaster").text(user.favorite_coaster || "No seleccionada");
    $("#user-home-park").text(user.home_park || "No seleccionado");
    const topParkName =
      topParks && topParks.length > 0
        ? topParks[0].park_name
        : "No seleccionado";
    $("#user-top-park").text(topParkName);

    // Fecha de ingreso
    const joinedDate = new Date(user.created_at).toLocaleDateString("es-ES", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });
    $("#user-joined").text(joinedDate);

    // Stats técnicas
    if (techStats) {
      const totalHeight = parseInt(techStats.total_height || 0);
      const totalInv = parseInt(techStats.total_inversions || 0);
      const fastest = techStats.fastest_coaster && techStats.fastest_coaster !== "—" ? techStats.fastest_coaster : "—";
      const longest = techStats.longest_coaster && techStats.longest_coaster !== "—" ? techStats.longest_coaster : "—";

      $("#stat-tech-height").text(totalHeight);
      $("#stat-tech-inversions").text(totalInv);
      $("#stat-tech-speed").text(fastest);
      $("#stat-tech-length").text(longest);
      $("#stat-tech-country").text(techStats.most_visited_country || "--");
      $("#stat-tech-manufacturer").text(
        techStats.favorite_manufacturer || "--",
      );
      $("#stat-tech-total-manu").text(techStats.total_manufacturers || 0);
    }

    // Botón de amistad, tops, fotos y amigos
    renderFriendshipButton(user.id, fStatus);
    renderTops(topCoasters, topParks);
    renderPhotos(userPhotos, user);
    renderFriendsList(data.friends || [], user.username);

    setupTabs();
  }

  function setupTabs() {
    const menuLinks = $("#sidebar-menu a.list-group-item").not(
      'a[href*="trip_generator"]',
    );

    function activateSection(menuId) {
      menuLinks.removeClass("active");
      $(`#${menuId}`).addClass("active");
      $(".content-section").hide();
      const map = {
        "menu-profile": "#section-info",
        "menu-tops":    "#section-tops",
        "menu-photos":  "#section-photos",
        "menu-friends": "#section-friends",
      };
      if (map[menuId]) $(map[menuId]).show();
    }

    menuLinks.on("click", function (e) {
      e.preventDefault();
      activateSection($(this).attr("id"));
    });

    // Leer el hash de la URL para abrir la pestaña correcta al cargar
    const hash = window.location.hash;
    if      (hash === "#tops")    activateSection("menu-tops");
    else if (hash === "#photos")  activateSection("menu-photos");
    else if (hash === "#friends") activateSection("menu-friends");
    else                          activateSection("menu-profile");
  }

  function renderFriendshipButton(targetId, status) {
    const container = $("#friendship-action-container");
    container.empty();
    if (status === null) return;

    let btnHtml = "";
    if (status === "none") {
      btnHtml = `<button class="btn btn-success fw-bold px-4 rounded-pill shadow-sm py-2 action-friend" data-action="request">
                    <i class="fa-solid fa-user-plus me-2"></i>Enviar Solicitud</button>`;
    } else if (status === "pending_sent") {
      btnHtml = `<button class="btn btn-outline-secondary fw-bold px-4 rounded-pill py-2 action-friend" data-action="cancel">
                    <i class="fa-solid fa-clock me-2"></i>Solicitud Enviada</button>`;
    } else if (status === "pending_received") {
      btnHtml = `<div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-success fw-bold px-3 rounded-pill py-2 action-friend" data-action="accept">
                        <i class="fa-solid fa-check me-1"></i>Aceptar</button>
                    <button class="btn btn-outline-danger fw-bold px-3 rounded-pill py-2 action-friend" data-action="reject">
                        <i class="fa-solid fa-xmark"></i></button>
                 </div>`;
    } else if (status === "accepted") {
      btnHtml = `<button class="btn btn-outline-success fw-bold px-4 rounded-pill py-2 profile-remove-friend-trigger"
                     data-id="${targetId}" data-name="${$("#user-username").text()}">
                     <i class="fa-solid fa-user-check me-2"></i>Amigos
                 </button>`;
    }
    container.html(btnHtml);

    $(".action-friend")
      .off("click")
      .on("click", async function (e) {
        e.preventDefault();
        const btn = $(this);
        const action = btn.data("action");
        btn
          .prop("disabled", true)
          .prepend(
            '<span class="spinner-border spinner-border-sm me-2"></span>',
          );

        const endpointMap = {
          request: "friend_request",
          accept: "accept_friend",
          reject: "reject_remove_friend",
          remove: "reject_remove_friend",
          cancel: "reject_remove_friend",
        };

        try {
          const res = await fetch(
            `${BASE_URL}/api/php/users.php?action=${endpointMap[action]}`,
            {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ target_id: targetId }),
            },
          );
          const resData = await res.json();
          fetchProfileData(targetId); // refrescar siempre
          if (!resData.success)
            alert(
              "Error: " + (resData.error || "No se pudo realizar la acción"),
            );
        } catch (err) {
          console.error(err);
        }
      });
  }

  function renderTops(coasters, parks) {
    const container = $("#tops-list-container");
    const selector = $("#tops-type-selector");

    const fallbackImage =
      "https://cdn.hourdetroit.com/wp-content/uploads/sites/20/2019/05/Cedar-Point-Main-4.png";

    function drawList(items, isCoaster) {
      container.empty();
      if (!items || items.length === 0) {
        container.html(
          `<div class="p-4 text-center text-muted"><i class="fa-solid fa-ghost fs-1 mb-3"></i><br>El usuario no ha definido un ranking de ${isCoaster ? "coasters" : "parques"} aún.</div>`,
        );
        return;
      }

      items.forEach((item, index) => {
        const imgUrl = item.imagen_url
          ? item.imagen_url.startsWith("/")
            ? BASE_URL + item.imagen_url
            : item.imagen_url
          : fallbackImage;
        const link = isCoaster
          ? `${BASE_URL}/web/views/public/coasters/coasters.php?id=${item.id}`
          : `${BASE_URL}/web/views/public/parks/parks.php?id=${item.id}`;
        const rank = parseInt(item.rank_position) || index + 1;
        const title = isCoaster ? item.coaster_name : item.park_name;
        const subtitle1 = isCoaster ? item.manufacturer : item.park_country;
        const subtitle2 = isCoaster ? item.location : "";

        const subtitleHTML = subtitle2
          ? `${subtitle1} &bull; <span class="text-secondary">${subtitle2}</span>`
          : `${subtitle1}`;

        const html = `
            <a href="${link}" class="list-group-item list-group-item-action bg-transparent border-bottom border-secondary border-opacity-25 px-0 py-3" style="transition: all 0.2s ease-in-out;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.05)'" onmouseout="this.style.backgroundColor='transparent'">
                <div class="d-flex align-items-center gap-3 px-3">
                    <img src="${imgUrl}" alt="${title}" class="rounded shadow-sm" style="width: 120px; height: 80px; object-fit: cover;" onerror="this.src='${fallbackImage}'">
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="fw-bold mb-1 text-light fs-5"><span class="text-success me-2">${rank}</span> ${title}</h5>
                        <p class="mb-0 text-muted small">${subtitleHTML}</p>
                    </div>
                </div>
            </a>
            `;
        container.append(html);
      });
    }

    // Por defecto mostramos coasters
    drawList(coasters, true);

    selector.off("change").on("change", function () {
      if ($(this).val() === "coasters") {
        drawList(coasters, true);
      } else {
        drawList(parks, false);
      }
    });
  }

  function renderPhotos(photos, user) {
    const container = $("#photos-grid-container");
    container.empty();

    if (!photos || photos.length === 0) {
      container.html(
        '<div class="col-12 text-center py-5 text-muted"><i class="fa-solid fa-camera-viewfinder fs-1 mb-3 opacity-50"></i><br>No hay fotos públicas de este viajero.</div>',
      );
      return;
    }

    photos.forEach((photo, index) => {
      const url = photo.photo_url.startsWith("/")
        ? BASE_URL + photo.photo_url
        : photo.photo_url;
      const caption =
        photo.caption || `${photo.coaster_name} en ${photo.park_name}`;
      
      const username = user ? user.username : (photo.username || $("#user-username").text());
      const profileImage = user ? user.profile_image : (photo.profile_image || "");
      const avatarObj = resolveAvatarUrl(profileImage, username);
      const avatarSrc = avatarObj.type === "image" ? avatarObj.src : `https://ui-avatars.com/api/?name=${username}&background=random`;
      const likes = photo.likes || 0;

      const html = `
        <div class="col-6 col-md-4 col-xl-3">
            <div class="position-relative ratio ratio-1x1 overflow-hidden shadow-sm group-hover-zoom photo-square-container"
                 style="cursor: pointer;"
                 data-id="${photo.id || index}"
                 data-index="${index}"
                 data-url="${url}"
                 data-username="${username}"
                 data-avatar="${avatarSrc}"
                 data-caption="${caption}"
                 data-likes="${likes}">
                <img src="${url}" class="object-fit-cover w-100 h-100" style="transition: transform 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" alt="Foto">
                <div class="position-absolute bottom-0 start-0 w-100 p-2" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent); pointer-events:none;">
                    <p class="text-white small mb-0 fw-bold text-truncate w-100" style="font-size: 0.75rem;">${caption}</p>
                </div>
            </div>
        </div>
        `;
      container.append(html);
    });

    let currentPhotoIndex = 0;
    
    function updateModalContent(index) {
        const allPhotosList = $(".photo-square-container");
        if (index < 0 || index >= allPhotosList.length) return;
        currentPhotoIndex = index;
        const el = $(allPhotosList[index]);
        
        const id = el.data("id");
        const url = el.data("url");
        const username = el.data("username");
        const avatar = el.data("avatar");
        const caption = el.data("caption");

        $("#ig-modal-img").attr("src", url);
        $("#ig-modal-avatar").attr("src", avatar);
        $("#ig-modal-username").text(username);
        
        if (caption) {
          $("#ig-modal-caption-user").text(username);
          $("#ig-modal-caption").html(`<span class="text-muted opacity-50 mx-1">&bull;</span> ${caption}`);
        } else {
          $("#ig-modal-caption-user").text("");
          $("#ig-modal-caption").text("");
        }

        $("#ig-modal-prev").toggle(index > 0);
        $("#ig-modal-next").toggle(index < allPhotosList.length - 1);
    }

    $("#photos-grid-container").off("click", ".photo-square-container").on("click", ".photo-square-container", function() {
        const index = $(this).data("index");
        updateModalContent(index);
        new bootstrap.Modal(document.getElementById("ig-lightbox-modal")).show();
    });

    $("#ig-modal-prev").off("click").on("click", function() {
        updateModalContent(currentPhotoIndex - 1);
    });

    $("#ig-modal-next").off("click").on("click", function() {
        updateModalContent(currentPhotoIndex + 1);
    });

  }

  // ─── Renderizar amigos del perfil visitado ──────────────────────
  function renderFriendsList(friends, ownerUsername) {
    const container = $("#friends-list-container");
    container.empty();

    $("#friends-section-username").text(ownerUsername);
    $("#friends-section-count").text(friends.length);

    if (!friends || friends.length === 0) {
      container.html('<div class="p-5 text-center text-muted"><i class="fa-solid fa-user-group fa-3x mb-3 d-block opacity-25"></i>Este usuario aún no tiene amigos en la plataforma.</div>');
      return;
    }

    let html = '';
    friends.forEach(friend => {
      const avatarObj = resolveAvatarUrl(friend.profile_image, friend.username);
      const avatarSrc = avatarObj.type === "image"
        ? avatarObj.src
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(friend.username)}&background=198754&color=fff&size=128`;

      let details = [];
      if (friend.city || friend.country) {
          let loc = [friend.city, friend.country].filter(Boolean).join(", ");
          details.push(`<i class="fa-solid fa-location-dot text-success me-1"></i>${loc}`);
      }
      if (friend.credits > 0) {
          details.push(`<i class="fa-solid fa-ticket text-warning me-1"></i>${friend.credits} credits`);
      }
      if (friend.created_at) {
          const date = new Date(friend.created_at);
          const mes = new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(date);
          const anio = date.getFullYear();
          details.push(`<i class="fa-regular fa-calendar text-info me-1"></i>Miembro desde ${mes} de ${anio}`);
      }
      let detailsHtml = details.length > 0 ? details.join('<span class="mx-2 opacity-25">&bull;</span>') : 'Miembro RCW';

      html += `
        <div class="d-flex align-items-center gap-3 px-4 py-3 position-relative"
             style="border-bottom: 1px solid var(--rcw-border); transition: background 0.2s;"
             onmouseover="this.style.background='rgba(25,135,84,0.06)'"
             onmouseout="this.style.background='transparent'">

          <!-- Avatar -->
          <img src="${avatarSrc}"
               alt="${friend.username}"
               class="rounded-circle object-fit-cover flex-shrink-0"
               style="width: 46px; height: 46px; border: 2px solid rgba(25,135,84,0.4);"
               onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(friend.username)}&background=198754&color=fff&size=128'">

          <!-- Nombre -->
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-white text-truncate" style="font-size: 0.95rem;">${friend.username}</div>
            <small class="text-muted d-block text-truncate mt-1" style="font-size: 0.75rem;">${detailsHtml}</small>
          </div>

          <!-- Acciones -->
          <div class="d-flex gap-2 flex-shrink-0 position-relative" style="z-index: 2;">
            <a href="${BASE_URL}/web/views/public/users/user_profile.php?id=${friend.id}"
               class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 20px; font-size: 0.78rem;">
              <i class="fa-solid fa-eye me-1"></i>Ver perfil
            </a>
            <button class="btn btn-sm btn-success px-3 rcw-add-from-profile-btn"
                    data-id="${friend.id}"
                    style="border-radius: 20px; font-size: 0.78rem; display: none;">
              <i class="fa-solid fa-user-plus me-1"></i>Añadir
            </button>
          </div>
        </div>
      `;
    });

    container.html(html);
  }

  // ─── Modal Confirmar Eliminar Amigo (perfil público) ───────────
  $(document).on("click", ".profile-remove-friend-trigger", function() {
    const id = $(this).data("id");
    const name = $(this).data("name") || $("#user-username").text();
    $("#removeProfileFriendName").text(name);
    $("#confirmRemoveProfileFriendBtn").data("id", id);
    new bootstrap.Modal(document.getElementById("removeProfileFriendModal")).show();
  });

  $("#confirmRemoveProfileFriendBtn").on("click", async function() {
    const btn = $(this);
    const targetId = btn.data("id");
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span>');

    try {
      const res = await fetch(`${BASE_URL}/api/php/users.php?action=reject_remove_friend`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ target_id: targetId }),
      });
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById("removeProfileFriendModal")).hide();
        fetchProfileData(targetId);
      } else {
        alert("Error: " + (data.error || "No se pudo eliminar"));
      }
    } catch (err) {
      alert("Error de conexión.");
    }
    btn.prop("disabled", false).text("Eliminar");
  });

});
