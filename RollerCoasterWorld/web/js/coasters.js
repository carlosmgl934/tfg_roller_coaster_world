$(document).ready(function () {
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

  document.getElementById("btn-borrar").addEventListener("click", clearFilters);

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
              <a href="${BASE_URL}/web/views/public/coasters.php?id=${coaster.id}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
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

      $.ajax({
        url: BASE_URL + "/api/php/coasters.php",
        type: "GET",
        data: {
          action: "apply_filters",
          page: page,
          opened: opened,
          ridden: ridden,
          height: height,
          speed: speed,
          length: length,
          inversions: inversions,
          manufacter: manufacter,
          country: country,
          year: year,
          search: search,
        },
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

  function loadFilters() {
    $.ajax({
      url: BASE_URL + "/api/php/coasters.php",
      type: "GET",
      data: { action: "manufacter" },
      success: function (data) {
        if (data.success) {
          data.manufacters.forEach(function (manufacter) {
            const option = document.createElement("option");
            option.value = manufacter.coaster_manufacter;
            option.textContent = manufacter.coaster_manufacter;
            document.querySelector("#manufacter-filter").appendChild(option);
          });
        }
      },
    });

    $.ajax({
      url: BASE_URL + "/api/php/coasters.php",
      type: "GET",
      data: { action: "country" },
      success: function (data) {
        if (data.success) {
          data.countries.forEach(function (country) {
            const option = document.createElement("option");
            option.value = country.park_country;
            option.textContent = country.park_country;
            document.querySelector("#country-filter").appendChild(option);
          });
        }
      },
    });
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
        BASE_URL + `/web/views/public/coasters.php?id=${coaster.id}`;
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




// -------------------------------------
// FUNCIÓN PARA FICHAS DE MONTAÑAS RUSAS
// -------------------------------------
let coasterName = document.getElementById("coaster-name")
let parkName = document.getElementById("park-name")
let parkNameTable = document.getElementById("park-name-table")
let positionRank = document.getElementById("global-ranking")
let puntuacion = document.getElementById("coaster-score")
let personalRanking = document.getElementById("pesonal-ranking")
let currentState = document.getElementById("current-state")
let currentStateTable = document.getElementById("current-state-table")
let isRidden = document.getElementById("coaster-ridden")
let coasterHeight = document.getElementById("coaster-height")
let coasterSpeed = document.getElementById("coaster-speed")
let coasterLength = document.getElementById("coaster-length")
let coasterInversions = document.getElementById("coaster-inversions")
let coasterManufacter = document.getElementById("coaster-manufacter")
let coasterModel = document.getElementById("coaster-model")
let coasterYear = document.getElementById("coaster-year")


function loadCoastersData(id) { 
    $.ajax({
        url: BASE_URL + "/api/php/coasters.php",
        type: "GET",
        data: {
            action: "coaster",
            id: id
        },
        dataType: "json",
        success: function (data) {
            if (data.success) {
                let coaster = data.coaster;

                // --- Información principal (Hero) ---
                if(coasterName) coasterName.textContent = coaster.coaster_name;
                if(parkName) parkName.textContent = coaster.park_name;
                
                // Nuevo: País (Hero)
                let coasterCountry = document.getElementById("coaster-country");
                if(coasterCountry) coasterCountry.textContent = coaster.park_country || "N/A";

                // Enlace dinámico del parque en el Hero
                let parkLink = document.getElementById("park-link");
                if(parkLink) parkLink.href = BASE_URL + `/web/views/public/park_detail.php?id=${coaster.park_id}`;

                // --- Ficha técnica (Tabla) ---
                if(parkNameTable) {
                    parkNameTable.textContent = coaster.park_name;
                    parkNameTable.href = BASE_URL + `/web/views/public/park_detail.php?id=${coaster.park_id}`;
                }
                
                // Estadísticas técnicas
                if(coasterHeight) coasterHeight.textContent = coaster.height ? coaster.height + "m" : "N/A";
                if(coasterSpeed) coasterSpeed.textContent = coaster.speed ? coaster.speed + " km/h" : "N/A";
                if(coasterLength) coasterLength.textContent = coaster.coaster_length ? coaster.coaster_length + "m" : "N/A";
                if(coasterInversions) coasterInversions.textContent = coaster.inversions || "0";

                // Datos de fabricación
                if(coasterManufacter) coasterManufacter.textContent = coaster.coaster_manufacter || "Desconocido";
                if(coasterModel) coasterModel.textContent = coaster.coaster_model || "Desconocido";
                if(coasterYear) coasterYear.textContent = coaster.opening_year || "N/A";

                // --- Rankings y Estados ---
                if(positionRank) positionRank.textContent = coaster.global_rank ? "#" + coaster.global_rank : "#" + coaster.id;
                if(puntuacion) puntuacion.textContent = coaster.score ? coaster.score + "%" : "N/A";
                
                if(currentState) currentState.textContent = coaster.status || "Operativa";
                if(currentStateTable) currentStateTable.textContent = coaster.status || "Operativa";
                
                // --- Multimedia ---
                if (coaster.imagen_url) {
                    $(".col-lg-7 img").attr("src", coaster.imagen_url).attr("alt", coaster.coaster_name);
                }

                // --- Lógica del botón "Montada" (Visual) ---
                if (coaster.is_ridden > 0) {
                    $("#coaster-ridden").removeClass("fa-regular").addClass("fa-solid text-success");
                    $("#coaster-ridden").closest('button').addClass("border-success");
                }

            } else {
                console.error("Error de la API: " + data.error);
            }
        },
        error: function (xhr, status, error) {
            console.error("Error en la conexión con la API:", error);
        }
    });
}
// --- LÓGICA PARA CARGAR LA FICHA INDIVIDUAL ---
  const urlParams = new URLSearchParams(window.location.search);
  const coasterId = urlParams.get('id'); // Extrae el "id" de la URL

  if (coasterId) {
    loadCoastersData(coasterId);
  }
});
 