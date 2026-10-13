/* ============================================================
   spotlight-card.js — Plantilla de las fichas "AL AZAR"
   Cargar ANTES de spotlight.js (ambos con defer, el orden se respeta).

   Uso desde spotlight.js:
     RCWSpot.skeleton(grid);                       // al pulsar "Actualizar"
     RCWSpot.render(grid, [coaster, park], { animate: true });
   ============================================================ */
(function () {
  "use strict";

  const esc = (s) =>
    String(s ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c],
    );

  /* ------------------------------------------------------------
     MAPA DE CAMPOS  ->  ajustado a los campos reales de la API
     (/api/php/index.php?action=random)

     Coaster devuelve:
       id, coaster_name, imagen_url, manufacter, model,
       opening_year, height, speed, status, inversions,
       park_name, country, park_id

     Parque devuelve:
       id, park_name, country, location, imagen_url,
       num_coasters, opening_year, stars, precio_entrada,
       operating_coasters
     ------------------------------------------------------------ */
  function adapt(item, type) {
    if (type === "park") {
      const location = [item.location, item.country].filter(Boolean).join(", ");
      return {
        type,
        href: window.BASE_URL + "/web/views/public/parks/parks.php?id=" + item.id,
        name: item.park_name,
        img: item.imagen_url,
        places: [{ icon: "fa-location-dot", text: location }],
        status: null,
        ridden: false,
        facts: [
          { label: "Nº Montañas Rusas", value: item.operating_coasters, i18n: "spot.num_coasters" },
          { label: "Fundado",            value: item.opening_year,       i18n: "spot.founded"     },
          { label: "País",              value: item.country,            i18n: "spot.country"     },
        ],
      };
    }

    // Coaster: 2º dato = modelo
    const coasterExtra = { label: "Modelo", value: item.model, i18n: "spot.model" };

    return {
      type: "coaster",
      href: window.BASE_URL + "/web/views/public/coasters/coasters.php?id=" + item.id,
      name: item.coaster_name,
      img: item.imagen_url,
      places: [
        { icon: "fa-ticket",       text: item.park_name },
        { icon: "fa-location-dot", text: item.country   },
      ],
      status: item.status,
      ridden: item.ridden === true || item.ridden === "t" || item.ridden === "true" || item.ridden === 1,
      facts: [
        { label: "Año",       value: item.opening_year, i18n: "spot.year"          },
        coasterExtra,
        { label: "Fabricante", value: item.manufacter,   i18n: "spot.manufacturer", wide: true },
      ],
    };
  }

  /* Normaliza el texto de estado a una de 4 variantes visuales y traduce el label */
  function statusOf(raw) {
    const s = String(raw ?? "").toLowerCase();
    if (!s) return null;
    if (s === "operating" || s === "operativa" || s.includes("oper") || s.includes("abiert") || s.includes("open"))
      return { cls: "open",     text: t("spot.status_operating", raw) };
    if (s === "defunct" || s.includes("defunct") || s.includes("demantel") || s.includes("demontiert"))
      return { cls: "closed",   text: t("spot.status_defunct", raw) };
    if (s === "closed" || s.includes("cerr") || s.includes("clos") || s.includes("geschlossen") || s.includes("ferm"))
      return { cls: "closed",   text: t("spot.status_closed", raw) };
    if (s.includes("sbno") || s.includes("standing") || s.includes("stillgelegt") || s.includes("parad") || s.includes("arrêt"))
      return { cls: "closed",   text: t("spot.status_sbno", raw) };
    if (s.includes("constr") || s.includes("build") || s.includes("bau"))
      return { cls: "building", text: t("spot.status_construction", raw) };
    if (s.includes("reloc") || s.includes("reubic") || s.includes("verlegt") || s.includes("déplac"))
      return { cls: "building", text: t("spot.status_relocated", raw) };
    return { cls: "unknown",   text: raw };
  }

  const dash = (v) => (v === null || v === undefined || v === "" ? "—" : v);

  function t(key, fallback) {
    if (window.rcwI18n && typeof window.rcwI18n.t === "function") {
      const res = window.rcwI18n.t(key);
      if (res && res !== key) return res;
    }
    return fallback;
  }

  function cardHTML(item, type, { animate = false } = {}) {
    const c = adapt(item, type);
    const st = statusOf(c.status);
    const isPark = c.type === "park";
    const typeLabel = isPark ? t("spot.type_park", "Parque") : t("spot.type_coaster", "Coaster");
    const ghostIcon = isPark ? "fa-ticket" : "fa-train-tram";

    const places = c.places
      .filter((p) => p.text)
      .map((p) => `<span><i class="fa-solid ${p.icon}"></i>${esc(p.text)}</span>`)
      .join("");

    const facts = c.facts
      .map(
        (f) => `
        <div class="rcw-spot__fact${f.wide ? " rcw-spot__fact--wide" : ""}">
          <div class="rcw-spot__fact-label" data-i18n="${f.i18n}">${t(f.i18n, esc(f.label))}</div>
          <div class="rcw-spot__fact-value" title="${esc(dash(f.value))}">${esc(dash(f.value))}</div>
        </div>`,
      )
      .join("");

    return `
      <a href="${esc(c.href)}"
         class="rcw-spot rcw-spot--${c.type}${c.img ? "" : " rcw-spot--nophoto"}${animate ? " rcw-spot--enter" : ""}"
         aria-label="${esc(c.name)}">
        ${c.img
          ? `<img class="rcw-spot__blur" src="${esc(c.img)}" alt="" aria-hidden="true" loading="lazy"><img class="rcw-spot__img" src="${esc(c.img)}" alt="" loading="lazy">`
          : `<i class="fa-solid ${ghostIcon} rcw-spot__ghost" aria-hidden="true"></i>`}

        <div class="rcw-spot__top">
          <span class="rcw-spot__chip rcw-spot__chip--type">${typeLabel}</span>
          <div class="rcw-spot__chips">
            ${c.ridden ? `<span class="rcw-spot__chip rcw-spot__chip--done"><i class="fa-solid fa-check"></i><span data-i18n="spot.ridden">${t("spot.ridden", "YA PROBADA")}</span></span>` : ""}
            ${st ? `<span class="rcw-spot__chip rcw-spot__chip--status rcw-spot__chip--${st.cls}">${esc(st.text)}</span>` : ""}
          </div>
        </div>

        <div class="rcw-spot__body">
          <h3 class="rcw-spot__title">${esc(c.name)}</h3>
          <div class="rcw-spot__place">${places}</div>

          <div class="rcw-spot__foot">
            <div class="rcw-spot__facts">${facts}</div>
          </div>
        </div>
      </a>`;
  }

  function skeletonHTML(n = 2) {
    return Array.from({ length: n }, () => '<div class="rcw-spot rcw-spot--skeleton" aria-hidden="true"></div>').join("");
  }

  window.RCWSpot = {
    cardHTML,
    /** items: [{ type: "coaster" | "park", data: {...} }]  o  [coaster, park] en ese orden */
    render(grid, items, opts = {}) {
      if (!grid) return;
      const types = ["coaster", "park"];
      grid.innerHTML = items
        .map((it, i) => (it && it.data ? cardHTML(it.data, it.type, opts) : cardHTML(it, types[i] || "coaster", opts)))
        .join("");
    },
    skeleton(grid, n = 2) {
      if (grid) grid.innerHTML = skeletonHTML(n);
    },
  };
})();
