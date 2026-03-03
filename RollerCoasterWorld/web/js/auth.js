// Firebase config
const firebaseConfig = {
  apiKey: "AIzaSyAUFVSu8EvuFgeNgnQj4BH4MTuX0r_9qXY",
  authDomain: "tfg-roller-coaster-world-auth.firebaseapp.com",
  projectId: "tfg-roller-coaster-world-auth",
  storageBucket: "tfg-roller-coaster-world-auth.appspot.com",
  messagingSenderId: "882619658485",
  appId: "1:882619658485:web:568601d00570ca35dd55bc",
  measurementId: "G-6Y2GK2L79D",
};

// Inicializar Firebase solo una vez
if (typeof firebase !== "undefined" && !firebase.apps.length) {
  firebase.initializeApp(firebaseConfig);
}
const auth = firebase.auth();

// BASE_URL es inyectada por PHP en el header. Fallback por si acaso.
const BASE = window.BASE_URL || "";

console.log(
  "Firebase inicializado correctamente - auth.js cargado OK | BASE:",
  BASE,
);

// ── Registro con Email y Password ─────────────────────────────────────────────
function signUpWithEmail(email, password) {
  if (!email || !password) {
    alert("Completa email y contraseña");
    return;
  }
  if (password.length < 6) {
    alert("La contraseña debe tener al menos 6 caracteres");
    return;
  }

  auth
    .createUserWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      console.log("Registro exitoso! UID:", user.uid);
      alert("¡Registro completado!");

      user.getIdToken().then((idToken) => {
        fetch(BASE + "/api/php/auth.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id_token: idToken,
            username: user.displayName || user.email.split("@")[0],
          }),
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) console.log("Usuario guardado en Supabase");
            else console.warn("Supabase:", data.message);
          })
          .catch((err) => console.error("Error Supabase:", err));
      });

      window.location.href = BASE + "/web/views/home.php";
    })
    .catch((error) => {
      let msg = "Error al registrar: ";
      if (error.code === "auth/email-already-in-use")
        msg += "El email ya está registrado.";
      else if (error.code === "auth/invalid-email")
        msg += "El email no es válido.";
      else if (error.code === "auth/weak-password")
        msg += "Contraseña muy débil (mínimo 6 caracteres).";
      else msg += error.message;
      alert(msg);
    });
}

// ── Login con Email y Password ────────────────────────────────────────────────
function signInWithEmail(email, password) {
  if (!email || !password) {
    alert("Completa email y contraseña");
    return;
  }

  auth
    .signInWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      console.log("Login exitoso! UID:", user.uid);

      // ── Si hay un borrado pendiente por requires-recent-login, ejecutarlo ahora ──
      if (sessionStorage.getItem("pending_delete") === "1") {
        sessionStorage.removeItem("pending_delete");
        user
          .delete()
          .then(() => {
            fetch(BASE + "/api/php/delete_user.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ firebase_uid: user.uid }),
            }).catch((e) => console.error("Error borrando de Supabase:", e));
            alert("Cuenta eliminada correctamente.");
            window.location.href = BASE + "/web/firebase/auth/login.php";
          })
          .catch((err) => alert("Error al eliminar la cuenta: " + err.message));
        return; // No continuar con el flujo de login normal
      }

      user.getIdToken().then((idToken) => {
        fetch(BASE + "/api/php/save_session.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ firebase_uid: user.uid, email: user.email }),
        })
          .then((r) => r.json())
          .then((d) => console.log("Sesión PHP:", d));

        fetch(BASE + "/api/php/auth.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id_token: idToken,
            username: user.displayName || user.email.split("@")[0],
          }),
        })
          .then((r) => r.json())
          .then((d) => console.log("Sync Supabase:", d));
      });

      alert("¡Bienvenido!");
      window.location.href = BASE + "/web/views/home.php";
    })
    .catch((error) => {
      let msg = "Error al iniciar sesión: ";
      if (error.code === "auth/user-not-found") msg += "Usuario no encontrado.";
      else if (error.code === "auth/wrong-password")
        msg += "Contraseña incorrecta.";
      else if (error.code === "auth/invalid-email") msg += "Email inválido.";
      else msg += error.message;
      alert(msg);
    });
}

// ── Login con Google ──────────────────────────────────────────────────────────
function signInWithGoogle() {
  const provider = new firebase.auth.GoogleAuthProvider();
  auth
    .signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log("Login Google OK:", user.uid);

      // ── Si hay un borrado pendiente por requires-recent-login, ejecutarlo ahora ──
      if (sessionStorage.getItem("pending_delete") === "1") {
        sessionStorage.removeItem("pending_delete");
        user
          .delete()
          .then(() => {
            fetch(BASE + "/api/php/delete_user.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ firebase_uid: user.uid }),
            }).catch((e) => console.error("Error borrando de Supabase:", e));
            alert("Cuenta eliminada correctamente.");
            window.location.href = BASE + "/web/firebase/auth/login.php";
          })
          .catch((err) => alert("Error al eliminar la cuenta: " + err.message));
        return;
      }

      user.getIdToken().then((idToken) => {
        fetch(BASE + "/api/php/save_session.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ firebase_uid: user.uid, email: user.email }),
        })
          .then((r) => r.json())
          .then((d) => console.log("Sesión PHP:", d));

        fetch(BASE + "/api/php/auth.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id_token: idToken,
            username: user.displayName || user.email.split("@")[0],
          }),
        })
          .then((r) => r.json())
          .then((d) => console.log("Sync Supabase (Google):", d));
      });

      alert("¡Bienvenido con Google!");
      window.location.href = BASE + "/web/views/home.php";
    })
    .catch((error) => alert("Error con Google: " + error.message));
}

// ── Login con Facebook ────────────────────────────────────────────────────────
function signInWithFacebook() {
  const provider = new firebase.auth.FacebookAuthProvider();
  auth
    .signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log("Login Facebook OK:", user.uid);

      user.getIdToken().then((idToken) => {
        fetch(BASE + "/api/php/save_session.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ firebase_uid: user.uid, email: user.email }),
        })
          .then((r) => r.json())
          .then((d) => console.log("Sesión PHP:", d));

        fetch(BASE + "/api/php/auth.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            id_token: idToken,
            username: user.displayName || user.email.split("@")[0],
          }),
        })
          .then((r) => r.json())
          .then((d) => console.log("Sync Supabase (Facebook):", d));
      });

      alert("¡Bienvenido con Facebook!");
      window.location.href = BASE + "/web/views/home.php";
    })
    .catch((error) => alert("Error con Facebook: " + error.message));
}

// ── Logout ────────────────────────────────────────────────────────────────────
function signOut() {
  auth
    .signOut()
    .then(() => {
      fetch(BASE + "/api/php/logout.php", { method: "POST" });
      alert("Sesión cerrada");
      window.location.href = BASE + "/web/firebase/auth/login.php";
    })
    .catch((error) => console.error("Error logout:", error));
}

// ── Estado de autenticación ───────────────────────────────────────────────────
auth.onAuthStateChanged((user) => {
  if (user)
    console.log("Usuario logueado:", user.uid, user.email || user.displayName);
  else console.log("No hay usuario logueado");
});

// ── Protección de rutas privadas ──────────────────────────────────────────────
auth.onAuthStateChanged((user) => {
  const privatePages = [
    "/web/views/profile.php",
    "/web/views/carrito.php",
    "/web/views/trips.php",
    "/web/views/home.php",
    "/web/views/admin.php",
    "/web/views/coasters.php",
    "/web/views/index.php",
    "/web/views/parks.php",
  ];

  const path = window.location.pathname;
  if (privatePages.some((p) => path.endsWith(p) || path.includes(p))) {
    if (!user) {
      console.log("Acceso denegado: no logueado");
      window.location.href = BASE + "/web/firebase/auth/login.php";
    }
  }
});

// ── Cambiar contraseña ────────────────────────────────────────────────────────
function toggleFormPassword() {
  const form = document.getElementById("form-password");
  if (!form) return;
  form.style.display = form.style.display === "none" ? "block" : "none";
  document.getElementById("nueva-password").value = "";
  document.getElementById("confirmar-password").value = "";
  document.getElementById("msg-password").textContent = "";
}

function cambiarPassword() {
  const nueva = document.getElementById("nueva-password").value;
  const confirma = document.getElementById("confirmar-password").value;
  const msg = document.getElementById("msg-password");

  if (nueva.length < 6) {
    msg.textContent = "Mínimo 6 caracteres";
    msg.style.color = "red";
    return;
  }
  if (nueva !== confirma) {
    msg.textContent = "Las contraseñas no coinciden";
    msg.style.color = "red";
    return;
  }

  auth.currentUser
    .updatePassword(nueva)
    .then(() => {
      msg.textContent = "Contraseña cambiada correctamente.";
      msg.style.color = "green";
      setTimeout(toggleFormPassword, 2000);
    })
    .catch((err) => {
      msg.textContent =
        err.code === "auth/requires-recent-login"
          ? "Tu sesión ha expirado. Reincia la sesión y repite la operación."
          : "Error: " + err.message;
      msg.style.color = "red";
    });
}

// ── Eliminar cuenta ───────────────────────────────────────────────────────────
function borrarCuenta() {
  const user = auth.currentUser;
  if (!user) {
    alert("Debes iniciar sesión para eliminar la cuenta");
    return;
  }

  if (
    !confirm(
      "¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer",
    )
  )
    return;

  user
    .delete()
    .then(() => {
      //Borrar cuenta en Supabase
      fetch(BASE + "/api/php/delete_user.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ firebase_uid: user.uid }),
      })
        .then((r) => r.json())
        .catch((err) =>
          console.error("Error al eliminar usuario de Supabase:", err),
        );

      alert("Cuenta eliminada correctamente");
      window.location.href = BASE + "/web/firebase/auth/login.php";
    })
    .catch((error) => {
      if (error.code === "auth/requires-recent-login") {
        // Guardar marca para reintentar el borrado automáticamente tras el login
        sessionStorage.setItem("pending_delete", "1");
        alert(
          "Por seguridad, necesitas volver a iniciar sesión antes de eliminar tu cuenta.\n\nInicia sesión y la cuenta se eliminará automáticamente.",
        );
        // Cerrar sesión PHP + Firebase y redirigir al login
        fetch(BASE + "/api/php/logout.php", { method: "POST" }).finally(() => {
          auth.signOut().then(() => {
            window.location.href = BASE + "/web/firebase/auth/login.php";
          });
        });
      } else {
        alert("Error al eliminar la cuenta: " + error.message);
      }
    });
}
