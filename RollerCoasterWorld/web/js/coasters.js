$(document).ready(function () {
  if (document.getElementById("coaster-list")) {
    window.addEventListener("pageshow", function () {
      document.getElementById("height-filter").value = 0;
      document.getElementById("speed-filter").value = 0;
      document.getElementById("length-filter").value = 0;
      document.getElementById("inversions-filter").value = 0;
      document.getElementById("height-val").textContent = "0m";
      document.getElementById("speed-val").textContent = "0km/h";
      document.getElementById("length-val").textContent = "0m";
      document.getElementById("inversions-val").textContent = "0";
    });
    document
      .getElementById("height-filter")
      .addEventListener("input", function () {
        document.getElementById("height-val").textContent = this.value + "m";
      });

    document
      .getElementById("speed-filter")
      .addEventListener("input", function () {
        document.getElementById("speed-val").textContent = this.value + "km/h";
      });

    document
      .getElementById("length-filter")
      .addEventListener("input", function () {
        document.getElementById("length-val").textContent = this.value + "m";
      });

    document
      .getElementById("inversions-filter")
      .addEventListener("input", function () {
        document.getElementById("inversions-val").textContent = this.value;
      });

    // Inicializar los valores en caso de que el navegador restaure el estado del formulario al navegar atrás
    document.getElementById("height-val").textContent =
      document.getElementById("height-filter").value + "m";
    document.getElementById("speed-val").textContent =
      document.getElementById("speed-filter").value + "km/h";
    document.getElementById("length-val").textContent =
      document.getElementById("length-filter").value + "m";
    document.getElementById("inversions-val").textContent =
      document.getElementById("inversions-filter").value;

    document
      .getElementById("btn-filtrar")
      .addEventListener("click", applyFilters);

    document
      .getElementById("btn-borrar")
      .addEventListener("click", clearFilters);

    let searchDebounce = null;
    $("#coaster-search").on("keyup", function () {
      const search = this.value.trim();
      clearTimeout(searchDebounce);
      if (search.length < 3) {
        $("#search-results").html("").hide();
        return;
      }
      searchDebounce = setTimeout(async () => {
        try {
          const res = await fetch(
            `${BASE_URL}/api/php/coasters.php?action=search&search=${encodeURIComponent(search)}`,
          );
          const data = await res.json();
          let html = "";
          if (data.length > 0) {
            data.forEach((coaster) => {
              html += `
              <a href="${BASE_URL}/web/views/public/coasters/coasters.php?id=${coaster.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-0 fw-bold">${coaster.coaster_name}</h6>
                  <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>${coaster.park_name}</small>
                </div>
                <i class="fa-solid fa-chevron-right text-muted" style="font-size: 0.8rem;"></i>
              </a>`;
            });
            html += `
            <a href="#" class="list-group-item list-group-item-action text-center text-primary fw-bold" id="view-all-results">
              Ver todos los resultados para "${search}" <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>`;
          } else {
            html = `<div class="list-group-item text-muted text-center py-3">No se encontraron montañas rusas.</div>`;
          }
          $("#search-results").html(html).show();
        } catch (e) {
          console.warn("Error en búsqueda:", e);
        }
      }, 300);
    });

    let currentPage = 1;
    let currentSearchQuery = "";
    let isFiltering = false;
    let isAdvancedFiltering = false;

    function loadCoasters(page) {
      currentPage = page;

      if (isAdvancedFiltering) {
        let opened = document.getElementById("status-filter").checked;
        let ridden = document.getElementById("ridden-filter").checked;
        let height = document.getElementById("height-filter").value;
        let speed = document.getElementById("speed-filter").value;
        let length = document.getElementById("length-filter").value;
        let inversions = document.getElementById("inversions-filter").value;
        let manufacter = document.getElementById("manufacter-filter").value;
        let country = document.getElementById("country-filter").value;
        let year = document.getElementById("year-select").value;
        let search = document.getElementById("coaster-search").value;

        const params = new URLSearchParams({
          action: "apply_filters",
          page,
          opened,
          ridden,
          height,
          speed,
          length,
          inversions,
          manufacter,
          country,
          year,
          search,
        });
        fetch(`${BASE_URL}/api/php/coasters.php?${params}`)
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              let totalMsg = data.total || 0;
              $("#coaster-count").text(
                `Mostrando ${totalMsg} montaña${totalMsg !== 1 ? "s" : ""} rusa${totalMsg !== 1 ? "s" : ""}`,
              );
              displayCoasters(data.coasters);
              displayPagination(data.total, page);
            } else {
              document.getElementById("coaster-list").innerHTML =
                "<p style='color:red;'>Error cargando montañas rusas</p>";
            }
          })
          .catch((e) => console.warn("Error apply_filters:", e));
        return;
      }

      let actionName = isFiltering ? "filter" : "list";
      let ajaxData = {
        action: actionName,
        page: page,
      };

      if (isFiltering) {
        ajaxData.search = currentSearchQuery;
      }

      const params = new URLSearchParams(ajaxData);
      fetch(`${BASE_URL}/api/php/coasters.php?${params}`)
        .then((r) => r.json())
        .then((data) => {
          if (data.success) {
            let totalMsg = data.total || 0;
            $("#coaster-count").text(
              `Mostrando ${totalMsg} montaña${totalMsg !== 1 ? "s" : ""} rusa${totalMsg !== 1 ? "s" : ""}`,
            );
            displayCoasters(data.coasters);
            displayPagination(data.total, page);
          } else {
            document.getElementById("coaster-list").innerHTML =
              "<p style='color:red;'>Error cargando montañas rusas</p>";
          }
        })
        .catch((e) => console.warn("Error loadCoasters:", e));
    }

    async function loadFilters() {
      try {
        // Cargar fabricantes y países en paralelo
        const [mRes, cRes] = await Promise.all([
          fetch(`${BASE_URL}/api/php/coasters.php?action=manufacter`),
          fetch(`${BASE_URL}/api/php/coasters.php?action=country`),
        ]);
        const mData = await mRes.json();
        const cData = await cRes.json();

        if (mData.success) {
          mData.manufacters.forEach((m) => {
            const option = document.createElement("option");
            option.value = m.coaster_manufacter;
            option.textContent = m.coaster_manufacter;
            document.querySelector("#manufacter-filter").appendChild(option);
          });
        }
        if (cData.success) {
          cData.countries.forEach((c) => {
            const option = document.createElement("option");
            option.value = c.park_country;
            option.textContent = c.park_country;
            document.querySelector("#country-filter").appendChild(option);
          });
        }
      } catch (e) {
        console.warn("Error cargando filtros:", e);
      }
    }

    function applyFilters() {
      isAdvancedFiltering = true;
      isFiltering = false;
      loadCoasters(1);
    }

    function clearFilters() {
      isAdvancedFiltering = false;
      document.getElementById("height-filter").value = 0;
      document.getElementById("speed-filter").value = 0;
      document.getElementById("length-filter").value = 0;
      document.getElementById("inversions-filter").value = 0;
      document.getElementById("status-filter").checked = false;
      document.getElementById("ridden-filter").checked = false;
      document.getElementById("year-select").value = "";
      document.getElementById("manufacter-filter").value = "";
      document.getElementById("country-filter").value = "";

      document.getElementById("height-val").textContent = "0 m";
      document.getElementById("speed-val").textContent = "0 km/h";
      document.getElementById("length-val").textContent = "0 m";
      document.getElementById("inversions-val").textContent = "0";

      $("#coaster-search").val("");
      searchIcon
        .removeClass("fa-xmark text-danger")
        .addClass("fa-magnifying-glass text-muted")
        .css("cursor", "text");
      $("#search-results").html("").hide();
      isFiltering = false;
      currentSearchQuery = "";
      $("h1").text("Base de Datos de Montañas Rusas");

      loadCoasters(1);
    }

    function displayCoasters(coasters) {
      const container = document.querySelector("#coaster-list");
      container.innerHTML = "";
      coasters.forEach(function (coaster) {
        const coasterCard = document.createElement("a");
        coasterCard.href =
          BASE_URL + `/web/views/public/coasters/coasters.php?id=${coaster.id}`;
        coasterCard.classList.add(
          "list-group-item",
          "list-group-item-action",
          "d-flex",
          "align-items-center",
          "p-3",
        );

        const img = coaster.imagen_url
          ? `<img src="${coaster.imagen_url}" alt="${coaster.coaster_name}" class="rounded-0 shadow-sm" referrerpolicy="no-referrer" style="width: 100px; height: 100px; object-fit: cover; margin-right: 20px;">`
          : `<img src="https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg" alt="Sin imagen" class="rounded-0 shadow-sm" style="width: 100px; height: 100px; object-fit: cover; margin-right: 20px;">`;

        const manufacter = coaster.manufacter || "Desconocido";
        const modelo = coaster.modelo || "Desconocido";
        const year = coaster.opening_year || "N/A";

        coasterCard.innerHTML = `
        ${img}
        <div class="flex-grow-1">
          <h5 class="mb-1 fw-bold text-primary" style="font-size: 1.25rem;">${coaster.coaster_name}</h5>
          <p class="mb-1 text-muted"><i class="fa-solid fa-map-pin me-1"></i>${coaster.park_name}</p>
          <small class="text-secondary">${manufacter} • ${modelo} • ${year}</small>
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
    loadFilters();

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
        isAdvancedFiltering = false;
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
  }
  if (document.getElementById("coaster-name")) {
    // --- LÓGICA PARA CARGAR LA FICHA INDIVIDUAL ---
    const urlParams = new URLSearchParams(window.location.search);
    const coasterId = urlParams.get("id"); // Extrae el "id" de la URL

    // -------------------------------------
    // FUNCIÓN PARA FICHAS DE MONTAÑAS RUSAS
    // -------------------------------------
    let coasterName = document.getElementById("coaster-name");
    let parkName = document.getElementById("park-name");
    let parkNameTable = document.getElementById("park-name-table");
    let positionRank = document.getElementById("global-ranking");
    let puntuacion = document.getElementById("coaster-score");
    let personalRanking = document.getElementById("pesonal-ranking");
    let currentState = document.getElementById("current-state");
    let currentStateTable = document.getElementById("current-state-table");
    let isRidden = document.getElementById("coaster-ridden");
    let coasterHeight = document.getElementById("coaster-height");
    let coasterSpeed = document.getElementById("coaster-speed");
    let coasterLength = document.getElementById("coaster-length");
    let coasterInversions = document.getElementById("coaster-inversions");
    let coasterManufacter = document.getElementById("coaster-manufacter");
    let coasterModel = document.getElementById("coaster-model");
    let coasterYear = document.getElementById("coaster-year");

    async function loadCoastersData(id) {
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/coasters.php?action=coaster&id=${id}`,
        );
        const data = await res.json();
        if (data.success) {
          let coaster = data.coaster;

          // --- Información principal (Hero) ---
          if (coasterName) coasterName.textContent = coaster.coaster_name;
          if (parkName) parkName.textContent = coaster.park_name;

          // Nuevo: País (Hero)
          let coasterCountry = document.getElementById("coaster-country");
          if (coasterCountry)
            coasterCountry.textContent = coaster.park_country || "N/A";

          // Enlace dinámico del parque en el Hero
          let parkLink = document.getElementById("park-link");
          if (parkLink)
            parkLink.href =
              BASE_URL +
              `/web/views/public/parks/park_detail.php?id=${coaster.park_id}`;

          // --- Ficha técnica (Tabla) ---
          if (parkNameTable) {
            parkNameTable.textContent = coaster.park_name;
            parkNameTable.href =
              BASE_URL +
              `/web/views/public/parks/park_detail.php?id=${coaster.park_id}`;
          }

          // Estadísticas técnicas
          if (coasterHeight)
            coasterHeight.textContent = coaster.height
              ? coaster.height + "m"
              : "N/A";
          if (coasterSpeed)
            coasterSpeed.textContent = coaster.speed
              ? coaster.speed + " km/h"
              : "N/A";
          if (coasterLength)
            coasterLength.textContent = coaster.coaster_length
              ? coaster.coaster_length + "m"
              : "N/A";
          if (coasterInversions)
            coasterInversions.textContent = coaster.inversions || "0";

          // Datos de fabricación
          if (coasterManufacter)
            coasterManufacter.textContent =
              coaster.coaster_manufacter || "Desconocido";
          if (coasterModel)
            coasterModel.textContent = coaster.coaster_model || "Desconocido";
          if (coasterYear)
            coasterYear.textContent = coaster.opening_year || "N/A";

          // --- Rankings y Estados ---
          if (positionRank)
            positionRank.textContent = coaster.global_rank
              ? "#" + coaster.global_rank
              : "#" + coaster.id;
          if (puntuacion)
            puntuacion.textContent = coaster.score
              ? coaster.score + "%"
              : "N/A";
          if (personalRanking)
            personalRanking.textContent = coaster.personal_ranking
              ? "#" + coaster.personal_ranking
              : "—";

          if (currentState)
            currentState.textContent = coaster.status || "Operativa";
          if (currentStateTable)
            currentStateTable.textContent = coaster.status || "Operativa";

          // --- Multimedia ---
          if (coaster.imagen_url) {
            $("#coaster-hero-img")
              .attr("src", coaster.imagen_url)
              .attr("alt", coaster.coaster_name);
          }

          // --- Lógica del botón "Montada" (Visual) ---
          if (coaster.personal_ranking !== null) {
            $("#coaster-ridden")
              .removeClass("fa-regular fa-xmark text-success text-secondary")
              .addClass("fa-solid fa-circle-check text-white");
            $("#btn-ridden span").text("Montada");
            $("#btn-ridden")
              .removeClass("btn-outline-secondary btn-outline-success")
              .addClass("btn-success text-white");
          } else {
            $("#coaster-ridden")
              .removeClass("fa-solid fa-circle-check text-success text-white")
              .addClass("fa-solid fa-xmark text-secondary");
            $("#btn-ridden span").text("No montada");
            $("#btn-ridden")
              .removeClass(
                "btn-success text-white btn-outline-success btn-ridden-active",
              )
              .addClass("btn-outline-secondary");
          }
        } else {
          console.error("Error de la API: " + data.error);
        }
      } catch (e) {
        console.error("Error en loadCoastersData:", e);
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

    // --- LÓGICA PARA EL FORMULARIO DE FOTOS ---
    async function loadPhotos(id) {
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/coasters.php?action=photos&id=${id}`,
        );
        const data = await res.json();
        if (data.success) {
          $("#photos-count").text(data.total);
          if (data.photos.length === 0) {
            $("#photos-grid").html(
              '<p class="text-muted text-center py-3">Aún no hay fotos</p>',
            );
            return;
          }
          data.photos.forEach((photo) => {
            const col = document.createElement("div");
            col.className = "col-6 col-md-3";
            col.innerHTML = `<img src="${photo.photo_url}" alt="${photo.caption || "Foto"}" class="photo-thumb w-100" title="${photo.username}">`;
            document.querySelector("#photos-grid").appendChild(col);
          });
        }
      } catch (e) {
        console.error("Error cargando fotos:", e);
      }
    }

    async function loadReviews(order) {
      order = order || "default";
      $("#reviews-list").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin fs-3"></i></div>',
      );
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/coasters.php?action=reviews&id=${coasterId}&order=${order}`,
        );
        const data = await res.json();
        if (data.success) {
          $("#reviews-count").text(data.total);
          if (data.reviews.length === 0) {
            $("#reviews-list").html(
              '<p class="text-muted text-center py-4">Aún no hay reseñas para esta montaña rusa.</p>',
            );
            return;
          }
          $("#reviews-list").empty();
          data.reviews.forEach((review) => {
            let tagsHtml = "";
            if (review.tags && review.tags.length > 0) {
              tagsHtml = '<div class="d-flex flex-wrap gap-2 mt-2 mb-2">';
              review.tags.forEach((t) => {
                const cls = t.type === "pro" ? "success" : "danger";
                tagsHtml += `<span class="badge bg-${cls} bg-opacity-10 text-${cls} border border-${cls} border-opacity-25 rounded-pill px-3 py-1" style="font-weight:600;font-size:0.75rem;">${t.tag.replace(/_/g, " ").toUpperCase()}</span>`;
              });
              tagsHtml += "</div>";
            }
            $("#reviews-list").append(
              `<div class="border-bottom pb-3 mb-3">
              <div class="d-flex align-items-center gap-2 mb-1">
                <img src="${review.profile_image || "https://placehold.co/40x40"}" alt="${review.username}" class="review-avatar" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                <strong>${review.username}</strong>
                <span class="stars-display ms-2">${renderStars(review.note)}</span>
                <span class="text-muted small ms-2">• ${timeAgo(review.created_at)}</span>
              </div>
              ${tagsHtml}
              <p class="mb-0 mt-2">${review.review || ""}</p>
            </div>`,
            );
          });
        }
      } catch (e) {
        console.error("Error cargando reseñas:", e);
      }
    }

    if (coasterId) {
      loadCoastersData(coasterId);
      loadPhotos(coasterId);
      loadReviews();

      $("#reviews-order").on("change", function () {
        loadReviews($(this).val());
      });
    }

    const btnWriteReview = document.getElementById("btn-write-review");
    if (btnWriteReview) {
      btnWriteReview.addEventListener("click", function (e) {
        const isLogged =
          document.querySelector("main").getAttribute("data-logged") === "true";
        if (!isLogged) {
          e.preventDefault();
          const loginModal = new bootstrap.Modal(
            document.getElementById("loginModal"),
          );
          loginModal.show();
        }
      });
    }
  }

  // --- LÓGICA PARA EL FORMULARIO DE RESEÑAS ---
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
        const coasterId = formData.get("coaster_id");
        const note = formData.get("note");

        if (!note) {
          alert("Por favor, califica con estrellas la montaña rusa.");
          return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML =
          'Publicando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

        fetch(window.BASE_URL + "/api/php/coasters.php?action=save_review", {
          method: "POST",
          body: formData,
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.success) {
              window.location.href =
                window.BASE_URL +
                "/web/views/public/coasters/coasters.php?id=" +
                coasterId;
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

  // --- LÓGICA PARA EL MODAL DE FOTOS (Firebase + Cropper) ---
  if (document.getElementById("upload-photo-modal")) {
    let cropper = null;
    const photoInput = document.getElementById("photo");
    const cropPreview = document.getElementById("crop-preview");
    const cropContainer = document.getElementById("crop-container");
    const uploadBtn = document.getElementById("upload-photo-btn");

    // Al seleccionar archivo, mostrar previsualización e iniciar Cropper
    photoInput.addEventListener("change", function () {
      const file = this.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (e) {
        cropPreview.src = e.target.result;
        cropContainer.style.display = "block";

        if (cropper) cropper.destroy();
        cropper = new Cropper(cropPreview, {
          aspectRatio: 16 / 9,
          viewMode: 1,
        });
      };
      reader.readAsDataURL(file);
    });

    // Al pulsar subir
    uploadBtn.addEventListener("click", async function () {
      if (!cropper) {
        alert("Por favor selecciona una foto primero.");
        return;
      }

      // Desactivar botón mientras sube
      uploadBtn.disabled = true;
      uploadBtn.innerHTML =
        'Subiendo... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

      try {
        // 1. Obtener imagen recortada como Blob
        cropper.getCroppedCanvas({ width: 1280, height: 720 }).toBlob(
          async function (blob) {
            if (!blob) throw new Error("Error al recortar la imagen");

            const ext = photoInput.files[0].name.split(".").pop();
            const filename = `${Date.now()}_img.${ext}`;

            // 2. Subir a Supabase Storage via PHP proxy
            const uploadForm = new FormData();
            uploadForm.append("file", blob, filename);
            uploadForm.append("bucket", "coasters");
            uploadForm.append("path", coasterId);

            const uploadRes = await fetch(
              `${window.BASE_URL}/api/php/upload.php`,
              {
                method: "POST",
                body: uploadForm,
              },
            );
            const uploadData = await uploadRes.json();
            if (!uploadData.success)
              throw new Error(uploadData.error || "Error al subir la foto");
            const url = uploadData.url;

            // 3. Guardar en PostgreSQL vía API PHP
            const captionVal = document.getElementById("photo-caption").value;

            const photoForm = new FormData();
            photoForm.append("coaster_id", coasterId);
            photoForm.append("photo_url", url);
            photoForm.append("caption", captionVal);

            try {
              const saveRes = await fetch(
                `${window.BASE_URL}/api/php/coasters.php?action=save_photo`,
                {
                  method: "POST",
                  body: photoForm,
                },
              );
              const saveData = await saveRes.json();
              if (saveData.success) {
                const modalEl = document.getElementById("upload-photo-modal");
                bootstrap.Modal.getInstance(modalEl).hide();
                document.getElementById("upload-photo-form").reset();
                cropContainer.style.display = "none";
                if (cropper) {
                  cropper.destroy();
                  cropper = null;
                }
                alert("¡Foto enviada! Esperando aprobación del administrador");
              } else {
                alert(
                  "Error al guardar la foto: " +
                    (saveData.error || "Desconocido"),
                );
              }
            } catch (saveErr) {
              alert("Error en la conexión con la API.");
              console.error(saveErr);
            } finally {
              uploadBtn.disabled = false;
              uploadBtn.innerHTML =
                'Subir foto <i class="fa-solid fa-upload ms-1"></i>';
            }
          },
          "image/jpeg",
          0.85,
        ); // Exportar en JPG con calidad 85%
      } catch (err) {
        console.error("Error subiendo foto:", err);
        alert("Ocurrió un error al subir la foto.");
        uploadBtn.disabled = false;
        uploadBtn.innerHTML =
          'Subir foto <i class="fa-solid fa-upload ms-1"></i>';
      }
    });
  }
});
