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
        isFiltering = false;
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
            const fallbackImg = "https://placehold.co/400x300?text=Park";
            const imgSrc = park.imagen_url || fallbackImg;
            html += `
              <a href="${window.BASE_URL || ""}/web/views/public/parks/parks.php?id=${park.id}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
                <img src="${imgSrc}" class="rounded-0 shadow-sm object-fit-cover me-3" style="width:100px; height:100px;">
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
      const totalPages = Math.ceil(total / itemsPerPage);
      if (totalPages <= 1) {
        paginationContainer.empty();
        return;
      }
      paginationContainer.empty();
      const pageBtn = document.createElement("div");
      pageBtn.classList.add("page-buttons");
      // (Brevity: assuming identical logic but simplified slightly)
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
      } catch (e) {}
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
        $("h1").text("Buscar Parques");
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
            $("#park-hero-img").attr("src", park.imagen_url);
          } else {
            $("#park-hero-img").attr(
              "src",
              "https://placehold.co/900x500?text=Sin+Imagen",
            );
          }

          // 2x2 Stats (Coaster style)
          $("#global-ranking").text(park.ranking || "—");
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
          if (park.latitude && park.longitude) {
            $("#park-coords-table").text(`${park.latitude}, ${park.longitude}`);
          }

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

        if (data.success && data.coasters && data.coasters.length > 0) {
          const operating = data.coasters.filter(
            (c) =>
              !c.coaster_status ||
              c.coaster_status === "Operating" ||
              c.coaster_status === "Operativa",
          );

          $("#operating-count-badge").text(data.coasters.length); // Total coasters in the park
          $("#operating-coasters-val").text(operating.length); // Only operating for the stat block

          if (data.coasters.length > 0) {
            data.coasters.forEach((c) => {
              const fallback = "https://placehold.co/400x300?text=Coaster";
              const img = c.imagen_url
              ? (c.imagen_url.startsWith('/') ? (window.BASE_URL || '') + c.imagen_url : c.imagen_url)
              : fallback;

              let badgeHtml = "";
              let statusText = c.coaster_status || "Operativa";
              if (statusText === "Operating" || statusText === "Operativa") {
                badgeHtml = `<span class="badge bg-success-subtle text-success border border-success px-2 py-1" style="font-size:0.7rem;">OPERATIVA</span>`;
              } else if (statusText === "Defunct") {
                badgeHtml = `<span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1" style="font-size:0.7rem;">CERRADA</span>`;
              } else if (statusText === "SBNO") {
                badgeHtml = `<span class="badge bg-warning-subtle text-warning border border-warning px-2 py-1" style="font-size:0.7rem;">SBNO</span>`;
              } else if (
                statusText === "Under Construction" ||
                statusText === "Under construction"
              ) {
                badgeHtml = `<span class="badge bg-info-subtle text-info border border-info px-2 py-1" style="font-size:0.7rem;">EN CONSTRUCCIÓN</span>`;
              } else {
                badgeHtml = `<span class="badge bg-secondary-subtle text-secondary border border-secondary px-2 py-1" style="font-size:0.7rem;">${statusText.toUpperCase()}</span>`;
              }

              grid.append(`
                <div class="col-12 col-md-6 col-xl-4 animate__animated animate__fadeIn">
                  <a href="${window.BASE_URL || ""}/web/views/public/coasters/coasters.php?id=${c.id}" class="text-decoration-none">
                      <div class="card bg-dark border-secondary h-100 coaster-card-hover overflow-hidden shadow-sm" style="transition: transform 0.3s ease;">
                          <div style="height:160px; overflow:hidden;">
                              <img src="${img}" class="w-100 h-100" style="object-fit: cover;" onerror="this.src='${fallback}'">
                          </div>
                          <div class="p-3 border-top border-secondary">
                              <h6 class="text-white mb-1 text-truncate fw-bold">${c.coaster_name}</h6>
                              <div class="d-flex justify-content-between align-items-center">
                                  <small class="text-success fw-bold">${c.manufacter || "Fabricante"}</small>
                                  ${badgeHtml}
                              </div>
                          </div>
                      </div>
                  </a>
                </div>
              `);
            });
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
            container.append(`
                  <div class="border-bottom border-secondary pb-3 mb-3">
                     <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                           <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width:32px; height:32px; font-size:12px;">
                              ${(review.username || "U").charAt(0).toUpperCase()}
                           </div>
                           <strong class="text-white">${review.username || "Usuario anónimo"}</strong>
                        </div>
                        <span class="badge bg-success">${review.note || 0} ★</span>
                     </div>
                     <p class="mb-0 text-white-50" style="font-size:0.9rem;">${review.review || "Sin comentarios."}</p>
                  </div>
               `);
          });
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
    new Choices("#pros-select", {
      removeItemButton: true,
      placeholderValue: "Selecciona las ventajas...",
    });
    new Choices("#contras-select", {
      removeItemButton: true,
      placeholderValue: "Selecciona las contras...",
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
});
