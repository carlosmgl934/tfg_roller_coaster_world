$(document).ready(function () {
  // === BÚSQUEDA Y LISTADO DE PARQUES (park_search.php) ===

  if (document.getElementById("park-list")) {
    // Resetear filtros al recargar página
    window.addEventListener("pageshow", function () {
      document.getElementById("opening-year-min").value = "";
      document.getElementById("opening-year-max").value = "";
      document.getElementById("num-coaster-min").value = "";
      document.getElementById("num-coaster-max").value = "";
      document.getElementById("rating-filter").value = "";
      // Limpiar país y ubicación si se cargan dinámicamente
    });

    // Buscador en tiempo real (debounce para no saturar)
    let searchTimeout;
    $("#park-search").on("input", function () {
      clearTimeout(searchTimeout);
      const query = $(this).val().trim();

      searchTimeout = setTimeout(() => {
        if (query.length >= 2) {
          buscarParques(query);
        } else {
          $("#search-results").hide();
        }
      }, 300);
    });

    // Función para buscar parques
    function buscarParques(query) {
      $.ajax({
        url: '/api/parks/search',
        method: 'GET',
        data: { q: query },
        success: function (data) {
          const results = $("#search-results");
          results.empty().show();

          if (data.length === 0) {
            results.append('<div class="list-group-item text-muted">No se encontraron parques</div>');
            return;
          }

          data.forEach(park => {
            const item = `
              <a href="/parks.php?id=${park.id}" class="list-group-item list-group-item-action">
                <strong>${park.park_name}</strong><br>
                <small>${park.park_location}, ${park.park_country} · ${park.num_coaster} coasters</small>
              </a>`;
            results.append(item);
          });
        },
        error: function () {
          $("#search-results").html('<div class="list-group-item text-danger">Error al buscar</div>').show();
        }
      });
    }

    // Ocultar resultados al hacer click fuera
    $(document).on("click", function (e) {
      if (!$(e.target).closest("#park-search, #search-results").length) {
        $("#search-results").hide();
      }
    });

    // Filtros dinámicos (al cambiar cualquier filtro)
    $("#country-filter, #location-filter, #opening-year-min, #opening-year-max, #num-coaster-min, #num-coaster-max, #rating-filter").on("change input", function () {
      aplicarFiltros();
    });

    $("#clear-filters").click(function () {
      $("#country-filter").val("");
      $("#location-filter").val("");
      $("#opening-year-min, #opening-year-max").val("");
      $("#num-coaster-min, #num-coaster-max").val("");
      $("#rating-filter").val("");
      aplicarFiltros();
    });

    function aplicarFiltros() {
      // Aquí iría la lógica de filtrado (puede ser AJAX o JS local si tienes todos los datos)
      // Por simplicidad, recargamos con parámetros GET o usamos JS para filtrar
      console.log("Aplicando filtros...");
      // Ejemplo con AJAX:
      // $.get('/api/parks', { filters: ... }, function(data) { renderizarParques(data); });
    }

    // Renderizar parques (ejemplo básico)
    function renderizarParques(parques) {
      const contenedor = $("#park-list");
      contenedor.empty();

      if (parques.length === 0) {
        contenedor.html('<p class="text-center text-muted py-5">No se encontraron parques con esos filtros</p>');
        return;
      }

      parques.forEach(park => {
        const card = `
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0">
              <img src="${park.imagen_url || 'https://placehold.co/400x300'}" class="card-img-top" alt="${park.park_name}">
              <div class="card-body">
                <h5 class="card-title">${park.park_name}</h5>
                <p class="card-text text-muted">${park.park_location}, ${park.park_country}</p>
                <p class="mb-1"><strong>Apertura:</strong> ${park.opening_year}</p>
                <p class="mb-1"><strong>Coasters:</strong> ${park.num_coaster} (${park.operating_coasters} operativas)</p>
                <p class="mb-1"><strong>Entrada:</strong> $${park.precio_entrada || 'N/A'}</p>
                <p class="mb-0"><strong>Rating:</strong> ${park.stars} ★</p>
              </div>
              <div class="card-footer text-center">
                <a href="/parks.php?id=${park.id}" class="btn btn-success btn-sm">Ver detalle</a>
              </div>
            </div>
          </div>`;
        contenedor.append(card);
      });
    }
  }

  // === DETALLE DE PARQUE (parks.php) ===
  if (document.getElementById("park-name")) {
    const parkId = new URLSearchParams(window.location.search).get('id');

    $.ajax({
      url: '/api/parks/' + parkId,
      method: 'GET',
      success: function (park) {
        $("#park-name").text(park.park_name);
        $("#park-rating").text("★ " + (park.stars || "N/A"));
        $("#park-location").text(park.park_location + ", " + park.park_country);
        $("#opening-year").text(park.opening_year || "N/A");
        $("#park-country").text(park.park_country);
        $("#num-coaster").text(park.num_coaster || "N/A");
        $("#operating-coasters").text(park.operating_coasters || "N/A");
        $("#precio-entrada").text(park.precio_entrada ? "$" + park.precio_entrada : "N/A");
        $("#park-description").text(park.description || "Sin descripción disponible");
        $("#btn-website").attr("href", park.web || "#");
        $("#park-hero-img").attr("src", park.imagen_url || "https://placehold.co/900x500");

        // Cargar galería de fotos (ejemplo)
        $("#park-gallery").html('<p class="text-muted">Galería en desarrollo...</p>');
      },
      error: function () {
        $("#park-name").text("Error al cargar el parque");
      }
    });

    // Subida de foto (similar a coasters)
    $("#btn-upload-photo").click(function () {
      $("#uploadPhotoModal").modal("show");
    });

    // ... resto del código de CropperJS, galería, reseñas, etc. (igual que en coasters.js)
    // Puedes copiar y adaptar las funciones de subida de foto y reseñas del archivo coasters.js
  }
});