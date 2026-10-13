/* web/js/users/top_personal.js */
$(document).ready(function () {
  const BASE_URL = window.BASE_URL || "";

  let topsData = { coasters: [], parks: [] };

  // Tipo de vista persistido (misma clave que profile para coherencia)
  let coastersViewType = localStorage.getItem("tops-coasters-view") || "list";
  let parksViewType    = localStorage.getItem("tops-parks-view")    || "list";

  // ── Helpers ────────────────────────────────────────────────────────────────────

  function emptyState(msg) {
    return `<div class="text-center text-muted py-5">
      <i class="fa-solid fa-ghost fs-1 mb-3 opacity-50 d-block"></i><p>${msg}</p>
    </div>`;
  }

  function resolveImg(url) {
    if (!url) return null;
    return url.startsWith("/") ? BASE_URL + url : url;
  }

  function rankBadgeHtml(pos) {
    return `<span class="rank-img-badge">#${pos}</span>`;
  }

  function starsHtml(val) {
    const v = parseFloat(val) || 0;
    if (!v) return "";
    return `<div class="d-flex align-items-center gap-1 text-warning fw-bold" style="font-size:0.9rem;">
      <span>${v.toFixed(1)}</span><i class="fa-solid fa-star" style="font-size:0.75rem;"></i>
    </div>`;
  }

  // ── Filtros y Sliders Helper ──────────────────────────────────────────────────
  function isSliderModified(id) {
    const el = document.getElementById(id);
    if (!el || !el.noUiSlider) return false;
    const vals = el.noUiSlider.get();
    const opts = el.noUiSlider.options.range;
    return parseFloat(vals[0]) > opts.min || parseFloat(vals[1]) < opts.max;
  }

  function initOrUpdateSlider(id, min, max, step, formatDecimals = 0) {
    const el = document.getElementById(id);
    if (!el) return;
    
    if (min === max) {
      min = Math.floor(min - 10);
      max = Math.ceil(max + 10);
    }
    if (min == null || max == null || isNaN(min) || isNaN(max)) {
      min = 0; max = 100;
    }

    if (el.noUiSlider) {
      el.noUiSlider.updateOptions({
        range: { min, max }
      });
      el.noUiSlider.set([min, max]);
    } else {
      noUiSlider.create(el, {
        start: [min, max],
        connect: true,
        step: step,
        range: { min, max },
        format: {
          to: v => Number(v).toFixed(formatDecimals),
          from: v => Number(v)
        }
      });
      
      el.noUiSlider.on('update', function (values) {
        const valId = id.replace('slider-', 'val-');
        $(`#${valId}`).text(`${values[0]} - ${values[1]}`);
      });
      
      el.noUiSlider.on('change', function () {
        if (id.includes('parks')) {
          if (typeof updateParksBadge === 'function') updateParksBadge();
          if (typeof renderParks === 'function') renderParks();
        } else {
          updateCoastersBadge();
          renderCoasters();
        }
      });
    }
  }

  function getFilteredCoasters() {
    const fPark   = $("#coasters-filter-park").val();
    const fCountry= $("#coasters-filter-country").val();
    const fMfr    = $("#coasters-filter-manufacter").val();
    const fModel  = $("#coasters-filter-model").val();
    const fOperating = $("#coasters-filter-operating").is(":checked");

    const slHeight = document.getElementById('slider-height')?.noUiSlider?.get();
    const slSpeed = document.getElementById('slider-speed')?.noUiSlider?.get();
    const slLength = document.getElementById('slider-length')?.noUiSlider?.get();
    const slInversions = document.getElementById('slider-inversions')?.noUiSlider?.get();
    const slYear = document.getElementById('slider-year')?.noUiSlider?.get();

    let data = [...topsData.coasters];

    if (fPark)    data = data.filter((d) => d.park_name    === fPark);
    if (fCountry) data = data.filter((d) => d.country_name === fCountry);
    if (fMfr)     data = data.filter((d) => d.manufacter   === fMfr);
    if (fModel)   data = data.filter((d) => d.model        === fModel);

    if (fOperating) {
      data = data.filter(d => d.coaster_status && (d.coaster_status.toLowerCase() === 'operating' || d.coaster_status.toLowerCase() === 'operativa'));
    }

    if (slHeight) {
      const min = parseFloat(slHeight[0]), max = parseFloat(slHeight[1]);
      const mod = isSliderModified('slider-height');
      data = data.filter(d => { const v = parseFloat(d.height); return (!mod && isNaN(v)) || (!isNaN(v) && v >= min && v <= max); });
    }
    if (slSpeed) {
      const min = parseFloat(slSpeed[0]), max = parseFloat(slSpeed[1]);
      const mod = isSliderModified('slider-speed');
      data = data.filter(d => { const v = parseFloat(d.speed); return (!mod && isNaN(v)) || (!isNaN(v) && v >= min && v <= max); });
    }
    if (slLength) {
      const min = parseFloat(slLength[0]), max = parseFloat(slLength[1]);
      const mod = isSliderModified('slider-length');
      data = data.filter(d => { const v = parseFloat(d.coaster_length); return (!mod && isNaN(v)) || (!isNaN(v) && v >= min && v <= max); });
    }
    if (slInversions) {
      const min = parseInt(slInversions[0]), max = parseInt(slInversions[1]);
      const mod = isSliderModified('slider-inversions');
      data = data.filter(d => { const v = parseInt(d.inversions); return (!mod && isNaN(v)) || (!isNaN(v) && v >= min && v <= max); });
    }
    if (slYear) {
      const min = parseInt(slYear[0]), max = parseInt(slYear[1]);
      const mod = isSliderModified('slider-year');
      data = data.filter(d => { const v = parseInt(d.opening_year); return (!mod && isNaN(v)) || (!isNaN(v) && v >= min && v <= max); });
    }

    const sort    = $("#coasters-sort").val() || "rank";
    const sortDir = $("#coasters-sort-dir").attr("data-dir") || "asc";
    const asc     = sortDir === "asc";

    if (sort === "name") {
      data.sort((a, b) => { const c = a.coaster_name.localeCompare(b.coaster_name); return asc ? c : -c; });
    } else if (sort === "height") {
      data.sort((a, b) => asc ? (parseFloat(a.height)||0)-(parseFloat(b.height)||0) : (parseFloat(b.height)||0)-(parseFloat(a.height)||0));
    } else if (sort === "speed") {
      data.sort((a, b) => asc ? (parseFloat(a.speed)||0)-(parseFloat(b.speed)||0) : (parseFloat(b.speed)||0)-(parseFloat(a.speed)||0));
    } else if (sort === "length") {
      data.sort((a, b) => asc ? (parseFloat(a.coaster_length)||0)-(parseFloat(b.coaster_length)||0) : (parseFloat(b.coaster_length)||0)-(parseFloat(a.coaster_length)||0));
    } else if (sort === "inversions") {
      data.sort((a, b) => asc ? (parseInt(a.inversions)||0)-(parseInt(b.inversions)||0) : (parseInt(b.inversions)||0)-(parseInt(a.inversions)||0));
    } else if (sort === "year") {
      data.sort((a, b) => { const ya=parseInt(a.opening_year)||9999, yb=parseInt(b.opening_year)||9999; return asc ? ya-yb : yb-ya; });
    } else {
      data.sort((a, b) => { const ra=parseInt(a.rank_position)||9999, rb=parseInt(b.rank_position)||9999; return asc ? ra-rb : rb-ra; });
    }
    return data;
  }

  // ── Fetch ──────────────────────────────────────────────────────────────────────

  async function loadUserTops() {
    try {
      const [resC, resP] = await Promise.all([
        fetch(`${BASE_URL}/api/php/profile_config.php?action=get_top_coasters`),
        fetch(`${BASE_URL}/api/php/profile_config.php?action=get_top_parks`),
      ]);
      const [dataC, dataP] = await Promise.all([resC.json(), resP.json()]);
      topsData.coasters = Array.isArray(dataC) ? dataC : (dataC.tops || []);
      topsData.parks    = Array.isArray(dataP) ? dataP : (dataP.tops || []);

      // Badges de conteo en las pestañas
      if (topsData.coasters.length) {
        $("#tab-coasters-count").text(topsData.coasters.length).removeClass("d-none");
      }
      if (topsData.parks.length) {
        $("#tab-parks-count").text(topsData.parks.length).removeClass("d-none");
      }

      populateCoastersFilters(topsData.coasters);
      populateParksFilters(topsData.parks);
      renderCoasters();
      renderParks();

    } catch (err) {
      console.error("Error cargando tops:", err);
      $("#coasters-container").html(`<div class="alert alert-danger rounded-0">Error al cargar montañas rusas</div>`);
      $("#parks-container").html(`<div class="alert alert-danger rounded-0">Error al cargar parques</div>`);
    }
  }

  // ── Coasters — dispatch por vista ──────────────────────────────────────────────

  function renderCoasters() {
    if (coastersViewType === "mini") { renderCoastersMini(); return; }

    const sort    = $("#coasters-sort").val() || "rank";
    const isGrid  = coastersViewType === "grid";

    let data = getFilteredCoasters();

    // Actualizar contador
    $("#coasters-count").text(data.length);
    $("#coasters-count-label").text(data.length === 1 ? "coaster" : "coasters");

    const container = $("#coasters-container").empty();

    if (!data.length) {
      container.html(emptyState("Ningún coaster coincide con los filtros."));
      return;
    }

    const colClass = isGrid ? "col-tp-5" : "col-12";
    const $row = $('<div class="row g-2"></div>');

    function coasterStatBadges(item) {
      const mfr = item.manufacter
        ? `<span class="text-secondary d-flex align-items-center gap-1"><i class="fa-solid fa-industry"></i><span>${item.manufacter}</span></span>`
        : "";
      if (sort === "height") return (item.height ? `<span class="text-info d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-ruler-vertical"></i>${item.height}m</span>` : "") + mfr;
      if (sort === "speed")  return (item.speed  ? `<span class="text-warning d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-bolt"></i>${item.speed}km/h</span>` : "") + mfr;
      if (sort === "length") return (item.coaster_length ? `<span class="text-info d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-ruler-horizontal"></i>${item.coaster_length}m</span>` : "") + mfr;
      if (sort === "inversions") return (item.inversions != null ? `<span class="text-warning d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-infinity"></i>${item.inversions} inv.</span>` : "") + mfr;
      if (sort === "year") return (item.opening_year ? `<span class="text-secondary d-flex align-items-center gap-1 fw-semibold"><i class="fa-regular fa-calendar"></i>${item.opening_year}</span>` : "") + mfr;
      return (item.height ? `<span class="text-info d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-ruler-vertical"></i>${item.height}m</span>` : "") +
             (item.speed  ? `<span class="text-warning d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-bolt"></i>${item.speed}km/h</span>` : "") + mfr;
    }

    data.forEach((item) => {
      const detailUrl = `${BASE_URL}/web/views/public/coasters/coasters.php?id=${item.coaster_id}`;
      const src = resolveImg(item.imagen_url);

      if (isGrid) {
        const imgHtml = src
          ? `<img src="${src}" alt="${item.coaster_name}" class="top-card-grid-img" loading="lazy">`
          : `<div class="top-card-grid-placeholder"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

        $row.append(`
          <div class="${colClass}">
            <a href="${detailUrl}" class="top-card d-block text-decoration-none shadow-sm" style="border-radius:4px;">
              <div class="position-relative">${imgHtml}${rankBadgeHtml(item.rank_position)}</div>
              <div class="p-2" style="background:#161c22;">
                <div class="fw-bold text-white small text-truncate">${item.coaster_name}</div>
                <div class="text-secondary text-truncate" style="font-size:.7rem;">${item.park_name || ""}</div>
              </div>
            </a>
          </div>`);
      } else {
        const listImgHtml = src
          ? `<img src="${src}" alt="${item.coaster_name}" class="top-card-list-img" loading="lazy">`
          : `<div class="top-card-list-placeholder"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;
        $row.append(`
          <div class="${colClass} mb-1">
            <div class="top-card d-flex flex-column flex-md-row">
              <div class="rank-img-container position-relative" style="flex:0 0 110px;min-height:90px;align-self:stretch;">
                ${listImgHtml}${rankBadgeHtml(item.rank_position)}
              </div>
              <div class="p-2 px-3 flex-grow-1 d-flex flex-column justify-content-center min-w-0 py-2">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="pe-3 min-w-0 flex-grow-1">
                    <a href="${detailUrl}" class="fw-bold text-white text-decoration-none d-block text-truncate hover-green"
                       style="font-family:var(--rcw-font-title);font-size:1.1rem;line-height:1.2;">${item.coaster_name}</a>
                    <div class="text-secondary d-flex align-items-center gap-2 mt-1 text-truncate" style="font-size:0.8rem;opacity:0.8;">
                      <i class="fa-solid fa-location-dot text-success opacity-75 flex-shrink-0"></i>
                      <span class="text-truncate">${item.park_name || ""} · ${item.country_name || ""}</span>
                    </div>
                  </div>
                  <div class="flex-shrink-0">${starsHtml(item.stars)}</div>
                </div>
                <div class="stats-expandable mt-2 gap-3 flex-wrap align-items-center d-none d-md-flex" style="font-size:0.85rem;">
                  ${coasterStatBadges(item)}
                </div>
                <button class="btn btn-sm btn-toggle-stats text-secondary p-0 mt-1 d-flex d-md-none align-items-center gap-1 w-100 text-start" style="font-size: 0.75rem; background:transparent; border:none; opacity: 0.7;">
                   <i class="fa-solid fa-chevron-down"></i>
                   <span>Ver detalles</span>
                </button>
              </div>
              <a href="${detailUrl}" class="d-none d-md-flex align-items-center px-3 text-decoration-none hover-bg-white-5" style="transition:all 0.2s;">
                <i class="fa-solid fa-chevron-right fs-5 text-secondary opacity-50"></i>
              </a>
            </div>
          </div>`);
      }
    });

    container.append($row);
  }

  function renderCoastersMini() {
    let data = getFilteredCoasters();

    $("#coasters-count").text(data.length);
    $("#coasters-count-label").text(data.length === 1 ? "coaster" : "coasters");

    const container = $("#coasters-container").empty();
    if (!data.length) { container.html(emptyState("Ningún coaster coincide con los filtros.")); return; }

    const $col = $('<div class="col-12 p-0"></div>');
    data.forEach((item) => {
      const detailUrl = `${BASE_URL}/web/views/public/coasters/coasters.php?id=${item.coaster_id}`;
      const src = resolveImg(item.imagen_url);
      const $a = $(document.createElement("a")).attr({ href: detailUrl, tabindex: "0" }).addClass("tops-mini-item");

      const rankVal = parseInt(item.rank_position) || 0;
      const $rank = $(document.createElement("span")).addClass("tops-mini-rank").text(rankVal);
      if (rankVal === 1)           $rank.addClass("rank-gold");
      else if (rankVal === 2)      $rank.addClass("rank-silver");
      else if (rankVal === 3)      $rank.addClass("rank-bronze");
      else if (rankVal <= 5)       $rank.addClass("rank-top5");
      $rank.addClass(rankVal.toString().length === 1 ? "rank-1d" : rankVal.toString().length === 2 ? "rank-2d" : "rank-3d");

      let $thumb;
      if (src) {
        $thumb = $(document.createElement("img")).addClass("tops-mini-thumb").attr({ src, alt: item.coaster_name, loading: "lazy" });
      } else {
        $thumb = $(document.createElement("div")).addClass("tops-mini-thumb-placeholder").html('<i class="fa-solid fa-image" aria-hidden="true"></i>');
      }

      const $info = $(document.createElement("div")).addClass("tops-mini-info");
      $info.append(
        $(document.createElement("div")).addClass("tops-mini-name").text(item.coaster_name),
        $(document.createElement("div")).addClass("tops-mini-sub").text((item.park_name || "") + (item.country_name ? " · " + item.country_name : ""))
      );
      const $chev = $(document.createElement("i")).addClass("fa-solid fa-chevron-right tops-mini-chevron").attr("aria-hidden", "true");

      $a.append($rank, $thumb, $info, $chev);
      $col.append($a);
    });
    container.append($col);
  }

  // ── Parks — dispatch por vista ─────────────────────────────────────────────────

  function getFilteredParks() {
    let data = [...topsData.parks];
    const fCountry = $("#parks-filter-country").val();
    if (fCountry) data = data.filter((d) => d.country_name === fCountry);

    const slCoasters = document.getElementById("slider-parks-coasters")?.noUiSlider?.get();
    if (slCoasters) {
      const min = parseInt(slCoasters[0]), max = parseInt(slCoasters[1]);
      const mod = isSliderModified('slider-parks-coasters');
      data = data.filter(d => { const v = parseInt(d.operating_coasters); return (!mod && isNaN(v)) || (!isNaN(v) && v >= min && v <= max); });
    }

    const sort     = $("#parks-sort").val() || "rank";
    const sortDir  = $("#parks-sort-dir").attr("data-dir") || "asc";
    const asc      = sortDir === "asc";

    if (sort === "name") {
      data.sort((a, b) => { const c = (a.park_name || "").localeCompare(b.park_name || ""); return asc ? c : -c; });
    } else if (sort === "coasters") {
      data.sort((a, b) => asc ? (parseInt(a.operating_coasters)||0)-(parseInt(b.operating_coasters)||0) : (parseInt(b.operating_coasters)||0)-(parseInt(a.operating_coasters)||0));
    } else {
      data.sort((a, b) => { const ra=parseInt(a.rank_position)||9999, rb=parseInt(b.rank_position)||9999; return asc ? ra-rb : rb-ra; });
    }

    return data;
  }

  function renderParks() {
    if (parksViewType === "mini") { renderParksMini(); return; }

    const isGrid = parksViewType === "grid";
    let data = getFilteredParks();

    // Actualizar contador
    $("#parks-count").text(data.length);

    const container = $("#parks-container").empty();
    if (!data.length) { container.html(emptyState("No has añadido ningún parque a tu top aún.")); return; }

    const colClass = isGrid ? "col-tp-5" : "col-12";
    const $row = $('<div class="row g-2"></div>');

    data.forEach((item) => {
      const detailUrl = `${BASE_URL}/web/views/public/parks/parks.php?id=${item.park_id}`;
      const src = resolveImg(item.imagen_url);

      if (isGrid) {
        const imgHtml = src
          ? `<img src="${src}" alt="${item.park_name}" class="top-card-grid-img" loading="lazy">`
          : `<div class="top-card-grid-placeholder"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

        $row.append(`
          <div class="${colClass}">
            <a href="${detailUrl}" class="top-card d-block text-decoration-none shadow-sm" style="border-radius:4px;">
              <div class="position-relative">${imgHtml}${rankBadgeHtml(item.rank_position)}</div>
              <div class="p-2" style="background:#161c22;">
                <div class="fw-bold text-white small text-truncate">${item.park_name}</div>
                <div class="text-secondary text-truncate" style="font-size:.7rem;">${item.country_name || ""}</div>
              </div>
            </a>
          </div>`);
      } else {
        const listImgHtml = src
          ? `<img src="${src}" alt="${item.park_name}" class="top-card-list-img" loading="lazy">`
          : `<div class="top-card-list-placeholder"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

        $row.append(`
          <div class="${colClass} mb-1">
            <div class="top-card d-flex flex-column flex-md-row">
              <div class="rank-img-container position-relative" style="flex:0 0 110px;min-height:90px;align-self:stretch;">
                ${listImgHtml}${rankBadgeHtml(item.rank_position)}
              </div>
              <div class="p-2 px-3 flex-grow-1 d-flex flex-column justify-content-center min-w-0 py-2">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="pe-3 min-w-0 flex-grow-1">
                    <a href="${detailUrl}" class="fw-bold text-white text-decoration-none d-block text-truncate hover-green"
                       style="font-family:var(--rcw-font-title);font-size:1.1rem;line-height:1.2;">${item.park_name}</a>
                    <div class="text-secondary d-flex align-items-center gap-2 mt-1 text-truncate" style="font-size:0.8rem;opacity:0.8;">
                      <i class="fa-solid fa-location-dot text-success opacity-75 flex-shrink-0"></i>
                      <span class="text-truncate">${item.country_name || ""}</span>
                    </div>
                  </div>
                  <div class="flex-shrink-0">${starsHtml(item.stars)}</div>
                </div>
                <div class="stats-expandable mt-2 gap-3 flex-wrap align-items-center d-none d-md-flex" style="font-size:0.85rem;">
                  ${item.operating_coasters ? `<span class="text-info d-flex align-items-center gap-1 fw-semibold"><i class="fa-solid fa-ticket"></i>${item.operating_coasters} coasters</span>` : ""}
                </div>
                <button class="btn btn-sm btn-toggle-stats text-secondary p-0 mt-1 d-flex d-md-none align-items-center gap-1 w-100 text-start" style="font-size: 0.75rem; background:transparent; border:none; opacity: 0.7;">
                   <i class="fa-solid fa-chevron-down"></i>
                   <span>Ver detalles</span>
                </button>
              </div>
              <a href="${detailUrl}" class="d-none d-md-flex align-items-center px-3 text-decoration-none hover-bg-white-5" style="transition:all 0.2s;">
                <i class="fa-solid fa-chevron-right fs-5 text-secondary opacity-50"></i>
              </a>
            </div>
          </div>`);
      }
    });

    container.append($row);
  }

  function renderParksMini() {
    let data = getFilteredParks();

    $("#parks-count").text(data.length);

    const container = $("#parks-container").empty();
    if (!data.length) { container.html(emptyState("Ningún parque coincide con los filtros.")); return; }

    const $col = $('<div class="col-12 p-0"></div>');
    data.forEach((item) => {
      const detailUrl = `${BASE_URL}/web/views/public/parks/parks.php?id=${item.park_id}`;
      const src = resolveImg(item.imagen_url);
      const $a = $(document.createElement("a")).attr({ href: detailUrl, tabindex: "0" }).addClass("tops-mini-item");

      const rankVal = parseInt(item.rank_position) || 0;
      const $rank = $(document.createElement("span")).addClass("tops-mini-rank").text(rankVal);
      if (rankVal === 1)       $rank.addClass("rank-gold");
      else if (rankVal === 2)  $rank.addClass("rank-silver");
      else if (rankVal === 3)  $rank.addClass("rank-bronze");
      else if (rankVal <= 5)   $rank.addClass("rank-top5");
      $rank.addClass(rankVal.toString().length === 1 ? "rank-1d" : rankVal.toString().length === 2 ? "rank-2d" : "rank-3d");

      let $thumb;
      if (src) {
        $thumb = $(document.createElement("img")).addClass("tops-mini-thumb").attr({ src, alt: item.park_name, loading: "lazy" });
      } else {
        $thumb = $(document.createElement("div")).addClass("tops-mini-thumb-placeholder").html('<i class="fa-solid fa-image" aria-hidden="true"></i>');
      }

      const $info = $(document.createElement("div")).addClass("tops-mini-info");
      $info.append(
        $(document.createElement("div")).addClass("tops-mini-name").text(item.park_name),
        $(document.createElement("div")).addClass("tops-mini-sub").text(item.country_name || "")
      );
      const $chev = $(document.createElement("i")).addClass("fa-solid fa-chevron-right tops-mini-chevron").attr("aria-hidden", "true");

      $a.append($rank, $thumb, $info, $chev);
      $col.append($a);
    });
    container.append($col);
  }

  // ── Toggles de vista — Coasters ────────────────────────────────────────────────

  $("#coasters-view-mini").on("click", function () {
    coastersViewType = "mini";
    localStorage.setItem("tops-coasters-view", "mini");
    $(this).addClass("active").siblings().removeClass("active");
    renderCoasters();
  });
  $("#view-list").on("click", function () {
    coastersViewType = "list";
    localStorage.setItem("tops-coasters-view", "list");
    $(this).addClass("active").siblings().removeClass("active");
    renderCoasters();
  });
  $("#view-grid").on("click", function () {
    coastersViewType = "grid";
    localStorage.setItem("tops-coasters-view", "grid");
    $(this).addClass("active").siblings().removeClass("active");
    renderCoasters();
  });

  // ── Toggles de vista — Parks ───────────────────────────────────────────────────

  $("#parks-view-mini").on("click", function () {
    parksViewType = "mini";
    localStorage.setItem("tops-parks-view", "mini");
    $(this).addClass("active").siblings().removeClass("active");
    renderParks();
  });
  $("#view-list-parks").on("click", function () {
    parksViewType = "list";
    localStorage.setItem("tops-parks-view", "list");
    $(this).addClass("active").siblings().removeClass("active");
    renderParks();
  });
  $("#view-grid-parks").on("click", function () {
    parksViewType = "grid";
    localStorage.setItem("tops-parks-view", "grid");
    $(this).addClass("active").siblings().removeClass("active");
    renderParks();
  });

  // ── Restaurar botón activo según localStorage ──────────────────────────────────
  if (coastersViewType === "mini")       { $("#coasters-view-mini").addClass("active"); $("#view-list").removeClass("active"); }
  else if (coastersViewType === "grid")  { $("#view-grid").addClass("active");          $("#view-list").removeClass("active"); }

  if (parksViewType === "mini")          { $("#parks-view-mini").addClass("active");  $("#view-list-parks").removeClass("active"); }
  else if (parksViewType === "grid")     { $("#view-grid-parks").addClass("active");  $("#view-list-parks").removeClass("active"); }

  // ── Tab events ─────────────────────────────────────────────────────────────────
  $('button[data-bs-toggle="tab"]').on("shown.bs.tab", function (event) {
    // Re-renderizar la pestaña activa por si cambiaron los datos
    if (event.target.id === "coasters-tab") renderCoasters();
    else renderParks();
  });

  // ── Poblar filtros dinámicamente con los datos cargados ───────────────────────

  function populateCoastersFilters(data) {
    const parks    = [...new Set(data.map((d) => d.park_name).filter(Boolean))].sort();
    const countries= [...new Set(data.map((d) => d.country_name).filter(Boolean))].sort();
    const mfrs     = [...new Set(data.map((d) => d.manufacter).filter(Boolean))].sort();
    const models   = [...new Set(data.map((d) => d.model).filter(Boolean))].sort();

    const $pk = $("#coasters-filter-park").find("option:first").end();
    const $co = $("#coasters-filter-country").find("option:first").end();
    const $mf = $("#coasters-filter-manufacter").find("option:first").end();
    const $mo = $("#coasters-filter-model").find("option:first").end();
    $pk.find("option:not(:first)").remove();
    $co.find("option:not(:first)").remove();
    $mf.find("option:not(:first)").remove();
    $mo.find("option:not(:first)").remove();
    parks.forEach((v)   => $pk.append(`<option value="${v}">${v}</option>`));
    countries.forEach((v)=> $co.append(`<option value="${v}">${v}</option>`));
    mfrs.forEach((v)    => $mf.append(`<option value="${v}">${v}</option>`));
    models.forEach((v)  => $mo.append(`<option value="${v}">${v}</option>`));

    // Inicializar Sliders
    let minHeight = 9999, maxHeight = 0;
    let minSpeed = 9999, maxSpeed = 0;
    let minLength = 9999, maxLength = 0;
    let minInversions = 9999, maxInversions = 0;
    let minYear = 9999, maxYear = 0;

    data.forEach(d => {
      const h = parseFloat(d.height);
      if (!isNaN(h)) { minHeight = Math.min(minHeight, h); maxHeight = Math.max(maxHeight, h); }
      const s = parseFloat(d.speed);
      if (!isNaN(s)) { minSpeed = Math.min(minSpeed, s); maxSpeed = Math.max(maxSpeed, s); }
      const l = parseFloat(d.coaster_length);
      if (!isNaN(l)) { minLength = Math.min(minLength, l); maxLength = Math.max(maxLength, l); }
      const i = parseInt(d.inversions);
      if (!isNaN(i)) { minInversions = Math.min(minInversions, i); maxInversions = Math.max(maxInversions, i); }
      const y = parseInt(d.opening_year);
      if (!isNaN(y)) { minYear = Math.min(minYear, y); maxYear = Math.max(maxYear, y); }
    });

    if (minHeight === 9999) minHeight = 0; if (maxHeight === 0) maxHeight = 100;
    if (minSpeed === 9999) minSpeed = 0; if (maxSpeed === 0) maxSpeed = 150;
    if (minLength === 9999) minLength = 0; if (maxLength === 0) maxLength = 1000;
    if (minInversions === 9999) minInversions = 0; if (maxInversions === 0) maxInversions = 10;
    if (minYear === 9999) minYear = 1900; if (maxYear === 0) maxYear = new Date().getFullYear();

    initOrUpdateSlider('slider-height', Math.floor(minHeight), Math.ceil(maxHeight), 1, 0);
    initOrUpdateSlider('slider-speed', Math.floor(minSpeed), Math.ceil(maxSpeed), 1, 0);
    initOrUpdateSlider('slider-length', Math.floor(minLength), Math.ceil(maxLength), 1, 0);
    initOrUpdateSlider('slider-inversions', minInversions, maxInversions, 1, 0);
    initOrUpdateSlider('slider-year', minYear, maxYear, 1, 0);

    // Inicializar contador
    $("#coasters-count").text(data.length);
    $("#coasters-count-label").text(data.length === 1 ? "coaster" : "coasters");
  }

  function populateParksFilters(data) {
    const countries = [...new Set(data.map((d) => d.country_name).filter(Boolean))].sort();
    const $co = $("#parks-filter-country").find("option:first").end();
    $co.find("option:not(:first)").remove();
    countries.forEach((v) => $co.append(`<option value="${v}">${v}</option>`));

    let minC = 9999, maxC = 0;
    data.forEach(d => {
      const c = parseInt(d.operating_coasters);
      if (!isNaN(c)) { minC = Math.min(minC, c); maxC = Math.max(maxC, c); }
    });
    if (minC === 9999) minC = 0; if (maxC === 0) maxC = 20;
    initOrUpdateSlider('slider-parks-coasters', minC, maxC, 1, 0);

    $("#parks-count").text(data.length);
  }

  // ── Direcci\u00f3n natural por criterio (definida antes de usarse) \u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500
  const COASTERS_DEFAULT_DIR = {
    rank: "asc", name: "asc",
    height: "desc", speed: "desc", length: "desc", inversions: "desc", year: "desc"
  };

  function setCoastersSortDir(criterion) {
    const dir = COASTERS_DEFAULT_DIR[criterion] || "asc";
    const $btn = $("#coasters-sort-dir");
    $btn.attr("data-dir", dir);
    $btn.find("i").toggleClass("fa-caret-up", dir === "asc").toggleClass("fa-caret-down", dir === "desc");
  }

  // ── Arrancar ───────────────────────────────────────────────────────────────────
  // Aplicar dirección natural inicial antes de cargar datos
  setCoastersSortDir($("#coasters-sort").val() || "rank");
  loadUserTops();

  // ── Event listeners filtros coasters ──────────────────────────────────────────
  function updateCoastersBadge() {
    let active = [$("#coasters-filter-park").val(), $("#coasters-filter-country").val(),
                    $("#coasters-filter-manufacter").val(), $("#coasters-filter-model").val()]
      .filter((v) => v && v !== "").length;
      
    if ($("#coasters-filter-operating").is(":checked")) active++;
    
    if (isSliderModified('slider-height')) active++;
    if (isSliderModified('slider-speed')) active++;
    if (isSliderModified('slider-length')) active++;
    if (isSliderModified('slider-inversions')) active++;
    if (isSliderModified('slider-year')) active++;

    const $badge = $("#coasters-filters-badge");
    active > 0 ? $badge.text(active).removeClass("d-none") : $badge.addClass("d-none");

    // Mostrar / ocultar botón limpiar
    $("#coasters-clear-row").toggle(active > 0);
  }

  $("#coasters-sort").on("change", function () {
    setCoastersSortDir($(this).val());
    updateCoastersBadge();
    renderCoasters();
  });

  $("#coasters-filter-park, #coasters-filter-country, #coasters-filter-manufacter, #coasters-filter-model")
    .on("change", function () { updateCoastersBadge(); renderCoasters(); });

  $("#coasters-filter-operating").on("change", function () {
    updateCoastersBadge(); renderCoasters();
  });


  $("#coasters-sort-dir").on("click", function () {
    const $btn = $(this);
    const newDir = $btn.attr("data-dir") === "asc" ? "desc" : "asc";
    $btn.attr("data-dir", newDir);
    $btn.find("i").toggleClass("fa-caret-up", newDir === "asc").toggleClass("fa-caret-down", newDir === "desc");
    renderCoasters();
  });

  $("#coasters-filters-toggle").on("click", function () {
    const $panel = $("#coasters-filters-collapsible");
    const isOpen = $panel.hasClass("is-open");
    $panel.toggleClass("is-open", !isOpen);
    $(this).toggleClass("is-open", !isOpen).attr("aria-expanded", !isOpen);
  });

  // ── Event listeners filtros parques ───────────────────────────────────────────
  function updateParksBadge() {
    const fCountry = $("#parks-filter-country").val();
    const fSort    = $("#parks-sort").val();
    let active = (fCountry ? 1 : 0) + (fSort && fSort !== "rank" ? 1 : 0);
    if (isSliderModified('slider-parks-coasters')) active++;
    const $badge = $("#parks-filters-badge");
    active > 0 ? $badge.text(active).removeClass("d-none") : $badge.addClass("d-none");

    // Mostrar / ocultar botón limpiar
    $("#parks-clear-row").toggle(active > 0);
  }

  const PARKS_DEFAULT_DIR = { rank: "asc", name: "asc", coasters: "desc" };

  function setParksSortDir(criterion) {
    const dir = PARKS_DEFAULT_DIR[criterion] || "asc";
    const $btn = $("#parks-sort-dir");
    $btn.attr("data-dir", dir);
    $btn.find("i").toggleClass("fa-caret-up", dir === "asc").toggleClass("fa-caret-down", dir === "desc");
  }

  $("#parks-sort").on("change", function () {
    setParksSortDir($(this).val());
    updateParksBadge();
    renderParks();
  });

  $("#parks-filter-country").on("change", function () { updateParksBadge(); renderParks(); });

  $("#parks-sort-dir").on("click", function () {
    const $btn = $(this);
    const newDir = $btn.attr("data-dir") === "asc" ? "desc" : "asc";
    $btn.attr("data-dir", newDir);
    $btn.find("i").toggleClass("fa-caret-up", newDir === "asc").toggleClass("fa-caret-down", newDir === "desc");
    renderParks();
  });

  $("#parks-filters-toggle").on("click", function () {
    const $panel = $("#parks-filters-collapsible");
    const isOpen = $panel.hasClass("is-open");
    $panel.toggleClass("is-open", !isOpen);
    $(this).toggleClass("is-open", !isOpen).attr("aria-expanded", !isOpen);
  });

  // ── Limpiar filtros coasters ───────────────────────────────────────────────
  $("#btn-clear-coasters").on("click", function () {
    // Resetear selects
    $("#coasters-filter-park, #coasters-filter-country, #coasters-filter-manufacter, #coasters-filter-model")
      .val("").trigger("change");
    // Resetear switch
    $("#coasters-filter-operating").prop("checked", false);
    // Resetear sort a rank asc
    $("#coasters-sort").val("rank");
    setCoastersSortDir("rank");
    // Resetear sliders al rango completo
    ['slider-height','slider-speed','slider-length','slider-inversions','slider-year'].forEach(function(id) {
      const el = document.getElementById(id);
      if (el && el.noUiSlider) {
        const { min, max } = el.noUiSlider.options.range;
        el.noUiSlider.set([min, max]);
      }
    });
    updateCoastersBadge();
    renderCoasters();
  });

  // ── Limpiar filtros parques ────────────────────────────────────────────────
  $("#btn-clear-parks").on("click", function () {
    // Resetear selects
    $("#parks-filter-country").val("");
    // Resetear sort a rank asc
    $("#parks-sort").val("rank");
    setParksSortDir("rank");
    // Resetear slider
    const el = document.getElementById('slider-parks-coasters');
    if (el && el.noUiSlider) {
      const { min, max } = el.noUiSlider.options.range;
      el.noUiSlider.set([min, max]);
    }
    updateParksBadge();
    renderParks();
  });

  // ── Toggle de estadísticas en vista móvil ─────────────────────────────────────
  $(document).on("click", ".btn-toggle-stats", function (e) {
    e.preventDefault();
    const btn = $(this);
    const target = btn.closest(".top-card").find(".stats-expandable");

    if (target.hasClass("d-none")) {
      target.removeClass("d-none").addClass("d-flex").hide().slideDown(250, function () { $(this).css("display", ""); });
      btn.find("i").removeClass("fa-chevron-down").addClass("fa-chevron-up");
      btn.find("span").text("Contraer");
    } else {
      target.slideUp(250, function () { $(this).removeClass("d-flex").addClass("d-none").css("display", ""); });
      btn.find("i").removeClass("fa-chevron-up").addClass("fa-chevron-down");
      btn.find("span").text("Ver detalles");
    }
  });
});
