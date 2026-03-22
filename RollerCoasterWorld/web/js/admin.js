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
          { credentials: "include" },
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
              div.classList.add(
                "col-12",
                "col-md-6",
                "col-xl-4",
                "photo-card-wrapper",
              );

              // Guardamos en data-search el username y nombre de coaster para filtrar
              const searchStr =
                `${photo.username} ${photo.coaster_name}`.toLowerCase();
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
            { method: "POST", credentials: "include" },
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
            { method: "POST", credentials: "include" },
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
    $("#search-pending").on("input", function () {
      const val = $(this).val().toLowerCase().trim();
      $(".photo-card-wrapper").each(function () {
        const text = $(this).attr("data-search");
        if (text.includes(val)) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Botón Actualizar
    $("#btn-refresh").on("click", function () {
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
  if (document.getElementById("admin-coaster-search")) {
    const $searchInput = $("#admin-coaster-search");
    const $searchIcon  = $("#admin-search-icon");
    const $list        = $("#admin-coaster-list");
    const $pagination  = $("#admin-coaster-pagination");
    const $count       = $("#admin-coaster-count");

    let currentPage   = 1;
    const ITEMS_PAGE  = 15;
    let searchDebounce = null;

    // ── Helpers ─────────────────────────────────────────────
    function getFilters() {
      return {
        opened:     $("#filter-open-only").is(":checked") ? "true" : "",
        manufacter: $("#filter-manufacter").val() || "",
        country:    $("#filter-country").val()    || "",
        park:       $("#filter-park").val()       || "",
        year:       $("#filter-year").val()       || "",
        height:     $("#filter-height").val()     || "0",
        speed:      $("#filter-speed").val()      || "0",
      };
    }

    function hasActiveFilters(f) {
      return (
        f.opened || f.manufacter || f.country ||
        f.park   || f.year       ||
        parseInt(f.height) > 0   ||
        parseInt(f.speed)  > 0
      );
    }

    // ── Render rows ─────────────────────────────────────────
    function renderRows(coasters) {
      $list.empty();
      if (!coasters || coasters.length === 0) {
        $list.html(
          '<div class="list-group-item text-center text-muted py-4">' +
          '<i class="fa-regular fa-face-frown fa-2x mb-2 d-block"></i>' +
          "No se encontraron coasters.</div>"
        );
        return;
      }
      coasters.forEach((c) => {
        $list.append(`
          <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
            <div class="flex-grow-1">
              <h6 class="mb-0 fw-bold text-success">${c.coaster_name}</h6>
              <small class="text-muted">
                ${c.coaster_manufacter || "Desconocido"} &bull;
                ${c.park_name   || "Desconocido"} &bull;
                ${c.park_country || "—"} &bull;
                ${c.opening_year || "—"} &bull;
                ${c.coaster_status || "—"}
              </small>
            </div>
            <div class="d-flex gap-2 ms-3 flex-shrink-0">
              <a href="#" class="btn btn-sm btn-outline-primary rounded-0" data-id="${c.id}">
                <i class="fa-solid fa-pen"></i> Editar
              </a>
              <button class="btn btn-sm btn-outline-danger rounded-0 btn-delete-coaster"
                data-id="${c.id}" data-name="${c.coaster_name}">
                <i class="fa-solid fa-trash"></i> Eliminar
              </button>
            </div>
          </div>
        `);
      });
    }

    // ── Paginación (estilo web pública) ──────────────────────
    function renderPagination(total, page) {
      $pagination.empty();
      const totalPages = Math.ceil(total / ITEMS_PAGE);
      if (totalPages <= 1) return;

      const nav = $('<nav aria-label="Paginación coasters"></nav>');
      const ul  = $('<ul class="pagination pagination-sm mb-0"></ul>');

      // Primera página
      ul.append(`
        <li class="page-item ${page === 1 ? "disabled" : ""}">
          <button class="page-link rounded-0" data-page="1" title="Primera página">&#171;</button>
        </li>`);

      // Anterior
      ul.append(`
        <li class="page-item ${page === 1 ? "disabled" : ""}">
          <button class="page-link rounded-0" data-page="${page - 1}">&#8249;</button>
        </li>`);

      // Páginas numeradas (máximo 7 botones visibles)
      let start = Math.max(1, page - 3);
      let end   = Math.min(totalPages, start + 6);
      start     = Math.max(1, end - 6);

      if (start > 1) ul.append('<li class="page-item disabled"><span class="page-link">…</span></li>');
      for (let i = start; i <= end; i++) {
        ul.append(`
          <li class="page-item ${i === page ? "active" : ""}">
            <button class="page-link rounded-0" data-page="${i}">${i}</button>
          </li>`);
      }
      if (end < totalPages) ul.append('<li class="page-item disabled"><span class="page-link">…</span></li>');

      // Siguiente
      ul.append(`
        <li class="page-item ${page === totalPages ? "disabled" : ""}">
          <button class="page-link rounded-0" data-page="${page + 1}">&#8250;</button>
        </li>`);

      // Última página
      ul.append(`
        <li class="page-item ${page === totalPages ? "disabled" : ""}">
          <button class="page-link rounded-0" data-page="${totalPages}" title="Última página">&#187;</button>
        </li>`);


      nav.append(ul);
      $pagination.append(nav);

      // Click en botón de página
      $pagination.find("button.page-link[data-page]").on("click", function () {
        const p = parseInt($(this).data("page"));
        if (p < 1 || p > totalPages) return;
        loadCoasters(p);
        window.scrollTo({ top: $list.offset().top - 80, behavior: "smooth" });
      });
    }

    // ── Carga principal ──────────────────────────────────────
    async function loadCoasters(page) {
      page = page || 1;
      currentPage = page;

      const search  = $searchInput.val().trim();
      const filters = getFilters();
      const useSearch  = search.length >= 3;
      const useFilters = hasActiveFilters(filters);

      if (!useSearch && !useFilters) {
        $list.html(
          '<div class="list-group-item text-center text-muted py-5" id="admin-coaster-loading">' +
          '<i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>' +
          "Usa el buscador o activa un filtro para ver coasters.</div>"
        );
        $count.text("");
        $pagination.empty();
        return;
      }

      $list.html(
        '<div class="list-group-item text-center text-muted py-4">' +
        '<div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando...</div>'
      );

      try {
        let url;
        if (useSearch) {
          url = `${BASE_URL}/api/php/admin/admin_coasters.php?action=searchCoasters` +
                `&search=${encodeURIComponent(search)}&page=${page}`;
        } else {
          const params = new URLSearchParams({ action: "filterCoasters", page });
          if (filters.opened)     params.set("opened",     filters.opened);
          if (filters.manufacter) params.set("manufacter", filters.manufacter);
          if (filters.country)    params.set("country",    filters.country);
          if (filters.park)       params.set("park",       filters.park);
          if (filters.year)       params.set("year",       filters.year);
          if (parseInt(filters.height) > 0) params.set("height", filters.height);
          if (parseInt(filters.speed)  > 0) params.set("speed",  filters.speed);
          url = `${BASE_URL}/api/php/admin/admin_coasters.php?${params}`;
        }

        const res  = await fetch(url, { credentials: "include" });
        const data = await res.json();

        if (data.success) {
          const total = data.total || 0;
          $count.text(`Mostrando ${total} coaster${total !== 1 ? "s" : ""}`);
          renderRows(data.coasters);
          renderPagination(total, page);
        } else {
          $count.text("");
          $list.html(
            '<div class="list-group-item text-center text-danger py-4">' +
            (data.error || "Error desconocido") + "</div>"
          );
          $pagination.empty();
        }
      } catch (err) {
        console.error("Error cargando coasters:", err);
      }
    }

    // ── Lupa ↔ X roja ────────────────────────────────────────
    $searchInput.on("input", function () {
      if ($(this).val().length > 0) {
        $searchIcon
          .removeClass("fa-magnifying-glass text-muted")
          .addClass("fa-xmark text-danger")
          .css("cursor", "pointer");
      } else {
        $searchIcon
          .removeClass("fa-xmark text-danger")
          .addClass("fa-magnifying-glass text-muted")
          .css("cursor", "default");
      }
    });

    $searchIcon.on("click", function () {
      if ($(this).hasClass("fa-xmark")) {
        $searchInput.val("").trigger("input").focus();
        loadCoasters(1);
      }
    });

    // ── Buscador con debounce ─────────────────────────────────
    $searchInput.on("keyup", function () {
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(() => loadCoasters(1), 350);
    });

    // ── Sliders: solo actualizar etiqueta (la carga la lanza el botón Filtrar) ──
    $("#filter-height").on("input", function () {
      $("#height-val").text($(this).val() + " m");
    });

    $("#filter-speed").on("input", function () {
      $("#speed-val").text($(this).val() + " km/h");
    });

    $("#btn-filtrar").on("click", () => loadCoasters(1));

    $("#btn-borrar").on("click", function () {
      $("#filter-open-only").prop("checked", false);
      $("#filter-manufacter, #filter-country, #filter-park, #filter-year").val("");
      $("#filter-height").val(0); $("#height-val").text("0 m");
      $("#filter-speed").val(0);  $("#speed-val").text("0 km/h");
      $searchInput.val("").trigger("input");
      loadCoasters(1);
    });

    // ── Modal borrar ─────────────────────────────────────────
    $(document).on("click", ".btn-delete-coaster", function () {
      $("#delete-coaster-name").text($(this).data("name"));
      $("#confirm-delete-coaster").data("id", $(this).data("id"));
      new bootstrap.Modal(document.getElementById("modal-delete-coaster")).show();
    });
  }
});

