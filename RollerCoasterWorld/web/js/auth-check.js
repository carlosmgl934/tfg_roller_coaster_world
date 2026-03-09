// auth-check.js — protección de rutas usando BASE_URL inyectada por PHP
const BASE_CHECK = window.BASE_URL || "";

if (typeof firebase !== "undefined" && !firebase.apps.length) {
  firebase.initializeApp({
    apiKey: "AIzaSyAUFVSu8EvuFgeNgnQj4BH4MTuX0r_9qXY",
    authDomain: "tfg-roller-coaster-world-auth.firebaseapp.com",
    projectId: "tfg-roller-coaster-world-auth",
    storageBucket: "tfg-roller-coaster-world-auth.appspot.com",
    messagingSenderId: "882619658485",
    appId: "1:882619658485:web:568601d00570ca35dd55bc",
  });
}
if (!window.auth) {
  window.auth = firebase.app().auth();
}

window.auth.onAuthStateChanged((user) => {
  const privatePages = [
    // --- Vistas Privadas de Usuario ---
    "/home.php",
    "/profile.php",
    "/carrito.php",
    "/trips.php",
    "/forums.php",
    "/friends.php",
    "/checkout.php",
    "/orders.php",

    // --- Panel de Administrador (Todas son privadas) ---
    "/admin.php",
    "/dashboard.php",
    "/users.php",
    "/admin/coasters.php", // La vista de admin/coasters.php
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
