// forums.js — Lógica del foro con Firebase Realtime Database
// TODO: escuchar mensajes en tiempo real, enviar mensajes, crear hilos

$(document).ready(function () {
  // Inicializar Choices.js
  const el = document.getElementById("collaborators");
  if (el) {
    new Choices(el, {
      removeItemButton: true,
      searchEnabled: true,
      searchPlaceholderValue: "Buscar colaborador...",
      placeholderValue: "Selecciona colaboradores entre tus amigos",
      noResultsText: "Sin resultados",
      noChoicesText: "No hay más opciones",
      itemSelectText: "",
      shouldSort: true,
    });
  }

  // Controlar la visibilidad de los colaboradores según la privacidad
  const radios = document.querySelectorAll('input[name="privacy"]');
  const collabsSection = document.getElementById("collaborators-section");
  const hintText = document.getElementById("privacy-hint-text");

  radios.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (this.value === "private") {
        collabsSection.style.display = "block";
        hintText.textContent =
          "Solo los colaboradores que designes pueden escribir, pero cualquiera puede leer el foro";
      } else {
        collabsSection.style.display = "none";
        hintText.textContent =
          "Cualquier usuario puede ver y escribir en el foro";
      }
    });
  });

  document
    .getElementById("forum-submit-btn")
    .addEventListener("click", function () {
      // Comprobar si el usuario está logueado
      const isLogged =
        document
          .getElementById("forum-main-container")
          .getAttribute("data-logged") === "true";
      if (!isLogged) {
        // Mostrar el modal de Bootstrap
        const loginModal = new bootstrap.Modal(
          document.getElementById("loginModal"),
        );
        loginModal.show();
        return;
      }

      const form = document.getElementById("forum-form");
      const formData = new FormData(form);
      const msgDiv = document.getElementById("error-success-message");
      const msgText = document.getElementById("error-success-message-text");
      const btn = this;

      msgDiv.style.display = "none";

      const titleInput = document.getElementById("title");
      const subjectInput = document.getElementById("form_subject");
      const privacyRadios = document.querySelectorAll('input[name="privacy"]');

      // Limpiar bordes rojos previos
      titleInput.classList.remove("is-invalid");
      subjectInput.classList.remove("is-invalid");

      let hasError = false;

      const title = titleInput.value.trim();
      const subject = subjectInput.value.trim();

      if (!title) {
        titleInput.classList.add("is-invalid");
        hasError = true;
      }

      if (!subject) {
        subjectInput.classList.add("is-invalid");
        hasError = true;
      }

      if (hasError) {
        msgDiv.style.display = "block";
        msgText.textContent =
          "Por favor, completa todos los campos marcados en rojo";
        msgText.style.color = "red";
        return;
      }

      const privacy = formData.get("privacy");
      if (!privacy || (privacy !== "private" && privacy !== "public")) {
        msgDiv.style.display = "block";
        msgText.textContent = "Por favor, selecciona una privacidad";
        msgText.style.color = "red";
        return;
      }

      if (subject.length > 255) {
        subjectInput.classList.add("is-invalid");
        msgDiv.style.display = "block";
        msgText.textContent =
          "La descripción no puede exceder los 255 caracteres";
        msgText.style.color = "red";
        return;
      }

      if (title.length > 50) {
        titleInput.classList.add("is-invalid");
        msgDiv.style.display = "block";
        msgText.textContent = "El título no puede exceder los 50 caracteres";
        msgText.style.color = "red";
        return;
      }

      // Remover clase is-invalid al escribir
      titleInput.addEventListener(
        "input",
        function () {
          this.classList.remove("is-invalid");
        },
        { once: true },
      );
      subjectInput.addEventListener(
        "input",
        function () {
          this.classList.remove("is-invalid");
        },
        { once: true },
      );

      btn.disabled = true;
      btn.textContent = "Creando foro...";
      fetch(window.BASE_URL + "/api/php/forums.php?action=create_forum", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            msgDiv.style.display = "block";
            msgText.textContent = "Foro creado exitosamente";
            msgText.style.color = "green";
            form.reset();
          } else {
            msgDiv.style.display = "block";
            msgText.textContent = data.error;
            msgText.style.color = "red";
          }
        })
        .catch((e) => {
          console.warn("Error creating forum:", e);
          msgDiv.style.display = "block";
          msgText.textContent = "Error al crear el foro";
          msgText.style.color = "red";
        })
        .finally(() => {
          btn.disabled = false;
          btn.textContent = "Crear foro";
        });
    });

  // Cargar colaboradores solo la primera vez que se seleccione privacidad "privada"
  let friendsLoaded = false;

  $("input[name='privacy']").on("change", function () {
    const isLogged =
      document
        .getElementById("forum-main-container")
        .getAttribute("data-logged") === "true";

    if ($(this).val() === "private") {
      $("#collaborators-section").show();

      if (isLogged && !friendsLoaded) {
        // friendsLoaded = true; // Removido para que intente cargar siempre si falló
        fetch(window.BASE_URL + "/api/php/forums.php?action=get_friends")
          .then((res) => {
            if (!res.ok) throw new Error("Network response was not ok");
            return res.json();
          })
          .then((data) => {
            if (data.success && Array.isArray(data.friends)) {
              friendsLoaded = true;
              const collaborators = document.getElementById("collaborators");
              data.friends.forEach((friend) => {
                const option = document.createElement("option");
                option.value = friend.id;
                option.textContent = friend.username;
                collaborators.appendChild(option);
              });
            } else {
              friendsLoaded = true;
              console.warn(
                "No se pudieron cargar amigos o no hay amigos:",
                data.error || "Respuesta sin datos",
              );
            }
          })
          .catch((e) => {
            console.error("Fetch error:", e);
            // Si el error es literal "no tengo amigos" o un fallo de red, silenciamos el error rojo para que no moleste.
            // Marcamos como loaded para que no se quede bloqueado en bucle.
            friendsLoaded = true;
          });
      } else if (isLogged && friendsLoaded) {
        // Si ya están cargados exitosamente y cambiamos de "público" a "privado", no limpiamos mensajes aquí para no ocultar nada de validación de envío por accidente.
      } else if (!isLogged) {
        // Si no está logueado y es privado, mostramos el error (porque igual va a dar error al enviar)
        const msgDiv = document.getElementById("error-success-message");
        const msgText = document.getElementById("error-success-message-text");
        if (msgDiv && msgText) {
          msgDiv.style.display = "block";
          msgText.textContent = "Inicia sesión para seleccionar amigos";
          msgText.style.color = "red";
        }
      }
    } else {
      // Al cambiar a Público, limpiamos el mensaje SOLO si decía algo de los amigos o inicio de sesión
      const msgDiv = document.getElementById("error-success-message");
      const msgText = document.getElementById("error-success-message-text");
      if (
        msgDiv &&
        msgText &&
        (msgText.textContent === "Error al obtener los amigos" ||
          msgText.textContent === "Inicia sesión para seleccionar amigos")
      ) {
        msgDiv.style.display = "none";
        msgText.textContent = "";
      }

      $("#collaborators-section").hide();
    }
  });
});
