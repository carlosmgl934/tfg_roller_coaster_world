/**
 * tickets.js - Sistema de entradas RollerCoasterWorld
 * Gestiona: catálogo, modal compra, carrito, checkout, mis pedidos, admin pedidos
 */

const TICKETS_API =
  window.TICKETS_API || window.BASE_URL + "/api/php/tickets.php";
const ORDERS_API = window.ORDERS_API || window.BASE_URL + "/api/php/orders.php";
const ADMIN_ORDERS_API =
  window.ADMIN_ORDERS_API ||
  (window.BASE_URL
    ? window.BASE_URL + "/api/php/admin/admin_orders.php"
    : window.location.origin + "/api/php/admin/admin_orders.php");
const PDF_BASE =
  window.PDF_BASE || window.BASE_URL + "/api/php/generate_ticket_pdf.php";

/* ----------------------------------------------------------------------------------------------------------
   UTILIDADES
   ---------------------------------------------------------------------------------------------------------- */
const fmt = (v) =>
  v != null && !isNaN(parseFloat(v)) ? parseFloat(v).toFixed(2) + " €" : "—";
const date = (d) => {
  if (!d)
    return '<span class="text-muted" style="font-size:.72rem;font-style:italic;">Sin fecha</span>';
  const parsed = new Date(d + "T00:00:00");
  if (isNaN(parsed))
    return '<span class="text-muted" style="font-size:.72rem;font-style:italic;">Sin fecha</span>';
  return parsed.toLocaleDateString("es-ES", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
};
const typeLabel = (t) =>
  t === "pase_rapido" ? "Pase Rápido" : "Entrada General";
const statusBadge = (s) => {
  const map = {
    confirmado: "badge-confirmado",
    cancelado: "badge-cancelado",
    solicitada_cancelacion: "badge-pendiente",
  };
  const lbl = {
    confirmado: "Confirmado",
    cancelado: "Cancelado",
    solicitada_cancelacion: "Reembolso solicitado",
  };
  return `<span class="status-badge ${map[s] || ""}">${lbl[s] || s}</span>`;
};

async function apiPost(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const r = await fetch(url, {
    headers: {
      "X-CSRF-Token":
        document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute("content") ?? "",
    },
    method: "POST",
    body: fd,
    credentials: "include",
  });
  return r.json();
}
async function apiGet(url) {
  const r = await fetch(url, { credentials: "include" });
  return r.json();
}

/* ............................................................
   BADGE CARRITO (global)
   ............................................................ */

/* ............................................................
   CATÁLOGO DE ENTRADAS (/tickets)
   ............................................................ */
let allParks = [];
let currentPark = null;
let qty = 1;

// Precios de complementos (por persona excepto parking que es fijo)
const ADDON_CONFIG = {
  pase_rapido: { pct: 0.5, perPerson: true },
  photopass: { pct: 0.3, perPerson: true },
  buffet: { pct: 0.2, perPerson: true },
  parking: { pct: null, perPerson: false }, // precio fijo determinista
};

function parkingPrice(parkId) {
  return 10 + (parkId % 9); // €10–€18, siempre el mismo para el mismo parque
}

function fmtAddon(n) {
  return n.toFixed(2) + " €";
}

async function initTicketsCatalog() {
  if (!document.getElementById("tickets-grid")) return;

  const res = await apiGet(TICKETS_API + "?action=list_parks");
  allParks = res.parks || res.data || [];
  renderCatalog(allParks);

  document.getElementById("tickets-search")?.addEventListener("input", (e) => {
    const q = e.target.value.toLowerCase();
    renderCatalog(
      allParks.filter(
        (p) =>
          p.park_name.toLowerCase().includes(q) ||
          p.park_country.toLowerCase().includes(q),
      ),
    );
  });

  // ── Paso 1: cantidad ──────────────────────────────────────────
  document.getElementById("qty-minus")?.addEventListener("click", () => {
    if (qty > 1) {
      qty--;
      updateStep1();
    }
  });
  document.getElementById("qty-plus")?.addEventListener("click", () => {
    if (qty < 10) {
      qty++;
      updateStep1();
    }
  });

  // ── Navegación wizard ─────────────────────────────────────────
  document
    .getElementById("btn-next-step")
    ?.addEventListener("click", goToStep2);
  document
    .getElementById("btn-prev-step")
    ?.addEventListener("click", goToStep1);

  // ── Paso 2: add-ons ───────────────────────────────────────────
  document.querySelectorAll(".addon-check").forEach((cb) => {
    cb.addEventListener("change", () => {
      const card = cb.closest(".addon-card");
      card.classList.toggle("border-success", cb.checked);
      card.classList.toggle("border-secondary", !cb.checked);
      updateStep2Total();
    });
  });

  // ── Añadir al carrito ─────────────────────────────────────────
  document.getElementById("btn-add-cart")?.addEventListener("click", addToCart);
}

function renderCatalog(parks) {
  const grid = document.getElementById("tickets-grid");
  const loading = document.getElementById("tickets-loading");
  if (loading) loading.remove();
  document.getElementById("tickets-count").textContent = parks.length;

  if (!parks.length) {
    grid.innerHTML = `<div class="col-12 text-center py-5 text-muted">
      <i class="fa-solid fa-ticket fa-3x d-block mb-3 opacity-25"></i>
      No hay parques con entradas disponibles actualmente
    </div>`;
    return;
  }

  grid.innerHTML = parks
    .map(
      (p) => `
    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
      <div class="park-ticket-card card shadow-sm border-0 h-100">
        <div class="card-img-wrap">
          <img src="${p.imagen_url || "https://placehold.co/400x200/111827/444?text=Parque"}"
               alt="${p.park_name}" loading="lazy"
               onerror="this.src='https://placehold.co/400x200/111827/444?text=Parque'">
          <span class="price-badge badge bg-success">${fmt(p.precio_entrada)}</span>
        </div>
        <div class="card-body d-flex flex-column">
          <div class="fw-bold text-white mb-1" style="font-size:1rem;">${p.park_name}</div>
          <div class="text-muted small mb-2"><i class="fa-solid fa-map-pin me-1 text-success" style="font-size:.7rem;"></i>${p.park_country}</div>
          <div class="text-success fw-bold fs-5 mb-3">${fmt(p.precio_entrada)} <small class="text-muted fw-normal" style="font-size:.72rem;">/ persona</small></div>
          <button class="btn btn-success fw-bold rounded-0 shadow-sm mt-auto w-100 btn-buy" data-park-id="${p.id}">
            <i class="fa-solid fa-ticket me-2"></i>Comprar entrada
          </button>
        </div>
      </div>
    </div>
  `,
    )
    .join("");

  grid.querySelectorAll(".btn-buy").forEach((btn) => {
    btn.addEventListener("click", () =>
      openBuyModal(parseInt(btn.dataset.parkId)),
    );
  });
}

function openBuyModal(parkId) {
  if (!window.IS_LOGGED) {
    window.location.href = window.BASE_URL + "/web/views/auth/login.php";
    return;
  }
  currentPark = allParks.find((p) => p.id === parkId);
  if (!currentPark) return;

  qty = 1;

  // Reset checkboxes
  document.querySelectorAll(".addon-check").forEach((cb) => {
    cb.checked = false;
    cb.closest(".addon-card")?.classList.replace(
      "border-success",
      "border-secondary",
    );
  });

  document.getElementById("modal-park-name").textContent =
    currentPark.park_name;
  document.getElementById("modal-park-country").textContent =
    currentPark.park_country;

  const fpInput = document.getElementById("modal-visit-date");
  if (fpInput._flatpickr) fpInput._flatpickr.destroy();
  flatpickr(fpInput, {
    minDate: "today",
    locale: "es",
    dateFormat: "Y-m-d",
    disableMobile: true,
    defaultDate: "today",
  });

  goToStep1(false); // muestra paso 1 sin animación innecesaria
  updateStep1();
  new bootstrap.Modal(document.getElementById("buy-modal")).show();
}

function goToStep1(reset = true) {
  document.getElementById("step-1").classList.remove("d-none");
  document.getElementById("step-2").classList.add("d-none");
  document.getElementById("footer-step-1").classList.remove("d-none");
  document.getElementById("footer-step-2").classList.add("d-none");
  document.getElementById("step-tab-1").classList.add("active");
  document.getElementById("step-tab-2").classList.remove("active");
}

function goToStep2() {
  const visitDate = document.getElementById("modal-visit-date").value;
  if (!visitDate) {
    showToast("Selecciona una fecha de visita", "error");
    return;
  }

  const base = parseFloat(currentPark.precio_entrada);
  const parking = parkingPrice(currentPark.id);

  // Rellenar precios de add-ons
  document.getElementById("price-pase").textContent = fmtAddon(
    base * qty * 0.5,
  );
  document.getElementById("price-photo").textContent = fmtAddon(
    base * qty * 0.3,
  );
  document.getElementById("price-buffet").textContent = fmtAddon(
    base * qty * 0.2,
  );
  document.getElementById("price-parking").textContent = fmtAddon(parking);

  // Resumen
  document.getElementById("s-qty").textContent = qty;
  document.getElementById("s-base").textContent = fmtAddon(base * qty);

  updateStep2Total();

  document.getElementById("step-1").classList.add("d-none");
  document.getElementById("step-2").classList.remove("d-none");
  document.getElementById("footer-step-1").classList.add("d-none");
  document.getElementById("footer-step-2").classList.remove("d-none");
  document.getElementById("step-tab-1").classList.remove("active");
  document.getElementById("step-tab-2").classList.add("active");
}

function updateStep1() {
  if (!currentPark) return;
  const base = parseFloat(currentPark.precio_entrada);
  document.getElementById("qty-display").textContent = qty;
  document.getElementById("step1-qty-label").textContent = `× ${qty}`;
  document.getElementById("step1-total").textContent = fmtAddon(base * qty);
}

function updateStep2Total() {
  if (!currentPark) return;
  const base = parseFloat(currentPark.precio_entrada);
  const parking = parkingPrice(currentPark.id);

  const pase = document.getElementById("addon-pase").checked;
  const photo = document.getElementById("addon-photo").checked;
  const buffet = document.getElementById("addon-buffet").checked;
  const park = document.getElementById("addon-parking").checked;

  let total = base * qty;
  if (pase) total += base * qty * 0.5;
  if (photo) total += base * qty * 0.3;
  if (buffet) total += base * qty * 0.2;
  if (park) total += parking;

  // Mostrar/ocultar filas del resumen
  document.querySelector(".addon-row-pase").classList.toggle("d-none", !pase);
  document.querySelector(".addon-row-photo").classList.toggle("d-none", !photo);
  document
    .querySelector(".addon-row-buffet")
    .classList.toggle("d-none", !buffet);
  document
    .querySelector(".addon-row-parking")
    .classList.toggle("d-none", !park);

  document.getElementById("s-pase").textContent = fmtAddon(base * qty * 0.5);
  document.getElementById("s-photo").textContent = fmtAddon(base * qty * 0.3);
  document.getElementById("s-buffet").textContent = fmtAddon(base * qty * 0.2);
  document.getElementById("s-parking").textContent = fmtAddon(parking);
  document.getElementById("modal-total").textContent = fmtAddon(total);
}

async function addToCart() {
  const visitDate = document.getElementById("modal-visit-date").value;
  if (!visitDate) {
    showToast("Selecciona una fecha de visita", "error");
    return;
  }

  const base = parseFloat(currentPark.precio_entrada);
  const parking = parkingPrice(currentPark.id);
  const pase = document.getElementById("addon-pase").checked;
  const photo = document.getElementById("addon-photo").checked;
  const buffet = document.getElementById("addon-buffet").checked;
  const park = document.getElementById("addon-parking").checked;

  let unitPrice = base;
  if (pase) unitPrice += base * 0.5;
  if (photo) unitPrice += base * 0.3;
  if (buffet) unitPrice += base * 0.2;
  // Parking se añade como importe fijo, no por persona
  const parkingTotal = park ? parking : 0;

  const totalPrice = +(unitPrice * qty + parkingTotal).toFixed(2);

  const btn = document.getElementById("btn-add-cart");
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Añadiendo...';

  const res = await apiPost(TICKETS_API + "?action=add_to_cart", {
    park_id: currentPark.id,
    park_name: currentPark.park_name,
    park_img: currentPark.imagen_url || "",
    ticket_type: "entrada",
    quantity: qty,
    unit_price: +unitPrice.toFixed(2),
    visit_date: visitDate,
    addon_pase_rapido: pase ? 1 : 0,
    addon_photopass: photo ? 1 : 0,
    addon_buffet: buffet ? 1 : 0,
    addon_parking: park ? 1 : 0,
    parking_price: parkingTotal,
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="fa-solid fa-cart-plus me-2"></i>Añadir al carrito';

  if (res.success) {
    bootstrap.Modal.getInstance(document.getElementById("buy-modal")).hide();
    window.updateCartBadge?.();
    const addons = [
      pase && "Pase Rápido",
      photo && "PhotoPass",
      buffet && "Buffet",
      park && "Parking",
    ]
      .filter(Boolean)
      .join(", ");
    showToast(`${qty} entrada(s) añadida(s)${addons ? ` + ${addons}` : ""}`);
  } else {
    showToast(res.error || "Error al añadir", "error");
  }
}

/* ............................................................

/* ............................................................
   CHECKOUT (/checkout)
   ............................................................ */
async function initCheckout() {
  if (!document.getElementById("checkout-items-list")) return;
  const res = await apiGet(TICKETS_API + "?action=get_cart");
  const items = res.items || [];
  const subtotal =
    res.subtotal ??
    (res.items || []).reduce((s, i) => s + parseFloat(i.total || 0), 0);
  const discount = res.discount ?? 0;
  const total = res.total ?? 0;
  const coupon = res.coupon || null;

  if (!items.length) {
    window.location.href = window.CARRITO_URL;
    return;
  }

  document.getElementById("checkout-items-list").innerHTML = items
    .map(
      (i) => `
    <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--rcw-border)!important;">
      <div>
        <div class="fw-semibold text-white" style="font-size:.9rem;">${i.park_name}</div>
        <small class="text-muted">${typeLabel(i.ticket_type)} · ${date(i.visit_date)} · ${i.quantity} persona(s)</small>
      </div>
      <div class="fw-bold text-success ms-3">${fmt(i.total)}</div>
    </div>
  `,
    )
    .join("");

  let breakdown = items
    .map(
      (i) =>
        `<div class="d-flex justify-content-between text-muted small mb-1">
      <span>${i.park_name}-${i.quantity}</span><span>${fmt(i.total)}</span>
    </div>`,
    )
    .join("");
  if (discount > 0 && coupon) {
    breakdown += `<div class="d-flex justify-content-between text-success small mb-1 fw-semibold">
      <span><i class="fa-solid fa-tag me-1"></i>Cupón &ldquo;${coupon.code}&rdquo; (-${coupon.percent}%)</span>
      <span>-${fmt(discount)}</span>
    </div>`;
  }
  document.getElementById("checkout-breakdown").innerHTML = breakdown;
  document.getElementById("checkout-grand-total").textContent = fmt(total);

  // Botón principal -> redirige a Stripe directamente
  document
    .getElementById("btn-pay-stripe")
    ?.addEventListener("click", goToStripe);

  // Manejar retorno de Stripe (payment=success|cancel)
  const returnStatus = window.STRIPE_RETURN_STATUS || "";
  const returnSession = window.STRIPE_RETURN_SESSION || "";

  if (returnStatus === "success" && returnSession) {
    document.getElementById("checkout-form-wrap")?.classList.add("d-none");
    document.getElementById("checkout-verifying")?.classList.remove("d-none");
    verifyStripeSession(returnSession);
  } else if (returnStatus === "cancel") {
    document.getElementById("checkout-form-wrap")?.classList.add("d-none");
    document.getElementById("checkout-cancelled")?.classList.remove("d-none");
  }
}

/** Redirige al usuario a la pasarela de Stripe */
async function goToStripe() {
  const name = document.getElementById("checkout-name")?.value.trim();
  const email = document.getElementById("checkout-email")?.value.trim();

  if (!name || !email) {
    showToast(
      "Indica el nombre del titular y el email donde recibirás las entradas",
      "error",
    );
    if (!name) document.getElementById("checkout-name")?.focus();
    else document.getElementById("checkout-email")?.focus();
    return;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showToast("El email no tiene un formato válido", "error");
    return;
  }

  const btn = document.getElementById("btn-pay-stripe");
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Redirigiendo a Stripe...';

  const stripeApi =
    window.STRIPE_API || window.BASE_URL + "/api/php/stripe_checkout.php";
  const res = await apiPost(stripeApi + "?action=create_session", {
    name,
    email,
  });

  if (res.success && res.url) {
    window.location.href = res.url;
  } else {
    btn.disabled = false;
    btn.innerHTML =
      '<i class="fa-solid fa-credit-card me-2"></i>Pagar con Stripe';
    showToast(res.error || "Error al iniciar el pago", "error");
  }
}

/** Verifica el pago tras volver de Stripe y crea los pedidos */
async function verifyStripeSession(sessionId) {
  const stripeApi =
    window.STRIPE_API || window.BASE_URL + "/api/php/stripe_checkout.php";
  const res = await apiPost(stripeApi + "?action=verify_session", {
    session_id: sessionId,
  });

  document.getElementById("checkout-verifying")?.classList.add("d-none");

  if (res.success) {
    const ids = res.order_ids || [];
    document.getElementById("checkout-success")?.classList.remove("d-none");
    document.getElementById("success-order-ref").textContent = ids
      .map(
        (id) =>
          `#RCW-${new Date().getFullYear()}-${String(id).padStart(6, "0")}`,
      )
      .join(", ");
    window.updateCartBadge?.();
  } else {
    document.getElementById("checkout-form-wrap")?.classList.remove("d-none");
    showToast(
      res.error || "No se pudo verificar el pago. Contacta con soporte.",
      "error",
    );
  }
}

/* ............................................................
   MIS PEDIDOS (/orders)
   ............................................................ */
async function initOrders() {
  if (!document.getElementById("orders-activas")) return;
  const res = await apiGet(ORDERS_API + "?action=my_orders");
  document.getElementById("orders-loading")?.remove();

  // Notificación de reembolsos procesados
  if (res.unnotified_refunds && res.unnotified_refunds.length > 0) {
    const total = res.unnotified_refunds.reduce(
      (a, b) => parseFloat(a) + parseFloat(b),
      0,
    );
    const msgEl = document.getElementById("refund-notice-msg");
    const modalEl = document.getElementById("refundNoticeModal");
    if (msgEl && modalEl) {
      msgEl.innerHTML = `Se te han reembolsado <strong>${fmt(total)}</strong> de tus entradas canceladas.`;
      const modal = new bootstrap.Modal(modalEl);
      modal.show();

      // Marcamos como notificados inmediatamente al mostrar el aviso
      apiPost(ORDERS_API + "?action=mark_refunds_notified", {});
    }
  }
  const today = new Date().toISOString().split("T")[0];
  const all = res.orders || res.data || [];
  const activas = all.filter(
    (o) => o.visit_date >= today && o.status !== "cancelado",
  );
  const pasadas = all.filter(
    (o) => o.visit_date < today && o.status !== "cancelado",
  );

  document.getElementById("count-activas").textContent = activas.length;
  document.getElementById("count-pasadas").textContent = pasadas.length;

  document.getElementById("orders-activas").innerHTML = activas.length
    ? activas.map(renderTicket).join("")
    : emptyOrders();
  document.getElementById("orders-pasadas").innerHTML = pasadas.length
    ? pasadas.map(renderTicket).join("")
    : emptyOrders();

  document.getElementById("orders-activas").classList.remove("d-none");

  document.querySelectorAll("[data-tab]").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.querySelectorAll("[data-tab]").forEach((b) => {
        b.classList.remove("active", "btn-success");
        b.classList.add("btn-outline-secondary");
      });
      btn.classList.add("active", "btn-success");
      btn.classList.remove("btn-outline-secondary");

      const tab = btn.dataset.tab;
      document
        .getElementById("orders-activas")
        .classList.toggle("d-none", tab !== "activas");
      document
        .getElementById("orders-pasadas")
        .classList.toggle("d-none", tab !== "pasadas");
    });
  });

  // Evento para solicitar cancelación
  document.querySelectorAll(".btn-request-cancel").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modalEl = document.getElementById("refundConfirmModal");
      if (!modalEl) return;

      // Usar getOrCreateInstance para no crear instancias duplicadas
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      const confirmBtn = document.getElementById("btn-confirm-refund-modal");

      // Clonar para limpiar eventos previos, y SIEMPRE resetear estado
      const newConfirmBtn = confirmBtn.cloneNode(true);
      newConfirmBtn.disabled = false;
      newConfirmBtn.innerHTML =
        '<i class="fa-solid fa-rotate-left me-1"></i>Confirmar Solicitud';
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

      newConfirmBtn.addEventListener("click", async () => {
        newConfirmBtn.disabled = true;
        newConfirmBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

        const res = await apiPost(ORDERS_API + "?action=request_cancel", {
          order_id: btn.dataset.id,
        });

        // Siempre restaurar el botón antes de cualquier otra acción
        newConfirmBtn.disabled = false;
        newConfirmBtn.innerHTML =
          '<i class="fa-solid fa-rotate-left me-1"></i>Confirmar Solicitud';

        modal.hide();

        if (res.success) {
          showToast("Solicitud enviada correctamente");
          initOrders();
        } else {
          showToast(res.error || "Error al procesar la solicitud", "error");
        }
      });

      modal.show();
    });
  });
}

function renderTicket(o) {
  const code = `RCW-${new Date().getFullYear()}-${String(o.id).padStart(6, "0")}`;
  const pdfLink =
    o.status === "confirmado"
      ? `<a href="${PDF_BASE}?order_id=${o.id}" target="_blank" class="btn btn-success btn-sm rounded-0 fw-bold w-100" style="font-size:.75rem;">
         <i class="fa-solid fa-download me-1"></i>PDF
       </a>`
      : `<span class="text-muted small text-center d-block" style="font-size:.72rem;">Pendiente<br>de confirmación</span>`;

  const today = new Date().toISOString().split("T")[0];
  const canCancel = o.status === "confirmado" && o.visit_date >= today;

  const refundAction = canCancel
    ? `<a href="javascript:void(0)" class="btn-request-cancel text-danger text-decoration-none d-block text-center mt-2 fw-semibold" 
          data-id="${o.id}" style="font-size: .72rem; opacity: 0.8; transition: all 0.2s;"
          onmouseover="this.style.opacity='1'; this.style.textDecoration='underline'" 
          onmouseout="this.style.opacity='0.8'; this.style.textDecoration='none'">
         <i class="fa-solid fa-rotate-left me-1"></i>Solicitar devolución
       </a>`
    : o.status === "solicitada_cancelacion"
      ? `<span class="text-warning small d-block text-center mt-2 fw-bold" style="font-size:.68rem;">
           <i class="fa-solid fa-clock-rotate-left me-1"></i>Reembolso en proceso
         </span>`
      : "";

  return `
    <div class="ticket-card">
      ${
        o.imagen_url
          ? `<img src="${o.imagen_url}" alt="${o.park_name}" class="tc-img" onerror="this.parentElement.innerHTML='<div class=tc-img-placeholder><i class=fa-solid fa-tree-city fa-2x></i></div>'">`
          : `<div class="tc-img-placeholder"><i class="fa-solid fa-tree-city fa-2x"></i></div>`
      }
      <div class="tc-body">
        <div>
          <div class="tc-park">${o.park_name}</div>
          <div class="tc-meta"><i class="fa-solid fa-map-pin me-1 text-success" style="font-size:.7rem;"></i>${o.park_country}</div>
          <div class="tc-badges mt-2">
            ${statusBadge(o.status)}
            <span class="status-badge" style="background:rgba(25,135,84,.15);color:#4ade80;border:1px solid rgba(25,135,84,.4);font-size:.65rem;">
              ${typeLabel(o.ticket_type)}
            </span>
          </div>
        </div>
        <div class="d-flex gap-3 mt-3 text-muted" style="font-size:.8rem;">
          <span><i class="fa-regular fa-calendar me-1"></i>${date(o.visit_date)}</span>
          <span><i class="fa-solid fa-users me-1"></i>${o.quantity} persona(s)</span>
        </div>
      </div>
      <div class="tc-side">
        <div class="d-flex justify-content-between align-items-baseline w-100 mb-md-auto">
          <div class="tc-total">${fmt(o.price)}</div>
          <div class="tc-code">${code}</div>
        </div>
        ${pdfLink}
        ${refundAction}
      </div>
    </div>`;
}

function emptyOrders() {
  return `<div class="text-center py-4 text-muted">
    <i class="fa-solid fa-ticket opacity-25 fa-2x d-block mb-2"></i>
    Sin entradas en esta sección
  </div>`;
}

/* ............................................................
   ADMIN PEDIDOS (/admin/orders)
   ............................................................ */
async function initAdminOrders() {
  if (!document.getElementById("admin-orders-tbody")) return;
  await loadAdminOrders();
  await loadCancellationCount();

  document
    .getElementById("filter-status")
    ?.addEventListener("change", loadAdminOrders);
  document
    .getElementById("filter-date")
    ?.addEventListener("change", loadAdminOrders);
  document
    .getElementById("btn-refresh-orders")
    ?.addEventListener("click", async () => {
      await loadAdminOrders();
      await loadPendingCount();
    });
  document
    .getElementById("btn-clear-filters")
    ?.addEventListener("click", () => {
      document.getElementById("filter-status").value = "";
      document.getElementById("filter-date").value = "";
      loadAdminOrders();
    });
}

async function loadAdminOrders() {
  const status = document.getElementById("filter-status")?.value || "";
  const visitDate = document.getElementById("filter-date")?.value || "";
  let url = ADMIN_ORDERS_API + "?action=list";
  if (status) url += "&status=" + encodeURIComponent(status);
  if (visitDate) url += "&visit_date=" + encodeURIComponent(visitDate);

  const res = await apiGet(url);
  const orders = res.data || [];
  const tbody = document.getElementById("admin-orders-tbody");
  const empty = document.getElementById("admin-orders-empty");

  if (!orders.length) {
    tbody.innerHTML = "";
    empty.classList.remove("d-none");
    return;
  }
  empty.classList.add("d-none");

  tbody.innerHTML = orders
    .map((o) => {
      const addons = o.addon_label
        ? `<div class="text-success small" style="font-size:.65rem; line-height:1.2;">+ ${o.addon_label}</div>`
        : "";
      return `
    <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
      <td class="px-3 text-muted" style="font-size:.75rem;">#${o.id}</td>
      <td><div class="fw-semibold text-white" style="font-size:.82rem;">${o.username}</div><div class="text-muted" style="font-size:.7rem;">${o.email}</div></td>
      <td><div class="fw-semibold text-white" style="font-size:.82rem;">${o.park_name}</div>${addons}</td>
      <td><span class="status-badge ${o.ticket_type === "pase_rapido" ? "badge-confirmado" : "badge-pendiente"}" style="font-size:.65rem;">${typeLabel(o.ticket_type)}</span></td>
      <td class="text-muted" style="font-size:.82rem;">${date(o.visit_date)}</td>
      <td class="text-center text-muted">${o.quantity}${o.unit_price != null ? `<small class="d-block" style="font-size:.6rem;">${fmt(o.unit_price)}/ud</small>` : ""}</td>
      <td class="text-end fw-bold text-success">${fmt(o.price)}</td>
      <td>${statusBadge(o.status)}</td>
      <td class="text-muted" style="font-size:.75rem;">${new Date(o.created_at).toLocaleDateString("es-ES")}</td>
      <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
          ${
            o.status === "confirmado" || o.status === "solicitada_cancelacion"
              ? `
            <button class="btn btn-outline-danger btn-sm rounded-0 btn-cancel-order" data-id="${o.id}" title="Cancelar" style="font-size:.72rem;padding:3px 8px;">
              <i class="fa-solid fa-xmark"></i>
            </button>
          `
              : o.status === "pendiente"
                ? `
            <button class="btn btn-outline-success btn-sm rounded-0 btn-confirm-order" data-id="${o.id}" title="Confirmar" style="font-size:.72rem;padding:3px 8px;">
              <i class="fa-solid fa-check"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm rounded-0 btn-cancel-order" data-id="${o.id}" title="Cancelar" style="font-size:.72rem;padding:3px 8px;">
              <i class="fa-solid fa-xmark"></i>
            </button>
          `
                : '<span class="text-muted" style="font-size:.72rem;">-</span>'
          }
        </div>
      </td>
    </tr>
  `;
    })
    .join("");

  // Helper: actualiza la fila en el sitio sin recargar la tabla
  function updateRowInPlace(btn, newStatus) {
    const row = btn.closest("tr");
    if (!row) return;

    const statusCell = row.cells[7];
    if (statusCell) statusCell.innerHTML = statusBadge(newStatus);

    const actionsCell = row.cells[9];
    if (actionsCell)
      actionsCell.innerHTML =
        '<span class="text-muted" style="font-size:.72rem;">-</span>';

    // Resaltar fila brevemente con animación
    row.style.transition = "background .4s ease";
    row.style.background =
      newStatus === "confirmado"
        ? "rgba(25,135,84,.18)"
        : "rgba(220,53,69,.12)";
    setTimeout(() => {
      row.style.background = "";
    }, 1500);
  }

  tbody.querySelectorAll(".btn-confirm-order").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modalEl = document.getElementById("modal-confirm-order-admin");
      if (!modalEl) return;
      const doBtn = document.getElementById("btn-do-confirm-order");
      const newDoBtn = doBtn.cloneNode(true);
      doBtn.parentNode.replaceChild(newDoBtn, doBtn);
      const modal = new bootstrap.Modal(modalEl);
      newDoBtn.addEventListener("click", async () => {
        newDoBtn.disabled = true;
        newDoBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Confirmando...';
        const res = await apiPost(ADMIN_ORDERS_API + "?action=confirm", {
          order_id: btn.dataset.id,
        });
        modal.hide();
        newDoBtn.disabled = false;
        newDoBtn.innerHTML =
          '<i class="fa-solid fa-check me-1"></i>Sí, confirmar';
        if (res.success) {
          updateRowInPlace(btn, "confirmado");
          await loadPendingCount();
          showToast("Pedido confirmado correctamente ?");
        } else {
          showToast(res.error || res.message || "Error al confirmar", "error");
        }
      });
      modal.show();
    });
  });
  tbody.querySelectorAll(".btn-cancel-order").forEach((btn) => {
    btn.addEventListener("click", () => {
      const modalEl = document.getElementById("modal-cancel-order-admin");
      if (!modalEl) return;
      const doBtn = document.getElementById("btn-do-cancel-order");
      const newDoBtn = doBtn.cloneNode(true);
      doBtn.parentNode.replaceChild(newDoBtn, doBtn);
      const modal = new bootstrap.Modal(modalEl);
      newDoBtn.addEventListener("click", async () => {
        newDoBtn.disabled = true;
        newDoBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Cancelando...';
        const res = await apiPost(ADMIN_ORDERS_API + "?action=cancel", {
          order_id: btn.dataset.id,
        });
        modal.hide();
        newDoBtn.disabled = false;
        newDoBtn.innerHTML =
          '<i class="fa-solid fa-xmark me-1"></i>Sí, cancelar';
        if (res.success) {
          updateRowInPlace(btn, "cancelado");
          await loadPendingCount();
          showToast("Pedido cancelado");
        } else {
          showToast(res.error || res.message || "Error al cancelar", "error");
        }
      });
      modal.show();
    });
  });
}

async function loadPendingCount() {
  const res = await apiGet(ADMIN_ORDERS_API + "?action=pending_count");
  const el = document.getElementById("admin-pending-count");
  if (el) el.textContent = res.count ?? res.data?.count ?? "-";
}

async function loadCancellationCount() {
  const res = await apiGet(ADMIN_ORDERS_API + "?action=cancellations_count");
  const count = res.count ?? 0;
  const wrap = document.getElementById("refund-alert-wrap");
  const textEl = document.getElementById("admin-refunds-text");
  if (wrap && textEl) {
    if (count > 0) {
      const plural = count > 1 ? "es" : "";
      const pending = count > 1 ? "s" : "";
      textEl.textContent = `¡${count} devolución${plural} pendiente${pending}!`;
      wrap.classList.remove("d-none");
    } else {
      wrap.classList.add("d-none");
    }
  }
}

/* ...........................................................
   TOAST
   ........................................................... */
function showToast(msg, type = "success") {
  const toastEl = document.getElementById("cart-toast");
  if (!toastEl) return;
  const msgEl = document.getElementById("cart-toast-msg");
  if (msgEl) msgEl.textContent = msg;

  const icon = document.getElementById("cart-toast-icon-tag");
  if (icon) {
    icon.className =
      type === "error"
        ? "fa-solid fa-circle-xmark me-2"
        : "fa-solid fa-circle-check me-2";
  }

  toastEl.classList.remove("bg-dark", "bg-danger");
  toastEl.classList.add(type === "error" ? "bg-danger" : "bg-dark");

  new bootstrap.Toast(toastEl, { delay: 3500 }).show();
}
/* ...........................................................
   INIT
   ........................................................... */
document.addEventListener("DOMContentLoaded", () => {
  window.updateCartBadge?.();
  initTicketsCatalog();

  initCheckout();
  initOrders();
  initAdminOrders();
});
