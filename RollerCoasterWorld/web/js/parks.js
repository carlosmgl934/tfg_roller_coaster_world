$(document).ready(function () {

  // =====================================================
  // === BÚSQUEDA Y LISTADO DE PARQUES (park_search.php) ===
  // =====================================================

  if (document.getElementById("park-list")) {

    // Resetear filtros al recargar página
    window.addEventListener("pageshow", function () {
      document.getElementById("opening-year-min").value = "";
      document.getElementById("opening-year-max").value = "";
      document.getElementById("num-coaster-min").value = "";
      document.getElementById("num-coaster-max").value = "";
      document.getElementById("rating-filter").value = "";
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

    // Ocultar resultados al hacer clic fuera
    $(document).on("click", function (e) {
      if (!$(e.target).closest("#park-search, #search-results").length) {
        $("#search-results").hide();
      }
    });

    // Filtros dinámicos
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
      console.log("Aplicando filtros...");
      // TODO: implementar llamada AJAX con los filtros activos
      // $.get('/api/parks', { filters: ... }, function(data) { renderizarParques(data); });
    }

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

  // =====================================================
  // === DETALLE DE PARQUE (parks.php / park_detail.php) ===
  // =====================================================

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
        $("#park-gallery").html('<p class="text-muted">Galería en desarrollo...</p>');
      },
      error: function () {
        $("#park-name").text("Error al cargar el parque");
      }
    });

    // Abrir modal de subida de foto
    $("#btn-upload-photo").click(function () {
      $("#uploadPhotoModal").modal("show");
    });
  }

  // =====================================================
  // === FORMULARIO DE RESEÑA DE PARQUE (form_park_rating.php) ===
  // =====================================================

  if (document.getElementById("park-review-form")) {

    // Inicializar Choices.js para el select múltiple de pros/contras
    if (document.getElementById("pros-contras")) {
      new Choices('#pros-contras', {
        removeItemButton: true,
        placeholderValue: 'Selecciona pros y contras',
        noResultsText: 'No se encontraron resultados',
      });
    }

    // Subida de foto con CropperJS
    let cropper;

    $("#photo-upload").on("change", function (e) {
      const file = e.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (e) {
        $("#cropper-image").attr("src", e.target.result);
        $(".crop-container").show();
        $("#crop-save-btn").show();

        if (cropper) cropper.destroy();
        cropper = new Cropper($("#cropper-image")[0], {
          aspectRatio: 16 / 9,
          viewMode: 1,
          autoCropArea: 0.8,
        });
      };
      reader.readAsDataURL(file);
    });

    $("#crop-save-btn").click(function () {
      if (!cropper) return;

      // Leer el park_id desde el input hidden del formulario
      const parkId = $("input[name='park_id']").val();

      cropper.getCroppedCanvas().toBlob(function (blob) {
        const formData = new FormData();
        formData.append('photo', blob, 'park-photo.jpg');
        formData.append('park_id', parkId);

        $.ajax({
          url: '/api/parks/upload-photo',
          method: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function (data) {
            if (data.success) {
              alert("¡Foto enviada! Esperando aprobación");
              $("#uploadPhotoModal").modal("hide");
              $("#photo-upload").val("");
              $(".crop-container").hide();
              $("#crop-save-btn").hide();
              if (cropper) { cropper.destroy(); cropper = null; }
            } else {
              alert("Error: " + (data.error || "Desconocido"));
            }
          },
          error: function () {
            alert("Error al subir la foto");
          }
        });
      }, 'image/jpeg', 0.85);
    });

    // Enviar reseña
    $("#park-review-form").submit(function (e) {
      e.preventDefault();

      const rating = $("input[name='rating']:checked").val();
      if (!rating) {
        alert("Selecciona una valoración");
        return;
      }

      // TODO: completar el envío AJAX de la reseña
      // const formData = $(this).serializeArray();
      // $.ajax({ url: '/api/parks/review', method: 'POST', data: ..., success: ... });
    });
  }

});