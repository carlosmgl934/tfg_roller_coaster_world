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

    // Listener para el selector de ordenación
    const sortFilter = document.getElementById("sort-filter");
    const sortDirectionBtn = document.getElementById("sort-direction-btn");
    const sortDirectionInput = document.getElementById("sort-direction");

    // Cargar orden guardado o usar ASC por defecto
    if (sortFilter && sortDirectionInput) {
      const savedSort = localStorage.getItem("coasters_sort");
      const savedDir = localStorage.getItem("coasters_sort_dir") || "ASC";

      if (savedSort) {
        sortFilter.value = savedSort;
      }
      sortDirectionInput.value = savedDir;
      if (sortDirectionBtn) updateSortIcon(savedDir);

      sortFilter.addEventListener("change", function () {
        const val = this.value;
        let defaultDir = "ASC"; // Cambiado a ascendente por defecto general
        if (["height", "speed"].includes(val)) {
          defaultDir = "DESC";
        }

        sortDirectionInput.value = defaultDir;
        if (sortDirectionBtn) updateSortIcon(defaultDir);

        localStorage.setItem("coasters_sort", val);
        localStorage.setItem("coasters_sort_dir", defaultDir);

        loadCoasters(1);
      });
    }

    if (sortDirectionBtn && sortDirectionInput) {
      sortDirectionBtn.addEventListener("click", function () {
        const currentDir = sortDirectionInput.value;
        const newDir = currentDir === "ASC" ? "DESC" : "ASC";
        sortDirectionInput.value = newDir;
        updateSortIcon(newDir);

        localStorage.setItem("coasters_sort_dir", newDir);

        loadCoasters(1);
      });
    }

    function updateSortIcon(dir) {
      const icon = sortDirectionBtn.querySelector("i");
      if (dir === "ASC") {
        icon.className = "fa-solid fa-arrow-up-wide-short";
      } else {
        icon.className = "fa-solid fa-arrow-down-wide-short";
      }
    }

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

    // Keyboard navigation
    $("#coaster-search").on("keydown", function (e) {
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

    let currentPage = 1;
    let currentSearchQuery = "";
    let isFiltering = false;
    let isAdvancedFiltering = false;

    function loadCoasters(page) {
      currentPage = page;
      const sort =
        document.getElementById("sort-filter")?.value ||
        localStorage.getItem("coasters_sort") ||
        "id";
      const orderDir =
        document.getElementById("sort-direction")?.value ||
        localStorage.getItem("coasters_sort_dir") ||
        "ASC";

      if (isAdvancedFiltering) {
        let opened = document.getElementById("status-filter").checked;
        let ridden = document.getElementById("ridden-filter").checked;
        let height = document.getElementById("height-filter").value;
        let speed = document.getElementById("speed-filter").value;
        let length = document.getElementById("length-filter").value;
        let inversions = document.getElementById("inversions-filter").value;
        let manufacter = document.getElementById("manufacter-filter").value;
        let country = document.getElementById("country-filter").value;
        let parkId = document.getElementById("park-filter").value;
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
          park_id: parkId,
          year,
          search,
          sort,
          order_dir: orderDir,
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
      let ajaxData = { action: actionName, page, sort, order_dir: orderDir };

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

        // Inicializar autocompletado de parques (sustituye a la carga masiva)
        initAutocomplete({
          inputId: "filter-park-search",
          dropdownId: "filter-park-results",
          fetchItems: async (q) => {
            const url = `${BASE_URL}/api/php/parks.php?action=list&limit=50${q ? "&q=" + encodeURIComponent(q) : "&sort=name"}`;
            const res = await fetch(url);
            const data = await res.json();
            if (!data.success) return [];
            return data.data.map((p) => ({
              label: p.park_name,
              sublabel: p.park_country || "",
              value: p.park_name,
              id: p.id,
            }));
          },
          onSelect: (item) => {
            document.getElementById("park-filter").value = item.id;
          },
        });
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
      document.getElementById("park-filter").value = "";
      document.getElementById("filter-park-search").value = "";
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

        let validImgUrl = coaster.imagen_url;
        if (validImgUrl && !validImgUrl.startsWith("http")) {
          validImgUrl =
            BASE_URL + (validImgUrl.startsWith("/") ? "" : "/") + validImgUrl;
        }

        const img = validImgUrl
          ? `<img src="${validImgUrl}" alt="${coaster.coaster_name}" class="rounded-0 shadow-sm" referrerpolicy="no-referrer" style="width: 110px; height: 110px; object-fit: cover; margin-right: 20px; flex-shrink:0;">`
          : `<img src="https://www.hussrides.com/fileadmin/_processed_/5/e/csm_giant-frisbee-cedarpoint-01_0697df513a.jpg" alt="Sin imagen" class="rounded-0 shadow-sm" style="width: 110px; height: 110px; object-fit: cover; margin-right: 20px; flex-shrink:0;">`;

        const manufacter = coaster.manufacter || "Desconocido";
        const modelo = coaster.modelo || "Desconocido";
        const year = coaster.opening_year || "N/A";

        const scoreText = parseFloat(coaster.score || 0).toFixed(2) + " ★";
        coasterCard.innerHTML = `
        ${img}
        <div class="flex-grow-1">
          <h5 class="mb-1 fw-bold text-primary" style="font-size: 1.25rem;">${coaster.coaster_name}</h5>
          <p class="mb-1 text-muted"><i class="fa-solid fa-map-pin me-1"></i>${coaster.park_name}</p>
          <small class="text-secondary">${manufacter} • ${modelo} • ${year} • ${scoreText}</small>
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
      pageBtn.classList.add(
        "page-buttons",
        "d-flex",
        "flex-nowrap",
        "justify-content-center",
        "gap-1",
      );
      const totalPages = Math.ceil(total / 15);
      const prevBtn = document.createElement("button");
      prevBtn.className = "btn btn-outline-success";
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
      btnFirst.className = "btn btn-success";
      btnFirst.textContent = "1";
      btnFirst.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadCoasters(1);
      });
      pageBtn.appendChild(btnFirst);

      const btnDots = document.createElement("button");
      btnDots.className = "btn border-0 text-secondary";
      btnDots.textContent = "...";
      btnDots.disabled = true;
      pageBtn.appendChild(btnDots);

      let start = Math.max(2, page - 1);
      let end = Math.min(totalPages - 1, start + 2);
      start = Math.max(1, end - 2);

      for (let i = start; i <= end; i++) {
        const pageButton = document.createElement("button");
        pageButton.className = "btn btn-light text-success border";
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
      btnDots2.className = "btn border-0 text-secondary";
      btnDots2.textContent = "...";
      btnDots2.disabled = true;
      pageBtn.appendChild(btnDots2);

      const btnLast = document.createElement("button");
      btnLast.className = "btn btn-success";
      btnLast.textContent = `${totalPages}`;
      btnLast.addEventListener("click", function () {
        window.scrollTo({ top: 10, behavior: "smooth" });
        loadCoasters(totalPages);
      });
      pageBtn.appendChild(btnLast);

      const nextBtn = document.createElement("button");
      nextBtn.className = "btn btn-outline-success";
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
              `/web/views/public/parks/parks.php?id=${coaster.park_id}`;

          // --- Ficha técnica (Tabla) ---
          if (parkNameTable) {
            parkNameTable.textContent = coaster.park_name;
            parkNameTable.href =
              BASE_URL +
              `/web/views/public/parks/parks.php?id=${coaster.park_id}`;
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
              : "—";
          if (puntuacion)
            puntuacion.textContent = parseFloat(coaster.score || 0).toFixed(2);
          if (personalRanking)
            personalRanking.textContent = coaster.personal_ranking
              ? "#" + coaster.personal_ranking
              : "—";

          if (currentState) {
            let statusText = coaster.coaster_status || "Operativa";
            $(currentState).removeClass(
              "text-success text-danger text-warning text-info text-secondary",
            );
            if (statusText === "Operating" || statusText === "Operativa") {
              currentState.textContent = "Operativa";
              $(currentState).addClass("text-success");
            } else if (
              statusText === "Defunct" ||
              statusText === "Closed" ||
              statusText === "Cerrada"
            ) {
              currentState.textContent = "Cerrada";
              $(currentState).addClass("text-danger");
            } else if (statusText === "SBNO") {
              currentState.textContent = "SBNO";
              $(currentState).addClass("text-warning");
            } else if (
              statusText === "Construction" ||
              statusText === "En Construcción" ||
              statusText === "En construcción"
            ) {
              currentState.textContent = "En Construcción";
              $(currentState).addClass("text-info");
            } else {
              currentState.textContent = statusText.toUpperCase();
              $(currentState).addClass("text-info");
            }
          }
          if (currentStateTable) {
            let statusText = coaster.coaster_status || "Operativa";
            let finalStatus = statusText;
            if (statusText === "Operating" || statusText === "Operativa")
              finalStatus = "Operativa";
            else if (
              statusText === "Defunct" ||
              statusText === "Closed" ||
              statusText === "Cerrada"
            )
              finalStatus = "Cerrada";
            else if (
              statusText === "Construction" ||
              statusText === "En Construcción" ||
              statusText === "En construcción"
            )
              finalStatus = "En Construcción";
            currentStateTable.textContent = finalStatus;
          }

          // --- Multimedia ---
          if (coaster.imagen_url) {
            let validImgUrl = coaster.imagen_url;
            if (!validImgUrl.startsWith("http")) {
              validImgUrl =
                BASE_URL +
                (validImgUrl.startsWith("/") ? "" : "/") +
                validImgUrl;
            }
            $("#coaster-hero-img")
              .attr("src", validImgUrl)
              .attr("alt", coaster.coaster_name);
          }

          // --- Lógica del botón "Probada" (Visual) ---
          if (coaster.personal_ranking !== null) {
            $("#coaster-ridden")
              .removeClass("fa-regular fa-xmark text-success text-secondary")
              .addClass("fa-solid fa-circle-check text-white");
            $("#btn-ridden span").text("Ya probada");
            $("#btn-ridden")
              .removeClass("btn-outline-secondary btn-outline-success")
              .addClass("btn-success text-white");
          } else {
            $("#coaster-ridden")
              .removeClass("fa-solid fa-circle-check text-success text-white")
              .addClass("fa-solid fa-xmark text-secondary");
            $("#btn-ridden span").text("No probada todavía");
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
          const coasterNameEl = document.getElementById("coaster-name");
          const coasterName = coasterNameEl ? coasterNameEl.textContent : "";

          data.photos.forEach((photo, index) => {
            const userKey = window.CURRENT_USER_ID || "guest";
            const hasLiked =
              localStorage.getItem(
                "liked_photo_" + userKey + "_" + photo.id,
              ) === "true";
            const heartClass = hasLiked ? "fa-solid text-danger" : "fa-regular";

            const col = document.createElement("div");
            col.className = "col-6 col-sm-4 col-md-3 col-lg-2 mb-4";
            col.innerHTML = `
                        <div class="photo-square-container position-relative overflow-hidden w-100" 
                        style="padding-bottom: 100%; border-radius: 10px; cursor: pointer;"
                        data-id="${photo.id}"
                        data-index="${index}"
                        data-url="${photo.photo_url}"
                        data-username="${photo.username}"
                        data-avatar="${window.rcwGetAvatarPath(photo.profile_image, photo.username)}"
                        data-caption="${photo.caption || ""}"
                        data-likes="${photo.likes || 0}">
                       <img src="${photo.photo_url}" alt="${photo.caption || "Foto"}" class="position-absolute w-100 h-100" style="object-fit: cover; top:0; left:0; transition: transform 0.3s ease;">
                       
                       <!-- Likes Overlay Badge -->
                       <div class="position-absolute top-0 end-0 m-2" style="z-index: 10;">
                           <button class="btn grid-like-btn d-flex align-items-center gap-1 px-2 py-1" 
                                   data-id="${photo.id}" 
                                   style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.15); border-radius: 20px; font-size: 0.75rem; color: #fff; line-height: 1;">
                               <i class="${heartClass} fa-heart ${photo.user_has_liked ? "text-danger" : ""}"></i>
                               <span class="grid-likes-count fw-bold">${photo.likes || 0}</span>
                           </button>
                       </div>
                   </div>
                   <div class="mt-2 px-1">
                       <div class="text-white-50 text-truncate w-100" style="font-size: 0.82rem; font-weight: 500;" title="${photo.username}">
                           <i class="fa-solid fa-user fa-xs me-1 opacity-50"></i>${photo.username}
                       </div>
                   </div>
            `;
            document.querySelector("#photos-grid").appendChild(col);
          });

          // Ocultar el botón si hay pocas fotos (umbral diferente para PC y móvil)
          const isMobile = window.innerWidth < 992;
          const threshold = isMobile ? 4 : 6;
          if (data.photos.length <= threshold) {
            $("#btn-view-all-photos").hide();
          }
        }
      } catch (e) {
        console.error("Error cargando fotos:", e);
      }
    }

    // Handler para expandir/contraer fotos
    $("#btn-view-all-photos").on("click", function (e) {
      e.preventDefault();
      const grid = $("#photos-grid");
      if (grid.hasClass("expanded")) {
        grid.removeClass("expanded");
        $(this).html(
          'Ver todas las fotos <i class="fa-solid fa-arrow-right ms-1"></i>',
        );
      } else {
        grid.addClass("expanded");
        $(this).html(
          'Contraer fotos <i class="fa-solid fa-arrow-up ms-1"></i>',
        );
      }
    });

    // Handlers para Lightbox IG y Likes
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
      const likes = el.data("likes");
      const userKey = window.CURRENT_USER_ID || "guest";
      const hasLiked =
        localStorage.getItem("liked_photo_" + userKey + "_" + id) === "true";

      $("#ig-modal-img").attr("src", url);
      $("#ig-modal-avatar").attr("src", avatar);
      $("#ig-modal-username").text(username);

      if (caption) {
        $("#ig-modal-caption-user").text(username);
        $("#ig-modal-caption").html(
          `<span class="text-muted opacity-50 mx-1">&bull;</span> ${caption}`,
        );
      } else {
        $("#ig-modal-caption-user").text("");
        $("#ig-modal-caption").text("");
      }

      $("#ig-modal-likes").text(likes + " me gusta");

      const btn = $("#ig-modal-like-btn");
      btn.data("id", id);
      if (hasLiked) {
        btn.html('<i class="fa-solid fa-heart text-danger"></i>');
      } else {
        btn.html('<i class="fa-regular fa-heart"></i>');
      }

      $("#ig-modal-prev").toggle(index > 0);
      $("#ig-modal-next").toggle(index < allPhotosList.length - 1);
    }

    $("#photos-grid").on("click", ".photo-square-container", function () {
      const index = $(this).data("index");
      // Fallback in case index is not present (although we added it above)
      if (index !== undefined) {
        updateModalContent(index);
      } else {
        // Backward compatibility behavior if needed, but it should be set
        const id = $(this).data("id");
        const url = $(this).data("url");
        const username = $(this).data("username");
        const avatar = $(this).data("avatar");
        const caption = $(this).data("caption");
        const likes = $(this).data("likes");
        const userKey = window.CURRENT_USER_ID || "guest";
        const hasLiked =
          localStorage.getItem("liked_photo_" + userKey + "_" + id) === "true";

        $("#ig-modal-img").attr("src", url);
        $("#ig-modal-avatar").attr("src", avatar);
        $("#ig-modal-username").text(username);

        if (caption) {
          $("#ig-modal-caption-user").text(username);
          $("#ig-modal-caption").html(
            `<span class="text-muted opacity-50 mx-1">&bull;</span> ${caption}`,
          );
        } else {
          $("#ig-modal-caption-user").text("");
          $("#ig-modal-caption").text("");
        }

        $("#ig-modal-likes").text(likes + " me gusta");

        const btn = $("#ig-modal-like-btn");
        btn.data("id", id);
        if (hasLiked) {
          btn.html('<i class="fa-solid fa-heart text-danger"></i>');
        } else {
          btn.html('<i class="fa-regular fa-heart"></i>');
        }
        $("#ig-modal-prev").hide();
        $("#ig-modal-next").hide();
      }

      new bootstrap.Modal(document.getElementById("ig-lightbox-modal")).show();
    });

    $("#ig-modal-prev")
      .off("click")
      .on("click", function () {
        updateModalContent(currentPhotoIndex - 1);
      });

    $("#ig-modal-next")
      .off("click")
      .on("click", function () {
        updateModalContent(currentPhotoIndex + 1);
      });

    // Función única para gestionar likes (Sincronizada y con protección anti-doble click)
    async function togglePhotoLike(photoId, btnElement) {
      if (!photoId || btnElement.prop("disabled")) return;

      const userKey = window.CURRENT_USER_ID || "guest";
      const hasLiked =
        localStorage.getItem("liked_photo_" + userKey + "_" + photoId) ===
        "true";

      // Bloquear todos los botones relacionados con esta foto para evitar spam
      const gridBtns = $(`.grid-like-btn[data-id='${photoId}']`);
      const modalBtn = $("#ig-modal-like-btn");

      const allRelatedBtns = gridBtns.add(modalBtn);
      allRelatedBtns.prop("disabled", true).addClass("opacity-50");

      try {
        const formData = new FormData();
        formData.append("photo_id", photoId);
        formData.append("unlike", hasLiked);

        const res = await fetch(
          `${window.BASE_URL}/api/php/coasters.php?action=like_photo`,
          {
            method: "POST",
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
            body: formData,
          },
        );
        const data = await res.json();

        if (data.success) {
          const newHasLiked = !hasLiked;
          if (newHasLiked) {
            localStorage.setItem(
              "liked_photo_" + userKey + "_" + photoId,
              "true",
            );
          } else {
            localStorage.removeItem("liked_photo_" + userKey + "_" + photoId);
          }

          // 1. Actualizar el Grid (todos los botones de esa foto)
          gridBtns.each(function () {
            const icon = $(this).find("i");
            if (newHasLiked) {
              icon.removeClass("fa-regular").addClass("fa-solid text-danger");
            } else {
              icon.removeClass("fa-solid text-danger").addClass("fa-regular");
            }
            $(this).find(".grid-likes-count").text(data.likes);
          });

          // 2. Sincronizar el dataset de la foto (para que el modal herede los likes actuales si se abre)
          $(`.photo-square-container[data-id='${photoId}']`).data(
            "likes",
            data.likes,
          );

          // 3. Sincronizar el Modal (si es la foto que estamos viendo)
          if (modalBtn.data("id") == photoId) {
            modalBtn.html(
              newHasLiked
                ? '<i class="fa-solid fa-heart text-danger"></i>'
                : '<i class="fa-regular fa-heart"></i>',
            );
            $("#ig-modal-likes").text(data.likes + " me gusta");
          }
        }
      } catch (e) {
        console.error("Error toggle like:", e);
      } finally {
        allRelatedBtns.prop("disabled", false).removeClass("opacity-50");
      }
    }

    $("#ig-modal-like-btn").on("click", function () {
      const id = $(this).data("id");
      togglePhotoLike(id, $(this));
    });

    // Lógica para dar a like directamente desde el grid
    $("#photos-grid").on("click", ".grid-like-btn", function (e) {
      e.stopPropagation(); // Evitar que se abra el lightbox al dar like
      const id = $(this).data("id");
      togglePhotoLike(id, $(this));
    });

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
                tagsHtml += `<span class="badge bg-${cls} text-white rounded-pill px-3 py-1" style="font-weight:600;font-size:0.75rem;">${t.tag.replace(/_/g, " ").toUpperCase()}</span>`;
              });
              tagsHtml += "</div>";
            }

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

            $("#reviews-list").append(
              `<div class="border-bottom pb-4 mb-4${isOwn ? " own-review" : ""}">
              <div class="d-flex align-items-start gap-3 mb-2">
                <img src="${window.rcwGetAvatarPath(review.profile_image, review.username)}" alt="${review.username}" class="review-avatar shadow-sm" style="width:50px;height:50px;object-fit:cover;border-radius:50%;background:#2d333b; border: 2px solid rgba(255,255,255,0.05);" onerror="this.src='${window.BASE_URL}/web/img/avatars/default_avatar.svg';this.onerror=null;">
                <div class="flex-grow-1 min-w-0">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                    <strong class="text-white fs-6 text-truncate flex-grow-1 min-w-0" title="${review.username}">${review.username}</strong>
                    ${editBtn}
                  </div>
                  <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="stars-display lh-1">${renderStars(review.note)}</span>
                    <span class="text-muted small">• ${timeAgo(review.created_at)}</span>
                  </div>
                </div>
              </div>
              ${tagsHtml}
              <div class="mt-3 p-3 bg-dark bg-opacity-25 rounded border-start border-3 border-success border-opacity-50">
                <p class="mb-0 text-white-50" style="font-size:0.92rem; line-height:1.7;">${review.review || ""}</p>
              </div>
            </div>`,
            );
          });
        }
      } catch (e) {
        console.error("Error cargando reseñas:", e);
      }
    }

    // ── Lógica modal de edición de reseña (coasters) ─────────────────────────
    // SFTP Sync Trigger
    let editReviewModal = null;
    const editModalEl = document.getElementById("edit-review-modal");
    if (editModalEl && typeof bootstrap !== "undefined") {
      editReviewModal = new bootstrap.Modal(editModalEl);
    }

    let editProsChoices = null;
    let editContrasChoices = null;

    function initEditChoices() {
      try {
        if (typeof Choices === "undefined") {
          console.error("Choices.js not loaded!");
          return;
        }
        if (!editProsChoices && document.getElementById("edit-pros-select")) {
          editProsChoices = new Choices("#edit-pros-select", {
            removeItemButton: true,
            placeholderValue: "Selecciona las ventajas...",
            noChoicesText: "No hay más opciones",
            itemSelectText: "Presiona para seleccionar",
          });
        }
        if (
          !editContrasChoices &&
          document.getElementById("edit-contras-select")
        ) {
          editContrasChoices = new Choices("#edit-contras-select", {
            removeItemButton: true,
            placeholderValue: "Selecciona las contras...",
            noChoicesText: "No hay más opciones",
            itemSelectText: "Presiona para seleccionar",
          });
        }
      } catch (e) {
        console.error("Error initializing Choices.js:", e);
      }
    }

    // Actualizar nota oculta cuando cambia el radio
    $(document).on("change", 'input[name="edit_note"]', function () {
      $("#edit-review-note").val($(this).val());
    });

    // Abrir modal al pulsar lápiz
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

      if (editReviewModal) editReviewModal.show();
    });

    // Guardar cambios
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
          `${BASE_URL}/api/php/coasters.php?action=update_review`,
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
          if (editReviewModal) editReviewModal.hide();
          loadReviews($("#reviews-order").val() || "default");
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

    if (coasterId) {
      loadCoastersData(coasterId);
      loadPhotos(coasterId);
      loadReviews();

      $("#reviews-order").on("change", function () {
        loadReviews($(this).val());
      });
    }
  }

  // --- LÓGICA PARA EL FORMULARIO DE RESEÑAS ---
  if (document.getElementById("review-form")) {
    const rf = document.getElementById("review-form");
    const coasterIdInput = rf.querySelector('input[name="coaster_id"]');
    if (coasterIdInput) {
      const cId = coasterIdInput.value;
      fetch(`${BASE_URL}/api/php/coasters.php?action=check_review&id=${cId}`)
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
    const coasterId = new URLSearchParams(window.location.search).get("id");
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
          aspectRatio: 1,
          viewMode: 1,
        });
      };
      reader.readAsDataURL(file);
    });

    // Helper: muestra el modal de notificación
    function showNotify(title, message, isError = false) {
      document.getElementById("notify-modal-title").textContent = title;
      document.getElementById("notify-modal-body").textContent = message;
      const header = document.getElementById("notify-modal-header");
      header.className = isError
        ? "modal-header border-secondary pb-0 bg-danger"
        : "modal-header border-secondary pb-0 bg-success";
      new bootstrap.Modal(document.getElementById("notify-modal")).show();
    }

    // Al pulsar subir
    uploadBtn.addEventListener("click", async function () {
      if (!cropper) {
        showNotify(
          "Selecciona una foto",
          "Por favor selecciona y recorta una foto primero.",
          true,
        );
        return;
      }

      // Desactivar botón mientras sube
      uploadBtn.disabled = true;
      uploadBtn.innerHTML =
        'Subiendo... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

      // 1. Obtener imagen recortada como Blob
      cropper.getCroppedCanvas({ width: 1080, height: 1080 }).toBlob(
        async function (blob) {
          try {
            if (!blob) throw new Error("Error al recortar la imagen");

            const rawExt = photoInput.files[0].name.split(".").pop();
            const ext = rawExt.replace(/[^a-zA-Z0-9]/g, ""); // Anti Safari exception
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
                headers: {
                  "X-CSRF-Token":
                    document
                      .querySelector('meta[name="csrf-token"]')
                      ?.getAttribute("content") ?? "",
                },
                body: uploadForm,
              },
            );

            // Leer como texto primero para detectar errores del servidor
            const rawText = await uploadRes.text();
            let uploadData;
            try {
              uploadData = JSON.parse(rawText);
            } catch (e) {
              throw new Error(
                "El servidor devolvió una respuesta inválida: " +
                  rawText.substring(0, 200),
              );
            }

            if (!uploadData.success) {
              throw new Error(uploadData.error || "Error al subir la foto");
            }
            const url = uploadData.url;

            // 3. Guardar en PostgreSQL vía API PHP
            const captionVal = document.getElementById("photo-caption").value;

            const photoForm = new FormData();
            photoForm.append("coaster_id", coasterId);
            photoForm.append("photo_url", url);
            photoForm.append("caption", captionVal);

            const saveRes = await fetch(
              `${window.BASE_URL}/api/php/coasters.php?action=save_photo`,
              {
                method: "POST",
                headers: {
                  "X-CSRF-Token":
                    document
                      .querySelector('meta[name="csrf-token"]')
                      ?.getAttribute("content") ?? "",
                },
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
              showNotify(
                "¡Foto enviada!",
                "Tu foto está esperando aprobación del administrador. La verás publicada pronto.",
              );
            } else {
              showNotify(
                "Error al guardar",
                "Error al guardar la foto: " +
                  (saveData.error || "Desconocido"),
                true,
              );
            }
          } catch (err) {
            console.error("Error subiendo foto:", err);
            showNotify(
              "Error al subir",
              err.message || "Ocurrió un error inesperado.",
              true,
            );
          } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML =
              'Subir foto <i class="fa-solid fa-upload ms-1"></i>';
          }
        },
        "image/jpeg",
        0.85,
      ); // Exportar en JPG con calidad 85%
    });
  }

  function initAutocomplete({ inputId, dropdownId, fetchItems, onSelect }) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    if (!input || !dropdown) return;

    let debounce = null;
    let activeIdx = -1;
    let items = [];

    function renderItems(list) {
      items = list;
      activeIdx = -1;
      if (!list.length) {
        dropdown.innerHTML = '<div class="ac-empty">Sin resultados</div>';
        dropdown.classList.remove("d-none");
        return;
      }
      dropdown.innerHTML = list
        .map(
          (item, i) =>
            `<div class="ac-item" data-idx="${i}">${item.label}${item.sublabel ? `<span class="ac-sublabel">${item.sublabel}</span>` : ""}</div>`,
        )
        .join("");
      dropdown.classList.remove("d-none");
      dropdown.querySelectorAll(".ac-item").forEach((el) => {
        el.addEventListener("mousedown", (e) => {
          e.preventDefault();
          selectItem(parseInt(el.dataset.idx));
        });
      });
    }

    function selectItem(idx) {
      const item = items[idx];
      if (!item) return;
      input.value = item.label;
      onSelect(item);
      closeDropdown();
    }

    function closeDropdown() {
      dropdown.classList.add("d-none");
      dropdown.innerHTML = "";
      activeIdx = -1;
      items = [];
    }

    input.addEventListener("input", () => {
      clearTimeout(debounce);
      debounce = setTimeout(async () => {
        const q = input.value.trim();
        const list = await fetchItems(q);
        if (document.activeElement === input) renderItems(list);
      }, 200);
    });

    input.addEventListener("click", async () => {
      const list = await fetchItems(input.value.trim());
      renderItems(list);
    });

    input.addEventListener("keydown", (e) => {
      const visibleItems = dropdown.querySelectorAll(".ac-item");
      if (e.key === "ArrowDown") {
        e.preventDefault();
        activeIdx = Math.min(activeIdx + 1, visibleItems.length - 1);
        visibleItems.forEach((el, i) =>
          el.classList.toggle("ac-active", i === activeIdx),
        );
        if (visibleItems[activeIdx])
          visibleItems[activeIdx].scrollIntoView({ block: "nearest" });
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        activeIdx = Math.max(activeIdx - 1, 0);
        visibleItems.forEach((el, i) =>
          el.classList.toggle("ac-active", i === activeIdx),
        );
        if (visibleItems[activeIdx])
          visibleItems[activeIdx].scrollIntoView({ block: "nearest" });
      } else if (e.key === "Enter") {
        e.preventDefault();
        if (activeIdx >= 0) selectItem(activeIdx);
        else if (items.length === 1) selectItem(0);
        else closeDropdown();
      } else if (e.key === "Escape") {
        closeDropdown();
        input.blur();
      }
    });

    input.addEventListener("blur", () => {
      setTimeout(() => closeDropdown(), 150);
    });

    document.addEventListener("click", (e) => {
      if (!input.contains(e.target) && !dropdown.contains(e.target))
        closeDropdown();
    });
  }

  // --- COMPARTIR ---
  $("#btn-share").on("click", async function () {
    const title =
      document.getElementById("coaster-name")?.textContent ||
      "RollerCoaster World";
    const text = `Mira esta montaña rusa en RollerCoaster World: ${title}`;
    const url = window.location.href;

    if (navigator.share) {
      try {
        await navigator.share({ title, text, url });
      } catch (err) {
        if (err.name !== "AbortError") console.error("Error sharing:", err);
      }
    } else {
      try {
        await navigator.clipboard.writeText(url);
        if (window.rcwToast)
          window.rcwToast("Enlace copiado al portapapeles", "success");
        else alert("Enlace copiado al portapapeles");
      } catch (err) {
        console.error("Error copying:", err);
      }
    }
  });
});
