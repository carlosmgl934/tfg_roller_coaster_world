$(document).ready(function () {
  const apiBase = (window.BASE_URL || "") + "/api/php/parks.php";

  const resultsContainer = $("#park-list");
  const countContainer = $("#park-count");
  const paginationContainer = $("#park-pagination");

  // --- LÓGICA DE LISTADO DE PARQUES (Sólo si el contenedor existe) ---
  if (resultsContainer.length) {
    // --- Range Slider Badges ---
    window.addEventListener("pageshow", function () {
      const yearMin = document.getElementById("opening-year-min");
      const coastMin = document.getElementById("num-coaster-min");
      const rateFilt = document.getElementById("rating-filter");

      if (yearMin) yearMin.value = 1800;
      if (coastMin) coastMin.value = 0;
      if (rateFilt) rateFilt.value = 0;

      const yVal = document.getElementById("year-val");
      const cVal = document.getElementById("coasters-val");
      const rVal = document.getElementById("rating-val");

      if (yVal) yVal.textContent = "1800";
      if (cVal) cVal.textContent = "0";
      if (rVal) rVal.textContent = "0★";
    });

    const yearMinInput = document.getElementById("opening-year-min");
    if (yearMinInput) {
      yearMinInput.addEventListener("input", function () {
        document.getElementById("year-val").textContent = this.value;
      });
    }

    const coasterMinInput = document.getElementById("num-coaster-min");
    if (coasterMinInput) {
      coasterMinInput.addEventListener("input", function () {
        document.getElementById("coasters-val").textContent = this.value;
      });
    }

    const ratingFiltInput = document.getElementById("rating-filter");
    if (ratingFiltInput) {
      ratingFiltInput.addEventListener("input", function () {
        document.getElementById("rating-val").textContent = this.value + "★";
      });
    }

    // Restaurar valores iniciales
    if (yearMinInput && document.getElementById("year-val"))
      document.getElementById("year-val").textContent = yearMinInput.value;
    if (coasterMinInput && document.getElementById("coasters-val"))
      document.getElementById("coasters-val").textContent =
        coasterMinInput.value;
    if (ratingFiltInput && document.getElementById("rating-val"))
      document.getElementById("rating-val").textContent =
        ratingFiltInput.value + "★";

    // Botón Filtrar
    const btnFiltrar = document.getElementById("btn-filtrar");
    if (btnFiltrar) {
      btnFiltrar.addEventListener("click", function () {
        loadParks(getFilters(), 1);
      });
    }

    // Listener para el selector de ordenación
    const sortFilter = document.getElementById("sort-filter");
    const sortDirectionBtn = document.getElementById("sort-direction-btn");
    const sortDirectionInput = document.getElementById("sort-direction");

    if (sortFilter) {
      sortFilter.addEventListener("change", function () {
        const val = this.value;
        let defaultDir = "ASC";
        if (["stars", "coasters"].includes(val)) defaultDir = "DESC";

        sortDirectionInput.value = defaultDir;
        updateSortIcon(defaultDir);
        loadParks(getFilters(), 1);
      });
    }

    if (sortDirectionBtn && sortDirectionInput) {
      sortDirectionBtn.addEventListener("click", function () {
        const currentDir = sortDirectionInput.value;
        const newDir = currentDir === "ASC" ? "DESC" : "ASC";
        sortDirectionInput.value = newDir;
        updateSortIcon(newDir);
        loadParks(getFilters(), 1);
      });
    }

    function updateSortIcon(dir) {
      if (!sortDirectionBtn) return;
      const icon = sortDirectionBtn.querySelector("i");
      if (!icon) return;
      icon.className =
        dir === "ASC"
          ? "fa-solid fa-arrow-up-wide-short"
          : "fa-solid fa-arrow-down-wide-short";
    }

    let currentPage = 1;
    const itemsPerPage = 15;

    function loadParks(params = {}, page = 1) {
      currentPage = page;
      resultsContainer.html(`
        <div class="col-12 text-center py-5">
          <div class="spinner-border text-success" role="status"></div>
          <p class="mt-3 text-muted">Cargando parques...</p>
        </div>
      `);

      const url = new URL(apiBase, window.location.origin);
      url.searchParams.append("action", "list");
      url.searchParams.append("page", currentPage);
      if (params.q) url.searchParams.append("q", params.q);
      if (params.country && params.country !== "Todos")
        url.searchParams.append("country", params.country);
      if (params.location) url.searchParams.append("location", params.location);
      if (params.min_year)
        url.searchParams.append("opening_year_min", params.min_year);
      if (params.max_year)
        url.searchParams.append("opening_year_max", params.max_year);
      if (params.min_coasters)
        url.searchParams.append("min_coasters", params.min_coasters);
      if (params.max_coasters)
        url.searchParams.append("max_coasters", params.max_coasters);
      if (params.min_rating && params.min_rating !== "Todos")
        url.searchParams.append("min_stars", params.min_rating);
      if (params.sort) url.searchParams.append("sort", params.sort);
      if (params.order_dir)
        url.searchParams.append("order_dir", params.order_dir);

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          resultsContainer.empty();
          let total = data.total || 0;
          if (countContainer.length)
            countContainer.text(
              `Mostrando ${total} parque${total !== 1 ? "s" : ""}`,
            );

          let parks = [];
          if (Array.isArray(data)) parks = data;
          else if (data && Array.isArray(data.data)) parks = data.data;

          if (parks.length === 0) {
            resultsContainer.html(
              '<p class="text-center text-muted py-5">No se encontraron parques</p>',
            );
            if (paginationContainer.length) paginationContainer.empty();
            return;
          }

          let html = "";
          parks.forEach((park) => {
            const fallbackImg =
              "https://cdn.hourdetroit.com/wp-content/uploads/sites/20/2019/05/Cedar-Point-Main-4.png";
            const imgSrc = park.imagen_url
              ? park.imagen_url.startsWith("/")
                ? (window.BASE_URL || "") + park.imagen_url
                : park.imagen_url
              : fallbackImg;
            html += `
              <a href="${window.BASE_URL || ""}/web/views/public/parks/parks.php?id=${park.id}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                <img src="${imgSrc}" class="rounded-0 shadow-sm object-fit-cover me-3" style="width:110px; height:110px; flex-shrink:0;">
                <div class="flex-grow-1">
                  <h5 class="mb-1 fw-bold text-success">${park.park_name || "Sin nombre"}</h5>
                  <p class="mb-1 text-muted"><i class="fa-solid fa-map-pin me-1"></i>${park.park_location || "N/A"}, ${park.park_country || ""}</p>
                  <small class="text-secondary">${park.opening_year || "N/A"} • ${park.operating_coasters || 0} montañas rusas • ${park.stars || "0.00"} ★</small>
                </div>
                <i class="fa-solid fa-chevron-right text-muted"></i>
              </a>`;
          });
          resultsContainer.html(html);
          renderPagination(total);
        });
    }

    function getFilters() {
      const minYear = parseInt($("#opening-year-min").val()) || 0;
      const minCoast = parseInt($("#num-coaster-min").val()) || 0;
      const minRating = parseFloat($("#rating-filter").val()) || 0;
      return {
        q: isFiltering ? $("#park-search").val().trim() : "",
        country: $("#country-filter").val(),
        location: $("#location-filter").val(),
        min_year: minYear > 1800 ? minYear : "",
        min_coasters: minCoast > 0 ? minCoast : "",
        min_rating: minRating > 0 ? minRating : "",
        sort: $("#sort-filter").val() || "name",
        order_dir: $("#sort-direction").val() || "DESC",
      };
    }

    function renderPagination(total) {
      if (!paginationContainer.length) return;
      paginationContainer.empty();
      const totalPages = Math.ceil(total / itemsPerPage);
      if (totalPages <= 1) {
        return;
      }
      const pageBtn = document.createElement("div");
      pageBtn.classList.add(
        "page-buttons",
        "d-flex",
        "flex-nowrap",
        "justify-content-center",
        "gap-1",
      );
      const prevBtn = document.createElement("button");
      prevBtn.className = "btn btn-outline-success mx-1";
      prevBtn.textContent = "«";
      if (currentPage === 1) prevBtn.disabled = true;
      prevBtn.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadParks(getFilters(), currentPage - 1);
      });
      pageBtn.appendChild(prevBtn);

      const btnFirst = document.createElement("button");
      if (currentPage === 1) {
        btnFirst.className = "btn btn-success text-white";
      } else {
        btnFirst.className = "btn btn-light text-success border";
      }
      btnFirst.textContent = "1";
      btnFirst.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadParks(getFilters(), 1);
      });
      pageBtn.appendChild(btnFirst);

      const btnDots = document.createElement("button");
      btnDots.className = "btn border-0 text-secondary";
      btnDots.textContent = "...";
      btnDots.disabled = true;
      pageBtn.appendChild(btnDots);

      let start = Math.max(2, currentPage - 1);
      let end = Math.min(totalPages - 1, start + 2);
      start = Math.max(2, end - 2);

      for (let i = start; i <= end; i++) {
        const pageButton = document.createElement("button");
        pageButton.className = "btn btn-light text-success border";
        pageButton.textContent = i;
        if (i === currentPage) {
          pageButton.classList.remove("btn-light", "text-success", "border");
          pageButton.classList.add("btn-success", "text-white");
        }
        pageButton.addEventListener("click", function () {
          window.scrollTo({ top: 10, behavior: "smooth" });
          loadParks(getFilters(), i);
        });
        pageBtn.appendChild(pageButton);
      }

      const btnDots2 = document.createElement("button");
      btnDots2.className = "btn border-0 text-secondary";
      btnDots2.textContent = "...";
      btnDots2.disabled = true;
      pageBtn.appendChild(btnDots2);

      const btnLast = document.createElement("button");
      if (currentPage === totalPages) {
        btnLast.className = "btn btn-success text-white";
      } else {
        btnLast.className = "btn btn-light text-success border";
      }
      btnLast.textContent = `${totalPages}`;
      btnLast.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadParks(getFilters(), totalPages);
      });
      pageBtn.appendChild(btnLast);

      const nextBtn = document.createElement("button");
      nextBtn.className = "btn btn-outline-success";
      nextBtn.textContent = "»";
      if (currentPage === totalPages) nextBtn.disabled = true;
      nextBtn.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadParks(getFilters(), currentPage + 1);
      });
      pageBtn.appendChild(nextBtn);

      paginationContainer.append(pageBtn);
    }

    async function loadFilters() {
      try {
        const url = new URL(apiBase, window.location.origin);
        url.searchParams.append("action", "country");
        const res = await fetch(url);
        const data = await res.json();
        const countries = data.data || data;
        if (Array.isArray(countries)) {
          countries.forEach((c) => {
            if (c) $("#country-filter").append(new Option(c, c));
          });
        }
      } catch (e) { }
    }

    let isFiltering = false;
    loadFilters();
    loadParks(getFilters());

    // Búsqueda autocomplete
    let searchDebounce = null;
    const searchInput = $("#park-search");
    searchInput.on("keyup", function () {
      const search = this.value.trim();
      clearTimeout(searchDebounce);
      if (search.length < 3) {
        $("#search-results").hide();
        return;
      }
      searchDebounce = setTimeout(async () => {
        const url = new URL(apiBase, window.location.origin);
        url.searchParams.append("action", "list");
        url.searchParams.append("q", search);
        url.searchParams.append("limit", "10"); // Pedimos algunos más para saber si hay más de 5

        const res = await fetch(url);
        const data = await res.json();
        const parksData = data.data || [];
        let html = "";
        const MAX_PREVIEW = 5;

        if (parksData.length === 0) {
          html = `<div class="list-group-item text-muted text-center py-3">No se encontraron parques para "${search}".</div>`;
        } else {
          parksData.slice(0, MAX_PREVIEW).forEach((p) => {
            html += `
              <a href="${window.BASE_URL || ""}/web/views/public/parks/parks.php?id=${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-0 fw-bold">${p.park_name}</h6>
                  <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>${p.park_location || ""}${p.park_location && p.park_country ? ", " : ""}${p.park_country || ""}</small>
                </div>
                <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
              </a>`;
          });

          html += `
            <a href="#" class="list-group-item list-group-item-action text-center text-primary fw-bold" id="view-all-park-results">
              Ver todos los resultados para "${search}" <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>`;
        }

        $("#search-results").html(html).show();
      }, 300);
    });

    // Keyboard navigation
    searchInput.on("keydown", function (e) {
      const $dropdown = $("#search-results");
      if (!$dropdown.is(":visible")) return;

      const $items = $dropdown.find("a.list-group-item");
      if (!$items.length) return;

      let index = $items.index($items.filter(".active"));

      if (e.key === "ArrowDown") {
        e.preventDefault();
        index = (index + 1) % $items.length;
        $items.removeClass("bg-secondary border-success active text-white");
        $items
          .eq(index)
          .addClass("bg-secondary border-success active text-white");
        $items.eq(index)[0].scrollIntoView({ block: "nearest" });
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        index = index - 1 < 0 ? $items.length - 1 : index - 1;
        $items.removeClass("bg-secondary border-success active text-white");
        $items
          .eq(index)
          .addClass("bg-secondary border-success active text-white");
        $items.eq(index)[0].scrollIntoView({ block: "nearest" });
      } else if (e.key === "Enter") {
        e.preventDefault();
        if (index >= 0) {
          $items.eq(index)[0].click();
        } else {
          $items.first()[0].click();
        }
      }
    });

    $("#clear-filters").click(function () {
      $("#country-filter, #location-filter").val("");
      $("#opening-year-min").val(1800);
      $("#num-coaster-min").val(0);
      $("#rating-filter").val(0);
      $("#year-val").text("1800");
      $("#coasters-val").text("0");
      $("#rating-val").text("0★");
      isFiltering = false;
      $("h1").text("Buscar Parques");
      loadParks(getFilters(), 1);
    });

    // "Ver todos los resultados" para parques
    $(document).on("click", "#view-all-park-results", function (e) {
      e.preventDefault();
      isFiltering = true;
      $("#search-results").html("").hide();
      const q = $("#park-search").val().trim();
      $("h1").text('Resultados para: "' + q + '"');
      loadParks(getFilters(), 1);
    });

    // Ocultar dropdown al hacer click fuera
    $(document).on("click", function (e) {
      if (!$(e.target).closest("#park-search, #search-results").length) {
        $("#search-results").hide();
      }
    });

    // Reabrir dropdown al enfocar el input
    searchInput.on("focus", function () {
      if (
        $(this).val().length >= 3 &&
        $("#search-results").children().length > 0
      ) {
        $("#search-results").show();
      }
    });

    // Icono de búsqueda ↔ X
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
        $("h1").text("Base de datos de parques de atracciones");
        loadParks(getFilters(), 1);
      }
    });
  }

  // ==========================================
  // LÓGICA DE PARK_DETAIL.PHP
  // ==========================================
  if (document.getElementById("park-name")) {
    const urlParams = new URLSearchParams(window.location.search);
    const parkId = urlParams.get("id");

    async function loadParkData(id) {
      try {
        const res = await fetch(`${apiBase}?action=details&id=${id}`);
        const data = await res.json();

        if (data.success) {
          const park = data;

          // Hero
          $("#park-name").text(park.park_name);
          $("#park-location-header").text(park.park_location);
          $("#park-country-header").text(park.park_country);

          if (park.imagen_url) {
            const heroUrl = park.imagen_url.startsWith("/")
              ? (window.BASE_URL || "") + park.imagen_url
              : park.imagen_url;
            $("#park-hero-img").attr("src", heroUrl);
          } else {
            $("#park-hero-img").attr(
              "src",
              "https://cdn.hourdetroit.com/wp-content/uploads/sites/20/2019/05/Cedar-Point-Main-4.png",
            );
          }

          // 2x2 Stats (Coaster style)
          $("#global-ranking").text(park.ranking ? "#" + park.ranking : "—");
          $("#park-score").text(
            park.stars ? parseFloat(park.stars).toFixed(2) : "0.00",
          );
          $("#stat-num-coasters").text(park.num_coasters || "0");
          $("#current-state").text("Abierto").addClass("text-success"); // Default

          // Estadísticas Rápidas
          $("#opening-year-val").text(park.opening_year || "—");
          $("#operating-coasters-val").text(park.operating_coasters || "0");
          $("#reviews-count-val").text(park.reviews_count || "0");
          $("#reviews-header-count").text(park.reviews_count || "0");
          $("#entry-price-val").text(
            park.precio_entrada ? park.precio_entrada + "€" : "S/D",
          );

          // Ficha Técnica Table
          $("#park-location-table").text(park.park_location || "—");
          $("#park-country-table").text(park.park_country || "—");
          $("#park-year-table").text(park.opening_year || "—");
          $("#park-price-table").text(
            park.precio_entrada ? park.precio_entrada + "€" : "S/D",
          );

          // Botones
          if (park.website) {
            $("#btn-website")
              .attr("href", park.website)
              .attr("target", "_blank")
              .removeClass("disabled")
              .show();
          } else {
            $("#btn-website").addClass("disabled");
          }

          // Botón comprar entradas (solo si tiene precio)
          if (park.precio_entrada && parseFloat(park.precio_entrada) > 0) {
            const ticketsUrl =
              (window.BASE_URL || "") +
              "/web/views/public/shop/tickets.php?park_id=" +
              park.id;
            const btnBuy = $(`
              <a id="btn-buy-tickets" href="${ticketsUrl}"
                 class="btn btn-success fw-bold d-flex align-items-center justify-content-center gap-2 w-100"
                 style="border-radius:0;padding:10px;">
                <i class="fa-solid fa-ticket fs-5"></i>
                <span>Comprar Entradas</span>
                <span class="badge bg-dark ms-1" style="font-size:.75rem;">${parseFloat(park.precio_entrada).toFixed(2)}€</span>
              </a>`);
            // Insertar al inicio del contenedor, ocupa toda la línea (w-100)
            $("#park-action-buttons").prepend(btnBuy);
          }

          if (park.park_location) {
            $("#btn-map")
              .off("click")
              .on("click", function () {
                window.open(
                  `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(park.park_name + " " + park.park_location)}`,
                  "_blank",
                );
              });
          }
        } else {
          $("#park-name").text("Error: Parque no encontrado");
        }
      } catch (e) {
        console.error("Error cargando detalles del parque:", e);
        $("#park-name").text("Error de conexión");
      }
    }

    async function loadParkCoasters(id) {
      const coasterApi = (window.BASE_URL || "") + "/api/php/coasters.php";
      try {
        const res = await fetch(
          `${coasterApi}?action=apply_filters&park_id=${id}&limit=100`,
        );
        const data = await res.json();

        const grid = $("#park-coasters-grid");
        grid.empty();
        // SCROLL INTERNO para no alargar demasiado la página
        grid.css({
          "max-height": "600px",
          "overflow-y": "auto",
          "overflow-x": "hidden",
          "padding-right": "8px",
        });

        if (data.success && data.coasters && data.coasters.length > 0) {
          const operatingList = [];
          const constructionList = [];
          const closedList = [];

          data.coasters.forEach((c) => {
            let statusText = c.coaster_status || "Operativa";
            if (statusText === "Operating" || statusText === "Operativa") {
              operatingList.push({ c, statusText });
            } else if (
              statusText === "Under Construction" ||
              statusText === "Under construction" ||
              statusText === "Construction"
            ) {
              constructionList.push({ c, statusText });
            } else {
              closedList.push({ c, statusText });
            }
          });

          $("#operating-coasters-val").text(operatingList.length); // Only operating for the stat block

          if (data.coasters.length > 0) {
            let html = "";

            const renderRow = (item) => {
              const c = item.c;
              const statusText = item.statusText;
              const fallback = "https://placehold.co/400x300?text=Coaster";
              const img = c.imagen_url
                ? c.imagen_url.startsWith("/")
                  ? (window.BASE_URL || "") + c.imagen_url
                  : c.imagen_url
                : fallback;

              let statusLabel = statusText;
              let statusColor = "#6c757d";
              let statusTextLabel = "UNK";

              if (statusText === "Operating" || statusText === "Operativa") {
                statusLabel = "Operativa";
                statusColor = "#00e676";
                statusTextLabel = "OP";
              } else if (
                statusText === "Defunct" ||
                statusText === "Closed" ||
                statusText === "Cerrada"
              ) {
                statusLabel = "Cerrada";
                statusColor = "#ff4c4c";
                statusTextLabel = "DEF";
              } else if (statusText === "SBNO") {
                statusLabel = "SBNO";
                statusColor = "#ffc107";
                statusTextLabel = "SBN";
              } else if (
                statusText === "Under Construction" ||
                statusText === "Under construction" ||
                statusText === "Construction"
              ) {
                statusLabel = "En Construcción";
                statusColor = "#0dcaf0";
                statusTextLabel = "CNS";
              }

              // Stats con Bootstrap icons layout
              const statItems = [];
              if (c.height && c.height !== "0")
                statItems.push(
                  `<span><i class="fa-solid fa-ruler-vertical opacity-75 me-1"></i>${c.height}m</span>`,
                );
              if (c.speed && c.speed !== "0")
                statItems.push(
                  `<span><i class="fa-solid fa-gauge opacity-75 me-1"></i>${c.speed}km/h</span>`,
                );
              if (c.coaster_length && c.coaster_length !== "0")
                statItems.push(
                  `<span><i class="fa-solid fa-road opacity-75 me-1"></i>${c.coaster_length}m</span>`,
                );
              if (c.inversions && c.inversions !== "0")
                statItems.push(
                  `<span><i class="fa-solid fa-arrows-rotate opacity-75 me-1"></i>${c.inversions} inv.</span>`,
                );

              // Subtítulo text-muted Bootstrap
              const subtitleParts = [];
              if (c.manufacter)
                subtitleParts.push(
                  `<span class="text-success fw-medium">${c.manufacter}</span>`,
                );
              if (c.modelo) subtitleParts.push(`<span>${c.modelo}</span>`);
              if (c.coaster_type)
                subtitleParts.push(`<span>${c.coaster_type}</span>`);

              return `
               <a href="${window.BASE_URL || ""}/web/views/public/coasters/coasters.php?id=${c.id}" class="list-group-item list-group-item-action bg-transparent border-bottom border-secondary border-opacity-25 px-0 py-3 text-decoration-none animate__animated animate__fadeIn" style="transition: all 0.2s ease-in-out;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.03)'" onmouseout="this.style.backgroundColor='transparent'">
                   <div class="d-flex align-items-center gap-2 gap-sm-3 px-2 px-sm-3">
                       
                       <!-- Imagen adaptable -->
                       <img src="${img}" class="rounded-3 shadow-sm flex-shrink-0" style="width: clamp(70px, 20vw, 130px); height: clamp(55px, 15vw, 90px); object-fit: cover;" onerror="this.src='${fallback}'">

                       <!-- Contenido central: nombre + subtítulo + stats -->
                       <div class="flex-grow-1 min-w-0 py-1" style="overflow: hidden;">
                           <h5 class="fw-bold text-white mb-1" style="font-size: clamp(0.85rem, 3vw, 1.1rem); overflow-wrap: break-word; word-break: break-word; white-space: normal;">${c.coaster_name}</h5>
                           <div class="text-truncate d-none d-sm-block" style="font-size:0.82rem; color: #8b949e; margin-bottom: 4px;">
                             ${subtitleParts.join(' <span class="mx-1 opacity-50">&bull;</span> ')}
                           </div>
                           <div class="d-flex flex-column flex-sm-row flex-wrap" style="gap: 4px 10px; font-size:0.78rem; color: #6e7681;">
                             ${statItems.length > 0 ? statItems.join("") : '<span class="fst-italic opacity-50 d-none d-sm-inline">Sin datos estadísticos</span>'}
                           </div>
                       </div>

                       <!-- Lado derecho: Estado -->
                       <div class="text-end d-flex flex-column align-items-center flex-shrink-0" style="min-width: 60px;">
                           <span style="color: ${statusColor}; font-weight: 800; font-size: 0.72rem; letter-spacing: 0.4px; text-transform: uppercase; white-space: nowrap;">
                               ${statusLabel}
                           </span>
                           <i class="fa-solid fa-chevron-right mt-1" style="color: #495057; font-size: 0.85rem;"></i>
                       </div>
                   </div>
               </a>`;
            };

            const separator = (title) => `
            <div class="col-12 w-100 mt-4 mb-2 d-flex align-items-center" style="gap:10px;">
                <div style="flex:1; height:1px; background:linear-gradient(to right, transparent, rgba(0,230,118,0.25));"></div>
                <span style="font-size:0.68rem; font-weight:800; letter-spacing:2.5px; text-transform:uppercase; color:rgba(0,230,118,0.55);">${title}</span>
                <div style="flex:1; height:1px; background:linear-gradient(to left, transparent, rgba(0,230,118,0.25));"></div>
            </div>`;

            if (constructionList.length > 0) {
              html += separator("En Construcción");
              html +=
                `<div class="list-group list-group-flush w-100 bg-transparent px-2">` +
                constructionList.map(renderRow).join("") +
                `</div>`;
            }

            if (operatingList.length > 0) {
              html += separator("Operativas");
              html +=
                `<div class="list-group list-group-flush w-100 bg-transparent px-2">` +
                operatingList.map(renderRow).join("") +
                `</div>`;
            }

            if (closedList.length > 0) {
              html += separator("Cerradas / SBNO");
              html +=
                `<div class="list-group list-group-flush w-100 bg-transparent px-2">` +
                closedList.map(renderRow).join("") +
                `</div>`;
            }

            grid.html(html);
          } else {
            grid.html(
              '<div class="col-12 text-center py-5 text-white-50">No hay montañas rusas listadas para este parque.</div>',
            );
          }
        } else {
          grid.html(
            '<div class="col-12 text-center py-5 text-white-50">No hay montañas rusas listadas para este parque.</div>',
          );
        }
      } catch (e) {
        console.error("Error cargando coasters del parque:", e);
        $("#park-coasters-grid").html(
          '<div class="col-12 text-center text-danger py-5">Error al conectar con la base de datos de atracciones.</div>',
        );
      }
    }

    function renderStars(note) {
      const full = Math.floor(note);
      const half = note % 1 >= 0.5 ? 1 : 0;
      const empty = 5 - full - half;

      let html = "";
      for (let i = 0; i < full; i++)
        html += '<i class="fa-solid fa-star text-warning"></i>';
      if (half)
        html += '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
      for (let i = 0; i < empty; i++)
        html += '<i class="fa-regular fa-star text-warning"></i>';

      return html;
    }

    function timeAgo(dateString) {
      const diff = Math.floor((new Date() - new Date(dateString)) / 1000);
      if (diff < 60) return "hace un momento";
      if (diff < 3600) return `hace ${Math.floor(diff / 60)} minutos`;
      if (diff < 86400) return `hace ${Math.floor(diff / 3600)} horas`;
      if (diff < 2592000) return `hace ${Math.floor(diff / 86400)} días`;
      if (diff < 31536000) return `hace ${Math.floor(diff / 2592000)} meses`;
      return `hace ${Math.floor(diff / 31536000)} años`;
    }

    async function loadReviews(order = "newest") {
      try {
        const res = await fetch(
          `${apiBase}?action=reviews&id=${parkId}&order=${order}`,
        );
        if (!res.ok) throw new Error("API action not found on backend");
        const data = await res.json();
        if (data.success && data.reviews && data.reviews.length > 0) {
          const container = $("#reviews-list");
          container.empty();
          data.reviews.forEach((review) => {
            let tagsHtml = "";
            if (review.tags && review.tags.length > 0) {
              tagsHtml = '<div class="d-flex flex-wrap gap-2 mt-2 mb-2">';
              review.tags.forEach((t) => {
                const cls = t.type === "pro" ? "success" : "danger";
                tagsHtml += `<span class="badge bg-${cls} text-white rounded-pill px-3 py-1" style="font-weight:600;font-size:0.75rem;">${t.tag.replace(/_/g, " ").toUpperCase()}</span>`;
              });
              tagsHtml += "</div>";
            }

            // Avatar: foto del usuario o SVG por defecto
            const defaultAvatarUrl = `${window.BASE_URL}/web/img/avatars/default_avatar.svg`;
            const rawImg = review.profile_image
              ? review.profile_image.startsWith("/")
                ? BASE_URL + review.profile_image
                : review.profile_image
              : null;
            const avatarSrc = rawImg || defaultAvatarUrl;
            const avatarHtml = `<img src="${avatarSrc}" alt="${review.username}" referrerpolicy="no-referrer"
              style="width:40px;height:40px;object-fit:cover;border-radius:50%;border:2px solid var(--rcw-green-dim,#198754);flex-shrink:0;background:#2d333b;"
              onerror="this.src='${defaultAvatarUrl}';this.onerror=null;">`;

            const isOwn =
              (window.CURRENT_USER_ID &&
                parseInt(review.user_id) === window.CURRENT_USER_ID) ||
              (window.CURRENT_USERNAME &&
                review.username === window.CURRENT_USERNAME);

            const editBtn = isOwn
              ? `<button class="btn btn-link p-0 text-warning edit-review-btn d-flex flex-column align-items-center lh-1"
                   data-id="${review.id}"
                   data-note="${review.note}"
                   data-text="${encodeURIComponent(review.review || "")}"
                   data-tags='${JSON.stringify(review.tags || [])}'
                   title="Editar mi reseña"
                   style="text-decoration:none; min-width: 60px;">
                   <i class="fa-solid fa-pen-to-square mb-1 fs-5"></i>
                   <span class="fw-bold" style="font-size:0.55rem;text-transform:uppercase;">Editar reseña</span>
                 </button>`
              : "";

            container.append(`
              <div class="border-bottom pb-4 mb-4 animate__animated animate__fadeIn${isOwn ? " own-review" : ""}">
                <div class="d-flex align-items-start gap-3 mb-2">
                  ${avatarHtml}
                  <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                      <strong class="text-white fs-6 text-truncate flex-grow-1 min-w-0" title="${review.username || "Usuario anónimo"}">${review.username || "Usuario anónimo"}</strong>
                      ${editBtn}
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                      <span class="stars-display lh-1">${renderStars(review.note)}</span>
                      <span class="text-muted small">• ${timeAgo(review.created_at)}</span>
                    </div>
                  </div>
                </div>
                ${tagsHtml}
                ${review.review
                ? `
                <div class="mt-3 p-3 bg-dark bg-opacity-25 rounded border-start border-3 border-success border-opacity-50">
                  <p class="mb-0 text-white-50" style="font-size:0.92rem; line-height:1.7;">${review.review}</p>
                </div>`
                : ""
              }
              </div>
            `);
          });

          // Auto-abrir modal de edición si viene del botón "Editar mi reseña"
          if (new URLSearchParams(window.location.search).get("edit") === "true") {
            const editBtn = $(".edit-review-btn").first();
            if (editBtn.length) {
              setTimeout(() => {
                editBtn.click();
                window.history.replaceState({}, document.title, window.location.pathname + "?id=" + parkId);
              }, 200);
            }
          }
        } else {
          $("#reviews-list").html(
            '<div class="text-center text-muted py-5"><i class="fa-regular fa-comment-dots fa-3x mb-3 d-block"></i>Aún no hay reseñas para este parque</div>',
          );
        }
      } catch (e) {
        console.warn(
          "Falta endpoint de reviews de parques o hubo un error:",
          e,
        );
        $("#reviews-list").html(
          '<div class="text-center text-muted py-5"><i class="fa-regular fa-comment-dots fa-3x mb-3 d-block"></i>Aún no hay reseñas para este parque</div>',
        );
      }
    }

    // ── Lógica modal de edición de reseña (parks) ────────────────────────────
    let editReviewModalPark = null;
    const editModalElPark = document.getElementById("edit-review-modal");
    if (editModalElPark && typeof bootstrap !== "undefined") {
      editReviewModalPark = new bootstrap.Modal(editModalElPark);
    }

    let editProsChoices = null;
    let editContrasChoices = null;

    function initEditChoices() {
      if (!editProsChoices && document.getElementById("edit-pros-select")) {
        editProsChoices = new Choices("#edit-pros-select", {
          removeItemButton: true,
          searchEnabled: false,
          placeholderValue: "Selecciona las ventajas...",
          itemSelectText: "",
          position: 'bottom',
        });
      }
      if (
        !editContrasChoices &&
        document.getElementById("edit-contras-select")
      ) {
        editContrasChoices = new Choices("#edit-contras-select", {
          removeItemButton: true,
          searchEnabled: false,
          placeholderValue: "Selecciona las contras...",
          itemSelectText: "",
          position: 'bottom',
        });
      }
    }

    // Actualizar nota oculta cuando cambia el radio (parques)
    $(document).on("change", 'input[name="edit_note"]', function () {
      $("#edit-review-note").val($(this).val());
    });

    $(document).on("click", ".edit-review-btn", function () {
      const id = $(this).data("id");
      const note = parseFloat($(this).data("note")) || 0;
      const text = decodeURIComponent($(this).data("text") || "");
      $("#edit-review-id").val(id);
      $("#edit-review-note").val(note);
      $("#edit-review-text").val(text);
      // Marcar el radio correspondiente
      $(`input[name="edit_note"][value="${note}"]`).prop("checked", true);

      // Cargar tags
      initEditChoices();
      const rawTags = $(this).attr("data-tags") || "[]";
      let tags = [];
      try {
        tags = JSON.parse(rawTags);
      } catch (e) {
        tags = [];
      }

      if (tags && tags.length > 0) {
        const pros = tags.filter((t) => t.type === "pro").map((t) => t.tag);
        const contras = tags.filter((t) => t.type === "con").map((t) => t.tag);
        editProsChoices.removeActiveItems();
        editProsChoices.setChoiceByValue(pros);
        editContrasChoices.removeActiveItems();
        editContrasChoices.setChoiceByValue(contras);
      } else {
        editProsChoices.removeActiveItems();
        editContrasChoices.removeActiveItems();
      }

      if (editReviewModalPark) editReviewModalPark.show();
    });

    $(document).on("click", "#save-edit-review-btn", async function () {
      const btn = $(this);
      const reviewId = $("#edit-review-id").val();
      const note = parseFloat($("#edit-review-note").val()) || 0;
      const text = $("#edit-review-text").val().trim();
      if (!note) {
        alert("Por favor, selecciona una puntuación.");
        return;
      }
      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');
      const fd = new FormData();
      fd.append("review_id", reviewId);
      fd.append("note", note);
      fd.append("review", text);

      // Añadir tags
      const pros = editProsChoices.getValue(true);
      const contras = editContrasChoices.getValue(true);
      pros.forEach((p) => fd.append("pros[]", p));
      contras.forEach((c) => fd.append("contras[]", c));
      try {
        const res = await fetch(
          `${window.BASE_URL}/api/php/parks.php?action=update_review`,
          {
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
            method: "POST",
            body: fd,
          },
        );
        const data = await res.json();
        if (data.success) {
          if (editReviewModalPark) editReviewModalPark.hide();
          loadReviews($("#reviews-sort").val() || "newest");
        } else {
          alert("Error: " + (data.error || "No se pudo guardar."));
        }
      } catch (e) {
        alert("Error de conexión.");
      } finally {
        btn
          .prop("disabled", false)
          .html('<i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios');
      }
    });

    if (parkId) {
      loadParkData(parkId);
      loadParkCoasters(parkId);
      loadReviews();

      $("#reviews-sort").on("change", function () {
        loadReviews($(this).val());
      });
    }
  }

  // --- LÓGICA PARA EL FORMULARIO DE RESEÑAS DE PARQUES ---
  if (document.getElementById("review-form")) {
    const rf = document.getElementById("review-form");
    const parkIdInput = rf.querySelector('input[name="park_id"]');
    if (parkIdInput) {
      const pId = parkIdInput.value;
      fetch(`${BASE_URL}/api/php/parks.php?action=check_review&id=${pId}`)
        .then((r) => r.json())
        .then((data) => {
          if (data.success && data.hasReviewed) {
            rf.classList.add("d-none");
            const msg = document.getElementById("already-reviewed-msg");
            if (msg) msg.classList.remove("d-none");
          }
        })
        .catch((e) => console.error("Error check review:", e));
    }

    new Choices("#pros-select", {
      removeItemButton: true,
      searchEnabled: false,
      placeholderValue: "Selecciona las ventajas...",
      itemSelectText: "",
      position: 'bottom',
    });
    new Choices("#contras-select", {
      removeItemButton: true,
      searchEnabled: false,
      placeholderValue: "Selecciona las contras...",
      itemSelectText: "",
      position: 'bottom',
    });

    document
      .getElementById("review-form")
      .addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const parkId = formData.get("park_id");
        const note = formData.get("note");

        if (!note) {
          alert("Por favor, califica con estrellas el parque.");
          return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          'Publicando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

        fetch(window.BASE_URL + "/api/php/parks.php?action=save_review", {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: formData,
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              window.location.href =
                window.BASE_URL +
                "/web/views/public/parks/parks.php?id=" +
                parkId;
            } else {
              alert("Error: " + data.error);
              submitBtn.disabled = false;
              submitBtn.innerHTML =
                'Publicar Reseña <i class="fa-solid fa-paper-plane ms-2"></i>';
            }
          })
          .catch((err) => {
            console.error(err);
            alert("Error de conexión");
            submitBtn.disabled = false;
            submitBtn.innerHTML =
              'Publicar Reseña <i class="fa-solid fa-paper-plane ms-2"></i>';
          });
      });
  }

  // --- COMPARTIR ---
  $("#btn-share").on("click", async function () {
    const title =
      document.getElementById("park-name")?.textContent ||
      "RollerCoaster World";
    const text = `Mira este parque en RollerCoaster World: ${title}`;
    const url = window.location.href;

    const fallbackCopy = async () => {
      try {
        await navigator.clipboard.writeText(url);
        if (window.rcwToast) {
          window.rcwToast("Enlace copiado al portapapeles", "success");
        } else {
          alert("Enlace copiado al portapapeles");
        }
      } catch (err) {
        console.error("Error copying to clipboard:", err);
      }
    };

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url });
      } catch (err) {
        if (err.name === "InvalidStateError") {
          await fallbackCopy();
        } else if (err.name !== "AbortError") {
          console.error("Error sharing:", err);
          await fallbackCopy();
        }
      }
    } else {
      await fallbackCopy();
    }
  });
});
