/* trips.js — v3 diary style */
(function () {
  const B = window.BASE_URL,
    API = B + "/api/php/trips.php";
  let calendar = null,
    modals = {},
    rankPeriod = "year";
  const gm = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    let m = bootstrap.Modal.getInstance(el);
    if (!m) m = new bootstrap.Modal(el);
    return m;
  };
  const esc = (s) =>
    String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const escJS = (s) => String(s ?? "").replace(/'/g, "\\'");
  const fd = (s) =>
    s
      ? new Date(s).toLocaleDateString("es-ES", {
          day: "2-digit",
          month: "short",
          year: "numeric",
        })
      : "—";
  const dw = (s) =>
    s
      ? new Date(s).toLocaleDateString("es-ES", {
          weekday: "long",
          day: "numeric",
          month: "long",
        })
      : "";
  const days = (a, b) =>
    Math.max(1, Math.round((new Date(b) - new Date(a)) / 864e5)) + 1;

  const getDynamicCountdown = (start) => {
    const now = new Date();
    const s = new Date(start);
    s.setHours(0, 0, 0, 0);
    const diffMs = s - now;

    if (diffMs < 0) {
      return `<div class="d-flex align-items-center gap-1 text-success"><i class="fa-solid fa-play opacity-75"></i><span>En curso</span></div>`;
    }

    const d = Math.floor(diffMs / 864e5);
    const h = Math.floor((diffMs % 864e5) / 36e5);
    const m = Math.floor((diffMs % 36e5) / 6e4);
    const sec = Math.floor((diffMs % 6e4) / 1000);

    if (d > 0) {
      return `<div class="d-flex align-items-center gap-1" style="font-variant-numeric: tabular-nums; font-weight: 700;">
                <i class="fa-regular fa-clock opacity-75"></i>
                <span>${d}d ${h.toString().padStart(2, "0")}h ${m.toString().padStart(2, "0")}m</span>
              </div>`;
    } else {
      return `<div class="d-flex align-items-center gap-1 text-danger" style="font-variant-numeric: tabular-nums; font-weight: 800;">
                <i class="fa-solid fa-hourglass-half fa-spin-pulse"></i>
                <span>${h.toString().padStart(2, "0")}h ${m.toString().padStart(2, "0")}m ${sec.toString().padStart(2, "0")}s</span>
              </div>`;
    }
  };

  let countdownInterval = null;

  const flatpickrConfig = {
    locale: "es",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "d/m/Y",
    theme: "dark",
    disableMobile: "true",
  };

  const initFlatpickr = () => {
    // Calendarios de viaje
    const startEl = document.getElementById("ct-start");
    const endEl = document.getElementById("ct-end");

    if (startEl && endEl) {
      const fpStart = flatpickr(startEl, {
        ...flatpickrConfig,
        defaultDate: "today",
        onChange: function (selectedDates, dateStr) {
          fpEnd.set("minDate", dateStr);
          if (
            fpEnd.selectedDates[0] &&
            fpEnd.selectedDates[0] < selectedDates[0]
          ) {
            fpEnd.clear();
          }
          if (window.generateDays) window.generateDays();
        },
      });

      const fpEnd = flatpickr(endEl, {
        ...flatpickrConfig,
        minDate: fpStart.selectedDates[0] || "today",
        placeholder: "dd/mm/aaaa",
        onChange: function (selectedDates, dateStr) {
          if (window.generateDays) window.generateDays();
        },
      });
    }

    // Calendarios de estadísticas — sólo notifican al módulo de ranking,
    // que gestiona el estado interno (currentPeriod, customStart/End…)
    const rankStartEl = document.getElementById("rank-start-date");
    const rankEndEl = document.getElementById("rank-end-date");

    if (rankStartEl) {
      flatpickr(rankStartEl, {
        ...flatpickrConfig,
        onChange: function (selectedDates, dateStr) {
          if (rankEndEl && rankEndEl._flatpickr) {
            rankEndEl._flatpickr.set("minDate", dateStr);
          }
          if (window.rankingOnDateChange) {
            window.rankingOnDateChange("start", dateStr);
          }
        },
      });
    }

    if (rankEndEl) {
      flatpickr(rankEndEl, {
        ...flatpickrConfig,
        onChange: function (selectedDates, dateStr) {
          if (window.rankingOnDateChange) {
            window.rankingOnDateChange("end", dateStr);
          }
        },
      });
    }

    // Horas (Timepicker)
    document.querySelectorAll('input[type="time"]').forEach((el) => {
      flatpickr(el, {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        theme: "dark",
      });
    });
  };
  async function api(a, body) {
    const o = body
      ? {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
          body: JSON.stringify(body),
        }
      : { credentials: "same-origin" };
    return (await fetch(API + "?action=" + a, o)).json();
  }
  // Normalize diacritics client-side so 'kolmarden' → matches 'Kölmarden'
  const norm = (s) =>
    s
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();

  // ── TOAST ────────────────────────────────────────────────────
  function toast(msg, type = "success") {
    const icons = { success: "✅", error: "❌", info: "ℹ️", warning: "⚠️" };
    document.getElementById("toast-icon").textContent = icons[type] || "";
    document.getElementById("toast-title").textContent =
      type === "error" ? "Error" : type === "warning" ? "Aviso" : "OK";
    document.getElementById("toast-body").textContent = msg;
    bootstrap.Toast.getOrCreateInstance(
      document.getElementById("rcw-toast"),
    ).show();
  }

  // ── GENERIC CONFIRM MODAL ────────────────────────────────────
  function confirmModal(
    msg,
    onConfirm,
    title = "Confirmar",
    color = "#dc3545",
  ) {
    document.getElementById("gcm-message").textContent = msg;
    document.getElementById("gcm-title").innerHTML =
      `<i class="fa-solid fa-triangle-exclamation me-2"></i>${title}`;
    document.getElementById("gcm-confirm-btn").style.background = color;
    const btn = document.getElementById("gcm-confirm-btn");
    const fresh = btn.cloneNode(true);
    btn.replaceWith(fresh);
    fresh.onclick = () => {
      gm("generic-confirm-modal").hide();
      onConfirm();
    };
    gm("generic-confirm-modal").show();
  }

  window.refreshAll = () => {
    loadCalendar();
    loadInvites();
    loadTodayDashboard();
    window.loadTrips();
    window.loadRanking();
  };

  document.addEventListener("DOMContentLoaded", () => {
    refreshAll();
    document.getElementById("ct-submit-btn").onclick = submitCreate;
    document.getElementById("ct-start").onchange = generateDays;
    document.getElementById("ct-end").onchange = generateDays;
    setupAC(
      "av-park-search",
      "av-park-dropdown",
      "av-park-id",
      "search_parks",
      null,
    );
    setupAC(
      "lr-coaster-search",
      "lr-coaster-dropdown",
      "lr-coaster-id",
      "search_coasters",
      () => document.getElementById("lr-park-id").value,
    );
    // Friends autocomplete
    setupFriendsAC();

    // Inicializar Flatpickr
    initFlatpickr();

    // Iniciar timer de cuenta atrás
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(() => {
      document.querySelectorAll(".dynamic-countdown").forEach((el) => {
        const start = el.dataset.start;
        if (start) el.innerHTML = getDynamicCountdown(start);
      });
    }, 1000);
  });

  async function loadTodayDashboard() {
    const dash = document.getElementById("today-widget-container");
    if (!dash) return;

    // YYYY-MM-DD local timezone
    const tzoffset = new Date().getTimezoneOffset() * 60000;
    const todayIso = new Date(Date.now() - tzoffset)
      .toISOString()
      .split("T")[0];

    const j = await api("day_detail&date=" + todayIso);
    const d = j.data || {
      daily_visits: [],
      trip_parks: [],
      rides: [],
      coasters_by_park: {},
    };

    const parksToday = [...d.trip_parks, ...d.daily_visits];

    if (parksToday.length > 0) {
      const mainPark = parksToday[0];
      const bgImg = mainPark.imagen_url
        ? `url('${mainPark.imagen_url.startsWith("http") ? mainPark.imagen_url : B + mainPark.imagen_url}')`
        : `linear-gradient(135deg, var(--rcw-bg-card), var(--rcw-bg-main))`;

      let html = `<div class="card shadow-sm rounded-0 border-top-only position-relative overflow-hidden" style="min-height: 140px;">`;
      html += `<div class="w-100 h-100 position-absolute top-0 start-0" style="background: ${bgImg} center/cover; opacity: 0.3; z-index: 0;"></div>`;
      html += `<div class="card-body position-relative d-flex align-items-center justify-content-between flex-wrap gap-3" style="z-index: 1;">`;
      html += `<div>`;
      html += `<h4 class="fw-bold text-white mb-1" style="font-family: var(--rcw-font-title);"><i class="fa-solid fa-map-pin text-success me-2"></i>Hoy estás en ${esc(mainPark.park_name)}</h4>`;
      html += `<p class="text-light mb-0 text-capitalize">${dw(todayIso)}</p>`;
      html += `</div>`;
      html += `<button class="btn btn-success rounded-0 fw-bold px-4 py-2 shadow" onclick="openDay('${todayIso}')"><i class="fa-solid fa-clipboard-check me-2"></i>Ir a la Agenda de Hoy</button>`;
      html += `</div></div>`;

      dash.innerHTML = html;
    } else {
      let html = `<div class="card shadow-sm rounded-0 border-success" style="background:var(--rcw-bg-card-alt); border-width: 1px 1px 1px 4px;">`;
      html += `<div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">`;
      html += `<div>`;
      html += `<h5 class="fw-bold text-white mb-1"><i class="fa-solid fa-calendar-xmark text-muted me-2"></i>Hoy no tienes eventos</h5>`;
      html += `<p class="text-muted mb-0 text-capitalize">${dw(todayIso)}</p>`;
      html += `</div>`;
      html += `<button class="btn btn-outline-success rounded-0 fw-bold px-3" onclick="window.openAddVisit('${todayIso}')"><i class="fa-solid fa-plus me-2"></i>Registrar Visita Hoy</button>`;
      html += `</div></div>`;
      dash.innerHTML = html;
    }
  }

  // ── CALENDAR (park per day, not spanning bars) ────────────
  async function loadCalendar() {
    try {
      const j = await api("calendar");
      const data = j.data || [];

      // Group by date to handle multiple parks per day
      const grouped = {};
      data.forEach((e) => {
        if (!grouped[e.start]) grouped[e.start] = [];
        grouped[e.start].push(e);
      });

      const evts = Object.keys(grouped).map((date) => {
        let list = grouped[date];
        // Filter out 'trip_empty' if there are actual visits that day
        if (list.length > 1) {
          const nonEmpty = list.filter((e) => e.type !== "trip_empty");
          if (nonEmpty.length > 0) list = nonEmpty;
        }
        const primary = list[0];
        return {
          id: primary.id,
          title: primary.title,
          start: date,
          end: date,
          allDay: true,
          classNames: [
            primary.type === "trip_park" ? "fc-event-trip" : "fc-event-visit",
          ],
          extendedProps: {
            ...primary,
            all_parks: list,
          },
        };
      });
      if (calendar) {
        document.querySelectorAll(".fc-daygrid-day-frame").forEach((cell) => {
          cell.style.background = "";
          const o = cell.querySelector(".fc-cell-overlay");
          if (o) o.remove();
          const num = cell.querySelector(".fc-daygrid-day-number");
          if (num) num.classList.remove("has-photo-bg");
        });
        calendar.removeAllEvents();
        evts.forEach((e) => calendar.addEvent(e));
        return;
      }
      calendar = new FullCalendar.Calendar(
        document.getElementById("calendar"),
        {
          initialView: "dayGridMonth",
          locale: "es",
          height: "auto",
          contentHeight: window.innerWidth < 768 ? 500 : "auto",
          aspectRatio: window.innerWidth < 768 ? 0.8 : 1.35,
          firstDay: 1,
          handleWindowResize: true,
          headerToolbar: {
            left: "prev,next",
            center: "title",
            right: "",
          },
          eventDidMount: function (arg) {
            if (arg.view.type !== "dayGridMonth") return;

            const e = arg.event.extendedProps;
            const parks = e.all_parks || [e];
            const cell = arg.el.closest(".fc-daygrid-day-frame");
            if (!cell) return;

            if (cell.querySelector(".fc-cell-overlay")) return;

            if (parks.length >= 2) {
              const p1 = parks[0];
              const p2 = parks[1];
              const img1 = p1.imagen_url || B + "/dummy.jpg";
              const img2 = p2.imagen_url || B + "/dummy.jpg";

              cell.style.background = "none";
              const dual = document.createElement("div");
              dual.className = "fc-cell-dual";
              dual.innerHTML = `
                    <img src="${img1}" class="fc-cell-img-1">
                    <img src="${img2}" class="fc-cell-img-2">
                    <div class="fc-cell-sep"></div>
                `;
              cell.appendChild(dual);

              const overlay = document.createElement("div");
              overlay.className = "fc-cell-overlay";
              overlay.innerHTML = `
                     <div class="fc-cell-overlay-trip" style="background: rgba(0,0,0,0.5)">${parks.length} PARQUES</div>
                     <div class="fc-cell-overlay-name">Varios parques</div>
                 `;
              cell.appendChild(overlay);
            } else {
              const primary = parks[0];
              const isTrip =
                primary.type === "trip_park" || primary.type === "trip_empty";
              const tripTitle = isTrip ? primary.trip_title : "";
              const img = primary.imagen_url || B + "/dummy.jpg";

              cell.style.background = `url('${img}') center/cover`;
              const overlay = document.createElement("div");
              overlay.className = "fc-cell-overlay";
              overlay.innerHTML = `
                     ${isTrip ? `<div class="fc-cell-overlay-trip">${tripTitle}</div>` : "<div></div>"}
                     <div class="fc-cell-overlay-name">${primary.title}</div>
                 `;
              cell.appendChild(overlay);
            }

            const dayNum = cell.querySelector(".fc-daygrid-day-number");
            if (dayNum) dayNum.classList.add("has-photo-bg");

            arg.el.style.display = "none";
          },
          events: evts,
          dateClick: (i) => openDay(i.dateStr),
          eventClick: (i) => {
            const p = i.event.extendedProps;
            if (p.trip_id) openTrip(p.trip_id);
            else openDay(p.start);
          },
        },
      );
      calendar.render();
    } catch (e) {
      console.error(e);
    }
  }

  // ── INVITES ───────────────────────────────────────────────
  async function loadInvites() {
    try {
      const j = await api("pending_invites");
      const d = j.data || [];
      const c = document.getElementById("invites-container");
      if (!d.length) {
        c.innerHTML = "";
        return;
      }
      c.innerHTML = d
        .map(
          (
            i,
          ) => `<div class="invite-banner"><span class="invite-banner-text"><i class="fa-solid fa-envelope me-2"></i><strong>${esc(i.invited_by_name)}</strong> te invita a <strong>"${esc(i.trip_title)}"</strong></span>
    <div class="invite-banner-actions"><button class="btn btn-sm btn-success rounded-0 fw-bold" onclick="respondInv(${i.id},true)">Aceptar</button>
    <button class="btn btn-sm btn-outline-danger rounded-0" onclick="respondInv(${i.id},false)">Rechazar</button></div></div>`,
        )
        .join("");
    } catch (e) {}
  }

  // ── CREATE TRIP — GENERATE DAYS WITH "ADD PARK" BUTTON ───
  window.openCreateTripModal = () => {
    ["ct-title", "ct-desc", "ct-start", "ct-end", "ct-countries"].forEach(
      (id) => (document.getElementById(id).value = ""),
    );
    document.getElementById("ct-error").classList.add("d-none");
    document.getElementById("ct-days-container").classList.add("d-none");
    document.getElementById("ct-days-list").innerHTML = "";
    gm("create-trip-modal").show();
  };

  function generateDays() {
    const s = document.getElementById("ct-start").value,
      e = document.getElementById("ct-end").value;
    const cont = document.getElementById("ct-days-container"),
      list = document.getElementById("ct-days-list");
    if (!s || !e || new Date(e) < new Date(s)) {
      cont.classList.add("d-none");
      return;
    }
    cont.classList.remove("d-none");
    list.innerHTML = "";
    let cur = new Date(s);
    const end = new Date(e);
    let n = 1;
    while (cur <= end) {
      const ds = cur.toISOString().split("T")[0];
      const lbl = cur.toLocaleDateString("es-ES", {
        weekday: "short",
        day: "numeric",
        month: "short",
      });
      const dayDiv = document.createElement("div");
      dayDiv.className = "ct-day-block mb-3 p-3";
      dayDiv.style.cssText =
        "background:var(--rcw-bg-card-alt);border:1px solid var(--rcw-border)";
      dayDiv.dataset.date = ds;
      dayDiv.innerHTML = `<div class="d-flex align-items-center justify-content-between mb-2">
      <strong class="small text-success">Día ${n} — <span class="text-capitalize">${lbl}</span></strong>
      <button type="button" class="btn btn-outline-success btn-sm rounded-0 ct-add-park-btn" data-date="${ds}"><i class="fa-solid fa-plus me-1"></i>Añadir parque</button></div>
      <div class="ct-parks-slots"></div>`;
      list.appendChild(dayDiv);
      // Add first park slot automatically
      addParkSlot(dayDiv.querySelector(".ct-parks-slots"), ds, 1);
      // Button handler
      dayDiv.querySelector(".ct-add-park-btn").onclick = function () {
        const slots = dayDiv.querySelector(".ct-parks-slots");
        const order = slots.querySelectorAll(".ct-park-slot").length + 1;
        addParkSlot(slots, ds, order);
      };
      cur.setDate(cur.getDate() + 1);
      n++;
    }
  }

  function addParkSlot(container, date, order) {
    const slot = document.createElement("div");
    slot.className =
      "ct-park-slot d-flex align-items-center gap-2 mb-2 position-relative";
    slot.innerHTML = `<span class="badge bg-success rounded-0" style="min-width:24px">${order}</span>
    <input type="text" class="form-control form-control-sm rounded-0 ct-park-search-input" placeholder="Buscar parque..." autocomplete="off" style="flex:1">
    <input type="hidden" class="ct-park-id-input" data-date="${date}" data-order="${order}">
    <button type="button" class="btn btn-sm btn-outline-danger rounded-0 ct-remove-slot" title="Quitar"><i class="fa-solid fa-xmark"></i></button>
    <div class="ac-dropdown ct-park-drop-dynamic"></div>`;
    container.appendChild(slot);
    // Remove slot
    slot.querySelector(".ct-remove-slot").onclick = () => {
      slot.remove();
    };
    // Autocomplete
    const inp = slot.querySelector(".ct-park-search-input"),
      dr = slot.querySelector(".ct-park-drop-dynamic"),
      hid = slot.querySelector(".ct-park-id-input");
    let t;
    inp.oninput = () => {
      clearTimeout(t);
      const q = inp.value.trim();
      if (q.length < 2) {
        dr.classList.remove("show");
        return;
      }
      t = setTimeout(async () => {
        const j = await api("search_parks&q=" + encodeURIComponent(q));
        const d = j.data || [];
        if (!d.length) {
          dr.classList.remove("show");
          return;
        }
        // Client-side diacritic-insensitive reorder (server uses unaccent too)
        const qn = norm(q);
        const sorted = d.sort((a, b) => {
          const na = norm(a.park_name),
            nb = norm(b.park_name);
          return na.startsWith(qn) ? -1 : nb.startsWith(qn) ? 1 : 0;
        });
        dr.innerHTML = sorted
          .map(
            (p) =>
              `<div class="ac-item" data-id="${p.id}" data-name="${esc(p.park_name)}" data-country="${esc(p.park_country || "")}">${esc(p.park_name)} <small class="text-muted">${esc(p.park_location || "")}</small></div>`,
          )
          .join("");
        dr.classList.add("show");
        dr.querySelectorAll(".ac-item").forEach((el) => {
          el.onclick = () => {
            inp.value = el.dataset.name;
            hid.value = el.dataset.id;
            dr.classList.remove("show");

            if (el.dataset.country && window.addCountryTag) {
              window.addCountryTag(el.dataset.country);
            }
          };
        });
      }, 250);
    };
    document.addEventListener("click", (e) => {
      if (!dr.contains(e.target) && e.target !== inp)
        dr.classList.remove("show");
    });
  }

  window.currentEditTripId = null;

  window.openCreateTripModal = () => {
    window.currentEditTripId = null;
    document.getElementById("ct-title").value = "";
    document.getElementById("ct-desc").value = "";
    if (document.getElementById("ct-start")._flatpickr) {
      document.getElementById("ct-start")._flatpickr.setDate("today");
    } else {
      document.getElementById("ct-start").value = "";
    }
    if (document.getElementById("ct-end")._flatpickr) {
      document.getElementById("ct-end")._flatpickr.clear();
    } else {
      document.getElementById("ct-end").value = "";
    }
    document.getElementById("ct-countries").value = "";
    document.getElementById("ct-days-container").classList.add("d-none");
    document
      .getElementById("create-trip-modal")
      .querySelector(".modal-title").innerHTML =
      '<i class="fa-solid fa-plus-circle me-2"></i>Nuevo Viaje';
    document.getElementById("ct-submit-btn").innerHTML =
      '<i class="fa-solid fa-plus me-1"></i>Crear Viaje';

    if (window.renderCountryTags) window.renderCountryTags();
    gm("create-trip-modal").show();
  };

  window.openEditTrip = async (id) => {
    gm("trip-detail-modal").hide();

    const j = await api("detail&trip_id=" + id);
    if (!j.success) {
      toast("Error al cargar el viaje para edición", "error");
      return;
    }
    const t = j.data;

    document.getElementById("ct-title").value = t.title || "";
    document.getElementById("ct-desc").value = t.description || "";

    // Actualizar Flatpickr si existe
    if (document.getElementById("ct-start")._flatpickr) {
      document.getElementById("ct-start")._flatpickr.setDate(t.start_date);
    } else {
      document.getElementById("ct-start").value = t.start_date || "";
    }

    if (document.getElementById("ct-end")._flatpickr) {
      document.getElementById("ct-end")._flatpickr.setDate(t.end_date);
    } else {
      document.getElementById("ct-end").value = t.end_date || "";
    }

    const manualCountries = t.parks_visited
      ? t.parks_visited
          .split(",")
          .map((c) => c.trim())
          .filter((c) => c)
      : [];

    const allRaw = [...(t.countries || []), ...manualCountries];
    const uniqueMap = new Map();
    allRaw.forEach((c) => {
      const key = c
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();
      if (!uniqueMap.has(key)) uniqueMap.set(key, c); // Guarda la primera variante que encuentre (idealmente la de t.countries que suele venir de la BD con acentos correctos)
    });
    const allCountries = Array.from(uniqueMap.values());

    document.getElementById("ct-countries").value = allCountries.join(", ");

    if (window.renderCountryTags) window.renderCountryTags();

    document
      .getElementById("create-trip-modal")
      .querySelector(".modal-title").innerHTML =
      '<i class="fa-solid fa-pen me-2"></i>Editar Viaje';
    document.getElementById("ct-submit-btn").innerHTML =
      '<i class="fa-solid fa-save me-1"></i>Guardar Cambios';

    window.currentEditTripId = id;

    // We don't repopulate days complex config for edits currently to avoid duplicates,
    // we just hide it and show it's already configured.
    document.getElementById("ct-days-container").classList.add("d-none");

    setTimeout(() => gm("create-trip-modal").show(), 300);
  };

  async function submitCreate() {
    const title = document.getElementById("ct-title").value.trim(),
      desc = document.getElementById("ct-desc").value.trim();
    const start = document.getElementById("ct-start").value,
      end = document.getElementById("ct-end").value;
    const err = document.getElementById("ct-error");
    if (!title || !start || !end) {
      err.textContent = "Completa los campos obligatorios";
      err.classList.remove("d-none");
      return;
    }
    if (new Date(end) < new Date(start)) {
      err.textContent = "La fecha fin debe ser posterior";
      err.classList.remove("d-none");
      return;
    }
    const countries = document.getElementById("ct-countries").value.trim();

    try {
      const btn = document.getElementById("ct-submit-btn");
      const oldHtml = btn.innerHTML;
      btn.innerHTML = '<div class="spinner-border spinner-border-sm"></div>';
      btn.disabled = true;

      let j;
      if (window.currentEditTripId) {
        j = await api("update", {
          trip_id: window.currentEditTripId,
          title,
          description: desc,
          start_date: start,
          end_date: end,
          parks_visited: countries, // Usamos este campo internamente
        });
      } else {
        const parks = [];
        document.querySelectorAll(".ct-park-id-input").forEach((h) => {
          if (h.value)
            parks.push({
              park_id: +h.value,
              visit_date: h.dataset.date,
              visit_order: +h.dataset.order,
            });
        });
        j = await api("create", {
          title,
          description: desc,
          start_date: start,
          end_date: end,
          countries,
          parks,
        });
      }

      btn.innerHTML = oldHtml;
      btn.disabled = false;

      if (j.success) {
        gm("create-trip-modal").hide();
        loadTrips();
        loadCalendar();
        toast(
          window.currentEditTripId
            ? "Viaje actualizado"
            : "Viaje creado correctamente",
        );
        if (window.currentEditTripId) openTrip(window.currentEditTripId);
      } else {
        err.textContent = j.error || "Error";
        err.classList.remove("d-none");
      }
    } catch (e) {
      err.textContent = "Error de red";
      err.classList.remove("d-none");
    }
  }

  // ── COUNTRY TAGS LOGIC ──────────────────────────────────────
  document.addEventListener("DOMContentLoaded", () => {
    const popularCountries = [
      "España",
      "Francia",
      "Alemania",
      "Italia",
      "Reino Unido",
      "Suecia",
      "Dinamarca",
      "Noruega",
      "Finlandia",
      "Países Bajos",
      "Bélgica",
      "Polonia",
      "Suiza",
      "Austria",
      "Portugal",
      "Estados Unidos",
      "Canadá",
      "Japón",
      "China",
    ];

    const hiddenInput = document.getElementById("ct-countries");
    const container = document.getElementById("ct-countries-container");
    const visualInput = document.getElementById("ct-countries-input");
    const dropdown = document.getElementById("ct-countries-dropdown");

    if (!hiddenInput || !container || !visualInput || !dropdown) return;

    let tags = [];

    window.renderCountryTags = () => {
      // Leer del hidden input y limpiar
      const currentVal = hiddenInput.value.trim();
      tags = currentVal
        ? currentVal
            .split(",")
            .map((t) => t.trim())
            .filter((t) => t)
        : [];

      // Limpiar visual tags pero dejar el input de texto
      container.querySelectorAll(".badge").forEach((e) => e.remove());

      // Renderizar los pills antes del input
      tags.forEach((tag) => {
        const pill = document.createElement("span");
        pill.className =
          "badge bg-success rounded-pill d-flex align-items-center gap-1 my-1 px-2 py-1";
        pill.style.fontSize = "0.85rem";
        pill.innerHTML = `${esc(tag)} <i class="fa-solid fa-xmark" style="cursor:pointer" onclick="window.removeCountryTag('${esc(tag)}')"></i>`;
        container.insertBefore(pill, visualInput);
      });
    };

    window.removeCountryTag = (tagToRemove) => {
      tags = tags.filter((t) => t !== tagToRemove);
      hiddenInput.value = tags.join(", ");
      window.renderCountryTags();
    };

    window.addCountryTag = (tagToAdd) => {
      tagToAdd = tagToAdd.trim();
      if (tagToAdd && !tags.includes(tagToAdd)) {
        tags.push(tagToAdd);
        hiddenInput.value = tags.join(", ");
      }
      visualInput.value = "";
      dropdown.style.display = "none";
      window.renderCountryTags();
      visualInput.focus();
    };

    visualInput.addEventListener("input", (e) => {
      const q = e.target.value.toLowerCase().trim();
      if (!q) {
        dropdown.style.display = "none";
        return;
      }
      const matches = popularCountries.filter(
        (c) => c.toLowerCase().includes(q) && !tags.includes(c),
      );

      if (matches.length > 0) {
        dropdown.innerHTML = matches
          .map(
            (c) => `
                  <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); window.addCountryTag('${escJS(c)}')">${c}</a></li>
              `,
          )
          .join("");
        dropdown.style.display = "block";
      } else {
        // Permitir añadir lo que escriban aunque no esté en la lista popular
        dropdown.innerHTML = `<li><a class="dropdown-item text-success" href="#" onclick="event.preventDefault(); window.addCountryTag('${escJS(q)}')"><i class="fa-solid fa-plus me-2"></i>Añadir "${esc(q)}"</a></li>`;
        dropdown.style.display = "block";
      }
    });

    visualInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === ",") {
        e.preventDefault();
        if (visualInput.value.trim()) {
          window.addCountryTag(visualInput.value);
        }
      } else if (
        e.key === "Backspace" &&
        visualInput.value === "" &&
        tags.length > 0
      ) {
        window.removeCountryTag(tags[tags.length - 1]);
      }
    });

    // Cerrar dropdown al clicar fuera
    document.addEventListener("click", (e) => {
      if (!container.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = "none";
      }
    });

    container.addEventListener("click", () => visualInput.focus());
  });

  window.respondInv = async (id, a) => {
    await api("respond_invite", { invite_id: id, accept: a });
    loadInvites();
    loadTrips();
    loadCalendar();
  };

  // ── AUTOCOMPLETE ──────────────────────────────────────────
  function setupAC(iid, did, hid, action, pidFn) {
    const inp = document.getElementById(iid),
      dr = document.getElementById(did);
    let t;
    inp.oninput = () => {
      clearTimeout(t);
      const q = inp.value.trim();
      if (q.length < 2) {
        dr.classList.remove("show");
        return;
      }
      t = setTimeout(async () => {
        let u = action;
        if (pidFn) {
          const p = pidFn();
          if (p) u += "&park_id=" + p;
        }
        const j = await api(u + "&q=" + encodeURIComponent(q));
        const d = j.data || [];
        if (!d.length) {
          dr.classList.remove("show");
          return;
        }
        dr.innerHTML = d
          .map((i) => {
            const n = i.park_name || i.coaster_name;
            const s = i.park_location || i.park_name || "";
            return `<div class="ac-item" data-id="${i.id}" data-name="${esc(n)}">${esc(n)}${s ? " <small>" + esc(s) + "</small>" : ""}</div>`;
          })
          .join("");
        dr.classList.add("show");
        dr.querySelectorAll(".ac-item").forEach((el) => {
          el.onclick = () => {
            inp.value = el.dataset.name;
            document.getElementById(hid).value = el.dataset.id;
            dr.classList.remove("show");
          };
        });
      }, 300);
    };
    document.addEventListener("click", (e) => {
      if (!dr.contains(e.target) && e.target !== inp)
        dr.classList.remove("show");
    });
  }

  // ── FRIENDS AUTOCOMPLETE ──────────────────────────────────────
  let myFriends = [];
  async function setupFriendsAC() {
    try {
      const res = await fetch(
        window.BASE_URL + "/api/php/users.php?action=get_friends_data",
      );
      const j = await res.json();
      if (j.success && j.data && j.data.friends) {
        myFriends = j.data.friends;
      }
    } catch (e) {
      console.error("Error loading friends:", e);
    }

    const inp = document.getElementById("collab-username");
    const dr = document.getElementById("collab-dropdown");
    if (!inp || !dr) return;

    inp.addEventListener("input", () => {
      const q = inp.value.toLowerCase().trim();
      if (!q) {
        dr.innerHTML = "";
        dr.style.display = "none";
        return;
      }
      const filtered = myFriends.filter((f) =>
        f.username.toLowerCase().includes(q),
      );
      if (!filtered.length) {
        dr.innerHTML =
          '<div class="ac-item text-muted" style="padding:0.5rem;">No tienes amigos con ese nombre</div>';
      } else {
        dr.innerHTML = filtered
          .map(
            (f) => `
          <div class="ac-item d-flex align-items-center gap-2" style="cursor:pointer; padding:0.5rem;" onclick="document.getElementById('collab-username').value='${esc(f.username)}'; document.getElementById('collab-dropdown').style.display='none';">
            <img src="${f.profile_image ? esc(f.profile_image) : window.BASE_URL + "/web/img/avatars/default_avatar.svg"}" style="width:24px;height:24px;border-radius:50%;object-fit:cover;">
            <strong>${esc(f.username)}</strong>
          </div>
        `,
          )
          .join("");
      }
      dr.style.display = "block";
    });

    document.addEventListener("click", (e) => {
      if (!dr.contains(e.target) && e.target !== inp) {
        dr.style.display = "none";
      }
    });
  }

  // ─── VIAJES ──────────────────────────────────────────────────
  window.loadTrips = async function () {
    const container = document.getElementById("trips-grid");
    if (!container) return;
    container.innerHTML =
      '<div class="text-center py-4 text-muted small" style="grid-column: 1 / -1;"><div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>Cargando viajes...</div>';
    try {
      const j = await api("list");
      const d = j.data || [];
      if (!d.length) {
        container.innerHTML =
          '<div class="text-center py-4 text-muted" style="grid-column: 1 / -1;"><i class="fa-solid fa-suitcase fa-2x mb-2 opacity-50"></i><br>Aún no has registrado ningún viaje.</div>';
        return;
      }
      let html = "";
      d.forEach((t) => {
        const start = new Date(t.start_date);
        start.setHours(0, 0, 0, 0);
        const end = new Date(t.end_date);
        end.setHours(23, 59, 59, 999);
        const mon = start.toLocaleString("es-ES", { month: "short" });
        const y = start.getFullYear();
        const diff = Math.ceil((end - start) / 86400000) + 1;
        const today = new Date();
        let t_status = "upcoming";
        if (today > end) t_status = "past";
        else if (today >= start && today <= end) t_status = "active";

        const statusClass =
          t_status === "past"
            ? "bg-secondary"
            : t_status === "active"
              ? "bg-success"
              : "bg-warning text-dark";
        const statusText =
          t_status === "past"
            ? "Pasado"
            : t_status === "active"
              ? "Activo"
              : "Próximo";
        let imgUrl =
          "https://st3.depositphotos.com/3436901/14792/i/450/depositphotos_147926787-stock-photo-plane-flying-over-blue-sky.jpg";
        if (t.cover_image) {
          imgUrl = t.cover_image.startsWith("http")
            ? t.cover_image
            : window.RCW_BASE_URL + t.cover_image;
        }
        const pNames = t.park_names ? t.park_names : "Sin parques planificados";
        const startStr = start.toLocaleDateString("es-ES", {
          day: "numeric",
          month: "short",
        });
        const endStr = end.toLocaleDateString("es-ES", {
          day: "numeric",
          month: "short",
          year: "numeric",
        });

        html += `
          <div class="card shadow-sm h-100 border-0" style="background:var(--rcw-bg-card-alt); border-radius: 12px; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s;" onclick="openTrip(${t.id})" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--bs-shadow-sm)'">
            <div style="height: 130px; position: relative; overflow: hidden; border-top-left-radius: 12px; border-top-right-radius: 12px;">
               <img src="${imgUrl}" onerror="this.onerror=null; this.src='${window.RCW_BASE_URL}/dummy.jpg';" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover; z-index: 0;">
               <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, transparent 10%, rgba(10,12,16,0.95)); z-index: 1;"></div>
               <div class="position-absolute bottom-0 start-0 w-100 p-3 pb-2 text-white" style="z-index: 2;">
                 <h5 class="fw-bold mb-1 text-truncate" style="font-family: var(--rcw-font-title); font-size: 1.15rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">${esc(t.title)}</h5>
                 <div class="small text-truncate" style="color: #a3aed0; font-size: 0.8rem;"><i class="fa-solid fa-map-location-dot me-1"></i>${esc(pNames)}</div>
               </div>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-start">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <span class="badge bg-dark border border-secondary text-light fw-normal"><i class="fa-regular fa-calendar text-success me-1"></i>${startStr} — ${endStr}</span>
                <span class="badge bg-dark border border-secondary text-light fw-normal"><i class="fa-regular fa-clock text-success me-1"></i>${diff} d</span>
              </div>
              ${t.description ? `<div class="small text-muted mt-1" style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height: 1.4;">${esc(t.description)}</div>` : ""}
            </div>
          </div>
        `;
      });
      container.innerHTML = html;

      // Filtrar y mostrar Próximos Viajes
      renderUpcomingTrips(d);
    } catch (e) {
      container.innerHTML =
        '<div class="text-center py-4 text-danger" style="grid-column: 1 / -1;">Error cargando viajes.</div>';
    }
  };

  function renderUpcomingTrips(allTrips) {
    const section = document.getElementById("upcoming-trips-section");
    const grid = document.getElementById("upcoming-trips-grid");
    if (!section || !grid) return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const upcoming = allTrips
      .filter((t) => new Date(t.start_date) > today)
      .sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

    if (upcoming.length === 0) {
      section.classList.add("d-none");
      return;
    }

    section.classList.remove("d-none");
    let html = "";
    upcoming.forEach((t) => {
      let imgUrl =
        "https://st3.depositphotos.com/3436901/14792/i/450/depositphotos_147926787-stock-photo-plane-flying-over-blue-sky.jpg";
      if (t.cover_image) {
        imgUrl = t.cover_image.startsWith("http")
          ? t.cover_image
          : window.RCW_BASE_URL + t.cover_image;
      }

      html += `
        <div class="col-11 col-sm-8 col-md-6 col-lg-4 flex-shrink-0">
          <div class="card shadow-sm border-0 h-100 rounded-3 overflow-hidden position-relative" 
               style="background:var(--rcw-bg-card-alt); cursor:pointer; transition:transform 0.2s;" 
               onclick="openTrip(${t.id})"
               onmouseover="this.style.transform='translateY(-3px)'" 
               onmouseout="this.style.transform='translateY(0)'">
            <div style="height: 160px; position: relative;">
               <img src="${imgUrl}" onerror="this.onerror=null; this.src='${window.RCW_BASE_URL}/dummy.jpg';" class="w-100 h-100" style="object-fit: cover;">
               <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);"></div>
               <div class="position-absolute bottom-0 start-0 p-3 w-100">
                  <div class="badge bg-success mb-2" style="font-size:0.6rem; letter-spacing:1px;">PRÓXIMO</div>
                  <h5 class="text-white fw-bold mb-0 text-truncate" style="font-family:var(--rcw-font-title)">${esc(t.title)}</h5>
               </div>
            </div>
            <div class="card-body p-3">
               <div class="d-flex align-items-center justify-content-between mb-2">
                  <div class="small text-muted"><i class="fa-regular fa-calendar me-1"></i>${fd(t.start_date)}</div>
                  <div class="fw-bold text-warning small dynamic-countdown" data-start="${t.start_date}">${getDynamicCountdown(t.start_date)}</div>
               </div>
               <div class="small text-muted text-truncate">${esc(t.park_names || "Sin parques")}</div>
            </div>
          </div>
        </div>
      `;
    });
    grid.innerHTML = html;
  }
  // ─── RANKING ─────────────────────────────────────────────────
  window.loadRanking = async function () {
    const sType = document.getElementById("rank-type-select");
    const container = document.getElementById("ranking-container");
    if (!container || !sType) return;
    const pBtns = document.querySelectorAll(".rank-period-btn");
    const sDate = document.getElementById("rank-start-date");
    const eDate = document.getElementById("rank-end-date");
    const prevBtn = document.getElementById("rank-prev-btn");
    const nextBtn = document.getElementById("rank-next-btn");
    const navLabel = document.getElementById("rank-nav-label");
    let currentPeriod = "year";
    let baseDate = new Date();
    let customStart = "";
    let customEnd = "";
    let cachedData = null;

    function updateLabel() {
      const navContainer = document.getElementById("rank-nav-container");
      if (currentPeriod === "all" || currentPeriod === "custom") {
        if (navContainer) navContainer.classList.add("d-none");
      } else {
        if (navContainer) navContainer.classList.remove("d-none");
        if (currentPeriod === "year")
          navLabel.textContent = baseDate.getFullYear();
        else if (currentPeriod === "month") {
          let s = baseDate.toLocaleString("es-ES", {
            month: "long",
            year: "numeric",
          });
          navLabel.textContent = s.charAt(0).toUpperCase() + s.slice(1);
        } else if (currentPeriod === "week") {
          const wStart = new Date(baseDate);
          wStart.setDate(wStart.getDate() - wStart.getDay() + 1);
          navLabel.textContent =
            "Semana " +
            wStart.toLocaleDateString("es-ES", {
              day: "numeric",
              month: "short",
            });
        }
      }

      if (currentPeriod !== "custom" && currentPeriod !== "all") {
        const d = getDates();
        if (sDate) {
          if (sDate._flatpickr) sDate._flatpickr.setDate(d.start || "", false);
          else sDate.value = d.start || "";
        }
        if (eDate) {
          if (eDate._flatpickr) eDate._flatpickr.setDate(d.end || "", false);
          else eDate.value = d.end || "";
        }
      } else if (currentPeriod === "all") {
        if (sDate) {
          if (sDate._flatpickr) sDate._flatpickr.clear();
          else sDate.value = "";
        }
        if (eDate) {
          if (eDate._flatpickr) eDate._flatpickr.clear();
          else eDate.value = "";
        }
      }
    }

    function getDates() {
      let start, end;
      const fmt = (d) =>
        d.getFullYear() +
        "-" +
        String(d.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(d.getDate()).padStart(2, "0");

      if (currentPeriod === "week") {
        const d = new Date(baseDate);
        const day = d.getDay() || 7;
        d.setDate(d.getDate() - day + 1);
        start = fmt(d);
        const e = new Date(d);
        e.setDate(e.getDate() + 6);
        end = fmt(e);
      } else if (currentPeriod === "month") {
        start = fmt(new Date(baseDate.getFullYear(), baseDate.getMonth(), 1));
        end = fmt(new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, 0));
      } else if (currentPeriod === "year") {
        start = fmt(new Date(baseDate.getFullYear(), 0, 1));
        end = fmt(new Date(baseDate.getFullYear(), 11, 31));
      } else if (currentPeriod === "custom") {
        start = customStart;
        end = customEnd;
      }
      return { start, end };
    }

    async function fetchRanking() {
      const { start, end } = getDates();
      let act = sType.value === "coasters" ? "ride_ranking" : "park_ranking";

      container.innerHTML =
        '<div class="text-center py-4 text-muted small"><div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>Cargando ranking...</div>';
      try {
        let q = act;
        if (start) q += `&start=${start}`;
        if (end) q += `&end=${end}`;
        const j = await api(q);
        cachedData = j.data || [];
        const tc = document.getElementById("rank-trip-count");
        if (tc && j.total_trips !== undefined)
          tc.textContent =
            j.total_trips + (j.total_trips === 1 ? " viaje" : " viajes");
        renderRanking();
      } catch (e) {
        container.innerHTML =
          '<div class="text-center py-4 text-danger">Error cargando ranking.</div>';
      }
    }

    function renderRanking() {
      if (!cachedData || !cachedData.length) {
        container.innerHTML =
          '<div class="text-center py-5 text-muted"><i class="fa-solid fa-chart-line fa-2x mb-3 opacity-50"></i><br>No hay datos en este periodo.</div>';
        return;
      }

      const max = Math.max(
        ...cachedData.map((i) =>
          parseInt(
            sType.value === "coasters" ? i.times_ridden : i.times_visited,
          ),
        ),
      );
      let html =
        '<div class="list-group list-group-flush custom-scrollbar" style="max-height: 700px; overflow-y: auto; overflow-x: hidden;">';

      cachedData.forEach((item, idx) => {
        const isC = sType.value === "coasters";
        const title = isC ? item.coaster_name : item.park_name;
        const sub = isC ? item.park_name : item.park_location;
        const count = parseInt(isC ? item.times_ridden : item.times_visited);
        const img = item.imagen_url || "";
        const pct = (count / max) * 100;

        html += `
          <div class="list-group-item bg-transparent border-bottom border-secondary border-opacity-25 px-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="fw-bold text-success fs-5" style="min-width:30px;">#${idx + 1}</div>
              <div style="width: 48px; height: 48px; flex-shrink: 0; border-radius: 4px; border: 1px solid var(--rcw-border); overflow:hidden; background:rgba(255,255,255,0.03); display:flex; align-items:center; justify-content:center;">
                ${
                  img
                    ? `<img src="${esc(img)}" onerror="this.onerror=null; this.src='${window.RCW_BASE_URL}/dummy.jpg';" style="width: 100%; height: 100%; object-fit: cover;">`
                    : `<i class="fa-solid ${isC ? "fa-roller-coaster" : "fa-fort-awesome"} opacity-25"></i>`
                }
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-end mb-1 gap-2">
                  <div class="min-w-0">
                    <h6 class="fw-bold text-white mb-0 text-truncate">${esc(title)}</h6>
                    <small class="text-muted text-truncate d-block">${esc(sub)}</small>
                  </div>
                  <div class="fw-bold text-success fs-6 fs-md-5 flex-shrink-0 text-end" style="min-width: 60px;">${count} <span class="d-block d-sm-inline text-muted fw-normal" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; font-size: 0.7em;">${isC ? (count === 1 ? "vez montada" : "veces montada") : count === 1 ? "visita" : "visitas"}</span></div>
                </div>
                <div class="progress rounded-pill bg-dark mt-2" style="height: 6px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: ${pct}%"></div>
                </div>
              </div>
            </div>
          </div>
        `;
      });
      html += "</div>";
      container.innerHTML = html;
    }

    sType.addEventListener("change", fetchRanking);

    pBtns.forEach((b) => {
      b.addEventListener("click", (e) => {
        pBtns.forEach((btn) => {
          btn.classList.remove("btn-light", "text-success", "active");
          btn.classList.add("btn-outline-light", "border-opacity-50");
        });
        e.target.classList.remove("btn-outline-light", "border-opacity-50");
        e.target.classList.add("btn-light", "text-success", "active");
        currentPeriod = e.target.dataset.period;
        baseDate = new Date();
        updateLabel();
        if (currentPeriod !== "custom") fetchRanking();
      });
    });

    function handleArrowClick(dir) {
      if (currentPeriod === "all" || currentPeriod === "custom") {
        currentPeriod = "year";
        baseDate = new Date();
        document.querySelectorAll(".rank-period-btn").forEach((btn) => {
          btn.classList.remove("btn-light", "text-success", "active");
          btn.classList.add("btn-outline-light", "border-opacity-50");
          if (btn.dataset.period === "year") {
            btn.classList.remove("btn-outline-light", "border-opacity-50");
            btn.classList.add("btn-light", "text-success", "active");
          }
        });
      }
      if (currentPeriod === "year")
        baseDate.setFullYear(baseDate.getFullYear() + dir);
      else if (currentPeriod === "month") {
        baseDate.setDate(1);
        baseDate.setMonth(baseDate.getMonth() + dir);
      } else if (currentPeriod === "week")
        baseDate.setDate(baseDate.getDate() + dir * 7);
      updateLabel();
      fetchRanking();
    }

    if (prevBtn) prevBtn.addEventListener("click", () => handleArrowClick(-1));
    if (nextBtn) nextBtn.addEventListener("click", () => handleArrowClick(1));

    // Cambia visualmente el botón activo a "personalizado" y actualiza el estado
    function switchToCustom() {
      currentPeriod = "custom";
      pBtns.forEach((btn) => {
        btn.classList.remove("btn-light", "text-success", "active");
        btn.classList.add("btn-outline-light", "border-opacity-50");
        if (btn.dataset.period === "custom") {
          btn.classList.remove("btn-outline-light", "border-opacity-50");
          btn.classList.add("btn-light", "text-success", "active");
        }
      });
      updateLabel();
    }

    // Llamado desde initFlatpickr cuando el usuario cambia una fecha de estadísticas
    window.rankingOnDateChange = function (which, dateStr) {
      if (which === "start") customStart = dateStr;
      else customEnd = dateStr;
      if (currentPeriod !== "custom") switchToCustom();
      if (customStart && customEnd) fetchRanking();
    };

    updateLabel();
    fetchRanking();
  };
})();
