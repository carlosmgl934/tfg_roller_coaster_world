// forums.js — Lógica del foro (sin Choices.js)

$(document).ready(function () {
  // ── Visibilidad sección colaboradores ────────────────────────────
  const radios = document.querySelectorAll('input[name="privacy"]');
  const collabsSection = document.getElementById("collaborators-section");
  const hintText = document.getElementById("privacy-hint-text");

  radios.forEach((radio) => {
    radio.addEventListener("change", function () {
      if (this.value === "private") {
        hintText.textContent =
          "Solo los colaboradores que designes pueden escribir, pero cualquiera puede leer el foro";
      } else {
        hintText.textContent =
          "Cualquier usuario puede ver y escribir en el foro";
      }
    });
  });

  // ── Validación en tiempo real (Blur) ─────────────────────────────
  const titleInput = document.getElementById("title");

  if (titleInput) {
    titleInput.addEventListener("blur", function () {
      const val = this.value.trim();
      if (val.length > 0 && val.length < 5) {
        this.classList.add("is-invalid");
      }
    });

    titleInput.addEventListener("input", function () {
      if (
        this.classList.contains("is-invalid") &&
        this.value.trim().length >= 5
      ) {
        this.classList.remove("is-invalid");
      }
    });
  }

  // ── Submit del formulario ────────────────────────────────────────
  const submitBtn = document.getElementById("forum-submit-btn");
  if (submitBtn) {
    submitBtn.addEventListener("click", function () {
      const isLogged =
        document
          .getElementById("forum-main-container")
          .getAttribute("data-logged") === "true";
      if (!isLogged) {
        new bootstrap.Modal(document.getElementById("loginModal")).show();
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
      titleInput.classList.remove("is-invalid");
      subjectInput.classList.remove("is-invalid");

      let hasError = false;
      const title = titleInput.value.trim();
      const subject = subjectInput.value.trim();

      if (!title || title.length < 5) {
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
          title.length > 0 && title.length < 5
            ? "El título debe tener al menos 5 caracteres"
            : "Por favor, completa todos los campos marcados en rojo";
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
        headers: { 
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' 
        },
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            msgDiv.style.display = "block";
            msgText.textContent = "Foro creado exitosamente. Redirigiendo...";
            msgText.style.color = "green";
            form.reset();
            // Limpiar tags del picker
            document.querySelectorAll(".friend-tag").forEach((t) => t.remove());
            document
              .querySelectorAll("#collaborators-hidden input")
              .forEach((i) => i.remove());
            selectedFriends = {};
            closeDropdown();

            // Redirigir al nuevo foro pasados 2 segundos
            setTimeout(() => {
              window.location.href = `${window.BASE_URL}/web/views/public/forums/forums.php?id=${data.forum_id}`;
            }, 2000);
          } else {
            msgDiv.style.display = "block";
            msgText.textContent = data.error;
            msgText.style.color = "red";
            btn.disabled = false;
            btn.textContent = "+ Crear foro";
          }
        })
        .catch((e) => {
          console.warn("Error creating forum:", e);
          msgDiv.style.display = "block";
          msgText.textContent = "Error al crear el foro";
          msgText.style.color = "red";
          btn.disabled = false;
          btn.textContent = "+ Crear foro";
        });
    });
  }

  // ── FRIEND PICKER (custom) ───────────────────────────────────────
  let allFriends = [];
  let selectedFriends = {};
  let friendsLoaded = false;

  function getAvatarUrl(img, username) {
    const fallback = window.BASE_URL + '/web/img/avatars/default_avatar.svg';
    if (!img) return fallback;
    if (img.startsWith("http://") || img.startsWith("https://")) return img;
    return `https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/${img}`;
  }

  function renderPickerDropdown(query) {
    const list = document.getElementById("friend-list");
    const empty = document.getElementById("friend-empty");
    if (!list) return;

    const q = (query || "").toLowerCase().trim();
    const filtered = allFriends.filter(
      (f) => !q || f.username.toLowerCase().includes(q),
    );

    if (filtered.length === 0) {
      list.innerHTML = "";
      empty.style.display = "block";
      return;
    }
    empty.style.display = "none";

    list.innerHTML = filtered
      .map((f) => {
        const isSel = !!selectedFriends[f.id];
        const avatar = getAvatarUrl(f.profile_image, f.username);
        const errSrc = getAvatarUrl(null, f.username);
        return `
        <div class="friend-item${isSel ? " is-selected" : ""}"
             data-id="${f.id}" data-name="${f.username}" data-img="${f.profile_image || ""}">
          <img src="${avatar}" alt="${f.username}"
               onerror="this.src='${errSrc}'">
          <span>${f.username}</span>
        </div>`;
      })
      .join("");
  }

  function addFriendTag(id, username) {
    if (selectedFriends[id]) return;

    if (Object.keys(selectedFriends).length >= 5) {
      const msg = document.getElementById("collab-limit-msg");
      if (msg) {
        msg.style.display = "block";
        setTimeout(() => {
          msg.style.display = "none";
        }, 4000);
      }
      document.getElementById("friend-search-input").value = "";
      return;
    }

    selectedFriends[id] = { id, username };

    // Tag visual
    const tagsDiv = document.getElementById("friend-tags");
    const srchInp = document.getElementById("friend-search-input");
    const tag = document.createElement("span");
    tag.className = "friend-tag";
    tag.dataset.id = id;
    tag.innerHTML = `${username} <button class="remove-tag" type="button" aria-label="Quitar">&times;</button>`;
    tag.querySelector(".remove-tag").addEventListener("click", (e) => {
      e.stopPropagation();
      removeFriendTag(id);
    });
    tagsDiv.insertBefore(tag, srchInp);

    // Hidden input para el form
    const inp = document.createElement("input");
    inp.type = "hidden";
    inp.name = "collaborators[]";
    inp.value = id;
    inp.id = `hidden-collab-${id}`;
    document.getElementById("collaborators-hidden").appendChild(inp);

    renderPickerDropdown(srchInp.value);
    srchInp.value = "";
    srchInp.focus();
  }

  function removeFriendTag(id) {
    delete selectedFriends[id];
    const tag = document.querySelector(`.friend-tag[data-id="${id}"]`);
    if (tag) tag.remove();
    const hidden = document.getElementById(`hidden-collab-${id}`);
    if (hidden) hidden.remove();

    // Ocultar aviso si bajamos de 5
    if (Object.keys(selectedFriends).length < 5) {
      const msg = document.getElementById("collab-limit-msg");
      if (msg) msg.style.display = "none";
    }

    const srchInp = document.getElementById("friend-search-input");
    renderPickerDropdown(srchInp ? srchInp.value : "");
  }

  function openDropdown() {
    const dd = document.getElementById("friend-dropdown");
    if (dd) dd.style.display = "block";
  }

  function closeDropdown() {
    const dd = document.getElementById("friend-dropdown");
    if (dd) dd.style.display = "none";
  }

  async function loadFriends() {
    if (friendsLoaded) {
      openDropdown();
      return;
    }
    try {
      const res = await fetch(
        window.BASE_URL + "/api/php/forums.php?action=get_friends",
      );
      const data = await res.json();
      if (data.success && Array.isArray(data.friends)) {
        allFriends = data.friends;
      }
    } catch (e) {
      console.warn("[friend-picker] Error cargando amigos:", e);
    }
    friendsLoaded = true;
    renderPickerDropdown("");
    openDropdown();
  }

  // Binds del picker
  const pickerEl = document.getElementById("friend-picker");
  const srchInput = document.getElementById("friend-search-input");
  const friendListEl = document.getElementById("friend-list");

  if (pickerEl && srchInput && friendListEl) {
    // Abrir al clicar en cualquier parte del picker
    pickerEl.addEventListener("click", () => {
      srchInput.focus();
      loadFriends();
    });

    // Filtrar mientras escribe
    srchInput.addEventListener("input", () => {
      if (!friendsLoaded) {
        loadFriends();
        return;
      }
      renderPickerDropdown(srchInput.value);
      openDropdown();
    });

    // Seleccionar amigo
    friendListEl.addEventListener("click", (e) => {
      const item = e.target.closest(".friend-item");
      if (!item || item.classList.contains("is-selected")) return;
      addFriendTag(item.dataset.id, item.dataset.name);
    });

    // Cerrar dropdown al clicar fuera
    document.addEventListener("click", (e) => {
      const dd = document.getElementById("friend-dropdown");
      if (dd && !pickerEl.contains(e.target) && !dd.contains(e.target)) {
        closeDropdown();
      }
    });
  }

  // ── Render lista de foros ────────────────────────────────────────
  function listForums(forums) {
    const container = document.getElementById("forum-list");
    if (!container) return;

    if (!forums || forums.length === 0) {
      container.innerHTML = `
        <div class="text-center text-muted py-5">
          <i class="fa-solid fa-comments fa-3x mb-3 d-block opacity-25"></i>
          <h5>No hay foros disponibles</h5>
          <p class="small">Sé el primero en crear uno.</p>
        </div>`;
      return;
    }

    container.innerHTML = forums
      .map((forum) => {
        const privacyIcon =
          forum.privacy === "private" ? "fa-lock" : "fa-earth-europe";
        const privacyLabel =
          forum.privacy === "private" ? "Privado" : "Público";
        const privacyClass =
          forum.privacy === "private" ? "text-warning" : "text-success";

        const fecha = forum.created_at
          ? new Date(forum.created_at).toLocaleDateString("es-ES", {
              day: "numeric",
              month: "short",
              year: "numeric",
            })
          : "";
        let autor = "";
        if (forum.author_name) {
          autor = `<div class="d-flex flex-wrap align-items-center gap-3">
                     <div class="d-flex align-items-center">
                       <small class="text-muted"><i class="fa-solid fa-user me-1"></i>${forum.author_name}</small>
                     </div>`;
                     
          if (forum.collaborators_json) {
            try {
               let collabs = typeof forum.collaborators_json === "string" ? JSON.parse(forum.collaborators_json) : forum.collaborators_json;
               if (collabs && collabs.length > 0) {
                 collabs = collabs.filter(c => c && c.username);
                 if (collabs.length > 0) {
                   autor += `<div class="d-flex align-items-center bg-dark bg-opacity-50 px-2 py-1 rounded-pill border border-secondary border-opacity-25" title="Colaboradores">
                               <small class="text-muted me-2 fw-semibold" style="font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px;">Colabs</small>
                               <div class="d-flex">`;
                   collabs.slice(0, 3).forEach(c => {
                       let imgSrc = window.BASE_URL + '/web/img/avatars/default_avatar.svg';
                       if (c.profile_image) {
                           if (c.profile_image.startsWith('http://') || c.profile_image.startsWith('https://')) { imgSrc = c.profile_image; }
                           else if (c.profile_image.startsWith('/')) { imgSrc = c.profile_image.includes('/web/img/uploads/') ? window.BASE_URL + '/web/img/uploads/' + c.profile_image.split('/web/img/uploads/')[1] : window.BASE_URL + c.profile_image; }
                           else { imgSrc = 'https://ubtoaaawqdneblyvbelr.supabase.co/storage/v1/object/public/avatars/' + c.profile_image; }
                       }
                       autor += `<img src="${imgSrc}" alt="${c.username}" title="${c.username}" class="rounded-circle border border-dark shadow-sm" style="width: 24px; height: 24px; object-fit: cover; margin-left: -6px; z-index: 1; position: relative;">`;
                   });
                   if (collabs.length > 3) {
                       autor += `<div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center border border-dark shadow-sm" style="width: 24px; height: 24px; font-size: 0.65rem; margin-left: -6px; z-index: 1; position: relative;">+${collabs.length - 3}</div>`;
                   }
                   autor += `</div></div>`;
                 }
               }
            } catch(e) { console.warn("Error parsing collabs", e); }
          }
          autor += `</div>`;
        }

        const href = window.IS_LOGGED_IN ? `${window.BASE_URL}/web/views/public/forums/forums.php?id=${forum.id}` : `#`;
        const extraAttr = window.IS_LOGGED_IN ? '' : `data-bs-toggle="modal" data-bs-target="#loginModal" onclick="event.preventDefault();"`;

        return `
        <a href="${href}" ${extraAttr} class="forum-card-item d-flex flex-column h-100">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 text-white fw-bold d-flex align-items-center gap-2">
              <span class="forum-icon-bg"><i class="fa-regular fa-comments"></i></span>
              ${forum.title}
            </h5>
            <span class="forum-privacy-badge ${forum.privacy === "private" ? "private" : "public"}">
              <i class="fa-solid ${privacyIcon} me-1"></i>${privacyLabel}
            </span>
          </div>
          <p class="mb-3 text-white-50 text-truncate" style="max-width: 90%; font-size: 0.95rem;">
            ${forum.forum_subject}
          </p>
          <div class="mt-auto pt-3 border-top border-secondary border-opacity-10 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            ${autor}
            <div class="text-secondary small text-nowrap ms-sm-auto bg-dark bg-opacity-25 px-2 py-1 rounded">
              <i class="fa-regular fa-calendar me-1"></i>${fecha}
            </div>
          </div>
        </a>`;
      })
      .join("");
  }

  // ── Buscador de foros ────────────────────────────────────────────
  const buscador = document.getElementById("forum-search-input");
  const btnMine = document.getElementById("filter-mine-btn");
  let isMineFilterActive = false;

  if (btnMine) {
      btnMine.addEventListener("click", function () {
          isMineFilterActive = !isMineFilterActive;
          if (isMineFilterActive) {
             btnMine.classList.remove("btn-outline-success");
             btnMine.classList.add("btn-success");
             btnMine.classList.add("text-white");
          } else {
             btnMine.classList.remove("btn-success");
             btnMine.classList.remove("text-white");
             btnMine.classList.add("btn-outline-success");
          }
          triggerSearch();
      });
  }

  function triggerSearch() {
      const val = buscador ? buscador.value.trim() : "";
      let url = window.BASE_URL + "/api/php/forums.php?";

      if (val.length > 2) {
          url += "action=search_forums&search=" + encodeURIComponent(val);
      } else {
          url += "action=list";
      }

      if (isMineFilterActive) {
          url += "&mine=true";
      }

      fetch(url)
        .then((res) => res.json())
        .then((data) => {
            if (data.success) listForums(data.forums);
            else console.warn("Error al cargar los foros");
        })
        .catch(e => console.error("Error connecting to API", e));
  }

  if (buscador) {
    // Carga inicial
    triggerSearch();

    // Búsqueda dinámica
    buscador.addEventListener("input", function () {
       triggerSearch();
    });
  }
});
