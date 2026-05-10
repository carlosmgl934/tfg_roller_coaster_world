/* ================================================================
   admin_forums.js — Panel de Administración de Foros
   RollerCoaster World
   ================================================================ */
(function () {
'use strict';

const BASE         = window.BASE_URL;
const API_FORUMS   = BASE + '/api/php/forums.php';
const API_ADMIN    = BASE + '/api/php/admin/admin_forums.php';

/* ── Estado global ────────────────────────────────────────────── */
let allForums        = [];
let currentForumId   = null;
let currentForumTitle= '';
let currentForumAuthorId = null;
let allMessages      = [];
let showOnlyHidden   = false;
let searchDebounce   = null;

/* ── Modales Bootstrap ────────────────────────────────────────── */
let modalDetail      = null;
let modalDelForum    = null;
let modalDelMsg      = null;
let modalUnban       = null;
let modalRemCollab   = null;
let modalBanOwner    = null;

/* ═══════════════════════════════════════════════════════════════
   INICIALIZACIÓN
═══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    modalDetail    = new bootstrap.Modal(document.getElementById('modal-forum-detail'));
    modalDelForum  = new bootstrap.Modal(document.getElementById('modal-delete-forum'));
    modalDelMsg    = new bootstrap.Modal(document.getElementById('modal-delete-message'));
    modalUnban     = new bootstrap.Modal(document.getElementById('modal-unban'));
    modalRemCollab = new bootstrap.Modal(document.getElementById('modal-remove-collab'));
    modalBanOwner  = new bootstrap.Modal(document.getElementById('modal-ban-owner'));

    // Filtros y búsqueda
    document.getElementById('btn-forums-filtrar').addEventListener('click', loadForums);
    document.getElementById('btn-forums-borrar').addEventListener('click', clearFilters);
    document.getElementById('forums-search').addEventListener('input', () => {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(loadForums, 350);
    });

    // Tabs del modal
    document.querySelectorAll('#forum-detail-tabs .nav-link').forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.tab));
    });

    // Toggle ocultos
    document.getElementById('filter-show-hidden').addEventListener('change', e => {
        showOnlyHidden = e.target.checked;
        renderMessages();
    });

    // Confirmar eliminar foro
    document.getElementById('confirm-delete-forum').addEventListener('click', confirmDeleteForum);

    // Confirmar eliminar mensaje
    document.getElementById('confirm-delete-msg').addEventListener('click', confirmDeleteMessage);

    // Confirmar desbanear
    document.getElementById('confirm-unban').addEventListener('click', confirmUnban);

    // Confirmar quitar colaborador
    document.getElementById('confirm-remove-collab').addEventListener('click', confirmRemoveCollab);

    // Confirmar banear al creador (elimina el foro)
    document.getElementById('confirm-ban-owner').addEventListener('click', () => {
        modalBanOwner.hide();
        confirmDeleteForum();
    });

    // Botón eliminar foro desde detalle
    document.getElementById('btn-delete-forum-from-detail').addEventListener('click', () => {
        openDeleteForum(currentForumId, currentForumTitle);
    });

    loadForums();
});

/* ═══════════════════════════════════════════════════════════════
   CARGAR LISTA DE FOROS
═══════════════════════════════════════════════════════════════ */
async function loadForums() {
    const search  = document.getElementById('forums-search').value.trim();
    const privacy = document.getElementById('filter-privacy').value;

    const list = document.getElementById('admin-forums-list');
    list.innerHTML = `<div class="list-group-item text-center text-muted py-5">
        <i class="fa-solid fa-spinner fa-spin me-2 text-success"></i>Cargando foros...
    </div>`;

    let url = `${API_ADMIN}?action=list_forums`;
    if (search)  url += `&search=${encodeURIComponent(search)}`;
    if (privacy) url += `&privacy=${encodeURIComponent(privacy)}`;

    try {
        const resp = await fetch(url, { 
                credentials: 'same-origin' });
        const json = await resp.json();

        if (!json.success) throw new Error(json.error || 'Error');

        allForums = json.forums || [];
        renderForumList();
        updateStats();
    } catch (e) {
        list.innerHTML = `<div class="list-group-item text-danger py-4 text-center">
            <i class="fa-solid fa-circle-exclamation me-2"></i>${e.message}
        </div>`;
    }
}

function clearFilters() {
    document.getElementById('forums-search').value  = '';
    document.getElementById('filter-privacy').value = '';
    loadForums();
}

/* ── Renderizar lista ─────────────────────────────────────────── */
function renderForumList() {
    const list = document.getElementById('admin-forums-list');
    document.getElementById('forums-count').textContent =
        `${allForums.length} foro${allForums.length !== 1 ? 's' : ''} encontrado${allForums.length !== 1 ? 's' : ''}`;
    document.getElementById('forums-count-label').textContent =
        `${allForums.length} foros`;

    if (allForums.length === 0) {
        list.innerHTML = `<div class="list-group-item text-center text-muted py-5">
            <i class="fa-solid fa-comments fa-2x mb-2 d-block text-success"></i>
            No hay foros que coincidan con los filtros.
        </div>`;
        return;
    }

    list.innerHTML = allForums.map(f => {
        const privBadge = f.privacy === 'public'
            ? `<span class="badge bg-primary rounded-0 me-1">Público</span>`
            : `<span class="badge bg-secondary rounded-0 me-1">Privado</span>`;

        const date = new Date(f.created_at).toLocaleDateString('es-ES', {
            day: '2-digit', month: 'short', year: 'numeric'
        });

        return `
        <div class="list-group-item list-group-item-action rounded-0 py-3 px-3" data-forum-id="${f.id}">
            <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        ${privBadge}
                        <span class="fw-bold text-white">${escHtml(f.title)}</span>
                    </div>
                    <p class="text-muted small mb-1 text-truncate" style="max-width:500px;">${escHtml(f.forum_subject)}</p>
                    <div class="d-flex flex-wrap gap-3 small text-muted">
                        <span><i class="fa-solid fa-user me-1 text-success"></i>${escHtml(f.author_name)}</span>
                        <span><i class="fa-solid fa-calendar me-1"></i>${date}</span>
                        <span><i class="fa-solid fa-message me-1 text-info"></i>${f.msg_count} msg</span>
                        ${f.hidden_count > 0 ? `<span class="text-warning"><i class="fa-solid fa-eye-slash me-1"></i>${f.hidden_count} ocultos</span>` : ''}
                        <span><i class="fa-solid fa-user-check me-1 text-primary"></i>${f.collab_count} colabs</span>
                        ${f.ban_count > 0 ? `<span class="text-danger"><i class="fa-solid fa-ban me-1"></i>${f.ban_count} bans</span>` : ''}
                    </div>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button class="btn btn-sm btn-success rounded-0 fw-bold"
                            onclick="openForumDetail(${f.id}, '${escHtml(f.title).replace(/'/g,"\\'")}', '${f.privacy}', ${f.author_id})">
                        <i class="fa-solid fa-eye me-1"></i>Gestionar
                    </button>
                    <button class="btn btn-sm btn-danger rounded-0"
                            onclick="openDeleteForum(${f.id}, '${escHtml(f.title).replace(/'/g,"\\'")}')">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    }).join('');
}

/* ── Actualizar stats sidebar ─────────────────────────────────── */
function updateStats() {
    const total   = allForums.length;
    const pub     = allForums.filter(f => f.privacy === 'public').length;
    const priv    = allForums.filter(f => f.privacy === 'private').length;
    const msgs    = allForums.reduce((s, f) => s + (+f.msg_count), 0);
    const hidden  = allForums.reduce((s, f) => s + (+f.hidden_count), 0);
    const bans    = allForums.reduce((s, f) => s + (+f.ban_count), 0);

    document.getElementById('stat-total').textContent   = total;
    document.getElementById('stat-public').textContent  = pub;
    document.getElementById('stat-private').textContent = priv;
    document.getElementById('stat-msgs').textContent    = msgs;
    document.getElementById('stat-hidden').textContent  = hidden;
    document.getElementById('stat-bans').textContent    = bans;
}

/* ═══════════════════════════════════════════════════════════════
   MODAL DETALLE DEL FORO
═══════════════════════════════════════════════════════════════ */
async function openForumDetail(forumId, title, privacy, authorId) {
    currentForumId       = forumId;
    currentForumTitle    = title;
    currentForumAuthorId = parseInt(authorId, 10) || null;

    document.getElementById('forum-detail-title').textContent = title;
    const privBadge = document.getElementById('forum-detail-privacy-badge');
    privBadge.textContent  = privacy === 'public' ? 'Público' : 'Privado';
    privBadge.className    = `badge ms-2 ${privacy === 'public' ? 'bg-primary' : 'bg-secondary'}`;

    // Resetear tabs
    switchTab('messages');
    document.getElementById('filter-show-hidden').checked = false;
    showOnlyHidden = false;

    modalDetail.show();

    // Cargar las 4 secciones en paralelo
    await Promise.all([
        fetchMessages(forumId),
        fetchCollaborators(forumId),
        fetchBanned(forumId),
        fetchParticipants(forumId),
    ]);
}

/* ── Cambiar tab ──────────────────────────────────────────────── */
function switchTab(tab) {
    ['messages', 'collaborators', 'banned'].forEach(t => {
        const panel = document.getElementById(`tab-${t}`);
        const btn   = document.querySelector(`[data-tab="${t}"]`);
        const isActive = t === tab;
        panel?.classList.toggle('d-none', !isActive);
        btn?.classList.toggle('active', isActive);
        if (btn) {
            if (isActive) {
                btn.style.color      = '#e6edf3';
                btn.style.background = '#1c2128';
                btn.style.borderColor= '#30363d #30363d #1c2128';
            } else {
                btn.style.color      = '#94a3b8';
                btn.style.background = 'transparent';
                btn.style.borderColor= 'transparent';
            }
        }
    });
}

/* ═══════════════════════════════════════════════════════════════
   MENSAJES
═══════════════════════════════════════════════════════════════ */
async function fetchMessages(forumId) {
    document.getElementById('messages-list').innerHTML =
        `<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando...</div>`;
    try {
        const resp = await fetch(`${API_FORUMS}?action=get_messages&forum_id=${forumId}&limit=100`, { credentials: 'same-origin' });
        const json = await resp.json();
        allMessages = json.messages || [];
        document.getElementById('tab-badge-msgs').textContent = allMessages.length;
        renderMessages();
    } catch {
        document.getElementById('messages-list').innerHTML =
            `<div class="text-danger py-3 text-center small">Error cargando mensajes</div>`;
    }
}

function renderMessages() {
    const list = document.getElementById('messages-list');
    const msgs = showOnlyHidden ? allMessages.filter(m => m.is_hidden) : allMessages;

    if (msgs.length === 0) {
        list.innerHTML = `<div class="text-center text-muted py-4 small">
            <i class="fa-solid fa-inbox fa-2x mb-2 d-block text-success"></i>
            ${showOnlyHidden ? 'No hay mensajes ocultos.' : 'No hay mensajes en este foro.'}
        </div>`;
        return;
    }

    list.innerHTML = msgs.map(m => {
        const date = new Date(m.created_at).toLocaleString('es-ES', {
            day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
        });
        const hiddenClass = m.is_hidden ? 'opacity-50 border-warning' : '';
        const hiddenLabel = m.is_hidden
            ? `<span class="badge bg-warning text-dark rounded-0 ms-1"><i class="fa-solid fa-eye-slash me-1"></i>Oculto</span>`
            : '';

        // ── Adjunto ───────────────────────────────────────────────
        let attachmentHtml = '';
        if (m.attachment_url) {
            const url      = escHtml(m.attachment_url);
            const fileName = escHtml(m.file_name || 'archivo');
            const isImage  = /\.(jpe?g|png|gif|webp|avif|svg)(\?|$)/i.test(m.attachment_url);
            if (isImage) {
                attachmentHtml = `
                <a href="${url}" target="_blank" rel="noopener" title="Ver imagen completa" class="d-inline-block mt-2">
                    <img src="${url}" alt="${fileName}"
                         style="max-height:120px;max-width:100%;border:1px solid #30363d;object-fit:cover;
                                cursor:zoom-in;transition:opacity .2s;"
                         onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
                </a>`;
            } else {
                attachmentHtml = `
                <a href="${url}" target="_blank" rel="noopener"
                   class="d-inline-flex align-items-center gap-1 mt-2 text-info small"
                   style="text-decoration:none;" title="Descargar ${fileName}">
                    <i class="fa-solid fa-file-arrow-down"></i>
                    <span style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${fileName}</span>
                </a>`;
            }
        }

        return `
        <div class="card rounded-0 mb-2 ${hiddenClass}" style="background:#0d1117;border:1px solid #30363d;" id="msg-card-${m.id}">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="fw-bold small text-success">${escHtml(m.username)}</span>
                            <span class="text-muted" style="font-size:.7rem;">${date}</span>
                            ${hiddenLabel}
                        </div>
                        ${m.content ? `<p class="mb-0 small" style="color:#e6edf3;word-break:break-word;">${escHtml(m.content).substring(0, 300)}${m.content.length > 300 ? '…' : ''}</p>` : ''}
                        ${attachmentHtml}
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button class="btn btn-sm admin-action-btn rounded-0" title="${m.is_hidden ? 'Mostrar' : 'Ocultar'}"
                                onclick="toggleHideMessage(${m.id}, ${m.is_hidden ? 0 : 1})">
                            <i class="fa-solid fa-eye${m.is_hidden ? '' : '-slash'}"></i>
                        </button>
                        <button class="btn btn-sm admin-action-btn admin-action-del rounded-0" title="Eliminar"
                                onclick="openDeleteMessage(${m.id}, '${escHtml(m.content || '').substring(0,80).replace(/'/g,"\\'")}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');
}

/* ── Ocultar / mostrar mensaje ────────────────────────────────── */
async function toggleHideMessage(msgId, hidden) {
    try {
        const fd = new FormData();
        fd.append('message_id', msgId);
        fd.append('hidden', hidden);
        const resp = await fetch(`${API_FORUMS}?action=hide_message`, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
            method: 'POST', credentials: 'same-origin', body: fd
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || 'Error');
        await fetchMessages(currentForumId);
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

/* ── Eliminar mensaje ─────────────────────────────────────────── */
let pendingDeleteMsgId = null;
function openDeleteMessage(msgId, preview) {
    pendingDeleteMsgId = msgId;
    document.getElementById('delete-msg-preview').textContent = preview || '(sin contenido)';
    modalDelMsg.show();
}
async function confirmDeleteMessage() {
    if (!pendingDeleteMsgId) return;
    try {
        const fd = new FormData();
        fd.append('message_id', pendingDeleteMsgId);
        const resp = await fetch(`${API_FORUMS}?action=delete_message`, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
            method: 'POST', credentials: 'same-origin', body: fd
        } );
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || 'Error');
        modalDelMsg.hide();
        await Promise.all([
            fetchMessages(currentForumId),
            fetchParticipants(currentForumId)
        ]);
        // actualizar stat en lista
        const f = allForums.find(f => f.id == currentForumId);
        if (f) { f.msg_count = Math.max(0, (+f.msg_count) - 1); updateStats(); renderForumList(); }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        pendingDeleteMsgId = null;
    }
}

/* ═══════════════════════════════════════════════════════════════
   COLABORADORES
═══════════════════════════════════════════════════════════════ */
async function fetchCollaborators(forumId) {
    document.getElementById('collaborators-list').innerHTML =
        `<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando...</div>`;
    try {
        const resp = await fetch(`${API_FORUMS}?action=get_collaborators&forum_id=${forumId}`, { 
                credentials: 'same-origin' });
        const json = await resp.json();
        const collabs = json.collaborators || [];
        document.getElementById('tab-badge-collabs').textContent = collabs.length;
        renderCollaborators(collabs, forumId);
    } catch {
        document.getElementById('collaborators-list').innerHTML =
            `<div class="text-danger py-3 text-center small">Error cargando colaboradores</div>`;
    }
}

function renderCollaborators(collabs, forumId) {
    const list = document.getElementById('collaborators-list');
    if (collabs.length === 0) {
        list.innerHTML = `<div class="text-center text-muted py-4 small">
            <i class="fa-solid fa-user-check fa-2x mb-2 d-block text-primary"></i>
            Este foro no tiene colaboradores.
        </div>`;
        return;
    }
    list.innerHTML = collabs.map(c => `
        <div class="d-flex align-items-center justify-content-between py-2 px-1 border-bottom" style="border-color:#30363d!important;">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-dark fw-bold"
                     style="width:32px;height:32px;font-size:.8rem;flex-shrink:0;">
                    ${escHtml(c.username).charAt(0).toUpperCase()}
                </div>
                <span class="small fw-bold text-white">${escHtml(c.username)}</span>
                <span class="text-muted small">#${c.user_id}</span>
            </div>
            <button class="btn btn-sm btn-warning rounded-0 text-dark fw-bold"
                    onclick="openRemoveCollab(${forumId}, ${c.user_id}, '${escHtml(c.username).replace(/'/g,"\\'")}')">
                <i class="fa-solid fa-user-minus me-1"></i>Quitar
            </button>
        </div>`).join('');
}

/* ── Quitar colaborador ───────────────────────────────────────── */
let pendingRemCollab = null;
function openRemoveCollab(forumId, userId, username) {
    pendingRemCollab = { forumId, userId };
    document.getElementById('remove-collab-username').textContent = username;
    modalRemCollab.show();
}
async function confirmRemoveCollab() {
    if (!pendingRemCollab) return;
    try {
        const fd = new FormData();
        fd.append('forum_id', pendingRemCollab.forumId);
        fd.append('target_user_id', pendingRemCollab.userId);
        const resp = await fetch(`${API_FORUMS}?action=remove_collaborator`, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
            method: 'POST', credentials: 'same-origin', body: fd
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || 'Error');
        modalRemCollab.hide();
        await fetchCollaborators(pendingRemCollab.forumId);
        const f = allForums.find(f => f.id == pendingRemCollab.forumId);
        if (f) { f.collab_count = Math.max(0, (+f.collab_count) - 1); updateStats(); renderForumList(); }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        pendingRemCollab = null;
    }
}

/* ═══════════════════════════════════════════════════════════════
   BANEADOS
═══════════════════════════════════════════════════════════════ */
async function fetchBanned(forumId) {
    document.getElementById('banned-list').innerHTML =
        `<div class="text-center text-muted py-4"><i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando...</div>`;
    try {
        const resp = await fetch(`${API_FORUMS}?action=get_banned&forum_id=${forumId}`, { 
                credentials: 'same-origin' });
        const json = await resp.json();
        const banned = json.banned || [];
        document.getElementById('tab-badge-bans').textContent = banned.length;
        renderBanned(banned, forumId);
    } catch {
        document.getElementById('banned-list').innerHTML =
            `<div class="text-danger py-3 text-center small">Error cargando baneados</div>`;
    }
}

/* ── Participantes del foro (para banear) ─────────────────────── */
async function fetchParticipants(forumId) {
    document.getElementById('participants-list').innerHTML =
        `<div class="text-center text-muted py-3 small"><i class="fa-solid fa-spinner fa-spin text-success me-2"></i>Cargando...</div>`;
    try {
        const resp = await fetch(`${API_FORUMS}?action=get_participants&forum_id=${forumId}`, { credentials: 'same-origin' });
        const json = await resp.json();
        const participants = json.participants || [];
        renderParticipants(participants, forumId);
    } catch {
        document.getElementById('participants-list').innerHTML =
            `<div class="text-danger py-3 text-center small">Error cargando participantes</div>`;
    }
}

function renderParticipants(participants, forumId) {
    const list = document.getElementById('participants-list');
    if (participants.length === 0) {
        list.innerHTML = `<div class="text-center text-muted py-3 small">
            <i class="fa-solid fa-ghost me-1"></i>Ningún usuario ha participado aún.
        </div>`;
        return;
    }
    list.innerHTML = participants.map(p => {
        const last = p.last_message_at
            ? new Date(p.last_message_at).toLocaleDateString('es-ES', { day:'2-digit', month:'short' })
            : '—';
        const isOwner = currentForumAuthorId && (+p.user_id === +currentForumAuthorId);
        const ownerBadge = isOwner
            ? `<span class="badge rounded-0 ms-1" style="background:#d4ac0d;color:#000;"><i class="fa-solid fa-crown me-1"></i>Creador</span>`
            : '';
        const banBtn = isOwner
            ? `<button class="btn btn-sm btn-danger rounded-0 fw-bold"
                    onclick="openBanUser(${forumId}, ${p.user_id}, '${escHtml(p.username).replace(/'/g,"\\'")}')"
                    title="Banear al creador eliminará el foro">
                <i class="fa-solid fa-ban me-1"></i>Banear
               </button>`
            : `<button class="btn btn-sm btn-danger rounded-0 fw-bold"
                    onclick="openBanUser(${forumId}, ${p.user_id}, '${escHtml(p.username).replace(/'/g,"\\'")}')"
                    title="Banear a ${escHtml(p.username)}">
                <i class="fa-solid fa-ban me-1"></i>Banear
               </button>`;
        return `
        <div class="d-flex align-items-center justify-content-between py-2 px-1 border-bottom" style="border-color:#30363d!important;">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                     style="width:32px;height:32px;font-size:.8rem;flex-shrink:0;
                            background:${isOwner ? '#d4ac0d' : '#198754'};color:${isOwner ? '#000' : '#fff'};">
                    ${isOwner ? '<i class="fa-solid fa-crown" style="font-size:.75rem;"></i>' : escHtml(p.username).charAt(0).toUpperCase()}
                </div>
                <div>
                    <div class="small fw-bold text-white">${escHtml(p.username)}${ownerBadge}</div>
                    <div class="text-muted" style="font-size:.7rem;">
                        <i class="fa-solid fa-message me-1"></i>${p.message_count} msg · último: ${last}
                    </div>
                </div>
            </div>
            ${banBtn}
        </div>`;
    }).join('');
}

async function openBanUser(forumId, userId, username) {
    const isOwner = currentForumAuthorId && (+userId === +currentForumAuthorId);

    if (isOwner) {
        // Mostrar modal de advertencia: banear creador = eliminar foro
        pendingDeleteForumId = forumId;
        document.getElementById('ban-owner-username').textContent   = username;
        document.getElementById('ban-owner-forum-title').textContent = currentForumTitle;
        document.getElementById('delete-forum-title').textContent    = currentForumTitle;
        modalBanOwner.show();
        return;
    }

    if (!confirm(`¿Banear a "${username}" de este foro?`)) return;
    try {
        const fd = new FormData();
        fd.append('forum_id', forumId);
        fd.append('target_user_id', userId);
        const resp = await fetch(`${API_FORUMS}?action=ban_user`, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
            method: 'POST', credentials: 'same-origin', body: fd
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || 'Error');
        await Promise.all([
            fetchMessages(forumId),
            fetchParticipants(forumId),
            fetchBanned(forumId),
        ]);
        const f = allForums.find(f => f.id == forumId);
        if (f) { f.ban_count = (+f.ban_count) + 1; updateStats(); renderForumList(); }
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

function renderBanned(banned, forumId) {
    const list = document.getElementById('banned-list');
    if (banned.length === 0) {
        list.innerHTML = `<div class="text-center text-muted py-4 small">
            <i class="fa-solid fa-ban fa-2x mb-2 d-block text-danger"></i>
            No hay usuarios baneados en este foro.
        </div>`;
        return;
    }
    list.innerHTML = banned.map(b => {
        const date = new Date(b.banned_at).toLocaleDateString('es-ES', {
            day: '2-digit', month: 'short', year: 'numeric'
        });
        return `
        <div class="d-flex align-items-center justify-content-between py-2 px-1 border-bottom" style="border-color:#30363d!important;">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-danger d-flex align-items-center justify-content-center text-white fw-bold"
                     style="width:32px;height:32px;font-size:.8rem;flex-shrink:0;">
                    ${escHtml(b.username).charAt(0).toUpperCase()}
                </div>
                <div>
                    <div class="small fw-bold text-white">${escHtml(b.username)}</div>
                    <div class="text-muted" style="font-size:.7rem;">Baneado: ${date}</div>
                </div>
            </div>
            <button class="btn btn-sm btn-warning rounded-0 text-dark fw-bold"
                    onclick="openUnban(${forumId}, ${b.user_id}, '${escHtml(b.username).replace(/'/g,"\\'")}')">
                <i class="fa-solid fa-user-check me-1"></i>Desbanear
            </button>
        </div>`;
    }).join('');
}

/* ── Desbanear ────────────────────────────────────────────────── */
let pendingUnban = null;
function openUnban(forumId, userId, username) {
    pendingUnban = { forumId, userId };
    document.getElementById('unban-username').textContent = username;
    modalUnban.show();
}
async function confirmUnban() {
    if (!pendingUnban) return;
    try {
        const fd = new FormData();
        fd.append('forum_id', pendingUnban.forumId);
        fd.append('target_user_id', pendingUnban.userId);
        const resp = await fetch(`${API_FORUMS}?action=unban_user`, { 
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
            method: 'POST', credentials: 'same-origin', body: fd
        } );
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || 'Error');
        modalUnban.hide();
        // Refrescar baneados y participantes
        await Promise.all([
            fetchBanned(pendingUnban.forumId),
            fetchParticipants(pendingUnban.forumId),
        ]);
        const f = allForums.find(f => f.id == pendingUnban.forumId);
        if (f) { f.ban_count = Math.max(0, (+f.ban_count) - 1); updateStats(); renderForumList(); }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        pendingUnban = null;
    }
}


/* ═══════════════════════════════════════════════════════════════
   ELIMINAR FORO
═══════════════════════════════════════════════════════════════ */
let pendingDeleteForumId = null;
function openDeleteForum(forumId, title) {
    pendingDeleteForumId = forumId;
    document.getElementById('delete-forum-title').textContent = title;
    modalDelForum.show();
}
async function confirmDeleteForum() {
    if (!pendingDeleteForumId) return;
    try {
        const resp = await fetch(`${API_ADMIN}?action=delete_forum`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '', 'Content-Type': 'application/json' },
            body: JSON.stringify({ forum_id: pendingDeleteForumId }),
        });
        const json = await resp.json();
        if (!json.success) throw new Error(json.error || 'Error');
        modalDelForum.hide();
        modalDetail.hide();
        allForums = allForums.filter(f => f.id != pendingDeleteForumId);
        renderForumList();
        updateStats();
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        pendingDeleteForumId = null;
    }
}

/* ═══════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════ */
function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

/* ── Exponer funciones usadas en onclick ─────────────────────── */
window.loadForums        = loadForums;
window.openForumDetail   = openForumDetail;
window.openDeleteForum   = openDeleteForum;
window.openDeleteMessage = openDeleteMessage;
window.openRemoveCollab  = openRemoveCollab;
window.openUnban         = openUnban;
window.toggleHideMessage = toggleHideMessage;
window.openBanUser       = openBanUser;

})(); // fin IIFE
