/**
 * web/js/users/edit_top.js
 * RollerCoasterWorld - Gestor integral para la edición a pantalla completa de tops personales.
 */

class TopEditor {
  constructor() {
    this.baseUrl = window.BASE_URL || "";
    this.activeTab = "coasters"; // 'coasters' | 'parks'
    this.topsData = {
      coasters: [],
      parks: [],
    };
    this.hasUnsavedChanges = false;
    this.sortable = null;
    this.searchDebounceTimer = null;
    this.fallbackImage =
      "https://www.themeparkexperts.com/wp-content/uploads/2019/07/themepark_cedarpoint_montanarusa.jpg";
    this.lastSearchQuery = {
      coasters:
        (typeof sessionStorage !== "undefined" &&
          sessionStorage.getItem("rcw_last_search_coasters")) ||
        "",
      parks:
        (typeof sessionStorage !== "undefined" &&
          sessionStorage.getItem("rcw_last_search_parks")) ||
        "",
    };
  }

  init() {
    this.bindGlobalEvents();
    this.setupTabs();
    this.setupAutocomplete();
    this.setupInListFilter();
    this.updateRestoreButtonState();
    this.loadData();
  }

  getLastSearch() {
    return (
      this.lastSearchQuery[this.activeTab] ||
      this.lastSearchQuery[this.activeTab === "coasters" ? "parks" : "coasters"] ||
      ""
    );
  }

  setLastSearch(query) {
    if (!query || typeof query !== "string") return;
    const trimmed = query.trim();
    if (!trimmed) return;

    this.lastSearchQuery[this.activeTab] = trimmed;
    try {
      if (typeof sessionStorage !== "undefined") {
        sessionStorage.setItem(`rcw_last_search_${this.activeTab}`, trimmed);
      }
    } catch (e) {}

    this.updateRestoreButtonState();
  }

  updateRestoreButtonState() {
    const lastQuery = this.getLastSearch();
    const $btn = $("#btn-restore-search");
    if (!$btn.length) return;

    if (lastQuery) {
      $btn
        .removeClass("disabled text-muted opacity-25")
        .addClass("text-white-50")
        .css({
          cursor: "pointer",
          pointerEvents: "auto",
        })
        .attr("title", `Reescribir última búsqueda: "${lastQuery}"`);
    } else {
      $btn
        .addClass("disabled text-muted opacity-25")
        .removeClass("text-white-50")
        .css({
          cursor: "not-allowed",
          pointerEvents: "none",
        })
        .attr("title", "No hay búsquedas anteriores para restaurar");
    }
  }

  restoreLastSearch() {
    const lastQuery = this.getLastSearch();
    if (!lastQuery) return;

    const $input = $("#search-add-input");
    const $btnIcon = $("#restore-search-icon");

    // Animación visual de rotación en el icono de refresco
    $btnIcon.addClass("fa-spin");
    setTimeout(() => {
      $btnIcon.removeClass("fa-spin");
    }, 450);

    // Escribir el texto y disparar eventos
    $input.val(lastQuery).trigger("input").focus();

    // Búsqueda inmediata para no esperar el debounce
    if (lastQuery.trim().length >= 2) {
      clearTimeout(this.searchDebounceTimer);
      this.performSearch(lastQuery.trim());
    }
  }

  bindGlobalEvents() {
    const self = this;

    // Advertencia al intentar cerrar o recargar pestaña con cambios sin guardar
    window.addEventListener("beforeunload", (e) => {
      if (self.hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = "Tienes cambios sin guardar en tu ranking.";
      }
    });

    // Interceptar botón "Volver" con modal si hay cambios sin guardar
    $("#btn-back-to-tops").on("click", function (e) {
      if (self.hasUnsavedChanges) {
        e.preventDefault();
        const targetUrl = $(this).attr("href");
        self.showConfirm({
          title: "Cambios sin guardar",
          message:
            "Tienes cambios pendientes en tu ranking. Si sales ahora, <strong>perderás las modificaciones no guardadas</strong>.",
          subtext: "Guarda antes de salir si quieres conservar tu nuevo orden.",
          icon: "fa-triangle-exclamation text-warning",
          confirmText: "Salir sin guardar",
          confirmClass: "btn-outline-danger",
          onConfirm: () => {
            self.hasUnsavedChanges = false;
            window.location.href = targetUrl;
          },
        });
      }
    });

    // Guardar cambios
    $("#btn-save-top").on("click", () => self.saveChanges());

    // Invertir orden
    $("#btn-reverse-order").on("click", () => self.reverseCurrentOrder());
  }

  setUnsaved(hasChanges) {
    this.hasUnsavedChanges = hasChanges;
    if (hasChanges) {
      $("#unsaved-indicator").removeClass("d-none").addClass("d-inline-flex");
    } else {
      $("#unsaved-indicator").addClass("d-none").removeClass("d-inline-flex");
    }
  }

  setupTabs() {
    const self = this;
    $("#top-coasters-tab").on("click", function () {
      if (self.activeTab === "coasters") return;
      self.activeTab = "coasters";
      $(this).addClass("active");
      $("#top-parks-tab").removeClass("active");
      self.updateUiForTab();
      self.renderCurrentList();
    });

    $("#top-parks-tab").on("click", function () {
      if (self.activeTab === "parks") return;
      self.activeTab = "parks";
      $(this).addClass("active");
      $("#top-coasters-tab").removeClass("active");
      self.updateUiForTab();
      self.renderCurrentList();
    });
  }

  updateUiForTab() {
    const isCoasters = this.activeTab === "coasters";
    $("#search-add-input")
      .val("")
      .attr(
        "placeholder",
        isCoasters
          ? "Buscar montaña rusa para añadir al top..."
          : "Buscar parque para añadir al top..."
      );
    $("#search-add-icon")
      .removeClass("fa-xmark text-danger")
      .addClass("fa-magnifying-glass text-muted");
    $("#autocomplete-dropdown").addClass("d-none").empty();
    $("#filter-current-input").val("");
    this.updateRestoreButtonState();
  }

  async loadData() {
    const $container = $("#edit-top-list-container");
    $container.html(
      `<div class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-2 mb-3"></i><p>Cargando tus rankings...</p></div>`
    );

    try {
      const [resC, resP] = await Promise.all([
        fetch(`${this.baseUrl}/api/php/profile_config.php?action=get_top_coasters`),
        fetch(`${this.baseUrl}/api/php/profile_config.php?action=get_top_parks`),
      ]);

      const [dataC, dataP] = await Promise.all([resC.json(), resP.json()]);

      this.topsData.coasters = dataC.success && dataC.tops ? dataC.tops : [];
      this.topsData.parks = dataP.success && dataP.tops ? dataP.tops : [];

      this.updateCounters();
      this.renderCurrentList();
      this.setUnsaved(false);
    } catch (err) {
      console.error("Error cargando tops:", err);
      $container.html(
        `<div class="text-center py-5 text-danger"><i class="fa-solid fa-triangle-exclamation fs-2 mb-2"></i><p>Error al cargar los datos del ranking.</p></div>`
      );
    }
  }

  updateCounters() {
    $("#coasters-count-badge").text(this.topsData.coasters.length);
    $("#parks-count-badge").text(this.topsData.parks.length);
    $("#current-count-display").text(
      this.activeTab === "coasters"
        ? `${this.topsData.coasters.length} coasters en tu ranking`
        : `${this.topsData.parks.length} parques en tu ranking`
    );
  }

  resolveImageUrl(raw) {
    if (!raw) return this.fallbackImage;
    const str = String(raw).trim();
    if (str.startsWith("http://") || str.startsWith("https://")) return str;
    const base = this.baseUrl.replace(/\/+$/, "");
    const path = str.startsWith("/") ? str : "/" + str;
    return `${base}${path}`;
  }

  formatStatus(rawStatus) {
    if (!rawStatus) return null;
    const s = String(rawStatus).toLowerCase().trim();
    if (
      s === "sbno" ||
      s.includes("sbno") ||
      s.includes("standing but not operating")
    ) {
      return {
        label: "SBNO",
        badgeClass: "badge-status-sbno",
        icon: "fa-pause",
      };
    }
    if (
      s.includes("operat") ||
      s.includes("abiert") ||
      s === "en funcionamiento"
    ) {
      return {
        label: "Operativa",
        badgeClass: "badge-status-operating",
        icon: "fa-circle-check",
      };
    }
    if (
      s.includes("cerrad") ||
      s.includes("defunct") ||
      s.includes("closed") ||
      s.includes("demolish") ||
      s.includes("clausur")
    ) {
      return {
        label: "Cerrada",
        badgeClass: "badge-status-closed",
        icon: "fa-circle-xmark",
      };
    }
    if (
      s.includes("construc") ||
      s.includes("under") ||
      s.includes("en obra")
    ) {
      return {
        label: "En construcción",
        badgeClass: "badge-status-construction",
        icon: "fa-person-digging",
      };
    }
    if (
      s.includes("reub") ||
      s.includes("relocat") ||
      s.includes("traslad")
    ) {
      return {
        label: "Reubicada",
        badgeClass: "badge-status-relocated",
        icon: "fa-arrow-right-arrow-left",
      };
    }
    return {
      label: rawStatus,
      badgeClass: "badge-status-other",
      icon: "fa-circle-info",
    };
  }

  showConfirm({
    title = "Confirmar acción",
    message = "¿Estás seguro?",
    subtext = "",
    icon = "fa-triangle-exclamation text-warning",
    confirmText = "Confirmar",
    confirmClass = "btn-danger",
    onConfirm = null,
    onCancel = null,
  } = {}) {
    const $modalEl = $("#rcw-confirm-modal");
    if (!$modalEl.length || !window.bootstrap) {
      if (confirm(message.replace(/<[^>]*>?/gm, ""))) {
        if (onConfirm) onConfirm();
      } else {
        if (onCancel) onCancel();
      }
      return;
    }

    $("#rcw-confirm-title").text(title);
    $("#rcw-confirm-message").html(message);
    if (subtext) {
      $("#rcw-confirm-subtext").text(subtext).removeClass("d-none");
    } else {
      $("#rcw-confirm-subtext").text("").addClass("d-none");
    }

    $("#rcw-confirm-icon").attr("class", `fa-solid ${icon}`);

    const $okBtn = $("#rcw-confirm-btn-ok");
    $okBtn
      .text(confirmText)
      .attr("class", `btn btn-sm ${confirmClass} rounded-0 px-4 fw-bold`);

    const bsModal = bootstrap.Modal.getOrCreateInstance($modalEl[0]);

    $okBtn.off("click").on("click", function () {
      bsModal.hide();
      if (typeof onConfirm === "function") {
        onConfirm();
      }
    });

    bsModal.show();
  }

  showAlert({
    title = "Aviso",
    message = "",
    type = "info",
    onClose = null,
  } = {}) {
    const $modalEl = $("#rcw-alert-modal");
    if (!$modalEl.length || !window.bootstrap) {
      alert(message.replace(/<[^>]*>?/gm, ""));
      if (onClose) onClose();
      return;
    }

    let iconClass = "fa-circle-info text-info";
    let btnClass = "btn-success";

    if (type === "success") {
      iconClass = "fa-circle-check text-success";
      btnClass = "btn-success";
    } else if (type === "danger" || type === "error") {
      iconClass = "fa-circle-xmark text-danger";
      btnClass = "btn-danger";
    } else if (type === "warning") {
      iconClass = "fa-triangle-exclamation text-warning";
      btnClass = "btn-warning text-dark";
    }

    $("#rcw-alert-title").text(title);
    $("#rcw-alert-message").html(message);
    $("#rcw-alert-icon").attr("class", `fa-solid ${iconClass}`);
    $("#rcw-alert-btn-ok").attr(
      "class",
      `btn btn-sm ${btnClass} rounded-0 px-4 fw-bold`
    );

    const bsModal = bootstrap.Modal.getOrCreateInstance($modalEl[0]);
    $modalEl.off("hidden.bs.modal").one("hidden.bs.modal", function () {
      if (typeof onClose === "function") onClose();
    });

    bsModal.show();
  }

  renderCurrentList() {
    const self = this;
    const isCoasters = this.activeTab === "coasters";
    const items = isCoasters ? this.topsData.coasters : this.topsData.parks;
    const $container = $("#edit-top-list-container").empty();

    this.updateCounters();

    if (!items.length) {
      $container.html(`
        <div class="text-center text-muted py-5 border border-secondary border-dashed p-5">
          <i class="fa-solid fa-ghost fa-3x mb-3 opacity-40"></i>
          <h5 class="text-white-50">No hay elementos en este ranking</h5>
          <p class="small text-muted mb-0">Usa el buscador superior para añadir tus primeros favoritos.</p>
        </div>
      `);
      return;
    }

    items.forEach((item, index) => {
      const id = isCoasters ? item.coaster_id || item.id : item.park_id || item.id;
      const title = isCoasters ? item.coaster_name : item.park_name;
      const parkName = item.park_name || item.location || "";
      const country = item.country_name || item.park_country || "";
      const subtitle = isCoasters
        ? parkName && country
          ? `${parkName} · ${country}`
          : parkName || country
        : country;
      const imgUrl = self.resolveImageUrl(item.imagen_url);
      const rank = index + 1;

      let rankClass = "rank-normal";
      if (rank === 1) rankClass = "rank-1";
      else if (rank === 2) rankClass = "rank-2";
      else if (rank === 3) rankClass = "rank-3";

      const html = `
        <div class="edit-top-item" data-id="${id}" data-index="${index}">
          <!-- Drag Handle -->
          <div class="drag-handle" title="Arrastra para mover">
            <i class="fa-solid fa-grip-lines fs-5"></i>
          </div>

          <!-- Rank Badge -->
          <div class="rank-badge-editor ${rankClass}">#${rank}</div>

          <!-- Thumbnail -->
          <div class="edit-item-thumb">
            <img src="${imgUrl}" alt="${title}" onerror="this.src='${self.fallbackImage}'">
          </div>

          <!-- Info -->
          <div class="edit-item-info">
            <div class="edit-item-title text-truncate mb-1">${title}</div>
            <div class="edit-item-sub text-truncate">${subtitle}</div>
          </div>

          <!-- Quick Move Dropdown & Delete -->
          <div class="edit-item-actions">
            <div class="dropdown">
              <button class="btn-action-icon dropdown-toggle dropdown-toggle-split" 
                      data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Mover a posición...">
                <i class="fa-solid fa-arrows-up-down" style="font-size:0.85rem;"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0" style="min-width:0; background:#11161b;">
                <div class="d-flex align-items-stretch" style="border:1px solid rgba(255,255,255,0.12); overflow:hidden;">
                  <!-- Opciones rápidas -->
                  <div class="d-flex flex-column" style="min-width:145px; background:#161c22;">
                    <button class="dropdown-item py-1 px-3 d-flex align-items-center gap-2 btn-move-quick" 
                            data-action="first" type="button" style="font-size:0.78rem; color:#fff;">
                      <i class="fa-solid fa-angles-up text-success"></i><span>Mover al inicio (#1)</span>
                    </button>
                    <button class="dropdown-item py-1 px-3 d-flex align-items-center gap-2 btn-move-quick" 
                            data-action="up" type="button" style="font-size:0.78rem; color:#fff;">
                      <i class="fa-solid fa-chevron-up" style="color:#38bdf8;"></i><span>Subir 1 posición</span>
                    </button>
                    <button class="dropdown-item py-1 px-3 d-flex align-items-center gap-2 btn-move-quick" 
                            data-action="down" type="button" style="font-size:0.78rem; color:#fff;">
                      <i class="fa-solid fa-chevron-down" style="color:#ef4444;"></i><span>Bajar 1 posición</span>
                    </button>
                    <button class="dropdown-item py-1 px-3 d-flex align-items-center gap-2 btn-move-quick" 
                            data-action="last" type="button" style="font-size:0.78rem; color:#fff;">
                      <i class="fa-solid fa-angles-down text-warning"></i><span>Mover al final</span>
                    </button>
                  </div>
                  <!-- Separador -->
                  <div style="width:3px; background:var(--rcw-green-neon); flex-shrink:0;"></div>
                  <!-- Salto a posición específica -->
                  <div class="d-flex flex-column justify-content-center align-items-center gap-1 px-2 py-2" 
                       style="min-width:85px; background:#0f1418;">
                    <label style="font-size:0.68rem; color:#888; text-transform:uppercase; font-weight:700;">Posición</label>
                    <input type="number" min="1" max="${items.length}" value="${rank}" 
                           class="form-control form-control-sm bg-dark text-white border-secondary text-center input-move-pos"
                           style="width:55px; height:26px; font-size:0.8rem; padding:2px;">
                    <button class="btn btn-sm btn-success rounded-0 w-100 btn-apply-pos" 
                            type="button" style="font-size:0.72rem; padding:2px 8px; font-weight:800;">IR</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botón Eliminar -->
            <button class="btn-action-icon btn-delete" title="Quitar de mi top">
              <i class="fa-solid fa-trash-can" style="font-size:0.85rem;"></i>
            </button>
          </div>
        </div>
      `;
      $container.append(html);
    });

    this.bindItemActions();
    this.initSortable();
  }

  bindItemActions() {
    const self = this;
    const isCoasters = this.activeTab === "coasters";
    const items = isCoasters ? this.topsData.coasters : this.topsData.parks;

    // Mover con opciones rápidas
    $(".btn-move-quick").off("click").on("click", function () {
      const $item = $(this).closest(".edit-top-item");
      const currentIndex = parseInt($item.data("index"));
      const action = $(this).data("action");

      let targetIndex = currentIndex;
      if (action === "first") targetIndex = 0;
      else if (action === "last") targetIndex = items.length - 1;
      else if (action === "up") targetIndex = Math.max(0, currentIndex - 1);
      else if (action === "down") targetIndex = Math.min(items.length - 1, currentIndex + 1);

      if (targetIndex !== currentIndex) {
        self.moveItem(currentIndex, targetIndex);
      }
    });

    // Salto directo a posición numérica
    $(".btn-apply-pos").off("click").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      const $btn = $(this);
      const $item = $btn.closest(".edit-top-item");
      const currentIndex = parseInt($item.data("index"));
      const $input = $btn.siblings(".input-move-pos");
      const targetPos = parseInt($input.val());

      if (isNaN(targetPos) || targetPos < 1) return;
      const targetIndex = Math.min(items.length - 1, Math.max(0, targetPos - 1));

      // Cerrar el dropdown explícitamente antes de mover (necesario en móvil)
      const $dropdownToggle = $item.find("[data-bs-toggle='dropdown']");
      if ($dropdownToggle.length && window.bootstrap) {
        const ddInstance = bootstrap.Dropdown.getInstance($dropdownToggle[0]);
        if (ddInstance) ddInstance.hide();
      }

      if (targetIndex !== currentIndex) {
        self.moveItem(currentIndex, targetIndex);
      }
    });

    // Aceptar Enter en el input de posición
    $(".input-move-pos").off("keydown.pos").on("keydown.pos", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        e.stopPropagation();
        $(this).siblings(".btn-apply-pos").trigger("click");
      }
    });

    // Eliminar del top con modal de confirmación
    $(".btn-delete").off("click").on("click", function () {
      const $item = $(this).closest(".edit-top-item");
      const index = parseInt($item.data("index"));
      const title = $item.find(".edit-item-title").text().trim();

      self.showConfirm({
        title: "Quitar del ranking",
        message: `¿Estás seguro de que quieres quitar <strong>"${title}"</strong> de tu top personal?`,
        subtext: "Podrás volver a añadirlo en cualquier momento desde el buscador.",
        icon: "fa-trash-can text-danger",
        confirmText: "Quitar",
        confirmClass: "btn-danger",
        onConfirm: () => {
          self.removeItem(index);
        },
      });
    });
  }

  initSortable() {
    const self = this;
    const containerEl = document.getElementById("edit-top-list-container");
    if (!containerEl || !window.Sortable) return;

    if (this.sortable) {
      this.sortable.destroy();
    }

    this.sortable = new Sortable(containerEl, {
      handle: ".drag-handle",
      animation: 180,
      forceFallback: true,
      fallbackOnBody: true,
      fallbackClass: "sortable-fallback-item",
      fallbackTolerance: 3,
      touchStartThreshold: 5,
      delay: 120,
      delayOnTouchOnly: true,
      scrollSensitivity: 80,
      scrollSpeed: 18,
      ghostClass: "sortable-ghost",
      dragClass: "sortable-drag",
      chosenClass: "sortable-chosen",
      setData: function (dataTransfer) {
        dataTransfer.setData("Text", "");
      },
      onStart: function (evt) {
        document.body.classList.add("is-dragging");
        if (window.getSelection) window.getSelection().removeAllRanges();
        const fallbackEl = document.querySelector(".sortable-fallback-item");
        if (fallbackEl && evt.item) {
          fallbackEl.style.width = evt.item.offsetWidth + "px";
        }

        // ── Auto-scroll: rastreamos el dedo/cursor directamente ───────────
        // Más fiable que buscar el ghost element, funciona igual en PC y móvil
        const ZONE_TOP    = 160; // más margen arriba por el sticky header
        const ZONE_BOTTOM = 110; // margen normal en la parte inferior
        const SPEED = 25;        // px/frame máximo
        let currentY = -1;
        let rafId    = null;
        let scrolling = false;

        function onPointerMove(e) {
          currentY = e.touches ? e.touches[0].clientY : e.clientY;
        }

        document.addEventListener("pointermove", onPointerMove, { passive: true });
        document.addEventListener("touchmove",   onPointerMove, { passive: true });

        function autoScroll() {
          if (currentY < 0) {
            self._autoScrollRaf = rafId = requestAnimationFrame(autoScroll);
            return;
          }

          const vh = window.visualViewport ? window.visualViewport.height : window.innerHeight;
          let delta = 0;

          if (currentY < ZONE_TOP) {
            delta = -Math.round(SPEED * (1 - currentY / ZONE_TOP));
          } else if (currentY > vh - ZONE_BOTTOM) {
            delta = Math.round(SPEED * ((currentY - (vh - ZONE_BOTTOM)) / ZONE_BOTTOM));
          }

          if (delta !== 0) {
            window.scrollBy({ top: delta, behavior: "instant" });
            // Seguir en RAF y mantener self._autoScrollRaf sincronizado
            self._autoScrollRaf = rafId = requestAnimationFrame(autoScroll);
          } else {
            // Sin scroll: pausar el RAF para no interferir con el scroll nativo
            rafId = null;
            self._autoScrollRaf = null;
            // Relanzar tras 50ms para detectar si el dedo vuelve a zona de scroll
            self._autoScrollTimeout = setTimeout(() => {
              if (self._autoScrollCleanup) { // sigue arrastrando
                self._autoScrollRaf = rafId = requestAnimationFrame(autoScroll);
              }
            }, 50);
          }
        }

        rafId = requestAnimationFrame(autoScroll);
        self._autoScrollRaf = rafId;
        self._autoScrollCleanup = () => {
          document.removeEventListener("pointermove", onPointerMove);
          document.removeEventListener("touchmove",   onPointerMove);
        };
      },
      onEnd: function (evt) {
        if (self._autoScrollRaf) {
          cancelAnimationFrame(self._autoScrollRaf);
          self._autoScrollRaf = null;
        }
        if (self._autoScrollCleanup) {
          self._autoScrollCleanup();
          self._autoScrollCleanup = null;
        }

        document.body.classList.remove("is-dragging");
        if (evt.oldIndex !== evt.newIndex) {
          self.moveItem(evt.oldIndex, evt.newIndex);
        }
      },
    });
  }

  moveItem(fromIndex, toIndex) {
    const isCoasters = this.activeTab === "coasters";
    const list = isCoasters ? this.topsData.coasters : this.topsData.parks;

    const [moved] = list.splice(fromIndex, 1);
    list.splice(toIndex, 0, moved);

    // Recalcular rank_position
    list.forEach((item, idx) => {
      item.rank_position = idx + 1;
    });

    this.setUnsaved(true);
    this.renderCurrentList();
  }

  removeItem(index) {
    const isCoasters = this.activeTab === "coasters";
    const list = isCoasters ? this.topsData.coasters : this.topsData.parks;

    list.splice(index, 1);

    // Recalcular posiciones
    list.forEach((item, idx) => {
      item.rank_position = idx + 1;
    });

    this.setUnsaved(true);
    this.renderCurrentList();
  }

  reverseCurrentOrder() {
    const isCoasters = this.activeTab === "coasters";
    const list = isCoasters ? this.topsData.coasters : this.topsData.parks;
    if (list.length < 2) return;

    const label = isCoasters ? "coasters" : "parques";

    this.showConfirm({
      title: "Invertir orden del ranking",
      message: `¿Quieres invertir por completo el orden actual de tu top de <strong>${label}</strong>?`,
      subtext: "El primer puesto pasará a ser el último y viceversa.",
      icon: "fa-arrow-down-up-across-line text-warning",
      confirmText: "Invertir Orden",
      confirmClass: "btn-warning text-dark",
      onConfirm: () => {
        list.reverse();
        list.forEach((item, idx) => {
          item.rank_position = idx + 1;
        });
        this.setUnsaved(true);
        this.renderCurrentList();
      },
    });
  }

  setupInListFilter() {
    $("#filter-current-input").on("input", function () {
      const q = $(this).val().toLowerCase().trim();
      const $items = $("#edit-top-list-container .edit-top-item");

      if (!q) {
        $items.show();
        return;
      }

      $items.each(function () {
        const title = $(this).find(".edit-item-title").text().toLowerCase();
        const sub = $(this).find(".edit-item-sub").text().toLowerCase();
        const matches = title.includes(q) || sub.includes(q);
        $(this).toggle(matches);
      });
    });
  }

  setupAutocomplete() {
    const self = this;
    const $input = $("#search-add-input");
    const $icon = $("#search-add-icon");
    const $dropdown = $("#autocomplete-dropdown");

    $input.on("input", function () {
      const text = $(this).val().trim();
      clearTimeout(self.searchDebounceTimer);

      if (text.length > 0) {
        self.setLastSearch(text);
        $icon
          .removeClass("fa-magnifying-glass text-muted")
          .addClass("fa-xmark text-danger")
          .css("cursor", "pointer")
          .attr("title", "Borrar texto");
      } else {
        $icon
          .removeClass("fa-xmark text-danger")
          .addClass("fa-magnifying-glass text-muted")
          .css("cursor", "default")
          .attr("title", "Buscar");
      }

      if (text.length < 2) {
        $dropdown.addClass("d-none").empty();
        return;
      }

      self.searchDebounceTimer = setTimeout(() => {
        self.performSearch(text);
      }, 250);
    });

    $icon.on("click", function () {
      if ($(this).hasClass("fa-xmark")) {
        $input.val("").trigger("input").focus();
        $dropdown.addClass("d-none").empty();
      }
    });

    $("#btn-restore-search").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      self.restoreLastSearch();
    });

    // Cerrar dropdown al hacer click fuera
    $(document).on("click", function (e) {
      if (!$(e.target).closest("#search-add-wrapper").length) {
        $dropdown.addClass("d-none");
      }
    });
  }

  performSearch(query) {
    const self = this;
    const isCoasters = this.activeTab === "coasters";
    const $dropdown = $("#autocomplete-dropdown");

    const endpoint = isCoasters
      ? `${this.baseUrl}/api/php/coasters.php?action=search&search=${encodeURIComponent(query)}&limit=30`
      : `${this.baseUrl}/api/php/profile_config.php?action=search&search=${encodeURIComponent(query)}`;

    $dropdown
      .removeClass("d-none")
      .html(
        '<div class="p-3 text-center text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Buscando...</div>'
      );

    $.ajax({
      url: endpoint,
      method: "GET",
      success: function (data) {
        $dropdown.empty();
        const results = Array.isArray(data) ? data : data.data || [];

        if (!results.length) {
          $dropdown.append(
            `<div class="p-3 text-center text-muted small">No se encontraron resultados para "${query}".</div>`
          );
          return;
        }

        const currentIds = new Set(
          (isCoasters ? self.topsData.coasters : self.topsData.parks).map((x) =>
            String(isCoasters ? x.coaster_id || x.id : x.park_id || x.id)
          )
        );

        results.forEach((item) => {
          const id = String(isCoasters ? item.id : item.park_id || item.id);
          const alreadyAdded = currentIds.has(id);
          const title = isCoasters ? item.coaster_name : item.park_name;
          const parkName = item.park_name || "";
          const country = item.park_country || item.country_name || "";
          const subtitle = isCoasters ? `${parkName} · ${country}` : country;
          const thumb = self.resolveImageUrl(item.imagen_url);

          const statusObj = isCoasters ? self.formatStatus(item.coaster_status) : null;
          const statusHtml = statusObj
            ? `<span class="coaster-status-badge ${statusObj.badgeClass}"><i class="fa-solid ${statusObj.icon} me-1"></i>${statusObj.label}</span>`
            : "";

          const $row = $(`
            <div class="autocomplete-item ${alreadyAdded ? "opacity-50" : ""}">
              <img src="${thumb}" alt="${title}" class="autocomplete-thumb me-2" onerror="this.src='${self.fallbackImage}'">
              <div class="flex-grow-1 min-w-0 pe-2">
                <div class="text-white fw-bold small d-flex align-items-center flex-wrap gap-2">
                  <span class="text-truncate">${title}</span>
                  ${statusHtml}
                </div>
                <div class="text-muted text-truncate" style="font-size:0.75rem;">${subtitle}</div>
              </div>
              <div class="flex-shrink-0">
                ${
                  alreadyAdded
                    ? '<span class="badge bg-secondary text-uppercase" style="font-size:0.65rem;">Ya en top</span>'
                    : `<button class="btn btn-sm btn-outline-success py-0 px-2 fw-bold" style="font-size:0.75rem;">
                         <i class="fa-solid fa-plus me-1"></i>Añadir
                       </button>`
                }
              </div>
            </div>
          `);

          if (!alreadyAdded) {
            $row.on("click", function () {
              const curSearch = $("#search-add-input").val().trim();
              if (curSearch) {
                self.setLastSearch(curSearch);
              }
              $dropdown.addClass("d-none");
              $("#search-add-input").val("").trigger("input").blur();
              if (document.activeElement) {
                document.activeElement.blur();
              }
              self.promptAddPosition(item);
            });
          }

          $dropdown.append($row);
        });
      },
      error: function (err) {
        console.error("Error en búsqueda:", err);
        $dropdown.html(
          '<div class="p-3 text-center text-danger small">Error en la búsqueda.</div>'
        );
      },
    });
  }

  addNewItem(rawItem) {
    this.promptAddPosition(rawItem);
  }

  promptAddPosition(rawItem) {
    const isCoasters = this.activeTab === "coasters";
    const list = isCoasters ? this.topsData.coasters : this.topsData.parks;
    const totalItems = list.length;

    // Si no hay ningún elemento en el ranking, se añade directo como #1
    if (totalItems === 0) {
      this.insertItemAt(rawItem, 0);
      return;
    }

    const title = isCoasters ? rawItem.coaster_name : rawItem.park_name;
    const parkName = rawItem.park_name || "";
    const country = rawItem.park_country || rawItem.country_name || "";
    const subtitle = isCoasters
      ? parkName && country
        ? `${parkName} · ${country}`
        : parkName || country
      : country;
    const thumb = this.resolveImageUrl(rawItem.imagen_url);

    // Actualizar preview en modal
    $("#modal-add-preview-img").attr("src", thumb);
    $("#modal-add-preview-title").text(title);
    $("#modal-add-preview-sub").text(subtitle || "");

    const nextRank = totalItems + 1;
    $("#badge-modal-add-last").text(`Puesto #${nextRank}`);
    $("#modal-add-pos-input")
      .attr("min", 1)
      .attr("max", nextRank)
      .val(nextRank);
    $("#modal-add-pos-helper").text(`Introduce un puesto entre 1 y ${nextRank}`);

    const $modalEl = $("#rcw-add-position-modal");
    if (!$modalEl.length || !window.bootstrap) {
      this.insertItemAt(rawItem, totalItems);
      return;
    }

    const bsModal = bootstrap.Modal.getOrCreateInstance($modalEl[0]);

    $("#btn-modal-add-first")
      .off("click")
      .on("click", () => {
        bsModal.hide();
        this.insertItemAt(rawItem, 0);
      });

    $("#btn-modal-add-last")
      .off("click")
      .on("click", () => {
        bsModal.hide();
        this.insertItemAt(rawItem, totalItems);
      });

    const applyCustom = () => {
      let pos = parseInt($("#modal-add-pos-input").val(), 10);
      if (isNaN(pos) || pos < 1) pos = 1;
      if (pos > nextRank) pos = nextRank;
      bsModal.hide();
      this.insertItemAt(rawItem, pos - 1);
    };

    $("#btn-modal-add-custom").off("click").on("click", applyCustom);
    $("#modal-add-pos-input")
      .off("keydown")
      .on("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          applyCustom();
        }
      });

    $modalEl.off("shown.bs.modal").one("shown.bs.modal", () => {
      $("#modal-add-pos-input").select();
    });

    $modalEl.off("hidden.bs.modal").on("hidden.bs.modal", () => {
      if (document.activeElement) {
        document.activeElement.blur();
      }
    });

    bsModal.show();
  }

  insertItemAt(rawItem, targetIndex) {
    const isCoasters = this.activeTab === "coasters";
    const list = isCoasters ? this.topsData.coasters : this.topsData.parks;

    const newItem = {
      ...rawItem,
      coaster_id: isCoasters ? rawItem.id : undefined,
      coaster_name: isCoasters ? rawItem.coaster_name : undefined,
      coaster_status: isCoasters ? rawItem.coaster_status : undefined,
      park_id: !isCoasters ? rawItem.park_id || rawItem.id : rawItem.park_id,
      park_name: rawItem.park_name,
      country_name: rawItem.country_name || rawItem.park_country,
      imagen_url: rawItem.imagen_url,
      rank_position: targetIndex + 1,
    };

    const safeIndex = Math.max(0, Math.min(list.length, targetIndex));
    list.splice(safeIndex, 0, newItem);

    // Recalcular posiciones de todo el ranking
    list.forEach((item, idx) => {
      item.rank_position = idx + 1;
    });

    this.setUnsaved(true);
    this.renderCurrentList();

    // Feedback visual en el nuevo elemento 
    const $insertedItem = $(
      `#edit-top-list-container .edit-top-item[data-index="${safeIndex}"]`
    );
    if ($insertedItem.length) {
      $insertedItem.addClass("sortable-chosen");
      setTimeout(() => $insertedItem.removeClass("sortable-chosen"), 1200);
    }
  }

  async saveChanges() {
    const $btn = $("#btn-save-top");
    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...');

    const csrfToken =
      document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content") ?? "";

    const coastersPayload = this.topsData.coasters.map((c, i) => ({
      coaster_id: c.coaster_id || c.id,
      rank_position: i + 1,
    }));

    const parksPayload = this.topsData.parks.map((p, i) => ({
      park_id: p.park_id || p.id,
      rank_position: i + 1,
    }));

    try {
      const [resC, resP] = await Promise.all([
        fetch(`${this.baseUrl}/api/php/profile_config.php?action=save_top_coasters`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken,
          },
          body: JSON.stringify({ items: coastersPayload }),
        }),
        fetch(`${this.baseUrl}/api/php/profile_config.php?action=save_top_parks`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken,
          },
          body: JSON.stringify({ items: parksPayload }),
        }),
      ]);

      const [dataC, dataP] = await Promise.all([resC.json(), resP.json()]);

      if (dataC.success && dataP.success) {
        this.setUnsaved(false);
        $btn
          .removeClass("btn-success")
          .addClass("btn-outline-success")
          .html('<i class="fa-solid fa-check me-2"></i>¡Guardado!');
        setTimeout(() => {
          $btn
            .removeClass("btn-outline-success")
            .addClass("btn-success")
            .html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios')
            .prop("disabled", false);
        }, 1800);
      } else {
        const msg =
          (!dataC.success ? "Coasters: " + (dataC.error || "error") : "") +
          (!dataP.success ? " Parques: " + (dataP.error || "error") : "");
        this.showAlert({
          title: "Error al guardar",
          message: msg.trim() || "No se pudieron guardar las modificaciones en la base de datos.",
          type: "danger",
        });
        $btn
          .prop("disabled", false)
          .html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
      }
    } catch (err) {
      console.error(err);
      this.showAlert({
        title: "Error de red",
        message: "No se pudo conectar con el servidor para guardar los cambios.",
        type: "danger",
      });
      $btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
    }
  }
}

// Inicializar al cargar el DOM
$(document).ready(function () {
  window.topEditor = new TopEditor();
  window.topEditor.init();
});
