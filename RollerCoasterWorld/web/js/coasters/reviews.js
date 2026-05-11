$(document).ready(function () {
  const reviewContainer = $("#reviews-container");
  const searchInput = $("#review_search");
  const textRev = $("#text-rev");
  const paginationEl = $("#pagination");

  // Controles de Ordenación y Filtros
  const sortSelect = $("#review_sort");
  const btnSortOrder = $("#btn_sort_order");
  const iconSortOrder = $("#icon_sort_order");
  const btnFriendsOnly = $("#btn_friends_only");

  const LIMIT = 10;
  let currentPage = 1;
  let totalReviews = 0;
  let searchDebounce = null;

  searchInput.on("input", function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      currentPage = 1;
      loadReviews();
    }, 300);
  });

  sortSelect.on("change", () => {
    currentPage = 1;
    loadReviews();
  });

  btnSortOrder.on("click", function () {
    let currentOrder = $(this).attr("data-order");
    if (currentOrder === "desc") {
      $(this).attr("data-order", "asc");
      iconSortOrder
        .removeClass("fa-arrow-down-short-wide")
        .addClass("fa-arrow-up-wide-short");
    } else {
      $(this).attr("data-order", "desc");
      iconSortOrder
        .removeClass("fa-arrow-up-wide-short")
        .addClass("fa-arrow-down-short-wide");
    }
    currentPage = 1;
    loadReviews();
  });

  btnFriendsOnly.on("change", function (e) {
    if ($(this).is(":checked") && window.IS_LOGGED_IN !== true) {
      e.preventDefault();
      $(this).prop("checked", false);
      if (document.getElementById("loginModal")) {
        const m = new bootstrap.Modal(document.getElementById("loginModal"));
        m.show();
      } else {
        alert("Debes iniciar sesión para filtrar las reseñas de tus amigos.");
      }
      return;
    }
    currentPage = 1;
    loadReviews();
  });

  loadReviews();

  function loadReviews() {
    const valLength = searchInput.val().trim().length;

    if (valLength === 0) {
      textRev.text("");
      fetchReviews();
    } else if (valLength > 0 && valLength < 3) {
      textRev.text("Escribe al menos 3 caracteres para buscar.");
      reviewContainer.empty();
      paginationEl.empty();
    } else {
      textRev.text("");
      fetchReviews();
    }
  }

  function fetchReviews() {
    const searchTerm = encodeURIComponent(searchInput.val().trim());
    const sort = sortSelect.val();
    const order = btnSortOrder.attr("data-order");
    const friendsOnly = btnFriendsOnly.is(":checked");

    fetch(
      `${BASE_URL}/api/php/coasters.php?action=all_reviews&search=${searchTerm}&sort=${sort}&order=${order}&friends_only=${friendsOnly}&page=${currentPage}`,
    )
      .then((response) => response.json())
      .then((data) => {
        reviewContainer.empty();
        if (data.success) {
          totalReviews = data.total ?? 0;
          if (data.reviews.length === 0) {
            textRev.text("No se han encontrado reseñas.");
            paginationEl.empty();
          } else {
            data.reviews.forEach(function (review) {
              reviewContainer.append(createReviewCard(review));
            });
            renderPagination();
          }
        } else {
          textRev.text("Error al cargar las reseñas.");
          paginationEl.empty();
        }
      })
      .catch(() => {
        textRev.text("Error de conexión.");
        paginationEl.empty();
      });
  }

  function renderPagination() {
    paginationEl.empty();
    const totalPages = Math.ceil(totalReviews / LIMIT);
    if (totalPages <= 1) return;

    const ul = $('<ul class="pagination mb-0"></ul>');

    // Botón Anterior
    const prevDisabled = currentPage === 1 ? "disabled" : "";
    ul.append(`
      <li class="page-item ${prevDisabled}">
        <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Anterior">
          <i class="fa-solid fa-chevron-left"></i>
        </a>
      </li>
    `);

    // Páginas con ventana deslizante
    const delta = 2;
    const left = Math.max(1, currentPage - delta);
    const right = Math.min(totalPages, currentPage + delta);

    if (left > 1) {
      ul.append(
        `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`,
      );
      if (left > 2)
        ul.append(
          `<li class="page-item disabled"><span class="page-link">…</span></li>`,
        );
    }

    for (let p = left; p <= right; p++) {
      const active = p === currentPage ? "active" : "";
      ul.append(
        `<li class="page-item ${active}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`,
      );
    }

    if (right < totalPages) {
      if (right < totalPages - 1)
        ul.append(
          `<li class="page-item disabled"><span class="page-link">…</span></li>`,
        );
      ul.append(
        `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`,
      );
    }

    // Botón Siguiente
    const nextDisabled = currentPage === totalPages ? "disabled" : "";
    ul.append(`
      <li class="page-item ${nextDisabled}">
        <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Siguiente">
          <i class="fa-solid fa-chevron-right"></i>
        </a>
      </li>
    `);

    // Info de resultados
    const start = (currentPage - 1) * LIMIT + 1;
    const end = Math.min(currentPage * LIMIT, totalReviews);
    const info =
      $(`<span class="text-secondary ms-3 align-self-center" style="font-size:0.85rem;">
      Mostrando ${start}–${end} de ${totalReviews} reseñas
    </span>`);

    paginationEl.append(ul).append(info);

    // Eventos de paginación
    paginationEl.find("a.page-link[data-page]").on("click", function (e) {
      e.preventDefault();
      const page = parseInt($(this).data("page"));
      if (isNaN(page) || page < 1 || page > totalPages) return;
      currentPage = page;
      fetchReviews();
      $("html, body").animate(
        { scrollTop: reviewContainer.offset().top - 80 },
        300,
      );
    });
  }

  function createReviewCard(review) {
    const full = Math.floor(review.note);
    const half = review.note % 1 >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;

    let starsHtml = "";
    for (let i = 0; i < full; i++)
      starsHtml += '<i class="fa-solid fa-star text-warning"></i>';
    if (half)
      starsHtml += '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
    for (let i = 0; i < empty; i++)
      starsHtml += '<i class="fa-regular fa-star text-warning"></i>';

    const date = new Date(review.created_at).toLocaleDateString("es-ES", {
      day: "numeric",
      month: "long",
      year: "numeric",
    });

    const avatarPath =
      typeof window.rcwGetAvatarPath === "function"
        ? window.rcwGetAvatarPath(review.profile_image, review.username)
        : review.profile_image || `${BASE_URL}/web/img/avatars/default.png`;

    const coasterPhoto = review.imagen_url
      ? review.imagen_url
      : `${BASE_URL}/web/img/rc_placeholder.webp`;

    let tagsHtml = "";
    if (review.tags && review.tags.length > 0) {
      tagsHtml = '<div class="d-flex flex-wrap gap-2 mt-2 mb-2">';
      review.tags.forEach((t) => {
        const bgColor = t.type === "pro" ? "#05c46b" : "#ff3f34";
        tagsHtml += `<span class="badge text-white rounded-pill px-3 py-1" style="background-color:${bgColor}; font-weight:600; font-size:0.75rem;">${t.tag.replace(/_/g, " ").toUpperCase()}</span>`;
      });
      tagsHtml += "</div>";
    }
    const reviewText = review.review
      ? `"${review.review}"`
      : '<span class="fst-italic text-secondary">El usuario dejó una puntuación sin reseña escrita.</span>';

    const profileUrl = `${BASE_URL}/web/views/public/users/user_profile.php?id=${review.user_id}`;
    const coasterUrl = `${BASE_URL}/web/views/public/coasters/coasters.php?id=${review.coaster_id}`;

    return `
      <div class="list-group-item p-4 mb-3 rounded-0 shadow border border-secondary border-opacity-25" style="background-color: #1a1e23;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3 gap-3">
          
          <!-- Bloque de Usuario y Coaster Central -->
          <div class="d-flex align-items-center gap-3 flex-grow-1">
            <a href="${profileUrl}" title="Ver perfil de ${review.username}" style="flex-shrink:0;">
              <img src="${avatarPath}" alt="${review.username}" class="rounded-circle shadow-sm" style="width: 55px; height: 55px; object-fit: cover; border: 2px solid var(--theme-color); transition: opacity 0.2s; background:#2d333b;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'" onerror="this.src='${window.BASE_URL}/web/img/avatars/default_avatar.svg';this.onerror=null;">
            </a>
            <div class="d-flex flex-column min-w-0" style="flex: 1;">
              <h6 class="mb-1 fw-bold d-flex align-items-center flex-wrap gap-2 text-white" style="font-size: 1.1rem; min-width: 0;">
                <a href="${profileUrl}" class="text-white text-decoration-none hover-underline text-truncate" style="max-width: 250px;">${review.username}</a>
                <span class="badge bg-success shadow-none border border-success border-opacity-50 text-white text-truncate" style="font-size: 0.7rem; letter-spacing: 0.5px; max-width: 200px;">
                  <i class="fa-solid fa-map-pin me-1"></i>${review.park_name}
                </span>
              </h6>
              <div class="d-flex align-items-center mt-1">
                 <a href="${coasterUrl}" title="Ver ${review.coaster_name}">
                   <img src="${coasterPhoto}" alt="Foto de ${review.coaster_name}" class="rounded me-2 shadow-sm border border-secondary border-opacity-25" style="width: 40px; height: 40px; object-fit: cover;">
                 </a>
                 <small class="text-secondary d-flex flex-column justify-content-center">
                   <span>
                     <i class="fa-solid fa-roller-coaster me-1 opacity-75"></i> En 
                     <a href="${coasterUrl}" class="text-white text-decoration-none fw-bold hover-underline">${review.coaster_name}</a> 
                   </span>
                   <span class="opacity-100"><i class="fa-regular fa-clock me-1 mt-1"></i> ${date}</span>
                 </small>
              </div>
            </div>
          </div>

          <!-- Estrellas y Puntuación -->
          <div class="text-start text-md-end bg-dark px-3 py-2 rounded-0 shadow-sm border border-secondary border-opacity-25" style="min-width: 130px;">
            <div class="fs-6 lh-1 mb-1">${starsHtml}</div>
            <strong class="text-white" style="font-size: 1.1rem;">${parseFloat(review.note).toFixed(1)} <span class="text-secondary fs-6">/ 5</span></strong>
          </div>
        </div>
        
        <!-- Texto de la reseña -->
        ${tagsHtml}
        <div class="mt-3 p-3 bg-dark bg-opacity-25 rounded border-start border-3 border-success">
            <p class="mb-0 text-white-50" style="line-height: 1.7; font-size: 0.95rem;">
              ${reviewText}
            </p>
        </div>
      </div>
    `;
  }
});
