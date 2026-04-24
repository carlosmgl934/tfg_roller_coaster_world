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
window.storage = firebase.storage();

// BASE_URL es inyectada por PHP en el header. Fallback por si acaso.
const BASE = window.BASE_URL || "";

console.log(
  "Firebase inicializado correctamente - auth.js cargado OK | BASE:",
  BASE,
);

// Helper global para avatares (importante al tener nombres de archivo locales sueltos)
window.rcwGetAvatarPath = function(imgSrc, username = 'Usuario', color1 = '198754', textCol = 'fff') {
    if (!imgSrc) return window.BASE_URL + '/web/img/avatars/default_avatar.svg';
    if (imgSrc.startsWith("http://") || imgSrc.startsWith("https://")) return imgSrc;
    if (imgSrc.startsWith("/")) return window.BASE_URL + imgSrc;
    // Si solo hay un nombre pelado ("1774886670_xxxx.webp"), está en Supabase Storage
    return "https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/" + imgSrc;
};

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
  let cancelar = document.getElementById("btn-cancelar-password");
  let forgotPasswordBtn = document.getElementById("forgotPasswordBtn");

  if (registro) registro.addEventListener("click", signUpWithEmail);
  if (registroGoogle)
    registroGoogle.addEventListener("click", signInWithGoogle);
  if (registroFacebook)
    registroFacebook.addEventListener("click", signInWithFacebook);
  if (login) login.addEventListener("click", signInWithEmail);
  if (cambiarPsw) cambiarPsw.addEventListener("click", cambiarPassword);
  if (togglePsw) togglePsw.addEventListener("click", toggleFormPassword);
  if (eliminar) eliminar.addEventListener("click", borrarCuenta);
  if (cancelar) cancelar.addEventListener("click", cancelarPassword);
  if (forgotPasswordBtn) forgotPasswordBtn.addEventListener("click", resetPassword);

  let usernameAvailable = false;
  let passwordValid = false;

  const usernameInput = document.getElementById("username");
  if (usernameInput) {
    usernameInput.addEventListener("blur", function() {
      const val = this.value.trim();
      const feedback = document.getElementById("username-feedback");
      if (!val) {
        feedback.style.display = "none";
        usernameAvailable = false;
        return;
      }
      fetch(BASE + "/api/php/auth/check_username.php?username=" + encodeURIComponent(val))
        .then(r => r.json())
        .then(data => {
          feedback.style.display = "block";
          if (data.available) {
            feedback.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i><span class="text-success">Usuario disponible</span>';
            usernameAvailable = true;
          } else {
            const errorMsg = data.error || 'Este nombre de usuario ya está en uso';
            feedback.innerHTML = '<i class="fa-solid fa-xmark text-danger me-1"></i><span class="text-danger">' + errorMsg + '</span>';
            usernameAvailable = false;
          }
        })
        .catch(err => console.error(err));
    });
  }

  const registerPasswordInput = document.getElementById("password");
  const reqLength = document.getElementById("req-length");
  const reqUpper = document.getElementById("req-upper");
  const reqLower = document.getElementById("req-lower");
  const reqNumber = document.getElementById("req-number");

  if (registerPasswordInput && reqLength) {
    registerPasswordInput.addEventListener("input", function() {
      const val = this.value;
      const setValid = (el, valid, text) => {
        if (valid) {
          el.innerHTML = '<i class="fa-solid fa-check text-success me-2"></i><span class="text-success">' + text + '</span>';
        } else {
          el.innerHTML = '<i class="fa-solid fa-xmark text-danger me-2"></i><span class="text-muted">' + text + '</span>';
        }
      };

      const hasLen = val.length >= 6;
      const hasUpper = /[A-Z]/.test(val);
      const hasLower = /[a-z]/.test(val);
      const hasNum = /[0-9]/.test(val);

      setValid(reqLength, hasLen, "Mínimo 6 caracteres");
      setValid(reqUpper, hasUpper, "Al menos 1 mayúscula (A-Z)");
      setValid(reqLower, hasLower, "Al menos 1 minúscula (a-z)");
      setValid(reqNumber, hasNum, "Al menos 1 número (0-9)");

      passwordValid = hasLen && hasUpper && hasLower && hasNum;
    });
  }

  // Iniciar sesión también con Enter
  const passwordInput = document.getElementById("password");
  if (passwordInput) {
    passwordInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        if (document.getElementById("signUpWithEmail")) {
          signUpWithEmail();
        } else if (document.getElementById("signInWithEmail")) {
          signInWithEmail();
        }
      }
    });
  }

  // Logout handler para todos los botones de cerrar sesión
  const logoutButtons = document.querySelectorAll(".signOutBtn, #signOut");
  logoutButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      fetch(BASE + "/api/php/auth/logout.php", { method: "POST" }).finally(() => {
        window.auth.signOut().then(() => {
          window.location.href = BASE + "/web/views/auth/login.php";
        });
      });
    });
  });

  // ── Funciones de Verificación ─────────────────────────────────────────────────
  window.cancelVerification = function() {
    if(window.verificationInterval) clearInterval(window.verificationInterval);
    const modal = bootstrap.Modal.getInstance(document.getElementById("verification-modal"));
    if(modal) modal.hide();
    window.auth.signOut().then(() => {
        window.location.href = BASE + "/web/views/auth/login.php";
    });
  }

  function showVerificationModal(user) {
    const existing = document.getElementById("verification-modal");
    if (existing) existing.remove();

    const html = `
    <div class="modal fade" id="verification-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
          <div class="modal-body text-center p-5">
            <div class="mb-4">
              <i class="fa-regular fa-envelope-open fa-4x text-success animate__animated animate__pulse animate__infinite"></i>
            </div>
            <h3 class="fw-bold mb-3">Revisa tu correo</h3>
            <p class="text-muted mb-4">
              Hemos enviado un enlace de verificación a <br><strong>${user.email}</strong>.<br><br>
              Haz clic en el enlace para activar tu cuenta. Esta ventana te iniciará sesión automáticamente cuando lo hagas.
            </p>
            <div class="spinner-border text-success" role="status">
              <span class="visually-hidden">Esperando verificación...</span>
            </div>
            <p class="mt-3 text-secondary small">Esperando confirmación...</p>
            <button class="btn btn-outline-danger mt-4 rounded-pill px-4" onclick="window.cancelVerification()">Cancelar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML("beforeend", html);
    const modal = new bootstrap.Modal(document.getElementById("verification-modal"));
    modal.show();

    // Polling Verification
    window.verificationInterval = setInterval(() => {
      user.reload().then(() => {
        if (user.emailVerified) {
          clearInterval(window.verificationInterval);
          modal.hide();
          setupSessionAndRedirect(user);
        }
      }).catch(console.error);
    }, 3000);
  }

  function setupSessionAndRedirect(user) {
    user.getIdToken().then((idToken) => {
      // Sincronizar con Supabase en paralelo (no bloqueante)
      fetch(BASE + "/api/php/auth/auth.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          id_token: idToken,
          username: user.displayName || user.email.split("@")[0],
        }),
      })
        .then((r) => r.json())
        .then((d) => console.log("Sync Supabase:", d))
        .catch(console.error);

      // Guardar sesión PHP y redirigir
      const params = new URLSearchParams(window.location.search);
      const redirectUrl = params.get("redirect") || BASE + "/web/views/public/index.php";

      fetch(BASE + "/api/php/auth/save_session.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ firebase_uid: user.uid, email: user.email }),
      })
        .then((r) => r.json())
        .then((d) => {
          console.log("Sesión PHP:", d);
          showAlert("¡Verificación completada! Bienvenido.");
          setTimeout(() => { window.location.href = redirectUrl; }, 1200);
        })
        .catch(() => {
          showAlert("¡Verificación completada! Bienvenido.");
          setTimeout(() => { window.location.href = redirectUrl; }, 1200);
        });
    });
  }

  // ── Registro con Email y Password ─────────────────────────────────────────────
  function signUpWithEmail() {
    email = document.getElementById("email").value;
    password = document.getElementById("password").value;

    if (!email || !password) {
      showAlert("Completa email y contraseña");
      return;
    }
    const isRegisterPage = document.getElementById("username") !== null;
    if (isRegisterPage && !usernameAvailable) {
      showAlert("Por favor, elige un nombre de usuario válido y disponible.");
      return;
    }
    if (isRegisterPage && !passwordValid) {
      showAlert("La contraseña no cumple con los requisitos de seguridad.");
      return;
    }
    if (!isRegisterPage && password.length < 6) {
      showAlert("La contraseña debe tener al menos 6 caracteres");
      return;
    }

    window.auth
      .createUserWithEmailAndPassword(email, password)
      .then((userCredential) => {
        const user = userCredential.user;
        console.log("Registro exitoso! UID:", user.uid);

        // 1. Enviar correo de verificación
        user.sendEmailVerification().catch(console.error);

        // 2. Guardar en base de datos local (aunque no esté verificado, para tener el registro)
        user.getIdToken().then((idToken) => {
          fetch(BASE + "/api/php/auth/auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id_token: idToken,
              username: document.getElementById("username") ? document.getElementById("username").value.trim() : (user.displayName || user.email.split("@")[0]),
            }),
          })
            .then((r) => r.json())
            .then((data) => {
              if (data.success) console.log("Usuario guardado en Supabase");
              else console.warn("Supabase:", data.message);
            })
            .catch((err) => console.error("Error Supabase:", err));
        });

        // 3. Mostrar modal de verificación y esperar
        showVerificationModal(user);
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
        console.log("Credenciales correctas. UID:", user.uid);

        // 1. Verificar si el email está confirmado
        if (!user.emailVerified) {
            user.sendEmailVerification().catch(console.error);
            showVerificationModal(user);
            return;
        }

        console.log("Login exitoso y verificado! UID:", user.uid);

        if (sessionStorage.getItem("pending_delete") === "1") {
          sessionStorage.removeItem("pending_delete");
          user
            .delete()
            .then(() => {
              fetch(BASE + "/api/php/auth/delete_user.php", {
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

        setupSessionAndRedirect(user);
      })
      .catch((error) => {
        let msg;
        switch (error.code) {
          case "auth/user-not-found":
            msg =
              "No existe ninguna cuenta con este email. ¿Quieres registrarte?";
            break;
          case "auth/wrong-password":
            msg = "Contraseña incorrecta. Revísala e inténtalo de nuevo.";
            break;
          case "auth/invalid-email":
            msg = "El formato del email no es válido.";
            break;
          case "auth/user-disabled":
            msg =
              "Esta cuenta ha sido desactivada. Contacta con el administrador.";
            break;
          case "auth/too-many-requests":
            msg =
              "Demasiados intentos fallidos. Espera unos minutos antes de volver a intentarlo.";
            break;
          case "auth/invalid-credential":
            msg =
              "Email o contraseña incorrectos. Comprueba tus datos e inténtalo de nuevo.";
            break;
          case "auth/network-request-failed":
            msg =
              "Sin conexión a internet. Revisa tu red e inténtalo de nuevo.";
            break;
          default:
            msg = "Error al iniciar sesión: " + error.message;
        }
        showAlert(msg);
      });
  }

  // ── Restablecer Contraseña ────────────────────────────────────────────────────
  function resetPassword(e) {
    if (e) e.preventDefault();
    const emailNode = document.getElementById("email");
    let email = emailNode ? emailNode.value : null;

    // Fallback if we are in profile and already have a logged in user
    if (!email && window.auth.currentUser) {
      email = window.auth.currentUser.email;
    }

    if (!email) {
      showAlert("Por favor, introduce tu correo electrónico en el campo superior primero antes de hacer clic en recuperar contraseña.");
      return;
    }

    window.auth.sendPasswordResetEmail(email)
      .then(() => {
        showAlert("¡Listo! Hemos enviado un enlace a " + email + " para que puedas restablecer tu contraseña. Revisa también la carpeta de SPAM.");
      })
      .catch((error) => {
        let msg = "Error al restablecer contraseña: ";
        if (error.code === 'auth/user-not-found') msg = "No hay ninguna cuenta registrada con este correo.";
        else if (error.code === 'auth/invalid-email') msg = "El correo electrónico no es válido.";
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
              fetch(BASE + "/api/php/auth/delete_user.php", {
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
          fetch(BASE + "/api/php/auth/auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id_token: idToken,
              username: user.displayName || user.email.split("@")[0],
            }),
          })
            .then((r) => r.json())
            .then((d) => console.log("Sync Supabase (Google):", d));

          const params = new URLSearchParams(window.location.search);
          const redirectUrl =
            params.get("redirect") || BASE + "/web/views/public/index.php";

          fetch(BASE + "/api/php/auth/save_session.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ firebase_uid: user.uid, email: user.email }),
          })
            .then((r) => r.json())
            .then((d) => {
              console.log("Sesión PHP:", d);
              showAlert("¡Bienvenido con Google!");
              window.location.href = redirectUrl;
            })
            .catch(() => {
              showAlert("¡Bienvenido con Google!");
              window.location.href = redirectUrl;
            });
        });
      })
      .catch((error) => {
        if (error.code === "auth/invalid-credential")
          showAlert(
            "Credenciales incorrectas o expiradas. Inténtalo de nuevo.",
          );
        else showAlert("Error con Google: " + error.message);
      });
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
          fetch(BASE + "/api/php/auth/auth.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              id_token: idToken,
              username: user.displayName || user.email.split("@")[0],
            }),
          })
            .then((r) => r.json())
            .then((d) => console.log("Sync Supabase (Facebook):", d));

          const params = new URLSearchParams(window.location.search);
          const redirectUrl =
            params.get("redirect") || BASE + "/web/views/public/index.php";

          fetch(BASE + "/api/php/auth/save_session.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ firebase_uid: user.uid, email: user.email }),
          })
            .then((r) => r.json())
            .then((d) => {
              console.log("Sesión PHP:", d);
              showAlert("¡Bienvenido con Facebook!");
              window.location.href = redirectUrl;
            })
            .catch(() => {
              showAlert("¡Bienvenido con Facebook!");
              window.location.href = redirectUrl;
            });
        });
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

  // ── Cambiar contraseña ────────────────────────────────────────────────────────
  function toggleFormPassword() {
    const form = document.getElementById("form-password");
    if (!form) return;
    form.style.display = form.style.display === "none" ? "block" : "none";
    if (document.getElementById("old-password")) document.getElementById("old-password").value = "";
    document.getElementById("nueva-password").value = "";
    document.getElementById("confirmar-password").value = "";
    document.getElementById("msg-password").textContent = "";
  }

  function cambiarPassword() {
    const antigua = document.getElementById("old-password").value;
    const nueva = document.getElementById("nueva-password").value;
    const confirma = document.getElementById("confirmar-password").value;
    const msg = document.getElementById("msg-password");

    if (!antigua) {
      msg.textContent = "Introduce tu contraseña actual.";
      msg.style.color = "red";
      return;
    }
    if (nueva.length < 6) {
      msg.textContent = "La nueva contraseña debe tener un mínimo de 6 caracteres";
      msg.style.color = "red";
      return;
    }
    if (nueva !== confirma) {
      msg.textContent = "Las contraseñas nuevas no coinciden";
      msg.style.color = "red";
      return;
    }

    const user = window.auth.currentUser;
    if (!user) return;
    
    // Configurar credencial y re-autenticar
    const credential = firebase.auth.EmailAuthProvider.credential(user.email, antigua);

    user.reauthenticateWithCredential(credential)
      .then(() => {
        // Una vez re-autenticado, actualizamos la contraseña
        return user.updatePassword(nueva);
      })
      .then(() => {
        msg.textContent = "Contraseña cambiada correctamente.";
        msg.style.color = "green";
        setTimeout(toggleFormPassword, 2000);
      })
      .catch((err) => {
        if (err.code === "auth/wrong-password") {
            msg.textContent = "La contraseña actual es incorrecta.";
        } else if (err.code === "auth/too-many-requests") {
            msg.textContent = "Demasiados intentos. Por favor, inténtalo más tarde.";
        } else {
            msg.textContent = "Error: " + err.message;
        }
        msg.style.color = "red";
      });
  }

  function cancelarPassword() {
    toggleFormPassword();
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
            fetch(BASE + "/api/php/auth/delete_user.php", {
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
              fetch(BASE + "/api/php/auth/logout.php", { method: "POST" }).finally(
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
