$(document).ready(function () {
  $("#coaster-search").on("keyup", function () {
    const search = $(this).val();
    if (search.length >= 3) {
      $.ajax({
        url: BASE_URL + "/api/php/coasters.php",
        type: "GET",
        data: {
          action: "search",
          search: search,
        },
        success: function (data) {
          let html = "";
          if (data.length > 0) {
            data.forEach(function (coaster) {
              html += `
              <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-0 fw-bold">${coaster.coaster_name}</h6>
                  <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>${coaster.park_name}</small>
                </div>
                <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
              </a>`;
            });
            // Añadir botón de "Ver todos los resultados"
            html += `
            <a href="#" class="list-group-item list-group-item-action text-center text-primary fw-bold" id="view-all-results">
              Ver todos los resultados para "${search}" <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>`;
          } else {
            html = `<div class="list-group-item text-muted text-center py-3">No se encontraron montañas rusas.</div>`;
          }

          $("#search-results").html(html).show();
        },
      });
    } else {
      $("#search-results").html("").hide();
    }
  });

  let currentPage = 1;
  let currentSearchQuery = "";
  let isFiltering = false;

  function loadCoasters(page) {
    currentPage = page;

    let actionName = isFiltering ? "filter" : "list";
    let ajaxData = {
      action: actionName,
      page: page,
    };

    if (isFiltering) {
      ajaxData.search = currentSearchQuery;
    }

    $.ajax({
      url: BASE_URL + "/api/php/coasters.php",
      type: "GET",
      data: ajaxData,

      success: function (data) {
        if (data.success) {
          displayCoasters(data.coasters);
          displayPagination(data.total, page);
        } else {
          document.getElementById("coaster-list").innerHTML =
            "<p style='color: red;'>Error cargando montañas rusas</p>";
        }
      },
    });
  }

  function displayCoasters(coasters) {
    const container = document.querySelector("#coaster-list");
    container.innerHTML = "";
    coasters.forEach(function (coaster) {
      const coasterCard = document.createElement("a");
      coasterCard.href =
        BASE_URL + `/web/views/public/coaster_detail.php?id=${coaster.id}`;
      coasterCard.classList.add(
        "list-group-item",
        "list-group-item-action",
        "d-flex",
        "align-items-center",
        "p-3",
      );

      const img = coaster.imagen_url
        ? `<img src="${coaster.imagen_url}" alt="${coaster.coaster_name}" class="rounded shadow-sm" style="width: 100px; height: 100px; object-fit: cover; margin-right: 20px;">`
        : `<img src="https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg" alt="Sin imagen" class="rounded shadow-sm" style="width: 100px; height: 100px; object-fit: cover; margin-right: 20px;">`;

      const manufacturer = coaster.manufacturer || "Desconocido";
      const modelo = coaster.modelo || "Desconocido";
      const year = coaster.opening_year || "N/A";

      coasterCard.innerHTML = `
        ${img}
        <div class="flex-grow-1">
          <h5 class="mb-1 fw-bold text-primary" style="font-size: 1.25rem;">${coaster.coaster_name}</h5>
          <p class="mb-1 text-muted"><i class="fa-solid fa-map-pin me-1"></i>${coaster.park_name}</p>
          <small class="text-secondary">${manufacturer} • ${modelo} • ${year}</small>
        </div>
        <i class="fa-solid fa-chevron-right text-muted ms-3"></i>
      `;
      container.appendChild(coasterCard);
    });
  }

  function displayPagination(total, page) {
    const pagination = document.querySelector(".pagination");
    pagination.innerHTML = "";
    const pageBtn = document.createElement("div");
    pageBtn.classList.add("page-buttons");
    const totalPages = Math.ceil(total / 15);

    const prevBtn = document.createElement("button");
    prevBtn.className = "btn btn-outline-success mx-1";
    prevBtn.textContent = "«";
    if (page === 1) {
      prevBtn.disabled = true;
    }
    prevBtn.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(page - 1);
    });
    pageBtn.appendChild(prevBtn);

    const btnFirst = document.createElement("button");
    btnFirst.className = "btn btn-success mx-1";
    btnFirst.textContent = "1";
    btnFirst.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(1);
    });
    pageBtn.appendChild(btnFirst);

    const btnDots = document.createElement("button");
    btnDots.className = "btn border-0 text-secondary mx-1";
    btnDots.textContent = "...";
    btnDots.disabled = true;
    pageBtn.appendChild(btnDots);

    let start = Math.max(2, page - 1);
    let end = Math.min(totalPages - 1, start + 2);
    start = Math.max(1, end - 2);

    for (let i = start; i <= end; i++) {
      const pageButton = document.createElement("button");
      pageButton.className = "btn btn-light text-success border mx-1";
      pageButton.textContent = i;
      if (i === page) {
        pageButton.classList.remove("btn-light", "text-success", "border");
        pageButton.classList.add("btn-success", "text-white");
      }
      pageButton.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadCoasters(i);
      });
      pageBtn.appendChild(pageButton);
    }

    const btnDots2 = document.createElement("button");
    btnDots2.className = "btn border-0 text-secondary mx-1";
    btnDots2.textContent = "...";
    btnDots2.disabled = true;
    pageBtn.appendChild(btnDots2);

    const btnLast = document.createElement("button");
    btnLast.className = "btn btn-success mx-1";
    btnLast.textContent = `${totalPages}`;
    btnLast.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(totalPages);
    });
    pageBtn.appendChild(btnLast);

    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-outline-success mx-1";
    nextBtn.textContent = "»";
    if (page === totalPages) {
      nextBtn.disabled = true;
    }
    nextBtn.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadCoasters(page + 1);
    });
    pageBtn.appendChild(nextBtn);
    pagination.appendChild(pageBtn);
  }

  loadCoasters(1);

  // --- ICONO DE BUSQUEDA X ---
  const searchInput = $("#coaster-search");
  const searchIcon = $("#search-icon");

  searchInput.on("input", function () {
    if ($(this).val().length > 0) {
      searchIcon
        .removeClass("fa-magnifying-glass text-muted")
        .addClass("fa-xmark text-danger")
        .css("cursor", "pointer");
    } else {
      searchIcon
        .removeClass("fa-xmark text-danger")
        .addClass("fa-magnifying-glass text-muted")
        .css("cursor", "text");
    }
  });

  searchIcon.on("click", function () {
    if ($(this).hasClass("fa-xmark")) {
      searchInput.val("").focus();
      $("#search-results").html("").hide();
      $(this)
        .removeClass("fa-xmark text-danger")
        .addClass("fa-magnifying-glass text-muted")
        .css("cursor", "text");
      isFiltering = false;
      currentSearchQuery = "";
      $("h1").text("Buscar Montañas Rusas");
      loadCoasters(1);
    }
  });

  // 1. Manejador para el botón azul de "Ver todos los resultados"
  $(document).on("click", "#view-all-results", function (e) {
    e.preventDefault();
    const search = $("#coaster-search").val();

    currentSearchQuery = $("#coaster-search").val();
    isFiltering = true;

    $("#search-results").html("").hide();
    $("h1").text('Resultados para: "' + currentSearchQuery + '"');
    loadCoasters(1);
  });

  // 2. Ocultar resultados si se hace click fuera (en el fondo blanco de tu web, fuera del buscador)
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#coaster-search, #search-results").length) {
      $("#search-results").hide();
    }
  });

  // 3. Mostrar resultados si vuelves a hacer click en el input del buscador
  searchInput.on("focus", function () {
    if (
      $(this).val().length >= 3 &&
      $("#search-results").children().length > 0
    ) {
      $("#search-results").show();
    }
  });
});
