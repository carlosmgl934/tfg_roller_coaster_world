// admin.js — Lógica del panel de administrador
// TODO: gestión de usuarios, coasters, fotos, comentarios

$(document).ready(function () {
  if (document.getElementById("pending-photos-container")) {
    loadPendingPhotos();
  }

  /*
  ***************************************
        FOTOS
  ***************************************
  */

  async function loadPendingPhotos() {
    const pendingPhotosContainer = document.getElementById(
      "pending-photos-container",
    );
    if (pendingPhotosContainer) {
      $("#loading-spinner").show();
      $("#empty-state").hide();
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_photos.php?action=getPendingPhotos`,
          { credentials: 'include' },
        );
        const data = await res.json();
        if (data.success) {
          $("#loading-spinner").hide();
          if (data.photos.length === 0) {
            $("#empty-state").show();
          } else {
            $("#pending-count").text(data.photos.length);
            data.photos.forEach((photo) => {
              const div = document.createElement("div");
              div.classList.add("col-12", "col-md-6", "col-xl-4", "photo-card-wrapper");
              
              // Guardamos en data-search el username y nombre de coaster para filtrar
              const searchStr = `${photo.username} ${photo.coaster_name}`.toLowerCase();
              div.setAttribute("data-search", searchStr);
              
              div.innerHTML = `
                <div class="card border-secondary bg-dark h-100 overflow-hidden shadow-sm hover-elevate rounded-0">
                <div class="position-relative">
                    <img src=${photo.url} class="card-img-top rounded-0" style="height:220px; object-fit:cover;" alt="Foto">
                    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2 rounded-0"><i class="fa-solid fa-clock me-1"></i>Pendiente</span>
                </div>
                <div class="card-body">
                    <h5 class="card-title text-success fw-bold text-truncate">${photo.username}</h5>
                    <p class="card-text text-muted mb-1 small"><i class="fa-solid fa-user me-1"></i> Subido por: <strong>${photo.username}</strong></p>
                    <p class="card-text text-muted mb-3 small"><i class="fa-solid fa-train-tram me-1"></i> Destino: <strong>${photo.coaster_name}</strong></p>
                    
                    <div class="d-flex gap-2 mt-auto">
                        <button class="btn btn-success flex-grow-1 btn-approve rounded-0" data-id="${photo.id}">
                            <i class="fa-solid fa-check me-1"></i> Aprobar
                        </button>
                        <button class="btn btn-outline-danger flex-grow-1 btn-reject rounded-0" data-id="${photo.id}">
                            <i class="fa-solid fa-xmark me-1"></i> Rechazar
                        </button>
                    </div>
                </div>
            </div>    
                `;
              pendingPhotosContainer.appendChild(div);
            });
          }
        } else {
          $("#loading-spinner").hide();
          $("#empty-state").show();
        }

        // Lightbox: clic en imagen → ver foto completa con info
        $(pendingPhotosContainer).on("click", "img.card-img-top", function () {
          const src = $(this).attr("src");
          document.getElementById("lightbox-img").src = src;
          
          new bootstrap.Modal(document.getElementById("lightbox-modal")).show();
        });

        $(".btn-approve").on("click", async function () {
          const tarjeta = $(this).closest(".col-12");
          const photoId = $(this).data("id");
          const res = await fetch(
            `${BASE_URL}/api/php/admin/admin_photos.php?action=approvePhoto&id=${photoId}`,
            { method: "POST", credentials: 'include' },
          );
          const data = await res.json();
          if (data.success) {
            tarjeta.fadeOut(300, function () {
              $(this).remove();
            });
            let newCount = Math.max(
              0,
              parseInt($("#pending-count").text()) - 1,
            );
            $("#pending-count").text(newCount);
            if (newCount === 0) $("#empty-state").show();
          }
        });

        $(".btn-reject").on("click", async function () {
          const tarjeta = $(this).closest(".col-12");
          const photoId = $(this).data("id");
          const res = await fetch(
            `${BASE_URL}/api/php/admin/admin_photos.php?action=rejectPhoto&id=${photoId}`,
            { method: "POST", credentials: 'include' },
          );
          const data = await res.json();
          if (data.success) {
            tarjeta.fadeOut(300, function () {
              $(this).remove();
            });
            let newCount = Math.max(
              0,
              parseInt($("#pending-count").text()) - 1,
            );
            $("#pending-count").text(newCount);
            if (newCount === 0) $("#empty-state").show();
          }
        });
      } catch (error) {
        $("#loading-spinner").hide();
        $("#loading-spinner").hide();
        $("#empty-state").show();
        console.error("Error al cargar fotos pendientes:", error);
      }
    } else {
      $("#loading-spinner").hide();
      $("#empty-state").show();
    }
  }

  // Lógica de los botones y búsqueda
  if (document.getElementById("pending-photos-container")) {
    // Buscador en tiempo real
    $("#search-pending").on("input", function() {
      const val = $(this).val().toLowerCase().trim();
      $(".photo-card-wrapper").each(function() {
        const text = $(this).attr("data-search");
        if (text.includes(val)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Botón Actualizar
    $("#btn-refresh").on("click", function() {
      loadPendingPhotos();
    });
  }

  /****************************************
        COMENTARIOS
****************************************/
  /****************************************
        USUARIOS
****************************************/
  /****************************************
        COASTERS
****************************************/
});
