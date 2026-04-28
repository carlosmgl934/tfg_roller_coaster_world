/**
 * cart.js — Badge global del carrito en el navbar
 * Se carga en el header para todos los usuarios logueados
 */
(async function () {
  try {
    const res = await fetch(window.BASE_URL + '/api/php/tickets.php?action=get_cart', { credentials: 'include' });
    const data = await res.json();
    const count = data?.count ?? 0;
    const badge = document.getElementById('cart-nav-badge');
    if (badge) {
      badge.textContent = count;
      badge.classList.toggle('d-none', count === 0);
    }
  } catch (e) { /* silencioso */ }
})();
