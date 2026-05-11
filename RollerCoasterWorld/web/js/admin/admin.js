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
      // Limpiar tarjetas anteriores para evitar duplicados al refrescar
      $(pendingPhotosContainer).find(".photo-card-wrapper").remove();
      $("#loading-spinner").show();
      $("#empty-state").addClass("d-none");
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_photos.php?action=getPendingPhotos`,
          {
            credentials: "include",
          },
        );
        const data = await res.json();
        if (data.success) {
          $("#loading-spinner").hide();
          if (data.photos.length === 0) {
            $("#empty-state").removeClass("d-none");
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

              // Bloque de descripción (sólo si existe)
              const captionHtml = photo.caption
                ? `<div class="caption-block d-flex align-items-start gap-2 mb-3 p-2 rounded-0" style="background:rgba(255,255,255,.05); border-left:3px solid #6c757d;">
                    <p class="card-text text-muted small mb-0 flex-grow-1 fst-italic">"${photo.caption}"</p>
                    <button class="btn btn-sm btn-outline-danger rounded-0 btn-clear-caption flex-shrink-0" data-id="${photo.id}" title="Borrar descripción">
                      <i class="fa-solid fa-trash-can"></i>
                    </button>
                  </div>`
                : `<p class="text-muted small fst-italic mb-3" data-caption-placeholder="${photo.id}"><i class="fa-regular fa-comment-slash me-1"></i>Sin descripción</p>`;

              div.innerHTML = `
                <div class="card border-secondary bg-dark h-100 overflow-hidden shadow-sm hover-elevate rounded-0">
                <div class="position-relative">
                    <img src=${photo.url} class="card-img-top rounded-0" style="height:220px; object-fit:cover;" alt="Foto">
                    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2 rounded-0"><i class="fa-solid fa-clock me-1"></i>Pendiente</span>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-success fw-bold text-truncate">${photo.username}</h5>
                    <p class="card-text text-muted mb-1 small"><i class="fa-solid fa-user me-1"></i> Subido por: <strong>${photo.username}</strong></p>
                    <p class="card-text text-muted mb-3 small"><i class="fa-solid fa-angles-right me-1"></i> Destino: <strong>${photo.coaster_name}</strong></p>
                    ${captionHtml}
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
          $("#empty-state").removeClass("d-none");
        }
      } catch (error) {
        $("#loading-spinner").hide();
        $("#empty-state").removeClass("d-none");
        console.error("Error al cargar fotos pendientes:", error);
      }
    } else {
      $("#loading-spinner").hide();
      $("#empty-state").removeClass("d-none");
    }
  }

  // ── Delegación de eventos (funciona también tras refrescar) ──
  if (document.getElementById("pending-photos-container")) {
    const $container = $("#pending-photos-container");

    // Lightbox
    $container.on("click", "img.card-img-top", function () {
      document.getElementById("lightbox-img").src = $(this).attr("src");
      new bootstrap.Modal(document.getElementById("lightbox-modal")).show();
    });

    // Aprobar
    $container.on("click", ".btn-approve", async function () {
      const tarjeta = $(this).closest(".col-12");
      const photoId = $(this).data("id");
      const res = await fetch(
        `${BASE_URL}/api/php/admin/admin_photos.php?action=approvePhoto&id=${photoId}`,
        {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          credentials: "include",
        },
      );
      const data = await res.json();
      if (data.success) {
        tarjeta.fadeOut(300, function () {
          $(this).remove();
        });
        let newCount = Math.max(0, parseInt($("#pending-count").text()) - 1);
        $("#pending-count").text(newCount);
        if (newCount === 0) $("#empty-state").removeClass("d-none");
      }
    });

    // Rechazar
    $container.on("click", ".btn-reject", async function () {
      const tarjeta = $(this).closest(".col-12");
      const photoId = $(this).data("id");
      const res = await fetch(
        `${BASE_URL}/api/php/admin/admin_photos.php?action=rejectPhoto&id=${photoId}`,
        {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          credentials: "include",
        },
      );
      const data = await res.json();
      if (data.success) {
        tarjeta.fadeOut(300, function () {
          $(this).remove();
        });
        let newCount = Math.max(0, parseInt($("#pending-count").text()) - 1);
        $("#pending-count").text(newCount);
        if (newCount === 0) $("#empty-state").removeClass("d-none");
      }
    });

    // Borrar descripción
    $container.on("click", ".btn-clear-caption", async function () {
      const btn = $(this);
      const photoId = btn.data("id");
      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin"></i>');
      const res = await fetch(
        `${BASE_URL}/api/php/admin/admin_photos.php?action=clearCaption&id=${photoId}`,
        {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          credentials: "include",
        },
      );
      const data = await res.json();
      if (data.success) {
        // Sustituir bloque de descripción por el placeholder "Sin descripción"
        btn
          .closest(".caption-block")
          .replaceWith(
            `<p class="text-muted small fst-italic mb-3"><i class="fa-regular fa-comment-slash me-1"></i>Sin descripción</p>`,
          );
      } else {
        btn
          .prop("disabled", false)
          .html('<i class="fa-solid fa-trash-can"></i>');
      }
    });

    // Buscador en tiempo real
    $("#search-pending").on("input", function () {
      const val = $(this).val().toLowerCase().trim();
      $(".photo-card-wrapper").each(function () {
        const text = $(this).attr("data-search");
        $(this).toggle(text.includes(val));
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
  // ── Helpers ─────────────────────────────────────────────
  function getFilters() {
    return {
      opened: $("#filter-open-only").is(":checked") ? "true" : "",
      manufacter: $("#filter-manufacter").val() || "",
      country: $("#filter-country").val() || "",
      park: $("#filter-park").val() || "",
      year: $("#filter-year").val() || "",
      height: $("#filter-height").val() || "0",
      speed: $("#filter-speed").val() || "0",
    };
  }

  function hasActiveFilters(f) {
    return (
      f.opened ||
      f.manufacter ||
      f.country ||
      f.park ||
      f.year ||
      parseInt(f.height) > 0 ||
      parseInt(f.speed) > 0
    );
  }

  // ── Render rows ─────────────────────────────────────────
  let selectedCoasters = new Set();

  function renderRows(coasters) {
    const $list = $("#admin-coaster-list");
    $list.empty();
    if (!coasters || coasters.length === 0) {
      $list.html(
        '<div class="list-group-item text-center text-muted py-4">' +
          '<i class="fa-regular fa-face-frown fa-2x mb-2 d-block"></i>' +
          "No se encontraron coasters.</div>",
      );
      return;
    }
    coasters.forEach((c) => {
      const isChecked = selectedCoasters.has(c.id.toString()) ? "checked" : "";
      $list.append(`
        <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 gap-2">
          <div class="form-check me-2 mb-0 flex-shrink-0 d-none d-sm-block">
            <input class="form-check-input coaster-select-checkbox" type="checkbox" value="${c.id}" ${isChecked} style="transform: scale(1.1); cursor: pointer;">
          </div>
          <div class="flex-grow-1 min-w-0">
            <h6 class="mb-0 fw-bold text-success text-truncate" style="font-size: .95rem;">${c.coaster_name}</h6>
            <small class="text-muted text-truncate d-block" style="font-size: .75rem;">
              ${c.coaster_manufacter || "Desconocido"} &bull;
              ${c.park_name || "Desconocido"}
            </small>
          </div>
          <div class="d-flex gap-1 gap-sm-2 flex-shrink-0">
            <a href="#" class="btn btn-sm btn-outline-primary rounded-0 px-2 px-sm-3" 
              data-bs-toggle="modal" data-bs-target="#modal-edit-coaster"
              data-id="${c.id}"
              data-name="${c.coaster_name ? c.coaster_name.replace(/"/g, "&quot;") : ""}"
              data-year="${c.opening_year ?? ""}"
              data-height="${c.height ?? ""}"
              data-speed="${c.speed ?? ""}"
              data-length="${c.coaster_length ?? ""}"
              data-inversions="${c.inversions ?? ""}"
              data-status="${c.coaster_status || ""}"
              data-manufacturer="${c.coaster_manufacter ? c.coaster_manufacter.replace(/"/g, "&quot;") : ""}"
              data-model="${c.coaster_model ? c.coaster_model.replace(/"/g, "&quot;") : ""}"
              data-park="${c.park_name ? c.park_name.replace(/"/g, "&quot;") : ""}"
              data-park-id="${c.park_id ?? ""}"
              data-country="${c.park_country ? c.park_country.replace(/"/g, "&quot;") : ""}"
              data-image="${c.imagen_url || ""}">
              <i class="fa-solid fa-pen"></i> <span class="d-none d-md-inline">Editar</span>
            </a>
            <button class="btn btn-sm btn-outline-danger rounded-0 btn-delete-coaster px-2 px-sm-3"
              data-id="${c.id}" data-name="${c.coaster_name}">
              <i class="fa-solid fa-trash"></i> <span class="d-none d-md-inline">Eliminar</span>
            </button>
          </div>
        </div>
      `);
    });
  }

  // ── Paginación (estilo web pública) ──────────────────────
  function renderPagination(total, page) {
    const $pagination = $("#admin-coaster-pagination");
    const ITEMS_PAGE = 15;
    const $list = $("#admin-coaster-list");

    $pagination.empty();
    const totalPages = Math.ceil(total / ITEMS_PAGE);
    if (totalPages <= 1) return;

    const nav = $('<nav aria-label="Paginación coasters"></nav>');
    const ul = $('<ul class="pagination pagination-sm mb-0"></ul>');

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
    let end = Math.min(totalPages, start + 6);
    start = Math.max(1, end - 6);

    if (start > 1)
      ul.append(
        '<li class="page-item disabled"><span class="page-link">…</span></li>',
      );
    for (let i = start; i <= end; i++) {
      ul.append(`
        <li class="page-item ${i === page ? "active" : ""}">
          <button class="page-link rounded-0" data-page="${i}">${i}</button>
        </li>`);
    }
    if (end < totalPages)
      ul.append(
        '<li class="page-item disabled"><span class="page-link">…</span></li>',
      );

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
      window.loadAdminCoasters(p);
      window.scrollTo({ top: $list.offset().top - 80, behavior: "smooth" });
    });
  }

  // ── Carga principal ──────────────────────────────────────
  window.loadAdminCoasters = async function (page) {
    const $searchInput = $("#admin-coaster-search");
    const $list = $("#admin-coaster-list");
    const $count = $("#admin-coaster-count");
    const $pagination = $("#admin-coaster-pagination");

    if (!$searchInput.length) return; // Only run if on coasters page

    page = page || 1;
    let currentPage = page; // Use local variable

    const search = $searchInput.val().trim();
    const filters = getFilters();
    const useSearch = search.length >= 3;
    const useFilters = hasActiveFilters(filters);

    if (!useSearch && !useFilters) {
      $list.html(
        '<div class="list-group-item text-center text-muted py-5" id="admin-coaster-loading">' +
          '<i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>' +
          "Usa el buscador o activa un filtro para ver coasters.</div>",
      );
      $count.text("");
      $pagination.empty();
      return;
    }

    $list.html(
      '<div class="list-group-item text-center text-muted py-4">' +
        '<div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando...</div>',
    );

    try {
      const params = new URLSearchParams({
        action: "filterCoasters",
        page,
      });

      if (search) params.set("search", search);
      if (filters.opened) params.set("opened", filters.opened);
      if (filters.manufacter) params.set("manufacter", filters.manufacter);
      if (filters.country) params.set("country", filters.country);
      if (filters.park) params.set("park", filters.park);
      if (filters.year) params.set("year", filters.year);
      if (parseInt(filters.height) > 0) params.set("height", filters.height);
      if (parseInt(filters.speed) > 0) params.set("speed", filters.speed);

      const url = `${BASE_URL}/api/php/admin/admin_coasters.php?${params}`;

      const res = await fetch(url, { credentials: "include" });
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
            (data.error || "Error desconocido") +
            "</div>",
        );
        $pagination.empty();
      }
    } catch (err) {
      console.error("Error cargando coasters:", err);
    }
  };

  if (document.getElementById("admin-coaster-search")) {
    const $searchInput = $("#admin-coaster-search");
    const $searchIcon = $("#admin-search-icon");
    let searchDebounce = null;

    // ── Poblar dropdowns de filtros ────────────────────────────
    // Fabricantes
    fetch(`${BASE_URL}/api/php/coasters.php?action=manufacter`)
      .then((r) => r.json())
      .then((data) => {
        if (data.success && data.manufacters) {
          data.manufacters
            .filter((m) => m.coaster_manufacter)
            .forEach((m) => {
              $("#filter-manufacter").append(
                `<option value="${m.coaster_manufacter}">${m.coaster_manufacter}</option>`,
              );
            });
        }
      })
      .catch(() => {});

    // Países
    fetch(`${BASE_URL}/api/php/parks.php?action=country`)
      .then((r) => r.json())
      .then((data) => {
        if (data.success && data.data) {
          data.data
            .filter((c) => c && c.trim() !== "")
            .forEach((c) => {
              $("#filter-country").append(`<option value="${c}">${c}</option>`);
            });
        }
      })
      .catch(() => {});

    // ── Autocompletado del Filtro de Parques ────────────────────────
    initAutocomplete({
      inputId: "filter-park-search",
      dropdownId: "filter-park-results",
      fetchItems: async (q) => {
        const url = `${BASE_URL}/api/php/parks.php?action=list&limit=20${q ? "&q=" + encodeURIComponent(q) : "&sort=name"}`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) return [];
        const parks = data.data.map((p) => ({
          label: p.park_name,
          sublabel: p.park_country || "",
          value: p.park_name,
          id: p.id,
        }));
        return [
          { label: "Todos los parques", value: "", id: "", unknown: false },
          {
            label: "Desconocido",
            value: "Desconocido",
            id: "__null__",
            unknown: true,
          },
          ...parks,
        ];
      },
      onSelect: (item) => {
        document.getElementById("filter-park").value = item.id;
      },
    });

    // ── Buscador con debounce ──────────────────────────────────
    $searchInput.on("input", function () {
      const val = $(this).val();
      if (val.length > 0) {
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
      clearTimeout(searchDebounce);
      searchDebounce = setTimeout(() => {
        window.loadAdminCoasters(1);
      }, 400);
    });

    // Limpiar buscador al hacer click en la X
    $searchIcon.on("click", function () {
      if ($searchInput.val().length > 0) {
        $searchInput.val("");
        $(this)
          .removeClass("fa-xmark text-danger")
          .addClass("fa-magnifying-glass text-muted")
          .css("cursor", "default");
        window.loadAdminCoasters(1);
      }
    });

    // ── Filtrar ───────────────────────────────────────────────
    $("#btn-filtrar").on("click", function () {
      window.loadAdminCoasters(1);
    });

    // ── Limpiar filtros ──────────────────────────────────────
    $("#btn-borrar").on("click", function () {
      $("#filter-open-only").prop("checked", false);
      $("#filter-manufacter").val("");
      $("#filter-country").val("");
      $("#filter-park").val("");
      $("#filter-park-search").val("");
      $("#filter-year").val("");
      $("#filter-height").val(0);
      $("#filter-speed").val(0);
      $("#height-val").text("0 m");
      $("#speed-val").text("0 km/h");
      $searchInput.val("");
      $searchIcon
        .removeClass("fa-xmark text-danger")
        .addClass("fa-magnifying-glass text-muted")
        .css("cursor", "default");
      window.loadAdminCoasters(1);
    });

    // ── Sliders (valores en tiempo real) ──────────────────────
    $("#filter-height").on("input", function () {
      $("#height-val").text($(this).val() + " m");
    });
    $("#filter-speed").on("input", function () {
      $("#speed-val").text($(this).val() + " km/h");
    });

    // ── Modal borrar (delegado) ──────────────────────────────
    $(document).on("click", ".btn-delete-coaster", function () {
      const name = $(this).data("name");
      const id = $(this).data("id");
      $("#delete-coaster-name").text(name);
      $("#confirm-delete-coaster").data("id", id).data("name", name);
      new bootstrap.Modal(
        document.getElementById("modal-delete-coaster"),
      ).show();
    });

    // ── Bulk delete (selección) ──────────────────────────────
    $(document).on("change", ".coaster-select-checkbox", function () {
      const id = $(this).val();
      if ($(this).is(":checked")) selectedCoasters.add(id);
      else selectedCoasters.delete(id);

      const count = selectedCoasters.size;
      if (count > 0) {
        $("#bulk-delete-count").text(count);
        $("#btn-bulk-delete").removeClass("d-none");
      } else {
        $("#btn-bulk-delete").addClass("d-none");
      }
    });

    $("#btn-bulk-delete").on("click", function () {
      $("#bulk-delete-coaster-count").text(selectedCoasters.size);
      new bootstrap.Modal(
        document.getElementById("modal-bulk-delete-coaster"),
      ).show();
    });

    $("#confirm-bulk-delete-coaster").on("click", async function () {
      const btn = $(this);
      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Eliminando...');

      try {
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_coasters.php?action=bulkDeleteCoasters`,
          {
            method: "POST",
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ coasterIds: Array.from(selectedCoasters) }),
          },
        );
        const data = await res.json();
        if (data.success) {
          selectedCoasters.clear();
          $("#btn-bulk-delete").addClass("d-none");
          bootstrap.Modal.getInstance(
            document.getElementById("modal-bulk-delete-coaster"),
          ).hide();
          window.loadAdminCoasters(1);
        } else {
          alert(data.error || "Error al eliminar montañas rusas");
        }
      } catch (e) {
        alert("Error de conexión");
      }
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-trash-can me-1"></i>Eliminar Todo');
    });

    // ── Eliminar coaster (confirmación simple) ────────────────
    $("#confirm-delete-coaster").on("click", async function () {
      const btn = $(this);
      const id = btn.data("id");
      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin"></i>');
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_coasters.php?action=deleteCoaster`,
          {
            method: "POST",
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ coasterId: id }),
          },
        );
        const data = await res.json();
        if (data.success) {
          bootstrap.Modal.getInstance(
            document.getElementById("modal-delete-coaster"),
          ).hide();
          window.loadAdminCoasters(1); // recargar misma pág o pág 1
        } else {
          alert(data.error || "Error al eliminar");
        }
      } catch (err) {
        alert("Error de red.");
      }
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-trash me-1"></i>Eliminar');
    });

    // ── Modal duplicar (delegado) ──────────────────────────────
    $(document).on("click", ".btn-duplicate-coaster", function () {
      const btn = $(this);

      // Limpiar errores previos
      clearErrors();

      // Poblamos los campos del modal de AÑADIR (modal-add-coaster)
      document.getElementById("add-coaster-name").value =
        btn.attr("data-name") + " (Copia)";
      document.getElementById("add-coaster-manufacturer").value =
        btn.attr("data-manufacturer") || "Desconocido";
      document.getElementById("add-coaster-manufacturer").dataset.selected =
        "true";

      document.getElementById("add-coaster-model").value =
        btn.attr("data-model") || "Desconocido";
      document.getElementById("add-coaster-model").dataset.selected = "true";

      document.getElementById("add-coaster-park").value =
        btn.attr("data-park") || "Desconocido";
      document.getElementById("add-coaster-park").dataset.selected = "true";
      document.getElementById("add-coaster-park-id").value =
        btn.attr("data-park-id") || "";

      document.getElementById("add-coaster-country").value =
        btn.attr("data-country") || "Desconocido";
      document.getElementById("add-coaster-country").dataset.selected = "true";

      document.getElementById("add-coaster-year").value =
        btn.attr("data-year") || "";
      document.getElementById("add-coaster-height").value =
        btn.attr("data-height") || "";
      document.getElementById("add-coaster-speed").value =
        btn.attr("data-speed") || "";
      document.getElementById("add-coaster-length").value =
        btn.attr("data-length") || "";
      document.getElementById("add-coaster-inversions").value =
        btn.attr("data-inversions") || "";
      document.getElementById("add-coaster-status").value =
        btn.attr("data-status") || "";

      // Guardar la URL original en el campo oculto
      const imageUrl = btn.attr("data-image");
      document.getElementById("add-coaster-image-url").value = imageUrl || "";

      // Mostrar preview de la imagen si existe
      const preview = document.getElementById("add-coaster-preview");
      if (preview) {
        if (imageUrl) {
          let validImgUrl = imageUrl;
          if (!validImgUrl.startsWith("http")) {
            validImgUrl =
              BASE_URL + (validImgUrl.startsWith("/") ? "" : "/") + validImgUrl;
          }
          preview.innerHTML = `<img src="${validImgUrl}" style="width:100%;height:100%;object-fit:cover;">`;
        } else {
          preview.innerHTML =
            '<div class="text-center text-muted"><i class="fa-regular fa-image fa-3x d-block mb-3" style="opacity:0.2;"></i><span style="font-size:0.85rem; letter-spacing:1px; text-transform:uppercase;">Vista previa</span></div>';
        }
      }

      // Abrimos el modal de AÑADIR
      const addModal = new bootstrap.Modal(
        document.getElementById("modal-add-coaster"),
      );
      addModal.show();
    });
  }
  // ── Modal añadir coaster  ─────────────────────────────────────────
  const _btnAddCoaster = document.getElementById("btn-add-coaster");
  if (_btnAddCoaster) {
    _btnAddCoaster.addEventListener("click", function () {
      // Resetear campos previos al abrir para añadir normal
      document.getElementById("add-coaster-form").reset();
      document.getElementById("add-coaster-image-url").value = "";
      document.getElementById("add-coaster-preview").innerHTML =
        '<div class="text-center text-muted"><i class="fa-regular fa-image fa-3x d-block mb-3" style="opacity:0.2;"></i><span style="font-size:0.85rem; letter-spacing:1px; text-transform:uppercase;">Vista previa</span></div>';

      const modal = new bootstrap.Modal(
        document.getElementById("modal-add-coaster"),
      );
      modal.show();
    });
  }

  // btn-new-park dentro del modal de coasters → guarda estado y redirige
  const _btnNewPark = document.getElementById("btn-new-park");
  if (_btnNewPark) {
    _btnNewPark.addEventListener("click", function () {
      // Guardar el estado de todo el formulario en sessionStorage
      const pendingData = {
        name: document.getElementById("add-coaster-name").value,
        manufacturer: document.getElementById("add-coaster-manufacturer").value,
        manufacturerRaw: document.getElementById("add-coaster-manufacturer")
          .dataset.selected,
        model: document.getElementById("add-coaster-model").value,
        modelRaw: document.getElementById("add-coaster-model").dataset.selected,
        country: document.getElementById("add-coaster-country").value,
        countryRaw: document.getElementById("add-coaster-country").dataset
          .selected,
        year: document.getElementById("add-coaster-year").value,
        height: document.getElementById("add-coaster-height").value,
        speed: document.getElementById("add-coaster-speed").value,
        length: document.getElementById("add-coaster-length").value,
        inversions: document.getElementById("add-coaster-inversions").value,
        status: document.getElementById("add-coaster-status").value,
      };
      sessionStorage.setItem("pendingCoasterData", JSON.stringify(pendingData));
      window.location.href = `${BASE_URL}/web/views/admin/parks.php?action=add_park`;
    });
  }

  const _modalAddCoaster = document.getElementById("modal-add-coaster");
  if (_modalAddCoaster)
    _modalAddCoaster.addEventListener("show.bs.modal", function () {
      initAutocomplete({
        inputId: "add-coaster-manufacturer",
        dropdownId: "ac-dropdown-manufacturer",
        fetchItems: async (q) => {
          const res = await fetch(
            `${BASE_URL}/api/php/coasters.php?action=manufacter`,
          );
          const data = await res.json();
          if (!data.success) return [];
          const all = [
            { label: "Desconocido", value: "Desconocido", unknown: true },
            ...data.manufacters
              .filter((m) => m.coaster_manufacter)
              .map((m) => ({
                label: m.coaster_manufacter,
                value: m.coaster_manufacter,
              })),
          ];
          return q
            ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
            : all;
        },
        onSelect: (item) => {},
      });
      initAutocomplete({
        inputId: "add-coaster-model",
        dropdownId: "ac-dropdown-model",
        fetchItems: async (q) => {
          const url = `${BASE_URL}/api/php/admin/admin_coasters.php?action=listModels&limit=50${q ? "&q=" + encodeURIComponent(q) : ""}`;
          const res = await fetch(url);
          const data = await res.json();
          if (!data.success) return [];
          const models = data.models.map((m) => ({
            label: m.coaster_model,
            value: m.coaster_model,
          }));
          return [
            { label: "Desconocido", value: "Desconocido", unknown: true },
            ...models,
          ];
        },
        onSelect: (item) => {},
      });
      initAutocomplete({
        inputId: "add-coaster-park",
        dropdownId: "ac-dropdown-park",
        fetchItems: async (q) => {
          const url = `${BASE_URL}/api/php/parks.php?action=list&limit=50${q ? "&q=" + encodeURIComponent(q) : "&sort=name"}`;
          const res = await fetch(url);
          const data = await res.json();
          if (!data.success) return [];
          const parks = data.data.map((p) => ({
            label: p.park_name,
            sublabel: p.park_country || "",
            value: p.park_name,
            id: p.id,
          }));
          return [
            {
              label: "Desconocido",
              value: "Desconocido",
              id: "2895",
              unknown: true,
            },
            ...parks,
          ];
        },
        onSelect: (item) => {
          document.getElementById("add-coaster-park-id").value = item.id || "";
        },
      });
      initAutocomplete({
        inputId: "add-coaster-country",
        dropdownId: "ac-dropdown-country",
        fetchItems: async (q) => {
          const res = await fetch(
            `${BASE_URL}/api/php/parks.php?action=country`,
          );
          const data = await res.json();
          if (!data.success) return [];
          const all = [
            { label: "Desconocido", value: "Desconocido", unknown: true },
            ...data.data
              .filter((c) => c && c.trim() !== "")
              .map((c) => ({ label: c, value: c })),
          ];
          return q
            ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
            : all;
        },
        onSelect: () => {},
      });
    });

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
            `<div class="ac-item${item.unknown ? " ac-item-unknown" : ""}" data-idx="${i}">${item.label}${item.sublabel ? `<span class="ac-sublabel">${item.sublabel}</span>` : ""}</div>`,
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
      input.value = item.value;
      input.dataset.selected = "true";
      onSelect(item);
      closeDropdown();
      // Quitar el foco del input y mover al siguiente campo
      input.blur();
      const focusable = Array.from(
        document.querySelectorAll(
          'input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled])',
        ),
      ).filter((el) => el.offsetParent !== null);
      const idx2 = focusable.indexOf(input);
      if (idx2 >= 0 && focusable[idx2 + 1]) focusable[idx2 + 1].focus();
    }

    function closeDropdown() {
      dropdown.classList.add("d-none");
      dropdown.innerHTML = "";
      activeIdx = -1;
      items = [];
    }

    input.addEventListener("input", () => {
      input.dataset.selected = "false";
      clearTimeout(debounce);
      debounce = setTimeout(async () => {
        const q = input.value.trim();
        const list = await fetchItems(q);
        // Solo mostrar si el input sigue con foco
        if (document.activeElement === input) renderItems(list);
      }, 200);
    });

    // Solo abrir el dropdown si el usuario hace click directamente en el input
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
      // Cerrar con pequeño delay para permitir el mousedown del item
      setTimeout(() => closeDropdown(), 150);
    });

    document.addEventListener("click", (e) => {
      if (!input.contains(e.target) && !dropdown.contains(e.target))
        closeDropdown();
    });
  }

  const _addCoasterImage = document.getElementById("add-coaster-image");
  if (_addCoasterImage) {
    _addCoasterImage.addEventListener("change", function () {
      const file = this.files[0];
      const preview = document.getElementById("add-coaster-preview");
      if (!file) return;

      const url = URL.createObjectURL(file);
      const isVideo = file.type.startsWith("video/");

      if (isVideo) {
        preview.innerHTML = `<video src="${url}" style="width:100%;height:100%;object-fit:cover;" autoplay muted loop playsinline></video>`;
      } else {
        preview.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover;">`;
      }
    });
  }

  const _confirmAddCoaster = document.getElementById("confirm-add-coaster");
  if (_confirmAddCoaster)
    _confirmAddCoaster.addEventListener("click", async function () {
      const btn = this;
      btn.disabled = true;
      btn.innerHTML =
        'Añadiendo... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

      try {
        clearErrors();
        const name = document.getElementById("add-coaster-name").value.trim();
        const manufacturer = document
          .getElementById("add-coaster-manufacturer")
          .value.trim();
        const model = document.getElementById("add-coaster-model").value.trim();
        const park = document.getElementById("add-coaster-park").value.trim();
        const parkId = document.getElementById("add-coaster-park-id").value;
        const country = document
          .getElementById("add-coaster-country")
          .value.trim();
        const unknownYear = document.getElementById("unknown-year").checked;
        const unknownHeight = document.getElementById("unknown-height").checked;
        const unknownSpeed = document.getElementById("unknown-speed").checked;
        const unknownLength = document.getElementById("unknown-length").checked;
        const unknownInversions =
          document.getElementById("unknown-inversions").checked;

        const year = unknownYear
          ? ""
          : document.getElementById("add-coaster-year").value.trim();
        let height = unknownHeight
          ? ""
          : document.getElementById("add-coaster-height").value.trim();
        let speed = unknownSpeed
          ? ""
          : document.getElementById("add-coaster-speed").value.trim();
        let length = unknownLength
          ? ""
          : document.getElementById("add-coaster-length").value.trim();
        let inversions = unknownInversions
          ? ""
          : document.getElementById("add-coaster-inversions").value.trim();

        if (!unknownHeight && height === "") height = "0";
        if (!unknownSpeed && speed === "") speed = "0";
        if (!unknownLength && length === "") length = "0";
        if (!unknownInversions && inversions === "") inversions = "0";
        const status = document
          .getElementById("add-coaster-status")
          .value.trim();
        const image = document.getElementById("add-coaster-image").files[0];
        const existingImageUrl = document.getElementById(
          "add-coaster-image-url",
        ).value;

        if (!name) {
          showModalError("El nombre de la coaster es obligatorio.");
          markError("add-coaster-name");
          return;
        }

        if (
          !manufacturer ||
          document.getElementById("add-coaster-manufacturer").dataset
            .selected !== "true"
        ) {
          showModalError(
            "Por favor selecciona un fabricante de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
          );
          markError("add-coaster-manufacturer");
          return;
        }

        if (
          !model ||
          document.getElementById("add-coaster-model").dataset.selected !==
            "true"
        ) {
          showModalError(
            "Por favor selecciona un modelo de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
          );
          markError("add-coaster-model");
          return;
        }

        if (
          !park ||
          document.getElementById("add-coaster-park").dataset.selected !==
            "true"
        ) {
          showModalError(
            "Por favor selecciona un parque de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
          );
          markError("add-coaster-park");
          return;
        }

        if (
          !country ||
          document.getElementById("add-coaster-country").dataset.selected !==
            "true"
        ) {
          showModalError(
            "Por favor selecciona un país de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
          );
          markError("add-coaster-country");
          return;
        }

        if (!unknownYear) {
          if (!year) {
            showModalError(
              "Por favor introduce el año de apertura. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-year");
            return;
          }
          if (isNaN(year) || year.trim() === "") {
            showModalError(
              "Por favor introduce un año válido. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-year");
            return;
          }
          const currentYear = new Date().getFullYear();
          if (year > currentYear + 10 || year < 1800) {
            showModalError(
              "Por favor introduce un año válido. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-year");
            return;
          }
        }

        if (!unknownHeight) {
          if (!height) {
            showModalError(
              "Por favor introduce la altura. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-height");
            return;
          }
          if (isNaN(height) || height.trim() === "") {
            showModalError(
              "Por favor introduce una altura válida. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-height");
            return;
          }
          if (height > 400 || height < 0) {
            showModalError(
              "Por favor introduce una altura válida. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-height");
            return;
          }
        }

        if (!unknownSpeed) {
          if (!speed) {
            showModalError(
              "Por favor introduce la velocidad. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-speed");
            return;
          }
          if (isNaN(speed) || speed.trim() === "") {
            showModalError(
              "Por favor introduce una velocidad válida. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-speed");
            return;
          }
          if (speed > 400 || speed < 0) {
            showModalError(
              "Por favor introduce una velocidad válida. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-speed");
            return;
          }
        }

        if (!unknownLength) {
          if (!length) {
            showModalError(
              "Por favor introduce la longitud. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-length");
            return;
          }
          if (isNaN(length) || length.trim() === "") {
            showModalError(
              "Por favor introduce una longitud válida. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-length");
            return;
          }
          if (length > 20000 || length < 0) {
            showModalError(
              "Por favor introduce una longitud válida. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-length");
            return;
          }
        }

        if (!unknownInversions) {
          if (!inversions) {
            showModalError(
              "Por favor introduce el número de inversiones. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-inversions");
            return;
          }
          if (isNaN(inversions) || inversions.trim() === "") {
            showModalError(
              "Por favor introduce un número de inversiones válido. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-inversions");
            return;
          }
          if (inversions > 40 || inversions < 0) {
            showModalError(
              "Por favor introduce un número de inversiones válido. (Si no lo conoces, marca la opción 'Desconocido')",
            );
            markError("add-coaster-inversions");
            return;
          }
        }

        if (!status) {
          showModalError("Por favor selecciona un estado.");
          markError("add-coaster-status");
          return;
        }

        const formData = new FormData();
        formData.append("name", name);
        formData.append("manufacturer", manufacturer);
        formData.append("model", model);
        formData.append("park", park);
        formData.append("parkId", parkId);
        formData.append("country", country);
        formData.append("year", year);
        formData.append("height", height);
        formData.append("speed", speed);
        formData.append("length", length);
        formData.append("inversions", inversions);
        formData.append("status", status);

        const imageFile = document.getElementById("add-coaster-image").files[0];
        const existingImg = document.getElementById(
          "add-coaster-image-url",
        ).value;

        if (imageFile) {
          const uploadForm = new FormData();
          const cleanName = imageFile.name.replace(/[^a-zA-Z0-9.-]/g, "_");
          uploadForm.append("file", imageFile, cleanName);
          uploadForm.append("bucket", "coasters");
          uploadForm.append("path", "admin_uploads");

          const uploadRes = await fetch(`${BASE_URL}/api/php/upload.php`, {
            method: "POST",
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
            body: uploadForm,
          });
          const uploadData = await uploadRes.json();
          if (uploadData.success) {
            formData.append("imagenUrl", uploadData.url);
          } else {
            // Fallback local
            formData.append("image", imageFile);
          }
        } else if (existingImg && existingImg.trim() !== "") {
          // Caso duplicado: enviamos la URL que ya teníamos
          formData.append("imagenUrl", existingImg);
        }

        const response = await fetch(
          `${BASE_URL}/api/php/admin/admin_coasters.php?action=addCoaster`,
          {
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
            method: "POST",
            body: formData,
          },
        );

        let data = {};
        const contentType = response.headers.get("content-type") || "";
        if (contentType.includes("application/json")) {
          data = await response.json();
        }

        if (!response.ok) {
          showModalError(
            "Error al añadir coaster: " +
              (data.error || data.message || "HTTP " + response.status),
          );
          return;
        }

        if (data.success) {
          showModalSuccess("Coaster añadida correctamente");
          if (typeof window.loadAdminCoasters === "function")
            window.loadAdminCoasters(1);
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("modal-add-coaster"),
            )?.hide();
          }, 2000);
        } else {
          showModalError(
            "Error al añadir coaster: " +
              (data.error || data.message || "Error desconocido"),
          );
        }
      } catch (error) {
        console.error("Error al añadir coaster:", error);
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-plus me-2"></i>Añadir coaster';
      }
    });

  function showModalError(msg) {
    const container = document.getElementById("add-coaster-messages");
    const error = document.getElementById("add-coaster-error");
    container.classList.remove("d-none");
    error.classList.remove("d-none");
    error.querySelector("span").textContent = msg;
  }

  function showModalSuccess(msg) {
    const container = document.getElementById("add-coaster-messages");
    const success = document.getElementById("add-coaster-success");
    container.classList.remove("d-none");
    success.classList.remove("d-none");
    success.querySelector("span").textContent = msg;
  }

  function markError(id) {
    const el = document.getElementById(id);
    el.closest(".input-group")?.classList.add("field-error");
    el.classList.add("field-error");
  }

  function clearErrors() {
    document.querySelectorAll(".field-error").forEach((el) => {
      el.classList.remove("field-error");
    });
  }

  // ── EDITAR COASTER ─────────────────────────────────────────
  const _modalEditCoaster = document.getElementById("modal-edit-coaster");
  if (_modalEditCoaster) {
    // ── Inicializar autocompletes UNA SOLA VEZ (fuera del show.bs.modal) ──
    initAutocomplete({
      inputId: "edit-coaster-manufacturer",
      dropdownId: "ac-dropdown-edit-manufacturer",
      fetchItems: async (q) => {
        const res = await fetch(
          `${BASE_URL}/api/php/coasters.php?action=manufacter`,
        );
        const data = await res.json();
        if (!data.success) return [];
        const all = [
          { label: "Desconocido", value: "Desconocido", unknown: true },
          ...data.manufacters
            .filter((m) => m.coaster_manufacter)
            .map((m) => ({
              label: m.coaster_manufacter,
              value: m.coaster_manufacter,
            })),
        ];
        return q
          ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
          : all;
      },
      onSelect: () => {},
    });

    initAutocomplete({
      inputId: "edit-coaster-model",
      dropdownId: "ac-dropdown-edit-model",
      fetchItems: async (q) => {
        const url = `${BASE_URL}/api/php/admin/admin_coasters.php?action=listModels&limit=50${q ? "&q=" + encodeURIComponent(q) : ""}`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) return [];
        return [
          { label: "Desconocido", value: "Desconocido", unknown: true },
          ...data.models.map((m) => ({
            label: m.coaster_model,
            value: m.coaster_model,
          })),
        ];
      },
      onSelect: () => {},
    });

    initAutocomplete({
      inputId: "edit-coaster-park",
      dropdownId: "ac-dropdown-edit-park",
      fetchItems: async (q) => {
        const url = `${BASE_URL}/api/php/parks.php?action=list&limit=50${q ? "&q=" + encodeURIComponent(q) : "&sort=name"}`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) return [];
        return [
          {
            label: "Desconocido",
            value: "Desconocido",
            id: "2895",
            unknown: true,
          },
          ...data.data.map((p) => ({
            label: p.park_name,
            sublabel: p.park_country || "",
            value: p.park_name,
            id: p.id,
          })),
        ];
      },
      onSelect: (item) => {
        document.getElementById("edit-coaster-park-id").value = item.id || "";
      },
    });

    initAutocomplete({
      inputId: "edit-coaster-country",
      dropdownId: "ac-dropdown-edit-country",
      fetchItems: async (q) => {
        const res = await fetch(`${BASE_URL}/api/php/parks.php?action=country`);
        const data = await res.json();
        if (!data.success) return [];
        const all = [
          { label: "Desconocido", value: "Desconocido", unknown: true },
          ...data.data
            .filter((c) => c && c.trim() !== "")
            .map((c) => ({ label: c, value: c })),
        ];
        return q
          ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
          : all;
      },
      onSelect: () => {},
    });

    // ── Rellenar datos cada vez que se abre el modal ──
    _modalEditCoaster.addEventListener("show.bs.modal", (event) => {
      const button = event.relatedTarget;

      document.getElementById("edit-coaster-id").value =
        button.getAttribute("data-id");
      document.getElementById("edit-coaster-name").value =
        button.getAttribute("data-name");
      document.getElementById("edit-coaster-park-id").value =
        button.getAttribute("data-park-id") || "";

      const setNumVal = (id, val) => {
        const el = document.getElementById(`edit-coaster-${id}`);
        const chk = document.getElementById(`edit-unknown-${id}`);
        if (!val) {
          el.value = "";
          el.disabled = true;
          el.placeholder = "";
          chk.checked = true;
        } else {
          el.value = val;
          el.disabled = false;
          el.placeholder = "0";
          chk.checked = false;
        }
      };

      clearEditErrors();

      setNumVal("year", button.getAttribute("data-year"));
      setNumVal("height", button.getAttribute("data-height"));
      setNumVal("speed", button.getAttribute("data-speed"));
      setNumVal("length", button.getAttribute("data-length"));
      setNumVal("inversions", button.getAttribute("data-inversions"));
      document.getElementById("edit-coaster-status").value =
        button.getAttribute("data-status");

      // Rellenar los campos autocomplete y marcarlos como seleccionados
      const acMap = {
        "edit-coaster-manufacturer":
          button.getAttribute("data-manufacturer") || "Desconocido",
        "edit-coaster-model":
          button.getAttribute("data-model") || "Desconocido",
        "edit-coaster-park": button.getAttribute("data-park") || "Desconocido",
        "edit-coaster-country":
          button.getAttribute("data-country") || "Desconocido",
      };
      Object.entries(acMap).forEach(([id, val]) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.value = val;
        el.dataset.selected = "true";
      });

      const imageUrl = button.getAttribute("data-image");
      const preview = document.getElementById("edit-coaster-preview");
      if (preview) {
        let validImgUrl = imageUrl;
        if (validImgUrl && !validImgUrl.startsWith("http")) {
          validImgUrl =
            BASE_URL + (validImgUrl.startsWith("/") ? "" : "/") + validImgUrl;
        }
        preview.innerHTML = validImgUrl
          ? `<img src="${validImgUrl}" style="width:100%;height:100%;object-fit:cover;">`
          : "";
      }
    });

    // Auto-open modal if URL has ?edit_coaster=ID
    const editCoasterId = new URLSearchParams(window.location.search).get(
      "edit_coaster",
    );
    if (editCoasterId) {
      fetch(
        `${BASE_URL}/api/php/coasters.php?action=coaster&id=${editCoasterId}`,
      )
        .then((res) => res.json())
        .then((data) => {
          if (data.success && data.coaster) {
            const c = data.coaster;
            const btn = document.createElement("button");
            btn.setAttribute("data-bs-toggle", "modal");
            btn.setAttribute("data-bs-target", "#modal-edit-coaster");
            btn.setAttribute("data-id", c.id);
            btn.setAttribute("data-name", c.coaster_name || "");
            btn.setAttribute("data-year", c.opening_year || "");
            btn.setAttribute("data-height", c.height || "");
            btn.setAttribute("data-speed", c.speed || "");
            btn.setAttribute("data-length", c.coaster_length || "");
            btn.setAttribute("data-inversions", c.inversions || "");
            btn.setAttribute("data-status", c.coaster_status || "");
            btn.setAttribute(
              "data-manufacturer",
              c.coaster_manufacter || "Desconocido",
            );
            btn.setAttribute("data-model", c.coaster_model || "Desconocido");
            btn.setAttribute("data-park", c.park_name || "Desconocido");
            btn.setAttribute("data-park-id", c.park_id || "");
            btn.setAttribute("data-country", c.park_country || "Desconocido");
            btn.setAttribute("data-image", c.imagen_url || "");

            document.body.appendChild(btn);
            btn.click();
            btn.remove();

            // Clean URL
            const url = new URL(window.location);
            url.searchParams.delete("edit_coaster");
            window.history.replaceState({}, document.title, url);
          }
        })
        .catch((err) => console.error("Error fetching coaster for edit:", err));
    }
  }

  /*
  ***************************************
        PARQUES
  ***************************************
  */
  if (document.getElementById("admin-park-search")) {
    const $parkSearchInput = $("#admin-park-search");
    const $parkSearchIcon = $("#admin-park-search-icon");
    const $parkList = $("#admin-park-list");
    const $parkPagination = $("#admin-park-pagination");
    const $parkCount = $("#admin-park-count");

    let parkCurrentPage = 1;
    const PARK_ITEMS_PAGE = 15;
    let parkSearchDebounce = null;

    // ── Poblar selector de países ────────────────────────
    fetch(`${BASE_URL}/api/php/parks.php?action=country`)
      .then((r) => r.json())
      .then((data) => {
        if (data.success && data.data) {
          data.data
            .filter((c) => c && c.trim() !== "")
            .forEach((c) => {
              $("#filter-park-country").append(
                `<option value="${c}">${c}</option>`,
              );
            });
        }
      })
      .catch(() => {});

    // ── Helpers ─────────────────────────────────────────────
    function getParkFilters() {
      return {
        country: $("#filter-park-country").val() || "",
        year: $("#filter-park-year").val() || "",
      };
    }

    function hasActiveParkFilters(f) {
      return f.country || f.year;
    }

    // ── Autocomplete para países (Añadir/Editar Parque) ──
    initAutocomplete({
      inputId: "add-park-country",
      dropdownId: "ac-dropdown-park-country",
      fetchItems: async (q) => {
        const res = await fetch(`${BASE_URL}/api/php/parks.php?action=country`);
        const data = await res.json();
        if (!data.success) return [];
        const all = [
          { label: "Desconocido", value: "Desconocido", unknown: true },
          ...data.data
            .filter((c) => c && c.trim() !== "")
            .map((c) => ({ label: c, value: c })),
        ];
        return q
          ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
          : all;
      },
      onSelect: () => {},
    });

    initAutocomplete({
      inputId: "edit-park-country",
      dropdownId: "ac-dropdown-edit-park-country",
      fetchItems: async (q) => {
        const res = await fetch(`${BASE_URL}/api/php/parks.php?action=country`);
        const data = await res.json();
        if (!data.success) return [];
        const all = [
          { label: "Desconocido", value: "Desconocido", unknown: true },
          ...data.data
            .filter((c) => c && c.trim() !== "")
            .map((c) => ({ label: c, value: c })),
        ];
        return q
          ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
          : all;
      },
      onSelect: () => {},
    });

    // ── Render rows ─────────────────────────────────────────
    function renderParkRows(parks) {
      $parkList.empty();
      if (!parks || parks.length === 0) {
        $parkList.html(
          '<div class="list-group-item text-center text-muted py-4">' +
            '<i class="fa-regular fa-face-frown fa-2x mb-2 d-block"></i>' +
            "No se encontraron parques.</div>",
        );
        return;
      }
      parks.forEach((p) => {
        $parkList.append(`
          <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
            <div class="flex-grow-1">
              <h6 class="mb-0 fw-bold text-success">${p.park_name}</h6>
              <small class="text-muted">
                ${p.park_country || "—"} &bull;
                ${p.park_location || "—"} &bull;
                ${p.opening_year || "—"} &bull;
                <i class="fa-solid fa-train-tram me-1"></i>${p.operating_coasters || 0} op. / ${p.num_coasters || 0} total
              </small>
            </div>
            <div class="d-flex gap-2 ms-3 flex-shrink-0">
              <button class="btn btn-sm btn-outline-primary rounded-0 btn-edit-park"
                data-id="${p.id}"
                data-name="${(p.park_name || "").replace(/"/g, "&quot;")}">
                <i class="fa-solid fa-pen"></i> Editar
              </button>
              <button class="btn btn-sm btn-outline-warning rounded-0 btn-duplicate-park"
                data-id="${p.id}"
                data-name="${(p.park_name || "").replace(/"/g, "&quot;")}">
                <i class="fa-solid fa-copy"></i> Duplicar
              </button>
              <button class="btn btn-sm btn-outline-danger rounded-0 btn-delete-park"
                data-id="${p.id}"
                data-name="${(p.park_name || "").replace(/"/g, "&quot;")}">
                <i class="fa-solid fa-trash"></i> Eliminar
              </button>
            </div>
          </div>
        `);
      });
    }

    // ── Paginación ───────────────────────────────────────────
    function renderParkPagination(total, page) {
      $parkPagination.empty();
      const totalPages = Math.ceil(total / PARK_ITEMS_PAGE);
      if (totalPages <= 1) return;

      const nav = $('<nav aria-label="Paginación parques"></nav>');
      const ul = $('<ul class="pagination pagination-sm mb-0"></ul>');

      ul.append(
        `<li class="page-item ${page === 1 ? "disabled" : ""}"><button class="page-link rounded-0" data-page="1" title="Primera">&#171;</button></li>`,
      );
      ul.append(
        `<li class="page-item ${page === 1 ? "disabled" : ""}"><button class="page-link rounded-0" data-page="${page - 1}">&#8249;</button></li>`,
      );

      let start = Math.max(1, page - 3);
      let end = Math.min(totalPages, start + 6);
      start = Math.max(1, end - 6);

      if (start > 1)
        ul.append(
          '<li class="page-item disabled"><span class="page-link">…</span></li>',
        );
      for (let i = start; i <= end; i++) {
        ul.append(
          `<li class="page-item ${i === page ? "active" : ""}"><button class="page-link rounded-0" data-page="${i}">${i}</button></li>`,
        );
      }
      if (end < totalPages)
        ul.append(
          '<li class="page-item disabled"><span class="page-link">…</span></li>',
        );

      ul.append(
        `<li class="page-item ${page === totalPages ? "disabled" : ""}"><button class="page-link rounded-0" data-page="${page + 1}">&#8250;</button></li>`,
      );
      ul.append(
        `<li class="page-item ${page === totalPages ? "disabled" : ""}"><button class="page-link rounded-0" data-page="${totalPages}" title="Última">&#187;</button></li>`,
      );

      nav.append(ul);
      $parkPagination.append(nav);

      $parkPagination
        .find("button.page-link[data-page]")
        .on("click", function () {
          const p = parseInt($(this).data("page"));
          if (p < 1 || p > totalPages) return;
          loadParks(p);
          window.scrollTo({
            top: $parkList.offset().top - 80,
            behavior: "smooth",
          });
        });
    }

    // ── Carga principal ──────────────────────────────────────
    async function loadParks(page) {
      page = page || 1;
      parkCurrentPage = page;

      const search = $parkSearchInput.val().trim();
      const filters = getParkFilters();
      const useSearch = search.length >= 3;
      const useFilters = hasActiveParkFilters(filters);

      if (!useSearch && !useFilters) {
        $parkList.html(
          '<div class="list-group-item text-center text-muted py-5">' +
            '<i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>' +
            "Usa el buscador o activa un filtro para ver parques.</div>",
        );
        $parkCount.text("");
        $parkPagination.empty();
        return;
      }

      $parkList.html(
        '<div class="list-group-item text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando...</div>',
      );

      try {
        let url;
        if (useSearch) {
          url = `${BASE_URL}/api/php/admin/admin_parks.php?action=searchParks&search=${encodeURIComponent(search)}&page=${page}`;
        } else {
          const params = new URLSearchParams({ action: "filterParks", page });
          if (filters.country) params.set("country", filters.country);
          if (filters.year) params.set("year", filters.year);
          url = `${BASE_URL}/api/php/admin/admin_parks.php?${params}`;
        }

        const res = await fetch(url, {
          credentials: "include",
        });
        const data = await res.json();

        if (data.success) {
          const total = data.total || 0;
          $parkCount.text(`Mostrando ${total} parque${total !== 1 ? "s" : ""}`);
          renderParkRows(data.parks);
          renderParkPagination(total, page);
        } else {
          $parkCount.text("");
          $parkList.html(
            '<div class="list-group-item text-center text-danger py-4">' +
              (data.error || "Error desconocido") +
              "</div>",
          );
          $parkPagination.empty();
        }
      } catch (err) {
        console.error("Error cargando parques:", err);
      }
    }

    // ── Lupa ↔ X roja ────────────────────────────────────────
    $parkSearchInput.on("input", function () {
      if ($(this).val().length > 0) {
        $parkSearchIcon
          .removeClass("fa-magnifying-glass text-muted")
          .addClass("fa-xmark text-danger")
          .css("cursor", "pointer");
      } else {
        $parkSearchIcon
          .removeClass("fa-xmark text-danger")
          .addClass("fa-magnifying-glass text-muted")
          .css("cursor", "default");
      }
    });

    $parkSearchIcon.on("click", function () {
      if ($(this).hasClass("fa-xmark")) {
        $parkSearchInput.val("").trigger("input").focus();
        loadParks(1);
      }
    });

    $parkSearchInput.on("keyup", function () {
      clearTimeout(parkSearchDebounce);
      parkSearchDebounce = setTimeout(() => loadParks(1), 350);
    });

    $("#btn-park-filtrar").on("click", () => loadParks(1));

    $("#btn-park-borrar").on("click", function () {
      $("#filter-park-country, #filter-park-year").val("");
      $parkSearchInput.val("").trigger("input");
      loadParks(1);
    });

    // ── Modal borrar ─────────────────────────────────────────
    $(document).on("click", ".btn-delete-park", function () {
      $("#delete-park-name").text($(this).data("name"));
      $("#confirm-delete-park").data("id", $(this).data("id"));
      new bootstrap.Modal(document.getElementById("modal-delete-park")).show();
    });

    // ── Confirmar eliminar ────────────────────────────────────
    $(document).on("click", "#confirm-delete-park", async function () {
      const id = $(this).data("id");
      const btn = this;
      btn.disabled = true;
      btn.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin me-1"></i>Eliminando...';
      try {
        const fd = new FormData();
        fd.append("id", id);
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_parks.php?action=deletePark`,
          {
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
            method: "POST",
            credentials: "include",
            body: fd,
          },
        );
        const data = await res.json();
        if (data.success) {
          bootstrap.Modal.getInstance(
            document.getElementById("modal-delete-park"),
          )?.hide();
          loadParks(parkCurrentPage);
        } else {
          alert("Error: " + (data.error || "No se pudo eliminar."));
        }
      } catch (e) {
        console.error(e);
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i>Eliminar';
      }
    });

    // ── Modal duplicar ─────────────────────────────────────────
    $(document).on("click", ".btn-duplicate-park", function () {
      $("#duplicate-park-name").text($(this).data("name"));
      $("#confirm-duplicate-park").data("id", $(this).data("id"));
      new bootstrap.Modal(
        document.getElementById("modal-duplicate-park"),
      ).show();
    });

    $(document).on("click", "#confirm-duplicate-park", async function () {
      const id = $(this).data("id");
      const btn = this;
      btn.disabled = true;
      btn.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin me-1"></i>Duplicando...';
      try {
        const fd = new FormData();
        fd.append("id", id);
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_parks.php?action=duplicatePark`,
          {
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
            method: "POST",
            credentials: "include",
            body: fd,
          },
        );
        const data = await res.json();
        if (data.success) {
          bootstrap.Modal.getInstance(
            document.getElementById("modal-duplicate-park"),
          )?.hide();
          loadParks(parkCurrentPage);
        } else {
          alert("Error: " + (data.error || "No se pudo duplicar."));
        }
      } catch (e) {
        console.error(e);
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-copy me-1"></i>Duplicar';
      }
    });
  }

  // ── Modal añadir parque ───────────────────────────────────────────────────────
  const _btnAddPark = document.getElementById("btn-add-park");
  if (_btnAddPark) {
    _btnAddPark.addEventListener("click", function () {
      new bootstrap.Modal(document.getElementById("modal-add-park")).show();
    });
  }

  const _modalAddPark = document.getElementById("modal-add-park");
  if (_modalAddPark) {
    // Autocomplete de país al abrir el modal
    _modalAddPark.addEventListener("show.bs.modal", function () {
      initAutocomplete({
        inputId: "add-park-country",
        dropdownId: "ac-dropdown-park-country",
        fetchItems: async (q) => {
          const res = await fetch(
            `${BASE_URL}/api/php/parks.php?action=country`,
          );
          const data = await res.json();
          if (!data.success) return [];
          const all = [
            { label: "Desconocido", value: "Desconocido", unknown: true },
            ...data.data
              .filter((c) => c && c.trim() !== "")
              .map((c) => ({ label: c, value: c })),
          ];
          return q
            ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
            : all;
        },
        onSelect: () => {},
      });

      // Cargar coasters desconocidas
      loadUnknownCoasters("");

      // Buscador dentro de la lista de coasters con debounce
      const searchInput = document.getElementById("add-park-coasters-search");
      let searchTimeout;
      if (searchInput) {
        searchInput.addEventListener("input", function () {
          clearTimeout(searchTimeout);
          const val = this.value.trim();
          searchTimeout = setTimeout(() => {
            loadUnknownCoasters(val);
          }, 350);
        });
      }
    });

    // Reset al cerrar
    _modalAddPark.addEventListener("hidden.bs.modal", function () {
      document.getElementById("add-park-name").value = "";
      document.getElementById("add-park-country").value = "";
      document.getElementById("add-park-country").dataset.selected = "false";
      document.getElementById("add-park-location").value = "";
      document.getElementById("add-park-year").value = "";
      document.getElementById("add-park-year").disabled = false;
      document.getElementById("unknown-park-year").checked = false;
      document.getElementById("add-park-coasters-search").value = "";
      document.getElementById("add-park-coasters-ids").value = "";
      document.getElementById("park-coasters-badge").textContent =
        "0 seleccionadas";
      document.getElementById("add-park-messages").classList.add("d-none");
      document.getElementById("add-park-error").classList.add("d-none");
      document.getElementById("add-park-success").classList.add("d-none");
    });

    // ── Cargar lista de coasters sin parque (desconocido) ───────────────────────
    // Listado inicial basado solo en IDs seleccionados (sin la cache gigante)
    let _selectedCoasterIds = []; // IDs marcados

    async function loadUnknownCoasters(q) {
      const list = document.getElementById("add-park-coasters-list");

      list.innerHTML =
        '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-success"></div> Cargando...</div>';
      try {
        const url = `${BASE_URL}/api/php/admin/admin_parks.php?action=unknownCoasters${q ? "&q=" + encodeURIComponent(q) : ""}`;
        const res = await fetch(url, {
          credentials: "include",
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        // Siempre renderizar la nueva lista traída desde la BBDD (máx 300 resultados ahora)
        renderCoastersList(data.coasters, list);
      } catch (err) {
        list.innerHTML =
          '<div class="text-center text-danger py-3 small">Error al cargar coasters.</div>';
        return;
      }
    }

    function renderCoastersList(coasters, listElement) {
      if (!coasters.length) {
        listElement.innerHTML =
          '<div class="text-center text-muted py-3 small"><i class="fa-regular fa-face-frown me-1"></i>No se encontraron coasters sin parque asignado.</div>';
        return;
      }

      listElement.innerHTML = coasters
        .map((c) => {
          const checked = _selectedCoasterIds.includes(c.id);
          const isOp = c.coaster_status === "Operating";
          return `<div class="form-check d-flex justify-content-between align-items-center px-3 py-2" style="border-bottom:1px solid rgba(255,255,255,0.05); margin:0; padding-left: 1rem !important;">
            <label class="form-check-label text-light small d-flex align-items-center flex-grow-1" for="uc-${c.id}" style="cursor:pointer; padding-top:2px;">
              <span class="text-truncate">${c.coaster_name}</span>
              <span class="badge ms-2 ${isOp ? "bg-success" : "bg-secondary"}" style="font-size:0.65rem;">${c.coaster_status || "—"}</span>
            </label>
            <input class="form-check-input unknown-coaster-check mt-0 ms-3 flex-shrink-0" type="checkbox"
              id="uc-${c.id}" value="${c.id}" ${checked ? "checked" : ""}
              style="width: 1.2rem; height: 1.2rem; background-color: #0d1117; border: 2px solid #30363d; cursor: pointer;">
          </div>`;
        })
        .join("");

      // Eventos de checkbox
      listElement.querySelectorAll(".unknown-coaster-check").forEach((cb) => {
        cb.addEventListener("change", function () {
          const id = parseInt(this.value);
          if (this.checked) {
            if (!_selectedCoasterIds.includes(id)) _selectedCoasterIds.push(id);
          } else {
            _selectedCoasterIds = _selectedCoasterIds.filter((x) => x !== id);
          }
          document.getElementById("add-park-coasters-ids").value =
            _selectedCoasterIds.join(",");
          document.getElementById("park-coasters-badge").textContent =
            `${_selectedCoasterIds.length} seleccionada${_selectedCoasterIds.length !== 1 ? "s" : ""}`;
        });
      });
    }

    // --- Previsualización de imagen para nuevo parque ---
    const addParkImage = document.getElementById("add-park-image");
    const addParkPreview = document.getElementById("add-park-preview");
    const addParkPreviewContainer = document.getElementById(
      "add-park-preview-container",
    );
    const addParkDropzoneText = document.getElementById(
      "add-park-dropzone-text",
    );

    addParkImage?.addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          addParkPreview.src = e.target.result;
          addParkPreviewContainer.classList.remove("d-none");
          addParkDropzoneText.textContent = file.name;
        };
        reader.readAsDataURL(file);
      } else {
        addParkPreviewContainer.classList.add("d-none");
        addParkDropzoneText.textContent = "Subir imagen";
      }
    });

    // ── Confirmar añadir parque ──────────────────────────────────────────────────
    document
      .getElementById("confirm-add-park")
      .addEventListener("click", async function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML =
          'Añadiendo... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

        const showParkError = (msg) => {
          const c = document.getElementById("add-park-messages");
          const e = document.getElementById("add-park-error");
          c.classList.remove("d-none");
          e.classList.remove("d-none");
          e.querySelector("span").textContent = msg;
        };
        const showParkSuccess = (msg) => {
          const c = document.getElementById("add-park-messages");
          const s = document.getElementById("add-park-success");
          c.classList.remove("d-none");
          s.classList.remove("d-none");
          s.querySelector("span").textContent = msg;
        };
        const clearParkErrors = () => {
          document
            .querySelectorAll("#modal-add-park .field-error")
            .forEach((el) => el.classList.remove("field-error"));
          document.getElementById("add-park-messages").classList.add("d-none");
          document.getElementById("add-park-error").classList.add("d-none");
          document.getElementById("add-park-success").classList.add("d-none");
        };
        const markParkError = (id) => {
          const el = document.getElementById(id);
          el?.closest(".input-group")?.classList.add("field-error");
          el?.classList.add("field-error");
        };

        try {
          clearParkErrors();
          const name = document.getElementById("add-park-name").value.trim();
          const country = document
            .getElementById("add-park-country")
            .value.trim();
          const countryOk =
            document.getElementById("add-park-country").dataset.selected ===
            "true";
          const location = document
            .getElementById("add-park-location")
            .value.trim();
          const yearEl = document.getElementById("add-park-year");
          const yearUnknown =
            document.getElementById("unknown-park-year").checked;
          const year = yearEl.value.trim();
          const coasterIdsRaw = document.getElementById(
            "add-park-coasters-ids",
          ).value;
          const imageFile = addParkImage?.files[0];

          if (!name) {
            showParkError("El nombre del parque es obligatorio.");
            markParkError("add-park-name");
            return;
          }
          if (!country || !countryOk) {
            showParkError("Por favor selecciona un país de la lista.");
            markParkError("add-park-country");
            return;
          }
          if (!location) {
            showParkError("La localización es obligatoria.");
            markParkError("add-park-location");
            return;
          }
          if (!yearUnknown) {
            if (!year) {
              showParkError(
                "Introduce el año de apertura o marca 'Desconocido'.",
              );
              markParkError("add-park-year");
              return;
            }
            const y = parseInt(year);
            if (isNaN(y) || y < 1800 || y > new Date().getFullYear() + 10) {
              showParkError("Introduce un año válido.");
              markParkError("add-park-year");
              return;
            }
          }

          const formData = new FormData();
          formData.append("name", name);
          formData.append("country", country);
          formData.append("location", location);
          formData.append("year", yearUnknown ? "" : year);
          formData.append(
            "website",
            document.getElementById("add-park-website")?.value.trim() || "",
          );
          formData.append(
            "precio_entrada",
            document.getElementById("add-park-price")?.value.trim() || "",
          );
          formData.append("coasterIds", coasterIdsRaw);
          if (imageFile) {
            formData.append("image", imageFile);
          }

          const res = await fetch(
            `${BASE_URL}/api/php/admin/admin_parks.php?action=addPark`,
            {
              headers: {
                "X-CSRF-Token":
                  document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ?? "",
              },
              method: "POST",
              credentials: "include",
              body: formData,
            },
          );
          const data = await res.json();

          if (data.success) {
            showParkSuccess("Parque añadido correctamente.");
            _selectedCoasterIds = [];

            if (sessionStorage.getItem("pendingCoasterData")) {
              setTimeout(() => {
                const parkNameParam = encodeURIComponent(
                  document.getElementById("add-park-name").value.trim(),
                );
                window.location.href = `${BASE_URL}/web/views/admin/coasters.php?action=resume_coaster&park_id=${data.id}&park_name=${parkNameParam}`;
              }, 1000);
            } else {
              setTimeout(() => window.location.reload(), 1500);
            }
          } else {
            showParkError("Error: " + (data.error || "Error desconocido"));
          }
        } catch (err) {
          console.error("Error al añadir parque:", err);
        } finally {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-plus me-2"></i>Añadir parque';
        }
      });
  }

  // ══════════════════════════════════════════════════════════════
  //  MODAL EDITAR PARQUE
  // ══════════════════════════════════════════════════════════════
  const _modalEditPark = document.getElementById("modal-edit-park");
  if (_modalEditPark) {
    // Autocomplete de país (inicializar una sola vez)
    initAutocomplete({
      inputId: "edit-park-country",
      dropdownId: "ac-dropdown-edit-park-country",
      fetchItems: async (q) => {
        const res = await fetch(`${BASE_URL}/api/php/parks.php?action=country`);
        const data = await res.json();
        if (!data.success) return [];
        const all = [
          { label: "Desconocido", value: "Desconocido", unknown: true },
          ...data.data
            .filter((c) => c && c.trim())
            .map((c) => ({ label: c, value: c })),
        ];
        return q
          ? all.filter((i) => i.label.toLowerCase().includes(q.toLowerCase()))
          : all;
      },
      onSelect: () => {},
    });

    // Preview imagen edit
    const editParkImage = document.getElementById("edit-park-image");
    const editParkPreview = document.getElementById("edit-park-preview");
    editParkImage?.addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          editParkPreview.src = e.target.result;
          editParkPreview.style.display = "";
          document.getElementById("edit-park-dropzone-text").textContent =
            file.name;
        };
        reader.readAsDataURL(file);
      }
    });

    // Lista de coasters del parque + desconocidas
    let _editSelectedCoasterIds = [];

    window.loadEditParkCoasters = async function loadEditParkCoasters(
      q,
      parkId,
      isInitialLoad = false,
    ) {
      const list = document.getElementById("edit-park-coasters-list");
      if (!list) return;
      list.innerHTML =
        '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</div>';
      try {
        const url = `${BASE_URL}/api/php/admin/admin_parks.php?action=getParkCoasters&park_id=${parkId}${q ? "&q=" + encodeURIComponent(q) : ""}`;
        const res = await fetch(url, {
          credentials: "include",
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        window.renderEditCoastersList(
          data.coasters,
          list,
          parkId,
          isInitialLoad,
        );
      } catch (e) {
        list.innerHTML =
          '<div class="text-center text-danger py-3 small">Error al cargar coasters.</div>';
      }
    };

    window.renderEditCoastersList = function renderEditCoastersList(
      coasters,
      listEl,
      parkId,
      isInitialLoad = false,
    ) {
      if (!coasters.length) {
        listEl.innerHTML =
          '<div class="text-center text-muted py-3 small">No hay coasters disponibles.</div>';
        return;
      }

      // Solo en la carga inicial inicializamos la selección desde el servidor
      // En búsquedas posteriores respetamos la selección actual del usuario
      if (isInitialLoad) {
        _editSelectedCoasterIds = coasters
          .filter(
            (c) => c.in_park === true || c.in_park === "1" || c.in_park === 1,
          )
          .map((c) => parseInt(c.id));
        document.getElementById("edit-park-coasters-ids").value =
          _editSelectedCoasterIds.join(",");
        document.getElementById("edit-park-coasters-badge").textContent =
          `${_editSelectedCoasterIds.length} asignada${_editSelectedCoasterIds.length !== 1 ? "s" : ""}`;
      }

      // El estado checked se basa SIEMPRE en _editSelectedCoasterIds actual
      listEl.innerHTML = coasters
        .map((c) => {
          const checked = _editSelectedCoasterIds.includes(parseInt(c.id));
          const isOp = c.coaster_status === "Operating";
          const sep = checked
            ? "rgba(13,110,253,0.15)"
            : "rgba(255,255,255,0.05)";
          return `<div class="form-check d-flex justify-content-between align-items-center px-3 py-2"
                     style="border-bottom:1px solid ${sep};margin:0;padding-left:1rem !important;">
          <label class="form-check-label text-light small d-flex align-items-center flex-grow-1" for="ec-${c.id}" style="cursor:pointer;padding-top:2px;">
            <span class="text-truncate">${c.coaster_name}</span>
            <span class="badge ms-2 ${isOp ? "bg-success" : "bg-secondary"}" style="font-size:0.65rem;">${c.coaster_status || "—"}</span>
          </label>
          <input class="form-check-input edit-coaster-check mt-0 ms-3 flex-shrink-0" type="checkbox"
            id="ec-${c.id}" value="${c.id}" ${checked ? "checked" : ""}
            style="width:1.2rem;height:1.2rem;background-color:#0d1117;border:2px solid #30363d;cursor:pointer;">
        </div>`;
        })
        .join("");

      listEl.querySelectorAll(".edit-coaster-check").forEach((cb) => {
        cb.addEventListener("change", function () {
          const id = parseInt(this.value);
          if (this.checked) {
            if (!_editSelectedCoasterIds.includes(id))
              _editSelectedCoasterIds.push(id);
          } else {
            _editSelectedCoasterIds = _editSelectedCoasterIds.filter(
              (x) => x !== id,
            );
          }
          document.getElementById("edit-park-coasters-ids").value =
            _editSelectedCoasterIds.join(",");
          document.getElementById("edit-park-coasters-badge").textContent =
            `${_editSelectedCoasterIds.length} asignada${_editSelectedCoasterIds.length !== 1 ? "s" : ""}`;
        });
      });
    };

    // Buscador dentro del modal editar (con debounce) — isInitialLoad = false
    let _editCoasterSearchTimeout;
    document
      .getElementById("edit-park-coasters-search")
      ?.addEventListener("input", function () {
        clearTimeout(_editCoasterSearchTimeout);
        const parkId = document.getElementById("edit-park-id")?.value;
        _editCoasterSearchTimeout = setTimeout(
          () => loadEditParkCoasters(this.value.trim(), parkId, false),
          350,
        );
      });

    // ── Abrir modal editar (carga datos por API) — registrado aquí para funcionar también en park.php ──
    $(document).on("click", ".btn-edit-park", async function () {
      const id = $(this).data("id");
      try {
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_parks.php?action=getPark&id=${id}`,
          { credentials: "include" },
        );
        const data = await res.json();
        if (!data.success) {
          alert("Error cargando el parque.");
          return;
        }
        const p = data.park;

        document.getElementById("edit-park-id").value = p.id;
        document.getElementById("edit-park-name").value = p.park_name || "";
        document.getElementById("edit-park-location").value =
          p.park_location || "";
        document.getElementById("edit-park-website").value = p.website || "";
        document.getElementById("edit-park-price").value =
          p.precio_entrada || "";

        // País (autocomplete)
        const cntryEl = document.getElementById("edit-park-country");
        cntryEl.value = p.park_country || "";
        cntryEl.dataset.selected = "true";

        // Año
        const yearEl = document.getElementById("edit-park-year");
        const yearChk = document.getElementById("unknown-edit-park-year");
        if (p.opening_year) {
          yearEl.value = p.opening_year;
          yearEl.disabled = false;
          yearChk.checked = false;
        } else {
          yearEl.value = "";
          yearEl.disabled = true;
          yearChk.checked = true;
        }

        // Imagen preview
        const preview = document.getElementById("edit-park-preview");
        if (p.imagen_url) {
          let imgUrl = p.imagen_url.startsWith("/")
            ? BASE_URL + p.imagen_url
            : p.imagen_url;
          preview.src = imgUrl;
          preview.style.display = "";
        } else {
          preview.src = "";
          preview.style.display = "none";
        }
        document.getElementById("edit-park-dropzone-text").textContent =
          "Cambiar imagen";
        document.getElementById("edit-park-image").value = "";

        // Reset mensajes
        document.getElementById("edit-park-messages").classList.add("d-none");
        document.getElementById("edit-park-error").classList.add("d-none");
        document.getElementById("edit-park-success").classList.add("d-none");
        document.getElementById("edit-park-coasters-ids").value = "";

        new bootstrap.Modal(document.getElementById("modal-edit-park")).show();

        // Cargar coasters (lazy: después de abrir el modal)
        window.loadEditParkCoasters("", id, true);
      } catch (e) {
        console.error("Error abriendo edición de parque:", e);
      }
    });

    // ── Confirmar guardar edición ───────────────────────────────
    document
      .getElementById("confirm-edit-park")
      ?.addEventListener("click", async function () {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML =
          'Guardando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

        const showErr = (msg) => {
          const c = document.getElementById("edit-park-messages");
          const e = document.getElementById("edit-park-error");
          c.classList.remove("d-none");
          e.classList.remove("d-none");
          e.querySelector("span").textContent = msg;
        };
        const showOk = (msg) => {
          const c = document.getElementById("edit-park-messages");
          const s = document.getElementById("edit-park-success");
          c.classList.remove("d-none");
          s.classList.remove("d-none");
          s.querySelector("span").textContent = msg;
        };

        try {
          document.getElementById("edit-park-messages").classList.add("d-none");
          document.getElementById("edit-park-error").classList.add("d-none");
          document.getElementById("edit-park-success").classList.add("d-none");

          const id = document.getElementById("edit-park-id").value;
          const name = document.getElementById("edit-park-name").value.trim();
          const country = document
            .getElementById("edit-park-country")
            .value.trim();
          const location = document
            .getElementById("edit-park-location")
            .value.trim();
          const yearEl = document.getElementById("edit-park-year");
          const yearChk = document.getElementById("unknown-edit-park-year");
          const year = yearChk.checked ? "" : yearEl.value.trim();
          const website = document
            .getElementById("edit-park-website")
            .value.trim();
          const precio = document
            .getElementById("edit-park-price")
            .value.trim();
          const coasterIds = document.getElementById(
            "edit-park-coasters-ids",
          ).value;
          const imgFile = editParkImage?.files[0];

          if (!name) {
            showErr("El nombre es obligatorio.");
            return;
          }
          if (!country) {
            showErr("El país es obligatorio.");
            return;
          }
          if (!location) {
            showErr("La localización es obligatoria.");
            return;
          }

          const fd = new FormData();
          fd.append("id", id);
          fd.append("name", name);
          fd.append("country", country);
          fd.append("location", location);
          fd.append("year", year);
          fd.append("website", website);
          fd.append("precio_entrada", precio);
          fd.append("coasterIds", coasterIds);
          if (imgFile) fd.append("image", imgFile);

          const res = await fetch(
            `${BASE_URL}/api/php/admin/admin_parks.php?action=editPark`,
            {
              headers: {
                "X-CSRF-Token":
                  document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") ?? "",
              },
              method: "POST",
              credentials: "include",
              body: fd,
            },
          );
          const data = await res.json();

          if (data.success) {
            showOk("Parque actualizado correctamente.");
            setTimeout(() => {
              bootstrap.Modal.getInstance(_modalEditPark)?.hide();
              if (typeof window.loadAdminParks === "function")
                window.loadAdminParks(parkCurrentPage);
            }, 1500);
          } else {
            showErr("Error: " + (data.error || "No se pudo guardar."));
          }
        } catch (e) {
          console.error(e);
          showErr("Error inesperado.");
        } finally {
          btn.disabled = false;
          btn.innerHTML =
            '<i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios';
        }
      });

    // Reset al cerrar el modal de editar
    _modalEditPark.addEventListener("hidden.bs.modal", function () {
      document.getElementById("edit-park-messages").classList.add("d-none");
      document.getElementById("edit-park-error").classList.add("d-none");
      document.getElementById("edit-park-success").classList.add("d-none");
      document.getElementById("edit-park-image").value = "";
      _editSelectedCoasterIds = [];
    });

    // Cargar parques al inicio (solo en la vista admin que tiene la lista)
    if (typeof window.loadAdminParks === "function") window.loadAdminParks(1);
  }

  // ── Manejar acciones tras redirecciones ─────────────────────────────────
  const urlParams = new URLSearchParams(window.location.search);
  const actionParam = urlParams.get("action");

  if (actionParam === "add_park") {
    const parkModalEl = document.getElementById("modal-add-park");
    if (parkModalEl) {
      new bootstrap.Modal(parkModalEl).show();
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  } else if (actionParam === "resume_coaster") {
    const coasterModalEl = document.getElementById("modal-add-coaster");
    if (coasterModalEl) {
      const saved = sessionStorage.getItem("pendingCoasterData");
      if (saved) {
        try {
          const data = JSON.parse(saved);
          document.getElementById("add-coaster-name").value = data.name || "";
          document.getElementById("add-coaster-manufacturer").value =
            data.manufacturer || "";
          document.getElementById("add-coaster-manufacturer").dataset.selected =
            data.manufacturerRaw || "false";
          document.getElementById("add-coaster-model").value = data.model || "";
          document.getElementById("add-coaster-model").dataset.selected =
            data.modelRaw || "false";
          document.getElementById("add-coaster-country").value =
            data.country || "";
          document.getElementById("add-coaster-country").dataset.selected =
            data.countryRaw || "false";
          document.getElementById("add-coaster-year").value = data.year || "";
          document.getElementById("add-coaster-height").value =
            data.height || "";
          document.getElementById("add-coaster-speed").value = data.speed || "";
          document.getElementById("add-coaster-length").value =
            data.length || "";
          document.getElementById("add-coaster-inversions").value =
            data.inversions || "";
          document.getElementById("add-coaster-status").value =
            data.status || "";
        } catch (e) {
          console.error("Error cargando coaster data", e);
        }
        sessionStorage.removeItem("pendingCoasterData");
      }

      const newParkId = urlParams.get("park_id");
      const newParkName = urlParams.get("park_name");
      if (newParkId && newParkName) {
        document.getElementById("add-coaster-park").value = newParkName;
        document.getElementById("add-coaster-park").dataset.selected = "true";
        document.getElementById("add-coaster-park-id").value = newParkId;
      }

      new bootstrap.Modal(coasterModalEl).show();
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  // ── Abrir modal editar parque desde ?edit_park=ID ────────────────────────
  const editParkParam = urlParams.get("edit_park");
  if (editParkParam) {
    const parkId = parseInt(editParkParam);
    if (parkId) {
      // Simular click en btn-edit-park para reutilizar toda la lógica
      // Cargamos datos y abrimos el modal directamente
      (async () => {
        try {
          const res = await fetch(
            `${BASE_URL}/api/php/admin/admin_parks.php?action=getPark&id=${parkId}`,
            { credentials: "include" },
          );
          const data = await res.json();
          if (!data.success) {
            console.error("No se pudo cargar el parque para edición.");
            return;
          }
          const p = data.park;

          document.getElementById("edit-park-id").value = p.id;
          document.getElementById("edit-park-name").value = p.park_name || "";
          document.getElementById("edit-park-location").value =
            p.park_location || "";
          document.getElementById("edit-park-website").value = p.website || "";
          document.getElementById("edit-park-price").value =
            p.precio_entrada || "";

          const cntryEl = document.getElementById("edit-park-country");
          cntryEl.value = p.park_country || "";
          cntryEl.dataset.selected = "true";

          const yearEl = document.getElementById("edit-park-year");
          const yearChk = document.getElementById("unknown-edit-park-year");
          if (p.opening_year) {
            yearEl.value = p.opening_year;
            yearEl.disabled = false;
            yearChk.checked = false;
          } else {
            yearEl.value = "";
            yearEl.disabled = true;
            yearChk.checked = true;
          }

          const preview = document.getElementById("edit-park-preview");
          if (p.imagen_url) {
            preview.src = p.imagen_url.startsWith("/")
              ? BASE_URL + p.imagen_url
              : p.imagen_url;
            preview.style.display = "";
          } else {
            preview.src = "";
            preview.style.display = "none";
          }

          document.getElementById("edit-park-messages").classList.add("d-none");
          document.getElementById("edit-park-error").classList.add("d-none");
          document.getElementById("edit-park-success").classList.add("d-none");
          document.getElementById("edit-park-coasters-ids").value = "";

          new bootstrap.Modal(
            document.getElementById("modal-edit-park"),
          ).show();
          window.loadEditParkCoasters("", parkId, true);
          window.history.replaceState(
            {},
            document.title,
            window.location.pathname,
          );
        } catch (e) {
          console.error("Error abriendo modal editar parque desde URL:", e);
        }
      })();
    }
  }

  // ── Modal borrar coaster  ─────────────────────────────────────────
  const _btnConfirmDelete = document.getElementById("confirm-delete-coaster");
  if (_btnConfirmDelete) {
    _btnConfirmDelete.addEventListener("click", async function () {
      const btn = this;
      const coasterId = $(btn).data("id");
      const coasterName =
        document.getElementById("delete-coaster-name").innerText ||
        "la coaster";

      btn.disabled = true;
      btn.innerHTML =
        'Eliminando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

      try {
        const res = await fetch(
          `${BASE_URL}/api/php/admin/admin_coasters.php?action=deleteCoaster`,
          {
            method: "POST",
            credentials: "include",
            headers: {
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ coasterId }),
          },
        );
        const data = await res.json();

        // Cerrar modal de confirmación
        const deleteModalEl = document.getElementById("modal-delete-coaster");
        const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
        if (deleteModal) deleteModal.hide();

        // Preparar modal de notificación
        const notifModalEl = document.getElementById("modal-notification");
        const notifTitle = document.getElementById("modal-notification-title");
        const notifMsg = document.getElementById("modal-notification-message");
        const notifIcon = document.getElementById("modal-notification-icon");
        const notifHeader = document.getElementById(
          "modal-notification-header",
        );

        if (data.success) {
          notifHeader.className =
            "modal-header border-0 py-3 px-4 bg-success text-white";
          notifTitle.textContent = "Coaster eliminada";
          notifIcon.className =
            "fa-solid fa-circle-check text-success fs-1 mb-3";
          notifMsg.textContent = `"${coasterName}" se ha eliminado correctamente.`;

          // Refrescar la lista
          if (typeof window.loadAdminCoasters === "function") {
            window.loadAdminCoasters(1);
          }
        } else {
          notifHeader.className =
            "modal-header border-0 py-3 px-4 bg-danger text-white";
          notifTitle.textContent = "Error al eliminar";
          notifIcon.className =
            "fa-solid fa-circle-exclamation text-danger fs-1 mb-3";
          notifMsg.textContent =
            data.error || `No se ha podido eliminar "${coasterName}".`;
        }

        // Esperar a que el modal de borrar cierre antes de abrir el de notificación
        deleteModalEl.addEventListener(
          "hidden.bs.modal",
          function _onHidden() {
            deleteModalEl.removeEventListener("hidden.bs.modal", _onHidden);
            new bootstrap.Modal(notifModalEl).show();
          },
          { once: true },
        );
      } catch (error) {
        console.error("Error al eliminar:", error);
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-trash me-1"></i>Eliminar';
      }
    });
  }
});

// ── Modal editar coaster: preview imagen ─────────────────────────────
const _editCoasterImage = document.getElementById("edit-coaster-image");
if (_editCoasterImage) {
  _editCoasterImage.addEventListener("change", function () {
    const file = this.files[0];
    const preview = document.getElementById("edit-coaster-preview");
    if (!file) return;
    const url = URL.createObjectURL(file);
    const isVideo = file.type.startsWith("video/");
    preview.innerHTML = isVideo
      ? `<video src="${url}" style="width:100%;height:100%;object-fit:cover;" autoplay muted loop playsinline></video>`
      : `<img src="${url}" style="width:100%;height:100%;object-fit:cover;">`;
  });
}

// ── Helpers modal editar ─────────────────────────────────────────────
function showEditModalError(msg) {
  const container = document.getElementById("edit-coaster-messages");
  const error = document.getElementById("edit-coaster-error");
  const success = document.getElementById("edit-coaster-success");
  container.classList.remove("d-none");
  error.classList.remove("d-none");
  success.classList.add("d-none");
  error.querySelector("span").textContent = msg;
}

function showEditModalSuccess(msg) {
  const container = document.getElementById("edit-coaster-messages");
  const success = document.getElementById("edit-coaster-success");
  const error = document.getElementById("edit-coaster-error");
  container.classList.remove("d-none");
  success.classList.remove("d-none");
  error.classList.add("d-none");
  success.querySelector("span").textContent = msg;
}

function markEditError(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.closest(".input-group")?.classList.add("field-error");
  el.classList.add("field-error");
}

function clearEditErrors() {
  document.getElementById("edit-coaster-messages")?.classList.add("d-none");
  document.getElementById("edit-coaster-error")?.classList.add("d-none");
  document.getElementById("edit-coaster-success")?.classList.add("d-none");
  document
    .querySelectorAll("#modal-edit-coaster .field-error")
    .forEach((el) => {
      el.classList.remove("field-error");
    });
}

// ── Confirmar editar coaster ─────────────────────────────────────────
const _confirmEditCoaster = document.getElementById("confirm-edit-coaster");
if (_confirmEditCoaster) {
  _confirmEditCoaster.addEventListener("click", async function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML =
      'Actualizando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

    try {
      clearEditErrors();

      const id = document.getElementById("edit-coaster-id").value;
      const name = document.getElementById("edit-coaster-name").value.trim();
      const manufacturer = document
        .getElementById("edit-coaster-manufacturer")
        .value.trim();
      const model = document.getElementById("edit-coaster-model").value.trim();
      const park = document.getElementById("edit-coaster-park").value.trim();
      const parkId = document.getElementById("edit-coaster-park-id").value;
      const country = document
        .getElementById("edit-coaster-country")
        .value.trim();
      const status = document
        .getElementById("edit-coaster-status")
        .value.trim();
      const image = document.getElementById("edit-coaster-image").files[0];

      const unknownYear = document.getElementById("edit-unknown-year").checked;
      const unknownHeight = document.getElementById(
        "edit-unknown-height",
      ).checked;
      const unknownSpeed =
        document.getElementById("edit-unknown-speed").checked;
      const unknownLength = document.getElementById(
        "edit-unknown-length",
      ).checked;
      const unknownInversions = document.getElementById(
        "edit-unknown-inversions",
      ).checked;

      const year = unknownYear
        ? ""
        : document.getElementById("edit-coaster-year").value.trim();
      let height = unknownHeight
        ? ""
        : document.getElementById("edit-coaster-height").value.trim();
      let speed = unknownSpeed
        ? ""
        : document.getElementById("edit-coaster-speed").value.trim();
      let length = unknownLength
        ? ""
        : document.getElementById("edit-coaster-length").value.trim();
      let inversions = unknownInversions
        ? ""
        : document.getElementById("edit-coaster-inversions").value.trim();

      if (!unknownHeight && height === "") height = "0";
      if (!unknownSpeed && speed === "") speed = "0";
      if (!unknownLength && length === "") length = "0";
      if (!unknownInversions && inversions === "") inversions = "0";

      // Validaciones
      if (!name) {
        showEditModalError("El nombre de la coaster es obligatorio.");
        markEditError("edit-coaster-name");
        return;
      }

      if (!manufacturer) {
        showEditModalError(
          "Por favor selecciona un fabricante de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
        );
        markEditError("edit-coaster-manufacturer");
        return;
      }

      if (!model) {
        showEditModalError(
          "Por favor selecciona un modelo de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
        );
        markEditError("edit-coaster-model");
        return;
      }

      if (!park || !parkId) {
        showEditModalError(
          "Por favor selecciona un parque de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
        );
        markEditError("edit-coaster-park");
        return;
      }

      if (!country) {
        showEditModalError(
          "Por favor selecciona un país de la lista. (Si no lo conoces, busca la opción 'Desconocido' en la lista)",
        );
        markEditError("edit-coaster-country");
        return;
      }

      if (!status) {
        showEditModalError("Por favor selecciona un estado.");
        markEditError("edit-coaster-status");
        return;
      }

      if (!unknownYear) {
        const currentYear = new Date().getFullYear();
        if (!year || isNaN(year) || year.trim() === "") {
          showEditModalError(
            "Por favor introduce el año de apertura. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-year");
          return;
        }
        if (year > currentYear + 10 || year < 1800) {
          showEditModalError(
            "Por favor introduce un año válido. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-year");
          return;
        }
      }

      if (!unknownHeight) {
        if (!height || height.trim() === "") {
          showEditModalError(
            "Por favor introduce la altura. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-height");
          return;
        }
        if (isNaN(height)) {
          showEditModalError(
            "Por favor introduce una altura válida. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-height");
          return;
        }
        if (height > 400 || height < 0) {
          showEditModalError(
            "Por favor introduce una altura válida. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-height");
          return;
        }
      }

      if (!unknownSpeed) {
        if (!speed || speed.trim() === "") {
          showEditModalError(
            "Por favor introduce la velocidad. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-speed");
          return;
        }
        if (isNaN(speed)) {
          showEditModalError(
            "Por favor introduce una velocidad válida. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-speed");
          return;
        }
        if (speed > 400 || speed < 0) {
          showEditModalError(
            "Por favor introduce una velocidad válida. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-speed");
          return;
        }
      }

      if (!unknownLength) {
        if (!length || length.trim() === "") {
          showEditModalError(
            "Por favor introduce la longitud. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-length");
          return;
        }
        if (isNaN(length)) {
          showEditModalError(
            "Por favor introduce una longitud válida. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-length");
          return;
        }
        if (length > 20000 || length < 0) {
          showEditModalError(
            "Por favor introduce una longitud válida. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-length");
          return;
        }
      }

      if (!unknownInversions) {
        if (!inversions || inversions.trim() === "") {
          showEditModalError(
            "Por favor introduce el número de inversiones. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-inversions");
          return;
        }
        if (isNaN(inversions)) {
          showEditModalError(
            "Por favor introduce un número de inversiones válido. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-inversions");
          return;
        }
        if (inversions > 40 || inversions < 0) {
          showEditModalError(
            "Por favor introduce un número de inversiones válido. (Si no lo conoces, marca la opción 'Desconocido')",
          );
          markEditError("edit-coaster-inversions");
          return;
        }
      }

      const formData = new FormData();
      formData.append("id", id);
      formData.append("name", name);
      formData.append("manufacturer", manufacturer);
      formData.append("model", model);
      formData.append("parkId", parkId);
      formData.append("country", country);
      formData.append("year", unknownYear ? "" : year);
      formData.append("height", unknownHeight ? "" : height);
      formData.append("speed", unknownSpeed ? "" : speed);
      formData.append("length", unknownLength ? "" : length);
      formData.append("inversions", unknownInversions ? "" : inversions);
      formData.append("status", status);

      if (image) {
        const uploadForm = new FormData();
        const cleanName = image.name.replace(/[^a-zA-Z0-9.-]/g, "_");
        uploadForm.append("file", image, cleanName);
        uploadForm.append("bucket", "coasters");
        uploadForm.append("path", "admin_uploads");

        const uploadRes = await fetch(`${BASE_URL}/api/php/upload.php`, {
          method: "POST",
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          body: uploadForm,
        });
        const uploadData = await uploadRes.json();
        if (uploadData.success) {
          formData.append("imagenUrl", uploadData.url);
        } else {
          formData.append("image", image);
        }
      }

      const response = await fetch(
        `${BASE_URL}/api/php/admin/admin_coasters.php?action=updateCoaster`,
        {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: formData,
        },
      );

      let data = {};
      const contentType = response.headers.get("content-type") || "";
      if (contentType.includes("application/json")) {
        data = await response.json();
      } else {
        const text = await response.text();
        console.warn("Respuesta no-JSON del servidor:", text);
      }

      if (!response.ok) {
        showEditModalError(
          "Error al actualizar coaster: " +
            (data.error || data.message || "HTTP " + response.status),
        );
        return;
      }

      if (data.success) {
        showEditModalSuccess("Coaster actualizada correctamente.");
        if (typeof window.loadAdminCoasters === "function")
          window.loadAdminCoasters(1);
        setTimeout(() => {
          bootstrap.Modal.getInstance(
            document.getElementById("modal-edit-coaster"),
          )?.hide();
        }, 2000);
      } else {
        showEditModalError(
          "Error al actualizar coaster: " +
            (data.message || data.error || "Error desconocido"),
        );
      }
    } catch (error) {
      console.error("Error al editar coaster:", error);
      showEditModalError("Error inesperado al actualizar la coaster.");
    } finally {
      btn.disabled = false;
      btn.innerHTML =
        '<i class="fa-solid fa-arrows-rotate me-2"></i>Actualizar Atracción';
    }
  });
}

/****************************************
        NOTICIAS (NEWS)
  ****************************************/

// Función global para cargar noticias
window.loadAdminNews = async function (page) {
  const $list = $("#admin-news-list");
  const $count = $("#admin-news-count");
  if (!$list.length) return;

  page = page || 1;
  const search = $("#admin-news-search").val() || "";
  const tag = $("#filter-news-tag").val() || "";
  const featured = $("#filter-news-featured").is(":checked");

  $list.html(
    '<div class="list-group-item text-center text-muted py-4">' +
      '<div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando noticias...</div>',
  );

  try {
    const params = new URLSearchParams({
      action: "filterNews",
      page: page,
      search: search.trim(),
      tag: tag,
      featured: featured,
    });

    const res = await fetch(
      `${BASE_URL}/api/php/admin/admin_news.php?${params}`,
      {
        headers: {
          "X-CSRF-Token":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") ?? "",
        },
        credentials: "include",
      },
    );
    const data = await res.json();

    if (data.success) {
      const total = data.total || 0;
      $count.text("Mostrando " + total + " noticia" + (total !== 1 ? "s" : ""));
      renderNewsRows(data.news);
      renderNewsPagination(total, page);
    } else {
      $list.html(
        '<div class="list-group-item text-center text-danger py-4">' +
          (data.error || "Error al cargar") +
          "</div>",
      );
    }
  } catch (err) {
    console.error("Error cargando noticias:", err);
    $list.html(
      '<div class="list-group-item text-center text-danger py-4">Error de conexión con la API</div>',
    );
  }
};

function renderNewsRows(news) {
  const $list = $("#admin-news-list");
  $list.empty();
  if (!news || news.length === 0) {
    $list.html(
      '<div class="list-group-item text-center text-muted py-4">No se han encontrado noticias que coincidan.</div>',
    );
    return;
  }
  news.forEach(function (n) {
    const featuredBadge = n.is_featured
      ? '<span class="badge bg-warning text-dark ms-2">Destacada</span>'
      : "";
    $list.append(
      '<div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">' +
        '<div class="flex-grow-1">' +
        '<h6 class="mb-0 fw-bold text-success">' +
        n.title +
        " " +
        featuredBadge +
        "</h6>" +
        '<small class="text-muted">' +
        (n.tag || "Sin categoría") +
        " &bull; " +
        n.created_at +
        "</small>" +
        "</div>" +
        '<div class="d-flex gap-2 ms-3 flex-shrink-0">' +
        '<button class="btn btn-sm btn-outline-primary rounded-0 btn-edit-news" ' +
        'data-id="' +
        n.id +
        '" ' +
        'data-title="' +
        n.title.replace(/"/g, "&quot;") +
        '" ' +
        'data-tag="' +
        (n.tag || "").replace(/"/g, "&quot;") +
        '" ' +
        'data-link="' +
        (n.external_link || "").replace(/"/g, "&quot;") +
        '" ' +
        'data-image="' +
        (n.image_url || "").replace(/"/g, "&quot;") +
        '" ' +
        'data-featured="' +
        n.is_featured +
        '" ' +
        'data-description="' +
        n.description.replace(/"/g, "&quot;") +
        '">' +
        '<i class="fa-solid fa-pen"></i> Editar</button>' +
        '<button class="btn btn-sm btn-outline-danger rounded-0 btn-delete-news" ' +
        'data-id="' +
        n.id +
        '" data-title="' +
        n.title.replace(/"/g, "&quot;") +
        '">' +
        '<i class="fa-solid fa-trash"></i></button>' +
        "</div>" +
        "</div>",
    );
  });
}

function renderNewsPagination(total, page) {
  const $pagination = $("#admin-news-pagination");
  const ITEMS_PAGE = 15;
  $pagination.empty();
  const totalPages = Math.ceil(total / ITEMS_PAGE);
  if (totalPages <= 1) return;

  const nav = $("<nav></nav>");
  const ul = $('<ul class="pagination pagination-sm mb-0"></ul>');
  for (let i = 1; i <= totalPages; i++) {
    ul.append(
      '<li class="page-item ' +
        (i === page ? "active" : "") +
        '"><button class="page-link rounded-0" data-page="' +
        i +
        '">' +
        i +
        "</button></li>",
    );
  }
  nav.append(ul);
  $pagination.append(nav);
}

// --- Inicialización y Eventos News ---
if ($("#admin-news-list").length) {
  console.log("Admin News JS Loaded");
  window.loadAdminNews(1);

  // Búsqueda y Filtros
  $(document).on("click", "#btn-news-filtrar", function () {
    window.loadAdminNews(1);
  });
  $(document).on("click", "#btn-news-borrar", function () {
    $("#admin-news-search").val("");
    $("#filter-news-tag").val("");
    $("#filter-news-featured").prop("checked", false);
    window.loadAdminNews(1);
  });

  // Paginación
  $(document).on("click", "#admin-news-pagination button", function () {
    window.loadAdminNews(parseInt($(this).data("page")));
  });

  // Abrir Modal Añadir
  $(document).on("click", "#btn-add-news", function () {
    clearNewsErrors();
    $("#modal-news-title-header").text("Añadir Noticia");
    $("#news-form-id").val("");
    $("#news-form-title").val("");
    $("#news-form-tag").val("");
    $("#news-form-link").val("");
    $("#news-form-desc").val("");
    $("#news-form-image").val("");
    $("#news-form-file").val(""); // Limpiar archivo
    $("#news-form-image-preview").addClass("d-none");
    $("#news-form-featured").prop("checked", false);
    const modalEl = document.getElementById("modal-news-form");
    if (modalEl) {
      const m = bootstrap.Modal.getOrCreateInstance(modalEl);
      m.show();
    }
  });

  // Abrir Modal Editar
  $(document).on("click", ".btn-edit-news", function () {
    clearNewsErrors();
    const b = $(this);
    $("#modal-news-title-header").text("Editar Noticia");
    $("#news-form-id").val(b.data("id"));
    $("#news-form-title").val(b.data("title"));
    $("#news-form-tag").val(b.data("tag"));
    $("#news-form-link").val(b.data("link"));
    $("#news-form-desc").val(b.data("description"));
    $("#news-form-image").val(b.data("image"));
    $("#news-form-file").val(""); // Limpiar archivo previo

    const currentImg = b.data("image");
    if (currentImg) {
      $("#news-image-path-text").text(currentImg);
      $("#news-form-image-preview").removeClass("d-none");
    } else {
      $("#news-form-image-preview").addClass("d-none");
    }

    $("#news-form-featured").prop("checked", b.data("featured") == 1);
    const modalEl = document.getElementById("modal-news-form");
    if (modalEl) {
      const m = bootstrap.Modal.getOrCreateInstance(modalEl);
      m.show();
    }
  });

  // Guardar / Actualizar (Usando FormData para archivos)
  $(document).on("click", "#btn-save-news", async function () {
    const btn = $(this);
    clearNewsErrors();
    const id = $("#news-form-id").val();
    const action = id ? "updateNews" : "addNews";

    const title = $("#news-form-title").val()?.trim() || "";
    const tag = $("#news-form-tag").val()?.trim() || "";
    const external_link = $("#news-form-link").val()?.trim() || "";
    const description = $("#news-form-desc").val()?.trim() || "";
    const image_url = $("#news-form-image").val()?.trim() || "";

    if (!title || !description) {
      showNewsModalError("Título y descripción son obligatorios");
      if (!title) markNewsError("news-form-title");
      if (!description) markNewsError("news-form-desc");
      return;
    }

    const formData = new FormData();
    formData.append("id", id);
    formData.append("title", title);
    formData.append("tag", tag);
    formData.append("external_link", external_link);
    formData.append("description", description);
    formData.append("image_url", image_url);
    formData.append("is_featured", $("#news-form-featured").is(":checked"));

    const fileInput = document.getElementById("news-form-file");
    if (fileInput && fileInput.files.length > 0) {
      formData.append("image", fileInput.files[0]);
    }

    btn.prop("disabled", true).text("Guardando...");

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/admin/admin_news.php?action=${action}`,
        {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: formData,
        },
      );
      const data = await res.json();
      if (data.success) {
        const modalEl = document.getElementById("modal-news-form");
        const m = bootstrap.Modal.getInstance(modalEl);
        if (m) m.hide();
        window.loadAdminNews(1);
      } else {
        showNewsModalError(data.error || "Error al guardar noticia");
      }
    } catch (err) {
      console.error("Error saving news:", err);
      showNewsModalError("Error de conexión al servidor");
    } finally {
      btn.prop("disabled", false).text("Guardar noticia");
    }
  });

  // Modal Eliminar
  $(document).on("click", ".btn-delete-news", function () {
    const id = $(this).data("id");
    const title = $(this).data("title");
    $("#delete-news-title").text(title);
    $("#confirm-delete-news").attr("data-id", id);
    const modalEl = document.getElementById("modal-delete-news");
    if (modalEl) {
      const m = bootstrap.Modal.getOrCreateInstance(modalEl);
      m.show();
    }
  });

  $(document).on("click", "#confirm-delete-news", async function () {
    const btn = $(this);
    const id = btn.attr("data-id");
    try {
      const res = await fetch(
        `${BASE_URL}/api/php/admin/admin_news.php?action=deleteNews&id=${id}`,
        {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
        },
      );
      const data = await res.json();
      if (data.success) {
        const modalEl = document.getElementById("modal-delete-news");
        const m = bootstrap.Modal.getInstance(modalEl);
        if (m) m.hide();
        window.loadAdminNews(1);
      }
    } catch (err) {
      console.error("Error deleting news:", err);
    }
  });

  function showNewsModalError(msg) {
    const container = document.getElementById("news-form-messages");
    const error = document.getElementById("news-form-error");
    if (!container || !error) return;
    container.classList.remove("d-none");
    error.classList.remove("d-none");
    error.textContent = msg;
  }

  function markNewsError(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add("field-error");
  }

  function clearNewsErrors() {
    document.getElementById("news-form-messages")?.classList.add("d-none");
    document.getElementById("news-form-error")?.classList.add("d-none");
    document.querySelectorAll("#modal-news-form .field-error").forEach((el) => {
      el.classList.remove("field-error");
    });
  }

  /* ════════════════════════════════════════════════════════
     ADMIN PARKS
  ════════════════════════════════════════════════════════ */
  if (document.getElementById("admin-park-list")) {
    const parksApi = `${BASE_URL}/api/php/admin/admin_parks.php`;

    // ── Poblar selector de países ────────────────────────
    fetch(`${BASE_URL}/api/php/parks.php?action=country`)
      .then((r) => r.json())
      .then((data) => {
        const select = document.getElementById("filter-park-country");
        if (!select) return;
        const countries = data.data || data;
        if (Array.isArray(countries)) {
          countries.filter(Boolean).forEach((c) => {
            select.append(new Option(c, c));
          });
        }
      })
      .catch(() => {});

    // ── Carga principal de parques ───────────────────────
    let adminParkPage = 1;
    const PARKS_PER_PAGE = 15;

    window.loadAdminParks = async function (page) {
      adminParkPage = page || 1;
      const search = (
        document.getElementById("admin-park-search")?.value || ""
      ).trim();
      const country =
        document.getElementById("filter-park-country")?.value || "";
      const year = document.getElementById("filter-park-year")?.value || "";

      const $list = $("#admin-park-list");
      const $count = $("#admin-park-count");
      const $pag = $("#admin-park-pagination");

      if (!search && !country && !year) {
        $list.html(
          '<div class="list-group-item text-center text-muted py-5"><i class="fa-solid fa-hand-point-up fa-2x mb-2 d-block text-success"></i>Usa el buscador o activa un filtro para ver parques.</div>',
        );
        $count.text("");
        $pag.empty();
        return;
      }

      $list.html(
        '<div class="list-group-item text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-success me-2"></div>Cargando...</div>',
      );

      const params = new URLSearchParams({
        action: "list",
        page: adminParkPage,
      });
      if (search) params.set("q", search);
      if (country) params.set("country", country);
      if (year) params.set("year", year);

      try {
        const res = await fetch(`${BASE_URL}/api/php/parks.php?${params}`, {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          credentials: "include",
        });
        const data = await res.json();
        const parks = data.data || [];
        const total = data.total || parks.length;

        $count.text(`Mostrando ${total} parque${total !== 1 ? "s" : ""}`);

        if (!parks.length) {
          $list.html(
            '<div class="list-group-item text-center text-muted py-4">No se encontraron parques.</div>',
          );
          $pag.empty();
          return;
        }

        $list.empty();
        parks.forEach((p) => {
          const img = p.imagen_url
            ? p.imagen_url.startsWith("/")
              ? BASE_URL + p.imagen_url
              : p.imagen_url
            : "https://placehold.co/80x60/0d1117/444?text=Parque";
          const esc = (s) =>
            (s || "").replace(/&/g, "&amp;").replace(/"/g, "&quot;");
          const priceBadge = p.precio_entrada
            ? '<span class="badge bg-success ms-2" style="font-size:.7rem;">' +
              parseFloat(p.precio_entrada).toFixed(2) +
              "\u20AC</span>"
            : "";
          const html =
            '<div class="list-group-item list-group-item-action d-flex align-items-center p-3 gap-2 gap-sm-3">' +
            '<img src="' +
            img +
            '" onerror="this.src=\'https://placehold.co/80x60/0d1117/444?text=P\'" style="width:60px;height:45px;object-fit:cover;flex-shrink:0;" class="rounded-0 d-none d-sm-block">' +
            '<div class="flex-grow-1 min-w-0">' +
            '<h6 class="mb-0 fw-bold text-success text-truncate" style="font-size: .95rem;">' +
            esc(p.park_name) +
            "</h6>" +
            '<small class="text-muted text-truncate d-block" style="font-size: .75rem;">' +
            esc(p.park_country || "\u2014") +
            " &bull; " +
            (p.opening_year || "\u2014") +
            "</small>" +
            "</div>" +
            '<div class="d-flex gap-1 gap-sm-2 flex-shrink-0">' +
            '<button class="btn btn-sm btn-outline-primary rounded-0 px-2 px-sm-3 btn-edit-park" data-id="' +
            p.id +
            '" data-name="' +
            esc(p.park_name) +
            '" data-country="' +
            esc(p.park_country) +
            '" data-location="' +
            esc(p.park_location) +
            '" data-year="' +
            (p.opening_year || "") +
            '" data-website="' +
            esc(p.website) +
            '" data-price="' +
            (p.precio_entrada || "") +
            '" data-img="' +
            (p.imagen_url || "") +
            '"><i class="fa-solid fa-pen"></i> <span class="d-none d-md-inline">Editar</span></button>' +
            '<button class="btn btn-sm btn-outline-danger rounded-0 px-2 px-sm-3 btn-delete-park" data-id="' +
            p.id +
            '" data-name="' +
            esc(p.park_name) +
            '"><i class="fa-solid fa-trash"></i> <span class="d-none d-md-inline">Eliminar</span></button>' +
            "</div></div>";
          $list.append(html);
        });

        renderParkPagination(total, adminParkPage);
      } catch (err) {
        console.error("Error cargando parques:", err);
        $list.html(
          '<div class="list-group-item text-center text-danger py-4">Error de conexión</div>',
        );
      }
    };

    function renderParkPagination(total, page) {
      const $pag = $("#admin-park-pagination");
      $pag.empty();
      const totalPages = Math.ceil(total / PARKS_PER_PAGE);
      if (totalPages <= 1) return;
      const nav = $("<nav></nav>");
      const ul = $('<ul class="pagination pagination-sm mb-0"></ul>');
      ul.append(
        `<li class="page-item ${page === 1 ? "disabled" : ""}"><button class="page-link rounded-0" data-page="${page - 1}">&#8249;</button></li>`,
      );
      let start = Math.max(1, page - 2),
        end = Math.min(totalPages, start + 4);
      start = Math.max(1, end - 4);
      for (let i = start; i <= end; i++) {
        ul.append(
          `<li class="page-item ${i === page ? "active" : ""}"><button class="page-link rounded-0" data-page="${i}">${i}</button></li>`,
        );
      }
      ul.append(
        `<li class="page-item ${page === totalPages ? "disabled" : ""}"><button class="page-link rounded-0" data-page="${page + 1}">&#8250;</button></li>`,
      );
      nav.append(ul);
      $pag.append(nav);
      $pag.find("button[data-page]").on("click", function () {
        window.loadAdminParks(parseInt($(this).data("page")));
        window.scrollTo({
          top: $("#admin-park-list").offset().top - 80,
          behavior: "smooth",
        });
      });
    }

    // ── Buscador ─────────────────────────────────────────
    let parkSearchDebounce = null;
    $("#admin-park-search").on("input", function () {
      const $icon = $("#admin-park-search-icon");
      if ($(this).val().length > 0) {
        $icon
          .removeClass("fa-magnifying-glass text-muted")
          .addClass("fa-xmark text-danger")
          .css("cursor", "pointer");
      } else {
        $icon
          .removeClass("fa-xmark text-danger")
          .addClass("fa-magnifying-glass text-muted")
          .css("cursor", "default");
      }
      clearTimeout(parkSearchDebounce);
      parkSearchDebounce = setTimeout(() => window.loadAdminParks(1), 400);
    });
    $("#admin-park-search-icon").on("click", function () {
      if ($("#admin-park-search").val().length > 0) {
        $("#admin-park-search").val("");
        $(this)
          .removeClass("fa-xmark text-danger")
          .addClass("fa-magnifying-glass text-muted")
          .css("cursor", "default");
        window.loadAdminParks(1);
      }
    });

    // ── Filtros ──────────────────────────────────────────
    $("#btn-park-filtrar").on("click", () => window.loadAdminParks(1));
    $("#btn-park-borrar").on("click", function () {
      $("#filter-park-country").val("");
      $("#filter-park-year").val("");
      $("#admin-park-search").val("");
      window.loadAdminParks(1);
    });

    // ── Añadir parque ────────────────────────────────────
    $("#btn-add-park").on("click", function (e) {
      e.preventDefault();
      document.getElementById("add-park-name").value = "";
      document.getElementById("add-park-country").value = "";
      document.getElementById("add-park-location").value = "";
      document.getElementById("add-park-year").value = "";
      document.getElementById("add-park-website").value = "";
      document.getElementById("add-park-price").value = "";
      document
        .getElementById("add-park-preview-container")
        .classList.add("d-none");
      document.getElementById("add-park-dropzone-text").textContent =
        "Subir imagen";
      document.getElementById("add-park-messages").classList.add("d-none");
      document.getElementById("add-park-error").classList.add("d-none");
      document.getElementById("add-park-success").classList.add("d-none");
      loadUnassignedCoasters();
      new bootstrap.Modal(document.getElementById("modal-add-park")).show();
    });

    async function loadUnassignedCoasters(
      containerId = "add-park-coasters-list",
      searchId = "add-park-coasters-search",
      idsId = "add-park-coasters-ids",
      parkId = null,
    ) {
      const container = document.getElementById(containerId);
      const searchInput = document.getElementById(searchId);
      const idsInput = document.getElementById(idsId);
      if (!container) return;

      container.innerHTML =
        '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-success"></div> Cargando coasters...</div>';

      try {
        let url = `${BASE_URL}/api/php/coasters.php?action=apply_filters&park_id=null&limit=500`;
        if (parkId)
          url = `${BASE_URL}/api/php/coasters.php?action=apply_filters&park_id=${parkId}&limit=500`;

        const res = await fetch(url);
        const data = await res.json();
        const coasters = data.coasters || [];

        if (!coasters.length) {
          container.innerHTML =
            '<div class="text-muted py-2 text-center small">No hay coasters disponibles</div>';
          return;
        }

        let selectedIds = new Set(
          (idsInput.value || "").split(",").filter(Boolean),
        );

        function renderCoasters(list) {
          container.innerHTML = list
            .map(
              (c) => `
            <label class="d-flex align-items-center gap-2 py-1 px-2 rounded-0" style="cursor:pointer;font-size:.85rem;">
              <input type="checkbox" class="form-check-input coaster-assign-cb" value="${c.id}"
                     ${selectedIds.has(c.id.toString()) ? "checked" : ""} style="cursor:pointer;">
              <span class="text-white">${c.coaster_name}</span>
              <span class="text-muted ms-auto" style="font-size:.75rem;">${c.manufacter || ""}</span>
            </label>`,
            )
            .join("");

          container.querySelectorAll(".coaster-assign-cb").forEach((cb) => {
            cb.addEventListener("change", function () {
              if (this.checked) selectedIds.add(this.value);
              else selectedIds.delete(this.value);
              idsInput.value = Array.from(selectedIds).join(",");
              const badge = document.getElementById(
                containerId === "add-park-coasters-list"
                  ? "park-coasters-badge"
                  : "edit-park-coasters-badge",
              );
              if (badge)
                badge.textContent = `${selectedIds.size} seleccionadas`;
            });
          });
        }

        renderCoasters(coasters);

        if (searchInput) {
          searchInput.addEventListener("input", function () {
            const q = this.value.toLowerCase();
            renderCoasters(
              coasters.filter(
                (c) =>
                  c.coaster_name.toLowerCase().includes(q) ||
                  (c.manufacter || "").toLowerCase().includes(q),
              ),
            );
          });
        }
      } catch (e) {
        container.innerHTML =
          '<div class="text-danger small py-2">Error cargando coasters</div>';
      }
    }

    // Previsualización imagen añadir
    document
      .getElementById("add-park-image")
      ?.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
          document.getElementById("add-park-preview").src = e.target.result;
          document
            .getElementById("add-park-preview-container")
            .classList.remove("d-none");
          document.getElementById("add-park-dropzone-text").textContent =
            file.name;
        };
        reader.readAsDataURL(file);
      });

    // Guardar parque nuevo
    $("#confirm-add-park").on("click", async function () {
      const btn = $(this);
      const name = document.getElementById("add-park-name").value.trim();
      const country = document.getElementById("add-park-country").value.trim();
      const location = document
        .getElementById("add-park-location")
        .value.trim();
      const year = document.getElementById("add-park-year").value.trim();
      const website = document.getElementById("add-park-website").value.trim();
      const price = document.getElementById("add-park-price").value.trim();
      const coasterIds = document.getElementById("add-park-coasters-ids").value;
      const imageFile = document.getElementById("add-park-image").files[0];

      if (!name || !country || !location) {
        document.getElementById("add-park-messages").classList.remove("d-none");
        document.getElementById("add-park-error").classList.remove("d-none");
        document
          .getElementById("add-park-error")
          .querySelector("span").textContent =
          "Nombre, país y localización son obligatorios.";
        return;
      }

      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...');

      const fd = new FormData();
      fd.append("action", "addPark");
      fd.append("name", name);
      fd.append("country", country);
      fd.append("location", location);
      if (year) fd.append("year", year);
      if (website) fd.append("website", website);
      if (price) fd.append("precio_entrada", price);
      if (coasterIds) fd.append("coasterIds", coasterIds);
      if (imageFile) fd.append("image", imageFile);

      try {
        const res = await fetch(`${parksApi}`, {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: fd,
          credentials: "include",
        });
        const data = await res.json();
        document.getElementById("add-park-messages").classList.remove("d-none");
        if (data.success) {
          document
            .getElementById("add-park-success")
            .classList.remove("d-none");
          document
            .getElementById("add-park-success")
            .querySelector("span").textContent =
            "Parque añadido correctamente.";
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("modal-add-park"),
            ).hide();
            window.loadAdminParks(1);
          }, 1200);
        } else {
          document.getElementById("add-park-error").classList.remove("d-none");
          document
            .getElementById("add-park-error")
            .querySelector("span").textContent =
            data.error || "Error al añadir el parque.";
        }
      } catch (err) {
        document.getElementById("add-park-error").classList.remove("d-none");
        document
          .getElementById("add-park-error")
          .querySelector("span").textContent = "Error de conexión.";
      }
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-plus me-2"></i>Añadir parque');
    });

    // Autocomplete países (add y edit)
    ["add-park-country", "edit-park-country"].forEach((inputId) => {
      const dropdownId =
        inputId === "add-park-country"
          ? "ac-dropdown-park-country"
          : "ac-dropdown-edit-park-country";
      if (!document.getElementById(inputId)) return;
      initAutocomplete({
        inputId,
        dropdownId,
        fetchItems: async (q) => {
          const res = await fetch(
            `${BASE_URL}/api/php/parks.php?action=country`,
          );
          const data = await res.json();
          const all = data.data || [];
          return all
            .filter(
              (c) => c && c.toLowerCase().includes((q || "").toLowerCase()),
            )
            .map((c) => ({ label: c, value: c }));
        },
        onSelect: () => {},
      });
    });

    // ── Editar parque ────────────────────────────────────
    $(document).on("click", ".btn-edit-park", function () {
      const btn = $(this);
      document.getElementById("edit-park-id").value = btn.data("id");
      document.getElementById("edit-park-name").value = btn.data("name");
      document.getElementById("edit-park-country").value = btn.data("country");
      document.getElementById("edit-park-location").value =
        btn.data("location");
      document.getElementById("edit-park-year").value = btn.data("year");
      document.getElementById("edit-park-website").value = btn.data("website");
      document.getElementById("edit-park-price").value = btn.data("price");
      document.getElementById("edit-park-messages").classList.add("d-none");

      const prevImg = btn.data("img");
      const prev = document.getElementById("edit-park-preview");
      if (prevImg && prev) {
        prev.src = prevImg.startsWith("/") ? BASE_URL + prevImg : prevImg;
        prev.style.display = "block";
      }

      loadUnassignedCoasters(
        "edit-park-coasters-list",
        "edit-park-coasters-search",
        "edit-park-coasters-ids",
        btn.data("id"),
      );
      new bootstrap.Modal(document.getElementById("modal-edit-park")).show();
    });

    document
      .getElementById("edit-park-image")
      ?.addEventListener("change", function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => {
          const prev = document.getElementById("edit-park-preview");
          prev.src = e.target.result;
          prev.style.display = "block";
          document.getElementById("edit-park-dropzone-text").textContent =
            file.name;
        };
        reader.readAsDataURL(file);
      });

    $("#confirm-edit-park").on("click", async function () {
      const btn = $(this);
      const id = document.getElementById("edit-park-id").value;
      const fd = new FormData();
      fd.append("action", "editPark");
      fd.append("id", id);
      fd.append("name", document.getElementById("edit-park-name").value.trim());
      fd.append(
        "country",
        document.getElementById("edit-park-country").value.trim(),
      );
      fd.append(
        "location",
        document.getElementById("edit-park-location").value.trim(),
      );
      fd.append("year", document.getElementById("edit-park-year").value.trim());
      fd.append(
        "website",
        document.getElementById("edit-park-website").value.trim(),
      );
      fd.append(
        "precio_entrada",
        document.getElementById("edit-park-price").value.trim(),
      );
      fd.append(
        "coasterIds",
        document.getElementById("edit-park-coasters-ids").value,
      );
      const imgFile = document.getElementById("edit-park-image").files[0];
      if (imgFile) fd.append("image", imgFile);

      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...');

      try {
        const res = await fetch(`${parksApi}`, {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: fd,
          credentials: "include",
        });
        const data = await res.json();
        document
          .getElementById("edit-park-messages")
          .classList.remove("d-none");
        if (data.success) {
          document
            .getElementById("edit-park-success")
            .classList.remove("d-none");
          document
            .getElementById("edit-park-success")
            .querySelector("span").textContent = "Cambios guardados.";
          setTimeout(() => {
            bootstrap.Modal.getInstance(
              document.getElementById("modal-edit-park"),
            ).hide();
            window.loadAdminParks(adminParkPage);
          }, 1000);
        } else {
          document.getElementById("edit-park-error").classList.remove("d-none");
          document
            .getElementById("edit-park-error")
            .querySelector("span").textContent =
            data.error || "Error al editar.";
        }
      } catch (err) {
        console.error(err);
      }
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios');
    });

    // ── Eliminar parque ──────────────────────────────────
    $(document).on("click", ".btn-delete-park", function () {
      const id = $(this).data("id"),
        name = $(this).data("name");
      $("#delete-park-name").text(name);
      $("#confirm-delete-park").attr("data-id", id);
      new bootstrap.Modal(document.getElementById("modal-delete-park")).show();
    });

    $("#confirm-delete-park").on("click", async function () {
      const btn = $(this);
      const id = btn.attr("data-id");
      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin"></i>');
      try {
        const fd = new FormData();
        fd.append("action", "deletePark");
        fd.append("id", id);
        const res = await fetch(`${parksApi}`, {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: fd,
          credentials: "include",
        });
        const data = await res.json();
        if (data.success) {
          bootstrap.Modal.getInstance(
            document.getElementById("modal-delete-park"),
          ).hide();
          window.loadAdminParks(adminParkPage);
        } else {
          alert(data.error || "Error al eliminar");
        }
      } catch (err) {
        alert("Error de conexión");
      }
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-trash me-1"></i>Eliminar');
    });

    // ── Duplicar parque ──────────────────────────────────
    $(document).on("click", ".btn-duplicate-park", function () {
      const id = $(this).data("id"),
        name = $(this).data("name");
      $("#duplicate-park-name").text(name);
      $("#confirm-duplicate-park").attr("data-id", id);
      new bootstrap.Modal(
        document.getElementById("modal-duplicate-park"),
      ).show();
    });

    $("#confirm-duplicate-park").on("click", async function () {
      const btn = $(this);
      const id = btn.attr("data-id");
      btn
        .prop("disabled", true)
        .html('<i class="fa-solid fa-spinner fa-spin"></i>');
      try {
        const fd = new FormData();
        fd.append("action", "duplicatePark");
        fd.append("id", id);
        const res = await fetch(`${parksApi}`, {
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          method: "POST",
          body: fd,
          credentials: "include",
        });
        const data = await res.json();
        if (data.success) {
          bootstrap.Modal.getInstance(
            document.getElementById("modal-duplicate-park"),
          ).hide();
          window.loadAdminParks(1);
        } else {
          alert(data.error || "Error al duplicar");
        }
      } catch (err) {
        alert("Error de conexión");
      }
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-copy me-1"></i>Duplicar');
    });

    // ── Restaurar estado si viene de btn-new-park ────────
    const pendingPark = sessionStorage.getItem("pendingParkFromCoaster");
    if (pendingPark) {
      sessionStorage.removeItem("pendingParkFromCoaster");
      $("#btn-add-park").trigger("click");
    }
  }
}
