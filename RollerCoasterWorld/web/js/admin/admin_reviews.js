/* Admin Reviews Management JS */

let _reviewsPage = 1;
const _reviewsLimit = 10; //paginación
let _reviewSearch = "";
let _reviewType = "";
let _reviewSort = "recent";
let _allReviews = [];

// ── Toast helper (reemplaza los alert nativos) ──────────────────────────────
function _adminToast(msg, type = "success") {
  const toastEl = document.getElementById("admin-toast");
  const toastBody = document.getElementById("admin-toast-body");
  if (!toastEl || !toastBody) {
    alert(msg);
    return;
  }

  toastEl.className = "toast align-items-center text-white border-0 rounded-0";
  if (type === "success") toastEl.style.background = "#198754";
  else if (type === "danger") toastEl.style.background = "#dc3545";
  else toastEl.style.background = "#0d6efd";

  const icon =
    type === "success"
      ? '<i class="fa-solid fa-circle-check me-2"></i>'
      : '<i class="fa-solid fa-circle-exclamation me-2"></i>';
  toastBody.innerHTML = icon + msg;

  const toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 });
  toast.show();
}

// También exponerlo globalmente para que admin.js lo pueda usar
window.showModalNotification = (msg, type = "success") =>
  _adminToast(msg, type);

function _escRev(str) {
  if (str == null) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function _renderReviewsStarRating(note) {
  let html = "";
  const n = parseFloat(note) || 0;
  for (let i = 1; i <= 5; i++) {
    if (n >= i) {
      html += '<i class="fa-solid fa-star text-warning"></i>';
    } else if (n >= i - 0.5) {
      html += '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
    } else {
      html += '<i class="fa-regular fa-star text-warning"></i>';
    }
  }
  return `<span class="ms-2 badge bg-dark border border-secondary text-light">${html} <span class="ms-1 fw-bold">${n}</span></span>`;
}

function _highlight(text, search) {
  if (!search) return text;
  const safeSearch = search.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const regex = new RegExp(`(${safeSearch})`, "gi");
  return text.replace(
    regex,
    '<mark style="background-color: #ffc107; color: #000; padding: 0;">$1</mark>',
  );
}

function _renderReviewsTable(reviews) {
  const $list = $("#admin-reviews-list");
  $list.empty();

  if (!reviews || reviews.length === 0) {
    $list.html(`
            <div class="list-group-item text-center py-5 text-muted" style="background:#161b22; border-color:#30363d;">
                <i class="fa-solid fa-comment-slash fa-2x d-block mb-3" style="opacity:.3;"></i>
                No se encontraron reseñas con ese criterio.
            </div>
        `);
    return;
  }

  // Client-side pagination
  const start = (_reviewsPage - 1) * _reviewsLimit;
  const end = start + _reviewsLimit;
  const paginatedReviews = reviews.slice(start, end);

  paginatedReviews.forEach((rev) => {
    const defaultAvatar = `${window.BASE_URL}/web/img/avatars/default_avatar.svg`;
    const avatarSrc = rev.profile_image
      ? _escRev(rev.profile_image)
      : defaultAvatar;

    const defaultItemImg = `${window.BASE_URL}/web/img/placeholder.png`;
    const itemImgSrc = rev.item_image
      ? _escRev(rev.item_image)
      : defaultItemImg;

    const typeBadge =
      rev.type === "coaster"
        ? `<span class="badge text-uppercase fw-semibold" style="background:rgba(239,68,68,.18);color:#ef4444;border:1px solid rgba(239,68,68,.35);letter-spacing:.5px;">Montaña Rusa</span>`
        : `<span class="badge text-uppercase fw-semibold" style="background:rgba(59,130,246,.18);color:#60a5fa;border:1px solid rgba(59,130,246,.35);letter-spacing:.5px;">Parque</span>`;

    const dateStr = new Date(rev.created_at).toLocaleDateString("es-ES", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });

    let safeItemName = _escRev(rev.item_name);
    let safeUsername = _escRev(rev.username);
    let safeReviewText = _escRev(rev.review).replace(/\n/g, "<br>");

    if (_reviewSearch) {
      safeReviewText = _highlight(safeReviewText, _reviewSearch);
    }

    const isHidden =
      rev.is_hidden === true ||
      rev.is_hidden === "t" ||
      rev.is_hidden === "true";
    const opacityStyle = isHidden ? "opacity: 0.6;" : "";
    const hiddenBadge = isHidden
      ? `<span class="badge bg-secondary ms-2"><i class="fa-solid fa-eye-slash"></i> Oculta</span>`
      : "";

    const toggleHideBtn = isHidden
      ? `<button class="btn btn-sm btn-outline-info rounded-0 ms-1" title="Hacer visible" onclick="toggleReviewVisibility(${rev.id}, '${rev.type}', false)">
                 <i class="fa-solid fa-eye"></i> Mostrar
               </button>`
      : `<button class="btn btn-sm btn-outline-warning rounded-0 ms-1" title="Ocultar reseña" onclick="toggleReviewVisibility(${rev.id}, '${rev.type}', true)">
                 <i class="fa-solid fa-eye-slash"></i> Ocultar
               </button>`;

    const safeReviewRaw = _escRev(rev.review)
      .replace(/\\/g, "\\\\")
      .replace(/`/g, "'");

    const hasReviewText = rev.review && rev.review.trim() !== "";
    const deleteTextBtn = hasReviewText
      ? `<button class="btn btn-sm btn-outline-danger rounded-0" title="Borrar texto de la reseña (mantiene puntuación)"
                  onclick="openDeleteReviewModal(${rev.id}, '${rev.type}', \`${safeReviewRaw}\`)">
                  <i class="fa-solid fa-eraser"></i> Borrar Texto
              </button>`
      : "";

    $list.append(`
            <div class="list-group-item border-0 border-bottom px-3 px-md-4 py-3"
                 style="background:#161b22; border-color:#30363d !important; ${opacityStyle}">
                <div class="d-flex align-items-start gap-3">
                    <!-- Imagen del Item -->
                    <img src="${itemImgSrc}" class="rounded flex-shrink-0"
                         style="width:72px;height:72px;object-fit:cover;border:1px solid #30363d;"
                         onerror="this.src='${defaultItemImg}'">

                    <div class="flex-grow-1 overflow-hidden">
                        <!-- Fila 1: nombre (ancho completo, rompe correctamente) -->
                        <div class="fw-bold text-white mb-1" style="font-size:1rem; word-break:break-word; overflow-wrap:anywhere;">${safeItemName}</div>

                        <!-- Fila 2: badges (izq) | botones (der) -->
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2 flex-wrap">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                ${typeBadge}
                                ${_renderReviewsStarRating(rev.note)}
                                ${hiddenBadge}
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                ${deleteTextBtn}
                                ${toggleHideBtn}
                                <button class="btn btn-sm btn-danger rounded-0" title="Eliminar reseña permanentemente (borra puntuación del ranking)"
                                    onclick="openDestroyReviewModal(${rev.id}, '${rev.type}', '${safeUsername}', \`${safeReviewRaw}\`)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Fila 2: avatar + usuario + fecha -->
                        <div class="d-flex align-items-sm-center align-items-start gap-2 mb-2 text-muted small flex-column flex-sm-row">
                            <div class="d-flex align-items-center gap-2">
                                <img src="${avatarSrc}" class="rounded-circle flex-shrink-0"
                                     style="width:20px;height:20px;object-fit:cover;border:1px solid #198754;"
                                     onerror="this.src='${defaultAvatar}'">
                                <span class="fw-semibold text-light">${safeUsername}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="opacity-25 d-none d-sm-inline">&bull;</span>
                                <i class="fa-regular fa-calendar flex-shrink-0"></i>
                                <span class="text-nowrap">${dateStr}</span>
                            </div>
                        </div>

                        <!-- Fila 3: texto de la reseña (solo si existe) -->
                        ${
                          hasReviewText
                            ? `
                        <div class="p-2 rounded-1 text-light"
                             style="background:#0d1117; border:1px solid #30363d; font-size:0.9rem; line-height:1.5;">
                            ${safeReviewText}
                        </div>`
                            : ""
                        }
                    </div>
                </div>
            </div>
        `);
  });

  _renderReviewsPagination(reviews.length, _reviewsLimit);
}

window.toggleReviewVisibility = function (id, type, hide) {
  $.ajax({
    url: `${window.BASE_URL}/api/php/admin/admin_reviews.php?action=toggle_visibility`,
    method: "POST",
    headers: {
      "X-CSRF-Token":
        document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute("content") ?? "",
    },
    data: JSON.stringify({ id, type, hide }),
    contentType: "application/json",
    success(res) {
      if (res.success) {
        const idx = _allReviews.findIndex(
          (r) => r.id === id && r.type === type,
        );
        if (idx !== -1) {
          _allReviews[idx].is_hidden = hide;
        }
        _renderReviewsTable(_allReviews);
        _adminToast(
          hide ? "Reseña ocultada." : "Reseña visible de nuevo.",
          "success",
        );
      } else {
        _adminToast(res.error || "Error cambiando visibilidad", "danger");
      }
    },
    error() {
      _adminToast("Error de conexión", "danger");
    },
  });
};

function _renderReviewsPagination(total, limit) {
  const totalPages = Math.ceil(total / limit);
  const $nav = $("#admin-reviews-pagination");
  $nav.empty();
  if (totalPages <= 1) return;

  let html = '<ul class="pagination pagination-sm mb-0">';
  for (let i = 1; i <= totalPages; i++) {
    const active = i === _reviewsPage;
    html += `<li class="page-item ${active ? "active" : ""}">
            <a class="page-link rounded-0" href="#" data-page="${i}"
               style="${active ? "background:#198754;border-color:#198754;" : "background:#161b22;border-color:#30363d;color:#94a3b8;"}">${i}</a>
        </li>`;
  }
  html += "</ul>";
  $nav.html(html);

  $nav.find(".page-link").on("click", function (e) {
    e.preventDefault();
    _reviewsPage = parseInt($(this).data("page"));
    _renderReviewsTable(_allReviews);
  });
}

window.loadReviews = function () {
  const $list = $("#admin-reviews-list");
  $list.html(`
        <div class="list-group-item text-center py-5 text-muted" style="background:#161b22; border-color:#30363d;">
            <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando reseñas...
        </div>
    `);

  $.ajax({
    url:
      `${window.BASE_URL}/api/php/admin/admin_reviews.php?action=list_reviews` +
      `&search=${encodeURIComponent(_reviewSearch)}` +
      `&type=${encodeURIComponent(_reviewType)}` +
      `&sort=${encodeURIComponent(_reviewSort)}`,
    method: "GET",
    success(res) {
      if (res.success) {
        _allReviews = res.reviews;
        const maxPage = Math.ceil(_allReviews.length / _reviewsLimit) || 1;
        if (_reviewsPage > maxPage) _reviewsPage = maxPage;

        _renderReviewsTable(_allReviews);

        const n = _allReviews.length;
        $("#reviews-count").text(
          n === 1
            ? "1 reseña encontrada"
            : `${n.toLocaleString()} reseñas encontradas`,
        );
      } else {
        $list.html(`<div class="list-group-item text-center py-5 text-danger" style="background:#161b22; border-color:#30363d;">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>${res.error || "Error al cargar reseñas"}
                </div>`);
      }
    },
    error() {
      $list.html(`<div class="list-group-item text-center py-5 text-danger" style="background:#161b22; border-color:#30363d;">
                <i class="fa-solid fa-wifi me-2"></i>Error de conexión con la API
            </div>`);
    },
  });
};

// ── Borrar TEXTO de reseña (mantiene puntuación) ────────────────────────────
window.openDeleteReviewModal = function (id, type, reviewText) {
  $("#delete-review-text").html(reviewText.replace(/\n/g, "<br>"));
  $("#confirm-delete-review").data("id", id).data("type", type);
  new bootstrap.Modal($("#modal-delete-review")[0]).show();
};

// ── Eliminar reseña COMPLETA (borra fila y recalcula ranking) ───────────────
window.openDestroyReviewModal = function (id, type, username, reviewText) {
  $("#destroy-review-username").text(username);
  $("#destroy-review-text").html(reviewText.replace(/\n/g, "<br>"));
  $("#confirm-destroy-review").data("id", id).data("type", type);
  new bootstrap.Modal($("#modal-destroy-review")[0]).show();
};

$(document).ready(function () {
  loadReviews();

  let searchTimer;
  $("#review-search").on("input", function () {
    clearTimeout(searchTimer);
    _reviewSearch = $(this).val().trim();
    searchTimer = setTimeout(() => {
      _reviewsPage = 1;
      loadReviews();
    }, 450);
  });

  $("#btn-reviews-filtrar").on("click", function () {
    _reviewType = $("#filter-type").val();
    _reviewSort = $("#filter-sort").val();
    _reviewsPage = 1;
    loadReviews();
  });

  $("#btn-reviews-borrar").on("click", function () {
    $("#filter-type").val("");
    $("#filter-sort").val("recent");
    $("#review-search").val("");
    _reviewSearch = "";
    _reviewType = "";
    _reviewSort = "recent";
    _reviewsPage = 1;
    loadReviews();
  });

  // ── Confirmar: borrar solo texto ──────────────────────────────────────────
  $("#confirm-delete-review").on("click", function () {
    const id = $(this).data("id");
    const type = $(this).data("type");
    const $btn = $(this);
    const originalHtml = $btn.html();

    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Borrando...');

    $.ajax({
      url: `${window.BASE_URL}/api/php/admin/admin_reviews.php?action=delete_review`,
      method: "POST",
      headers: {
        "X-CSRF-Token":
          document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? "",
      },
      data: JSON.stringify({ id, type }),
      contentType: "application/json",
      success(res) {
        bootstrap.Modal.getInstance($("#modal-delete-review")[0]).hide();
        if (res.success) {
          loadReviews();
          _adminToast(
            "Texto de la reseña borrado. La puntuación se mantiene.",
            "success",
          );
        } else {
          _adminToast(res.error || "Error al eliminar.", "danger");
        }
      },
      error() {
        bootstrap.Modal.getInstance($("#modal-delete-review")[0])?.hide();
        _adminToast("Error de conexión al borrar reseña.", "danger");
      },
      complete() {
        $btn.prop("disabled", false).html(originalHtml);
      },
    });
  });

  // ── Confirmar: eliminar reseña completa ───────────────────────────────────
  $("#confirm-destroy-review").on("click", function () {
    const id = $(this).data("id");
    const type = $(this).data("type");
    const $btn = $(this);
    const originalHtml = $btn.html();

    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Eliminando...');

    $.ajax({
      url: `${window.BASE_URL}/api/php/admin/admin_reviews.php?action=destroy_review`,
      method: "POST",
      headers: {
        "X-CSRF-Token":
          document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") ?? "",
      },
      data: JSON.stringify({ id, type }),
      contentType: "application/json",
      success(res) {
        bootstrap.Modal.getInstance($("#modal-destroy-review")[0]).hide();
        if (res.success) {
          // Quitar de la lista local y rerenderizar sin recargar
          _allReviews = _allReviews.filter(
            (r) => !(r.id === id && r.type === type),
          );
          const maxPage = Math.ceil(_allReviews.length / _reviewsLimit) || 1;
          if (_reviewsPage > maxPage) _reviewsPage = maxPage;
          _renderReviewsTable(_allReviews);
          const n = _allReviews.length;
          $("#reviews-count").text(
            n === 1
              ? "1 reseña encontrada"
              : `${n.toLocaleString()} reseñas encontradas`,
          );
          _adminToast(
            "Reseña eliminada permanentemente. El ranking ha sido recalculado.",
            "success",
          );
        } else {
          _adminToast(res.error || "Error al eliminar.", "danger");
        }
      },
      error() {
        bootstrap.Modal.getInstance($("#modal-destroy-review")[0])?.hide();
        _adminToast("Error de conexión al eliminar reseña.", "danger");
      },
      complete() {
        $btn.prop("disabled", false).html(originalHtml);
      },
    });
  });
});
