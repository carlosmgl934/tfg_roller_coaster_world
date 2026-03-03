// auth-check.js
auth.onAuthStateChanged((user) => {
  // Lista de páginas que requieren login (ajusta según tus rutas)
  const privatePages = [
    "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/profile.php",
    "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/carrito.php",
    "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/trips.php",
    "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/admin.php",
    // Añade más según necesites
  ];

  const currentPath = window.location.pathname;

  if (privatePages.some((page) => currentPath.includes(page))) {
    if (!user) {
      console.log("Acceso denegado: no logueado en página privada");
      window.location.href =
        "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/firebase/auth/login.php";
    } else {
      console.log("Usuario logueado en página privada:", user.uid);
    }
  }
});
