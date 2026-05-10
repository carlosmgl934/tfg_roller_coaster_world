/**
 * cart.js — Carrito de compra RollerCoasterWorld
 * Gestiona: badge del navbar, página del carrito (/carrito)
 */

// ── Constante API (tickets.js puede haber definido ya TICKETS_API)
const CART_TICKETS_API = window.TICKETS_API || window.BASE_URL + "/api/php/tickets.php";

// ── Utilidades compartidas ──────────────────────────────────────────
const cartFmt    = (v) => parseFloat(v).toFixed(2) + " \u20AC";
const cartDate   = (d) => d ? new Date(d + "T00:00:00").toLocaleDateString("es-ES", { day: "2-digit", month: "long", year: "numeric" }) : "?";
const cartType   = (t) => t === "pase_rapido" ? "Pase R\u00e1pido" : "Entrada General";

async function cartApiPost(url, data) {
  const fd = new FormData();
  Object.entries(data).forEach(([k, v]) => fd.append(k, v));
  const r = await fetch(url, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, method: "POST", body: fd, credentials: "include" } );
  return r.json();
}
async function cartApiGet(url) {
  const r = await fetch(url, { credentials: "include" });
  return r.json();
}

// ── Badge global (navbar + dropdown) ────────────────────────────────
function updateNavBadge(count) {
  const badges  = document.querySelectorAll(".cart-nav-badge");
  const iconWrap = document.getElementById("cart-nav-icon-wrap");
  badges.forEach(b => { b.textContent = count; b.classList.toggle('d-none', count === 0); });
  if (iconWrap) {
    if (count > 0) { iconWrap.style.removeProperty('display'); iconWrap.classList.remove('d-none'); }
    else           { iconWrap.style.display = 'none'; iconWrap.classList.add('d-none'); }
  }
}

// Inicializar badge al cargar (para todas las p\u00e1ginas del header)
(async function initNavBadge() {
  try {
    const data = await cartApiGet(CART_TICKETS_API + '?action=get_cart');
    updateNavBadge(data?.count ?? 0);
  } catch (e) { /* silencioso */ }
})();

// Exponemos la funci\u00f3n para que tickets.js la llame si necesita actualizar el badge
window.updateCartBadge = async function () {
  try {
    const data = await cartApiGet(CART_TICKETS_API + '?action=get_cart');
    updateNavBadge(data?.count ?? 0);
  } catch (e) {}
};

