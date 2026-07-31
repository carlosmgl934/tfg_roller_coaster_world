/**
 * recommendations.js
 * Recomendador Inteligente Autónomo — Zero-Click
 *
 * Flujo:
 * 1. Al cargar el dashboard, llama a la API /recommendations?action=get
 * 2. Renderiza la sección "Especialmente para ti" con 3 cards
 * 3. Al hacer clic en una card → abre el modal de checkout pre-configurado
 * 4. El usuario puede ajustar cantidad y fechas
 * 5. Al confirmar → POST /book (crea pedido) → POST /confirm (genera agenda)
 */

(function () {
  "use strict";

  const API = window.BASE_URL + "/api/php/recommendations.php";

  // ── Estado global del módulo ──────────────────────────────────────────────
  let currentRec = null; // Recomendación actualmente en el modal
  let currentQty = 1; // Cantidad de entradas
  let currentOrderId = null; // ID del pedido creado
  let modalEl = null; // Instancia Bootstrap del modal

  // ── Bootstrap init ────────────────────────────────────────────────────────
  // Activo en cualquier página que tenga #recs-grid (dashboard Y generador de viajes)
  document.addEventListener("DOMContentLoaded", () => {
    const grid = document.getElementById("recs-grid");
    if (!grid) return;

    loadRecommendations();
    bindRefreshBtn();

    // ── Auto-confirmación al volver de Stripe ─────────────────────────────────
    // Si el servidor inyectó STRIPE_RETURN con status=success, confirmamos el viaje
    if (
      window.STRIPE_RETURN &&
      window.STRIPE_RETURN.status === "success" &&
      window.STRIPE_RETURN.session_id &&
      window.STRIPE_RETURN.order_id
    ) {
      autoConfirmAfterStripe(window.STRIPE_RETURN);
    }
  });

  // ═════════════════════════════════════════════════════════════════════════
  // 1. CARGA Y RENDERIZADO
  // ═════════════════════════════════════════════════════════════════════════
  async function loadRecommendations(forceRefresh = false) {
    showSkeletons();
    try {
      const url = API + (forceRefresh ? "?action=refresh" : "?action=get");
      const resp = await fetch(url, { credentials: "same-origin" });
      const json = await resp.json();

      if (!json.success || !Array.isArray(json.data)) {
        showError("No pudimos generar recomendaciones en este momento.");
        return;
      }
      renderCards(json.data);
    } catch (e) {
      console.error("[Recommendations]", e);
      showError("Error de conexión al cargar recomendaciones.");
    }
  }

  // ── Skeleton mientras carga ───────────────────────────────────────────────
  function showSkeletons() {
    const grid = document.getElementById("recs-grid");
    if (!grid) return;
    grid.innerHTML = [1, 2, 3]
      .map(
        () => `
            <div class="rcw-rec-skeleton">
                <div class="sk-img"></div>
                <div class="sk-body">
                    <div class="sk-line w-80"></div>
                    <div class="sk-line w-60"></div>
                    <div class="sk-line w-40"></div>
                    <div class="sk-line w-80"></div>
                </div>
            </div>
        `,
      )
      .join("");
  }

  function showError(msg) {
    const grid = document.getElementById("recs-grid");
    if (!grid) return;
    grid.innerHTML = `
            <div class="col-span-3" style="grid-column:1/-1;text-align:center;padding:2rem;color:#94a3b8;">
                <i class="fa-solid fa-triangle-exclamation mb-2 d-block" style="font-size:2rem;color:#f59e0b;"></i>
                ${msg}
            </div>`;
  }

  // ── Renderizar cards ──────────────────────────────────────────────────────
  function renderCards(recs) {
    const grid = document.getElementById("recs-grid");
    if (!grid) return;

    grid.innerHTML = recs.map((rec, i) => buildCard(rec, i)).join("");

    // Bind click en cada card
    grid.querySelectorAll(".rcw-rec-card").forEach((card) => {
      card.addEventListener("click", () => {
        const idx = parseInt(card.dataset.idx, 10);
        openCheckout(recs[idx]);
      });
    });
  }

  // ── Construye HTML de un card ─────────────────────────────────────────────
  function buildCard(rec, idx) {
    const stars = renderStars(parseFloat(rec.hotel_stars || 3), 5);
    const typeLabel =
      rec.rec_type === "wildcard" ? "Descubrimiento" : "Recomendado";
    const affinityPct = Math.round((parseFloat(rec.affinity_score) || 0) * 100);
    const priceText = rec.price_estimate
      ? parseFloat(rec.price_estimate).toFixed(0) + "€/pers."
      : "Precio a consultar";
    const imgHtml = rec.park_image_url
      ? `<img class="rcw-rec-img" src="${escHtml(rec.park_image_url)}"
                    alt="${escHtml(rec.park_name)}"
                    onerror="this.outerHTML='<div class=\\'rcw-rec-img-placeholder\\'><i class=\\'fa-solid fa-tree-city\\'></i></div>'">`
      : `<div class="rcw-rec-img-placeholder"><i class="fa-solid fa-tree-city"></i></div>`;
    const hotelStarsHtml = renderStars(rec.hotel_stars, 5, true);

    return `
        <div class="rcw-rec-card" data-idx="${idx}" role="button" tabindex="0"
             aria-label="Ver recomendación: ${escHtml(rec.park_name)}">

            <!-- Imagen -->
            ${imgHtml}

            <!-- Badges -->
            <span class="rcw-rec-type-badge ${rec.rec_type}">${typeLabel}</span>
            <span class="rcw-rec-affinity">${affinityPct}% afinidad</span>

            <!-- Cuerpo -->
            <div class="rcw-rec-body">
                <div class="rcw-rec-park-name">${escHtml(rec.park_name)}</div>
                <div class="rcw-rec-country">
                    <i class="fa-solid fa-location-dot" style="color:var(--rcw-green-neon);"></i>
                    ${escHtml(rec.park_country || "—")}
                </div>

                <!-- Razón IA -->
                <div class="rcw-rec-reason">${escHtml(rec.reason)}</div>

                <!-- Detalles hotel + precio -->
                <div class="rcw-rec-details">
                    <div class="rcw-rec-detail-row">
                        <span><i class="fa-solid fa-hotel me-1"></i>${escHtml(rec.hotel_name || "Hotel sugerido")}</span>
                        <span class="rcw-rec-stars">${hotelStarsHtml}</span>
                    </div>
                    <!-- HIDDEN-TFG-START -->
                    <div class="rcw-rec-detail-row hidden-tfg">
                        <span><i class="fa-solid fa-ticket me-1"></i>Entrada</span>
                        <strong>${priceText}</strong>
                    </div>
                    <!-- HIDDEN-TFG-END -->
                    <div class="rcw-rec-detail-row">
                        <span><i class="fa-solid fa-calendar-days me-1"></i>Duración sugerida</span>
                        <strong>${rec.duration_days} días</strong>
                    </div>
                </div>
            </div>

        </div>`;
  }

  // ═════════════════════════════════════════════════════════════════════════
  // 2. MODAL CHECKOUT
  // ═════════════════════════════════════════════════════════════════════════
  function openCheckout(rec) {
    currentRec = rec;
    currentQty = 1;
    currentOrderId = null;

    ensureModal();
    populateCheckout(rec);

    if (!modalEl) {
      modalEl = new bootstrap.Modal(
        document.getElementById("rec-checkout-modal"),
      );
    }
    modalEl.show();
  }

  function ensureModal() {
    if (document.getElementById("rec-checkout-modal")) return;

    const div = document.createElement("div");
    div.innerHTML = `
        <div class="modal fade" id="rec-checkout-modal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">

              <div class="modal-header border-0 px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="fa-solid fa-wand-magic-sparkles text-success fs-5"></i>
                  <h5 class="modal-title fw-bold mb-0 text-white" id="checkout-modal-title">Reservar viaje</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body px-4 py-3" id="checkout-modal-body">
                <!-- Poblado por JS -->
              </div>

              <div class="modal-footer border-0 px-4 pb-4 pt-0" id="checkout-modal-footer">
                <!-- Poblado por JS -->
              </div>

            </div>
          </div>
        </div>`;
    document.body.appendChild(div.firstElementChild);
  }

  function populateCheckout(rec) {
    const body = document.getElementById("checkout-modal-body");
    const footer = document.getElementById("checkout-modal-footer");
    const title = document.getElementById("checkout-modal-title");
    if (!body || !footer) return;

    title.textContent = `Reservar: ${rec.park_name}`;

    const hotelStarsHtml = renderStars(rec.hotel_stars, 5, true);
    const unitPrice = parseFloat(rec.price_estimate || 50);
    const hotelNight = parseFloat(rec.hotel_price_night || 80);
    const totalDays = parseInt(rec.duration_days, 10) || 2;

    // Fecha de inicio por defecto: 14 días desde hoy
    const defaultStart = new Date();
    defaultStart.setDate(defaultStart.getDate() + 14);
    const startStr = defaultStart.toISOString().split("T")[0];

    body.innerHTML = `
        <!-- Razón IA -->
        <div class="rcw-rec-reason mb-3">${escHtml(rec.reason)}</div>

        <!-- Detalles en tabla -->
        <div class="mb-3">
            <div class="checkout-detail-row">
                <span><i class="fa-solid fa-tree-city me-2 text-success"></i>Parque</span>
                <strong>${escHtml(rec.park_name)}</strong>
            </div>
            <div class="checkout-detail-row">
                <span><i class="fa-solid fa-location-dot me-2 text-success"></i>País</span>
                <span>${escHtml(rec.park_country || "—")}</span>
            </div>
            <div class="checkout-detail-row">
                <span><i class="fa-solid fa-ticket me-2 text-success"></i>Precio entrada / pers.</span>
                <strong>${unitPrice.toFixed(2)}€</strong>
            </div>
            <div class="checkout-detail-row">
                <span><i class="fa-solid fa-hotel me-2 text-success"></i>Hotel sugerido</span>
                <span>${escHtml(rec.hotel_name)} ${hotelStarsHtml}</span>
            </div>
            <div class="checkout-detail-row">
                <span><i class="fa-solid fa-moon me-2 text-success"></i>Precio hotel / noche</span>
                <strong>${hotelNight.toFixed(2)}€</strong>
            </div>
            <div class="checkout-detail-row">
                <span><i class="fa-solid fa-calendar-days me-2 text-success"></i>Duración sugerida</span>
                <span>${totalDays} días</span>
            </div>
        </div>

        <!-- Controles editables -->
        <div class="row g-3 mb-3">
            <div class="col-12 col-sm-6">
                <label class="form-label small fw-semibold text-secondary">
                    <i class="fa-solid fa-users me-1"></i>Nº de personas
                </label>
                <div class="rcw-qty-control">
                    <button type="button" id="qty-minus" aria-label="Reducir cantidad">−</button>
                    <span class="qty-val" id="qty-display">1</span>
                    <button type="button" id="qty-plus" aria-label="Aumentar cantidad">+</button>
                </div>
            </div>
            <div class="col-12 col-sm-6">
                <label class="form-label small fw-semibold text-secondary" for="checkout-start-date">
                    <i class="fa-solid fa-calendar me-1"></i>Fecha de inicio
                </label>
                <input type="date" class="form-control form-control-sm" id="checkout-start-date"
                       value="${startStr}" min="${startStr}">
            </div>
        </div>

        <!-- Total estimado -->
        <div class="d-flex justify-content-between align-items-center p-3 rounded-2"
             style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);">
            <span class="fw-semibold text-white">Total estimado</span>
            <span class="checkout-total" id="checkout-total-display">${unitPrice.toFixed(2)}€</span>
        </div>

        <p class="small text-muted mt-2 mb-0">
            <i class="fa-solid fa-circle-info me-1"></i>
            Precio estimado = entradas × personas. El hotel se gestiona aparte con el proveedor.
        </p>`;

    footer.innerHTML = `
        <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success rounded-0 fw-bold px-5" id="checkout-confirm-btn">
            <i class="fa-brands fa-stripe me-2"></i>Pagar con Stripe
        </button>`;

    // Nota modo test
    document.getElementById("checkout-modal-body").insertAdjacentHTML(
      "beforeend",
      `
        <div class="mt-3 p-3 rounded-2" style="background:rgba(245,158,11,0.08);border-left:3px solid #f59e0b;">
            <small class="text-warning fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>MODO TEST</small>
            <small class="text-muted d-block mt-1">Este pago es totalmente falso, se hace con dinero irreal, no te preocupes por gastarlo</small>
            <small class="text-muted d-block mt-1">Usar tarjeta: <span class="fw-bold text-white font-monospace">4242 4242 4242 4242</span> &middot; Fecha: 12/30 &middot; CVC: 123</small>
        </div>`,
    );

    // Bind controles
    bindQtyControls(unitPrice);
    document
      .getElementById("checkout-confirm-btn")
      .addEventListener("click", handleCheckoutConfirm);
  }

  function bindQtyControls(unitPrice) {
    const display = document.getElementById("qty-display");
    const total = document.getElementById("checkout-total-display");

    const update = () => {
      display.textContent = currentQty;
      total.textContent = (unitPrice * currentQty).toFixed(2) + "€";
    };

    document.getElementById("qty-minus").addEventListener("click", () => {
      if (currentQty > 1) {
        currentQty--;
        update();
      }
    });
    document.getElementById("qty-plus").addEventListener("click", () => {
      if (currentQty < 20) {
        currentQty++;
        update();
      }
    });
  }

  // ═════════════════════════════════════════════════════════════════════════
  // 3. CONFIRM → BOOK → STRIPE → (retorno) → SCHEDULE
  // ═════════════════════════════════════════════════════════════════════════
  async function handleCheckoutConfirm() {
    const btn = document.getElementById("checkout-confirm-btn");
    if (!btn || !currentRec) return;

    btn.disabled = true;
    btn.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2"></span>Creando pedido…';

    try {
      // Paso A: Crear / actualizar pedido pendiente
      const bookResp = await fetch(API + "?action=book", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "X-CSRF-Token":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") ?? "",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          park_id: currentRec.park_id,
          quantity: currentQty,
        }),
      });
      const bookJson = await bookResp.json();
      if (!bookJson.success)
        throw new Error(bookJson.error || "Error al crear el pedido");

      currentOrderId = bookJson.data.order_id;
      const startDate =
        document.getElementById("checkout-start-date")?.value || "";

      // Paso B: Crear Stripe Checkout Session para el viaje
      btn.innerHTML =
        '<span class="spinner-border spinner-border-sm me-2"></span>Abriendo pasarela de pago…';

      const sessionResp = await fetch(API + "?action=create_trip_session", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "X-CSRF-Token":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") ?? "",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          park_id: currentRec.park_id,
          order_id: currentOrderId,
          quantity: currentQty,
          duration_days: currentRec.duration_days,
          start_date: startDate,
        }),
      });
      const sessionJson = await sessionResp.json();
      if (!sessionJson.success)
        throw new Error(sessionJson.error || "Error al iniciar el pago");

      // Paso C: Redirigir a Stripe (la página abandonará el modal)
      // Response::success fusiona los campos en la raíz → sessionJson.url (no .data.url)
      window.location.href = sessionJson.url;
    } catch (err) {
      console.error("[Checkout Stripe]", err);
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-brands fa-stripe me-2"></i>Reintentar pago';
      showToast("Error: " + err.message, "danger");
    }
  }

  // ── Auto-confirmación al volver de Stripe (success) ──────────────────────────
  async function autoConfirmAfterStripe(ret) {
    showStripeVerifyingOverlay();
    try {
      const confirmResp = await fetch(API + "?action=confirm", {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "X-CSRF-Token":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") ?? "",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          order_id: ret.order_id,
          park_id: ret.park_id,
          duration_days: ret.duration,
          start_date: ret.start_date,
          stripe_session_id: ret.session_id,
        }),
      });
      const confirmJson = await confirmResp.json();
      if (!confirmJson.success)
        throw new Error(confirmJson.error || "Error al confirmar el viaje");

      hideStripeVerifyingOverlay();
      openSuccessModalFromReturn(confirmJson.data);
    } catch (err) {
      console.error("[AutoConfirm]", err);
      hideStripeVerifyingOverlay();
      showToast(
        "Pago recibido pero hubo un error al crear el viaje: " + err.message,
        "danger",
      );
    }
  }

  // ── Overlay de verificación ───────────────────────────────────────────────────
  function showStripeVerifyingOverlay() {
    let ov = document.getElementById("rcw-stripe-verifying");
    if (!ov) {
      ov = document.createElement("div");
      ov.id = "rcw-stripe-verifying";
      ov.style.cssText =
        "position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:1rem;";
      ov.innerHTML = `
        <div class="spinner-border text-success" style="width:3.5rem;height:3.5rem;"></div>
        <p class="text-white fw-semibold mb-0" style="font-size:1.1rem;">
          <i class="fa-brands fa-stripe me-2 text-primary"></i>Verificando pago y creando tu viaje…
        </p>
        <small class="text-muted">Por favor, no cierres esta página</small>`;
      document.body.appendChild(ov);
    }
    ov.style.display = "flex";
  }

  function hideStripeVerifyingOverlay() {
    const ov = document.getElementById("rcw-stripe-verifying");
    if (ov) ov.style.display = "none";
  }

  // ── Modal de éxito tras auto-confirmar ────────────────────────────────────────
  function openSuccessModalFromReturn(data) {
    ensureModal();
    if (!modalEl) {
      modalEl = new bootstrap.Modal(
        document.getElementById("rec-checkout-modal"),
      );
    }
    showPaymentSuccess(data);
    modalEl.show();
  }

  // ── Pantalla de éxito dentro del modal ────────────────────────────────────
  function showPaymentSuccess(data) {
    const body = document.getElementById("checkout-modal-body");
    const footer = document.getElementById("checkout-modal-footer");
    const title = document.getElementById("checkout-modal-title");
    if (!body) return;

    title.textContent = "¡Reserva Confirmada!";

    const itineraryHtml = (data.itinerary || [])
      .map(
        (day) => `
            <div class="rcw-itinerary-day">
                <h6>${escHtml(day.day)} — ${escHtml(day.title)}</h6>
                <ul>${(day.items || []).map((it) => `<li>${escHtml(it)}</li>`).join("")}</ul>
            </div>`,
      )
      .join("");

    body.innerHTML = `
        <div class="rcw-payment-success">
            <div class="success-icon"><i class="fa-solid fa-check"></i></div>
            <h5 class="fw-bold text-white mb-1">${escHtml(data.trip_title || "Viaje reservado")}</h5>
            <p class="text-muted small mb-3">
                ${escHtml(data.start_date || "")} → ${escHtml(data.end_date || "")}
            </p>
            <div class="alert alert-success rounded-2 text-start mb-3" role="alert">
                <i class="fa-solid fa-calendar-check me-2"></i>
                Tu viaje ha sido añadido a <strong>Mi Agenda de Parques</strong>
            </div>
        </div>
        <div class="mb-2">
            <p class="text-white fw-semibold mb-2">
                <i class="fa-solid fa-map-location-dot me-1 text-success"></i>
                Itinerario sugerido por la IA
            </p>
            ${itineraryHtml}
        </div>`;

    footer.innerHTML = `
        <a href="${window.BASE_URL}/web/views/public/trips/trips.php"
           class="btn btn-success rounded-0 fw-bold px-5">
            <i class="fa-solid fa-calendar-days me-2"></i>Ver mi Agenda
        </a>
        <button type="button" class="btn btn-outline-secondary rounded-0 px-4"
                data-bs-dismiss="modal">Cerrar</button>`;
  }

  // ═════════════════════════════════════════════════════════════════════════
  // 4. REFRESH BUTTON
  // ═════════════════════════════════════════════════════════════════════════
  function bindRefreshBtn() {
    const btn = document.getElementById("recs-refresh-btn");
    if (!btn) return;
    btn.addEventListener("click", async () => {
      btn.classList.add("spinning");
      btn.disabled = true;
      await loadRecommendations(true);
      btn.classList.remove("spinning");
      btn.disabled = false;
    });
  }

  // ═════════════════════════════════════════════════════════════════════════
  // HELPERS
  // ═════════════════════════════════════════════════════════════════════════
  function renderStars(val, max = 5, small = false) {
    const filled = Math.round(parseFloat(val) || 0);
    const cls = small ? "fa-xs" : "";
    let h = "";
    for (let i = 1; i <= max; i++) {
      h += `<i class="fa-${i <= filled ? "solid" : "regular"} fa-star ${cls}"
                     style="color:#fbbf24;"></i>`;
    }
    return h;
  }

  function escHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function showToast(msg, type = "success") {
    const container =
      document.getElementById("toast-container") || createToastContainer();
    const id = "toast-" + Date.now();
    const el = document.createElement("div");
    el.id = id;
    el.className = `toast align-items-center text-bg-${type} border-0 show`;
    el.setAttribute("role", "alert");
    el.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${escHtml(msg)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>`;
    container.appendChild(el);
    setTimeout(() => el.remove(), 4000);
  }

  function createToastContainer() {
    const c = document.createElement("div");
    c.id = "toast-container";
    c.className = "toast-container position-fixed bottom-0 end-0 p-3";
    c.style.zIndex = "9999";
    document.body.appendChild(c);
    return c;
  }
})();
