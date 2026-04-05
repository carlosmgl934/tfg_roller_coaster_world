/**
 * coaster_tops.js
 * Gestiona la página de Tops de la Comunidad (coasters).
 *
 * Flujo general:
 *  1. Al cargar: llama a fetchUserTops()
 *  2. Al cambiar el select de orden: vuelve a llamar a fetchUserTops()
 *  3. Al escribir en el buscador: filtra en cliente (sin nueva petición)
 *  4. Al activar "Solo amigos": llama a fetchUserTops() con filter=friends
 */

$(document).ready(function () {
  // ── Constantes ────────────────────────────────────────────────────────────
  const apiBase = (window.BASE_URL || "") + "/api/php/coasters.php";
  const defaultImg =
    (window.BASE_URL || "") + "/web/img/defaults/default_coaster.jpg";

  // ── Referencias al DOM ────────────────────────────────────────────────────
  const $grid = $("#tops-grid");
  const $search = $("#top-search");
  const $sortSelect = $("#sort-select");
  const $filterFriends = $("#filterFriends");

  // ── Estado ────────────────────────────────────────────────────────────────
  let allTops = []; // Guarda los datos de la última petición para filtrar sin recargar

  // ── Carga inicial ─────────────────────────────────────────────────────────
  fetchUserTops();

  // ── Eventos ───────────────────────────────────────────────────────────────

  // Cambio en el select de ordenación → nueva petición al servidor
  $sortSelect.on("change", function () {
    fetchUserTops();
  });

  // Cambio en el toggle de amigos → nueva petición al servidor
  $filterFriends.on("change", function () {
    fetchUserTops();
  });

  // Escritura en el buscador → filtrar en cliente (sin petición)
  $search.on("input", function () {
    const query = $(this).val().toLowerCase().trim();
    const filtered = allTops.filter((u) =>
      u.username.toLowerCase().includes(query),
    );
    renderUserTops(filtered);
  });

  // ── Funciones principales ─────────────────────────────────────────────────
  async function fetchUserTops() {
    showLoading();

    const sort = $sortSelect.val();
    let friends = "";
    if ($filterFriends.is(":checked")) {
      friends = "&filter=friends";
    }

    // URL con sort y filter
    let url = `${apiBase}?action=user_tops&sort=${sort}`;
    if (friends) url += "&filter=friends";

    try {
      const res = await fetch(url);
      const data = await res.json();

      if (data.success && data.data.length > 0) {
        allTops = data.data;
        renderUserTops(allTops);
      } else {
        // TODO: distinguir el mensaje si el filtro de amigos está activo
        showEmpty(
          "Ningún usuario ha creado todavía su top personal de coasters.",
        );
      }
    } catch (e) {
      console.error("Error cargando tops:", e);
      showError();
    }
  }

  function renderUserTops(users) {
    let html = "";

    users.forEach((user) => {
      // ── Avatar (lógica defensiva) ─────────────────────────────────
      const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.username)}&background=198754&color=fff`;
      const raw = user.profile_image;
      let avatar;
      if (!raw) {
        avatar = fallback;
      } else if (raw.startsWith("http://") || raw.startsWith("https://")) {
        avatar = raw;
      } else if (raw.startsWith("/")) {
        avatar = raw.includes("/web/img/uploads/") ? fallback : BASE_URL + raw;
      } else {
        avatar =
          "https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/" +
          raw;
      }

      // ── Filas de coasters ─────────────────────────────────────────
      let rowsHtml = "";
      user.top_coasters.forEach((c) => {
        const rowClass =
          c.rank_position === 1
            ? "rcw-rank-row rcw-rank-row--first"
            : "rcw-rank-row";
        const imgH = c.rank_position === 1 ? 50 : 46;
        const nameSize = c.rank_position <= 2 ? "0.9rem" : "0.85rem";
        rowsHtml += `
          <div class="d-flex align-items-center position-relative w-100 ${rowClass}">
            <div class="rcw-rank-badge rcw-rank-badge--${c.rank_position}">${c.rank_position}</div>
            <img src="${c.imagen_url || defaultImg}"
                 onerror="this.src='${defaultImg}'"
                 style="width:60px;height:${imgH}px;">
            <div class="ps-3 pe-2 py-1 flex-grow-1 min-w-0" style="padding-left:1.25rem !important;">
              <div class="text-white fw-bold text-truncate" style="font-size:${nameSize};">${c.coaster_name}</div>
              <div class="text-muted text-truncate" style="font-size:0.7rem;">
                <i class="fa-solid fa-location-dot me-1 text-success opacity-75"></i>${c.park_name}
              </div>
            </div>
          </div>`;
      });

      // ── Tarjeta completa ──────────────────────────────────────────
      const profileUrl = `${BASE_URL}/web/views/public/users/user_profile.php?id=${user.user_id}#tops`;
      html += `
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card h-100 bg-transparent border-0 rcw-top-card">
            <div class="card-body p-0 d-flex flex-column">

              <div class="d-flex align-items-center p-3 rcw-top-card-header">
                <img src="${avatar}"
                     onerror="this.src='${fallback}'"
                     class="rounded-circle object-fit-cover shadow-sm me-3"
                     style="width:48px;height:48px;border:2px solid var(--bs-success);">
                <div class="flex-grow-1 min-w-0">
                  <h5 class="fw-bold text-white mb-0 text-truncate" style="font-size:1rem;">Top de ${user.username}</h5>
                  <div class="d-flex align-items-baseline gap-2 mt-1" style="gap:0.4rem!important;">
                    <span style="font-size:1.25rem;font-weight:800;color:#39ff14;line-height:1;">${user.total_coasters}</span>
                    <span class="text-muted" style="font-size:0.72rem;">credits</span>
                    ${user.last_modified ? `<span class="text-muted" style="font-size:0.68rem;">· <i class='fa-solid fa-clock-rotate-left'></i> ${formatDate(user.last_modified)}</span>` : ''}
                  </div>
                </div>
              </div>

              <div class="flex-grow-1 p-3 d-flex flex-column gap-2 rcw-top-card-body">
                ${rowsHtml}
              </div>

              <a href="${profileUrl}" class="rcw-top-card-footer">
                <i class="fa-solid fa-eye me-1"></i> Ver top completo
              </a>

            </div>
          </div>
        </div>
      `;
    });

    $grid.html(html);
  }

  // ── Helper: formato de fecha legible ───────────────────────────────────────
  function formatDate(isoString) {
    if (!isoString) return '';
    const d = new Date(isoString);
    return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  // ── Helpers de estado del grid ────────────────────────────────────────────

  function showLoading() {
    $grid.html(`
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status"></div>
                <p class="mt-3 text-muted">Cargando tops...</p>
            </div>
        `);
  }

  function showEmpty(msg) {
    $grid.html(`<div class="col-12 text-center py-5 text-muted">${msg}</div>`);
  }

  function showError() {
    $grid.html(
      `<div class="col-12 text-center py-5 text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Error conectando con el servidor.</div>`,
    );
  }
});
