// profile.js — Lógica del perfil, amigos y top personal
// TODO: editar bio/avatar, gestionar solicitudes de amistad, top personal

$(document).ready(function () {
  // ── Inicializar Flatpickr para Fecha de Nacimiento ───────────────────────
  const birthPicker = flatpickr("#config-user-birthdate", {
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "d/m/Y",
    locale: "es",
    maxDate: "today",
    disableMobile: "true",
    onReady: function(selectedDates, dateStr, instance) {
      if (instance.altInput) {
        instance.altInput.id = "config-user-birthdate-alt";
        const label = document.querySelector('label[for="config-user-birthdate"]');
        if (label) label.setAttribute("for", "config-user-birthdate-alt");
      }
    }
  });

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

  // Capitalizar primera letra del username y del nombre completo al perder el foco
  const usernameInput = document.getElementById("config-user-username");
  if (usernameInput) {
    usernameInput.addEventListener("blur", function () {
      if (this.value.length > 0) {
        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
      }
    });
  }
  const nameInput = document.getElementById("config-user-name");
  if (nameInput) {
    nameInput.addEventListener("input", function () {
      const pos = this.selectionStart;
      if (this.value.length > 0) {
        this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
        this.setSelectionRange(pos, pos);
      }
    });
  }

  btnGuardar.addEventListener("click", async function () {
    const btn = this;
    const originalText = btn.innerHTML;

    let fullName = document.getElementById("config-user-name").value.trim();
    let username = document.getElementById("config-user-username").value.trim();
    let birthday = document.getElementById("config-user-birthdate").value;
    let gender = document.getElementById("config-user-gender").value;
    let city = document.getElementById("config-user-city").value.trim();
    let country = document.getElementById("config-user-country").value.trim();
    let topCoaster = document.getElementById("top-coaster-user").value.trim();
    let homePark = document.getElementById("home-park-user").value.trim();

    // Basta con que al menos un campo tenga valor para guardar
    const msgEl = document.getElementById("msg-guardar-config");
    const hayAlgoCambiado =
      fullName || username || birthday || gender || city || country || homePark;
    if (!hayAlgoCambiado) {
      if (msgEl) {
        msgEl.innerHTML =
          '<i class="fa-solid fa-circle-xmark me-2"></i>Rellena al menos un campo antes de guardar';
        msgEl.className = "text-danger mb-0 me-4 fw-bold";
        msgEl.classList.remove("d-none");
        setTimeout(() => msgEl.classList.add("d-none"), 4000);
      }
      return;
    }

    btn.disabled = true;
    btn.innerHTML =
      'Guardando... <i class="fa-solid fa-spinner fa-spin ms-2"></i>';

    const formData = new FormData();
    formData.append("fullName", fullName);
    formData.append("username", username);
    // El email no se envía: está deshabilitado y el backend lo obtiene de la sesión
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
      const msgEl2 = document.getElementById("msg-guardar-config");

      if (data.success) {
        if (msgEl2) {
          msgEl2.classList.remove("d-none");
          msgEl2.innerHTML =
            '<i class="fa-solid fa-circle-check me-2"></i>Guardado correctamente';
          msgEl2.className = "text-success mb-0 me-4 fw-bold";
          setTimeout(() => msgEl2.classList.add("d-none"), 3000);
        }

        // ── Actualizar header en tiempo real ──────────────────────────────────
        const savedUsername = username || fullName;
        if (savedUsername) {
          // Actualizar nombre mostrado en el navbar
          const headerName = document.getElementById("header-username-display");
          if (headerName) {
            headerName.textContent =
              savedUsername.charAt(0).toUpperCase() + savedUsername.slice(1);
          }
          // Actualizar iniciales del avatar (si no hay foto de perfil)
          const headerAvatar = document.getElementById("header-avatar");
          if (headerAvatar && !headerAvatar.querySelector("img")) {
            const parts = savedUsername.trim().split(/[\s_\-]+/);
            let initials = parts[0].charAt(0).toUpperCase();
            if (parts.length > 1) initials += parts[1].charAt(0).toUpperCase();
            const span = headerAvatar.querySelector("span");
            if (span) span.textContent = initials;
          }
          // Actualizar también el nombre en el dropdown header
          const headerDropName = document.getElementById(
            "header-dropdown-name",
          );
          if (headerDropName) {
            headerDropName.textContent =
              savedUsername.charAt(0).toUpperCase() + savedUsername.slice(1);
          }
        }
        // ─────────────────────────────────────────────────────────────────────

        cargarDatos();
      } else {
        if (msgEl2) {
          msgEl2.innerHTML =
            '<i class="fa-solid fa-circle-xmark me-2"></i>' +
            (data.error || "Error al guardar");
          msgEl2.className = "text-danger mb-0 me-4 fw-bold";
          msgEl2.classList.remove("d-none");
          setTimeout(() => msgEl2.classList.add("d-none"), 4000);
        }
      }
    } catch (e) {
      console.error("Error al guardar perfil:", e);
      const msgEl3 = document.getElementById("msg-guardar-config");
      if (msgEl3) {
        msgEl3.innerHTML =
          '<i class="fa-solid fa-circle-xmark me-2"></i>Error de conexión';
        msgEl3.className = "text-danger mb-0 me-4 fw-bold";
        msgEl3.classList.remove("d-none");
        setTimeout(() => msgEl3.classList.add("d-none"), 4000);
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

        if (data.user.birthdate) {
          birthPicker.setDate(data.user.birthdate);
        } else {
          birthPicker.clear();
        }
        document.getElementById("config-user-gender").value =
          data.user.gender || "";
        document.getElementById("config-user-city").value =
          data.user.city || "";
        document.getElementById("config-user-country").value =
          data.user.country || "";
        document.getElementById("top-coaster-user").value =
          data.stats && data.stats.top_coaster !== "—"
            ? data.stats.top_coaster
            : data.user.favorite_coaster || "";
        document.getElementById("home-park-user").value =
          data.user.home_park || "";

        // Actualizar avatar si hay imagen guardada
        if (data.user.profile_image) {
          // En la tarjeta de perfil
          const avatarDiv = document.querySelector(".avatar-circle");
          if (avatarDiv) {
            avatarDiv.innerHTML = `<img src="${data.user.profile_image}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
          }
          // En el header del navbar
          const headerAvatar = document.getElementById("header-avatar");
          if (headerAvatar) {
            headerAvatar.innerHTML = `<img src="${data.user.profile_image}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
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
          data.stats && data.stats.top_coaster !== "—"
            ? data.stats.top_coaster
            : data.user.favorite_coaster || "—";

        const elFavPark = document.getElementById("favorite-park");
        if (elFavPark)
          elFavPark.textContent =
            data.stats && data.stats.top_park !== "—"
              ? data.stats.top_park
              : "—";

        document.getElementById("home-park").textContent =
          data.user.home_park || "—";

        // Rellenar estadísticas automáticas
        if (data.stats) {
          const elCstrCount = document.getElementById("coasters-count");
          if (elCstrCount) elCstrCount.textContent = data.stats.coasters_count;

          const elParkCount = document.getElementById("parks-count");
          if (elParkCount) elParkCount.textContent = data.stats.parks_count;

          const elCountryCount = document.getElementById("countries-count");
          if (elCountryCount)
            elCountryCount.textContent = data.stats.countries_count;

          const elRevCount = document.getElementById("reviews-count");
          if (elRevCount) elRevCount.textContent = data.stats.reviews_count;

          const elRanking = document.getElementById("user-ranking");
          if (elRanking) elRanking.textContent = data.stats.ranking;

          // Technical Statistics
          const elMainCountry = document.getElementById("main-country");
          if (elMainCountry)
            elMainCountry.textContent = data.stats.main_country;

          const elMainManuf = document.getElementById("main-manufacturer");
          if (elMainManuf)
            elMainManuf.textContent = data.stats.main_manufacturer;

          const elTotalManuf = document.getElementById("total-manufacturers");
          if (elTotalManuf)
            elTotalManuf.textContent = data.stats.total_manufacturers;

          const elTotalHeight = document.getElementById("total-height");
          if (elTotalHeight)
            elTotalHeight.textContent = data.stats.total_height;

          const elTotalInv = document.getElementById("total-investments");
          if (elTotalInv) elTotalInv.textContent = data.stats.total_inversions;

          const elFastest = document.getElementById("fastest-coaster");
          if (elFastest) elFastest.textContent = data.stats.fastest_coaster;

          const elLongest = document.getElementById("longest-coaster");
          if (elLongest) elLongest.textContent = data.stats.longest_coaster;
        }
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

        // Actualizar cabecera del navbar en tiempo real con la foto
        const headerAvatar = document.getElementById("header-avatar");
        if (headerAvatar) {
          headerAvatar.innerHTML = `<img src="${previewUrl}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        }

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

  // ══════════════════════════════════════════════════════════════════
  //  MIS TOPS  —  Preview · Vista Completa · Edición
  // ══════════════════════════════════════════════════════════════════

  // Datos cacheados para no repetir fetch cada vez que se cambia de modo
  let topsData = { coasters: [], parks: [] };

  // Tipo de vista actual por pestaña ('list' | 'grid')
  let coastersViewType = "list";
  let parksViewType = "list";

  // ── Plantillas HTML ──────────────────────────────────────────────

  function emptyState(msg) {
    return `<div class="text-center text-muted py-5">
      <i class="fa-solid fa-ghost fs-1 mb-3 opacity-50 d-block"></i><p>${msg}</p>
    </div>`;
  }

  // Preview: top-10 compacto
  function renderCoastersPreview(data) {
    const el = $("#top-coasters-preview-list").empty();
    if (!data.length) {
      el.html(emptyState("Todavía no tienes montañas rusas en tu top."));
      return;
    }
    data.slice(0, 10).forEach((item, i) => {
      el.append(`
        <div class="tops-preview-item">
          <span class="tops-preview-rank">#${i + 1}</span>
          <div class="flex-grow-1">
            <div class="fw-bold text-white">${item.coaster_name}</div>
            <small class="text-secondary">${item.park_name} · ${item.country_name || ""}</small>
          </div>
        </div>`);
    });
    if (data.length > 10) {
      el.append(
        `<div class="text-center text-muted small py-2">+${data.length - 10} más en el top completo</div>`,
      );
    }
  }

  function renderParksPreview(data) {
    const el = $("#top-parks-preview-list").empty();
    if (!data.length) {
      el.html(emptyState("Todavía no tienes parques en tu top."));
      return;
    }
    data.slice(0, 10).forEach((item, i) => {
      el.append(`
        <div class="tops-preview-item">
          <span class="tops-preview-rank">#${i + 1}</span>
          <div class="flex-grow-1">
            <div class="fw-bold text-white">${item.park_name}</div>
            <small class="text-secondary">${item.country_name || ""}</small>
          </div>
        </div>`);
    });
    if (data.length > 10) {
      el.append(
        `<div class="text-center text-muted small py-2">+${data.length - 10} más en el top completo</div>`,
      );
    }
  }

  // Vista completa: tarjetas ricas con sort + filtros
  function renderCoastersFull() {
    const sort = $("#coasters-sort").val();
    const fPark = $("#coasters-filter-park").val();
    const fCountry = $("#coasters-filter-country").val();
    const fMfr = $("#coasters-filter-manufacter").val();
    const isGrid = coastersViewType === "grid";

    let data = [...topsData.coasters];

    // Filtrar
    if (fPark) data = data.filter((d) => d.park_name === fPark);
    if (fCountry) data = data.filter((d) => d.country_name === fCountry);
    if (fMfr) data = data.filter((d) => d.manufacter === fMfr);

    // Ordenar
    if (sort === "name")
      data.sort((a, b) => a.coaster_name.localeCompare(b.coaster_name));
    else if (sort === "height")
      data.sort(
        (a, b) => (parseFloat(b.height) || 0) - (parseFloat(a.height) || 0),
      );
    else if (sort === "speed")
      data.sort(
        (a, b) => (parseFloat(b.speed) || 0) - (parseFloat(a.speed) || 0),
      );
    else if (sort === "manufacter")
      data.sort((a, b) =>
        (a.manufacter || "").localeCompare(b.manufacter || ""),
      );
    // 'rank' = orden original (rank_position)

    const container = $("#top-coasters-full-container").empty();

    // Update counter pill
    $("#coasters-full-count").text(data.length);

    if (!data.length) {
      container.html(emptyState("Ningún elemento coincide con los filtros."));
      return;
    }

    const colClass = isGrid ? "col-6 col-md-4" : "col-12";

    data.forEach((item) => {
      const img = item.imagen_url
        ? `<img src="${item.imagen_url}" alt="${item.coaster_name}" loading="lazy">`
        : `<div style="height:150px;background:#0d1117;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

      if (isGrid) {
        container.append(`
          <div class="${colClass}">
            <div class="top-card position-relative">
              ${img}
              <span class="rank-badge">#${item.rank_position}</span>
              <div class="p-2">
                <div class="fw-bold text-white small text-truncate">${item.coaster_name}</div>
                <div class="text-secondary" style="font-size:.75rem;">${item.park_name}</div>
              </div>
            </div>
          </div>`);
      } else {
        container.append(`
          <div class="${colClass}">
            <div class="top-card d-flex align-items-stretch" style="height:120px;">
              <div style="width:120px;flex-shrink:0;position:relative;">
                ${img.replace("height:150px", "height:120px")}
                <span class="rank-badge">#${item.rank_position}</span>
              </div>
              <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between">
                <div>
                  <div class="fw-bold text-white">${item.coaster_name}</div>
                  <small class="text-secondary">${item.park_name} · ${item.country_name || ""}</small>
                </div>
                <div class="d-flex gap-3 flex-wrap">
                  ${item.height ? `<small class="text-info"><i class="fa-solid fa-ruler-vertical me-1"></i>${item.height} m</small>` : ""}
                  ${item.speed ? `<small class="text-warning"><i class="fa-solid fa-bolt me-1"></i>${item.speed} km/h</small>` : ""}
                  ${item.manufacter ? `<small class="text-secondary"><i class="fa-solid fa-industry me-1"></i>${item.manufacter}</small>` : ""}
                </div>
              </div>
            </div>
          </div>`);
      }
    });
  }

  function renderParksFull() {
    const sort = $("#parks-sort").val();
    const fCountry = $("#parks-filter-country").val();
    const isGrid = parksViewType === "grid";

    let data = [...topsData.parks];

    if (fCountry) data = data.filter((d) => d.country_name === fCountry);

    if (sort === "name")
      data.sort((a, b) => a.park_name.localeCompare(b.park_name));
    else if (sort === "coasters")
      data.sort(
        (a, b) =>
          (parseInt(b.operating_coasters) || 0) -
          (parseInt(a.operating_coasters) || 0),
      );
    else if (sort === "stars")
      data.sort(
        (a, b) => (parseFloat(b.stars) || 0) - (parseFloat(a.stars) || 0),
      );

    const container = $("#top-parks-full-container").empty();

    // Update counter pill
    $("#parks-full-count").text(data.length);

    if (!data.length) {
      container.html(emptyState("Ningún elemento coincide con los filtros."));
      return;
    }

    const colClass = isGrid ? "col-6 col-md-4" : "col-12";

    data.forEach((item) => {
      const img = item.imagen_url
        ? `<img src="${item.imagen_url}" alt="${item.park_name}" loading="lazy">`
        : `<div style="height:150px;background:#0d1117;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

      if (isGrid) {
        container.append(`
          <div class="${colClass}">
            <div class="top-card position-relative">
              ${img}
              <span class="rank-badge">#${item.rank_position}</span>
              <div class="p-2">
                <div class="fw-bold text-white small text-truncate">${item.park_name}</div>
                <div class="text-secondary" style="font-size:.75rem;">${item.country_name || ""}</div>
              </div>
            </div>
          </div>`);
      } else {
        container.append(`
          <div class="${colClass}">
            <div class="top-card d-flex align-items-stretch" style="height:120px;">
              <div style="width:120px;flex-shrink:0;position:relative;">
                ${img.replace("height:150px", "height:120px")}
                <span class="rank-badge">#${item.rank_position}</span>
              </div>
              <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between">
                <div>
                  <div class="fw-bold text-white">${item.park_name}</div>
                  <small class="text-secondary">${item.country_name || ""}</small>
                </div>
                <div class="d-flex gap-3 flex-wrap">
                  ${item.operating_coasters ? `<small class="text-info"><i class="fa-solid fa-ticket me-1"></i>${item.operating_coasters} coasters</small>` : ""}
                  ${item.stars ? `<small class="text-warning"><i class="fa-solid fa-star me-1"></i>${parseFloat(item.stars).toFixed(1)}</small>` : ""}
                </div>
              </div>
            </div>
          </div>`);
      }
    });
  }

  // Modo edición: lista arrastrable con número de ranking visible
  function renderCoastersEdit() {
    const el = $("#top-coasters-list-edit").empty();
    if (!topsData.coasters.length) {
      el.html(emptyState("Añade montañas rusas desde el buscador de arriba."));
      return;
    }
    topsData.coasters.forEach((item, i) => {
      el.append(`
        <div class="tops-edit-item" data-id="${item.coaster_id}">
          <i class="fa-solid fa-grip-lines drag-handle fs-5"></i>
          <span class="tops-rank-badge">#${i + 1}</span>
          <div class="flex-grow-1">
            <span class="fw-bold text-white">${item.coaster_name}</span>
            <small class="text-secondary ms-2">${item.park_name}</small>
          </div>
          <button class="btn btn-sm btn-outline-danger border-0 square-box tops-remove-item"><i class="fa-solid fa-trash"></i></button>
        </div>`);
    });
    if (window.Sortable) {
      new Sortable(document.getElementById("top-coasters-list-edit"), {
        handle: ".drag-handle",
        animation: 150,
        onEnd: function () {
          // Sync DOM order back into topsData so adding new items doesn't reset positions
          const newOrder = [];
          $("#top-coasters-list-edit .tops-edit-item").each(function (i) {
            $(this)
              .find(".tops-rank-badge")
              .text("#" + (i + 1));
            const id = $(this).data("id");
            const found = topsData.coasters.find((d) => d.coaster_id == id);
            if (found) newOrder.push({ ...found, rank_position: i + 1 });
          });
          topsData.coasters = newOrder;
          renderCoastersPreview(topsData.coasters);
        },
      });
    }
  }

  function renderParksEdit() {
    const el = $("#top-parks-list-edit").empty();
    if (!topsData.parks.length) {
      el.html(emptyState("Añade parques desde el buscador de arriba."));
      return;
    }
    topsData.parks.forEach((item, i) => {
      el.append(`
        <div class="tops-edit-item" data-id="${item.park_id}">
          <i class="fa-solid fa-grip-lines drag-handle fs-5"></i>
          <span class="tops-rank-badge">#${i + 1}</span>
          <div class="flex-grow-1">
            <span class="fw-bold text-white">${item.park_name}</span>
            <small class="text-secondary ms-2">${item.country_name || ""}</small>
          </div>
          <button class="btn btn-sm btn-outline-danger border-0 square-box tops-remove-item"><i class="fa-solid fa-trash"></i></button>
        </div>`);
    });
    if (window.Sortable) {
      new Sortable(document.getElementById("top-parks-list-edit"), {
        handle: ".drag-handle",
        animation: 150,
        onEnd: function () {
          // Sync DOM order back into topsData so adding new items doesn't reset positions
          const newOrder = [];
          $("#top-parks-list-edit .tops-edit-item").each(function (i) {
            $(this)
              .find(".tops-rank-badge")
              .text("#" + (i + 1));
            const id = $(this).data("id");
            const found = topsData.parks.find((d) => d.park_id == id);
            if (found) newOrder.push({ ...found, rank_position: i + 1 });
          });
          topsData.parks = newOrder;
          renderParksPreview(topsData.parks);
        },
      });
    }
  }

  // ── Helpers de filtros (popularlos dinámicamente) ────────────────

  function populateCoastersFilters(data) {
    const parks = [
      ...new Set(data.map((d) => d.park_name).filter(Boolean)),
    ].sort();
    const countries = [
      ...new Set(data.map((d) => d.country_name).filter(Boolean)),
    ].sort();
    const mfrs = [
      ...new Set(data.map((d) => d.manufacter).filter(Boolean)),
    ].sort();

    const $pk = $("#coasters-filter-park").find("option:first").end();
    const $co = $("#coasters-filter-country").find("option:first").end();
    const $mf = $("#coasters-filter-manufacter").find("option:first").end();

    $pk.find("option:not(:first)").remove();
    $co.find("option:not(:first)").remove();
    $mf.find("option:not(:first)").remove();

    parks.forEach((v) => $pk.append(`<option value="${v}">${v}</option>`));
    countries.forEach((v) => $co.append(`<option value="${v}">${v}</option>`));
    mfrs.forEach((v) => $mf.append(`<option value="${v}">${v}</option>`));
  }

  function populateParksFilters(data) {
    const countries = [
      ...new Set(data.map((d) => d.country_name).filter(Boolean)),
    ].sort();
    const $co = $("#parks-filter-country").find("option:first").end();
    $co.find("option:not(:first)").remove();
    countries.forEach((v) => $co.append(`<option value="${v}">${v}</option>`));
  }

  // ── Sidebar: Leyenda de estadísticas de coasters ─────────────────
  function renderTopsStats(coasters) {
    if (!coasters || !coasters.length) {
      $("#tops-legend-countries").html('<div class="text-center text-muted small py-2">Sin datos</div>');
      $("#tops-legend-manufacturers").html('<div class="text-center text-muted small py-2">Sin datos</div>');
      return;
    }

    // Count by country
    const countryCounts = {};
    coasters.forEach(c => {
      const country = c.country_name || 'Desconocido';
      countryCounts[country] = (countryCounts[country] || 0) + 1;
    });
    const sortedCountries = Object.entries(countryCounts).sort((a, b) => b[1] - a[1]);
    const maxCountry = sortedCountries[0][1];

    const $countries = $("#tops-legend-countries").empty();
    sortedCountries.slice(0, 15).forEach(([name, count]) => {
      const pct = Math.round((count / maxCountry) * 100);
      $countries.append(`
        <div class="tops-legend-item">
          <span class="tops-legend-name" title="${name}">${name}</span>
          <div class="tops-legend-bar-wrap">
            <div class="tops-legend-bar" style="width:${pct}%"></div>
          </div>
          <span class="tops-legend-count">${count}</span>
        </div>`);
    });

    // Count by manufacturer
    const mfrCounts = {};
    coasters.forEach(c => {
      const mfr = c.manufacter || 'Desconocido';
      mfrCounts[mfr] = (mfrCounts[mfr] || 0) + 1;
    });
    const sortedMfrs = Object.entries(mfrCounts).sort((a, b) => b[1] - a[1]);
    const maxMfr = sortedMfrs[0][1];

    const $mfrs = $("#tops-legend-manufacturers").empty();
    sortedMfrs.slice(0, 15).forEach(([name, count]) => {
      const pct = Math.round((count / maxMfr) * 100);
      $mfrs.append(`
        <div class="tops-legend-item">
          <span class="tops-legend-name" title="${name}">${name}</span>
          <div class="tops-legend-bar-wrap">
            <div class="tops-legend-bar" style="width:${pct}%"></div>
          </div>
          <span class="tops-legend-count">${count}</span>
        </div>`);
    });
  }

  // ── Helper: cambiar entre modos ──────────────────────────────────

  function setTopsMode(mode) {
    // Ocultar todo
    $(
      "#coasters-mode-preview, #coasters-mode-full, #coasters-mode-edit",
    ).addClass("d-none");
    $("#parks-mode-preview, #parks-mode-full, #parks-mode-edit").addClass(
      "d-none",
    );
    $("#tops-header-actions").addClass("d-none");
    $("#tops-back-btn-wrap").addClass("d-none");

    if (mode === "preview") {
      $("#coasters-mode-preview, #parks-mode-preview").removeClass("d-none");
      $("#tops-header-actions").removeClass("d-none");
    } else if (mode === "full") {
      $("#coasters-mode-full, #parks-mode-full").removeClass("d-none");
      $("#tops-back-btn-wrap").removeClass("d-none");
      renderCoastersFull();
      renderParksFull();
    } else if (mode === "edit") {
      $("#coasters-mode-edit, #parks-mode-edit").removeClass("d-none");
      $("#tops-back-btn-wrap").removeClass("d-none");
      renderCoastersEdit();
      renderParksEdit();
    }
  }

  // ── Carga inicial de datos ───────────────────────────────────────

  cargarTops();

  async function cargarTops() {
    try {
      const [resC, resP] = await Promise.all([
        fetch(`${BASE_URL}/api/php/profile_config.php?action=get_top_coasters`),
        fetch(`${BASE_URL}/api/php/profile_config.php?action=get_top_parks`),
      ]);
      const [dataC, dataP] = await Promise.all([resC.json(), resP.json()]);

      topsData.coasters = dataC.success && dataC.tops ? dataC.tops : [];
      topsData.parks = dataP.success && dataP.tops ? dataP.tops : [];

      populateCoastersFilters(topsData.coasters);
      populateParksFilters(topsData.parks);

      renderCoastersPreview(topsData.coasters);
      renderParksPreview(topsData.parks);
      renderTopsStats(topsData.coasters);
    } catch (e) {
      console.error("Error cargando tops:", e);
    }
  }

  // ── Botones de navegación ────────────────────────────────────────

  $("#btn-tops-full-view").on("click", () => setTopsMode("full"));
  $("#btn-tops-edit").on("click", () => setTopsMode("edit"));
  $("#btn-tops-back").on("click", () => setTopsMode("preview"));

  // ── Guardar Cambios ──────────────────────────────────────────────

  async function saveCoastersTop() {
    const $btn = $("#btn-save-coasters-top");
    const items = [];
    $("#top-coasters-list-edit .tops-edit-item").each(function (i) {
      items.push({ coaster_id: $(this).data("id"), rank_position: i + 1 });
    });

    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando…');

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/profile_config.php?action=save_top_coasters`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ items }),
        },
      );
      const data = await res.json();
      if (data.success) {
        await cargarTops(); // recarga datos frescos desde DB
        await cargarDatos(); // recarga panel de estadísticas automático
        setTopsMode("preview"); // vuelve al preview
      } else {
        alert("Error al guardar: " + (data.error || "desconocido"));
      }
    } catch (e) {
      console.error(e);
      alert("Error de red al guardar el top de coasters.");
    } finally {
      $btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
    }
  }

  async function saveParksTop() {
    const $btn = $("#btn-save-parks-top");
    const items = [];
    $("#top-parks-list-edit .tops-edit-item").each(function (i) {
      items.push({ park_id: $(this).data("id"), rank_position: i + 1 });
    });

    $btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando…');

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/profile_config.php?action=save_top_parks`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ items }),
        },
      );
      const data = await res.json();
      if (data.success) {
        await cargarTops();
        await cargarDatos(); // recarga panel de estadísticas automático
        setTopsMode("preview");
      } else {
        alert("Error al guardar: " + (data.error || "desconocido"));
      }
    } catch (e) {
      console.error(e);
      alert("Error de red al guardar el top de parques.");
    } finally {
      $btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-floppy-disk me-2"></i>Guardar Cambios');
    }
  }

  $("#btn-save-coasters-top").on("click", saveCoastersTop);
  $("#btn-save-parks-top").on("click", saveParksTop);

  // ── Sort y Filtros (Vista Completa) ──────────────────────────────

  $(
    "#coasters-sort, #coasters-filter-park, #coasters-filter-country, #coasters-filter-manufacter",
  ).on("change", renderCoastersFull);

  $("#parks-sort, #parks-filter-country").on("change", renderParksFull);

  // ── Toggle lista / cuadrícula ────────────────────────────────────

  $("#coasters-view-list").on("click", function () {
    coastersViewType = "list";
    $(this).removeClass("btn-outline-secondary").addClass("btn-success");
    $("#coasters-view-grid")
      .removeClass("btn-success")
      .addClass("btn-outline-secondary");
    renderCoastersFull();
  });
  $("#coasters-view-grid").on("click", function () {
    coastersViewType = "grid";
    $(this).removeClass("btn-outline-secondary").addClass("btn-success");
    $("#coasters-view-list")
      .removeClass("btn-success")
      .addClass("btn-outline-secondary");
    renderCoastersFull();
  });
  $("#parks-view-list").on("click", function () {
    parksViewType = "list";
    $(this).removeClass("btn-outline-secondary").addClass("btn-success");
    $("#parks-view-grid")
      .removeClass("btn-success")
      .addClass("btn-outline-secondary");
    renderParksFull();
  });
  $("#parks-view-grid").on("click", function () {
    parksViewType = "grid";
    $(this).removeClass("btn-outline-secondary").addClass("btn-success");
    $("#parks-view-list")
      .removeClass("btn-success")
      .addClass("btn-outline-secondary");
    renderParksFull();
  });

  // ── Eliminar items en modo edición ───────────────────────────────

  $(document).on(
    "click",
    "#top-coasters-list-edit .tops-remove-item",
    function () {
      const id = $(this).closest(".tops-edit-item").data("id");
      topsData.coasters = topsData.coasters.filter((d) => d.coaster_id != id);
      renderCoastersEdit();
      renderCoastersPreview(topsData.coasters);
    },
  );
  $(document).on(
    "click",
    "#top-parks-list-edit .tops-remove-item",
    function () {
      const id = $(this).closest(".tops-edit-item").data("id");
      topsData.parks = topsData.parks.filter((d) => d.park_id != id);
      renderParksEdit();
      renderParksPreview(topsData.parks);
    },
  );

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

  // === BÚSQUEDA DE MONTAÑAS RUSAS (MIS TOPS) ===
  let debounceTopCoaster = null;

  $("#top-coasters-search").on("input", function () {
    const searchTexto = $(this).val().trim();
    const dropdown = $("#top-coasters-dropdown");
    const icon = $("#top-coasters-search-icon");

    clearTimeout(debounceTopCoaster);

    if (searchTexto.length > 0) {
      icon
        .removeClass("fa-magnifying-glass text-muted")
        .addClass("fa-xmark text-danger")
        .css("cursor", "pointer");
    } else {
      icon
        .removeClass("fa-xmark text-danger")
        .addClass("fa-magnifying-glass text-muted")
        .css("cursor", "default");
    }

    if (searchTexto.length < 3) {
      dropdown.addClass("d-none").empty();
      return;
    }

    debounceTopCoaster = setTimeout(() => {
      $.ajax({
        url: BASE_URL + "/api/php/coasters.php",
        method: "GET",
        data: { action: "search", search: searchTexto, limit: 500 },
        success: function (data) {
          dropdown.empty();
          if (data.length === 0) {
            dropdown.append(
              '<li class="list-group-item text-muted small bg-dark border-secondary">No se encontraron montañas rusas</li>',
            );
          } else {
            data.forEach(function (coaster) {
              dropdown.append(
                `<li class="list-group-item list-group-item-action bg-dark text-white border-secondary" style="cursor:pointer;" data-id="${coaster.id}">
                  <strong>${coaster.coaster_name}</strong> <small class="text-secondary text-nowrap ms-2">en ${coaster.park_name}</small>
                </li>`,
              );
            });
          }
          dropdown.removeClass("d-none");
        },
        error: function (err) {
          console.error("Error buscando coasters", err);
        },
      });
    }, 300);
  });

  $("#top-coasters-search-icon").on("click", function () {
    if ($(this).hasClass("fa-xmark")) {
      $("#top-coasters-search").val("").trigger("input").focus();
      $("#top-coasters-dropdown").addClass("d-none").empty();
    }
  });

  $(document).on(
    "click",
    "#top-coasters-dropdown li.list-group-item-action",
    function () {
      const coasterId = $(this).data("id");
      const coasterName = $(this).find("strong").text();
      const parkName = $(this).find("small").text().replace("en ", "");

      // Evitar duplicados
      if (topsData.coasters.find((d) => d.coaster_id == coasterId)) {
        $("#top-coasters-search").val("").trigger("input");
        $("#top-coasters-dropdown").addClass("d-none");
        return;
      }

      // Capture current DOM order before re-rendering (preserves manual drag reordering)
      const currentOrder = [];
      $("#top-coasters-list-edit .tops-edit-item").each(function (i) {
        const id = $(this).data("id");
        const found = topsData.coasters.find((d) => d.coaster_id == id);
        if (found) currentOrder.push({ ...found, rank_position: i + 1 });
      });
      if (currentOrder.length === topsData.coasters.length)
        topsData.coasters = currentOrder;

      const newRank = topsData.coasters.length + 1;
      topsData.coasters.push({
        coaster_id: coasterId,
        coaster_name: coasterName,
        park_name: parkName,
        rank_position: newRank,
      });

      $("#top-coasters-search").val("").trigger("input");
      $("#top-coasters-dropdown").addClass("d-none");

      renderCoastersEdit();
      renderCoastersPreview(topsData.coasters);
    },
  );

  // === BÚSQUEDA DE PARQUES (MIS TOPS) ===
  let debounceTopPark = null;

  $("#top-parks-search").on("input", function () {
    const searchTexto = $(this).val().trim();
    const dropdown = $("#top-parks-dropdown");
    const icon = $("#top-parks-search-icon");

    clearTimeout(debounceTopPark);

    if (searchTexto.length > 0) {
      icon
        .removeClass("fa-magnifying-glass text-muted")
        .addClass("fa-xmark text-danger")
        .css("cursor", "pointer");
    } else {
      icon
        .removeClass("fa-xmark text-danger")
        .addClass("fa-magnifying-glass text-muted")
        .css("cursor", "default");
    }

    if (searchTexto.length < 3) {
      dropdown.addClass("d-none").empty();
      return;
    }

    debounceTopPark = setTimeout(() => {
      $.ajax({
        url: BASE_URL + "/api/php/profile_config.php",
        method: "GET",
        data: { action: "search", search: searchTexto }, // Usamos la de profile_config.php
        success: function (data) {
          dropdown.empty();
          if (data.length === 0) {
            dropdown.append(
              '<li class="list-group-item text-muted small bg-dark border-secondary">No se encontraron parques</li>',
            );
          } else {
            data.forEach(function (park) {
              dropdown.append(
                `<li class="list-group-item list-group-item-action bg-dark text-white border-secondary" style="cursor:pointer;" data-id="${park.park_id}">
                  <strong>${park.park_name}</strong> <small class="text-secondary text-nowrap ms-2">${park.country_name || ""}</small>
                </li>`,
              );
            });
          }
          dropdown.removeClass("d-none");
        },
        error: function (err) {
          console.error("Error buscando parques", err);
        },
      });
    }, 300);
  });

  $("#top-parks-search-icon").on("click", function () {
    if ($(this).hasClass("fa-xmark")) {
      $("#top-parks-search").val("").trigger("input").focus();
      $("#top-parks-dropdown").addClass("d-none").empty();
    }
  });

  $(document).on(
    "click",
    "#top-parks-dropdown li.list-group-item-action",
    function () {
      const parkId = $(this).data("id");
      const parkName = $(this).find("strong").text();
      const countryName = $(this).find("small").text();

      // Avoid duplicates
      if (topsData.parks.find((d) => d.park_id == parkId)) {
        $("#top-parks-search").val("").trigger("input");
        $("#top-parks-dropdown").addClass("d-none");
        return;
      }

      // Capture current DOM order before re-rendering (preserves manual drag reordering)
      const currentOrder = [];
      $("#top-parks-list-edit .tops-edit-item").each(function (i) {
        const id = $(this).data("id");
        const found = topsData.parks.find((d) => d.park_id == id);
        if (found) currentOrder.push({ ...found, rank_position: i + 1 });
      });
      if (currentOrder.length === topsData.parks.length)
        topsData.parks = currentOrder;

      const newRank = topsData.parks.length + 1;
      topsData.parks.push({
        park_id: parkId,
        park_name: parkName,
        country_name: countryName,
        rank_position: newRank,
      });

      $("#top-parks-search").val("").trigger("input");
      $("#top-parks-dropdown").addClass("d-none");

      renderParksEdit();
      renderParksPreview(topsData.parks);
    },
  );

  // === OCULTAR DROPDOWNS AL CLICAR FUERA ===
  $(document).on("click", function (e) {
    if (
      !$(e.target).closest("#top-coasters-search, #top-coasters-dropdown")
        .length
    ) {
      $("#top-coasters-dropdown").addClass("d-none");
    }
    if (!$(e.target).closest("#top-parks-search, #top-parks-dropdown").length) {
      $("#top-parks-dropdown").addClass("d-none");
    }
  });
});
