$(document).ready(function () {
  const reviewContainer = $("#reviews-container");
  const searchInput = $("#review_search");
  const textRev = $("#text-rev");
  
  // Controles de Ordenación y Filtros
  const sortSelect = $("#review_sort");
  const btnSortOrder = $("#btn_sort_order");
  const iconSortOrder = $("#icon_sort_order");
  const btnFriendsOnly = $("#btn_friends_only");

  let searchDebounce = null;

  searchInput.on("input", function () {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(loadReviews, 300);
  });

  sortSelect.on("change", loadReviews);

  btnSortOrder.on("click", function () {
    let currentOrder = $(this).attr("data-order");
    if (currentOrder === "desc") {
      $(this).attr("data-order", "asc");
      iconSortOrder.removeClass("fa-arrow-down-short-wide").addClass("fa-arrow-up-wide-short");
    } else {
      $(this).attr("data-order", "desc");
      iconSortOrder.removeClass("fa-arrow-up-wide-short").addClass("fa-arrow-down-short-wide");
    }
    loadReviews();
  });

  btnFriendsOnly.on("change", function(e) {
    if ($(this).is(":checked") && window.IS_LOGGED_IN !== true) {
      e.preventDefault();
      $(this).prop("checked", false);
      if (document.getElementById('loginModal')) {
         const m = new bootstrap.Modal(document.getElementById('loginModal'));
         m.show();
      } else {
         alert("Debes iniciar sesión para filtrar las reseñas de tus amigos.");
      }
      return;
    }
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
      `${BASE_URL}/api/php/coasters.php?action=all_reviews&search=${searchTerm}&sort=${sort}&order=${order}&friends_only=${friendsOnly}`,
    )
      .then((response) => response.json())
      .then((data) => {
        reviewContainer.empty();
        if (data.success) {
          if (data.reviews.length === 0) {
            textRev.text("No se han encontrado reseñas.");
          } else {
            data.reviews.forEach(function (review) {
              reviewContainer.append(createReviewCard(review));
            });
          }
        } else {
          textRev.text("Error al cargar las reseñas.");
        }
      })
      .catch((error) => {
        textRev.text("Error de conexión.");
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

    // Fallback de avatar en caso de que rcwGetAvatarPath no esté accesible
    const avatarPath =
      typeof window.rcwGetAvatarPath === "function"
        ? window.rcwGetAvatarPath(review.profile_image, review.username)
        : review.profile_image || `${BASE_URL}/web/img/avatars/default.png`;

    const coasterPhoto = review.imagen_url
      ? review.imagen_url
      : `${BASE_URL}/web/img/rc_placeholder.webp`;

    const reviewText = review.review
      ? `"${review.review}"`
      : '<span class="fst-italic text-secondary">El usuario dejó una puntuación sin reseña escrita.</span>';

    return `
      <div class="list-group-item p-4 mb-3 rounded-0 shadow border border-secondary border-opacity-25" style="background-color: #1a1e23;">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3 gap-3">
          
          <!-- Bloque de Usuario y Coaster Central -->
          <div class="d-flex align-items-center gap-3 flex-grow-1">
            <img src="${avatarPath}" alt="${review.username}" class="rounded-circle shadow-sm" style="width: 55px; height: 55px; object-fit: cover; border: 2px solid var(--theme-color);">
            <div class="d-flex flex-column">
              <h6 class="mb-1 fw-bold d-flex align-items-center gap-2 text-white" style="font-size: 1.1rem;">
                ${review.username}
                <span class="badge bg-success shadow-none border border-success border-opacity-50 text-white" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                  <i class="fa-solid fa-map-pin me-1"></i>${review.park_name}
                </span>
              </h6>
              <div class="d-flex align-items-center mt-1">
                 <img src="${coasterPhoto}" alt="Foto de ${review.coaster_name}" class="rounded me-2 shadow-sm border border-secondary border-opacity-25" style="width: 40px; height: 40px; object-fit: cover;">
                 <small class="text-secondary d-flex flex-column justify-content-center">
                   <span>
                     <i class="fa-solid fa-roller-coaster me-1 opacity-75"></i> En 
                     <a href="${BASE_URL}/web/views/public/coasters/coasters.php?id=${review.coaster_id}" class="text-white text-decoration-none fw-bold hover-underline">${review.coaster_name}</a> 
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
        <div class="mt-3 p-3 bg-dark bg-opacity-25 rounded border-start border-3 border-success">
            <p class="mb-0 text-white-50" style="line-height: 1.7; font-size: 0.95rem;">
              ${reviewText}
            </p>
        </div>
      </div>
    `;
  }
});
