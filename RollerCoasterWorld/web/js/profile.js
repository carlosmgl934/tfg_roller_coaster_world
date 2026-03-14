// profile.js — Lógica del perfil, amigos y top personal
// TODO: editar bio/avatar, gestionar solicitudes de amistad, top personal

$(document).ready(function () {
  // ── Auto-relleno de país usando Nominatim (OpenStreetMap) ──────────────────
  const cityInput = document.getElementById("config-user-city");
  const countryInput = document.getElementById("config-user-country");
  const loadingEl = document.getElementById("city-loading");

  if (cityInput && countryInput) {
    async function fetchCountry() {
      const city = cityInput.value.trim();
      if (!city) return;

      if (loadingEl) loadingEl.classList.remove("d-none");

      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?city=${encodeURIComponent(city)}&format=json&limit=1&addressdetails=1`,
          { headers: { "Accept-Language": "es" } },
        );
        const data = await res.json();

        if (data.length > 0 && data[0].address?.country) {
          countryInput.value = data[0].address.country;
        }
      } catch (e) {
        console.warn("No se pudo obtener el país automáticamente:", e);
      } finally {
        if (loadingEl) loadingEl.classList.add("d-none");
      }
    }

    cityInput.addEventListener("blur", fetchCountry);
    cityInput.addEventListener("change", fetchCountry);
  }

  // ── Cargar datos del usuario ───────────────────────────────────────────────

  const btnGuardar = document.getElementById("guardar-config-btn");

  // Capitalizar primera letra del username al perder el foco
  const usernameInput = document.getElementById("config-user-username");
  if (usernameInput) {
    usernameInput.addEventListener("blur", function () {
      if (this.value.length > 0) {
        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
      }
    });
  }

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
          msgEl.classList.remove("d-none");
          msgEl.innerHTML =
            '<i class="fa-solid fa-circle-check me-2"></i>Guardado correctamente';
          msgEl.className = "text-success mb-0 me-4 fw-bold";
          setTimeout(() => msgEl.classList.add("d-none"), 3000);
        } else {
          alert("Configuración guardada correctamente.");
        }
        cargarDatos();
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

        // Actualizar avatar si hay imagen guardada
        if (data.user.profile_image) {
          const avatarDiv = document.querySelector(".avatar-circle");
          if (avatarDiv) {
            avatarDiv.innerHTML = `<img src="${data.user.profile_image}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
          }
        }

        // Helper: capitaliza primera letra de un string, o devuelve fallback
        const cap = (s, fallback = "—") =>
          s ? s.charAt(0).toUpperCase() + s.slice(1) : fallback;

        // Actualizar tarjeta de perfil visual
        document.getElementById("full-name").textContent = cap(
          data.user.full_name,
        );
        document.getElementById("username").textContent = cap(
          data.user.username,
        );
        document.getElementById("email").textContent = data.user.email || "—";
        document.getElementById("profile-display-name").textContent = cap(
          data.user.username,
          "Usuario",
        );

        let birthDateFormatted = "—";
        if (data.user.birthdate) {
          const d = new Date(data.user.birthdate);
          birthDateFormatted = d.toLocaleDateString("es-ES");
        }
        document.getElementById("birth-date").textContent = birthDateFormatted;

        document.getElementById("gender").textContent = data.user.gender || "—";

        // Ubicación: ciudad, país, o ambos, de forma null-safe
        let locationText = "—";
        if (data.user.city && data.user.country) {
          locationText = cap(data.user.city) + ", " + cap(data.user.country);
        } else if (data.user.city) {
          locationText = cap(data.user.city);
        } else if (data.user.country) {
          locationText = cap(data.user.country);
        }
        document.getElementById("location").textContent = locationText;

        document.getElementById("favorite-coaster").textContent =
          data.user.favorite_coaster || "—";
        document.getElementById("home-park").textContent =
          data.user.home_park || "—";
      }
    } catch (e) {
      console.warn("Error cargando datos:", e);
    }
  }

  document
    .getElementById("change-avatar-btn")
    .addEventListener("click", function () {
      $("#avatar-input").click();
    });

  document
    .getElementById("avatar-input")
    .addEventListener("change", async function (e) {
      const file = e.target.files[0];
      if (!file) return;

      // Comprimir imagen antes de subir (max 400×400, calidad 85%)
      const compressedBlob = await comprimirImagen(file, 400, 400, 0.85);

      // Preview inmediata con la imagen comprimida
      const previewUrl = URL.createObjectURL(compressedBlob);
      const avatarDiv = document.querySelector(".avatar-circle");
      if (avatarDiv) {
        avatarDiv.innerHTML = `<img src="${previewUrl}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
      }

      try {
        const photoUrl = await subirFoto(compressedBlob, file.name);
        const res = await fetch(
          BASE_URL + "/api/php/profile_config.php?action=update_avatar",
          {
            method: "POST",
            body: JSON.stringify({ photo_url: photoUrl }),
            headers: { "Content-Type": "application/json" },
          },
        );
        const data = await res.json();
        if (data.success) {
          console.log("Foto de perfil actualizada correctamente");
        } else {
          console.error("Error al actualizar la foto de perfil:", data.error);
          showAvatarError("No se pudo guardar la foto. Inténtalo de nuevo.");
        }
      } catch (err) {
        console.error("Error subiendo avatar:", err);
        showAvatarError(err.message);
      }
    });

  // Modal de error para problemas con el avatar
  function showAvatarError(msg) {
    const existing = document.getElementById("avatar-error-modal");
    if (existing) existing.remove();
    const html = `
    <div class="modal fade" id="avatar-error-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-body text-center p-4">
            <i class="fa-solid fa-triangle-exclamation text-warning fs-1 mb-3 d-block"></i>
            <h5 class="fw-bold mb-2">Error al subir la foto</h5>
            <p class="text-muted mb-3">${msg}</p>
            <button class="btn btn-success px-4" data-bs-dismiss="modal">Entendido</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML("beforeend", html);
    new bootstrap.Modal(document.getElementById("avatar-error-modal")).show();
  }

  // Comprime una imagen a un tamaño máximo antes de subirla
  function comprimirImagen(file, maxW, maxH, quality) {
    return new Promise((resolve) => {
      const img = new Image();
      const url = URL.createObjectURL(file);
      img.onload = function () {
        let w = img.width,
          h = img.height;
        if (w > maxW || h > maxH) {
          const ratio = Math.min(maxW / w, maxH / h);
          w = Math.round(w * ratio);
          h = Math.round(h * ratio);
        }
        const canvas = document.createElement("canvas");
        canvas.width = w;
        canvas.height = h;
        canvas.getContext("2d").drawImage(img, 0, 0, w, h);
        URL.revokeObjectURL(url);
        canvas.toBlob(resolve, "image/jpeg", quality);
      };
      img.src = url;
    });
  }

  async function subirFoto(blob, originalName) {
    const formData = new FormData();
    const filename =
      (originalName || "avatar").replace(/\.[^.]+$/, "") + ".jpg";
    formData.append("file", blob, filename);
    formData.append("bucket", "avatars");

    const res = await fetch(`${BASE_URL}/api/php/upload.php`, {
      method: "POST",
      body: formData,
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || "Error al subir la foto");
    return data.url;
  }

  cargarParques();
  cargarDatos();

  function showSection(sectionId) {
    // Ocultar todas las secciones principales
    $(
      "#section-profile-content, #section-config-content, #section-tops-content, #section-reviews-content, #section-friends-content, #section-map-content",
    ).addClass("d-none");

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
      $("#section-tops-content").removeClass("d-none");
      $("#menu-tops").addClass("active");
    } else if (sectionId === "#profile-reviews") {
      $("#section-reviews-content").removeClass("d-none");
      $("#menu-reviews").addClass("active");
    } else if (sectionId === "#profile-friends") {
      $("#section-friends-content").removeClass("d-none");
      $("#menu-friends").addClass("active");
    } else if (sectionId === "#profile-map") {
      $("#section-map-content").removeClass("d-none");
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
