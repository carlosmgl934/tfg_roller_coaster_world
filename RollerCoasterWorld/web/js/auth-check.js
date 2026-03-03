// auth-check.js — protección de rutas usando BASE_URL inyectada por PHP
const BASE_CHECK = window.BASE_URL || "";

auth.onAuthStateChanged((user) => {
  const privatePages = [
    "/web/views/profile.php",
    "/web/views/carrito.php",
    "/web/views/trips.php",
    "/web/views/admin.php",
    "/web/views/home.php",
    "/web/views/coasters.php",
    "/web/views/parks.php",
  ];

  const path = window.location.pathname;

  if (privatePages.some((p) => path.endsWith(p) || path.includes(p))) {
    if (!user) {
      console.log("Acceso denegado: no logueado en página privada");
      window.location.href = BASE_CHECK + "/web/firebase/auth/login.php";
    } else {
      console.log("Usuario logueado en página privada:", user.uid);
    }
  }
});
