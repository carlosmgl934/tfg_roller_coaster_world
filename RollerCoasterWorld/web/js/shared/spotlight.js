/**
 * spotlight.js — Sección "AL AZAR" (Random Coaster + Park)
 * Depende de spotlight-card.js (debe cargarse antes con defer).
 * Usa window.RCWSpot.render() y window.RCWSpot.skeleton().
 */
(function () {
  "use strict";

  const API = window.BASE_URL + "/api/php/index.php?action=random";

  document.addEventListener("DOMContentLoaded", function () {
    var grid = document.getElementById("recs-grid");
    if (!grid) return;
    loadSpotlight();
    bindRefresh();
  });

  // ── Cargar datos ──────────────────────────────────────────────────────────────
  async function loadSpotlight() {
    var grid = document.getElementById("recs-grid");
    if (!grid) return;

    // Skeleton inmediato mientras se hace el fetch
    if (window.RCWSpot) {
      window.RCWSpot.skeleton(grid, 2);
    }

    try {
      var res  = await fetch(API, { credentials: "same-origin" });
      var json = await res.json();
      if (!json.success) throw new Error(json.error || "Error");

      var items = [];
      if (json.coaster) items.push({ type: "coaster", data: json.coaster });
      if (json.park)    items.push({ type: "park",    data: json.park    });

      if (window.RCWSpot && items.length) {
        window.RCWSpot.render(grid, items, { animate: true });
      } else {
        grid.innerHTML = errorHTML();
      }
    } catch (e) {
      console.error("[Spotlight]", e);
      grid.innerHTML = errorHTML();
    }
  }

  // ── Refresh ───────────────────────────────────────────────────────────────────
  function bindRefresh() {
    var btn = document.getElementById("recs-refresh-btn");
    if (!btn) return;
    btn.addEventListener("click", async function () {
      var grid = document.getElementById("recs-grid");
      // Mostrar skeleton al instante
      if (grid && window.RCWSpot) window.RCWSpot.skeleton(grid, 2);
      btn.classList.add("spinning");
      btn.disabled = true;
      await loadSpotlight();
      btn.classList.remove("spinning");
      btn.disabled = false;
    });
  }

  // ── HTML de error ─────────────────────────────────────────────────────────────
  function errorHTML() {
    return (
      '<div style="grid-column:1/-1;text-align:center;padding:2rem 0;color:#64748b;">' +
      '<i class="fa-solid fa-triangle-exclamation d-block mb-2" style="font-size:1.8rem;color:#f59e0b;"></i>' +
      '<span style="font-size:0.85rem;">No pudimos cargar el spotlight en este momento.</span></div>'
    );
  }
})();
