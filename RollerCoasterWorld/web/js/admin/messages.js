document.addEventListener("DOMContentLoaded", () => {
  let currentPage = 1;
  const limit = 10;
  let currentMsgId = null;
  let currentMsgData = null; // Store full data to re-use in modal

  // DOM Elements
  const listEl = document.getElementById("admin-msg-list");
  const countEl = document.getElementById("admin-msg-count");
  const paginationEl = document.getElementById("admin-msg-pagination");
  const searchInput = document.getElementById("admin-msg-search");
  const filterStatus = document.getElementById("filter-msg-status");
  const filterReason = document.getElementById("filter-msg-reason");

  // Modals
  const modalMsgDetailEl = document.getElementById("modal-msg-detail");
  const modalDeleteMsgEl = document.getElementById("modal-delete-msg");

  if (!modalMsgDetailEl)
    console.error(
      "[messages.js] ❌ No se encuentra #modal-msg-detail en el DOM",
    );
  if (!modalDeleteMsgEl)
    console.error(
      "[messages.js] ❌ No se encuentra #modal-delete-msg en el DOM",
    );

  const modalDetail = modalMsgDetailEl
    ? new bootstrap.Modal(modalMsgDetailEl)
    : null;
  const modalDelete = modalDeleteMsgEl
    ? new bootstrap.Modal(modalDeleteMsgEl)
    : null;

  // API Call Wrapper
  async function api(action, method = "GET", body = null) {
    const url = new URL(
      window.BASE_URL + "/api/php/admin/messages.php",
      window.location.origin,
    );
    url.searchParams.append("action", action);

    if (method === "GET") {
      url.searchParams.append("page", currentPage);
      url.searchParams.append("limit", limit);
      url.searchParams.append("search", searchInput.value.trim());
      url.searchParams.append("status", filterStatus.value);
      url.searchParams.append("reason", filterReason.value);
    }

    console.log(`[messages.js] → ${method} ${url.toString()}`);

    const options = { method, credentials: "include", headers: {} };
    if (method !== "GET") {
      options.headers["X-CSRF-Token"] = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? "";
    }
    if (body) {
      options.headers["Content-Type"] = "application/json";
      options.body = JSON.stringify(body);
    }

    try {
      const res = await fetch(url, options);
      console.log(`[messages.js] ← HTTP ${res.status} ${res.statusText}`);

      const text = await res.text();
      let j;
      try {
        j = JSON.parse(text);
      } catch (parseErr) {
        console.error(
          "[messages.js] ❌ Respuesta no es JSON:",
          text.slice(0, 300),
        );
        throw new Error("La respuesta del servidor no es JSON válido");
      }

      console.log("[messages.js] ← JSON:", j);
      if (!j.success) throw new Error(j.error || "Error de red");
      return j;
    } catch (e) {
      console.error("[messages.js] ❌ Error en api():", e.message);
      toast(e.message, "error");
      throw e;
    }
  }

  // Reason to Label & Color mapping
  const reasonMap = {
    error: { label: "Error/Bug", bg: "bg-danger" },
    suggestion: { label: "Sugerencia", bg: "bg-info text-dark" },
    report: { label: "Reporte", bg: "bg-warning text-dark" },
    info: { label: "Información", bg: "bg-primary" },
    other: { label: "Otro", bg: "bg-secondary" },
  };

  // Render List
  async function loadMessages() {
    listEl.innerHTML = `<div class="list-group-item text-center text-muted py-5"><i class="fa-solid fa-circle-notch fa-spin fa-2x mb-2 d-block text-success"></i>Cargando...</div>`;

    try {
      const j = await api("list");
      const { items, total, pages } = j;

      countEl.textContent = `${total} mensaje${total !== 1 ? "s" : ""} encontrado${total !== 1 ? "s" : ""}`;

      if (!items.length) {
        listEl.innerHTML = `<div class="list-group-item text-center text-muted py-5"><i class="fa-solid fa-inbox fa-2x mb-2 d-block text-secondary"></i>No hay mensajes que coincidan con los filtros.</div>`;
        paginationEl.innerHTML = "";
        return;
      }

      listEl.innerHTML = items
        .map((m) => {
          const r = reasonMap[m.reason] || reasonMap.other;
          const d = new Date(m.created_at).toLocaleDateString("es-ES", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
          });
          const unreadClass = !m.is_read
            ? "border-start border-4 border-success bg-dark"
            : "bg-dark";
          const titleClass = !m.is_read ? "text-white fw-bold" : "text-light";

          // Save full JSON string encoded so we can parse it on click without making another API call
          const dataJson = encodeURIComponent(JSON.stringify(m));

          return `
          <a href="#" data-msg-id="${m.id}" class="list-group-item list-group-item-action ${unreadClass} border-secondary text-light py-3 px-4" onclick="window.openMessageDetail('${dataJson}', event)">
            <div class="d-flex w-100 justify-content-between align-items-center mb-2">
              <h5 class="mb-0 ${titleClass} text-truncate pe-3">
                ${!m.is_read ? '<i class="fa-solid fa-circle text-success small me-2" style="font-size:0.5rem; vertical-align:middle;"></i>' : ""}
                ${m.subject}
              </h5>
              <small class="text-muted text-nowrap">${d}</small>
            </div>
            <div class="d-flex justify-content-between align-items-end">
              <div>
                <p class="mb-1 text-muted small"><i class="fa-solid fa-user me-1"></i>${m.user_name} &bull; ${m.user_email}</p>
                <span class="badge ${r.bg} rounded-pill">${r.label}</span>
                ${m.wants_reply ? '<span class="badge bg-secondary rounded-pill ms-1"><i class="fa-solid fa-reply me-1"></i>Solicita respuesta</span>' : ""}
              </div>
            </div>
          </a>
        `;
        })
        .join("");

      // Pagination
      let pHtml = "";
      if (pages > 1) {
        pHtml += `<ul class="pagination pagination-sm mb-0">`;
        for (let i = 1; i <= pages; i++) {
          pHtml += `<li class="page-item ${i === currentPage ? "active" : ""}"><a class="page-link shadow-none" href="#" onclick="window.goToPage(${i}, event)">${i}</a></li>`;
        }
        pHtml += `</ul>`;
      }
      paginationEl.innerHTML = pHtml;
    } catch (e) {
      listEl.innerHTML = `<div class="list-group-item text-center text-danger py-5"><i class="fa-solid fa-triangle-exclamation fa-2x mb-2 d-block"></i>Error al cargar los mensajes.</div>`;
    }
  }

  // Local toast fallback if showToast isn't globally defined
  function toast(msg, type = "success") {
    if (typeof showToast === "function") {
      toast(msg, type);
      return;
    }
    // Fallback: small Bootstrap-style alert that auto-dismisses
    const id = "msg-toast-" + Date.now();
    const color = type === "error" ? "bg-danger" : "bg-success";
    const el = document.createElement("div");
    el.id = id;
    el.className = `toast align-items-center text-white ${color} border-0 position-fixed bottom-0 end-0 m-3`;
    el.style.zIndex = 9999;
    el.setAttribute("role", "alert");
    el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    document.body.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 3000 });
    t.show();
    el.addEventListener("hidden.bs.toast", () => el.remove());
  }

  window.goToPage = (p, e) => {
    e.preventDefault();
    currentPage = p;
    loadMessages();
  };

  window.openMessageDetail = async (dataJson, e) => {
    e.preventDefault();
    const m = JSON.parse(decodeURIComponent(dataJson));
    currentMsgId = m.id;
    currentMsgData = m;

    const r = reasonMap[m.reason] || reasonMap.other;
    const d = new Date(m.created_at).toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });

    document.getElementById("msg-detail-subject").textContent = m.subject;
    document.getElementById("msg-detail-user").innerHTML =
      `<i class="fa-solid fa-user me-1 text-success"></i>${m.user_name}`;
    document.getElementById("msg-detail-email").innerHTML =
      `<i class="fa-solid fa-envelope me-1 text-success"></i>${m.user_email}`;
    document.getElementById("msg-detail-date").innerHTML =
      `<i class="fa-solid fa-calendar me-1 text-success"></i>${d}`;
    document.getElementById("msg-detail-body").textContent = m.user_message;

    const badge = document.getElementById("msg-detail-badge");
    badge.className = `badge ${r.bg} rounded-pill fs-6`;
    badge.textContent = r.label;

    const replyAlert = document.getElementById("msg-reply-alert");
    if (m.wants_reply) {
      replyAlert.classList.remove("d-none");
    } else {
      replyAlert.classList.add("d-none");
    }

    // Update toggle button text based on status
    updateToggleButton(m.is_read);

    modalDetail.show();

    // Auto-mark as read if unread
    if (!m.is_read) {
      try {
        await api("update_status", "POST", { id: m.id, is_read: true });
        m.is_read = true;
        currentMsgData.is_read = true;
        updateToggleButton(true);
        loadMessages(); // re-apply filter: desaparece si está en "No leídos"
      } catch (e) {}
    }
  };

  // Update a single card in the list without reloading everything
  function updateCardInPlace(id, isRead) {
    const card = listEl.querySelector(`[data-msg-id="${id}"]`);
    if (!card) return;

    const h5 = card.querySelector("h5");
    const dot = card.querySelector(".fa-circle");

    if (isRead) {
      card.classList.remove(
        "border-start",
        "border-4",
        "border-success",
        "bg-dark",
      );
      card.classList.add("bg-dark", "bg-opacity-50");
      h5.classList.replace("text-white", "text-light");
      h5.classList.remove("fw-bold");
      if (dot) dot.parentElement.removeChild(dot);
    } else {
      card.classList.remove("bg-opacity-50");
      card.classList.add(
        "border-start",
        "border-4",
        "border-success",
        "bg-dark",
      );
      h5.classList.replace("text-light", "text-white");
      h5.classList.add("fw-bold");
      if (!dot) {
        const icon = document.createElement("i");
        icon.className = "fa-solid fa-circle text-success small me-2";
        icon.style.cssText = "font-size:0.5rem; vertical-align:middle;";
        h5.prepend(icon);
      }
    }
  }

  function updateToggleButton(isRead) {
    const btn = document.getElementById("btn-toggle-read");
    if (isRead) {
      btn.innerHTML = `<i class="fa-solid fa-envelope me-2"></i>Marcar como No Leído`;
      btn.classList.replace("btn-outline-success", "btn-outline-secondary");
    } else {
      btn.innerHTML = `<i class="fa-solid fa-envelope-open me-2"></i>Marcar como Leído`;
      btn.classList.replace("btn-outline-secondary", "btn-outline-success");
    }
  }

  // Events
  document.getElementById("btn-msg-filtrar").addEventListener("click", () => {
    currentPage = 1;
    loadMessages();
  });

  document.getElementById("btn-msg-borrar").addEventListener("click", () => {
    searchInput.value = "";
    filterStatus.value = "";
    filterReason.value = "";
    currentPage = 1;
    loadMessages();
  });

  searchInput.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      currentPage = 1;
      loadMessages();
    }
  });

  document
    .getElementById("btn-toggle-read")
    .addEventListener("click", async () => {
      if (!currentMsgData) return;
      const newStatus = !currentMsgData.is_read;
      const btn = document.getElementById("btn-toggle-read");
      btn.disabled = true;
      try {
        await api("update_status", "POST", {
          id: currentMsgId,
          is_read: newStatus,
        });
        currentMsgData.is_read = newStatus;
        updateToggleButton(newStatus);
        updateCardInPlace(currentMsgId, newStatus);
        toast(newStatus ? "Marcado como leído" : "Marcado como no leído");
        loadMessages(); // re-apply current filter so card desaparece si no coincide
      } catch (e) {
        toast("Error al actualizar el estado", "error");
      } finally {
        btn.disabled = false;
      }
    });

  document.getElementById("btn-reply-msg").addEventListener("click", () => {
    if (!currentMsgData) return;
    const email = encodeURIComponent(currentMsgData.user_email);
    const subject = encodeURIComponent(`Re: ${currentMsgData.subject}`);
    const body = encodeURIComponent(
      `\n\n---\nHas escrito el ${new Date(currentMsgData.created_at).toLocaleDateString("es-ES")}:\n"${currentMsgData.user_message}"`,
    );
    window.open(
      `https://mail.google.com/mail/?view=cm&to=${email}&su=${subject}&body=${body}`,
      "_blank",
    );
  });

  document
    .getElementById("btn-delete-msg-prompt")
    .addEventListener("click", () => {
      modalDetail.hide();
      setTimeout(() => modalDelete.show(), 300);
    });

  document
    .getElementById("confirm-delete-msg")
    .addEventListener("click", async () => {
      if (!currentMsgId) return;
      const btn = document.getElementById("confirm-delete-msg");
      const originalHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Eliminando...`;
      try {
        await api("delete", "POST", { id: currentMsgId });
        toast("Mensaje eliminado correctamente");
        modalDelete.hide();
        // Remove card from DOM instantly
        const card = listEl.querySelector(`[data-msg-id="${currentMsgId}"]`);
        if (card) {
          card.style.transition = "opacity 0.3s";
          card.style.opacity = "0";
          setTimeout(() => {
            card.remove();
          }, 300);
        }
        currentMsgId = null;
        currentMsgData = null;
      } catch (e) {
        toast("Error al eliminar el mensaje", "error");
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
      }
    });

  // Initial load
  loadMessages();
});
