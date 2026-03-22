$(document).ready(function () {
  const apiBase = (window.BASE_URL || "") + "/api/php/parks.php";

  const resultsContainer = $("#park-list");
  const countContainer = $("#park-count");
  const paginationContainer = $("#park-pagination");

  if (!resultsContainer.length) {
    console.error("No se encontró #park-list");
    return;
  }

  // --- Range Slider Badges ---
  window.addEventListener("pageshow", function () {
    document.getElementById("opening-year-min").value = 1800;
    document.getElementById("num-coaster-min").value = 0;
    document.getElementById("rating-filter").value = 0;
    document.getElementById("year-val").textContent = "1800";
    document.getElementById("coasters-val").textContent = "0";
    document.getElementById("rating-val").textContent = "0★";
  });

  document
    .getElementById("opening-year-min")
    .addEventListener("input", function () {
      document.getElementById("year-val").textContent = this.value;
    });

  document
    .getElementById("num-coaster-min")
    .addEventListener("input", function () {
      document.getElementById("coasters-val").textContent = this.value;
    });

  document
    .getElementById("rating-filter")
    .addEventListener("input", function () {
      document.getElementById("rating-val").textContent = this.value + "★";
    });

  // Restaurar valores en caso de 'Atrás' del navegador
  document.getElementById("year-val").textContent =
    document.getElementById("opening-year-min").value;
  document.getElementById("coasters-val").textContent =
    document.getElementById("num-coaster-min").value;
  document.getElementById("rating-val").textContent =
    document.getElementById("rating-filter").value + "★";

  // Botón Filtrar
  document.getElementById("btn-filtrar").addEventListener("click", function () {
    isFiltering = false;
    loadParks(getFilters(), 1);
  });

  // Listener para el selector de ordenación
  const sortFilter = document.getElementById("sort-filter");
  const sortDirectionBtn = document.getElementById("sort-direction-btn");
  const sortDirectionInput = document.getElementById("sort-direction");

  if (sortFilter) {
    sortFilter.addEventListener("change", function () {
       // Reset direction to logical default based on selection
       const val = this.value;
       let defaultDir = "ASC"; // Default for Name, Year
       if (["stars", "coasters"].includes(val)) {
           defaultDir = "DESC";
       }
       
       sortDirectionInput.value = defaultDir;
       updateSortIcon(defaultDir);
       loadParks(getFilters(), 1);
    });
  } else {
      console.warn("Elemento #sort-filter no encontrado en parks.js");
  }

  if (sortDirectionBtn && sortDirectionInput) {
      sortDirectionBtn.addEventListener("click", function() {
          const currentDir = sortDirectionInput.value;
          const newDir = currentDir === "ASC" ? "DESC" : "ASC";
          console.log("Cambiando dirección orden:", currentDir, "->", newDir);
          sortDirectionInput.value = newDir;
          updateSortIcon(newDir);
          loadParks(getFilters(), 1);
      });
  } else {
      console.warn("Botón de dirección o input oculto no encontrado en parks.js");
  }

  function updateSortIcon(dir) {
      const icon = sortDirectionBtn.querySelector("i");
      if (!icon) return;
      if (dir === "ASC") {
          icon.className = "fa-solid fa-arrow-up-wide-short";
      } else {
          icon.className = "fa-solid fa-arrow-down-wide-short";
      }
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

    // Búsqueda
    if (params.q) url.searchParams.append("q", params.q);

    // Filtros
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
    if (params.order_dir) url.searchParams.append("order_dir", params.order_dir);

    console.log("Llamando a API:", url.toString());

    fetch(url)
      .then((response) => {
        console.log("Estado HTTP:", response.status, response.statusText);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      })
      .then((data) => {
        console.log("JSON recibido:", data); // ← mira aquí el JSON real

        resultsContainer.empty();

        // El API modificado devuelve { data: [...], total: N }
        let total = data.total || 0;
        if (countContainer.length) {
          countContainer.text(
            `Mostrando ${total} parque${total !== 1 ? "s" : ""}`,
          );
        }

        // Tu API devuelve array en data.data o directamente en data si no tiene format objecto
        let parks = [];
        if (Array.isArray(data)) {
          parks = data;
          total = parks.length;
        } else if (data && Array.isArray(data.data)) {
          parks = data.data;
        } else {
          resultsContainer.html(
            '<p class="text-center text-danger py-5">Formato de datos inesperado</p>',
          );
          console.error("Formato no reconocido:", data);
          return;
        }

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
          const imgSrc = park.imagen_url || fallbackImg;
          const img = `<img src="${imgSrc}" alt="${park.park_name}" class="rounded-0 shadow-sm"
            style="width: 100px; height: 100px; object-fit: cover; margin-right: 20px;"
            referrerpolicy="no-referrer"
            onerror="this.onerror=null;this.src='${fallbackImg}';">`;

          html += `
            <a href="${window.BASE_URL || ""}/web/views/public/parks/parks.php?id=${park.id}" class="list-group-item list-group-item-action d-flex align-items-center p-3">
              ${img}
              <div class="flex-grow-1">
                <h5 class="mb-1 fw-bold text-success" style="font-size: 1.25rem;">${park.park_name || "Sin nombre"}</h5>
                <p class="mb-1 text-muted"><i class="fa-solid fa-map-pin me-1"></i>${park.park_location || "N/A"}, ${park.park_country || ""}</p>
                <small class="text-secondary">${park.opening_year || "N/A"} • ${park.operating_coasters || 0} montañas rusas • ${park.stars || "0.00"} ★</small>
              </div>
              <i class="fa-solid fa-chevron-right text-muted ms-3"></i>
            </a>
          `;
        });
        resultsContainer.html(html);

        renderPagination(total);
      })
      .catch((error) => {
        console.error("Error al cargar parques:", error);
        resultsContainer.html(
          `<p class="text-center text-danger py-5">Error al cargar parques: ${error.message}</p>`,
        );
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

    // Botón «
    const prevBtn = document.createElement("button");
    prevBtn.className = "btn btn-outline-success mx-1";
    prevBtn.textContent = "«";
    if (currentPage === 1) prevBtn.disabled = true;
    prevBtn.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadParks(getFilters(), currentPage - 1);
    });
    pageBtn.appendChild(prevBtn);

    // Botón primera página
    const btnFirst = document.createElement("button");
    btnFirst.className = "btn btn-success mx-1";
    btnFirst.textContent = "1";
    btnFirst.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadParks(getFilters(), 1);
    });
    pageBtn.appendChild(btnFirst);

    const btnDots = document.createElement("button");
    btnDots.className = "btn border-0 text-secondary mx-1";
    btnDots.textContent = "...";
    btnDots.disabled = true;
    pageBtn.appendChild(btnDots);

    // Páginas centrales
    let start = Math.max(2, currentPage - 1);
    let end = Math.min(totalPages - 1, start + 2);
    start = Math.max(2, end - 2);

    for (let i = start; i <= end; i++) {
      const pageButton = document.createElement("button");
      pageButton.className = "btn btn-light text-success border mx-1";
      pageButton.textContent = i;
      if (i === currentPage) {
        pageButton.classList.remove("btn-light", "text-success", "border");
        pageButton.classList.add("btn-success", "text-white");
      }
      (function (page) {
        pageButton.addEventListener("click", function () {
          window.scrollTo({ top: 10, behavior: "smooth" });
          loadParks(getFilters(), page);
        });
      })(i);
      pageBtn.appendChild(pageButton);
    }

    const btnDots2 = document.createElement("button");
    btnDots2.className = "btn border-0 text-secondary mx-1";
    btnDots2.textContent = "...";
    btnDots2.disabled = true;
    pageBtn.appendChild(btnDots2);

    // Botón última página
    const btnLast = document.createElement("button");
    btnLast.className = "btn btn-success mx-1";
    btnLast.textContent = `${totalPages}`;
    btnLast.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadParks(getFilters(), totalPages);
    });
    pageBtn.appendChild(btnLast);

    // Botón »
    const nextBtn = document.createElement("button");
    nextBtn.className = "btn btn-outline-success mx-1";
    nextBtn.textContent = "»";
    if (currentPage === totalPages) nextBtn.disabled = true;
    nextBtn.addEventListener("click", function () {
      window.scrollTo({ top: 10, behavior: "smooth" });
      loadParks(getFilters(), currentPage + 1);
    });
    pageBtn.appendChild(nextBtn);

    paginationContainer[0].appendChild(pageBtn);
  }

  async function loadFilters() {
    try {
      const url = new URL(apiBase, window.location.origin);
      url.searchParams.append("action", "country");
      const res = await fetch(url);
      const data = await res.json();

      let countries = data.data || data;
      if (Array.isArray(countries)) {
        countries.forEach((c) => {
          if (c) {
            // Evita nulos o vacíos si los hay
            $("#country-filter").append(new Option(c, c));
          }
        });
      }
    } catch (e) {
      console.warn("Error cargando filtros:", e);
    }
  }

  // Cargar al inicio
  let isFiltering = false;
  loadFilters();
  loadParks(getFilters());

  // Búsqueda en autocomplete (debounce)
  let searchDebounce = null;
  const searchInput = $("#park-search");
  const searchIcon = $("#search-icon");

  searchInput.on("keyup", function () {
    const search = this.value.trim();
    clearTimeout(searchDebounce);

    if (search.length > 0) {
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

    if (search.length < 3) {
      $("#search-results").html("").hide();
      return;
    }

    searchDebounce = setTimeout(async () => {
      try {
        const url = new URL(apiBase, window.location.origin);
        url.searchParams.append("action", "list");
        url.searchParams.append("q", search);
        url.searchParams.append("page", "1");
        url.searchParams.append("limit", "5");

        const res = await fetch(url);
        const data = await res.json();

        // listParks devuelve { success: true, data: [...], total: N }
        let parksData = Array.isArray(data.data) ? data.data : [];

        let html = "";
        if (parksData.length > 0) {
          parksData.forEach((p) => {
            html += `
            <a href="${window.BASE_URL || ""}/web/views/public/parks/parks.php?id=${p.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
              <div>
                <h6 class="mb-0 fw-bold">${p.park_name}</h6>
                <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>${p.park_location}, ${p.park_country}</small>
              </div>
              <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
            </a>`;
          });
          html += `
          <a href="#" class="list-group-item list-group-item-action text-center text-primary fw-bold" id="view-all-results">
            Ver todos los resultados para "${search}" <i class="fa-solid fa-arrow-right ms-1"></i>
          </a>`;
        } else {
          html = `<div class="list-group-item text-muted text-center py-3">No se encontraron parques.</div>`;
        }
        $("#search-results").html(html).show();
      } catch (e) {
        console.warn("Error en búsqueda:", e);
      }
    }, 300);
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
      $("h1").text("Base de Datos de Parques de Atracciones");
      loadParks(getFilters(), 1);
    }
  });

  $(document).on("click", "#view-all-results", function (e) {
    e.preventDefault();
    isFiltering = true;
    $("#search-results").html("").hide();
    $("h1").text('Resultados para: "' + searchInput.val() + '"');
    loadParks(getFilters(), 1);
  });

  $(document).on("click", function (e) {
    if (!$(e.target).closest("#park-search, #search-results").length) {
      $("#search-results").hide();
    }
  });

  searchInput.on("focus", function () {
    if (
      $(this).val().length >= 3 &&
      $("#search-results").children().length > 0
    ) {
      $("#search-results").show();
    }
  });

  // Limpiar filtros
  $("#clear-filters").click(function () {
    $("#country-filter, #location-filter").val("");
    $("#opening-year-min").val(1800);
    $("#num-coaster-min").val(0);
    $("#rating-filter").val(0);

    document.getElementById("year-val").textContent = "1800";
    document.getElementById("coasters-val").textContent = "0";
    document.getElementById("rating-val").textContent = "0★";

    searchInput.val("");
    searchIcon
      .removeClass("fa-xmark text-danger")
      .addClass("fa-magnifying-glass text-muted")
      .css("cursor", "text");
    $("#search-results").html("").hide();
    isFiltering = false;
    $("h1").text("Base de Datos de Parques de Atracciones");
    loadParks({}, 1);
  });

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

        if (data.success && data.park) {
          const park = data.park;

          // Hero
          $("#park-name").text(park.park_name);
          if (park.imagen_url) {
            $("#park-hero-img").attr("src", park.imagen_url);
          } else {
            $("#park-hero-img").attr(
              "src",
              "https://placehold.co/1200x600?text=Sin+Imagen",
            );
          }

          // Ratings y Ubicación
          $("#park-rating").text(park.stars ? park.stars + " ★" : "0.00 ★");
          $("#park-location").html(
            `<i class="fa-solid fa-map-pin me-2"></i>${park.park_location}, ${park.park_country}`,
          );

          // Cajas de información
          $("#opening-year").text(park.opening_year || "N/A");
          $("#park-country").text(park.park_country || "N/A");
          $("#num-coaster").text(park.num_coasters || "0");
          $("#operating-coasters").text(park.operating_coasters || "0");
          $("#precio-entrada").text(
            park.precio_entrada ? park.precio_entrada + "€" : "N/A",
          );

          // Descripción y Botones
          $("#park-description").text(
            park.descripcion || "Sin descripción disponible para este parque.",
          );

          if (park.website) {
            $("#btn-website")
              .attr("href", park.website)
              .attr("target", "_blank")
              .show();
          } else {
            $("#btn-website").hide();
          }

          if (park.park_location) {
            $("#btn-map").on("click", function () {
              window.open(
                `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(park.park_name + " " + park.park_location)}`,
                "_blank",
              );
            });
          } else {
            $("#btn-map").hide();
          }
        } else {
          $("#park-name").text("Error: Parque no encontrado");
        }
      } catch (e) {
        console.error("Error cargando detalles del parque:", e);
        $("#park-name").text("Error de conexión");
      }
    }

    async function loadPhotos(id) {
      try {
        const res = await fetch(`${apiBase}?action=photos&id=${id}`);
        if (!res.ok) throw new Error("API action not found on backend");
        const data = await res.json();
        if (data.success && data.photos && data.photos.length > 0) {
          $("#park-gallery").empty();
          data.photos.forEach((photo) => {
            const col = document.createElement("div");
            col.className = "col-6 col-md-3";
            col.innerHTML = `<img src="${photo.photo_url}" alt="${photo.caption || "Foto del parque"}" class="photo-thumb w-100 rounded shadow-sm">`;
            document.querySelector("#park-gallery").appendChild(col);
          });
        } else {
          $("#park-gallery").html(
            '<p class="text-muted text-center py-3">Aún no hay fotos</p>',
          );
        }
      } catch (e) {
        console.warn("Falta endpoint de fotos de parques o hubo un error:", e);
        $("#park-gallery").html(
          '<p class="text-muted text-center py-3">Aún no hay fotos</p>',
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
          $("#reviews-list").empty();
          data.reviews.forEach((review) => {
            // Render logic similar to coasters
            $("#reviews-list").append(`
                  <div class="border-bottom pb-3 mb-3">
                     <div class="d-flex align-items-center gap-2 mb-1">
                        <strong>${review.username || "Usuario anónimo"}</strong>
                        <span class="badge bg-success ms-2">${review.note || 0} ★</span>
                     </div>
                     <p class="mb-0 mt-2">${review.review || ""}</p>
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
      loadPhotos(parkId);
      loadReviews();

      $("#reviews-sort").on("change", function () {
        loadReviews($(this).val());
      });
    }
  }
});
