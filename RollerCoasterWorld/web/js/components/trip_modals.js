/* trip_modals.js — Global modals logic for trips */
(function () {
  const B = window.BASE_URL || window.RCW_BASE_URL || "";
  const API = B + "/api/php/trips.php";
  const SUPABASE_URL =
    "https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/";

  const resolveAvatar = (img) => {
    if (!img) return B + "/web/img/avatars/default_avatar.svg";
    if (img.startsWith("http")) return img;
    if (img.startsWith("/")) return B + img;
    return SUPABASE_URL + img;
  };

  let modals = {};

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
  const norm = (s) =>
    s
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();

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

  function toast(msg, type = "success") {
    const icons = { success: "✅", error: "❌", info: "ℹ️", warning: "⚠️" };
    const ti = document.getElementById("toast-icon");
    if (ti) ti.textContent = icons[type] || "";
    const tt = document.getElementById("toast-title");
    if (tt)
      tt.textContent =
        type === "error" ? "Error" : type === "warning" ? "Aviso" : "OK";
    const tb = document.getElementById("toast-body");
    if (tb) tb.textContent = msg;
    const rcw = document.getElementById("rcw-toast");
    if (rcw) bootstrap.Toast.getOrCreateInstance(rcw).show();
  }
  window.rcwToast = toast;

  function confirmModal(
    msg,
    onConfirm,
    title = "Confirmar",
    color = "#dc3545",
  ) {
    const mgcm = document.getElementById("gcm-message");
    if (!mgcm) return;
    mgcm.textContent = msg;
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

  // Reload callbacks
  window.rcw_reload_trips_callbacks = window.rcw_reload_trips_callbacks || [];
  function callReloads() {
    if (window.loadTrips) window.loadTrips();
    if (window.loadRanking) window.loadRanking();
    if (window.refreshAll) window.refreshAll();
  }

  const getFlagEmoji = (countryName) => {
    if (!countryName) return "🌍";
    const map = {
      // Europa
      España: "🇪🇸",
      Spain: "🇪🇸",
      Francia: "🇫🇷",
      France: "🇫🇷",
      Alemania: "🇩🇪",
      Germany: "🇩🇪",
      Italia: "🇮🇹",
      Italy: "🇮🇹",
      "Reino Unido": "🇬🇧",
      "United Kingdom": "🇬🇧",
      Suecia: "🇸🇪",
      Sweden: "🇸🇪",
      Dinamarca: "🇩🇰",
      Denmark: "🇩🇰",
      Noruega: "🇳🇴",
      Norway: "🇳🇴",
      Finlandia: "🇫🇮",
      Finland: "🇫🇮",
      "Países Bajos": "🇳🇱",
      Netherlands: "🇳🇱",
      Bélgica: "🇧🇪",
      Belgium: "🇧🇪",
      Polonia: "🇵🇱",
      Poland: "🇵🇱",
      Suiza: "🇨🇭",
      Switzerland: "🇨🇭",
      Austria: "🇦🇹",
      Portugal: "🇵🇹",
      Irlanda: "🇮🇪",
      Ireland: "🇮🇪",
      Grecia: "🇬🇷",
      Greece: "🇬🇷",
      Rusia: "🇷🇺",
      Russia: "🇷🇺",
      Turquía: "🇹🇷",
      Turkey: "🇹🇷",
      "República Checa": "🇨🇿",
      "Czech Republic": "🇨🇿",
      Hungría: "🇭🇺",
      Hungary: "🇭🇺",
      // América
      "Estados Unidos": "🇺🇸",
      "United States": "🇺🇸",
      Canadá: "🇨🇦",
      Canada: "🇨🇦",
      México: "🇲🇽",
      Mexico: "🇲🇽",
      Brasil: "🇧🇷",
      Brazil: "🇧🇷",
      Argentina: "🇦🇷",
      Chile: "🇨🇱",
      Colombia: "🇨🇴",
      Perú: "🇵🇪",
      Peru: "🇵🇪",
      // Asia y Oceanía
      Japón: "🇯🇵",
      Japan: "🇯🇵",
      China: "🇨🇳",
      "Corea del Sur": "🇰🇷",
      "South Korea": "🇰🇷",
      Australia: "🇦🇺",
      India: "🇮🇳",
      Tailandia: "🇹🇭",
      Thailand: "🇹🇭",
      Malasia: "🇲🇾",
      Malaysia: "🇲🇾",
      Singapur: "🇸🇬",
      Singapore: "🇸🇬",
      Vietnam: "🇻🇳",
      Taiwán: "🇹🇼",
      Taiwan: "🇹🇼",
      "Hong Kong": "🇭🇰",
      Indonesia: "🇮🇩",
      // Medio Oriente y África
      "Emiratos Árabes Unidos": "🇦🇪",
      UAE: "🇦🇪",
      "Arabia Saudita": "🇸🇦",
      "Saudi Arabia": "🇸🇦",
      Egipto: "🇪🇬",
      Egypt: "🇪🇬",
      Marruecos: "🇲🇦",
      Morocco: "🇲🇦",
      Sudáfrica: "🇿🇦",
      "South Africa": "🇿🇦",
    };
    return map[countryName] || "🌍";
  };

  const normalizeCountry = (name) => {
    if (!name) return "";
    const n = name.trim().toLowerCase();
    const map = {
      // España
      spain: "España",
      españa: "España",
      // Francia
      france: "Francia",
      francia: "Francia",
      // Alemania
      germany: "Alemania",
      alemania: "Alemania",
      // Italia
      italy: "Italia",
      italia: "Italia",
      // Reino Unido
      uk: "Reino Unido",
      "united kingdom": "Reino Unido",
      "reino unido": "Reino Unido",
      // Suecia
      sweden: "Suecia",
      suecia: "Suecia",
      sweeden: "Suecia",
      // Dinamarca
      denmark: "Dinamarca",
      dinamarca: "Dinamarca",
      // Noruega
      norway: "Noruega",
      noruega: "Noruega",
      // Finlandia
      finland: "Finlandia",
      finlandia: "Finlandia",
      // Países Bajos
      netherlands: "Países Bajos",
      holland: "Países Bajos",
      "países bajos": "Países Bajos",
      "paises bajos": "Países Bajos",
      // Bélgica
      belgium: "Bélgica",
      bélgica: "Bélgica",
      belgica: "Bélgica",
      // Polonia
      poland: "Polonia",
      polonia: "Polonia",
      // Suiza
      switzerland: "Suiza",
      suiza: "Suiza",
      // Austria
      austria: "Austria",
      // Portugal
      portugal: "Portugal",
      // Irlanda
      ireland: "Irlanda",
      irlanda: "Irlanda",
      // Grecia
      greece: "Grecia",
      grecia: "Grecia",
      // Rusia
      russia: "Rusia",
      rusia: "Rusia",
      // Turquía
      turkey: "Turquía",
      turquía: "Turquía",
      turquia: "Turquía",
      // República Checa
      "czech republic": "República Checa",
      "república checa": "República Checa",
      "republica checa": "República Checa",
      // Hungría
      hungary: "Hungría",
      hungría: "Hungría",
      hungria: "Hungría",
      // Estados Unidos
      usa: "Estados Unidos",
      "united states": "Estados Unidos",
      "estados unidos": "Estados Unidos",
      // Canadá
      canada: "Canadá",
      canadá: "Canadá",
      // México
      mexico: "México",
      méxico: "México",
      // Brasil
      brazil: "Brasil",
      brasil: "Brasil",
      // Argentina
      argentina: "Argentina",
      // Chile
      chile: "Chile",
      // Colombia
      colombia: "Colombia",
      // Perú
      peru: "Perú",
      perú: "Perú",
      // Japón
      japan: "Japón",
      japón: "Japón",
      japon: "Japón",
      // China
      china: "China",
      // Corea del Sur
      "south korea": "Corea del Sur",
      "corea del sur": "Corea del Sur",
      // Australia
      australia: "Australia",
      // India
      india: "India",
      // Tailandia
      thailand: "Tailandia",
      tailandia: "Tailandia",
      // Malasia
      malaysia: "Malasia",
      malasia: "Malasia",
      // Singapur
      singapore: "Singapur",
      singapur: "Singapur",
      // Vietnam
      vietnam: "Vietnam",
      // Taiwán
      taiwan: "Taiwán",
      taiwán: "Taiwán",
      // Hong Kong
      "hong kong": "Hong Kong",
      // Indonesia
      indonesia: "Indonesia",
      // Emiratos
      uae: "Emiratos Árabes Unidos",
      "emiratos árabes unidos": "Emiratos Árabes Unidos",
      "emiratos arabes unidos": "Emiratos Árabes Unidos",
      // Arabia Saudita
      "saudi arabia": "Arabia Saudita",
      "arabia saudita": "Arabia Saudita",
      // Egipto
      egypt: "Egipto",
      egipto: "Egipto",
      // Marruecos
      morocco: "Marruecos",
      marruecos: "Marruecos",
      // Sudáfrica
      "south africa": "Sudáfrica",
      sudáfrica: "Sudáfrica",
      sudafrica: "Sudáfrica",
    };
    return map[n] || name.trim().charAt(0).toUpperCase() + name.trim().slice(1);
  };

  const getTripCountdown = (start, end) => {
    const now = new Date();
    const s = new Date(start);
    const e = new Date(end);
    s.setHours(0, 0, 0, 0);
    e.setHours(23, 59, 59, 999);

    if (now > e)
      return {
        text: "Completado",
        class: "bg-secondary",
        customClass: "trip-countdown-done",
      };
    if (now >= s && now <= e)
      return {
        text: "En curso",
        class: "bg-success",
        customClass: "trip-countdown-active",
      };

    const diffMs = s - now;
    const diffDays = Math.floor(diffMs / 864e5);

    if (diffMs < 1728e5) {
      // < 48h
      const h = Math.floor(diffMs / 36e5);
      const m = Math.floor((diffMs % 36e5) / 6e4);
      return {
        text: `Faltan ${h}h ${m}min`,
        class: "bg-warning text-dark",
        customClass: "trip-countdown-urgent",
        urgent: true,
      };
    }

    return {
      text: `Faltan ${diffDays} días`,
      class: "bg-warning text-dark",
      customClass: "",
    };
  };
  window.openDay = async function (ds, tripId = null) {
    const body = document.getElementById("day-modal-body");
    body.innerHTML =
      '<div class="text-center py-5"><div class="spinner-border text-success" style="width: 3rem; height: 3rem;"></div></div>';

    // El usuario pide que el modal anterior siempre desaparezca
    try {
      gm("trip-detail-modal").hide();
    } catch (e) {}
    try {
      gm("calendar-modal").hide();
    } catch (e) {}

    gm("day-detail-modal").show();
    try {
      let q = "day_detail&date=" + ds;
      if (tripId) q += "&trip_id=" + tripId;
      const j = await api(q);
      const d = j.data;
      const cbp = d.coasters_by_park || {};
      const nbp = d.notes_by_park || {};

      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const visitDate = new Date(ds + "T00:00:00");
      const isFuture = visitDate > today;

      const allParks = [
        ...(d.trip_parks || []).map((p) => ({ ...p, _type: "trip" })),
        ...(d.daily_visits || []).map((p) => ({ ...p, _type: "visit" })),
      ];

      // Contador de veces montado hoy por coaster_id
      const riddenCountMap = {};
      (d.rides || []).forEach((r) => {
        const id = +r.coaster_id;
        riddenCountMap[id] = (riddenCountMap[id] || 0) + 1;
      });

      // Configurar Hero Banner
      let heroMediaHtml = "";
      let heroTitle = dw(ds);
      let heroSubtitle = "";

      if (allParks.length >= 2) {
        // Dual diagonal image for multi-park days
        const p1 = allParks[0];
        const p2 = allParks[1];
        const img1 = esc(p1.imagen_url || "");
        const img2 = esc(p2.imagen_url || "");
        heroMediaHtml = `
          ${img1 ? `<img src="${img1}" class="day-detail-hero-dual-1" onerror="this.style.display='none'">` : `<div class="day-detail-hero-dual-1" style="background:linear-gradient(135deg,#0f2d1f,#1a3a2a);"></div>`}
          ${img2 ? `<img src="${img2}" class="day-detail-hero-dual-2" onerror="this.style.display='none'">` : `<div class="day-detail-hero-dual-2" style="background:linear-gradient(135deg,#1a2040,#0d1530);"></div>`}
          <div class="day-detail-hero-dual-sep"></div>`;
        heroTitle = dw(ds);
        heroSubtitle = allParks.map((p) => esc(p.park_name)).join(" · ");
      } else if (allParks.length === 1) {
        const heroImage = allParks[0].imagen_url || "";
        heroMediaHtml = heroImage
          ? `<img src="${esc(heroImage)}" class="day-detail-hero-img" onerror="this.onerror=null; this.src='${window.RCW_BASE_URL}/dummy.jpg';">`
          : "";
        heroTitle = allParks[0].park_name;
        heroSubtitle =
          dw(ds) +
          (allParks[0].park_location ? " · " + allParks[0].park_location : "");
      }

      let h = `<div class="day-detail-hero" style="background: #111;">
            ${heroMediaHtml}
          <div class="day-detail-hero-overlay">
              <button type="button" class="btn-close btn-close-white position-absolute" data-bs-dismiss="modal" style="top: 15px; right: 15px;"></button>
              <h2 class="text-white fw-bold mb-1" style="font-family: var(--rcw-font-title); font-size: 1.5rem; letter-spacing:-0.02em;">${esc(heroTitle)}</h2>
              <div class="text-white-50 small fw-semibold" style="letter-spacing:0.05em; text-transform:uppercase;">${heroSubtitle}</div>
          </div>
      </div>`;

      // Barra técnica compacta justo debajo del Hero
      if (allParks.length > 0) {
        if (allParks.length === 1) {
          const p1 = allParks[0];
          h += `<div class="day-tech-bar">`;
          const p1CoasterCount =
            p1.operating_coasters > 0
              ? p1.operating_coasters
              : (cbp[+p1.park_id] || []).length;
          if (p1CoasterCount > 0) {
            h += `<div class="day-tech-item"><i class="fa-solid fa-roller-coaster text-success"></i><span><strong>${p1CoasterCount}</strong> ${p1CoasterCount === 1 ? "coaster operativa" : "coasters operativas"}</span></div>`;
            h += `<span class="opacity-25">·</span>`;
          }
          if (p1.opening_year) {
            h += `<div class="day-tech-item"><i class="fa-solid fa-calendar-check text-info"></i><span>Est. <strong>${p1.opening_year}</strong></span></div>`;
            h += `<span class="opacity-25">·</span>`;
          }
          if (p1.stars > 0) {
            h += `<div class="day-tech-item"><i class="fa-solid fa-star text-warning"></i><span><strong>${parseFloat(p1.stars).toFixed(1)}</strong> / 5.0</span></div>`;
          }
          h += `</div>`;
        } else {
          // Multi-park: one compact row per park
          h += `<div class="day-tech-bar day-tech-bar-multi">`;
          allParks.forEach((p, i) => {
            const hasAny =
              p.operating_coasters > 0 ||
              (cbp[+p.park_id] || []).length > 0 ||
              p.opening_year ||
              p.stars > 0;
            if (!hasAny) return;
            if (i > 0) h += `<div class="day-tech-divider"></div>`;
            h += `<div class="day-tech-park-block">
              <span class="day-tech-park-name">${esc(p.park_name)}</span>
              <div class="day-tech-park-stats">`;
            const pCoasterCount =
              p.operating_coasters > 0
                ? p.operating_coasters
                : (cbp[+p.park_id] || []).length;
            if (pCoasterCount > 0) {
              h += `<div class="day-tech-item"><i class="fa-solid fa-roller-coaster text-success"></i><span><strong>${pCoasterCount}</strong> ${pCoasterCount === 1 ? "coaster" : "coasters"}</span></div>`;
            }
            if (p.opening_year) {
              h += `<div class="day-tech-item"><i class="fa-solid fa-calendar-check text-info"></i><span>Est. <strong>${p.opening_year}</strong></span></div>`;
            }
            if (p.stars > 0) {
              h += `<div class="day-tech-item"><i class="fa-solid fa-star text-warning"></i><span><strong>${parseFloat(p.stars).toFixed(1)}</strong></span></div>`;
            }
            h += `</div></div>`;
          });
          h += `</div>`;
        }
      }

      h += `<div class="container-fluid py-3 py-lg-4 overflow-x-hidden">
              <div class="row g-4 g-lg-5">
                <div class="col-12 col-lg-6 day-modal-left-col">`;

      if (allParks.length) {
        allParks.forEach((p) => {
          const pid = +p.park_id;
          const coasters = cbp[pid] || [];
          const isTrip = p._type === "trip";

          h += `<div class="d-flex align-items-center justify-content-between mb-3 mt-2">
                  <h5 class="fw-bold text-success mb-0" style="font-size:1.1rem; font-family:var(--rcw-font-title)">${esc(p.park_name)}</h5>
                  ${
                    d.can_edit
                      ? `
                  <button class="btn btn-link text-danger p-0" style="text-decoration:none; opacity:0.6;" 
                    onclick="${isTrip ? `removeTripPark(${p.id},'${ds}')` : `removeVisit(${p.id},'${ds}')`}" 
                    title="Eliminar parque y sus registros de hoy">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                  `
                      : ""
                  }
                </div>`;

          if (!isFuture && d.can_edit) {
            if (coasters.length) {
              h += `<div class="day-coaster-section">
                <div class="px-0 pt-2 pb-2 text-muted fw-bold small text-uppercase" style="letter-spacing:0.1em; font-size:0.65rem;"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Log</div>
                <div class="day-coaster-list">`;
              coasters.forEach((c) => {
                const count = riddenCountMap[+c.id] || 0;
                h += `<div class="day-coaster-row rounded-3" id="cr-${c.id}">
                  <div class="day-coaster-img-container shadow-sm">
                    ${c.imagen_url ? `<img src="${esc(c.imagen_url)}" onerror="this.onerror=null; this.src='${window.RCW_BASE_URL}/dummy.jpg';" class="day-coaster-img">` : ""}
                  </div>
                  <div class="flex-grow-1 min-w-0 py-1">
                    <div class="day-coaster-name">${esc(c.coaster_name)}</div>
                  </div>
                  <div class="day-coaster-row-right">
                    ${count > 0 ? `<span class="pro-timeline-title">${count}</span>` : ""}
                    <input type="time" class="form-control form-control-sm rounded-3 day-coaster-time" id="ct-time-${c.id}" style="width:95px; background:rgba(255,255,255,0.06)!important; border:1px solid rgba(255,255,255,0.15)!important; color:var(--rcw-text-primary)!important; font-weight:600; font-size:0.85rem;" value="${new Date().toTimeString().slice(0, 5)}">
                    <button class="btn btn-success btn-sm rounded-3 fw-bold day-coaster-add-btn px-3"
                      onclick="quickLogRide(${c.id},'${escJS(c.coaster_name)}',${pid},'${ds}',${isTrip ? p.trip_id : "null"},'ct-time-${c.id}','cr-${c.id}')">
                      <i class="fa-solid fa-plus"></i>
                    </button>
                  </div>
                </div>`;
              });
              h += "</div></div>";
            }

            // Notas del día
            const notes = nbp[pid] || [];
            h += `<div class="day-notes-section mt-3 mb-4">
              <div class="px-0 pt-2 pb-2 text-muted fw-bold small text-uppercase" style="letter-spacing:0.1em; font-size:0.65rem;"><i class="fa-solid fa-note-sticky text-info me-2"></i>Notas del día</div>
              <div class="day-notes-list d-flex flex-column gap-2" id="notes-list-${pid}">`;

            notes.forEach((n) => {
              h += `<div class="day-note-item d-flex align-items-start justify-content-between py-1">
                <div class="flex-grow-1 text-light fw-semibold" style="font-size: 0.85rem; line-height: 1.4;">
                  <span class="text-info me-2 fs-5" style="vertical-align: middle;">•</span>${esc(n.note_text)}
                </div>
                <button class="btn btn-link text-danger p-0 ms-2 opacity-50" style="margin-top: 2px; text-decoration: none;" onclick="deleteDailyNote(${n.id},'${ds}')" title="Eliminar nota"><i class="fa-solid fa-xmark"></i></button>
              </div>`;
            });

            h += `</div>
              <div class="mt-2 d-flex gap-2">
                <input type="text" class="form-control form-control-sm rounded-3" id="new-note-input-${pid}" placeholder="Añadir nota..." style="background:rgba(255,255,255,0.06)!important; border:1px solid rgba(255,255,255,0.15)!important; color:var(--rcw-text-primary)!important;">
                <button class="btn btn-info btn-sm rounded-3 fw-bold px-3" onclick="addDailyNote(${pid},'${ds}','new-note-input-${pid}')"><i class="fa-solid fa-plus"></i></button>
              </div>
            </div>`;
          }
        });
      } else {
        h +=
          '<div class="text-center py-5 text-muted"><i class="fa-solid fa-bed d-block mb-3" style="font-size:2.5rem;opacity:.3"></i>No hay parques asignados a este día</div>';
      }

      if (d.can_edit) {
        h += `<div class="mt-4"><button class="btn btn-outline-secondary w-100 py-2 fw-bold" style="border-radius:6px; font-size:0.85rem; letter-spacing:0.04em;" onclick="openAddVisit('${ds}')"><i class="fa-solid fa-location-dot me-2 text-success"></i>Añadir otro parque visitado hoy</button></div>`;
      }
      h += `</div>`; // End col-lg-6

      h += `<div class="col-12 col-lg-6">`;

      if (visitDate > today) {
        h += `<div class="text-center py-5 text-muted mt-4">
                <i class="fa-solid fa-clock-rotate-left mb-3 d-block" style="font-size:3rem;opacity:.2"></i>
                <h5 class="fw-bold text-light">Aún falta para esto</h5>
                <p class="small opacity-75">Tendrás que esperar todavía para disfrutar de este parque 😉</p>
              </div>`;
      } else {
        // Leyenda para la agenda
        h += `<div class="timeline-legend rounded-2">
                <div class="legend-item"><span class="legend-bullet" style="background:var(--rcw-green-neon)"></span>Visitado</div>
                <div class="legend-item"><span class="legend-bullet" style="background:#f59e0b; box-shadow:0 0 5px #f59e0b"></span>First Time</div>
              </div>`;

        if (d.rides?.length) {
          h += `<h6 class="fw-bold text-warning mb-2 d-flex align-items-center gap-2" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:.5rem; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em;">
            <i class="fa-solid fa-list-check"></i>
            Agenda de hoy (${d.rides.length})</h6>
            <div class="ride-timeline custom-scrollbar" style="max-height: 450px; overflow-y: auto; padding-right: 8px; overscroll-behavior: contain;">`;

          let lastTime = null;
          let lastParkId = null;
          d.rides.forEach((r) => {
            let parkSeparatorStr = "";
            if (lastParkId !== r.park_id) {
              parkSeparatorStr = `<div class="text-center my-3 text-muted small d-flex align-items-center opacity-75"><hr class="flex-grow-1 border-secondary"><span class="text-uppercase fw-bold px-2" style="letter-spacing:1px;font-size:0.75rem">${esc(r.park_name)}</span><hr class="flex-grow-1 border-secondary"></div>`;
              lastParkId = r.park_id;
              lastTime = null;
            }

            let timeDiffStr = "";
            const tDate = r.ridden_at ? new Date(r.ridden_at) : null;
            const t = tDate
              ? tDate.toLocaleTimeString("es-ES", {
                  hour: "2-digit",
                  minute: "2-digit",
                })
              : "";

            if (tDate && lastTime) {
              const diffMins = Math.round((tDate - lastTime) / 60000);
              if (diffMins > 0) {
                let diffText = "";
                if (diffMins >= 60) {
                  const h = Math.floor(diffMins / 60);
                  const m = diffMins % 60;
                  diffText = m > 0 ? `+${h}h ${m}min` : `+${h}h`;
                } else {
                  diffText = `+${diffMins} min`;
                }
                timeDiffStr = `<div class="text-muted small my-1 ms-4" style="border-left: 2px dashed var(--rcw-border); padding-left: 1rem;"><i class="fa-solid fa-clock me-1"></i>${diffText}</div>`;
              }
            }
            if (tDate) lastTime = tDate;
            const ft = r.first_time === true || r.first_time === "true";
            h +=
              parkSeparatorStr +
              timeDiffStr +
              `<div class="ride-item ${ft ? "ride-item-new" : ""}">
              <div class="ride-item-info">
                <div class="ride-item-name">${esc(r.coaster_name)}${ft ? ' <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem">NEW</span>' : ""}</div>
                <div class="ride-item-meta">${esc(r.park_name)}${r.seat_row ? " · Fila " + r.seat_row : ""}${r.notes ? " · " + esc(r.notes) : ""}</div>
              </div>
              <span class="ride-item-time">${t}</span>
              <div class="ride-item-actions"><button onclick="deleteRide(${r.id},'${ds}')"><i class="fa-solid fa-trash"></i></button></div>
            </div>`;
          });
          h += "</div>";
        } else {
          h += `<div class="text-center py-5 text-muted"><i class="fa-regular fa-clock mb-3 d-block" style="font-size:2.5rem;opacity:.2"></i>Aún no has montado en nada</div>`;
        }
      }
      h += `</div></div></div>`;
      body.innerHTML = h;
      if (typeof window.initFlatpickr === "function") window.initFlatpickr();
    } catch (e) {
      body.innerHTML =
        '<div class="p-4 text-danger text-center">Error cargando datos del día</div>';
    }
  };

  window.quickLogRide = async (
    cid,
    cname,
    pid,
    ds,
    tid,
    timeInputId,
    rowId,
  ) => {
    const timeVal = document.getElementById(timeInputId)?.value || "";
    const ridden_at = timeVal ? `${ds}T${timeVal}:00` : null;
    const btn = document
      .getElementById(rowId)
      ?.querySelector(".day-coaster-add-btn");
    if (btn) {
      btn.disabled = true;
      btn.innerHTML =
        '<div class="spinner-border spinner-border-sm" style="width:14px;height:14px"></div>';
    }
    const body = { park_id: pid, coaster_id: cid, visit_date: ds };
    if (tid && tid !== "null") body.trip_id = +tid;
    if (ridden_at) body.ridden_at = ridden_at;
    const j = await api("log_ride", body);
    if (j.success) {
      callReloads();
      toast("¡Coaster registrada! " + cname);
      setTimeout(() => openDay(ds), 300);
    } else {
      toast(j.error || "Error al registrar", "error");
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-plus"></i>';
      }
    }
  };

  window.openAddVisit = (ds) => {
    document.getElementById("av-date").value = ds;
    document.getElementById("av-park-search").value = "";
    document.getElementById("av-park-id").value = "";
    document.getElementById("av-notes").value = "";
    gm("day-detail-modal").hide();
    setTimeout(() => gm("add-visit-modal").show(), 300);
  };

  window.submitVisitGlobal = async function () {
    const pid = document.getElementById("av-park-id").value,
      d = document.getElementById("av-date").value;
    if (!pid) {
      toast("Selecciona un parque", "warning");
      return;
    }
    const j = await api("add_daily_visit", {
      park_id: +pid,
      visit_date: d,
    });
    if (j.success) {
      const notesVal = document.getElementById("av-notes").value.trim();
      if (notesVal) {
        await api("add_daily_note", {
          park_id: +pid,
          visit_date: d,
          note_text: notesVal,
        });
      }
      gm("add-visit-modal").hide();
      callReloads();
      setTimeout(() => openDay(d), 300);
    } else toast(j.error || "Error", "error");
  };

  window.addDailyNote = async (pid, ds, inputId) => {
    const input = document.getElementById(inputId);
    const text = input?.value.trim();
    if (!text) return;
    const j = await api("add_daily_note", {
      park_id: pid,
      visit_date: ds,
      note_text: text,
    });
    if (j.success) {
      input.value = "";
      openDay(ds);
    } else toast(j.error || "Error", "error");
  };

  window.deleteDailyNote = (id, ds) => {
    confirmModal(
      "¿Estás seguro de que quieres eliminar esta nota de forma permanente?",
      async () => {
        const j = await api("delete_daily_note", { note_id: id });
        if (j.success) {
          openDay(ds);
          toast("Nota eliminada correctamente");
        } else toast(j.error || "Error", "error");
      },
      "Eliminar nota",
      "#dc3545",
    );
  };

  window.openLogRide = (pid, pn, ds, tid) => {
    document.getElementById("lr-park-id").value = pid;
    document.getElementById("lr-park-name").value = pn;
    document.getElementById("lr-date").value = ds;
    document.getElementById("lr-trip-id").value = tid || "";
    [
      "lr-coaster-search",
      "lr-coaster-id",
      "lr-seat",
      "lr-time",
      "lr-notes",
    ].forEach((id) => (document.getElementById(id).value = ""));
    document.getElementById("lr-error").classList.add("d-none");
    gm("day-detail-modal").hide();
    setTimeout(() => gm("log-ride-modal").show(), 300);
  };

  window.submitRideGlobal = async function () {
    const cid = document.getElementById("lr-coaster-id").value,
      pid = document.getElementById("lr-park-id").value,
      d = document.getElementById("lr-date").value,
      err = document.getElementById("lr-error");
    if (!cid) {
      err.textContent = "Selecciona una montaña rusa";
      err.classList.remove("d-none");
      return;
    }
    const b = {
      park_id: +pid,
      coaster_id: +cid,
      visit_date: d,
      notes: document.getElementById("lr-notes").value,
    };
    const tid = document.getElementById("lr-trip-id").value;
    if (tid) b.trip_id = +tid;
    const seat = document.getElementById("lr-seat").value;
    if (seat) b.seat_row = +seat;
    const tv = document.getElementById("lr-time").value;
    if (tv) b.ridden_at = `${d}T${tv}:00`;
    const j = await api("log_ride", b);
    if (j.success) {
      gm("log-ride-modal").hide();
      callReloads();
      if (j.data?.first_time)
        setTimeout(() => gm("new-credit-modal").show(), 200);
      setTimeout(() => openDay(d), 300);
    } else {
      err.textContent = j.error || "Error";
      err.classList.remove("d-none");
    }
  };

  window.deleteRide = (id, ds) => {
    confirmModal(
      "¿Eliminar este ride?",
      async () => {
        await api("delete_ride", { ride_id: id });
        callReloads();
        openDay(ds);
      },
      "Eliminar ride",
    );
  };

  window.removeVisit = (id, ds) => {
    confirmModal(
      "¿Eliminar esta visita y TODOS sus registros de hoy?",
      async () => {
        await api("remove_daily_visit", { visit_id: id });
        callReloads();
        if (ds) openDay(ds);
        else gm("day-detail-modal").hide();
      },
      "Eliminar visita",
    );
  };

  window.removeTripPark = (id, ds) => {
    confirmModal(
      "¿Quitar este parque del itinerario y borrar sus registros de hoy?",
      async () => {
        await api("remove_park_day", { id: id });
        callReloads();
        if (ds) openDay(ds);
        else gm("day-detail-modal").hide();
      },
      "Quitar parque",
    );
  };

  // ── TRIP MODAL (Page 1) ────────────
  window.openTrip = async (id) => {
    const body = document.getElementById("td-body");
    if (!body) return;
    body.innerHTML =
      '<div class="text-center py-5"><div class="spinner-border text-success" style="width: 3rem; height: 3rem;"></div></div>';
    gm("trip-detail-modal").show();

    try {
      const j = await api("detail&trip_id=" + id);
      const t = j.data;

      // Configurar botón eliminar (se muestra solo si puede editar)
      const delBtn = document.getElementById("td-delete-btn");
      if (delBtn) {
        if (t && t.can_edit) {
          delBtn.classList.remove("d-none");
          delBtn.onclick = () => {
            window._delId = id;
            gm("trip-detail-modal").hide();
            setTimeout(() => gm("delete-confirm-modal").show(), 300);
          };
        } else {
          delBtn.classList.add("d-none");
        }
      }

      // Calculate missing dates for the calendar
      const startDate = new Date(t.start_date);
      const endDate = new Date(t.end_date);
      const tripDaysCount = days(t.start_date, t.end_date);

      // Hero Image
      let heroImg = t.cover_image || "";
      if (
        !heroImg &&
        t.parks_by_day &&
        t.parks_by_day.length > 0 &&
        t.parks_by_day[0].imagen_url
      ) {
        heroImg = t.parks_by_day[0].imagen_url;
      }

      // Unique parks & countries (calculated early for hero)
      const uniqueParks = [];
      const seenParks = new Set();
      if (t.parks_by_day) {
        t.parks_by_day.forEach((p) => {
          if (!seenParks.has(p.park_id)) {
            seenParks.add(p.park_id);
            uniqueParks.push(p);
          }
        });
      }
      const totalParksCount = uniqueParks.length;
      const manualCountries = t.parks_visited
        ? t.parks_visited
            .split(",")
            .map(normalizeCountry)
            .filter((c) => c)
        : [];
      const countries = [
        ...new Set([
          ...(t.countries || []).map(normalizeCountry),
          ...manualCountries,
        ]),
      ];

      const countriesHtml =
        countries.length > 0
          ? `<div class="d-flex align-items-center gap-2 bg-dark bg-opacity-75 px-3 py-1 rounded-1 border border-secondary border-opacity-50 flex-wrap">
             ${countries.map((c) => `<span class="text-white fw-bold small">${getFlagEmoji(c)} ${esc(c)}</span>`).join('<span class="text-white-50">·</span>')}
           </div>`
          : "";

      const countdown = getTripCountdown(t.start_date, t.end_date);

      let h = `<div class="pro-trip-hero" style="background: #111;">
              ${heroImg ? `<img src="${esc(heroImg)}" class="pro-trip-hero-img" onerror="this.onerror=null; this.src='${window.RCW_BASE_URL}/dummy.jpg';">` : ""}
          <div class="pro-trip-hero-overlay"></div>
          <div class="pro-trip-hero-content d-flex justify-content-between align-items-end w-100 flex-wrap gap-3">
              <div style="flex-grow:1; max-width:800px;">
                  <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">
                    <h2 class="pro-trip-title mb-0">${esc(t.title)}</h2>
                    <span class="trip-countdown-pill ${countdown.customClass || ""}">
                      ${countdown.text}
                    </span>
                  </div>
                  <div class="pro-trip-desc pe-4 mb-3">${esc(t.description || "Sin descripción")}</div>
                  
                  <div class="d-flex align-items-center gap-3 flex-wrap">
                      <div class="d-flex align-items-center gap-2 bg-dark bg-opacity-75 px-3 py-1 rounded-1 border border-secondary border-opacity-50">
                          <i class="fa-solid fa-plane-departure text-success"></i>
                          <span class="text-white fw-bold small" style="letter-spacing:0.5px;">${fd(t.start_date)}</span>
                      </div>
                      <div class="text-white-50 fw-semibold small px-1">${tripDaysCount} DÍAS</div>
                      <div class="d-flex align-items-center gap-2 bg-dark bg-opacity-75 px-3 py-1 rounded-1 border border-secondary border-opacity-50">
                          <span class="text-white fw-bold small" style="letter-spacing:0.5px;">${fd(t.end_date)}</span>
                          <i class="fa-solid fa-plane-arrival text-warning"></i>
                      </div>
                      ${countriesHtml}
                  </div>
              </div>
              <div class="d-flex gap-2 flex-shrink-0 align-self-end">
                  ${
                    t.can_edit
                      ? `
                  <button class="btn-hero-action btn-hero-edit" onclick="openEditTrip(${t.id})"><i class="fa-solid fa-pen"></i><span>Editar</span></button>
                  <button class="btn-hero-action btn-hero-team" onclick="openCollabs(${t.id})"><i class="fa-solid fa-users"></i><span>Equipo</span></button>
                  `
                      : ""
                  }
              </div>
          </div>
      </div>`;

      h += `<div class="container-fluid py-4 px-4">`;

      // 1. STATS GRID
      const totalCoasters = t.park_coasters ? t.park_coasters.length : 0;
      h += `<div class="row g-2 mb-5">`;

      // Duración
      h += `<div class="col-6 col-md">
              <div class="pro-stat-card" style="--accent-color:#10b981;">
                <div class="pro-stat-header"><span class="pro-stat-label">Duración</span><i class="fa-solid fa-calendar-days pro-stat-icon"></i></div>
                <div class="pro-stat-value">${tripDaysCount}</div>
                <div class="pro-stat-footer">DÍAS TOTALES</div>
              </div>
            </div>`;

      // Parques
      h += `<div class="col-6 col-md">
              <div class="pro-stat-card" style="--accent-color:#3b82f6;">
                <div class="pro-stat-header"><span class="pro-stat-label">Parques</span><i class="fa-solid fa-map-location-dot pro-stat-icon"></i></div>
                <div class="pro-stat-value">${totalParksCount}</div>
                <div class="pro-stat-footer">${totalParksCount === 1 ? "PARQUE" : "PARQUES"}</div>
              </div>
            </div>`;

      // Coasters
      h += `<div class="col-6 col-md">
              <div class="pro-stat-card" style="--accent-color:#10b981;">
                <div class="pro-stat-header"><span class="pro-stat-label">Coasters</span><i class="fa-solid fa-roller-coaster pro-stat-icon"></i></div>
                <div class="pro-stat-value" style="color:#10b981;">${totalCoasters}</div>
                <div class="pro-stat-footer">A PROBAR</div>
              </div>
            </div>`;

      // Países
      h += `<div class="col-6 col-md">
              <div class="pro-stat-card" style="--accent-color:#f59e0b;">
                <div class="pro-stat-header"><span class="pro-stat-label">Países</span><i class="fa-solid fa-earth-europe pro-stat-icon"></i></div>
                <div class="pro-stat-value">${countries.length || 1}</div>
                <div class="pro-stat-footer">${countries.length === 1 ? "PAÍS" : "PAÍSES"}</div>
              </div>
            </div>`;

      // Equipo
      h += `<div class="col-6 col-md">
              <div class="pro-stat-card" style="--accent-color:#a78bfa;">
                <div class="pro-stat-header"><span class="pro-stat-label">Equipo</span><i class="fa-solid fa-users pro-stat-icon"></i></div>
                <div class="pro-stat-value">${(t.collaborators ? t.collaborators.length : 0) + 1}</div>
                <div class="pro-stat-footer d-flex align-items-center gap-1">`;

      // Dueño siempre primero
      const ownerImg = resolveAvatar(t.owner_image);
      h += `<img src="${ownerImg}" style="width:20px;height:20px;border-radius:50%;object-fit:cover;border:2px solid #a78bfa;" title="Creador: ${esc(t.owner_name)}">`;

      if (t.collaborators && t.collaborators.length > 0) {
        t.collaborators.slice(0, 3).forEach((c) => {
          const pImg = resolveAvatar(c.profile_image);
          h += `<img src="${pImg}" style="width:20px;height:20px;border-radius:50%;object-fit:cover;border:1px solid rgba(255,255,255,0.2);" title="${esc(c.username)}">`;
        });
        if (t.collaborators.length > 3)
          h += `<span class="text-muted small ms-1 fw-bold">+${t.collaborators.length - 3}</span>`;
      }
      h += `      </div>
              </div>
            </div>`;

      h += `</div>`; // End Stats Grid

      // 2. ITINERARIO TIMELINE
      h += `<div class="pro-section-title">Timeline del Itinerario</div>`;
      h += `<div class="pro-timeline-container mb-5">`;

      const parksByDate = {};
      if (t.parks_by_day?.length) {
        t.parks_by_day.forEach((p) => {
          if (!parksByDate[p.visit_date]) parksByDate[p.visit_date] = [];
          parksByDate[p.visit_date].push(p);
        });
      }

      let currDate = new Date(startDate);
      while (currDate <= endDate) {
        const dateStr = currDate.toISOString().split("T")[0];
        const parksToday = parksByDate[dateStr] || [];
        const isFirstDay = dateStr === startDate.toISOString().split("T")[0];
        const isLastDay = dateStr === endDate.toISOString().split("T")[0];

        if (parksToday.length > 0) {
          let mediaHtml = "";
          let titleHtml = "";

          if (parksToday.length >= 2) {
            const p1 = parksToday[0];
            const p2 = parksToday[1];
            const img1 = p1.imagen_url ? esc(p1.imagen_url) : "";
            const img2 = p2.imagen_url ? esc(p2.imagen_url) : "";

            mediaHtml = `<div class="pro-timeline-dual">
                           <img src="${img1}" class="pro-timeline-img-dual-1" onerror="this.src='${B}/web/img/placeholder_park.jpg'">
                           <img src="${img2}" class="pro-timeline-img-dual-2" onerror="this.src='${B}/web/img/placeholder_park.jpg'">
                           <div class="pro-timeline-diagonal-sep"></div>
                         </div>`;
            titleHtml = ""; // Hide title for 2+ parks
          } else {
            const primaryPark = parksToday[0];
            const imgUrl = esc(primaryPark.imagen_url || "");
            mediaHtml = imgUrl
              ? `<img src="${imgUrl}" class="pro-timeline-img" onerror="this.style.display='none'">`
              : `<div class="pro-timeline-img" style="background:linear-gradient(135deg,#0f2d1f,#1a3a2a);"></div>`;
            titleHtml = `<div class="pro-timeline-title text-truncate">${esc(primaryPark.park_name)}</div>`;
          }

          let flightBadge = "";
          if (isFirstDay)
            flightBadge = `<div class="position-absolute top-0 end-0 bg-success text-white px-2 py-1 z-3" style="font-size:0.65rem; font-weight:800; text-transform:uppercase; border-bottom-left-radius: 4px;"><i class="fa-solid fa-plane-departure me-1"></i>Ida</div>`;
          if (isLastDay)
            flightBadge = `<div class="position-absolute top-0 end-0 bg-warning text-dark px-2 py-1 z-3" style="font-size:0.65rem; font-weight:800; text-transform:uppercase; border-bottom-left-radius: 4px;"><i class="fa-solid fa-plane-arrival me-1"></i>Vuelta</div>`;

          h += `<div class="pro-timeline-card" onclick="openDay('${dateStr}', ${t.id})">
                  ${flightBadge}
                  ${mediaHtml}
                  <div class="pro-timeline-overlay">
                    <div class="pro-timeline-date">${new Date(dateStr).toLocaleDateString("es-ES", { weekday: "short", day: "numeric", month: "short" })}</div>
                    <div>
                      ${titleHtml}
                      ${parksToday.length > 1 ? `<div style="font-size:0.75rem; font-weight:700; color:#fff; text-shadow: 0 1px 3px rgba(0,0,0,0.5);">${parksToday.length} parques visitados</div>` : ""}
                    </div>
                  </div>
                </div>`;
        } else {
          // Empty day
          let emptyIconHtml =
            '<i class="fa-solid fa-person-walking-luggage" style="font-size:2rem;color:rgba(255,255,255,0.2);"></i>';
          let emptyText = "Día libre";
          let bgClass =
            "background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1);";

          if (isFirstDay) {
            emptyIconHtml =
              '<i class="fa-solid fa-plane-departure text-success" style="font-size:2rem;opacity:0.8;"></i>';
            emptyText = "Vuelo de Ida";
            bgClass =
              "background: rgba(16,185,129,0.05); border: 1px dashed rgba(16,185,129,0.3);";
          } else if (isLastDay) {
            emptyIconHtml =
              '<i class="fa-solid fa-plane-arrival text-warning" style="font-size:2rem;opacity:0.8;"></i>';
            emptyText = "Vuelo de Vuelta";
            bgClass =
              "background: rgba(245,158,11,0.05); border: 1px dashed rgba(245,158,11,0.3);";
          }

          h += `<div class="pro-timeline-card d-flex flex-column align-items-center justify-content-center gap-2" style="${bgClass}" onclick="openDay('${dateStr}', ${t.id})">
                  <div class="position-absolute" style="top:0.75rem;left:0.75rem;">
                    <div class="pro-timeline-date text-muted bg-transparent p-0">${new Date(dateStr).toLocaleDateString("es-ES", { weekday: "short", day: "numeric", month: "short" })}</div>
                  </div>
                  ${emptyIconHtml}
                  <div class="fw-bold text-white-50" style="font-size:0.85rem;">${emptyText}</div>
                </div>`;
        }
        currDate.setDate(currDate.getDate() + 1);
      }
      h += `</div>`; // End Timeline

      // 3. PARQUES Y COASTERS (2 columnas en pantallas grandes)
      h += `<div class="row g-4">`;

      // Columna Izquierda: Parques
      h += `<div class="col-12 col-lg-6">
              <div class="pro-section-title">Parques a Visitar</div>
              <div class="d-flex flex-column gap-2" style="max-height:350px; overflow-y:auto; padding-right:8px;">`;
      if (uniqueParks.length > 0) {
        uniqueParks.forEach((p) => {
          const pImg = p.imagen_url ? esc(p.imagen_url) : "";
          h += `<a href="${B}/web/views/public/parks/parks.php?id=${p.park_id}" class="pro-park-card" style="display:flex;align-items:center;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:8px;min-height:72px;padding:0 12px;gap:12px;text-decoration:none;margin-bottom:8px;transition:all 0.2s;">
                      ${
                        pImg
                          ? `<img src="${pImg}" style="width:56px;height:56px;border-radius:8px;object-fit:cover;flex-shrink:0;" onerror="this.style.display='none'">`
                          : `<div style="width:56px;height:56px;border-radius:8px;background:linear-gradient(135deg,#0a2518,#134d30);flex-shrink:0;"></div>`
                      }
                      <div style="flex:1;min-width:0;">
                          <div style="font-size:15px;font-weight:700;color:#fff;">${esc(p.park_name)}</div>
                      </div>
                      <i class="fa-solid fa-chevron-right" style="color:rgba(255,255,255,0.2);font-size:0.85rem;flex-shrink:0;"></i>
                    </a>`;
        });
      } else {
        h += `<div class="text-muted fst-italic">No hay parques asignados.</div>`;
      }
      h += `  </div>
            </div>`;

      // Columna Derecha: Coasters
      h += `<div class="col-12 col-lg-6">
              <div class="pro-section-title">Coasters Totales</div>`;
      if (t.park_coasters && t.park_coasters.length > 0) {
        h += `<div class="d-flex flex-column gap-1 pro-scroll-y" style="max-height:350px; overflow-y:auto; padding-right:8px;">`;

        const coastersByPark = {};
        t.park_coasters.forEach((c) => {
          if (!coastersByPark[c.park_id]) coastersByPark[c.park_id] = [];
          coastersByPark[c.park_id].push(c);
        });

        uniqueParks.forEach((p) => {
          const coasters = coastersByPark[p.park_id];
          if (coasters && coasters.length > 0) {
            h += `<div class="pro-section-group-header">
                          <div class="pro-section-group-title">${esc(p.park_name)}</div>
                          <div class="pro-section-group-line"></div>
                        </div>`;
            coasters.forEach((c) => {
              const cImg = c.imagen_url ? esc(c.imagen_url) : "";

              let coasterData = [];
              if (c.speed)
                coasterData.push(
                  `<span style="color:#10b981;">${c.speed} km/h</span>`,
                );
              if (c.height)
                coasterData.push(
                  `<span style="color:#3fb1ff;">${c.height} m</span>`,
                );
              if (c.inversions && c.inversions > 0)
                coasterData.push(
                  `<span style="color:#f59e0b;">${c.inversions} inv.</span>`,
                );
              const dataHtml =
                coasterData.length > 0
                  ? `<div class="pro-coaster-meta mt-1">${coasterData.join(' <span style="opacity:0.3;">|</span> ')}</div>`
                  : "";

              h += `<a href="${B}/web/views/public/coasters/coasters.php?id=${c.id}" class="pro-coaster-item" style="display:flex;align-items:center;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:6px;min-height:56px;padding:0 12px;gap:12px;text-decoration:none;margin-bottom:6px;transition:all 0.2s;">
                              <div style="width:44px;height:44px;flex-shrink:0;border-radius:6px;overflow:hidden;background:#1c2128;">
                                  ${
                                    cImg
                                      ? `<img src="${cImg}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">`
                                      : `<i class="fa-solid fa-roller-coaster text-muted opacity-25" style="line-height:44px;text-align:center;display:block;"></i>`
                                  }
                              </div>
                              <div style="flex:1;min-width:0;">
                                  <div style="font-size:14px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(c.coaster_name)}</div>
                                  ${dataHtml}
                              </div>
                              <i class="fa-solid fa-chevron-right" style="color:rgba(255,255,255,0.2);font-size:0.75rem;flex-shrink:0;"></i>
                            </a>`;
            });
          }
        });
        h += `</div>`;
      } else {
        h += `<div class="text-muted fst-italic">No hay coasters operativas listadas.</div>`;
      }
      h += `</div>`;

      h += `</div>`; // End row

      h += `</div>`; // End container

      body.innerHTML = h;
    } catch (e) {
      body.innerHTML =
        '<div class="p-4 text-danger text-center">Error cargando datos del viaje</div>';
    }
  };

  window.confirmDelGlobal = async function () {
    if (!window._delId) return;
    await api("delete", { trip_id: window._delId });
    gm("delete-confirm-modal").hide();
    window._delId = null;
    callReloads();
  };

  window.openCollabs = async (id) => {
    window._cTid = id;
    const body = document.getElementById("collabs-body");
    const inp = document.getElementById("collab-username");
    if (inp) inp.value = "";
    body.innerHTML =
      '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-info"></div></div>';
    gm("trip-detail-modal").hide();
    setTimeout(() => gm("collabs-modal").show(), 300);
    const j = await api("collaborators&trip_id=" + id);
    const d = j.data || [];
    body.innerHTML = d.length
      ? d
          .map(
            (
              c,
            ) => `<div class="d-flex align-items-center justify-content-between gap-2 mb-2 p-2 rounded-0 border border-secondary" style="background:var(--rcw-bg-card-alt)">
              <div class="d-flex align-items-center gap-2">
                <img src="${c.profile_image ? esc(c.profile_image) : B + "/web/img/avatars/default_avatar.svg"}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                <strong>${esc(c.username)}</strong>
                <span class="badge ${c.status === "accepted" ? "bg-success" : "bg-warning text-dark"} ms-1" style="border-radius:4px;">${c.status}</span>
              </div>
              <button class="btn btn-sm text-danger border-0 p-1" onclick="rmCollab(${id},${c.user_id})" title="Eliminar"><i class="fa-solid fa-xmark fa-lg"></i></button>
            </div>`,
          )
          .join("")
      : '<p class="text-muted small text-center">Sin colaboradores</p>';
  };

  window.submitCollabGlobal = async function () {
    const u = document.getElementById("collab-username").value.trim();
    if (!u || !window._cTid) return;
    const j = await api("invite_collaborator", {
      trip_id: window._cTid,
      username: u,
    });
    if (j.success) {
      document.getElementById("collab-username").value = "";
      openCollabs(window._cTid);
    } else toast(j.error || "Error al invitar", "error");
  };

  window.rmCollab = async (tid, uid) => {
    await api("remove_collaborator", { trip_id: tid, user_id: uid });
    openCollabs(tid);
  };

  // Add listeners
  document.addEventListener("DOMContentLoaded", () => {
    const sbv = document.getElementById("av-submit-btn");
    if (sbv) sbv.addEventListener("click", window.submitVisitGlobal);
    const sbr = document.getElementById("lr-submit-btn");
    if (sbr) sbr.addEventListener("click", window.submitRideGlobal);
    const sbc = document.getElementById("confirm-delete-btn");
    if (sbc) sbc.addEventListener("click", window.confirmDelGlobal);
    const sbi = document.getElementById("collab-invite-btn");
    if (sbi) sbi.addEventListener("click", window.submitCollabGlobal);

    // Autocomplete for add visit and log ride
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
  });

  function setupAC(iid, did, hid, action, pidFn) {
    const inp = document.getElementById(iid),
      dr = document.getElementById(did);
    if (!inp || !dr) return;
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
    } catch (e) {}

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
      if (!dr.contains(e.target) && e.target !== inp) dr.style.display = "none";
    });
  }

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
      const currentVal = hiddenInput.value.trim();
      tags = currentVal
        ? currentVal
            .split(",")
            .map((t) => t.trim())
            .filter((t) => t)
        : [];
      container.querySelectorAll(".badge").forEach((e) => e.remove());
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
            (c) =>
              `<li><a class="dropdown-item" href="#" onclick="event.preventDefault(); window.addCountryTag('${escJS(c)}')">${c}</a></li>`,
          )
          .join("");
        dropdown.style.display = "block";
      } else {
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

    document.addEventListener("click", (e) => {
      if (!container.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = "none";
      }
    });

    container.addEventListener("click", () => visualInput.focus());
  });

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
      if (!uniqueMap.has(key)) uniqueMap.set(key, c);
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
          parks_visited: countries,
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
        callReloads();
        toast(
          window.currentEditTripId
            ? "Viaje actualizado"
            : "Viaje creado correctamente",
        );
        if (window.currentEditTripId && window.openTrip)
          window.openTrip(window.currentEditTripId);
      } else {
        err.textContent = j.error || "Error";
        err.classList.remove("d-none");
      }
    } catch (e) {
      err.textContent = "Error de red";
      err.classList.remove("d-none");
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("ct-submit-btn");
    if (btn) btn.addEventListener("click", submitCreate);
  });
})();
