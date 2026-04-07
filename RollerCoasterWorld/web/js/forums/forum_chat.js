// forum_chat.js — Lógica del chat interno del foro
// Polling cada 5s, reply inline, adjuntos con compresión, moderación

(function () {
  'use strict';

  /* ── CONSTANTES ──────────────────────────────────────────── */
  const BASE        = window.BASE_URL;
  const FORUM_ID    = window.FORUM_ID;
  const CURRENT_UID = window.CURRENT_USER ? parseInt(window.CURRENT_USER) : null;
  const IS_ADMIN    = window.IS_ADMIN === true;
  const SUP_URL     = window.SUPABASE_URL;
  const SUP_KEY     = window.SUPABASE_KEY;
  const POLL_MS     = 5000;   // cada 5s
  const MAX_BYTES   = 10 * 1024 * 1024; // 10 MB

  /* ── ESTADO ──────────────────────────────────────────────── */
  let forumRole    = 'reader';   // owner | collaborator | reader | banned
  let replyToId    = null;
  let pendingFile  = null;      // { file, url, name }
  let pollTimer    = null;
  let lastMsgTime  = null;      // ISO string del último msg cargado
  let pendingDeleteId = null;
  let pendingRemoveCollabId = null;
  let pendingRemoveCollabName = null;
  let rateLimitEnd = 0;         // timestamp ms en que termina el cooldown
  let rateLimitInterval = null;
  let forumPrivacy = null;

  /* ── ELEMENTOS ───────────────────────────────────────────── */
  const el = id => document.getElementById(id);

  /* ══════════════════════════════════════════════════════════
     INIT
  ══════════════════════════════════════════════════════════ */
  async function init() {
    if (!FORUM_ID) return;
    await loadForum();
    await loadMessages(true);
    bindInput();
    bindFileInput();
    bindReplyCancel();
    startPolling();
  }

  /* ══════════════════════════════════════════════════════════
     LOAD FORUM INFO
  ══════════════════════════════════════════════════════════ */
  async function loadForum() {
    try {
      const res  = await fetch(`${BASE}/api/php/forums.php?action=get_forum&forum_id=${FORUM_ID}`);
      const data = await res.json();
      if (!data.success) return;

      const { forum, role } = data;
      forumRole = role;
      forumPrivacy = forum.privacy;

      // Header
      el('forum-header-title').textContent = forum.title;
      el('forum-header-sub').textContent   = forum.forum_subject;

      // Avatar del autor y colaboradores
      const avatarEl = el('forum-header-avatar');
      if (forum.author_image) {
        let authorHtml = `
          <div class="d-flex align-items-center gap-2">
            <img src="${avatarUrl(forum.author_image, forum.author_name)}" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover; border: 2px solid var(--rcw-green-neon);" title="Creador: ${esc(forum.author_name)}">
            <span class="badge bg-success bg-opacity-25 text-success rounded-pill d-none d-sm-inline" style="font-size: 0.65rem;">Propietario</span>
          </div>`;
        
        let collabsHtml = '';
        if (forum.privacy === 'private' && forum.collaborators_json) {
           try {
             let collabs = typeof forum.collaborators_json === 'string' ? JSON.parse(forum.collaborators_json) : forum.collaborators_json;
             collabs = collabs.filter(c => c && c.username);
             if (collabs.length > 0) {
                 collabsHtml = `<div class="d-flex align-items-center gap-2 ms-3 border-start border-secondary ps-3">
                                  <span class="text-muted small d-none d-sm-inline" style="font-size: 0.75rem;">Colaboradores</span>
                                  <div class="d-flex align-items-center gap-2">`;
                 collabs.slice(0, 4).forEach(c => {
                    collabsHtml += `<img src="${avatarUrl(c.profile_image, c.username)}" alt="${esc(c.username)}" title="Colaborador: ${esc(c.username)}" class="rounded-circle border border-secondary" style="width: 32px; height: 32px; object-fit: cover; transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">`;
                 });
                 if (collabs.length > 4) {
                     collabsHtml += `<div class="rounded-circle bg-dark text-muted d-flex align-items-center justify-content-center border border-secondary" style="width: 32px; height: 32px; font-size: 0.7rem; font-weight: bold;" title="+${collabs.length - 4} más">+${collabs.length - 4}</div>`;
                 }
                 collabsHtml += `</div></div>`;
             }
           } catch(e) { console.warn("Error parsing collabs", e) }
        }
        
        avatarEl.innerHTML = `<div class="d-flex align-items-center">${authorHtml}${collabsHtml}</div>`;
      }

      // Zona derecha: badge + botón owner
      const rightEl = el('forum-header-right');
      const privBadge = forum.privacy === 'private'
        ? `<span class="forum-privacy-badge private"><i class="fa-solid fa-lock me-1"></i>Privado</span>`
        : `<span class="forum-privacy-badge public"><i class="fa-solid fa-earth-europe me-1"></i>Público</span>`;

      let ownerBtn = '';
      if (role === 'owner' || IS_ADMIN) {
        ownerBtn = `<button class="forum-mod-btn" id="open-mod-btn" title="Panel de moderación">
                      <i class="fa-solid fa-shield-halved"></i>
                    </button>`;
      }
      rightEl.innerHTML = privBadge + ownerBtn;

      if (el('open-mod-btn')) {
        el('open-mod-btn').addEventListener('click', openModPanel);
      }

      // Banned
      if (role === 'banned') {
        el('forum-input-area').classList.add('d-none');
        el('forum-banned-notice').classList.remove('d-none');
      }

      // Si es público y no es colaborador/owner, input disponible
      // Si es privado y no es colaborador/owner → desactivar input
      if (forum.privacy === 'private' && role === 'reader' && !IS_ADMIN) {
        disableInput('Este foro es privado. Solo los colaboradores pueden escribir.');
      }

      document.title = `${forum.title} — RollerCoaster World`;

      // ── Evento info modal ─────────────────────────────────────────
      const textClickArea = el('forum-header-title').parentElement;
      const avatarClickArea = avatarEl;

      const openInfoModal = () => {
        el('forumInfoModalTitle').textContent = forum.title || '—';
        el('forumInfoModalDesc').textContent  = forum.forum_subject || '—';
        el('forumInfoModalMembers').textContent = forum.member_count !== undefined ? forum.member_count : '—';
        
        if (forum.created_at) {
          const d = new Date(forum.created_at);
          el('forumInfoModalCreated').textContent = d.toLocaleDateString('es-ES', {year:'numeric', month:'short', day:'numeric'});
        } else {
          el('forumInfoModalCreated').textContent = '—';
        }
        
        if (forum.author_name) {
          el('forumInfoModalAuthor').innerHTML = `Creado por <a href="${BASE}/web/views/public/users/user_profile.php?id=${forum.author_id}" class="text-success text-decoration-none" style="transition: opacity 0.2s;" onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">${esc(forum.author_name)}</a>`;
        } else {
          el('forumInfoModalAuthor').innerHTML = '';
        }
        
        const isPrivate = forum.privacy === 'private';
        el('forumInfoModalPrivacyIcon').className = isPrivate ? 'fa-solid fa-lock me-1' : 'fa-solid fa-earth-europe me-1';
        el('forumInfoModalPrivacyText').textContent = isPrivate ? 'Foro privado' : 'Foro público';
        
        // Colaboradores
        const collabsContainer = el('forumInfoModalCollabs');
        const collabsList = el('forumInfoModalCollabsList');
        collabsContainer.style.display = 'none';
        collabsList.innerHTML = '';
        if (isPrivate && forum.collaborators_json) {
           try {
             let collabs = typeof forum.collaborators_json === 'string' ? JSON.parse(forum.collaborators_json) : forum.collaborators_json;
             collabs = collabs.filter(c => c && c.username);
             if (collabs.length > 0) {
                 collabsContainer.style.display = 'block';
                 collabs.forEach(c => {
                     const imgSrc = avatarUrl(c.profile_image, c.username);
                     const profileLink = `${BASE}/web/views/public/users/user_profile.php?id=${c.id}`;
                     collabsList.innerHTML += `
                        <a href="${profileLink}" class="text-decoration-none d-flex align-items-center gap-2 mb-1 p-1 rounded" style="background: rgba(255,255,255,0.05); transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                           <img src="${imgSrc}" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                           <span class="small text-white">${esc(c.username)}</span>
                        </a>
                     `;
                 });
             }
           } catch(e) { console.warn("Error parsing collabs modal", e) }
        }

        new bootstrap.Modal(el('forumInfoModal')).show();
      };

      textClickArea.style.cursor = 'pointer';
      textClickArea.addEventListener('click', openInfoModal);
      
      avatarClickArea.style.cursor = 'pointer';
      avatarClickArea.addEventListener('click', openInfoModal);

    } catch (e) {
      console.error('[forum] Error cargando foro:', e);
    }
  }

  /* ══════════════════════════════════════════════════════════
     LOAD MESSAGES
  ══════════════════════════════════════════════════════════ */
  async function loadMessages(initial = false) {
    try {
      let url = `${BASE}/api/php/forums.php?action=get_messages&forum_id=${FORUM_ID}&limit=100`;
      if (!initial && lastMsgTime) {
        url += `&since=${encodeURIComponent(lastMsgTime)}`;
      }

      const res  = await fetch(url);
      const data = await res.json();

      if (!data.success) return;

      el('forum-loading').style.display = 'none';

      const msgs = data.messages;
      if (!msgs || msgs.length === 0) {
        if (initial) {
          el('forum-messages-list').innerHTML = `
            <div class="forum-no-msgs">
              <i class="fa-regular fa-comment-dots fa-3x mb-3 opacity-25"></i>
              <p>Aún no hay mensajes. ¡Sé el primero!</p>
            </div>`;
        }
        return;
      }

      if (initial) {
        // Cargar todos
        el('forum-messages-list').innerHTML = msgs.map(renderMessage).join('');
        scrollBottom();
      } else {
        // Solo añadir los nuevos (polling)
        const wasAtBottom = isAtBottom();
        msgs.forEach(m => {
          if (document.querySelector(`[data-msg-id="${m.id}"]`)) return; // Evitar duplicados por concurrencia

          const noMsgs = el('forum-messages-list').querySelector('.forum-no-msgs');
          if (noMsgs) noMsgs.remove();
          el('forum-messages-list').insertAdjacentHTML('beforeend', renderMessage(m));
        });
        if (wasAtBottom) scrollBottom();
      }

      // Actualizar lastMsgTime con el último mensaje
      if (msgs.length > 0) {
        lastMsgTime = msgs[msgs.length - 1].created_at;
      }

      // Bind acciones de los mensajes
      bindMessageActions();

    } catch (e) {
      console.error('[forum] Error cargando mensajes:', e);
    }
  }

  /* ══════════════════════════════════════════════════════════
     RENDER MENSAJE
  ══════════════════════════════════════════════════════════ */
  function renderMessage(m) {
    const isMine    = CURRENT_UID && (parseInt(m.user_id) === CURRENT_UID);
    const isOwnerMsg = (forumRole === 'owner' || IS_ADMIN);
    const sideClass = isMine ? 'mine' : 'theirs';

    const time = formatTime(m.created_at);
    const avatar = avatarUrl(m.profile_image, m.username);

    // Reply quote
    let replyHtml = '';
    if (m.reply_to_id && m.reply_username) {
      const snippet = (m.reply_content || '').substring(0, 80);
      replyHtml = `
        <div class="msg-reply-quote">
          <span class="reply-quote-name">${esc(m.reply_username)}</span>
          <span class="reply-quote-text">${esc(snippet)}${snippet.length >= 80 ? '…' : ''}</span>
        </div>`;
    }

    // Attachment
    let attachHtml = '';
    if (m.attachment_url) {
      const isImage = /\.(jpe?g|png|gif|webp|svg)(\?.*)?$/i.test(m.attachment_url);
      if (isImage) {
        attachHtml = `<div class="msg-attachment">
          <a href="${esc(m.attachment_url)}" target="_blank" rel="noopener">
            <img src="${esc(m.attachment_url)}" alt="${esc(m.file_name || 'Imagen')}" class="msg-attach-img" loading="lazy">
          </a>
        </div>`;
      } else {
        attachHtml = `<div class="msg-attachment">
          <a href="${esc(m.attachment_url)}" target="_blank" rel="noopener" class="msg-attach-file">
            <i class="fa-solid fa-file me-2"></i>${esc(m.file_name || 'Archivo')}
          </a>
        </div>`;
      }
    }

    // Texto
    const contentHtml = m.content ? `<div class="msg-text">${linkify(esc(m.content))}</div>` : '';

    // Oculto
    const hiddenBanner = m.is_hidden
      ? `<div class="msg-hidden-banner"><i class="fa-solid fa-eye-slash me-1"></i>Mensaje ocultado por el moderador</div>`
      : '';

    // Botones de acción
    let actionsHtml = `
      <button class="msg-action-btn reply-btn" data-id="${m.id}" data-user="${esc(m.username)}" data-content="${esc((m.content || '').substring(0, 100))}" data-tooltip="Responder">
        <i class="fa-solid fa-reply"></i>
      </button>`;

    if (isMine) {
      actionsHtml += `<button class="msg-action-btn delete-btn" data-id="${m.id}" data-tooltip="Borrar">
        <i class="fa-solid fa-trash"></i>
      </button>`;
    }

    if (isOwnerMsg && !isMine) {
      const hideIcon = m.is_hidden ? 'fa-eye' : 'fa-eye-slash';
      const hideVal  = m.is_hidden ? 0 : 1;
      actionsHtml += `
        <button class="msg-action-btn delete-btn" data-id="${m.id}" data-tooltip="Borrar mensaje">
          <i class="fa-solid fa-trash"></i>
        </button>
        <button class="msg-action-btn hide-btn" data-id="${m.id}" data-hidden="${hideVal}" data-tooltip="${m.is_hidden ? 'Mostrar' : 'Ocultar'} mensaje">
          <i class="fa-solid ${hideIcon}"></i>
        </button>
        <button class="msg-action-btn ban-btn" data-user-id="${m.user_id}" data-username="${esc(m.username)}" data-tooltip="Banear a ${esc(m.username)}">
          <i class="fa-solid fa-user-slash"></i>
        </button>`;
    }

    return `
      <div class="forum-msg-wrap ${sideClass}${m.is_hidden ? ' is-hidden' : ''}" data-msg-id="${m.id}">
        ${!isMine ? `<img src="${avatar}" alt="${esc(m.username)}" class="msg-avatar" onerror="this.src='${avatarFallback(m.username)}'">` : ''}
        <div class="msg-bubble">
          ${!isMine ? `<div class="msg-author">${esc(m.username)}</div>` : ''}
          ${replyHtml}
          ${hiddenBanner}
          ${contentHtml}
          ${attachHtml}
          <div class="msg-time">${time}</div>
          <div class="msg-actions">${actionsHtml}</div>
        </div>
        ${isMine ? `<img src="${avatar}" alt="${esc(m.username)}" class="msg-avatar" onerror="this.src='${avatarFallback(m.username)}'">` : ''}
      </div>`;
  }

  /* ══════════════════════════════════════════════════════════
     BIND ACCIONES DE MENSAJES
  ══════════════════════════════════════════════════════════ */
  function bindMessageActions() {
    // Reply
    document.querySelectorAll('.reply-btn').forEach(btn => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        if (!CURRENT_UID) return;
        replyToId = parseInt(btn.dataset.id);
        el('reply-preview-name').textContent    = btn.dataset.user;
        el('reply-preview-content').textContent = btn.dataset.content;
        el('forum-reply-preview').classList.remove('d-none');
        el('forum-msg-input').focus();
      });
    });

    // Delete
    document.querySelectorAll('.delete-btn').forEach(btn => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', () => {
        pendingDeleteId = parseInt(btn.dataset.id);
        new bootstrap.Modal(el('deleteModal')).show();
      });
    });

    // Hide / Unhide
    document.querySelectorAll('.hide-btn').forEach(btn => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', async () => {
        const msgId  = parseInt(btn.dataset.id);
        const hidden = parseInt(btn.dataset.hidden);
        const fd = new FormData();
        fd.append('message_id', msgId);
        fd.append('hidden', hidden);
        const res  = await fetch(`${BASE}/api/php/forums.php?action=hide_message`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          // Refresca el wrap del mensaje
          const wrap = document.querySelector(`[data-msg-id="${msgId}"]`);
          if (wrap) {
            wrap.classList.toggle('is-hidden', hidden === 1);
            // Flip el botón
            btn.dataset.hidden = hidden === 1 ? 0 : 1;
            btn.querySelector('i').className = hidden === 1 ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
            btn.title = hidden === 1 ? 'Mostrar mensaje' : 'Ocultar mensaje';
          }
        }
      });
    });

    // Ban
    document.querySelectorAll('.ban-btn').forEach(btn => {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', async () => {
        const targetId = parseInt(btn.dataset.userId);
        const uname    = btn.dataset.username;
        if (!confirm(`¿Banear a "${uname}" de este foro?`)) return;
        const fd = new FormData();
        fd.append('forum_id',       FORUM_ID);
        fd.append('target_user_id', targetId);
        const res  = await fetch(`${BASE}/api/php/forums.php?action=ban_user`, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          showToast(`Usuario "${uname}" baneado`, 'success');
        } else {
          showToast(data.error || 'Error al banear', 'error');
        }
      });
    });

    // Confirm delete message
    const confirmBtn = el('confirm-delete-btn');
    if (confirmBtn && !confirmBtn.dataset.bound) {
      confirmBtn.dataset.bound = '1';
      confirmBtn.addEventListener('click', async () => {
        if (!pendingDeleteId) return;
        const fd = new FormData();
        fd.append('message_id', pendingDeleteId);
        const res  = await fetch(`${BASE}/api/php/forums.php?action=delete_message`, { method: 'POST', body: fd });
        const data = await res.json();
        bootstrap.Modal.getInstance(el('deleteModal'))?.hide();
        if (data.success) {
          const wrap = document.querySelector(`[data-msg-id="${pendingDeleteId}"]`);
          if (wrap) wrap.remove();
          pendingDeleteId = null;
        } else {
          showToast(data.error || 'Error al borrar', 'error');
        }
      });
    }

    // Confirm remove collaborator
    const confirmCollabBtn = el('confirm-remove-collab-btn');
    if (confirmCollabBtn && !confirmCollabBtn.dataset.bound) {
      confirmCollabBtn.dataset.bound = '1';
      confirmCollabBtn.addEventListener('click', async () => {
        if (!pendingRemoveCollabId) return;
        const fd = new FormData();
        fd.append('forum_id',       FORUM_ID);
        fd.append('target_user_id', pendingRemoveCollabId);
        const res  = await fetch(`${BASE}/api/php/forums.php?action=remove_collaborator`, { method: 'POST', body: fd });
        const data = await res.json();
        bootstrap.Modal.getInstance(el('removeCollabModal'))?.hide();
        if (data.success) {
          showToast(`"${pendingRemoveCollabName}" eliminado de colaboradores`, 'success');
          loadCollaboratorsList(); // Recargar la lista
          pendingRemoveCollabId = null;
        } else {
          showToast(data.error || 'Error al eliminar', 'error');
        }
      });
    }
  }

  /* ══════════════════════════════════════════════════════════
     SEND MESSAGE
  ══════════════════════════════════════════════════════════ */
  async function sendMessage() {
    if (el('forum-send-btn').disabled) return; // Prevenir doble envío rápido

    if (!CURRENT_UID) {
      showToast('Debes iniciar sesión para enviar mensajes', 'error');
      return;
    }

    const input   = el('forum-msg-input');
    const content = input.value.trim();

    if (!content && !pendingFile) return;

    // Rate-limit visual check (el server también lo verifica)
    if (Date.now() < rateLimitEnd) return;

    const sendBtn = el('forum-send-btn');
    sendBtn.disabled = true;

    try {
      let attachUrl  = null;
      let attachName = null;

      // Subir adjunto si hay
      if (pendingFile) {
        const uploadResult = await uploadToSupabase(pendingFile.file);
        if (!uploadResult.ok) {
          showToast('Error al subir el archivo: ' + uploadResult.error, 'error');
          sendBtn.disabled = false;
          return;
        }
        attachUrl  = uploadResult.url;
        attachName = pendingFile.file.name;
      }

      const fd = new FormData();
      fd.append('forum_id', FORUM_ID);
      fd.append('content',  content);
      if (replyToId)  fd.append('reply_to_id',    replyToId);
      if (attachUrl)  fd.append('attachment_url',  attachUrl);
      if (attachName) fd.append('file_name',       attachName);

      const res  = await fetch(`${BASE}/api/php/forums.php?action=send_message`, { method: 'POST', body: fd });
      const data = await res.json();

      if (data.success) {
        input.value = '';
        autoResize(input);
        clearReply();
        clearAttachment();
        await loadMessages(false);  // recarga inmediata para ver el mensaje
      } else {
        // Rate-limit (429)
        if (res.status === 429 || (data.error && data.error.includes('esperar'))) {
          const match = data.error.match(/(\d+)s/);
          const secs  = match ? parseInt(match[1]) : 60;
          startRateLimitCountdown(secs);
        }
        showToast(data.error || 'Error al enviar', 'error');
      }
    } catch (e) {
      console.error('[forum] Error enviando:', e);
      showToast('Error de red', 'error');
    } finally {
      sendBtn.disabled = false;
    }
  }

  /* ══════════════════════════════════════════════════════════
     SUBIDA A SUPABASE STORAGE
  ══════════════════════════════════════════════════════════ */
  async function uploadToSupabase(file) {
    try {
      // Comprimir si es imagen
      const fileToUpload = await compressIfImage(file);
      const ext      = fileToUpload.name.split('.').pop();
      const filename = `${FORUM_ID}/${Date.now()}_${Math.random().toString(36).slice(2)}.${ext}`;
      const url      = `${SUP_URL}/storage/v1/object/forum-attachments/${filename}`;

      const res = await fetch(url, {
        method:  'POST',
        headers: {
          'Authorization': `Bearer ${SUP_KEY}`,
          'Content-Type':  fileToUpload.type || 'application/octet-stream',
          'x-upsert':      'false',
        },
        body: fileToUpload,
      });

      if (!res.ok) {
        const err = await res.text();
        return { ok: false, error: err };
      }

      const publicUrl = `${SUP_URL}/storage/v1/object/public/forum-attachments/${filename}`;
      return { ok: true, url: publicUrl };
    } catch (e) {
      return { ok: false, error: e.message };
    }
  }

  /**
   * Comprime imágenes en canvas antes de subir si superan los 2MB
   */
  async function compressIfImage(file) {
    const isImg = file.type.startsWith('image/') && file.type !== 'image/gif';
    if (!isImg || file.size < 2 * 1024 * 1024) return file;

    return new Promise(resolve => {
      const img = new Image();
      const url = URL.createObjectURL(file);
      img.onload = () => {
        URL.revokeObjectURL(url);
        const MAX_DIM = 1920;
        let w = img.width, h = img.height;
        if (w > MAX_DIM || h > MAX_DIM) {
          const ratio = Math.min(MAX_DIM / w, MAX_DIM / h);
          w = Math.round(w * ratio);
          h = Math.round(h * ratio);
        }
        const canvas = document.createElement('canvas');
        canvas.width  = w;
        canvas.height = h;
        canvas.getContext('2d').drawImage(img, 0, 0, w, h);
        canvas.toBlob(blob => {
          resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.webp'), { type: 'image/webp' }));
        }, 'image/webp', 0.82);
      };
      img.src = url;
    });
  }

  /* ══════════════════════════════════════════════════════════
     BIND INPUT / TEXTAREA
  ══════════════════════════════════════════════════════════ */
  function bindInput() {
    const input   = el('forum-msg-input');
    const sendBtn = el('forum-send-btn');
    if (!input || !sendBtn) return;

    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    input.addEventListener('input', () => autoResize(input));
    sendBtn.addEventListener('click', sendMessage);
  }

  function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 160) + 'px';
  }

  /* ══════════════════════════════════════════════════════════
     BIND FILE INPUT
  ══════════════════════════════════════════════════════════ */
  function bindFileInput() {
    const fileInput  = el('forum-file-input');
    if (!fileInput) return;

    fileInput.addEventListener('change', () => {
      const file = fileInput.files[0];
      if (!file) return;

      if (file.size > MAX_BYTES) {
        showToast('El archivo supera los 10 MB máximos', 'error');
        fileInput.value = '';
        return;
      }

      pendingFile = { file };

      // Mostrar preview
      const preview     = el('attach-preview');
      const previewImg  = el('attach-preview-img');
      const fileIcon    = preview.querySelector('.attach-file-icon');
      const previewName = el('attach-preview-name');

      preview.classList.remove('d-none');
      previewName.textContent = file.name;

      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
          previewImg.src = e.target.result;
          previewImg.classList.remove('d-none');
          fileIcon.classList.add('d-none');
        };
        reader.readAsDataURL(file);
      } else {
        previewImg.classList.add('d-none');
        fileIcon.classList.remove('d-none');
      }

      fileInput.value = '';
    });

    el('attach-remove-btn')?.addEventListener('click', clearAttachment);
  }

  function clearAttachment() {
    pendingFile = null;
    el('attach-preview')?.classList.add('d-none');
    el('attach-preview-img').src = '';
  }

  /* ══════════════════════════════════════════════════════════
     REPLY
  ══════════════════════════════════════════════════════════ */
  function bindReplyCancel() {
    el('reply-preview-close')?.addEventListener('click', clearReply);
  }

  function clearReply() {
    replyToId = null;
    el('forum-reply-preview')?.classList.add('d-none');
    el('reply-preview-name').textContent    = '';
    el('reply-preview-content').textContent = '';
  }

  /* ══════════════════════════════════════════════════════════
     PANEL MODERACIÓN
  ══════════════════════════════════════════════════════════ */
  async function openModPanel() {
    const modal = new bootstrap.Modal(el('moderationModal'));
    modal.show();

    // Mostrar sección de invitar solo si es foro privado
    const inviteWrapper = el('invite-collab-wrapper');
    if (inviteWrapper) {
       inviteWrapper.style.setProperty('display', forumPrivacy === 'private' ? 'flex' : 'none', 'important');
    }

    if (forumPrivacy === 'private') {
        await loadCollaboratorsList(); // Fetch current collabs first
        await loadFriendsForInvite();  // Fetch friends and populate exclude list
    }
    await loadBannedList();
  }

  let currentCollaboratorsIds = []; // Stores IDs to exclude from friend invite list

  async function loadCollaboratorsList() {
    const container = el('collaborators-list-container');
    if (!container) return;
    container.innerHTML = '<p class="text-muted small">Cargando...</p>';

    try {
      const res  = await fetch(`${BASE}/api/php/forums.php?action=get_collaborators&forum_id=${FORUM_ID}`);
      const data = await res.json();
      if (!data.success) { container.innerHTML = '<p class="text-danger small">Error al cargar</p>'; return; }

      const collaborators = data.collaborators;
      currentCollaboratorsIds = collaborators.map(c => parseInt(c.user_id));

      if (!collaborators || !collaborators.length) {
        container.innerHTML = '<p class="text-muted small">No hay colaboradores en este foro.</p>';
        return;
      }

      container.innerHTML = collaborators.map(c => `
        <div class="banned-user-row d-flex align-items-center justify-content-between gap-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <img src="${avatarUrl(c.profile_image, c.username)}" class="banned-avatar" alt="${esc(c.username)}"
                 onerror="this.src='${avatarFallback(c.username)}'">
            <span class="fw-semibold">${esc(c.username)}</span>
          </div>
          <button class="btn btn-sm btn-outline-danger rounded-pill remove-collab-btn" data-user-id="${c.user_id}" data-username="${esc(c.username)}">
            <i class="fa-solid fa-user-minus me-1"></i>Expulsar
          </button>
        </div>
      `).join('');

      // Bind expulsar colaborador
      document.querySelectorAll('.remove-collab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          pendingRemoveCollabName = btn.dataset.username;
          pendingRemoveCollabId = btn.dataset.userId;
          el('remove-collab-name').textContent = pendingRemoveCollabName;
          new bootstrap.Modal(el('removeCollabModal')).show();
        });
      });

    } catch (e) {
      container.innerHTML = '<p class="text-danger small">Error de red</p>';
    }
  }

  async function loadFriendsForInvite() {
    const selectEl = el('invite-collab-select');
    const btn = el('invite-collab-btn');
    if (!selectEl || !btn) return;
    
    selectEl.innerHTML = '<option value="">Cargando amigos...</option>';
    selectEl.disabled = true;
    btn.disabled = true;

    try {
      const res = await fetch(`${BASE}/api/php/forums.php?action=get_friends`);
      const data = await res.json();
      if (!data.success || !Array.isArray(data.friends)) {
          selectEl.innerHTML = '<option value="">Error cargando amigos</option>';
          return;
      }

      // Filter out those who are already collaborators
      const availableFriends = data.friends.filter(f => !currentCollaboratorsIds.includes(parseInt(f.id)));

      if (availableFriends.length === 0) {
          selectEl.innerHTML = '<option value="">No hay amigos disponibles para invitar</option>';
          return;
      }

      selectEl.innerHTML = '<option value="">Selecciona un amigo...</option>';
      availableFriends.forEach(f => {
          selectEl.innerHTML += `<option value="${f.id}">${esc(f.username)}</option>`;
      });

      selectEl.disabled = false;
      btn.disabled = false;

      // Bind the invite button only once
      if (!btn.dataset.bound) {
          btn.dataset.bound = '1';
          btn.addEventListener('click', async () => {
             const targetId = selectEl.value;
             if (!targetId) {
                 showToast('Selecciona un amigo primero', 'error');
                 return;
             }

             const fd = new FormData();
             fd.append('forum_id', FORUM_ID);
             fd.append('target_user_id', targetId);

             btn.disabled = true;
             const originalHtml = btn.innerHTML;
             btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

             try {
                const iRes = await fetch(`${BASE}/api/php/forums.php?action=invite_collaborator`, { method: 'POST', body: fd });
                const iData = await iRes.json();
                if (iData.success) {
                   showToast(iData.message || 'Invitación enviada', 'success');
                   await loadFriendsForInvite(); // Reload strictly to remove them from dropdown
                } else {
                   showToast(iData.error || 'Error al invitar', 'error');
                }
             } catch(err) {
                 showToast('Error de red al invitar', 'error');
             } finally {
                 btn.disabled = false;
                 btn.innerHTML = originalHtml;
             }
          });
      }

    } catch (e) {
      selectEl.innerHTML = '<option value="">Error de red</option>';
    }
  }

  async function loadBannedList() {
    const container = el('banned-list-container');
    if (!container) return;
    container.innerHTML = '<p class="text-muted small">Cargando...</p>';

    try {
      const res  = await fetch(`${BASE}/api/php/forums.php?action=get_banned&forum_id=${FORUM_ID}`);
      const data = await res.json();
      if (!data.success) { container.innerHTML = '<p class="text-danger small">Error al cargar</p>'; return; }

      const banned = data.banned;
      if (!banned.length) {
        container.innerHTML = '<p class="text-muted small">No hay usuarios baneados.</p>';
        return;
      }

      container.innerHTML = banned.map(b => `
        <div class="banned-user-row d-flex align-items-center justify-content-between gap-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <img src="${avatarUrl(b.profile_image, b.username)}" class="banned-avatar" alt="${esc(b.username)}"
                 onerror="this.src='${avatarFallback(b.username)}'">
            <span class="fw-semibold">${esc(b.username)}</span>
          </div>
          <button class="btn btn-sm btn-outline-success rounded-pill unban-btn" data-user-id="${b.user_id}" data-username="${esc(b.username)}">
            <i class="fa-solid fa-user-check me-1"></i>Desbanear
          </button>
        </div>
      `).join('');

      // Bind desbanear
      document.querySelectorAll('.unban-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
          const fd = new FormData();
          fd.append('forum_id',       FORUM_ID);
          fd.append('target_user_id', btn.dataset.userId);
          const res  = await fetch(`${BASE}/api/php/forums.php?action=unban_user`, { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success) {
            showToast(`"${btn.dataset.username}" desbaneado`, 'success');
            await loadBannedList();
          }
        });
      });

    } catch (e) {
      container.innerHTML = '<p class="text-danger small">Error de red</p>';
    }
  }

  /* ══════════════════════════════════════════════════════════
     POLLING
  ══════════════════════════════════════════════════════════ */
  function startPolling() {
    pollTimer = setInterval(() => loadMessages(false), POLL_MS);
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        clearInterval(pollTimer);
      } else {
        loadMessages(false);
        pollTimer = setInterval(() => loadMessages(false), POLL_MS);
      }
    });
  }

  /* ══════════════════════════════════════════════════════════
     RATE-LIMIT COUNTDOWN
  ══════════════════════════════════════════════════════════ */
  function startRateLimitCountdown(seconds) {
    rateLimitEnd = Date.now() + seconds * 1000;
    const bar    = el('forum-ratelimit');
    const secEl  = el('forum-ratelimit-seconds');
    const sendBtn = el('forum-send-btn');
    bar.classList.remove('d-none');
    sendBtn.disabled = true;

    clearInterval(rateLimitInterval);
    rateLimitInterval = setInterval(() => {
      const rem = Math.ceil((rateLimitEnd - Date.now()) / 1000);
      if (rem <= 0) {
        clearInterval(rateLimitInterval);
        bar.classList.add('d-none');
        sendBtn.disabled = false;
        return;
      }
      secEl.textContent = rem;
    }, 500);
  }

  /* ══════════════════════════════════════════════════════════
     UTILIDADES
  ══════════════════════════════════════════════════════════ */
  function disableInput(msg) {
    const area = el('forum-input-area');
    if (!area) return;
    area.innerHTML = `<div class="forum-input-disabled">${msg}</div>`;
  }

  function isAtBottom() {
    const area = el('forum-messages-area');
    return area ? (area.scrollHeight - area.scrollTop - area.clientHeight < 80) : false;
  }

  function scrollBottom() {
    const area = el('forum-messages-area');
    if (area) area.scrollTop = area.scrollHeight;
  }

  function formatTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const now = new Date();
    const sameDay = d.toDateString() === now.toDateString();
    return sameDay
      ? d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })
      : d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' }) + ' ' +
        d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
  }

  function avatarUrl(img, username) {
    const fb = avatarFallback(username);
    if (!img) return fb;
    if (img.startsWith('http')) return img;
    return `${SUP_URL}/storage/v1/object/public/avatars/${img}`;
  }

  function avatarFallback(username) {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(username || '?')}&background=10b981&color=fff&size=64`;
  }

  function esc(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function linkify(text) {
    return text.replace(/(https?:\/\/[^\s<>"']+)/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>');
  }

  /* ── Toast simple ─────────────────────────────────────────── */
  function showToast(msg, type = 'info') {
    const t = document.createElement('div');
    t.className = `forum-toast forum-toast-${type}`;
    t.textContent = msg;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 300);
    }, 3500);
  }

  /* ══════════════════════════════════════════════════════════
     ARRANQUE
  ══════════════════════════════════════════════════════════ */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
