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
    onReady: function (selectedDates, dateStr, instance) {
      if (instance.altInput) {
        instance.altInput.id = "config-user-birthdate-alt";
        const label = document.querySelector(
          'label[for="config-user-birthdate"]',
        );
        if (label) label.setAttribute("for", "config-user-birthdate-alt");
      }
    },
  });

  function showSection(sectionId) {
    // Hide all contents
    document
      .querySelectorAll('.col-lg-8 > div[id^="section-"]')
      .forEach((el) => {
        el.classList.add("d-none");
      });

    // Remove active class from all menu items
    document
      .querySelectorAll("#profile-menu .list-group-item")
      .forEach((el) => {
        el.classList.remove("active");
      });

    // Show the target section
    const targetEl = document.getElementById(sectionId);
    if (targetEl) targetEl.classList.remove("d-none");

    // Add active class to corresponding menu item
    const menuItem = document.querySelector(
      `#profile-menu a[href="#${sectionId.replace("section-", "")}"]`,
    );
    if (menuItem) menuItem.classList.add("active");

    if (sectionId === "section-reviews") {
      loadUserReviews();
    } else if (sectionId === "section-tops") {
      window.loadUserTops();
    } else if (sectionId === "section-trips-content") {
      if (window.loadTrips) window.loadTrips();
      if (window.loadRanking) window.loadRanking();
    }
  }

  // Setup trips view toggle (Grid vs Stats)
  document
    .querySelectorAll('input[name="trips-view-toggle"]')
    .forEach((radio) => {
      radio.addEventListener("change", function () {
        if (this.value === "list") {
          document.getElementById("trips-view-list").classList.remove("d-none");
          document.getElementById("trips-view-stats").classList.add("d-none");
        } else {
          document.getElementById("trips-view-list").classList.add("d-none");
          document
            .getElementById("trips-view-stats")
            .classList.remove("d-none");
        }
      });
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
  }

  // ── Delegación de eventos para expandir tarjetas de Top (Coasters/Parques) ──
  $(document).on("click", ".btn-toggle-stats", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const $btn = $(this);
    const $card = $btn.closest(".top-card");
    const $stats = $card.find(".stats-expandable");
    const $icon = $btn.find("i");
    const $label = $btn.find("span");

    if ($stats.hasClass("d-none")) {
      // Expandir
      $stats.removeClass("d-none");
      $icon.removeClass("fa-plus-circle fa-plus").addClass("fa-minus-circle");
      if ($label.length) $label.text("Contraer");
    } else {
      // Contraer
      $stats.addClass("d-none");
      $icon.removeClass("fa-minus-circle fa-minus").addClass("fa-plus-circle");
      if ($label.length) $label.text("Ver detalles");
    }
  });

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
          headers: {
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
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
        btnDir.on("click", function () {
          const currentDir = $(this).attr("data-dir");
          const newDir = currentDir === "asc" ? "desc" : "asc";
          $(this).attr("data-dir", newDir);
          $(this).html(
            `<i class="fa-solid fa-caret-${newDir === "asc" ? "up" : "down"}"></i>`,
          );
          loadTops();
        });

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
          let imgSrc = data.user.profile_image;
          // Si es URL absoluta, usarla tal cual
          if (imgSrc.startsWith("http://") || imgSrc.startsWith("https://")) {
            // OK — usar directamente
          } else if (imgSrc.startsWith("/")) {
            // Ruta local de otro XAMPP → no existe en esta máquina → ignorar
            if (imgSrc.includes("/web/img/uploads/")) {
              imgSrc = null; // No mostrar imagen rota
            } else {
              imgSrc = window.BASE_URL + imgSrc;
            }
          } else {
            // Solo nombre de archivo → construir URL Supabase
            imgSrc =
              "https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/" +
              imgSrc;
          }

          if (imgSrc) {
            // En la tarjeta de perfil
            const avatarDiv = document.querySelector(".avatar-circle");
            if (avatarDiv) {
              avatarDiv.innerHTML = `<img src="${imgSrc}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" onerror="this.parentElement.innerHTML=this.parentElement.dataset.initials||'?'">`;
            }
            // En el header del navbar
            const headerAvatar = document.getElementById("header-avatar");
            if (headerAvatar) {
              headerAvatar.innerHTML = `<img src="${imgSrc}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" onerror="this.parentElement.innerHTML=this.parentElement.dataset.initials||'?'">`;
            }
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

      if (!compressedBlob) {
        showAvatarError(
          "No se pudo procesar la imagen. El formato podría no estar soportado o la imagen está corrupta.",
        );
        return;
      }

      // Preview inmediata con la imagen comprimida
      const previewUrl = URL.createObjectURL(compressedBlob);
      const avatarDiv = document.querySelector(".avatar-circle");
      if (avatarDiv) {
        avatarDiv.innerHTML = `<img src="${previewUrl}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
      }

      try {
        const photoUrl = await subirFoto(compressedBlob, file.name);

        // Actualizar cabecera del navbar en tiempo real con la foto
        const headerAvatar = document.getElementById("header-avatar");
        if (headerAvatar) {
          headerAvatar.innerHTML = `<img src="${previewUrl}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">`;
        }

        const res = await fetch(
          BASE_URL + "/api/php/profile_config.php?action=update_avatar",
          {
            method: "POST",
            body: JSON.stringify({ photo_url: photoUrl }),
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token":
                document
                  .querySelector('meta[name="csrf-token"]')
                  ?.getAttribute("content") ?? "",
            },
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
        let errorMsg = err.message;
        if (
          errorMsg.includes("the string did not match the expected pattern") ||
          errorMsg.includes("is not of type 'Blob'")
        ) {
          errorMsg =
            "No se pudo procesar la imagen correctamente. Intenta con un archivo diferente (JPG o PNG válido).";
        }
        showAvatarError(errorMsg);
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
        canvas.toBlob((blob) => resolve(blob), "image/jpeg", quality);
      };
      img.onerror = function () {
        URL.revokeObjectURL(url);
        resolve(null);
      };
      img.src = url;
    });
  }

  async function subirFoto(blob, originalName) {
    if (!blob) throw new Error("Archivo inválido o corrupto");
    const formData = new FormData();
    let safeName = (originalName || "avatar").replace(/[^a-zA-Z0-9.-]/g, "_");
    const filename = safeName.replace(/\.[^.]+$/, "") + ".jpg";
    formData.append("file", blob, filename);
    formData.append("bucket", "avatars");

    try {
      const res = await fetch(`${BASE_URL}/api/php/upload.php`, {
        method: "POST",
        headers: {
          "X-CSRF-Token":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") ?? "",
        },
        body: formData,
      });

      const rawText = await res.text();

      let data;
      try {
        data = JSON.parse(rawText);
      } catch (parseErr) {
        throw new Error("El servidor no devolvió una respuesta JSON válida.");
      }

      if (!data.success) {
        throw new Error(data.error || "Error al subir la foto");
      }

      return data.url;
    } catch (e) {
      throw e;
    }
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
        <a href="${BASE_URL}/web/views/public/coasters/coasters.php?id=${item.coaster_id}" class="tops-preview-item text-decoration-none">
          <span class="tops-preview-rank">#${i + 1}</span>
          <div class="flex-grow-1">
            <div class="fw-bold text-white" style="line-height: 1.2; margin-bottom: 2px;">${item.coaster_name}</div>
            <small class="text-secondary" style="line-height: 1.1; display: block;">${item.park_name} · ${item.country_name || ""}</small>
          </div>
          <i class="fa-solid fa-chevron-right text-muted small ms-2"></i>
        </a>`);
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
        <a href="${BASE_URL}/web/views/public/parks/parks.php?id=${item.park_id}" class="tops-preview-item text-decoration-none">
          <span class="tops-preview-rank">#${i + 1}</span>
          <div class="flex-grow-1">
            <div class="fw-bold text-white" style="line-height: 1.2; margin-bottom: 2px;">${item.park_name}</div>
            <small class="text-secondary" style="line-height: 1.1; display: block;">${item.country_name || ""}</small>
          </div>
          <i class="fa-solid fa-chevron-right text-muted small ms-2"></i>
        </a>`);
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
    const sortDir = $("#coasters-sort-dir").attr("data-dir") || "desc";
    const fPark = $("#coasters-filter-park").val();
    const fCountry = $("#coasters-filter-country").val();
    const fMfr = $("#coasters-filter-manufacter").val();
    const fModel = $("#coasters-filter-model").val();
    const isGrid = coastersViewType === "grid";

    let data = [...topsData.coasters];

    // Filtrar
    if (fPark) data = data.filter((d) => d.park_name === fPark);
    if (fCountry) data = data.filter((d) => d.country_name === fCountry);
    if (fMfr) data = data.filter((d) => d.manufacter === fMfr);
    if (fModel) data = data.filter((d) => d.model === fModel);

    // Ordenar
    const asc = sortDir === "asc";
    if (sort === "name") {
      data.sort((a, b) => {
        const c = a.coaster_name.localeCompare(b.coaster_name);
        return asc ? c : -c;
      });
    } else if (sort === "height") {
      data.sort((a, b) =>
        asc
          ? (parseFloat(a.height) || 0) - (parseFloat(b.height) || 0)
          : (parseFloat(b.height) || 0) - (parseFloat(a.height) || 0),
      );
    } else if (sort === "speed") {
      data.sort((a, b) =>
        asc
          ? (parseFloat(a.speed) || 0) - (parseFloat(b.speed) || 0)
          : (parseFloat(b.speed) || 0) - (parseFloat(a.speed) || 0),
      );
    } else if (sort === "length") {
      data.sort((a, b) =>
        asc
          ? (parseFloat(a.coaster_length) || 0) -
            (parseFloat(b.coaster_length) || 0)
          : (parseFloat(b.coaster_length) || 0) -
            (parseFloat(a.coaster_length) || 0),
      );
    } else if (sort === "inversions") {
      data.sort((a, b) =>
        asc
          ? (parseInt(a.inversions) || 0) - (parseInt(b.inversions) || 0)
          : (parseInt(b.inversions) || 0) - (parseInt(a.inversions) || 0),
      );
    } else if (sort === "year") {
      data.sort((a, b) => {
        const ya = parseInt(a.opening_year) || 9999;
        const yb = parseInt(b.opening_year) || 9999;
        return asc ? ya - yb : yb - ya;
      });
    } else if (sort === "rank") {
      data.sort((a, b) => {
        const ra = parseInt(a.rank_position) || 9999;
        const rb = parseInt(b.rank_position) || 9999;
        return asc ? ra - rb : rb - ra;
      });
    }

    const container = $("#top-coasters-full-container").empty();

    // Update counter pill — singular / plural
    const count = data.length;
    $("#coasters-full-count").text(count);
    $("#coasters-full-label").text(count === 1 ? "coaster" : "coasters");

    if (!data.length) {
      container.html(emptyState("Ningún elemento coincide con los filtros."));
      return;
    }

    const colClass = isGrid ? "col-6 col-md-4" : "col-12";

    // Definir qué badges mostrar según el criterio de sort
    function getStatBadges(item, sortKey) {
      const mfr = item.manufacter
        ? `<small class="text-secondary d-flex align-items-center gap-1" title="${item.manufacter}"><i class="fa-solid fa-industry"></i><span class="text-truncate d-inline-block" style="max-width: 100px;">${item.manufacter}</span></small>`
        : "";

      if (sortKey === "height") {
        return (
          (item.height
            ? `<small class="text-info d-flex align-items-center gap-1"><i class="fa-solid fa-ruler-vertical"></i>${item.height} m</small>`
            : "") + mfr
        );
      }
      if (sortKey === "speed") {
        return (
          (item.speed
            ? `<small class="text-warning d-flex align-items-center gap-1"><i class="fa-solid fa-bolt"></i>${item.speed} km/h</small>`
            : "") + mfr
        );
      }
      if (sortKey === "length") {
        return (
          (item.coaster_length
            ? `<small class="text-info d-flex align-items-center gap-1"><i class="fa-solid fa-ruler-horizontal"></i>${item.coaster_length} m</small>`
            : "") + mfr
        );
      }
      if (sortKey === "inversions") {
        return (
          (item.inversions != null
            ? `<small class="text-warning d-flex align-items-center gap-1"><i class="fa-solid fa-infinity"></i>${item.inversions} inv.</small>`
            : "") + mfr
        );
      }
      if (sortKey === "year") {
        return (
          (item.opening_year
            ? `<small class="text-secondary d-flex align-items-center gap-1"><i class="fa-regular fa-calendar"></i>${item.opening_year}</small>`
            : "") + mfr
        );
      }
      return (
        (item.height
          ? `<small class="text-info d-flex align-items-center gap-1"><i class="fa-solid fa-ruler-vertical"></i>${item.height}m</small>`
          : "") +
        (item.speed
          ? `<small class="text-warning d-flex align-items-center gap-1"><i class="fa-solid fa-bolt"></i>${item.speed}km/h</small>`
          : "") +
        mfr
      );
    }

    data.forEach((item) => {
      const img = item.imagen_url
        ? `<img src="${item.imagen_url.startsWith("/") ? BASE_URL + item.imagen_url : item.imagen_url}" alt="${item.coaster_name}" loading="lazy">`
        : `<div style="height:150px;background:#0d1117;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

      const detailUrl = `${BASE_URL}/web/views/public/coasters/coasters.php?id=${item.coaster_id}`;

      if (isGrid) {
        container.append(`
          <div class="${colClass}">
            <a href="${detailUrl}" class="top-card position-relative d-block text-decoration-none shadow-sm">
              ${img}
              <span class="rank-badge">#${item.rank_position}</span>
              <div class="p-2">
                <div class="fw-bold text-white small text-truncate" style="font-family: var(--rcw-font-title);">${item.coaster_name}</div>
                <div class="text-secondary text-truncate" style="font-size:.7rem;">${item.park_name}</div>
              </div>
            </a>
          </div>`);
      } else {
        const isMobile = window.innerWidth < 768;
        const isRankSort = sort === "rank" || sort === "rank_position";
        const hasToggle = isRankSort || sort === "name";
        const shouldCollapse = hasToggle && (isMobile || isRankSort);

        // Estrellas
        const starsVal = parseFloat(item.stars) || 0;
        const starsHtml =
          starsVal > 0
            ? `<div class="d-flex align-items-center gap-1 text-warning fw-bold" style="font-size:0.9rem;">
               <span>${starsVal.toFixed(1)}</span>
               <i class="fa-solid fa-star" style="font-size:0.75rem;"></i>
             </div>`
            : "";

        container.append(`
          <div class="${colClass} mb-3">
            <div class="top-card d-flex flex-column flex-md-row text-decoration-none shadow-sm position-relative" 
               style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; transition: all 0.3s ease;">
              
              <!-- Imagen y Ranking -->
              <div class="rank-img-container p-2 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-radius: 8px 0 0 8px; min-width: 110px;">
                <div class="position-relative">
                  <img src="${item.imagen_url.startsWith("/") ? BASE_URL + item.imagen_url : item.imagen_url}" 
                       alt="${item.coaster_name}" 
                       class="rounded" 
                       style="width: 90px; height: 90px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                  <span class="rank-badge" style="position: absolute; top: -5px; left: -5px; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.5);">#${item.rank_position}</span>
                </div>
              </div>

              <!-- Contenido -->
              <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="pe-3">
                    <a href="${detailUrl}" class="fw-bold text-white text-decoration-none d-block hover-green" style="font-family: var(--rcw-font-title); font-size: 1.1rem; line-height: 1.2;">
                      ${item.coaster_name}
                    </a>
                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem; opacity: 0.8;">
                      <i class="fa-solid fa-location-dot me-1"></i>${item.park_name} · ${item.country_name || ""}
                    </small>
                  </div>
                  ${starsHtml}
                </div>

                <!-- Stats Expandibles -->
                <div class="stats-expandable mt-2 d-flex gap-2 flex-wrap ${shouldCollapse ? "d-none" : ""}">
                  ${getStatBadges(item, sort)
                    .replace(
                      /<small/g,
                      '<small class="badge bg-dark border border-secondary text-secondary fw-normal px-2 py-1"',
                    )
                    .replace(/fa-industry/g, "fa-industry me-1")
                    .replace(/fa-bolt/g, "fa-bolt me-1")}
                </div>

                ${
                  hasToggle
                    ? `
                <button class="btn btn-sm btn-toggle-stats text-muted p-0 mt-2 d-flex align-items-center gap-1" style="font-size: 0.7rem; background:transparent; border:none;">
                   <i class="fa-solid fa-${shouldCollapse ? "plus" : "minus"}-circle"></i>
                   <span>${shouldCollapse ? "Ver detalles" : "Contraer"}</span>
                </button>`
                    : ""
                }
              </div>

              <!-- Acceso rápido -->
              <a href="${detailUrl}" class="d-none d-md-flex align-items-center px-3 border-start border-secondary border-opacity-10 opacity-25 hover-opacity-100" style="transition: opacity 0.2s;">
                 <i class="fa-solid fa-chevron-right fs-5 text-white"></i>
              </a>
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
        ? `<img src="${item.imagen_url.startsWith("/") ? BASE_URL + item.imagen_url : item.imagen_url}" alt="${item.park_name}" loading="lazy">`
        : `<div style="height:150px;background:#0d1117;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-image text-secondary fs-3"></i></div>`;

      if (isGrid) {
        const detailUrl = `${BASE_URL}/web/views/public/parks/parks.php?id=${item.park_id}`;
        container.append(`
          <div class="${colClass}">
            <a href="${detailUrl}" class="top-card position-relative d-block text-decoration-none">
              ${img}
              <span class="rank-badge">#${item.rank_position}</span>
              <div class="p-2">
                <div class="fw-bold text-white small text-truncate">${item.park_name}</div>
                <div class="text-secondary" style="font-size:.75rem;">${item.country_name || ""}</div>
              </div>
            </a>
          </div>`);
      } else {
        const isRankSort = sort === "rank_position";
        const detailUrl = `${BASE_URL}/web/views/public/parks/parks.php?id=${item.park_id}`;

        // Estrellas
        const starsVal = parseFloat(item.stars) || 0;
        const starsHtml =
          starsVal > 0
            ? `<div class="d-flex align-items-center gap-1 text-warning fw-bold" style="font-size:0.9rem;">
               <span>${starsVal.toFixed(1)}</span>
               <i class="fa-solid fa-star" style="font-size:0.75rem;"></i>
             </div>`
            : "";

        container.append(`
          <div class="${colClass} mb-3">
            <div class="top-card d-flex flex-column flex-md-row text-decoration-none shadow-sm position-relative" 
               style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; transition: all 0.3s ease;">
              
              <!-- Imagen y Ranking -->
              <div class="rank-img-container p-2 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.2); border-radius: 8px 0 0 8px; min-width: 110px;">
                <div class="position-relative">
                   <img src="${item.imagen_url.startsWith("/") ? BASE_URL + item.imagen_url : item.imagen_url}" 
                       alt="${item.park_name}" 
                       class="rounded" 
                       style="width: 90px; height: 90px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                  <span class="rank-badge" style="position: absolute; top: -5px; left: -5px; font-size: 0.7rem; padding: 2px 8px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.5);">#${item.rank_position}</span>
                </div>
              </div>

              <!-- Contenido -->
              <div class="p-3 flex-grow-1 d-flex flex-column justify-content-center">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <div class="pe-3">
                    <a href="${detailUrl}" class="fw-bold text-white text-decoration-none d-block hover-green" style="font-family: var(--rcw-font-title); font-size: 1.1rem; line-height: 1.2;">
                      ${item.park_name}
                    </a>
                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem; opacity: 0.8;">
                      <i class="fa-solid fa-location-dot me-1"></i>${item.country_name || ""}
                    </small>
                  </div>
                  ${starsHtml}
                </div>

                <!-- Stats Expandibles -->
                <div class="stats-expandable mt-2 d-flex gap-2 flex-wrap ${isRankSort ? "d-none" : ""}">
                  ${item.operating_coasters ? `<small class="badge bg-dark border border-secondary text-info fw-normal px-2 py-1"><i class="fa-solid fa-ticket me-1"></i>${item.operating_coasters} coasters</small>` : ""}
                </div>

                ${
                  isRankSort
                    ? `
                <button class="btn btn-sm btn-toggle-stats text-muted p-0 mt-2 d-flex align-items-center gap-1" style="font-size: 0.7rem; background:transparent; border:none;">
                   <i class="fa-solid fa-plus-circle"></i>
                   <span>Ver detalles</span>
                </button>`
                    : ""
                }
              </div>

              <!-- Acceso rápido -->
              <a href="${detailUrl}" class="d-none d-md-flex align-items-center px-3 border-start border-secondary border-opacity-10 opacity-25 hover-opacity-100" style="transition: opacity 0.2s;">
                 <i class="fa-solid fa-chevron-right fs-5 text-white"></i>
              </a>
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
          <div class="flex-grow-1 min-w-0 pe-3">
            <div class="fw-bold text-white text-truncate">${item.coaster_name}</div>
            <small class="text-secondary d-block text-truncate">${item.park_name}</small>
          </div>
          <div class="d-flex gap-1">
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary border-0 square-box dropdown-toggle dropdown-toggle-split"
                data-bs-toggle="dropdown" aria-expanded="false" title="Mover a...">
                <i class="fa-solid fa-arrows-up-down"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;">
                <li><button class="dropdown-item tops-move-first" type="button">
                  <i class="fa-solid fa-angles-up me-2 text-success"></i>Mover al principio
                </button></li>
                <li><button class="dropdown-item tops-move-last" type="button">
                  <i class="fa-solid fa-angles-down me-2 text-warning"></i>Mover al final
                </button></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <div class="px-3 py-1 d-flex align-items-center gap-2">
                    <span style="font-size:0.85rem;white-space:nowrap;">Pos.:</span>
                    <input type="number" min="1" class="form-control form-control-sm bg-dark text-white border-secondary tops-move-pos-input"
                      style="width:60px;" placeholder="#">
                    <button class="btn btn-sm btn-success rounded-0 tops-move-pos-btn" type="button">OK</button>
                  </div>
                </li>
              </ul>
            </div>
            <button class="btn btn-sm btn-outline-danger border-0 square-box tops-remove-item"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>`);
    });
    if (window.Sortable) {
      new Sortable(document.getElementById("top-coasters-list-edit"), {
        handle: ".drag-handle",
        animation: 150,
        onEnd: function () {
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
          <div class="d-flex gap-1">
            <div class="dropdown">
              <button class="btn btn-sm btn-outline-secondary border-0 square-box dropdown-toggle dropdown-toggle-split"
                data-bs-toggle="dropdown" aria-expanded="false" title="Mover a...">
                <i class="fa-solid fa-arrows-up-down"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width:200px;">
                <li><button class="dropdown-item tops-move-first" type="button">
                  <i class="fa-solid fa-angles-up me-2 text-success"></i>Mover al principio
                </button></li>
                <li><button class="dropdown-item tops-move-last" type="button">
                  <i class="fa-solid fa-angles-down me-2 text-warning"></i>Mover al final
                </button></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <div class="px-3 py-1 d-flex align-items-center gap-2">
                    <span style="font-size:0.85rem;white-space:nowrap;">Pos.:</span>
                    <input type="number" min="1" class="form-control form-control-sm bg-dark text-white border-secondary tops-move-pos-input"
                      style="width:60px;" placeholder="#">
                    <button class="btn btn-sm btn-success rounded-0 tops-move-pos-btn" type="button">OK</button>
                  </div>
                </li>
              </ul>
            </div>
            <button class="btn btn-sm btn-outline-danger border-0 square-box tops-remove-item"><i class="fa-solid fa-trash"></i></button>
          </div>
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
    const models = [
      ...new Set(data.map((d) => d.model).filter(Boolean)),
    ].sort();

    const $pk = $("#coasters-filter-park").find("option:first").end();
    const $co = $("#coasters-filter-country").find("option:first").end();
    const $mf = $("#coasters-filter-manufacter").find("option:first").end();
    const $mo = $("#coasters-filter-model").find("option:first").end();

    $pk.find("option:not(:first)").remove();
    $co.find("option:not(:first)").remove();
    $mf.find("option:not(:first)").remove();
    $mo.find("option:not(:first)").remove();

    parks.forEach((v) => $pk.append(`<option value="${v}">${v}</option>`));
    countries.forEach((v) => $co.append(`<option value="${v}">${v}</option>`));
    mfrs.forEach((v) => $mf.append(`<option value="${v}">${v}</option>`));
    models.forEach((v) => $mo.append(`<option value="${v}">${v}</option>`));
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
      $("#tops-legend-countries").html(
        '<div class="text-center text-muted small py-2">Sin datos</div>',
      );
      $("#tops-legend-manufacturers").html(
        '<div class="text-center text-muted small py-2">Sin datos</div>',
      );
      return;
    }

    // Count by country
    const countryCounts = {};
    coasters.forEach((c) => {
      const country = c.country_name || "Desconocido";
      countryCounts[country] = (countryCounts[country] || 0) + 1;
    });
    const sortedCountries = Object.entries(countryCounts).sort(
      (a, b) => b[1] - a[1],
    );
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
    coasters.forEach((c) => {
      const mfr = c.manufacter || "Desconocido";
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

  // ── Modal Estadísticas Ampliadas ─────────────────────────────────
  $("#tops-stats-sidebar").on("click", function () {
    const coasters = topsData.coasters || [];
    const parks = topsData.parks || [];

    // 1. Contadores Globales
    $("#modal-stat-total-coasters").text(coasters.length);
    $("#modal-stat-total-parks").text(parks.length);

    const allCountries = new Set();
    coasters.forEach((c) => c.country_name && allCountries.add(c.country_name));
    parks.forEach((p) => p.country_name && allCountries.add(p.country_name));
    $("#modal-stat-total-countries").text(allCountries.size);

    // 2. Coasters por País (Completo)
    const countryCounts = {};
    coasters.forEach((c) => {
      const country = c.country_name || "Desconocido";
      countryCounts[country] = (countryCounts[country] || 0) + 1;
    });
    const sortedCountries = Object.entries(countryCounts).sort(
      (a, b) => b[1] - a[1],
    );
    const maxCountry = sortedCountries.length ? sortedCountries[0][1] : 1;

    const $modalCountries = $("#modal-list-countries").empty();
    sortedCountries.forEach(([name, count], idx) => {
      const pct = Math.round((count / maxCountry) * 100);
      $modalCountries.append(`
        <div class="px-4 py-3 d-flex align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
          <div class="flex-shrink-0 d-flex align-items-center justify-content-center fw-bold me-3" style="width:28px;height:28px;background:rgba(16,185,129,0.1);color:#10b981;font-size:0.75rem;">${idx + 1}</div>
          <div class="flex-grow-1 pe-3">
            <div class="d-flex justify-content-between align-items-end mb-2">
              <span class="fw-bold text-white small text-truncate" title="${name}">${name}</span>
              <span class="fw-bold text-success" style="font-size:0.85rem;">${count}</span>
            </div>
            <div style="height:4px;background:rgba(255,255,255,0.1);border-radius:2px;overflow:hidden;">
              <div style="height:100%;width:${pct}%;background:linear-gradient(90deg, #059669, #10b981);border-radius:2px;box-shadow:0 0 5px rgba(16,185,129,0.5);"></div>
            </div>
          </div>
        </div>
      `);
    });
    if (!sortedCountries.length)
      $modalCountries.append(
        '<div class="p-4 text-center text-muted" style="font-size:0.8rem;">No hay datos</div>',
      );

    // 3. Coasters por Fabricante (Completo)
    const mfrCounts = {};
    coasters.forEach((c) => {
      const mfr = c.manufacter || "Desconocido";
      mfrCounts[mfr] = (mfrCounts[mfr] || 0) + 1;
    });
    const sortedMfrs = Object.entries(mfrCounts).sort((a, b) => b[1] - a[1]);
    const maxMfr = sortedMfrs.length ? sortedMfrs[0][1] : 1;

    const $modalMfrs = $("#modal-list-manufacturers").empty();
    sortedMfrs.forEach(([name, count], idx) => {
      const pct = Math.round((count / maxMfr) * 100);
      $modalMfrs.append(`
        <div class="px-4 py-3 d-flex align-items-center" style="border-bottom: 1px solid rgba(255,255,255,0.06); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
          <div class="flex-shrink-0 d-flex align-items-center justify-content-center fw-bold me-3" style="width:28px;height:28px;background:rgba(16,185,129,0.1);color:#10b981;font-size:0.75rem;">${idx + 1}</div>
          <div class="flex-grow-1 pe-3">
            <div class="d-flex justify-content-between align-items-end mb-2">
              <span class="fw-bold text-white small text-truncate" title="${name}">${name}</span>
              <span class="fw-bold text-success" style="font-size:0.85rem;">${count}</span>
            </div>
            <div style="height:4px;background:rgba(255,255,255,0.1);border-radius:2px;overflow:hidden;">
              <div style="height:100%;width:${pct}%;background:linear-gradient(90deg, #059669, #10b981);border-radius:2px;box-shadow:0 0 5px rgba(16,185,129,0.5);"></div>
            </div>
          </div>
        </div>
      `);
    });
    if (!sortedMfrs.length)
      $modalMfrs.append(
        '<div class="p-4 text-center text-muted" style="font-size:0.8rem;">No hay datos</div>',
      );

    // 4. TOP RECORDS PERSONALES
    let maxHeight = 0;
    let maxHeightCoaster = null;
    let maxSpeed = 0;
    let maxSpeedCoaster = null;
    let maxLength = 0;
    let maxLengthCoaster = null;
    let maxInvers = 0;
    let maxInversCoaster = null;
    let minYear = Infinity;
    let minYearCoaster = null;

    coasters.forEach((c) => {
      const h = parseFloat(c.height);
      if (h > maxHeight) {
        maxHeight = h;
        maxHeightCoaster = c.coaster_name;
      }

      const s = parseFloat(c.speed);
      if (s > maxSpeed) {
        maxSpeed = s;
        maxSpeedCoaster = c.coaster_name;
      }

      const l = parseFloat(c.coaster_length);
      if (l > maxLength) {
        maxLength = l;
        maxLengthCoaster = c.coaster_name;
      }

      const inv = parseInt(c.inversions);
      if (inv > maxInvers) {
        maxInvers = inv;
        maxInversCoaster = c.coaster_name;
      }

      const y = parseInt(c.opening_year);
      if (y > 0 && y < minYear) {
        minYear = y;
        minYearCoaster = c.coaster_name;
      }
    });

    $("#max-stat-height").text(maxHeight > 0 ? `${maxHeight} m` : "—");
    $("#max-stat-height-name")
      .text(maxHeightCoaster || "—")
      .attr("title", maxHeightCoaster || "");

    $("#max-stat-speed").text(maxSpeed > 0 ? `${maxSpeed} km/h` : "—");
    $("#max-stat-speed-name")
      .text(maxSpeedCoaster || "—")
      .attr("title", maxSpeedCoaster || "");

    $("#max-stat-length").text(maxLength > 0 ? `${maxLength} m` : "—");
    $("#max-stat-length-name")
      .text(maxLengthCoaster || "—")
      .attr("title", maxLengthCoaster || "");

    $("#max-stat-inversions").text(maxInvers > 0 ? maxInvers : "—");
    $("#max-stat-inversions-name")
      .text(maxInversCoaster || "—")
      .attr("title", maxInversCoaster || "");

    $("#max-stat-year").text(minYear !== Infinity ? minYear : "—");
    $("#max-stat-year-name")
      .text(minYearCoaster || "—")
      .attr("title", minYearCoaster || "");

    // Mostrar modal
    const modalEl = document.getElementById("statsExpandedModal");
    if (modalEl) {
      new bootstrap.Modal(modalEl).show();
    }
  });

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
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
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
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") ?? "",
          },
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
    "#coasters-sort, #coasters-filter-park, #coasters-filter-country, #coasters-filter-manufacter, #coasters-filter-model",
  ).on("change", renderCoastersFull);

  // Botón de dirección (asc/desc) para el sort de coasters
  $("#coasters-sort-dir").on("click", function () {
    const currentDir = $(this).attr("data-dir");
    const newDir = currentDir === "desc" ? "asc" : "desc";
    $(this).attr("data-dir", newDir);
    const icon = $(this).find("i");
    if (newDir === "asc") {
      icon.removeClass().addClass("fa-solid fa-caret-up");
    } else {
      icon.removeClass().addClass("fa-solid fa-caret-down");
    }
    renderCoastersFull();
  });

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

  // ── Mover items en modo edición ───────────────────────────────────

  function reindexTopsDOM(listId) {
    $(`#${listId} .tops-edit-item`).each(function (i) {
      $(this)
        .find(".tops-rank-badge")
        .text("#" + (i + 1));
    });
  }

  function moveTopsItem(listId, $item, targetIndex) {
    const $list = $(`#${listId}`);
    $item.detach();
    const $remaining = $list.find(".tops-edit-item");
    const total = $remaining.length;
    targetIndex = Math.max(0, Math.min(targetIndex, total));
    if (targetIndex >= total) {
      $list.append($item);
    } else {
      $remaining.eq(targetIndex).before($item);
    }
    reindexTopsDOM(listId);
  }

  function syncTopsDataFromDOM(listId, dataArray, idKey) {
    const newOrder = [];
    $(`#${listId} .tops-edit-item`).each(function (i) {
      const id = $(this).data("id");
      const found = dataArray.find((d) => d[idKey] == id);
      if (found) newOrder.push({ ...found, rank_position: i + 1 });
    });
    return newOrder;
  }

  // -- Coasters: mover al principio
  $(document).on(
    "click",
    "#top-coasters-list-edit .tops-move-first",
    function () {
      const $item = $(this).closest(".tops-edit-item");
      moveTopsItem("top-coasters-list-edit", $item, 0);
      topsData.coasters = syncTopsDataFromDOM(
        "top-coasters-list-edit",
        topsData.coasters,
        "coaster_id",
      );
      renderCoastersPreview(topsData.coasters);
    },
  );
  // -- Coasters: mover al final
  $(document).on(
    "click",
    "#top-coasters-list-edit .tops-move-last",
    function () {
      const $item = $(this).closest(".tops-edit-item");
      const total = $("#top-coasters-list-edit .tops-edit-item").length;
      moveTopsItem("top-coasters-list-edit", $item, total);
      topsData.coasters = syncTopsDataFromDOM(
        "top-coasters-list-edit",
        topsData.coasters,
        "coaster_id",
      );
      renderCoastersPreview(topsData.coasters);
    },
  );
  // -- Coasters: mover a posición específica
  $(document).on(
    "click",
    "#top-coasters-list-edit .tops-move-pos-btn",
    function () {
      const $item = $(this).closest(".tops-edit-item");
      const pos = parseInt($(this).siblings(".tops-move-pos-input").val(), 10);
      if (!pos || pos < 1) return;
      moveTopsItem("top-coasters-list-edit", $item, pos - 1);
      topsData.coasters = syncTopsDataFromDOM(
        "top-coasters-list-edit",
        topsData.coasters,
        "coaster_id",
      );
      renderCoastersPreview(topsData.coasters);
    },
  );

  // -- Parks: mover al principio
  $(document).on("click", "#top-parks-list-edit .tops-move-first", function () {
    const $item = $(this).closest(".tops-edit-item");
    moveTopsItem("top-parks-list-edit", $item, 0);
    topsData.parks = syncTopsDataFromDOM(
      "top-parks-list-edit",
      topsData.parks,
      "park_id",
    );
    renderParksPreview(topsData.parks);
  });
  // -- Parks: mover al final
  $(document).on("click", "#top-parks-list-edit .tops-move-last", function () {
    const $item = $(this).closest(".tops-edit-item");
    const total = $("#top-parks-list-edit .tops-edit-item").length;
    moveTopsItem("top-parks-list-edit", $item, total);
    topsData.parks = syncTopsDataFromDOM(
      "top-parks-list-edit",
      topsData.parks,
      "park_id",
    );
    renderParksPreview(topsData.parks);
  });
  // -- Parks: mover a posición específica
  $(document).on(
    "click",
    "#top-parks-list-edit .tops-move-pos-btn",
    function () {
      const $item = $(this).closest(".tops-edit-item");
      const pos = parseInt($(this).siblings(".tops-move-pos-input").val(), 10);
      if (!pos || pos < 1) return;
      moveTopsItem("top-parks-list-edit", $item, pos - 1);
      topsData.parks = syncTopsDataFromDOM(
        "top-parks-list-edit",
        topsData.parks,
        "park_id",
      );
      renderParksPreview(topsData.parks);
    },
  );

  function showSection(sectionId) {
    // Ocultar todas las secciones principales
    $(
      "#section-profile-content, #section-config-content, #section-tops-content, #section-reviews-content, #section-friends-content, #section-trips-content, #section-map-content",
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
    } else if (sectionId === "#profile-trips") {
      $("#section-trips-content").removeClass("d-none");
      $("#menu-trips").addClass("active");
    } else if (sectionId === "#profile-map") {
      $("#section-map-content").removeClass("d-none");
      $("#menu-map").addClass("active");
    }
  }

  $("#menu-profile").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, " ");
    showSection("#profile-menu");
  });

  $("#menu-config").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, "#config");
    showSection("#profile-config");
  });

  $("#menu-tops").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, "#tops");
    showSection("#profile-tops");
  });

  $("#menu-reviews").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, "#reviews");
    showSection("#profile-reviews");
  });

  $("#menu-friends").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, "#friends");
    showSection("#profile-friends");
  });

  $("#menu-trips").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, "#trips");
    showSection("#profile-trips");
    loadTrips();
    loadRanking();
  });

  $("#menu-map").on("click", function (e) {
    e.preventDefault();
    history.replaceState(null, null, "#map");
    showSection("#profile-map");
  });

  // Al cargar la página con hash en la URL
  const sectionMap = {
    "#tops": "#profile-tops",
    "#config": "#profile-config",
    "#reviews": "#profile-reviews",
    "#friends": "#profile-friends",
    "#trips": "#profile-trips",
    "#map": "#profile-map",
  };

  if (sectionMap[window.location.hash]) {
    showSection(sectionMap[window.location.hash]);
  }

  // Fix: interceptar clics en enlaces del header que apunten a secciones del perfil.
  // Funciona aunque el hash ya sea el mismo (el hashchange no se dispararía en ese caso).
  $(document).on("click", 'a[href*="profile.php#"]', function (e) {
    const hash = "#" + this.href.split("#")[1];
    if (sectionMap[hash]) {
      e.preventDefault();
      history.replaceState(null, null, hash);
      showSection(sectionMap[hash]);
    }
  });

  // hashchange como fallback (por si el hash cambia desde otro sitio)
  window.addEventListener("hashchange", function () {
    const target = sectionMap[window.location.hash];
    if (target) showSection(target);
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
              let statusText = coaster.coaster_status || "Operativa";
              if (statusText === "Operating" || statusText === "Operativa")
                statusText = "Operativa";
              else if (
                statusText === "Defunct" ||
                statusText === "Closed" ||
                statusText === "Cerrada"
              )
                statusText = "Cerrada";
              else if (
                statusText === "Construction" ||
                statusText === "En Construcción" ||
                statusText === "En construcción"
              )
                statusText = "En Construcción";
              else statusText = statusText.toUpperCase();

              dropdown.append(
                `<li class="list-group-item list-group-item-action bg-dark text-white border-secondary py-2" style="cursor:pointer;" data-id="${coaster.id}">
                  <div class="fw-bold text-truncate" style="font-size:0.95rem;">${coaster.coaster_name}</div>
                  <div class="text-secondary" style="font-size:0.78rem;">
                    <i class="fa-solid fa-tree-city me-1 opacity-50"></i>${coaster.park_name}
                    <span class="mx-1 opacity-40">•</span>${statusText}
                    <span class="mx-1 opacity-40">•</span>${coaster.park_country || ""}
                  </div>
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

  // Navigación por teclado
  $("#top-coasters-search").on("keydown", function (e) {
    const $dropdown = $("#top-coasters-dropdown");
    if ($dropdown.hasClass("d-none")) return;

    const $items = $dropdown.find("li.list-group-item-action");
    if (!$items.length) return;

    let index = $items.index($items.filter(".active"));

    if (e.key === "ArrowDown") {
      e.preventDefault();
      index = (index + 1) % $items.length;
      $items
        .removeClass("bg-secondary border-success active")
        .addClass("bg-dark");
      $items
        .eq(index)
        .removeClass("bg-dark")
        .addClass("bg-secondary border-success active");
      $items.eq(index)[0].scrollIntoView({ block: "nearest" });
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      index = index - 1 < 0 ? $items.length - 1 : index - 1;
      $items
        .removeClass("bg-secondary border-success active")
        .addClass("bg-dark");
      $items
        .eq(index)
        .removeClass("bg-dark")
        .addClass("bg-secondary border-success active");
      $items.eq(index)[0].scrollIntoView({ block: "nearest" });
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (index >= 0) {
        $items.eq(index).trigger("click");
      } else {
        $items.first().trigger("click");
      }
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
                `<li class="list-group-item list-group-item-action bg-dark text-white border-secondary py-2" style="cursor:pointer;" data-id="${park.park_id}">
                  <div class="fw-bold text-truncate" style="font-size:0.95rem;">${park.park_name}</div>
                  <div class="text-secondary" style="font-size:0.78rem;">
                    <i class="fa-solid fa-location-dot me-1 opacity-50"></i>${park.country_name || ""}
                  </div>
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

  // Navigación por teclado
  $("#top-parks-search").on("keydown", function (e) {
    const $dropdown = $("#top-parks-dropdown");
    if ($dropdown.hasClass("d-none")) return;

    const $items = $dropdown.find("li.list-group-item-action");
    if (!$items.length) return;

    let index = $items.index($items.filter(".active"));

    if (e.key === "ArrowDown") {
      e.preventDefault();
      index = (index + 1) % $items.length;
      $items
        .removeClass("bg-secondary border-success active")
        .addClass("bg-dark");
      $items
        .eq(index)
        .removeClass("bg-dark")
        .addClass("bg-secondary border-success active");
      $items.eq(index)[0].scrollIntoView({ block: "nearest" });
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      index = index - 1 < 0 ? $items.length - 1 : index - 1;
      $items
        .removeClass("bg-secondary border-success active")
        .addClass("bg-dark");
      $items
        .eq(index)
        .removeClass("bg-dark")
        .addClass("bg-secondary border-success active");
      $items.eq(index)[0].scrollIntoView({ block: "nearest" });
    } else if (e.key === "Enter") {
      e.preventDefault();
      if (index >= 0) {
        $items.eq(index).trigger("click");
      } else {
        $items.first().trigger("click");
      }
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

  // === RECUPERAR CONTRASEÑA EN PERFIL ===
  const btnForgotProfile = document.getElementById("forgotPasswordProfileBtn");
  if (btnForgotProfile) {
    btnForgotProfile.addEventListener("click", function (e) {
      e.preventDefault();
      const email =
        document.getElementById("config-user-email").value ||
        window.auth?.currentUser?.email;

      if (!email) {
        if (typeof showAlert === "function")
          showAlert(
            "No se ha podido obtener tu correo electrónico. Cierra sesión y vuelve a entrar.",
          );
        else
          alert(
            "No se ha podido obtener tu correo electrónico. Cierra sesión y vuelve a entrar.",
          );
        return;
      }

      window.auth
        .sendPasswordResetEmail(email)
        .then(() => {
          if (typeof showAlert === "function") {
            showAlert(
              "¡Listo! Hemos enviado un enlace a " +
                email +
                " para que puedas restablecer tu contraseña. Revisa también la carpeta de SPAM.",
            );
          } else {
            alert(
              "¡Listo! Hemos enviado un enlace a " +
                email +
                " para restablecer tu contraseña.",
            );
          }
        })
        .catch((error) => {
          let txt = "Error al restablecer contraseña: ";
          if (error.code === "auth/user-not-found")
            txt =
              "No hay ninguna cuenta vinculada con este correo en nuestro proveedor (Google/Firebase).";
          else txt += error.message;

          if (typeof showAlert === "function") showAlert(txt);
          else alert(txt);
        });
    });
  }

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

  // ══════════════════════════════════════════════════════════════════
  //  MIS RESEÑAS
  // ══════════════════════════════════════════════════════════════════

  let reviewsData = []; // cache de todas las reseñas
  let reviewsLoaded = false; // lazy-load: sólo se carga una vez
  const REVIEWS_PER_PAGE = 6;
  let reviewsPage = 1;

  // ── Helper: genera las estrellas visuales (nota de 0–10 → 0–5 ★) ─
  function starsHtml(note) {
    const n = parseFloat(note) || 0; // la BD guarda 0-5 directamente
    const full = Math.floor(n);
    const half = n - full >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;
    let html = "";
    for (let i = 0; i < full; i++)
      html += '<i class="fa-solid fa-star text-warning"></i>';
    if (half)
      html += '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
    for (let i = 0; i < empty; i++)
      html += '<i class="fa-regular fa-star text-warning"></i>';
    return html;
  }

  // ── Helper: formatea fecha ─────────────────────────────────────────
  function formatReviewDate(dateStr) {
    if (!dateStr) return "";
    const d = new Date(dateStr);
    return d.toLocaleDateString("es-ES", {
      year: "numeric",
      month: "short",
      day: "numeric",
    });
  }

  // ── Aplica sort + tipo y devuelve el subarray de la página actual ──
  function getFilteredReviews() {
    const sort = $("#reviews-sort").val();
    const type = $("#reviews-type-filter").val();

    let data = [...reviewsData];

    // Filtro por tipo
    if (type) data = data.filter((r) => r.type === type);

    // Ordenación
    if (sort === "date_desc")
      data.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    else if (sort === "date_asc")
      data.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    else if (sort === "rating_desc")
      data.sort((a, b) => parseFloat(b.note) - parseFloat(a.note));
    else if (sort === "rating_asc")
      data.sort((a, b) => parseFloat(a.note) - parseFloat(b.note));

    return data;
  }

  // ── Render de reseñas ────────────────────────────────────────────
  function renderReviews() {
    const filtered = getFilteredReviews();
    const $list = $("#reviews-list").empty();
    const $pag = $("#reviews-pagination").empty();

    // Actualizar pastilla de contador (muestra total sin paginar)
    const total = filtered.length;
    if (total > 0) {
      $("#reviews-total-count").text(total);
      $("#reviews-total-pill").show();
    } else {
      $("#reviews-total-pill").hide();
    }

    if (!total) {
      $list.html(`
        <div class="text-center text-muted py-5">
          <i class="fa-solid fa-ghost fs-1 mb-3 opacity-50 d-block"></i>
          <p>Todavía no has dejado ninguna reseña.</p>
        </div>`);
      return;
    }

    // Paginación
    const totalPages = Math.ceil(total / REVIEWS_PER_PAGE);
    if (reviewsPage > totalPages) reviewsPage = totalPages;
    const start = (reviewsPage - 1) * REVIEWS_PER_PAGE;
    const page = filtered.slice(start, start + REVIEWS_PER_PAGE);

    // Renderizar tarjetas
    page.forEach((r) => {
      const isCoaster = r.type === "coaster";
      const detailUrl = isCoaster
        ? `${BASE_URL}/web/views/public/coasters/coasters.php?id=${r.item_id}`
        : `${BASE_URL}/web/views/public/parks/parks.php?id=${r.item_id}`;

      const typeIcon = isCoaster
        ? '<i class="fa-solid fa-ticket me-1 text-success"></i>'
        : '<i class="fa-solid fa-map-location-dot me-1 text-info"></i>';
      const typeLabel = isCoaster ? "Coaster" : "Parque";

      const imgHtml = r.imagen_url
        ? `<img src="${r.imagen_url.startsWith("/") ? BASE_URL + r.imagen_url : r.imagen_url}"
               alt="${r.title}" loading="lazy"
               style="width:100%;height:100%;object-fit:cover;"
               onerror="this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center h-100 text-secondary\'><i class=\'fa-solid fa-image fs-3\'></i></div>'">`
        : `<div class="d-flex align-items-center justify-content-center h-100 text-secondary">
             <i class="fa-solid fa-image fs-3"></i>
           </div>`;

      const nota = parseFloat(r.note) || 0;
      const notaText = nota % 1 === 0 ? nota.toFixed(0) : nota.toFixed(1);
      let tagsHtml = "";
      if (r.tags && r.tags.length > 0) {
        tagsHtml = '<div class="d-flex flex-wrap gap-1 mt-2 mb-2">';
        r.tags.forEach((t) => {
          const bgColor = t.type === "pro" ? "#05c46b" : "#ff3f34";
          tagsHtml += `<span class="badge text-white rounded-pill px-2 py-1" style="background-color:${bgColor}; font-weight:600; font-size:0.65rem;">${t.tag.replace(/_/g, " ").toUpperCase()}</span>`;
        });
        tagsHtml += "</div>";
      }

      const reviewText = r.review
        ? `<p class="mb-0 text-secondary small" style="line-height:1.6;display:-webkit-box;-webkit-line-clamp:5;-webkit-box-orient:vertical;overflow:hidden;">${r.review}</p>`
        : `<p class="mb-0 text-muted small fst-italic">Sin texto de reseña.</p>`;

      $list.append(`
        <div class="top-card d-flex flex-row align-items-stretch mb-3" style="min-height:130px;" data-review-id="${r.id}" data-review-type="${r.type}" data-item-id="${r.item_id}">
          <!-- Miniatura -->
          <div class="flex-shrink-0 bg-dark overflow-hidden d-none d-sm-block" style="width:130px;">
            ${imgHtml}
          </div>
          <div class="flex-shrink-0 bg-dark overflow-hidden d-block d-sm-none" style="width:90px;">
            ${imgHtml}
          </div>
          
          <!-- Contenido -->
          <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between min-w-0">
            <div>
              <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                <a href="${detailUrl}" class="fw-bold text-white text-decoration-none text-truncate-2"
                   style="font-size:0.95rem; line-height: 1.2; flex:1; min-width:0;">${r.title}</a>
                <span class="badge rounded-0 fw-bold px-2 py-1 flex-shrink-0"
                      style="background:rgba(255,255,255,0.07);color:#aaa;font-size:0.6rem;white-space:nowrap; height: fit-content;">
                  ${typeLabel}
                </span>
              </div>
              <small class="text-secondary d-block mb-2 text-truncate" style="font-size: 0.75rem;">${r.subtitle || ""}</small>
              ${tagsHtml}
              ${reviewText}
            </div>
            
            <!-- Footer: Estrellas y Botón -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top border-secondary border-opacity-10 gap-2">
              <div class="d-flex align-items-center gap-2">
                <div class="stars-container d-flex gap-1" style="font-size: 0.75rem;">${starsHtml(r.note)}</div>
                <span class="fw-bold text-warning" style="font-size:0.8rem;">${notaText}</span>
              </div>
              
              <div class="d-flex align-items-center gap-2 ms-auto">
                <small class="text-muted d-none d-md-inline" style="font-size: 0.7rem;">${formatReviewDate(r.created_at)}</small>
                <button class="btn btn-link p-0 text-warning profile-edit-review-btn d-flex align-items-center gap-1"
                        data-id="${r.id}" data-type="${r.type}"
                        data-note="${nota}" data-text="${encodeURIComponent(r.review || "")}"
                        data-tags='${JSON.stringify(r.tags || [])}'
                        title="Editar reseña" style="text-decoration:none;">
                  <i class="fa-solid fa-pen-to-square"></i>
                  <span class="fw-bold text-uppercase" style="font-size:0.65rem;">Editar</span>
                </button>
              </div>
            </div>
          </div>
        </div>`);
    });

    // Controles de paginación
    if (totalPages > 1) {
      if (reviewsPage > 1) {
        $pag.append(`<button class="btn btn-sm btn-outline-success rounded-0 px-3" id="rev-prev">
          <i class="fa-solid fa-chevron-left me-1"></i>Anterior
        </button>`);
      }
      $pag.append(
        `<span class="tops-counter-pill">${reviewsPage} / ${totalPages}</span>`,
      );
      if (reviewsPage < totalPages) {
        $pag.append(`<button class="btn btn-sm btn-outline-success rounded-0 px-3" id="rev-next">
          Siguiente<i class="fa-solid fa-chevron-right ms-1"></i>
        </button>`);
      }

      $("#rev-prev").on("click", () => {
        reviewsPage--;
        renderReviews();
      });
      $("#rev-next").on("click", () => {
        reviewsPage++;
        renderReviews();
      });
    }
  }

  // ── Carga inicial (lazy) ──────────────────────────────────────────
  async function cargarReviews() {
    if (reviewsLoaded) {
      renderReviews();
      return;
    }

    const $list = $("#reviews-list");
    $list.html(`<div class="text-center text-muted py-5">
      <i class="fa-solid fa-spinner fa-spin fs-3 mb-3 d-block"></i>Cargando reseñas…
    </div>`);
    $("#reviews-pagination").empty();

    try {
      const res = await fetch(
        `${BASE_URL}/api/php/profile_config.php?action=get_my_reviews`,
      );
      const data = await res.json();
      reviewsData = data.success && data.reviews ? data.reviews : [];
      reviewsLoaded = true;
      reviewsPage = 1;

      // Enlazar filtros AQUÍ, cuando los datos ya están listos
      $("#reviews-sort, #reviews-type-filter")
        .off("change.reviews")
        .on("change.reviews", function () {
          reviewsPage = 1;
          renderReviews();
        });

      renderReviews();
    } catch (e) {
      console.error("Error cargando reseñas:", e);
      $list.html(`<div class="text-center text-danger py-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i>Error al cargar las reseñas.
      </div>`);
    }
  }

  // ── Activar cuando se hace click en el menú de reseñas ───────────
  $("#menu-reviews").on("click", cargarReviews);

  // Si la URL ya está en #reviews al cargar la página, cargamos también
  if (window.location.hash === "#reviews") {
    cargarReviews();
  }

  // ── Modal de edición de reseñas desde el perfil ──────────────────────────
  let profileEditModal = null;

  // Crear el modal de edición dinámicamente si no existe
  if (!document.getElementById("profile-edit-review-modal")) {
    const modalHtml = `
    <div class="modal fade" id="profile-edit-review-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary text-white">
          <div class="modal-header bg-success">
            <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Editar reseña</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="pedit-review-id">
            <input type="hidden" id="pedit-review-type">
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold">Puntuación</label>
              <div class="star-rating pedit-star-rating-container" style="font-size: 2rem;">
                ${[10, 9, 8, 7, 6, 5, 4, 3, 2, 1]
                  .map((i) => {
                    const val = i / 2;
                    const half = i % 2 !== 0;
                    return `<input type="radio" name="pedit_note" id="pstar${i}" value="${val}">
                          <label for="pstar${i}" class="${half ? "half" : "full"}" title="${val}"></label>`;
                  })
                  .join("")}
              </div>
              <input type="hidden" id="pedit-review-note" value="0">
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small fw-semibold">Reseña (opcional)</label>
              <textarea class="form-control bg-dark text-white border-secondary rounded-0" id="pedit-review-text" rows="4" placeholder="Escribe tu opinión..."></textarea>
            </div>
            <!-- Pros -->
            <div class="mb-3 wrapper-pros">
                <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-plus-circle text-success me-1"></i> Ventajas</label>
                <select id="pedit-pros-select" multiple></select>
            </div>
            <!-- Contras -->
            <div class="mb-3 wrapper-contras">
                <label class="form-label text-muted small fw-semibold"><i class="fa-solid fa-minus-circle text-danger me-1"></i> Contras</label>
                <select id="pedit-contras-select" multiple></select>
            </div>
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-outline-secondary rounded-0" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success rounded-0 fw-bold px-4" id="pedit-save-btn">
              <i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios
            </button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.insertAdjacentHTML("beforeend", modalHtml);
  }

  profileEditModal = new bootstrap.Modal(
    document.getElementById("profile-edit-review-modal"),
  );

  let peditProsChoices = null;
  let peditContrasChoices = null;

  function initPeditChoices() {
    if (typeof Choices === "undefined") {
      console.error("Choices.js not loaded in profile.js!");
      return;
    }
    if (!peditProsChoices && document.getElementById("pedit-pros-select")) {
      peditProsChoices = new Choices("#pedit-pros-select", {
        removeItemButton: true,
        placeholderValue: "Selecciona las ventajas...",
      });
    }
    if (
      !peditContrasChoices &&
      document.getElementById("pedit-contras-select")
    ) {
      peditContrasChoices = new Choices("#pedit-contras-select", {
        removeItemButton: true,
        placeholderValue: "Selecciona las contras...",
      });
    }
  }

  const COASTER_PROS = [
    { value: "airtime", label: "Airtime" },
    { value: "arnes", label: "Arnés" },
    { value: "capacidad", label: "Capacidad" },
    { value: "comodidad", label: "Comodidad" },
    { value: "duracion", label: "Duración" },
    { value: "hangtime", label: "Hangtime" },
    { value: "intensidad", label: "Intensidad" },
    { value: "inversiones", label: "Inversiones" },
    { value: "launch", label: "Launch" },
    { value: "caidas", label: "Caídas" },
    { value: "suavidad", label: "Suavidad" },
    { value: "recorrido", label: "Layout" },
    { value: "tematizacion", label: "Tematización" },
    { value: "velocidad", label: "Velocidad" },
  ];
  const COASTER_CONTRAS = [
    { value: "airtime", label: "Airtime" },
    { value: "arnes", label: "Arnés" },
    { value: "capacidad", label: "Capacidad" },
    { value: "comodidad", label: "Comodidad" },
    { value: "mantenimiento", label: "Mantenimiento" },
    { value: "duracion_corta", label: "Corta duración" },
    { value: "intensidad", label: "Intensidad" },
    { value: "inversiones", label: "Inversiones" },
    { value: "launch", label: "Launch" },
    { value: "recorrido", label: "Layout" },
    { value: "vibracion", label: "Vibración" },
    { value: "dolorosa", label: "Dolorosa" },
    { value: "decepcionante", label: "Decepcionante" },
    { value: "tematizacion", label: "Tematización" },
    { value: "velocidad_nula", label: "Poca velocidad" },
  ];
  const PARK_PROS = [
    { value: "limpieza", label: "Limpieza" },
    { value: "personal", label: "Personal / atención" },
    { value: "comida", label: "Comida y restaurantes" },
    { value: "tematizacion", label: "Tematización / ambiente" },
    { value: "precio", label: "Relación calidad-precio" },
    { value: "colas", label: "Gestión de colas" },
    { value: "atracciones", label: "Variedad de atracciones" },
    { value: "mantenimiento", label: "Mantenimiento de instalaciones" },
    { value: "accesibilidad", label: "Accesibilidad (discapacitados)" },
    { value: "entretenimiento", label: "Shows y entretenimiento" },
    { value: "tiendas", label: "Tiendas y merchandising" },
  ];
  const PARK_CONTRAS = [
    { value: "suciedad", label: "Suciedad" },
    { value: "personal", label: "Mal personal / atención" },
    { value: "comida", label: "Mala comida / precios abusivos" },
    { value: "tematizacion", label: "Poca tematización" },
    { value: "precio", label: "Mala relación calidad-precio" },
    { value: "colas", label: "Largas colas / mala gestión" },
    { value: "pocas_atracciones", label: "Pocas atracciones" },
    { value: "mantenimiento", label: "Mal mantenimiento" },
    { value: "accesibilidad", label: "Poca accesibilidad" },
    { value: "entretenimiento", label: "Falta de entretenimiento" },
    { value: "masificacion", label: "Masificación" },
  ];

  // Actualizar nota oculta cuando cambia el radio (perfil)
  $(document).on("change", 'input[name="pedit_note"]', function () {
    $("#pedit-review-note").val($(this).val());
  });

  // Abrir modal desde tarjeta de reseña del perfil
  $(document).on("click", ".profile-edit-review-btn", function () {
    const id = $(this).data("id");
    const type = $(this).data("type"); // 'coaster' | 'park'
    const note = parseFloat($(this).data("note")) || 0;
    const text = decodeURIComponent($(this).data("text") || "");
    $("#pedit-review-id").val(id);
    $("#pedit-review-type").val(type);
    $("#pedit-review-note").val(note);
    $("#pedit-review-text").val(text);
    // Marcar el radio correspondiente
    $(`input[name="pedit_note"][value="${note}"]`).prop("checked", true);

    // Cargar tags
    initPeditChoices();
    const prosList = type === "coaster" ? COASTER_PROS : PARK_PROS;
    const contrasList = type === "coaster" ? COASTER_CONTRAS : PARK_CONTRAS;

    const rawTags = $(this).attr("data-tags") || "[]";
    let tags = [];
    try {
      tags = JSON.parse(rawTags);
    } catch (e) {
      tags = [];
    }

    const activePros = tags.filter((t) => t.type === "pro").map((t) => t.tag);
    const activeContras = tags
      .filter((t) => t.type === "con")
      .map((t) => t.tag);

    if (peditProsChoices) {
      peditProsChoices.clearChoices();
      const mappedPros = prosList.map((p) => ({
        ...p,
        selected: activePros.includes(p.value),
      }));
      peditProsChoices.setChoices(mappedPros, "value", "label", true);
    }
    if (peditContrasChoices) {
      peditContrasChoices.clearChoices();
      const mappedContras = contrasList.map((c) => ({
        ...c,
        selected: activeContras.includes(c.value),
      }));
      peditContrasChoices.setChoices(mappedContras, "value", "label", true);
    }

    profileEditModal.show();
  });

  // Guardar cambios desde el perfil
  $(document).on("click", "#pedit-save-btn", async function () {
    const btn = $(this);
    const reviewId = $("#pedit-review-id").val();
    const type = $("#pedit-review-type").val();
    const note = parseFloat($("#pedit-review-note").val()) || 0;
    const text = $("#pedit-review-text").val().trim();
    if (!note) {
      alert("Por favor, selecciona una puntuación.");
      return;
    }
    btn
      .prop("disabled", true)
      .html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Guardando...');
    const fd = new FormData();
    fd.append("review_id", reviewId);
    fd.append("note", note);
    fd.append("review", text);

    // Añadir tags
    const pros = peditProsChoices.getValue(true);
    const contras = peditContrasChoices.getValue(true);
    pros.forEach((p) => fd.append("pros[]", p));
    contras.forEach((c) => fd.append("contras[]", c));
    const endpoint =
      type === "coaster"
        ? `${BASE_URL}/api/php/coasters.php?action=update_review`
        : `${BASE_URL}/api/php/parks.php?action=update_review`;
    try {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: {
          "X-CSRF-Token":
            document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute("content") ?? "",
        },
        body: fd,
      });
      const data = await res.json();
      if (data.success) {
        profileEditModal.hide();
        // Actualizar localmente sin recargar toda la lista
        const idx = reviewsData.findIndex(
          (r) => String(r.id) === String(reviewId),
        );
        if (idx !== -1) {
          reviewsData[idx].note = note;
          reviewsData[idx].review = text;
          // Actualizar tags localmente
          reviewsData[idx].tags = [
            ...pros.map((p) => ({ type: "pro", tag: p })),
            ...contras.map((c) => ({ type: "con", tag: c })),
          ];
        }
        renderReviews();
      } else {
        alert("Error: " + (data.error || "No se pudo guardar."));
      }
    } catch (e) {
      alert("Error de conexión.");
    } finally {
      btn
        .prop("disabled", false)
        .html('<i class="fa-solid fa-floppy-disk me-1"></i>Guardar cambios');
    }
  });
  // ══════════════════════════════════════════════════════════════════
  //  MIS AMIGOS
  // ══════════════════════════════════════════════════════════════════

  let friendsData = [];
  let friendsLoaded = false;

  function renderFriends(data) {
    const source = data || friendsData;
    const $list = $("#friends-list").empty();
    const total = friendsData.length;

    if (total > 0) {
      $("#friends-total-count").text(total);
      $("#friends-total-pill").show();
    } else {
      $("#friends-total-pill").hide();
    }

    if (!source.length) {
      $list.html(
        '<div class="text-center text-muted py-5">' +
          '<i class="fa-solid fa-ghost fs-1 mb-3 opacity-50 d-block"></i>' +
          "<p>Todavía no tienes amigos agregados.</p>" +
          "</div>",
      );
      return;
    }

    source.forEach(function (f) {
      var imgSrc = f.profile_image
        ? f.profile_image.startsWith("http")
          ? f.profile_image
          : f.profile_image.startsWith("/")
            ? BASE_URL + f.profile_image
            : BASE_URL + "/" + f.profile_image
        : null;

      var imgHtml = imgSrc
        ? '<img src="' +
          imgSrc +
          '" alt="' +
          f.username +
          '" loading="lazy" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">'
        : '<div class="d-flex align-items-center justify-content-center h-100 text-secondary bg-dark"><i class="fa-solid fa-user fs-3"></i></div>';

      var ubicacion =
        f.city || f.country
          ? '<small class="text-secondary d-block mb-2"><i class="fa-solid fa-location-dot me-1"></i>' +
            [f.city, f.country].filter(Boolean).join(", ") +
            "</small>"
          : '<small class="text-muted d-block mb-2"><i class="fa-solid fa-location-crosshairs me-1"></i>Ubicaci\u00f3n desconocida</small>';

      var creditsText = f.credits || 0;
      var topCoaster = f.favorite_coaster || "Ninguna";

      var friendSinceHtml = "";
      if (f.since) {
        var d = new Date(f.since);
        var mes = new Intl.DateTimeFormat("es-ES", { month: "short" }).format(
          d,
        );
        friendSinceHtml =
          '<span class="badge rounded-0" style="background:rgba(255,255,255,0.07);color:#aaa;font-size:0.7rem;">' +
          '<i class="fa-solid fa-handshake text-success me-1"></i>Desde ' +
          mes +
          ". " +
          d.getFullYear() +
          "</span>";
      }

      $list.append(
        '<div class="top-card d-flex align-items-center mb-3 p-3">' +
          '<div style="width:70px;height:70px;border-radius:50%;overflow:hidden;flex-shrink:0;border:2px solid rgba(16,185,129,0.3);" class="me-3">' +
          imgHtml +
          "</div>" +
          '<div class="flex-grow-1" style="min-width:0;">' +
          '<a href="' +
          BASE_URL +
          "/web/views/public/users/user_profile.php?id=" +
          f.id +
          '" class="fw-bold text-white text-decoration-none fs-6 d-block text-truncate">' +
          f.username +
          "</a>" +
          ubicacion +
          '<div class="d-flex gap-2 mt-1 flex-wrap">' +
          '<span class="badge rounded-0" style="background:rgba(255,255,255,0.07);color:#aaa;font-size:0.7rem;"><i class="fa-solid fa-ticket text-success me-1"></i>' +
          creditsText +
          " credits</span>" +
          '<span class="badge rounded-0" style="background:rgba(255,255,255,0.07);color:#aaa;font-size:0.7rem;"><i class="fa-solid fa-heart text-danger me-1"></i>Top 1: ' +
          topCoaster +
          "</span>" +
          friendSinceHtml +
          "</div>" +
          "</div>" +
          '<div class="ms-3 flex-shrink-0">' +
          '<a href="' +
          BASE_URL +
          "/web/views/public/users/user_profile.php?id=" +
          f.id +
          '" class="btn btn-sm btn-outline-success rounded-0 px-3">Ver perfil</a>' +
          "</div>" +
          "</div>",
      );
    });
  }

  function applyFriendsFilterSort() {
    var query = $("#friends-search").val().toLowerCase().trim();
    var sortVal = $("#friends-sort").val();

    var filtered = friendsData.filter(function (f) {
      return f.username.toLowerCase().indexOf(query) !== -1;
    });

    filtered.sort(function (a, b) {
      if (sortVal === "date_desc")
        return (
          new Date(b.since || b.created_at || 0) -
          new Date(a.since || a.created_at || 0)
        );
      if (sortVal === "date_asc")
        return (
          new Date(a.since || a.created_at || 0) -
          new Date(b.since || b.created_at || 0)
        );
      if (sortVal === "credits_desc")
        return (b.credits || 0) - (a.credits || 0);
      if (sortVal === "name_asc")
        return a.username.localeCompare(b.username, "es");
      return 0;
    });

    if (!filtered.length) {
      $("#friends-list").html(
        '<div class="text-center text-muted py-4"><i class="fa-solid fa-search me-2"></i>No se encontraron amigos con ese nombre.</div>',
      );
      return;
    }
    renderFriends(filtered);
  }

  async function cargarAmigos() {
    if (friendsLoaded) {
      applyFriendsFilterSort();
      return;
    }

    $("#friends-list").html(
      '<div class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin fs-3 mb-3 d-block"></i>Cargando amigos\u2026</div>',
    );

    try {
      var res = await fetch(
        BASE_URL + "/api/php/users.php?action=get_friends_data",
      );
      var dataJson = await res.json();

      friendsData =
        dataJson.success && dataJson.data && dataJson.data.friends
          ? dataJson.data.friends
          : [];
      friendsLoaded = true;

      applyFriendsFilterSort();

      // Registrar eventos solo una vez tras la primera carga
      $("#friends-search").on("input", applyFriendsFilterSort);
      $("#friends-sort").on("change", applyFriendsFilterSort);
    } catch (e) {
      console.error("Error cargando amigos:", e);
      $("#friends-list").html(
        '<div class="text-center text-danger py-4"><i class="fa-solid fa-circle-exclamation me-2"></i>Error al cargar los amigos.</div>',
      );
    }
  }

  // Activar cuando se hace click en el men\u00fa de amigos
  $("#menu-friends").on("click", cargarAmigos);

  // Si la URL ya est\u00e1 en #friends al cargar la p\u00e1gina
  if (window.location.hash === "#friends") {
    cargarAmigos();
  }

  // ─── VIAJES ──────────────────────────────────────────────────
  const esc = (s) =>
    String(s ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");

  async function loadTrips() {
    const container = $("#trips-grid");
    container.html(
      '<div class="d-flex align-items-center justify-content-center py-4 text-muted small"><div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>Cargando viajes...</div>',
    );
    try {
      const res = await fetch(`${BASE_URL}/api/php/trips.php?action=list`);
      const j = await res.json();
      const d = j.data || [];
      if (!d.length) {
        container.html(
          '<div class="text-center py-4 text-muted"><i class="fa-solid fa-suitcase fa-2x mb-2 opacity-50"></i><br>Aún no tienes viajes registrados.</div>',
        );
        return;
      }
      let html = "";
      d.forEach((t) => {
        const start = new Date(t.start_date);
        start.setHours(0, 0, 0, 0);
        const end = new Date(t.end_date);
        end.setHours(23, 59, 59, 999);
        const diff = Math.ceil((end - start) / 86400000);

        const today = new Date();
        let t_status = "upcoming";
        if (today > end) t_status = "past";
        else if (today >= start && today <= end) t_status = "active";

        const statusClass =
          t_status === "past"
            ? "bg-secondary"
            : t_status === "active"
              ? "bg-success"
              : "bg-warning text-dark";
        const statusText =
          t_status === "past"
            ? "Pasado"
            : t_status === "active"
              ? "Activo"
              : "Próximo";
        let imgUrl = window.BASE_URL + "/dummy.jpg";
        if (t.cover_image) {
          imgUrl = t.cover_image.startsWith("http")
            ? t.cover_image
            : window.BASE_URL + t.cover_image;
        }
        const pNames = t.park_names ? t.park_names : "Sin parques planificados";
        const startStr = start.toLocaleDateString("es-ES", {
          day: "numeric",
          month: "short",
        });
        const endStr = end.toLocaleDateString("es-ES", {
          day: "numeric",
          month: "short",
          year: "numeric",
        });

        html += `
          <div class="col-12">
          <div class="card shadow-sm h-100 border-0" style="background:#111; border-radius: 0; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s;" onclick="openTrip(${t.id})" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 10px 20px rgba(0,0,0,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='var(--bs-shadow-sm)'">
            <div style="height: 130px; position: relative; overflow: hidden; border-radius: 0;">
               ${t.cover_image ? `<img src="${imgUrl}" referrerpolicy="no-referrer" onerror="this.style.opacity='0'" class="w-100 h-100" style="object-fit: cover; transition: transform 0.5s ease, opacity 0.3s ease; z-index: 0; position:absolute;">` : ""}
               <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(to bottom, transparent 10%, rgba(10,12,16,0.95)); z-index: 1;"></div>
               <div class="position-absolute bottom-0 start-0 w-100 p-3 pb-2 text-white" style="z-index: 2;">
                 <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge ${statusClass}" style="font-size:0.6rem; letter-spacing:0.05em;">${statusText}</span>
                    <span class="small opacity-75" style="font-size:0.75rem;"><i class="fa-regular fa-clock me-1"></i>${diff} d</span>
                 </div>
                 <h5 class="fw-bold mb-1 text-truncate" style="font-family: var(--rcw-font-title); font-size:1.15rem; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">${esc(t.title)}</h5>
               </div>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-start">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <span class="badge bg-dark border border-secondary text-light fw-normal"><i class="fa-regular fa-calendar text-success me-1"></i>${startStr} — ${endStr}</span>
                <div class="small text-truncate" style="color: #a3aed0; font-size: 0.8rem;"><i class="fa-solid fa-map-location-dot me-1"></i>${esc(pNames)}</div>
              </div>
              ${t.description ? `<div class="small text-muted mt-1" style="display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height: 1.4;">${esc(t.description)}</div>` : ""}
            </div>
          </div>
          </div>
        `;
      });
      container.html(html);
    } catch (e) {
      console.error("loadTrips error:", e);
      container.html(
        '<div class="text-center py-4 text-danger">Error cargando viajes.</div>',
      );
    }
  }

  // ─── RANKING ─────────────────────────────────────────────────
  async function loadRanking() {
    const sType = document.getElementById("rank-type-select");
    const container = document.getElementById("ranking-container");
    const pBtns = document.querySelectorAll(".rank-period-btn");
    const sDate = document.getElementById("rank-start-date");
    const eDate = document.getElementById("rank-end-date");
    const prevBtn = document.getElementById("rank-prev-btn");
    const nextBtn = document.getElementById("rank-next-btn");
    const navLabel = document.getElementById("rank-nav-label");
    let currentPeriod = "year";
    let baseDate = new Date();
    let customStart = "";
    let customEnd = "";
    let cachedData = null;

    function updateLabel() {
      const navContainer = document.getElementById("rank-nav-container");
      if (currentPeriod === "all" || currentPeriod === "custom") {
        if (navContainer) navContainer.classList.add("d-none");
        navLabel.textContent = "Siempre";
      } else {
        if (navContainer) navContainer.classList.remove("d-none");
        if (currentPeriod === "year")
          navLabel.textContent = baseDate.getFullYear();
        else if (currentPeriod === "month") {
          let s = baseDate.toLocaleString("es-ES", {
            month: "long",
            year: "numeric",
          });
          navLabel.textContent = s.charAt(0).toUpperCase() + s.slice(1);
        } else if (currentPeriod === "week") {
          const wStart = new Date(baseDate);
          wStart.setDate(wStart.getDate() - wStart.getDay() + 1);
          navLabel.textContent =
            "Semana " +
            wStart.toLocaleDateString("es-ES", {
              day: "numeric",
              month: "short",
            });
        }
      }

      if (currentPeriod !== "custom" && currentPeriod !== "all") {
        const d = getDates();
        if (sDate) {
          sDate.value = d.start || "";
          if (eDate) eDate.min = d.start || "";
        }
        if (eDate) eDate.value = d.end || "";
      } else if (currentPeriod === "all") {
        if (sDate) sDate.value = "";
        if (eDate) {
          eDate.value = "";
          eDate.min = "";
        }
      } else if (currentPeriod === "custom") {
        // Si entramos en custom y están vacíos, ponemos el año actual como base
        if (sDate && !sDate.value) {
          const fmt = (date) =>
            date.getFullYear() +
            "-" +
            String(date.getMonth() + 1).padStart(2, "0") +
            "-" +
            String(date.getDate()).padStart(2, "0");
          const start = fmt(new Date(baseDate.getFullYear(), 0, 1));
          const end = fmt(new Date(baseDate.getFullYear(), 11, 31));

          sDate.value = start;
          eDate.value = end;
          eDate.min = start;
          customStart = start;
          customEnd = end;
        }
      }
    }

    function getDates() {
      let start, end;
      const fmt = (d) =>
        d.getFullYear() +
        "-" +
        String(d.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(d.getDate()).padStart(2, "0");

      if (currentPeriod === "week") {
        const d = new Date(baseDate);
        const day = d.getDay() || 7;
        d.setDate(d.getDate() - day + 1);
        start = fmt(d);
        const e = new Date(d);
        e.setDate(e.getDate() + 6);
        end = fmt(e);
      } else if (currentPeriod === "month") {
        start = fmt(new Date(baseDate.getFullYear(), baseDate.getMonth(), 1));
        end = fmt(new Date(baseDate.getFullYear(), baseDate.getMonth() + 1, 0));
      } else if (currentPeriod === "year") {
        start = fmt(new Date(baseDate.getFullYear(), 0, 1));
        end = fmt(new Date(baseDate.getFullYear(), 11, 31));
      } else if (currentPeriod === "custom") {
        start = customStart;
        end = customEnd;
      }
      return { start, end };
    }

    async function fetchRanking() {
      const { start, end } = getDates();
      let url = `${BASE_URL}/api/php/trips.php?action=${sType.value === "coasters" ? "ride_ranking" : "park_ranking"}`;
      if (start) url += `&start=${start}`;
      if (end) url += `&end=${end}`;

      container.innerHTML =
        '<div class="text-center py-4 text-muted small"><div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>Cargando ranking...</div>';
      try {
        const res = await fetch(url);
        const j = await res.json();
        cachedData = j.data || [];
        const tc = document.getElementById("rank-trip-count");
        if (tc && j.total_trips !== undefined)
          tc.textContent =
            j.total_trips + (j.total_trips === 1 ? " viaje" : " viajes");
        renderRanking();
      } catch (e) {
        container.innerHTML =
          '<div class="text-center py-4 text-danger">Error cargando ranking.</div>';
      }
    }

    function renderRanking() {
      if (!cachedData || !cachedData.length) {
        container.innerHTML =
          '<div class="text-center py-5 text-muted"><i class="fa-solid fa-chart-line fa-2x mb-3 opacity-50"></i><br>No hay datos en este periodo.</div>';
        return;
      }

      const max = Math.max(
        ...cachedData.map((i) =>
          parseInt(
            sType.value === "coasters" ? i.times_ridden : i.times_visited,
          ),
        ),
      );
      let html =
        '<div class="list-group list-group-flush custom-scrollbar" style="max-height: 700px; overflow-y: auto; overflow-x: hidden;">';

      cachedData.forEach((item, idx) => {
        const isC = sType.value === "coasters";
        const title = isC ? item.coaster_name : item.park_name;
        const sub = isC ? item.park_name : item.park_location;
        const count = parseInt(isC ? item.times_ridden : item.times_visited);
        const img = item.imagen_url || window.BASE_URL + "/web/img/dummy.jpg";
        const pct = (count / max) * 100;

        html += `
          <div class="list-group-item bg-transparent border-bottom border-secondary border-opacity-25 px-2 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="fw-bold text-success fs-5" style="min-width:40px;">#${idx + 1}</div>
              <img src="${img}" onerror="this.src='${window.BASE_URL}/web/img/dummy.jpg'" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid var(--rcw-border);">
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between align-items-end mb-1 gap-2">
                  <div class="flex-grow-1 min-w-0">
                    <h6 class="fw-bold text-white mb-0 text-truncate" title="${title}">${title}</h6>
                    <small class="text-muted text-truncate d-block" title="${sub}">${sub}</small>
                  </div>
                  <div class="fw-bold text-success fs-5 flex-shrink-0 text-end" style="min-width: 40px;">
                    x${count}
                  </div>
                </div>
                <div class="progress rounded-pill bg-dark mt-2" style="height: 6px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: ${pct}%"></div>
                </div>
              </div>
            </div>
          </div>
        `;
      });
      html += "</div>";
      container.innerHTML = html;
    }

    sType.addEventListener("change", fetchRanking);

    pBtns.forEach((b) => {
      b.addEventListener("click", (e) => {
        pBtns.forEach((btn) => {
          btn.classList.remove("btn-outline-success", "active");
          btn.classList.add("btn-outline-secondary");
        });
        e.target.classList.remove("btn-outline-secondary");
        e.target.classList.add("btn-outline-success", "active");
        currentPeriod = e.target.dataset.period;
        baseDate = new Date();
        updateLabel();
        if (currentPeriod !== "custom") fetchRanking();
      });
    });

    function handleArrowClick(dir) {
      if (currentPeriod === "all" || currentPeriod === "custom") {
        currentPeriod = "year";
        baseDate = new Date();
        document.querySelectorAll(".rank-period-btn").forEach((btn) => {
          btn.classList.remove("btn-outline-success", "active");
          btn.classList.add("btn-outline-secondary");
          if (btn.dataset.period === "year") {
            btn.classList.remove("btn-outline-secondary");
            btn.classList.add("btn-outline-success", "active");
          }
        });
      }

      if (currentPeriod === "year")
        baseDate.setFullYear(baseDate.getFullYear() + dir);
      else if (currentPeriod === "month")
        baseDate.setMonth(baseDate.getMonth() + dir);
      else if (currentPeriod === "week")
        baseDate.setDate(baseDate.getDate() + dir * 7);
      updateLabel();
      fetchRanking();
    }

    if (prevBtn) prevBtn.addEventListener("click", () => handleArrowClick(-1));
    if (nextBtn) nextBtn.addEventListener("click", () => handleArrowClick(1));

    sDate.addEventListener("change", (e) => {
      customStart = e.target.value;
      if (eDate) {
        eDate.min = customStart;
        if (eDate.value && eDate.value < customStart) {
          eDate.value = customStart;
          customEnd = customStart;
        }
      }
      if (currentPeriod === "custom") fetchRanking();
    });
    eDate.addEventListener("change", (e) => {
      customEnd = e.target.value;
      if (sDate && customEnd && customEnd < sDate.value) {
        eDate.value = sDate.value;
        customEnd = sDate.value;
      }
      if (currentPeriod === "custom") fetchRanking();
    });

    updateLabel();
    fetchRanking();
  }
});
