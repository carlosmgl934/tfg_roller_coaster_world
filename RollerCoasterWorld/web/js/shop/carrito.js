// carrito.js — Lógica de la página del carrito
// Muestra items, vacía el carrito y aplica cupones

const CARRITO_API = window.TICKETS_API || window.BASE_URL + "/api/php/tickets.php";
const carritoFmt  = (v) => parseFloat(v).toFixed(2) + " \u20AC";
const carritoDate = (d) => d ? new Date(d + "T00:00:00").toLocaleDateString("es-ES", { day: "2-digit", month: "long", year: "numeric" }) : "?";
const carritoType = (t) => t === "pase_rapido" ? "Pase R\u00e1pido" : "Entrada General";

async function carritoApiPost(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const r = await fetch(url, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, method: "POST", body: fd, credentials: "include" } );
  return r.json();
}
async function carritoApiGet(url) {
  const r = await fetch(url, { credentials: "include" });
  return r.json();
}

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
      await carritoApiPost(CARRITO_API + "?action=clear_cart", {});
      await carritoApiPost(CARRITO_API + "?action=remove_coupon", {});
      window.updateCartBadge();
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
      const res = await carritoApiPost(CARRITO_API + "?action=apply_coupon", { code });
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
      await carritoApiPost(CARRITO_API + "?action=remove_coupon", {});
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
  const res = await carritoApiGet(CARRITO_API + "?action=get_cart");
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
  document.getElementById("summary-subtotal").textContent = carritoFmt(subtotal);
  // Fila de descuento cupón
  const discRow = document.getElementById("summary-discount-row");
  if (discRow) {
    if (discount > 0 && coupon) {
      discRow.classList.remove("d-none");
      document.getElementById("summary-discount").textContent =
        "-" + carritoFmt(discount);
      const lbl = document.getElementById("summary-coupon-label");
      if (lbl) lbl.textContent = `Cupón "${coupon.code}" (-${coupon.percent}%)`;
    } else {
      discRow.classList.add("d-none");
    }
  }
  document.getElementById("summary-total").textContent = carritoFmt(total);
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
          <div>
            <span class="fw-semibold text-white" style="font-size:.88rem;">${item.park_name}</span>
            <div class="mt-1 d-flex flex-wrap gap-1">
              ${item.addon_pase_rapido  ? '<span class="badge rounded-0" style="background:#92400e;font-size:.6rem;"><i class="fa-solid fa-bolt me-1"></i>Pase Rápido</span>' : ''}
              ${item.addon_photopass    ? '<span class="badge rounded-0" style="background:#164e63;font-size:.6rem;"><i class="fa-solid fa-camera me-1"></i>PhotoPass</span>' : ''}
              ${item.addon_buffet       ? '<span class="badge rounded-0" style="background:#14532d;font-size:.6rem;"><i class="fa-solid fa-utensils me-1"></i>Buffet</span>' : ''}
              ${item.addon_parking      ? '<span class="badge rounded-0" style="background:#1e3a5f;font-size:.6rem;"><i class="fa-solid fa-square-parking me-1"></i>Parking</span>' : ''}
            </div>
          </div>
        </div>
      </td>
      <td><span class="status-badge ${item.ticket_type === "pase_rapido" ? "badge-confirmado" : "badge-pendiente"}" style="font-size:.65rem;">${carritoType(item.ticket_type)}</span></td>
      <td class="text-muted">${carritoDate(item.visit_date)}</td>
      <td class="text-center">
        <div class="d-flex align-items-center justify-content-center gap-1">
          <button class="btn btn-outline-secondary btn-sm rounded-0 px-2 py-0 btn-qty-minus" data-index="${idx}" data-qty="${item.quantity}" ${item.quantity <= 1 ? 'disabled' : ''} title="Reducir cantidad">
            <i class="fa-solid fa-chevron-down" style="font-size:.65rem;"></i>
          </button>
          <span class="qty-display fw-bold text-white" style="min-width:1.4rem;text-align:center;">${item.quantity}</span>
          <button class="btn btn-outline-secondary btn-sm rounded-0 px-2 py-0 btn-qty-plus" data-index="${idx}" data-qty="${item.quantity}" ${item.quantity >= 10 ? 'disabled' : ''} title="Aumentar cantidad">
            <i class="fa-solid fa-chevron-up" style="font-size:.65rem;"></i>
          </button>
        </div>
      </td>
      <td class="text-end text-muted">${carritoFmt(item.unit_price)}</td>
      <td class="text-end fw-bold text-success pe-3">${carritoFmt(item.total)}</td>
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
      await carritoApiPost(CARRITO_API + "?action=remove_from_cart", {
        index: btn.dataset.index,
      });
      window.updateCartBadge?.();
      await loadCart();
    });
  });

  // Cantidad: subir / bajar
  tbody.querySelectorAll(".btn-qty-minus, .btn-qty-plus").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const idx    = parseInt(btn.dataset.index);
      const oldQty = parseInt(btn.dataset.qty);
      const delta  = btn.classList.contains("btn-qty-plus") ? 1 : -1;
      const newQty = Math.min(10, Math.max(1, oldQty + delta));
      if (newQty === oldQty) return;

      btn.disabled = true;
      const res = await carritoApiPost(CARRITO_API + "?action=update_cart_item", {
        index: idx, quantity: newQty,
      });
      if (res.success) {
        await loadCart();          // refresca fila y totales
      } else {
        btn.disabled = false;
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', initCart);
