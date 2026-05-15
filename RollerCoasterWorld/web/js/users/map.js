$(document).ready(function () {
  // ══════════════════════════════════════════════════════════════════
  //  MI MAPA — Leaflet.js + Nominatim geocoding
  // ══════════════════════════════════════════════════════════════════

  let mapInstance = null;
  let mapInitialized = false;
  let mapMarkerCluster = null;

  function createParkIcon(coasterCount) {
    const color = "#00c853";
    const size = coasterCount >= 5 ? 36 : coasterCount >= 2 ? 32 : 28;
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size + 8}" viewBox="0 0 36 44">
      <circle cx="18" cy="18" r="16" fill="${color}" fill-opacity="0.2" stroke="${color}" stroke-width="2"/>
      <circle cx="18" cy="18" r="9" fill="${color}"/>
      <line x1="18" y1="34" x2="18" y2="42" stroke="${color}" stroke-width="2" stroke-linecap="round"/>
    </svg>`;
    return L.divIcon({
      html: `<div class="rcw-map-marker">${svg}</div>`,
      className: "",
      iconSize: [size, size + 8],
      iconAnchor: [size / 2, size + 8],
      popupAnchor: [0, -(size + 4)],
    });
  }

  function buildPopupHtml(park) {
    const imgUrl = park.imagen_url
      ? park.imagen_url.startsWith("/")
        ? BASE_URL + park.imagen_url
        : park.imagen_url
      : null;
    const imgBlock = imgUrl
      ? `<img src="${imgUrl}" alt="${park.park_name}" class="rcw-map-popup-img" onerror="this.style.display='none'">`
      : `<div class="rcw-map-popup-img-placeholder"><i class="fa-solid fa-image"></i></div>`;
    const meta = [park.park_location, park.park_country]
      .filter(Boolean)
      .join(", ");
    const detailUrl = `${BASE_URL}/web/views/public/parks/parks.php?id=${park.park_id}`;
    const count = parseInt(park.coaster_count) || 1;
    return `
      <div class="rcw-map-popup">
        ${imgBlock}
        <div class="rcw-map-popup-body">
          <div class="rcw-map-popup-name">${park.park_name}</div>
          <div class="rcw-map-popup-meta">${meta}</div>
          <div class="rcw-map-popup-badge">
            <i class="fa-solid fa-ticket"></i> ${count} coaster${count !== 1 ? "s" : ""} en tu top
          </div>
        </div>
        <a href="${detailUrl}" class="rcw-map-popup-link">
          Ver parque <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
      </div>`;
  }

  // TTL de la cache de geocoding: 30 dias (en ms)
  const GEOCODE_TTL = 30 * 24 * 60 * 60 * 1000;

  async function geocodePark(park) {
    const cacheKey = `rcw_geocode_${park.park_id}`;

    // Intentar leer de localStorage (persiste entre sesiones)
    try {
      const raw = localStorage.getItem(cacheKey);
      if (raw) {
        const entry = JSON.parse(raw);
        // Validar TTL: si han pasado menos de 30 dias, usar la cache
        if (entry.ts && Date.now() - entry.ts < GEOCODE_TTL) {
          return { lat: entry.lat, lng: entry.lng };
        }
        // Expirada: eliminar
        localStorage.removeItem(cacheKey);
      }
    } catch (_) {}

    // No hay cache valida -> llamar a Nominatim
    const nominatimFetch = async (q) => {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=1`,
        {
          headers: {
            "Accept-Language": "es",
            "User-Agent": "RollerCoasterWorld/TFG",
          },
        },
      );
      return res.json();
    };

    try {
      // Intento 1: nombre + localización + país (más preciso)
      const query1 = [park.park_name, park.park_location, park.park_country]
        .filter(Boolean)
        .join(", ");
      let data = await nominatimFetch(query1);

      // Intento 2: localización + país (geográfico, seguro)
      // Tiene prioridad sobre "nombre + país" para evitar coincidencias en otras regiones
      if ((!data || !data.length) && park.park_location && park.park_country) {
        await new Promise((r) => setTimeout(r, 1100));
        data = await nominatimFetch(
          [park.park_location, park.park_country].filter(Boolean).join(", "),
        );
      }

      // Intento 3: nombre + país — SOLO si no hay localización
      // (si hay localización ya se buscó geográficamente en intento 2)
      if ((!data || !data.length) && park.park_country && !park.park_location) {
        await new Promise((r) => setTimeout(r, 1100));
        data = await nominatimFetch(
          [park.park_name, park.park_country].filter(Boolean).join(", "),
        );
      }

      if (data && data.length > 0) {
        const coords = {
          lat: parseFloat(data[0].lat),
          lng: parseFloat(data[0].lon),
          ts: Date.now(),
        };
        try {
          localStorage.setItem(cacheKey, JSON.stringify(coords));
        } catch (_) {}
        return { lat: coords.lat, lng: coords.lng };
      }
    } catch (_) {}
    return null;
  }

  // Devuelve las coords del localStorage sin llamar a la red (null si no hay cache valida)
  function getCachedCoords(parkId) {
    try {
      const raw = localStorage.getItem(`rcw_geocode_${parkId}`);
      if (!raw) return null;
      const entry = JSON.parse(raw);
      if (entry.ts && Date.now() - entry.ts < GEOCODE_TTL) {
        return { lat: entry.lat, lng: entry.lng };
      }
    } catch (_) {}
    return null;
  }

  function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
  }

  async function initMap() {
    if (mapInitialized) {
      if (mapInstance) mapInstance.invalidateSize();
      return;
    }
    mapInitialized = true;

    mapInstance = L.map("profile-map", {
      center: [20, 0],
      zoom: 2,
      zoomControl: true,
    });

    // ── Control de pantalla completa (Fullscreen API nativa) ──────
    const FullscreenControl = L.Control.extend({
      options: { position: "topleft" },
      onAdd: function () {
        const btn = L.DomUtil.create(
          "button",
          "leaflet-bar leaflet-control rcw-fullscreen-btn",
        );
        btn.innerHTML = '<i class="fa-solid fa-expand"></i>';
        btn.title = "Pantalla completa";
        btn.setAttribute("type", "button");
        L.DomEvent.disableClickPropagation(btn);
        L.DomEvent.on(btn, "click", function () {
          const mapEl = document.getElementById("profile-map");
          const canFs = !!(
            mapEl.requestFullscreen || mapEl.webkitRequestFullscreen
          );
          const isFs = !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            mapEl.classList.contains("is-fullscreen")
          );

          if (!isFs) {
            if (canFs) {
              (mapEl.requestFullscreen || mapEl.webkitRequestFullscreen).call(
                mapEl,
              );
            } else {
              // Fallback para móviles que no soportan Fullscreen API nativa
              mapEl.classList.add("is-fullscreen");
              document.body.style.overflow = "hidden";
              if (mapInstance) mapInstance.invalidateSize();
            }
            btn.innerHTML = '<i class="fa-solid fa-compress"></i>';
            btn.title = "Salir de pantalla completa";
          } else {
            if (
              document.fullscreenElement ||
              document.webkitFullscreenElement
            ) {
              (document.exitFullscreen || document.webkitExitFullscreen).call(
                document,
              );
            }
            mapEl.classList.remove("is-fullscreen");
            document.body.style.overflow = "";
            if (mapInstance) mapInstance.invalidateSize();
            btn.innerHTML = '<i class="fa-solid fa-expand"></i>';
            btn.title = "Pantalla completa";
          }
        });

        // Asegurar limpieza de estilos si se sale por escape (nativo)
        document.addEventListener("fullscreenchange", function () {
          if (
            !document.fullscreenElement &&
            !document.webkitFullscreenElement
          ) {
            btn.innerHTML = '<i class="fa-solid fa-expand"></i>';
            btn.title = "Pantalla completa";
            const mapEl = document.getElementById("profile-map");
            if (mapEl) mapEl.classList.remove("is-fullscreen");
            document.body.style.overflow = "";
            if (mapInstance) mapInstance.invalidateSize();
          }
        });

        // Soporte tecla ESC para el modo fallback
        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape") {
            const mapEl = document.getElementById("profile-map");
            if (mapEl && mapEl.classList.contains("is-fullscreen")) {
              mapEl.classList.remove("is-fullscreen");
              document.body.style.overflow = "";
              btn.innerHTML = '<i class="fa-solid fa-expand"></i>';
              if (mapInstance) mapInstance.invalidateSize();
            }
          }
        });
        return btn;
      },
    });
    new FullscreenControl().addTo(mapInstance);

    L.tileLayer(
      "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",
      {
        attribution:
          '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
        subdomains: "abcd",
        maxZoom: 19,
      },
    ).addTo(mapInstance);

    mapMarkerCluster = L.markerClusterGroup({
      showCoverageOnHover: false,
      maxClusterRadius: 60,
      spiderfyOnMaxZoom: true,
    });
    mapInstance.addLayer(mapMarkerCluster);

    // Fetch lista de parques del usuario
    let parks = [];
    try {
      const res = await fetch(
        `${BASE_URL}/api/php/profile_config.php?action=get_map_parks`,
      );
      const data = await res.json();
      parks = data.success && Array.isArray(data.parks) ? data.parks : [];
    } catch (e) {
      console.error("Error cargando parques del mapa:", e);
    }

    if (!parks.length) {
      $("#profile-map").addClass("d-none");
      $("#map-empty-state").removeClass("d-none");
      return;
    }

    $("#map-parks-count").text(parks.length);
    $("#map-parks-pill").show();

    // ── Paso 1: parques YA en localStorage → marcadores instantaneos ──
    const cached = [];
    const toGeocode = [];
    const bounds = [];

    for (const park of parks) {
      const coords = getCachedCoords(park.park_id);
      if (coords) {
        cached.push({ park, coords });
      } else {
        toGeocode.push(park);
      }
    }

    // Añadir marcadores cacheados al instante
    for (const { park, coords } of cached) {
      bounds.push([coords.lat, coords.lng]);
      const marker = L.marker([coords.lat, coords.lng], {
        icon: createParkIcon(parseInt(park.coaster_count) || 1),
      });
      marker.bindPopup(buildPopupHtml(park), {
        maxWidth: 260,
        className: "rcw-leaflet-popup",
      });
      mapMarkerCluster.addLayer(marker);
    }

    // Ajustar vista con lo que ya tenemos (respuesta inmediata)
    if (bounds.length > 0) {
      mapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 10 });
    }
    mapInstance.invalidateSize();

    // ── Paso 2: parques SIN cache → geocodificar con Nominatim ────────
    if (toGeocode.length > 0) {
      const $bar = $("#map-geocoding-bar").removeClass("d-none");
      const $status = $("#map-geocoding-status");
      const $progress = $("#map-geocoding-progress");
      const $pbBar = $("#map-geocoding-progressbar");
      const total = toGeocode.length;
      let done = 0;

      for (const park of toGeocode) {
        $status.text(`Localizando "${park.park_name}"...`);
        $progress.text(`${done} / ${total}`);
        $pbBar.css("width", `${Math.round((done / total) * 100)}%`);

        const coords = await geocodePark(park); // llama Nominatim + guarda en localStorage
        done++;

        if (coords) {
          bounds.push([coords.lat, coords.lng]);
          const marker = L.marker([coords.lat, coords.lng], {
            icon: createParkIcon(parseInt(park.coaster_count) || 1),
          });
          marker.bindPopup(buildPopupHtml(park), {
            maxWidth: 260,
            className: "rcw-leaflet-popup",
          });
          mapMarkerCluster.addLayer(marker);
        }

        if (done < total) await sleep(1100);
      }

      $bar.addClass("d-none");
      $progress.text(`${done} / ${total}`);
      $pbBar.css("width", "100%");

      // Re-ajustar viewport ahora que tenemos todos los marcadores
      if (bounds.length > 0) {
        mapInstance.fitBounds(bounds, { padding: [40, 40], maxZoom: 10 });
      }
      mapInstance.invalidateSize();
    }
  }

  $("#menu-map").on("click", function () {
    setTimeout(() => initMap(), 80);
  });

  if (window.location.hash === "#map") {
    setTimeout(() => initMap(), 200);
  }
  if (window.location.hash === "#trips") {
    if (typeof loadTrips === "function") loadTrips();
    if (typeof loadRanking === "function") loadRanking();
  }
});
