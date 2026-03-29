$(document).ready(function () {
  const apiBase = (window.BASE_URL || "") + "/api/php/parks.php";
  const topTypeSelect = $("#top-type");
  const topPodium = $("#top-podium");
  const topsList = $("#tops-list");
  const loadingSpinner = $("#top-loading-spinner");

  if (topTypeSelect.length) {
    // Escuchar cambios en el selector
    topTypeSelect.on("change", function () {
      const type = $(this).val();
      if (type === "global") {
        fetchGlobalTop();
      } else if (type === "users") {
        fetchUserTops(false);
      } else if (type === "friends") {
        fetchUserTops(true);
      }
    });

    // Cargar inicialmente (priorizando filtros de URL)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("filter") === "friends") {
      topTypeSelect.val("friends");
      fetchUserTops(true);
    } else {
      fetchGlobalTop();
    }
  }

  function showLoading() {
    loadingSpinner.removeClass("d-none");
    topPodium.html("");
    topsList.html(`
      <div class="col-12 text-center py-5">
          <div class="spinner-border text-success" role="status"></div>
          <p class="mt-3 text-muted">Cargando tops...</p>
      </div>
    `);
  }

  async function fetchGlobalTop() {
    showLoading();
    try {
      const res = await fetch(`${apiBase}?action=top_global`);
      const data = await res.json();

      if (data.success && data.data.length > 0) {
        renderGlobalTop(data.data);
      } else {
        topsList.html('<div class="col-12 text-center py-5 text-muted">Aún no hay suficientes valoraciones de parques para mostrar el ranking global.</div>');
      }
    } catch (e) {
      console.error(e);
      topsList.html('<div class="col-12 text-center py-5 text-danger">Error conectando con el servidor.</div>');
    } finally {
      loadingSpinner.addClass("d-none");
    }
  }

  async function fetchUserTops(isFriends = false) {
    showLoading();
    try {
      const url = isFriends ? `${apiBase}?action=user_tops&filter=friends` : `${apiBase}?action=user_tops`;
      const res = await fetch(url);
      const data = await res.json();

      if (data.success && data.data.length > 0) {
        renderUserTops(data.data, isFriends);
      } else {
        const msg = isFriends 
          ? "Tus amigos aún no han organizado su top personal de parques o no tienes amigos agregados."
          : "Ningún usuario ha creado todavía su top personal de parques.";
        topsList.html(`<div class="col-12 text-center py-5 text-muted">${msg}</div>`);
      }
    } catch (e) {
      console.error(e);
      topsList.html('<div class="col-12 text-center py-5 text-danger">Error conectando con el servidor.</div>');
    } finally {
      loadingSpinner.addClass("d-none");
    }
  }

  function renderGlobalTop(parks) {
    topPodium.html("");
    topsList.empty();

    let podiumHtml = "";
    let listHtml = "";

    parks.forEach((park, index) => {
      const pos = index + 1;
      const fallbackImg = "https://cdn.hourdetroit.com/wp-content/uploads/sites/20/2019/05/Cedar-Point-Main-4.png";
      const imgSrc = park.imagen_url ? (park.imagen_url.startsWith('/') ? BASE_URL + park.imagen_url : park.imagen_url) : fallbackImg;
      const parkUrl = BASE_URL + `/web/views/public/parks/parks.php?id=${park.id}`;

      let medalIcon = "";
      let borderClass = "";
      if (pos === 1) {
        medalIcon = '<i class="fa-solid fa-trophy text-warning fa-2x mb-2 shadow-sm rounded-circle p-2" style="background: rgba(255,193,7,0.1);"></i>';
        borderClass = "border-warning border-2";
      } else if (pos === 2) {
        medalIcon = '<i class="fa-solid fa-medal fa-2x mb-2 p-2 rounded-circle" style="color: #c0c0c0; background: rgba(192,192,192,0.1);"></i>';
        borderClass = "border-2" + ' style="border-color: #c0c0c0 !important;"';
      } else if (pos === 3) {
        medalIcon = '<i class="fa-solid fa-award fa-2x mb-2 p-2 rounded-circle" style="color: #cd7f32; background: rgba(205,127,50,0.1);"></i>';
        borderClass = "border-2" + ' style="border-color: #cd7f32 !important;"';
      }

      // Top 3 goes into podium, others into list
      if (pos <= 3) {
        podiumHtml += `
          <div class="col-12 col-md-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay: ${pos * 0.1}s">
            <a href="${parkUrl}" class="text-decoration-none">
              <div class="card h-100 bg-dark text-white shadow-lg flex-column text-center hover-scale ${borderClass}" style="transition: transform 0.3s ease;">
                <div class="position-relative overflow-hidden" style="height: 200px; border-radius: inherit; border-bottom-left-radius: 0; border-bottom-right-radius: 0;">
                  <img src="${imgSrc}" class="w-100 h-100 object-fit-cover" alt="${park.park_name}">
                  <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(0deg, rgba(33,37,41,1) 0%, rgba(33,37,41,0) 50%);"></div>
                </div>
                <div class="card-body position-relative mt-n4 z-index-1">
                  ${medalIcon}
                  <h4 class="card-title fw-bold text-success mt-2">${park.park_name}</h4>
                  <p class="card-text text-muted mb-3"><i class="fa-solid fa-location-dot me-1"></i>${park.park_location}, ${park.park_country}</p>
                  <div class="d-flex justify-content-center align-items-center gap-3">
                    <span class="fs-4 fw-bold text-white"><i class="fa-solid fa-star text-warning me-1"></i>${parseFloat(park.stars).toFixed(2)}</span>
                    <span class="text-secondary small"><i class="fa-solid fa-chart-line me-1"></i>#${pos} Global</span>
                  </div>
                </div>
              </div>
            </a>
          </div>
        `;
      } else {
        listHtml += `
          <div class="col-12 col-md-6 col-lg-4 animate__animated animate__fadeIn" style="animation-delay: ${pos * 0.05}s">
            <a href="${parkUrl}" class="text-decoration-none">
              <div class="card bg-dark text-white h-100 hover-scale shadow-sm" style="transition: transform 0.2s ease;">
                <div class="card-body d-flex align-items-center p-3">
                  <div class="fw-bold fs-2 text-success opacity-50 me-3" style="min-width: 40px;">#${pos}</div>
                  <img src="${imgSrc}" class="rounded-circle object-fit-cover me-3 shadow" style="width: 60px; height: 60px;">
                  <div class="flex-grow-1 min-w-0">
                    <h6 class="fw-bold text-truncate mb-1">${park.park_name}</h6>
                    <small class="text-muted text-truncate d-block"><i class="fa-solid fa-map-pin me-1"></i>${park.park_country}</small>
                  </div>
                  <div class="ms-2 text-end">
                    <div class="fw-bold text-warning"><i class="fa-solid fa-star me-1"></i>${parseFloat(park.stars).toFixed(2)}</div>
                  </div>
                </div>
              </div>
            </a>
          </div>
        `;
      }
    });

    topPodium.html(podiumHtml);
    if (listHtml) {
      topsList.html(listHtml);
    }
  }

  function renderUserTops(users, isFriends = false) {
    topPodium.html(""); // Hide podium for user tops
    topsList.empty();

    let html = "";
    users.forEach((user, index) => {
      let ranksHtml = "";
      
      // Render user's top parks
      user.top_parks.forEach((parkItem) => {
        const fall = "https://cdn.hourdetroit.com/wp-content/uploads/sites/20/2019/05/Cedar-Point-Main-4.png";
        const img = parkItem.imagen_url ? (parkItem.imagen_url.startsWith('/') ? BASE_URL + parkItem.imagen_url : parkItem.imagen_url) : fall;
        const pkUrl = BASE_URL + `/web/views/public/parks/parks.php?id=${parkItem.park_id}`;
        
        ranksHtml += `
          <a href="${pkUrl}" class="text-decoration-none d-block mb-3 list-group-item-action rounded p-2" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(25,135,84,0.1);">
            <div class="d-flex align-items-center">
              <div class="fw-bold text-success me-3 fs-5">#${parkItem.rank_position}</div>
              <img src="${img}" class="rounded object-fit-cover me-3 shadow-sm" style="width: 50px; height: 50px;">
              <div class="flex-grow-1 min-w-0 text-white">
                <div class="fw-semibold text-truncate" style="font-size: 0.95rem;">${parkItem.park_name}</div>
                <small class="text-muted text-truncate d-block">${parkItem.park_country || ""}</small>
              </div>
              <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
            </div>
          </a>
        `;
      });

      const avatar = user.profile_image ? user.profile_image : "https://ui-avatars.com/api/?name=" + encodeURIComponent(user.username) + "&background=198754&color=fff";
      
      html += `
        <div class="col-12 col-md-6 col-lg-4 animate__animated animate__zoomIn" style="animation-delay: ${index * 0.05}s">
          <div class="card bg-dark text-white border-secondary h-100 shadow">
            <div class="card-header bg-transparent border-bottom border-success border-opacity-25 pb-3 pt-4">
              <div class="d-flex align-items-center">
                <img src="${avatar}" class="rounded-circle shadow-sm border border-2 border-success p-1" style="width: 55px; height: 55px; object-fit: cover;">
                <div class="ms-3">
                  <h5 class="mb-0 fw-bold text-success">Top de ${user.username}</h5>
                  <small class="text-muted"><i class="fa-solid fa-map-location-dot me-1"></i>${user.top_parks.length} parque${user.top_parks.length !== 1 ? 's' : ''} rankeados</small>
                </div>
              </div>
            </div>
            <div class="card-body p-4">
              ${ranksHtml}
            </div>
          </div>
        </div>
      `;
    });

    topsList.html(html);
  }
});
