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
          { credentials: "include" },
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
                    <p class="card-text text-muted mb-3 small"><i class="fa-solid fa-train-tram me-1"></i> Destino: <strong>${photo.coaster_name}</strong></p>
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
        { method: "POST", credentials: "include" },
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
        { method: "POST", credentials: "include" },
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
        { method: "POST", credentials: "include" },
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
      $list.append(`
        <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
          <div class="flex-grow-1">
            <h6 class="mb-0 fw-bold text-success">${c.coaster_name}</h6>
            <small class="text-muted">
              ${c.coaster_manufacter || "Desconocido"} &bull;
              ${c.park_name || "Desconocido"} &bull;
              ${c.park_country || "—"} &bull;
              ${c.opening_year || "—"} &bull;
              ${c.coaster_status || "—"}
            </small>
          </div>
          <div class="d-flex gap-2 ms-3 flex-shrink-0">
            <a href="#" class="btn btn-sm btn-outline-primary rounded-0" 
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
      let url;
      if (useSearch) {
        url =
          `${BASE_URL}/api/php/admin/admin_coasters.php?action=searchCoasters` +
          `&search=${encodeURIComponent(search)}&page=${page}`;
      } else {
        const params = new URLSearchParams({
          action: "filterCoasters",
          page,
        });
        if (filters.opened) params.set("opened", filters.opened);
        if (filters.manufacter) params.set("manufacter", filters.manufacter);
        if (filters.country) params.set("country", filters.country);
        if (filters.park) params.set("park", filters.park);
        if (filters.year) params.set("year", filters.year);
        if (parseInt(filters.height) > 0) params.set("height", filters.height);
        if (parseInt(filters.speed) > 0) params.set("speed", filters.speed);
        url = `${BASE_URL}/api/php/admin/admin_coasters.php?${params}`;
      }

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

    // Parques
    fetch(`${BASE_URL}/api/php/parks.php?action=list&limit=500&sort=name`)
      .then((r) => r.json())
      .then((data) => {
        if (data.success && data.data) {
          data.data.forEach((p) => {
            $("#filter-park").append(
              `<option value="${p.park_name}">${p.park_name}</option>`,
            );
          });
        }
      })
      .catch(() => {});

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
  }
  // ── Modal añadir coaster  ─────────────────────────────────────────
  const _btnAddCoaster = document.getElementById("btn-add-coaster");
  if (_btnAddCoaster) {
    _btnAddCoaster.addEventListener("click", function () {
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
        renderItems(list);
      }, 200);
    });

    input.addEventListener("focus", async () => {
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
      }
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
        const country = document
          .getElementById("add-coaster-country")
          .value.trim();
        const year = document.getElementById("add-coaster-year").value.trim();
        const height = document
          .getElementById("add-coaster-height")
          .value.trim();
        const speed = document.getElementById("add-coaster-speed").value.trim();
        const length = document
          .getElementById("add-coaster-length")
          .value.trim();
        const inversions = document
          .getElementById("add-coaster-inversions")
          .value.trim();
        const status = document
          .getElementById("add-coaster-status")
          .value.trim();
        const image = document.getElementById("add-coaster-image").files[0];

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
        formData.append("country", country);
        formData.append("year", year);
        formData.append("height", height);
        formData.append("speed", speed);
        formData.append("length", length);
        formData.append("inversions", inversions);
        formData.append("status", status);
        if (image) {
          formData.append("image", image);
        }

        const response = await fetch(
          `${BASE_URL}/api/php/admin/admin_coasters.php?action=addCoaster`,
          {
            method: "POST",
            body: formData,
          },
        );

        if (!response.ok) {
          throw new Error("Error al añadir coaster");
        }

        const data = await response.json();
        if (data.success) {
          showModalSuccess("Coaster añadida correctamente");
        } else {
          showModalError("Error al añadir coaster: " + data.message);
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
          button.getAttribute("data-manufacturer") ?? "",
        "edit-coaster-model": button.getAttribute("data-model") ?? "",
        "edit-coaster-park": button.getAttribute("data-park") ?? "",
        "edit-coaster-country": button.getAttribute("data-country") ?? "",
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
        preview.innerHTML = imageUrl
          ? `<img src="${imageUrl}" style="width:100%;height:100%;object-fit:cover;">`
          : "";
      }
    });
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
              <a href="#" class="btn btn-sm btn-outline-primary rounded-0" data-id="${p.id}">
                <i class="fa-solid fa-pen"></i> Editar
              </a>
              <button class="btn btn-sm btn-outline-danger rounded-0 btn-delete-park"
                data-id="${p.id}" data-name="${p.park_name}">
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

        const res = await fetch(url, { credentials: "include" });
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
        const res = await fetch(url, { credentials: "include" });
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
          const coasterIds = coasterIdsRaw
            ? coasterIdsRaw.split(",").map(Number).filter(Boolean)
            : [];

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

          const res = await fetch(
            `${BASE_URL}/api/php/admin/admin_parks.php?action=addPark`,
            {
              method: "POST",
              credentials: "include",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                name,
                country,
                location,
                year: yearUnknown ? "" : year,
                coasterIds,
              }),
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
            headers: { "Content-Type": "application/json" },
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

      const year = document.getElementById("edit-coaster-year").value.trim();
      const height = document
        .getElementById("edit-coaster-height")
        .value.trim();
      const speed = document.getElementById("edit-coaster-speed").value.trim();
      const length = document
        .getElementById("edit-coaster-length")
        .value.trim();
      const inversions = document
        .getElementById("edit-coaster-inversions")
        .value.trim();

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
      if (image) formData.append("image", image);

      const response = await fetch(
        `${BASE_URL}/api/php/admin/admin_coasters.php?action=updateCoaster`,
        { method: "POST", body: formData },
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
