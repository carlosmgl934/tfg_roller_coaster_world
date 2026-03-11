// profile.js — Lógica del perfil, amigos y top personal
// TODO: editar bio/avatar, gestionar solicitudes de amistad, top personal

$(document).ready(function () {
  // ── Auto-relleno de país usando Nominatim (OpenStreetMap) ──────────────────
  const cityInput = document.getElementById("config-user-city");
  const countryInput = document.getElementById("config-user-country");
  const loadingEl = document.getElementById("city-loading");

  if (cityInput && countryInput) {
    cityInput.addEventListener("blur", async function () {
      const city = this.value.trim();
      if (!city) return;

      if (loadingEl) loadingEl.classList.remove("d-none");

      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(city)}&format=json&limit=1&addressdetails=1`,
          { headers: { "Accept-Language": "es" } },
        );
        const data = await res.json();

        if (data.length > 0 && data[0].address?.country) {
          // Solo rellenar si el usuario no ha escrito ya un país manualmente
          if (!countryInput.value.trim()) {
            countryInput.value = data[0].address.country;
          }
        }
      } catch (e) {
        console.warn("No se pudo obtener el país automáticamente:", e);
      } finally {
        if (loadingEl) loadingEl.classList.add("d-none");
      }
    });
  }

  // ── Cargar datos del usuario ───────────────────────────────────────────────

  const btnGuardar = document.getElementById("guardar-config-btn");

  btnGuardar.addEventListener("click", async function () {
    const btn = this;
    const originalText = btn.innerHTML;

    let fullName = document.getElementById("config-user-name").value.trim();
    let username = document.getElementById("config-user-username").value.trim();
    let email = document.getElementById("config-user-email").value.trim();
    let birthday = document.getElementById("config-user-birthdate").value;
    let gender = document.getElementById("config-user-gender").value;
    let city = document.getElementById("config-user-city").value.trim();
    let country = document.getElementById("config-user-country").value.trim();
    let topCoaster = document.getElementById("top-coaster-user").value.trim();
    let homePark = document.getElementById("home-park-user").value.trim();

    if (!username || !email) {
      alert("El usuario y correo electrónico son obligatorios.");
      return;
    }

    btn.disabled = true;
    btn.innerHTML =
      'Guardando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

    const formData = new FormData();
    formData.append("fullName", fullName);
    formData.append("username", username);
    formData.append("email", email);
    formData.append("birthday", birthday);
    formData.append("gender", gender);
    formData.append("city", city);
    formData.append("country", country);
    formData.append("topCoaster", topCoaster);
    formData.append("homePark", homePark);

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/profile_config.php?action=save_profile`,
        {
          method: "POST",
          body: formData,
        },
      );
      const data = await res.json();
      const msgEl = document.getElementById("msg-guardar-config");

      if (data.success) {
        if (msgEl) {
          msgEl.innerHTML =
            '<i class="fa-solid fa-circle-check me-2"></i>Guardado correctamente';
          msgEl.className = "text-success mb-0 me-4 fw-bold";
          setTimeout(() => msgEl.classList.add("d-none"), 3000);
        } else {
          alert("Configuración guardada correctamente.");
        }
      } else {
        if (msgEl) {
          msgEl.innerHTML =
            '<i class="fa-solid fa-circle-xmark me-2"></i>' +
            (data.error || "Error al guardar");
          msgEl.className = "text-danger mb-0 me-4 fw-bold";
          setTimeout(() => msgEl.classList.add("d-none"), 4000);
        } else {
          alert("Error: " + (data.error || "Ocurrió un problema"));
        }
      }
    } catch (e) {
      console.error("Error al guardar perfil:", e);
      const msgEl = document.getElementById("msg-guardar-config");
      if (msgEl) {
        msgEl.innerHTML =
          '<i class="fa-solid fa-circle-xmark me-2"></i>Error de conexión';
        msgEl.className = "text-danger mb-0 me-4 fw-bold";
        setTimeout(() => msgEl.classList.add("d-none"), 4000);
      } else {
        alert("Error de conexión al guardar los datos.");
      }
    } finally {
      btn.disabled = false;
      btn.innerHTML = originalText;
    }
  });

  // ── Autocomplete de Home Park ──────────────────────────────────────────────
  function cargarParques() {
    const input = document.getElementById("home-park-user");
    const dropdown = document.getElementById("home-park-dropdown");
    const loading = document.getElementById("home-park-loading");

    let selectedParkId = null;
    let debounceTimer = null;

    $(input).on("keyup", function () {
      const search = this.value.trim();

      clearTimeout(debounceTimer); // debounce: esperar 300ms tras dejar de escribir

      if (search.length < 3) {
        $(dropdown).addClass("d-none").empty();
        return;
      }

      debounceTimer = setTimeout(async () => {
        if (loading) $(loading).removeClass("d-none");

        try {
          const res = await fetch(
            `${BASE_URL}/api/php/profile_config.php?action=search&search=${encodeURIComponent(search)}`,
          );
          const data = await res.json();

          $(dropdown).empty();

          if (data.length > 0) {
            data.forEach((parque) => {
              const li = $(
                `<li class="list-group-item list-group-item-action" style="cursor:pointer;">${parque.park_name}</li>`,
              );
              li.on("click", function () {
                $(input).val(parque.park_name);
                selectedParkId = parque.park_id;
                $(dropdown).addClass("d-none").empty();
              });
              $(dropdown).append(li);
            });
            $(dropdown).removeClass("d-none");
          } else {
            $(dropdown).append(
              '<li class="list-group-item text-muted small">No se encontraron parques</li>',
            );
            $(dropdown).removeClass("d-none");
          }
        } catch (e) {
          console.warn("Error buscando parques:", e);
        } finally {
          if (loading) $(loading).addClass("d-none");
        }
      }, 300);
    });

    // Cerrar dropdown al hacer click fuera
    $(document).on("click", function (e) {
      if (!$(e.target).closest("#home-park-user, #home-park-dropdown").length) {
        $(dropdown).addClass("d-none");
      }
    });
  }

  async function cargarDatos() {
    try {
      const res = await fetch(
        `${BASE_URL}/api/php/profile_config.php?action=get_profile`,
      );
      const data = await res.json();

      if (data.success) {
        document.getElementById("config-user-name").value =
          data.user.full_name || "";
        document.getElementById("config-user-username").value =
          data.user.username || "";
        document.getElementById("config-user-email").value =
          data.user.email || "";
        document.getElementById("config-user-birthdate").value =
          data.user.birthdate || "";
        document.getElementById("config-user-gender").value =
          data.user.gender || "";
        document.getElementById("config-user-city").value =
          data.user.city || "";
        document.getElementById("config-user-country").value =
          data.user.country || "";
        document.getElementById("top-coaster-user").value =
          data.user.favorite_coaster || "";
        document.getElementById("home-park-user").value =
          data.user.home_park || "";

        // Actualizar tarjeta de perfil visual
        document.getElementById("full-name").textContent =
          data.user.full_name.charAt(0).toUpperCase() +
            data.user.full_name.slice(1) || "—";
        document.getElementById("username").textContent =
          data.user.username.charAt(0).toUpperCase() +
            data.user.username.slice(1) || "—";
        document.getElementById("email").textContent = data.user.email || "—";
        document.getElementById("profile-display-name").textContent =
          data.user.username.charAt(0).toUpperCase() +
            data.user.username.slice(1) || "Usuario";

        let birthDateFormatted = "—";
        if (data.user.birthdate) {
          const d = new Date(data.user.birthdate);
          birthDateFormatted = d.toLocaleDateString("es-ES");
        }
        document.getElementById("birth-date").textContent = birthDateFormatted;

        document.getElementById("gender").textContent = data.user.gender || "—";
        document.getElementById("location").textContent =
          data.user.city && data.user.country
            ? `${data.user.city}, ${data.user.country}`
            : data.user.city || data.user.country || "—";

        document.getElementById("favorite-coaster").textContent =
          data.user.favorite_coaster || "—";
        document.getElementById("home-park").textContent =
          data.user.home_park || "—";
      }
    } catch (e) {
      console.warn("Error cargando datos:", e);
    }
  }

  cargarParques();
  cargarDatos();

  function showSection(sectionId) {
    // Ocultar todas las secciones principales
    $("#section-profile-content, #section-config-content").addClass("d-none");

    // Quitar el active de todos los enlaces del menú
    $("#sidebar-menu .list-group-item").removeClass("active");

    // Mostrar solo el que queremos
    if (sectionId === "#profile-menu") {
      $("#section-profile-content").removeClass("d-none");
      $("#menu-profile").addClass("active");
    } else if (sectionId === "#profile-config") {
      $("#section-config-content").removeClass("d-none");
      $("#menu-config").addClass("active");
    } else if (sectionId === "#profile-tops") {
      $("#menu-tops").addClass("active");
    } else if (sectionId === "#profile-reviews") {
      $("#menu-reviews").addClass("active");
    } else if (sectionId === "#profile-friends") {
      $("#menu-friends").addClass("active");
    } else if (sectionId === "#profile-map") {
      $("#menu-map").addClass("active");
    }
  }

  $("#menu-profile").on("click", function (e) {
    e.preventDefault();
    showSection("#profile-menu");
  });

  $("#menu-config").on("click", function (e) {
    e.preventDefault();
    showSection("#profile-config");
  });

  $("#menu-tops").on("click", function (e) {
    e.preventDefault();
    showSection("#profile-tops");
  });

  $("#menu-reviews").on("click", function (e) {
    e.preventDefault();
    showSection("#profile-reviews");
  });

  $("#menu-friends").on("click", function (e) {
    e.preventDefault();
    showSection("#profile-friends");
  });

  $("#menu-map").on("click", function (e) {
    e.preventDefault();
    showSection("#profile-map");
  });
});
