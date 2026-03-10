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
window.auth = firebase.auth();

// BASE_URL es inyectada por PHP en el header. Fallback por si acaso.
const BASE = window.BASE_URL || "";

console.log(
  "Firebase inicializado correctamente - auth.js cargado OK | BASE:",
  BASE,
);

// ── Helpers de modal ──────────────────────────────────────────────────────────
function showAlert(msg) {
  const existing = document.getElementById("auth-modal");
  if (existing) existing.remove();

  const html = `
  <div class="modal fade" id="auth-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-body text-center p-4">
          <p class="mb-3" style="font-size:1rem;">${msg}</p>
          <button class="btn btn-success px-4" data-bs-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>`;
  document.body.insertAdjacentHTML("beforeend", html);
  const modal = new bootstrap.Modal(document.getElementById("auth-modal"));
  modal.show();
}

function showConfirm(msg, onConfirm) {
  const existing = document.getElementById("auth-confirm-modal");
  if (existing) existing.remove();

  const html = `
  <div class="modal fade" id="auth-confirm-modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow">
        <div class="modal-body text-center p-4">
          <p class="mb-4" style="font-size:1rem;">${msg}</p>
          <div class="d-flex gap-2 justify-content-center">
            <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger px-4" id="confirm-yes-btn">Confirmar</button>
          </div>
        </div>
      </div>
    </div>
  </div>`;
  document.body.insertAdjacentHTML("beforeend", html);
  const modal = new bootstrap.Modal(
    document.getElementById("auth-confirm-modal"),
  );
  modal.show();
  document
    .getElementById("confirm-yes-btn")
    .addEventListener("click", function () {
      modal.hide();
      onConfirm();
    });
}

$(document).ready(function () {
  let registro = document.getElementById("signUpWithEmail");
  let registroGoogle = document.getElementById("signInWithGoogle");
  let registroFacebook = document.getElementById("signInWithFacebook");
  let login = document.getElementById("signInWithEmail");
  let logOut = document.getElementById("signOut");
  let cambiarPsw = document.getElementById("cambiarPassword");
  let togglePsw = document.getElementById("toggleFormPassword");
  let eliminar = document.getElementById("borrarCuenta");

  if (registro) registro.addEventListener("click", signUpWithEmail);
  if (registroGoogle)
    registroGoogle.addEventListener("click", signInWithGoogle);
  if (registroFacebook)
    registroFacebook.addEventListener("click", signInWithFacebook);
  if (login) login.addEventListener("click", signInWithEmail);
  if (cambiarPsw) cambiarPsw.addEventListener("click", cambiarPassword);
  if (togglePsw) togglePsw.addEventListener("click", toggleFormPassword);
  if (eliminar) eliminar.addEventListener("click", borrarCuenta);

  if (logOut)
    logOut.addEventListener("click", function () {
      fetch(BASE + "/api/php/logout.php", { method: "POST" }).finally(() => {
        window.auth.signOut().then(() => {
          window.location.href = BASE + "/web/views/auth/login.php";
        });
      });
    });

  // ── Registro con Email y Password ─────────────────────────────────────────────
  function signUpWithEmail() {
    email = document.getElementById("email").value;
    password = document.getElementById("password").value;

    if (!email || !password) {
      showAlert("Completa email y contraseña");
      return;
    }
    if (password.length < 6) {
      showAlert("La contraseña debe tener al menos 6 caracteres");
      return;
    }

    window.auth
      .createUserWithEmailAndPassword(email, password)
      .then((userCredential) => {
        const user = userCredential.user;
        console.log("Registro exitoso! UID:", user.uid);
        showAlert("¡Registro completado!");
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
        window.location.href = BASE + "/web/views/public/index.php";
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
        showAlert(msg);
      });
  }

  // ── Login con Email y Password ────────────────────────────────────────────────
  function signInWithEmail() {
    email = document.getElementById("email").value;
    password = document.getElementById("password").value;

    if (!email || !password) {
      showAlert("Completa email y contraseña");
      return;
    }

    window.auth
      .signInWithEmailAndPassword(email, password)
      .then((userCredential) => {
        const user = userCredential.user;
        console.log("Login exitoso! UID:", user.uid);

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
              showAlert("Cuenta eliminada correctamente.");
              window.location.href = BASE + "/web/views/auth/login.php";
            })
            .catch((err) =>
              showAlert("Error al eliminar la cuenta: " + err.message),
            );
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
            .then((d) => console.log("Sync Supabase:", d));
        });

        showAlert("¡Bienvenido!");
        window.location.href = BASE + "/web/views/public/index.php";
      })
      .catch((error) => {
        let msg = "Error al iniciar sesión: ";
        if (error.code === "auth/user-not-found")
          msg += "Usuario no encontrado.";
        else if (error.code === "auth/wrong-password")
          msg += "Contraseña incorrecta.";
        else if (error.code === "auth/invalid-email") msg += "Email inválido.";
        else msg += error.message;
        showAlert(msg);
      });
  }

  // ── Login con Google ──────────────────────────────────────────────────────────
  function signInWithGoogle() {
    const provider = new firebase.auth.GoogleAuthProvider();
    window.auth
      .signInWithPopup(provider)
      .then((result) => {
        const user = result.user;
        console.log("Login Google OK:", user.uid);

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
              showAlert("Cuenta eliminada correctamente.");
              window.location.href = BASE + "/web/views/auth/login.php";
            })
            .catch((err) =>
              showAlert("Error al eliminar la cuenta: " + err.message),
            );
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

        showAlert("¡Bienvenido con Google!");
        window.location.href = BASE + "/web/views/public/index.php";
      })
      .catch((error) => showAlert("Error con Google: " + error.message));
  }

  // ── Login con Facebook ────────────────────────────────────────────────────────
  function signInWithFacebook() {
    const provider = new firebase.auth.FacebookAuthProvider();
    window.auth
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
        showAlert("¡Bienvenido con Facebook!");
        window.location.href = BASE + "/web/views/public/index.php";
      })
      .catch((error) => showAlert("Error con Facebook: " + error.message));
  }

  // ── Estado de autenticación ───────────────────────────────────────────────────
  window.auth.onAuthStateChanged((user) => {
    if (user)
      console.log(
        "Usuario logueado:",
        user.uid,
        user.email || user.displayName,
      );
    else console.log("No hay usuario logueado");
  });

  // ── Protección de rutas privadas ──────────────────────────────────────────────
  window.auth.onAuthStateChanged((user) => {
    const privatePages = [
      "/web/views/profile.php",
      "/web/views/carrito.php",
      "/web/views/trips.php",
      "/web/views/admin.php",
      "/web/views/coasters.php",
      "/web/views/index.php",
      "/web/views/parks.php",
    ];
    const path = window.location.pathname;
    if (privatePages.some((p) => path.endsWith(p) || path.includes(p))) {
      if (!user) {
        console.log("Acceso denegado: no logueado");
        window.location.href = BASE + "/web/views/auth/login.php";
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

    window.auth.currentUser
      .updatePassword(nueva)
      .then(() => {
        msg.textContent = "Contraseña cambiada correctamente.";
        msg.style.color = "green";
        setTimeout(toggleFormPassword, 2000);
      })
      .catch((err) => {
        msg.textContent =
          err.code === "auth/requires-recent-login"
            ? "Tu sesión ha expirado. Reinicia la sesión y repite la operación."
            : "Error: " + err.message;
        msg.style.color = "red";
      });
  }

  // ── Eliminar cuenta ───────────────────────────────────────────────────────────
  function borrarCuenta() {
    const user = window.auth.currentUser;
    if (!user) {
      showAlert("Debes iniciar sesión para eliminar la cuenta");
      return;
    }

    showConfirm(
      "¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer",
      function () {
        user
          .delete()
          .then(() => {
            fetch(BASE + "/api/php/delete_user.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({ firebase_uid: user.uid }),
            })
              .then((r) => r.json())
              .catch((err) =>
                console.error("Error al eliminar usuario de Supabase:", err),
              );
            showAlert("Cuenta eliminada correctamente");
            window.location.href = BASE + "/web/views/auth/login.php";
          })
          .catch((error) => {
            if (error.code === "auth/requires-recent-login") {
              sessionStorage.setItem("pending_delete", "1");
              showAlert(
                "Por seguridad, necesitas volver a iniciar sesión antes de eliminar tu cuenta. Inicia sesión y la cuenta se eliminará automáticamente.",
              );
              fetch(BASE + "/api/php/logout.php", { method: "POST" }).finally(
                () => {
                  window.auth.signOut().then(() => {
                    window.location.href = BASE + "/web/views/auth/login.php";
                  });
                },
              );
            } else {
              showAlert("Error al eliminar la cuenta: " + error.message);
            }
          });
      },
    );
  }
});
