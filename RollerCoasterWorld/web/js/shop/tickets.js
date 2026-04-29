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

/* ------------------------------------------------------------------------------------------------------------------------------------------------
   UTILIDADES
   ------------------------------------------------------------------------------------------------------------------------------------------------ */
const fmt = (v) => parseFloat(v).toFixed(2) + " \u20AC";
const date = (d) =>
  d
    ? new Date(d + "T00:00:00").toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "long",
        year: "numeric",
      })
    : "?";
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
async function updateCartBadge() {
  try {
    const res = await apiGet(TICKETS_API + "?action=get_cart");
    const count = res.count ?? 0;
    const badge = document.getElementById("cart-nav-badge");
    if (badge) {
      badge.textContent = count;
      badge.classList.toggle("d-none", count === 0);
    }
  } catch (e) {}
}

/* ............................................................
   CATÁLOGO DE ENTRADAS (/tickets)
   ............................................................ */
let allParks = [];
let currentPark = null;
let selectedType = "entrada";
let qty = 1;

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

  // Cantidad
  document.getElementById("qty-minus")?.addEventListener("click", () => {
    if (qty > 1) {
      qty--;
      updateModal();
    }
  });
  document.getElementById("qty-plus")?.addEventListener("click", () => {
    if (qty < 10) {
      qty++;
      updateModal();
    }
  });

  // Tipo
  document.querySelectorAll(".type-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document
        .querySelectorAll(".type-btn")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      selectedType = btn.dataset.type;
      updateModal();
    });
  });

  // Añadir al carrito
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
  selectedType = "entrada";
  document
    .querySelectorAll(".type-btn")
    .forEach((b) => b.classList.toggle("active", b.dataset.type === "entrada"));
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

  updateModal();
  new bootstrap.Modal(document.getElementById("buy-modal")).show();
}

function updateModal() {
  if (!currentPark) return;
  const basePrice = parseFloat(currentPark.precio_entrada);
  const unitPrice =
    selectedType === "pase_rapido" ? +(basePrice * 1.5).toFixed(2) : basePrice;
  document.getElementById("price-label-entrada").textContent = fmt(basePrice);
  document.getElementById("price-label-pase").textContent =
    fmt(+(basePrice * 1.5).toFixed(2)) + " (+50%)";
  document.getElementById("qty-display").textContent = qty;
  document.getElementById("modal-total").textContent = fmt(unitPrice * qty);
}

async function addToCart() {
  const visitDate = document.getElementById("modal-visit-date").value;
  if (!visitDate) {
    showToast("Selecciona una fecha de visita", "error");
    return;
  }
  const basePrice = parseFloat(currentPark.precio_entrada);
  const unitPrice =
    selectedType === "pase_rapido" ? +(basePrice * 1.5).toFixed(2) : basePrice;

  const btn = document.getElementById("btn-add-cart");
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Añadiendo...';

  const res = await apiPost(TICKETS_API + "?action=add_to_cart", {
    park_id: currentPark.id,
    park_name: currentPark.park_name,
    park_img: currentPark.imagen_url || "",
    ticket_type: selectedType,
    quantity: qty,
    unit_price: unitPrice,
    visit_date: visitDate,
  });

  btn.disabled = false;
  btn.innerHTML = '<i class="fa-solid fa-cart-plus me-2"></i>Añadir al carrito';

  if (res.success) {
    bootstrap.Modal.getInstance(document.getElementById("buy-modal")).hide();
    updateCartBadge();
    showToast(`${qty} entrada(s) añadida(s) al carrito "`);
  } else {
    showToast(res.error || "Error al añadir", "error");
  }
}

/* ............................................................
   CARRITO (/carrito)
   ............................................................ */
async function initCart() {
  if (!document.getElementById("cart-tbody")) return;
  await loadCart();

  document.getElementById("btn-clear-cart")?.addEventListener("click", () => {
    const modalEl = document.getElementById("modal-clear-cart");
    if (modalEl) new bootstrap.Modal(modalEl).show();
  });

  document
    .getElementById("btn-confirm-clear-cart")
    ?.addEventListener("click", async () => {
      const btn = document.getElementById("btn-confirm-clear-cart");
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
      await apiPost(TICKETS_API + "?action=clear_cart", {});
      await apiPost(TICKETS_API + "?action=remove_coupon", {});
      updateCartBadge();
      bootstrap.Modal.getInstance(
        document.getElementById("modal-clear-cart"),
      ).hide();
      btn.disabled = false;
      btn.innerHTML = "Sí, vaciar";
      await loadCart();
    });

  // Cupón: aplicar
  document
    .getElementById("btn-apply-coupon")
    ?.addEventListener("click", async () => {
      const code = (document.getElementById("coupon-input")?.value || "")
        .trim()
        .toUpperCase();
      if (!code) return;
      const btn = document.getElementById("btn-apply-coupon");
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
      const res = await apiPost(TICKETS_API + "?action=apply_coupon", { code });
      btn.disabled = false;
      btn.innerHTML = "Aplicar";
      const fb = document.getElementById("coupon-feedback");
      if (res.success) {
        fb.className = "small mb-2 text-success";
        fb.innerHTML = `<i class="fa-solid fa-check-circle me-1"></i>${res.description} (-${res.discount_percent}%)`;
        document
          .getElementById("btn-remove-coupon")
          ?.classList.remove("d-none");
        await loadCart();
      } else {
        fb.className = "small mb-2 text-danger";
        fb.innerHTML = `<i class="fa-solid fa-xmark-circle me-1"></i>${res.error || "Cupón no válido"}`;
      }
    });

  // Cupón: quitar
  document
    .getElementById("btn-remove-coupon")
    ?.addEventListener("click", async () => {
      await apiPost(TICKETS_API + "?action=remove_coupon", {});
      const inp = document.getElementById("coupon-input");
      const fb = document.getElementById("coupon-feedback");
      const rmv = document.getElementById("btn-remove-coupon");
      if (inp) inp.value = "";
      if (fb) fb.className = "small mb-2 d-none";
      if (rmv) rmv.classList.add("d-none");
      await loadCart();
    });
}

async function loadCart() {
  const res = await apiGet(TICKETS_API + "?action=get_cart");
  const items = res.items || [];
  const items_raw = res.items || [];
  const subtotal =
    res.subtotal ?? items_raw.reduce((s, i) => s + parseFloat(i.total || 0), 0);
  const discount = res.discount ?? 0;
  const total = res.total ?? 0;
  const coupon = res.coupon || null;
  const empty = document.getElementById("cart-empty");
  const content = document.getElementById("cart-content");
  const tbody = document.getElementById("cart-tbody");

  if (!items.length) {
    empty.classList.remove("d-none");
    content.classList.add("d-none");
    return;
  }
  empty.classList.add("d-none");
  content.classList.remove("d-none");
  document.getElementById("cart-item-count").textContent = items.reduce(
    (s, i) => s + i.quantity,
    0,
  );
  document.getElementById("summary-subtotal").textContent = fmt(subtotal);
  // Fila de descuento cupón
  const discRow = document.getElementById("summary-discount-row");
  if (discRow) {
    if (discount > 0 && coupon) {
      discRow.classList.remove("d-none");
      document.getElementById("summary-discount").textContent =
        "-" + fmt(discount);
      const lbl = document.getElementById("summary-coupon-label");
      if (lbl) lbl.textContent = `Cupón "${coupon.code}" (-${coupon.percent}%)`;
    } else {
      discRow.classList.add("d-none");
    }
  }
  document.getElementById("summary-total").textContent = fmt(total);
  // Mostrar cupón activo
  if (coupon) {
    const inp = document.getElementById("coupon-input");
    const fb = document.getElementById("coupon-feedback");
    const rmv = document.getElementById("btn-remove-coupon");
    if (inp) inp.value = coupon.code;
    if (fb) {
      fb.className = "small mb-2 text-success";
      fb.innerHTML = `<i class="fa-solid fa-check-circle me-1"></i>${coupon.description} (-${coupon.percent}%)`;
    }
    if (rmv) rmv.classList.remove("d-none");
  }

  tbody.innerHTML = items
    .map(
      (item, idx) => `
    <tr>
      <td class="ps-3">
        <div class="d-flex align-items-center gap-2">
          <img src="${item.park_img || "https://placehold.co/52x40/0d1117/444?text=P"}"
               class="cart-park-img" alt="${item.park_name}"
               onerror="this.src='https://placehold.co/52x40/0d1117/444?text=P'">
          <span class="fw-semibold text-white" style="font-size:.88rem;">${item.park_name}</span>
        </div>
      </td>
      <td><span class="status-badge ${item.ticket_type === "pase_rapido" ? "badge-confirmado" : "badge-pendiente"}" style="font-size:.65rem;">${typeLabel(item.ticket_type)}</span></td>
      <td class="text-muted">${date(item.visit_date)}</td>
      <td class="text-center">${item.quantity}</td>
      <td class="text-end text-muted">${fmt(item.unit_price)}</td>
      <td class="text-end fw-bold text-success pe-3">${fmt(item.total)}</td>
      <td>
        <button class="btn btn-sm btn-outline-danger rounded-0 btn-remove-item" data-index="${idx}" title="Eliminar">
          <i class="fa-solid fa-trash-can"></i>
        </button>
      </td>
    </tr>
  `,
    )
    .join("");

  tbody.querySelectorAll(".btn-remove-item").forEach((btn) => {
    btn.addEventListener("click", async () => {
      await apiPost(TICKETS_API + "?action=remove_from_cart", {
        index: btn.dataset.index,
      });
      updateCartBadge();
      await loadCart();
    });
  });
}

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

  document
    .getElementById("btn-modal-confirm-order")
    ?.addEventListener("click", confirmOrder);
}

async function confirmOrder() {
  const name = document.getElementById("checkout-name")?.value.trim();
  const email = document.getElementById("checkout-email")?.value.trim();

  if (!name || !email) {
    const modalEl = document.getElementById("modal-confirm-order");
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    // Pequeño retardo para que la animación del modal no oculte el toast
    setTimeout(() => {
      showToast("Por favor, rellena tu nombre y email para recibir las entradas", "error");
      if (!name) document.getElementById("checkout-name").focus();
      else if (!email) document.getElementById("checkout-email").focus();
    }, 400);
    return;
  }

  const btn = document.getElementById("btn-modal-confirm-order");
  btn.disabled = true;
  btn.innerHTML =
    '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

  const res = await apiPost(TICKETS_API + "?action=create_order", {
    name,
    email,
  });
  if (res.success) {
    const ids = res.order_ids || [];
    bootstrap.Modal.getInstance(
      document.getElementById("modal-confirm-order"),
    ).hide();
    document.getElementById("checkout-form-wrap").classList.add("d-none");
    document.getElementById("checkout-success").classList.remove("d-none");
    document.getElementById("success-order-ref").textContent = ids
      .map(
        (id) =>
          `#RCW-${new Date().getFullYear()}-${String(id).padStart(6, "0")}`,
      )
      .join(", ");
    updateCartBadge();
  } else {
    btn.disabled = false;
    btn.innerHTML = "Sí, confirmar";
    bootstrap.Modal.getInstance(
      document.getElementById("modal-confirm-order"),
    ).hide();
    showToast(res.error || "Error al confirmar el pedido", "error");
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
    const total = res.unnotified_refunds.reduce((a, b) => parseFloat(a) + parseFloat(b), 0);
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

      const modal = new bootstrap.Modal(modalEl);
      const confirmBtn = document.getElementById("btn-confirm-refund-modal");

      // Clonar para limpiar eventos previos
      const newConfirmBtn = confirmBtn.cloneNode(true);
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

      newConfirmBtn.addEventListener("click", async () => {
        newConfirmBtn.disabled = true;
        const res = await apiPost(ORDERS_API + "?action=request_cancel", {
          order_id: btn.dataset.id,
        });
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
        <div class="tc-total">${fmt(o.price)}</div>
        <div class="tc-code">${code}</div>
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
    .map(
      (o) => `
    <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
      <td class="px-3 text-muted" style="font-size:.75rem;">#${o.id}</td>
      <td><div class="fw-semibold text-white" style="font-size:.82rem;">${o.username}</div><div class="text-muted" style="font-size:.7rem;">${o.email}</div></td>
      <td class="fw-semibold text-white" style="font-size:.82rem;">${o.park_name}</td>
      <td><span class="status-badge ${o.ticket_type === "pase_rapido" ? "badge-confirmado" : "badge-pendiente"}" style="font-size:.65rem;">${typeLabel(o.ticket_type)}</span></td>
      <td class="text-muted" style="font-size:.82rem;">${date(o.visit_date)}</td>
      <td class="text-center text-muted">${o.quantity}</td>
      <td class="text-end fw-bold text-success">${fmt(o.price)}</td>
      <td>${statusBadge(o.status)}</td>
      <td class="text-muted" style="font-size:.75rem;">${new Date(o.created_at).toLocaleDateString("es-ES")}</td>
      <td class="text-center">
        <div class="d-flex gap-1 justify-content-center">
          ${(o.status === "confirmado" || o.status === "solicitada_cancelacion") ? `
            <button class="btn btn-outline-danger btn-sm rounded-0 btn-cancel-order" data-id="${o.id}" title="Cancelar" style="font-size:.72rem;padding:3px 8px;">
              <i class="fa-solid fa-xmark"></i>
            </button>
          ` : '<span class="text-muted" style="font-size:.72rem;">-</span>'}
        </div>
      </td>
    </tr>
  `,
    )
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
  updateCartBadge();
  initTicketsCatalog();
  initCart();
  initCheckout();
  initOrders();
  initAdminOrders();
});
