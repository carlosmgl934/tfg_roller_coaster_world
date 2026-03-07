// auth-check.js — protección de rutas usando BASE_URL inyectada por PHP
const BASE_CHECK = window.BASE_URL || "";

auth.onAuthStateChanged((user) => {
  const privatePages = [
    // --- Vistas Privadas de Usuario ---
    "/home.php",
    "/profile.php",
    "/carrito.php",
    "/trips.php",
    "/forums.php",
    "/parks.php",
    "/coaster_search.php",
    "/coaster_detail.php",
    "/park_detail.php",
    "/friends.php",
    "/checkout.php",
    "/orders.php",

    // --- Panel de Administrador (Todas son privadas) ---
    "/admin.php",
    "/dashboard.php",
    "/users.php",
    "/coasters.php", // La vista de admin/coasters.php
    "/messages.php",
    "/photos.php",
    "/comments.php",
  ];

  const path = window.location.pathname;

  if (privatePages.some((p) => path.endsWith(p) || path.includes(p))) {
    if (!user) {
      console.log("Acceso denegado: no logueado en página privada");
      window.location.href = BASE_CHECK + "/web/views/auth/login.php";
    } else {
      console.log("Usuario logueado en página privada:", user.uid);
    }
  }
});
