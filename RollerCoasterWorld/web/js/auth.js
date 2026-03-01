// Firebase config (tus claves reales del proyecto)
const firebaseConfig = {
  apiKey: "AIzaSyAUFVSu8EvuFgeNgnQj4BH4MTuX0r_9qXY",
  authDomain: "tfg-roller-coaster-world-auth.firebaseapp.com",
  projectId: "tfg-roller-coaster-world-auth",
  storageBucket: "tfg-roller-coaster-world-auth.appspot.com",
  messagingSenderId: "882619658485",
  appId: "1:882619658485:web:568601d00570ca35dd55bc",
  measurementId: "G-6Y2GK2L79D"
};

// Inicializar Firebase solo una vez
if (typeof firebase !== 'undefined' && !firebase.apps.length) {
  firebase.initializeApp(firebaseConfig);
}
const auth = firebase.auth();

console.log("Firebase inicializado correctamente - auth.js cargado OK");

// Registro con Email y Password
function signUpWithEmail(email, password) {
  console.log("Intentando registro con:", email);

  if (!email || !password) {
    alert("Completa email y contraseña");
    return;
  }

  if (password.length < 6) {
    alert("La contraseña debe tener al menos 6 caracteres");
    return;
  }

  auth.createUserWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      console.log("Registro exitoso! UID:", user.uid, "Email:", user.email);
      alert("¡Registro completado! UID: " + user.uid);

      // Guardar en Supabase
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email,
          username: user.displayName || user.email.split('@')[0]
        })
      })
      .then(response => response.json())
      .then(data => {
        console.log("Respuesta de Supabase:", data);
        if (data.success) {
          console.log("Usuario guardado en Supabase");
        } else {
          console.warn("No se pudo guardar en Supabase:", data.message);
        }
      })
      .catch(err => console.error("Error al guardar en Supabase:", err));

      // Redirigir
      window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/home.php";
    })
    .catch((error) => {
      console.error("Error en registro:", error.code, error.message);
      let msg = "Error al registrar: ";
      if (error.code === 'auth/email-already-in-use') msg += "El email ya está registrado.";
      else if (error.code === 'auth/invalid-email') msg += "El email no es válido.";
      else if (error.code === 'auth/weak-password') msg += "Contraseña muy débil (mínimo 6 caracteres).";
      else msg += error.message;
      alert(msg);
    });
}

// Login con Email y Password
function signInWithEmail(email, password) {
  console.log("Intentando login con:", email);

  if (!email || !password) {
    alert("Completa email y contraseña");
    return;
  }

  auth.signInWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      console.log("Login exitoso! UID:", user.uid, "Email:", user.email);

      // Guardar sesión PHP
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/save_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email
        })
      })
      .then(response => response.json())
      .then(data => {
        console.log("Sesión PHP guardada:", data);
        if (data.success) {
          console.log("Sesión PHP OK");
        } else {
          console.warn("Problema con sesión PHP:", data.message);
        }
      })
      .catch(err => console.error("Error guardando sesión PHP:", err));

      // Guardar también en Supabase (sincronizar)
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email,
          username: user.displayName || user.email.split('@')[0]
        })
      })
      .then(r => r.json())
      .then(data => console.log("Sync Supabase:", data))
      .catch(err => console.error("Error sync Supabase:", err));

      alert("¡Bienvenido!");
      window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/home.php";
    })
    .catch((error) => {
      console.error("Error en login:", error.code, error.message);
      let msg = "Error al iniciar sesión: ";
      if (error.code === 'auth/user-not-found') msg += "Usuario no encontrado.";
      else if (error.code === 'auth/wrong-password') msg += "Contraseña incorrecta.";
      else if (error.code === 'auth/invalid-email') msg += "Email inválido.";
      else msg += error.message;
      alert(msg);
    });
}

// Login con Google
function signInWithGoogle() {
  console.log("Intentando login con Google");
  const provider = new firebase.auth.GoogleAuthProvider();
  auth.signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log("Login Google OK:", user.uid);

      // Guardar sesión PHP
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/save_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email
        })
      })
      .then(r => r.json())
      .then(data => console.log("Sesión PHP guardada:", data))
      .catch(err => console.error("Error guardando sesión PHP:", err));

      // Sync con Supabase
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email,
          username: user.displayName || user.email.split('@')[0]
        })
      })
      .then(r => r.json())
      .then(data => console.log("Sync Supabase:", data))
      .catch(err => console.error("Error sync:", err));

      alert("¡Bienvenido con Google!");
      window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/home.php";
    })
    .catch((error) => {
      console.error("Error Google:", error.code, error.message);
      alert("Error con Google: " + error.message);
    });
}

// Login con Facebook
function signInWithFacebook() {
  console.log("Intentando login con Facebook");
  const provider = new firebase.auth.FacebookAuthProvider();
  auth.signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log("Login Facebook OK:", user.uid);

      // Guardar sesión PHP
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/save_session.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email
        })
      })
      .then(r => r.json())
      .then(data => console.log("Sesión PHP guardada:", data))
      .catch(err => console.error("Error guardando sesión PHP:", err));

      // Sync con Supabase
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/auth.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email,
          username: user.displayName || user.email.split('@')[0]
        })
      })
      .then(r => r.json())
      .then(data => console.log("Sync Supabase:", data))
      .catch(err => console.error("Error sync:", err));

      alert("¡Bienvenido con Facebook!");
      window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/home.php";
    })
    .catch((error) => {
      console.error("Error Facebook:", error.code, error.message);
      alert("Error con Facebook: " + error.message);
    });
}

// Logout
function signOut() {
  auth.signOut()
    .then(() => {
      console.log("Logout exitoso");
      alert("Sesión cerrada");

      // Limpiar sesión PHP (opcional)
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/logout.php', {
        method: 'POST'
      });

    window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/firebase/auth/login.php";
    })
    .catch((error) => {
      console.error("Error logout:", error);
    });
}

// Monitorear estado de autenticación
auth.onAuthStateChanged((user) => {
  if (user) {
    console.log("Usuario logueado:", user.uid, user.email || user.displayName);
  } else {
    console.log("No hay usuario logueado");
  }
});

// Protección frontend: redirige si no hay usuario logueado en páginas privadas
auth.onAuthStateChanged((user) => {
  const privatePaths = [
    '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/profile.php',
    '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/carrito.php',
    '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/trips.php',
      '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/home.php',
        '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/admin.php',
          '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/coasters.php',
            '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/index.php',
              '/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/parks.php',
    // Añade más rutas privadas aquí
  ];

  if (privatePaths.some(path => window.location.pathname.includes(path))) {
    if (!user) {
      console.log("Acceso denegado: no logueado");
      window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/firebase/auth/login.php";
    }
  }
});