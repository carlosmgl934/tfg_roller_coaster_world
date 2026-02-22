// Firebase config (tus claves reales)
const firebaseConfig = {
  apiKey: "AIzaSyAUFVSu8EvuFgeNgnQj4BH4MTuX0r_9qXY",
  authDomain: "tfg-roller-coaster-world-auth.firebaseapp.com",
  projectId: "tfg-roller-coaster-world-auth",
  storageBucket: "tfg-roller-coaster-world-auth.appspot.com",
  messagingSenderId: "882619658485",
  appId: "1:882619658485:web:568601d00570ca35dd55bc",
  measurementId: "G-6Y2GK2L79D"
};

// Inicializar Firebase
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

  auth.createUserWithEmailAndPassword(email, password)
    .then((userCredential) => {
      const user = userCredential.user;
      console.log("Registro exitoso! UID:", user.uid);

      // Guardar en Supabase - ruta ABSOLUTA
      fetch('/tfg/tfg_roller_coaster_world/RollerCoasterWorld/api/php/auth.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          firebase_uid: user.uid,
          email: user.email,
          username: user.displayName || user.email.split('@')[0]
        })
      })
      .then(response => {
        console.log("Respuesta del servidor:", response.status, response.statusText);
        return response.json();
      })
      .then(data => {
        console.log("Respuesta JSON:", data);
        if (data.success) {
          console.log("Usuario guardado en Supabase:", data);
        } else {
          console.warn("No guardado:", data.message);
        }
      })
      .catch(err => {
        console.error("Error en fetch a auth.php:", err);
      });

      alert("¡Registro completado!");
      window.location.href = "/tfg/tfg_roller_coaster_world/RollerCoasterWorld/web/views/home.php";
    })
    .catch((error) => {
      console.error("Error en registro Firebase:", error.code, error.message);
      alert("Error Firebase: " + error.message);
    });
}

// Login con Google (con sync a Supabase)
function signInWithGoogle() {
  console.log("Intentando login con Google");
  const provider = new firebase.auth.GoogleAuthProvider();
  auth.signInWithPopup(provider)
    .then((result) => {
      const user = result.user;
      console.log("Login Google OK:", user.uid);

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

// (las demás funciones: signInWithFacebook, signOut, onAuthStateChanged se mantienen iguales)

auth.onAuthStateChanged((user) => {
  console.log(user ? "Logueado: " + user.uid : "No logueado");
});