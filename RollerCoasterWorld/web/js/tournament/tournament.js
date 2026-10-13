/**
 * tournament.js — Roller Coaster Euro Cup
 * ─────────────────────────────────────────
 * Lógica completa del frontend del torneo.
 * Independiente — se borrará al acabar el evento.
 */
(function () {
    'use strict';

    const API = `${window.BASE_URL}/api/php/tournament.php`;
    const REFRESH_INTERVAL = 30000; // Refrescar partidos activos cada 30s
    const IS_LOGGED = window.TOURNAMENT_IS_LOGGED === true;

    let _tournamentData = null;
    let _countdownTimers = [];
    let _refreshTimer = null;

    /* ═══════════════════════════════════════════════════════════
       INIT
       ═══════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', () => {
        initTabs();
        loadTournament();
        loadSettings();
        initShareButton();

        // Auto-refresh de partidos activos
        _refreshTimer = setInterval(() => {
            const activeTab = document.querySelector('.tournament-nav-btn.active');
            if (activeTab && activeTab.dataset.tab === 'matches') {
                loadActiveMatches();
                loadRecentMatches();
            }
        }, REFRESH_INTERVAL);
    });

    /* ═══════════════════════════════════════════════════════════
       TABS
       ═══════════════════════════════════════════════════════════ */
    function initTabs() {
        document.querySelectorAll('.tournament-nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tournament-nav-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tournament-tab').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                const tab = document.getElementById(`tab-${btn.dataset.tab}`);
                if (tab) tab.classList.add('active');

                // Load data for specific tabs
                if (btn.dataset.tab === 'groups') loadGroups();
                if (btn.dataset.tab === 'bracket') loadBracket();
                if (btn.dataset.tab === 'jornadas') loadJornadas();
                if (btn.dataset.tab === 'admin') loadAdminData();
                if (btn.dataset.tab === 'matches') loadActiveMatches();
            });
        });

        // Event listeners for Calendario filters
        document.querySelectorAll('.tournament-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tournament-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                renderCalendarioPhase(btn.dataset.phase);
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       DATA LOADING
       ═══════════════════════════════════════════════════════════ */
    async function apiFetch(action, options = {}) {
        const method = options.method || 'GET';
        const url = `${API}?action=${action}${options.params || ''}`;
        
        const fetchOpts = { method, headers: {} };
        if (method !== 'GET') {
            fetchOpts.headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        if (options.body) {
            fetchOpts.headers['Content-Type'] = 'application/json';
            fetchOpts.body = JSON.stringify(options.body);
        }
        
        const res = await fetch(url, fetchOpts);
        return res.json();
    }

    async function loadTournament() {
    try {
        const data = await apiFetch('get_tournament');
        if (data.success) {
            _tournamentData = data;
            renderActiveMatches(data.active_matches || []);
            renderRecentMatches(data.recent_matches || []);
            loadJornadas();
        }
    } catch (e) {
        console.error('Error cargando torneo:', e);
    }
}

    async function loadActiveMatches() {
        try {
            const data = await apiFetch('get_active_matches');
            if (data.success) {
                renderActiveMatches(data.matches || []);
            }
        } catch (e) {
            console.error('Error cargando partidos:', e);
        }
    }

    async function loadGroups() {
        try {
            const data = await apiFetch('get_groups');
            if (data.success) {
                renderGroups(data.groups || {});
            }
        } catch (e) {
            console.error('Error cargando grupos:', e);
        }
    }

    async function loadBracket() {
        try {
            const data = await apiFetch('get_bracket');
            if (data.success) {
                renderBracket(data.bracket || {});
            }
        } catch (e) {
            console.error('Error cargando cuadro:', e);
        }
    }

    /* ═══════════════════════════════════════════════════════════
       RENDER: PARTIDOS ACTIVOS
       ═══════════════════════════════════════════════════════════ */
    function renderActiveMatches(matches) {
        clearCountdowns();
        const container = document.getElementById('active-matches-container');
        if (!container) return;

        if (!matches.length) {
            container.innerHTML = `
                <div class="tournament-empty">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <p data-i18n="tournament.empty_active">No hay partidos activos en este momento</p>
                </div>`;
            if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
            return;
        }

        container.innerHTML = matches.map(m => renderMatchCard(m, true)).join('');
        if (window.rcwI18n) window.rcwI18n.applyToContainer(container);

        matches.forEach(m => {
            if (m.status === 'active' && m.ends_at) startCountdown(m.id, m.ends_at);
        });

        // Bind vote buttons
        container.querySelectorAll('.tournament-vote-btn[data-require-login]').forEach(btn => {
            btn.addEventListener('click', () => showLoginModal());
        });
        container.querySelectorAll('.tournament-vote-btn[data-match]').forEach(btn => {
            btn.addEventListener('click', () => handleVote(btn));
        });
    }

    async function loadRecentMatches() {
    try {
        const data = await apiFetch('get_recent_matches');
        if (data.success) {
            renderRecentMatches(data.matches || []);
        }
    } catch (e) {
        console.error('Error cargando resultados recientes:', e);
    }
}

function renderRecentMatches(matches) {
    const container = document.getElementById('recent-matches-container');
    if (!container) return;

    if (!matches.length) {
        container.innerHTML = `
            <div class="tournament-empty">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <p data-i18n="tournament.empty_finished">Los resultados recientes aparecerán aquí</p>
            </div>`;
        if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
        return;
    }

    container.innerHTML = matches.map(m => renderMatchCard(m, false)).join('');
    if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
}

   function renderMatchCard(m, isActive) {
    const total = (m.votes_coaster1 || 0) + (m.votes_coaster2 || 0);
    const pct1 = total > 0 ? Math.round((m.votes_coaster1 / total) * 100) : 50;
    const pct2 = total > 0 ? 100 - pct1 : 50;

    const phaseLabel = getPhaseLabel(m.phase, m.group_letter);
    const isFinished = m.status === 'finished';
    const userVote = m.user_vote || null;

    const c1Winner = isFinished && m.winner_id == m.coaster1_id;
    const c2Winner = isFinished && m.winner_id == m.coaster2_id;
    const isDraw = isFinished && !m.winner_id;

    const c1Class = isFinished
        ? (isDraw ? 'draw' : (c1Winner ? 'winner' : 'loser'))
        : (userVote == m.coaster1_id ? 'voted' : '');
    const c2Class = isFinished
        ? (isDraw ? 'draw' : (c2Winner ? 'winner' : 'loser'))
        : (userVote == m.coaster2_id ? 'voted' : '');

    // "Tu voto" — independiente del resultado, solo se pinta si está finalizado
    const c1UserPick = isFinished && userVote == m.coaster1_id ? ' user-pick' : '';
    const c2UserPick = isFinished && userVote == m.coaster2_id ? ' user-pick' : '';
    
    const c1Flag = getCountryFlag(m.country1);
    const c2Flag = getCountryFlag(m.country2);
    const img1 = escapeHtml(m.img1 || '');
    const img2 = escapeHtml(m.img2 || '');
    const name1 = escapeHtml(m.name1);
    const name2 = escapeHtml(m.name2);
    const park1 = escapeHtml(m.park1 || '');
    const park2 = escapeHtml(m.park2 || '');

    const timerHtml = isActive && !isFinished
        ? `<span class="tournament-match-timer timer-active" id="timer-${m.id}"><i class="fa-solid fa-clock"></i> <span class="timer-text">--:--:--</span></span>`
        : isFinished
            ? `<span class="tournament-match-timer"><i class="fa-solid fa-flag-checkered"></i> <span data-i18n="tournament.status_finished">Finalizado</span></span>`
            : `<span class="tournament-match-timer"><i class="fa-solid fa-hourglass"></i> <span data-i18n="tournament.status_pending">Próximamente</span></span>`;

    return `
    <div class="tournament-match-card ${isActive && !isFinished ? 'match-active' : ''} ${isFinished ? 'match-finished' : ''}">
        <div class="tournament-match-header">
            <span class="tournament-match-phase">${phaseLabel}</span>
            ${timerHtml}
        </div>
        <div class="tournament-match-body">
            <!-- Coaster 1 -->
            <div class="tournament-match-coaster ${c1Class}${c1UserPick}">
                ${img1 ? `<img class="tournament-match-photo" src="${img1}" alt="${name1}" loading="lazy">` : ''}
                <div class="tournament-match-info">
                    <span class="tournament-match-flag">${c1Flag}</span>
                    <span class="tournament-match-name">${name1}</span>
                    <span class="tournament-match-park">${park1}</span>
                </div>
            </div>
            <!-- VS -->
            <div class="tournament-match-vs">
                <span class="tournament-vs-badge">VS</span>
            </div>
            <!-- Coaster 2 -->
            <div class="tournament-match-coaster ${c2Class}${c2UserPick}" style="text-align:right">
                ${img2 ? `<img class="tournament-match-photo" src="${img2}" alt="${name2}" loading="lazy">` : ''}
                <div class="tournament-match-info" style="align-items:flex-end">
                    <span class="tournament-match-flag">${c2Flag}</span>
                    <span class="tournament-match-name">${name2}</span>
                    <span class="tournament-match-park">${park2}</span>
                </div>
            </div>
        </div>
        ${isActive && !isFinished ? `
        <div class="tournament-match-votes">
            ${IS_LOGGED ? `
            <button class="tournament-vote-btn ${userVote == m.coaster1_id ? 'voted' : ''}"
                    data-match="${m.id}" data-coaster="${m.coaster1_id}"
                    ${userVote ? 'disabled' : ''}>
                ${userVote == m.coaster1_id ? '<i class="fa-solid fa-check"></i> <span data-i18n="tournament.vote_button_voted">Tu voto</span>' : '<i class="fa-solid fa-thumbs-up"></i> <span data-i18n="tournament.vote_button">Votar</span>'}
            </button>
            <div class="tournament-vote-vs-spacer"></div>
            <button class="tournament-vote-btn ${userVote == m.coaster2_id ? 'voted' : ''}"
                    data-match="${m.id}" data-coaster="${m.coaster2_id}"
                    ${userVote ? 'disabled' : ''}>
                ${userVote == m.coaster2_id ? '<i class="fa-solid fa-check"></i> <span data-i18n="tournament.vote_button_voted">Tu voto</span>' : '<i class="fa-solid fa-thumbs-up"></i> <span data-i18n="tournament.vote_button">Votar</span>'}
            </button>
            ` : `
            <button class="tournament-vote-btn" data-require-login="true">
                <i class="fa-solid fa-lock"></i> <span data-i18n="tournament.login_to_vote">Inicia sesión para votar</span>
            </button>
            <div class="tournament-vote-vs-spacer"></div>
            <button class="tournament-vote-btn" data-require-login="true">
                <i class="fa-solid fa-lock"></i> <span data-i18n="tournament.login_to_vote">Inicia sesión para votar</span>
            </button>
            `}
        </div>
        ` : ''}
        <div class="tournament-vote-bar-container">
            ${IS_LOGGED ? `
            <div class="tournament-vote-bar">
                <div class="tournament-vote-bar-fill-left" style="width:${total > 0 ? pct1 : 50}%"></div>
                <div class="tournament-vote-bar-fill-right" style="width:${total > 0 ? pct2 : 50}%"></div>
            </div>
            <div class="tournament-vote-percentages">
                <span class="tournament-vote-pct-left">${total > 0 ? pct1 + '%' : '—'} &nbsp;(${m.votes_coaster1 || 0} <span data-i18n="tournament.votes">votos</span>)</span>
                <span class="tournament-vote-pct-right">(${m.votes_coaster2 || 0} <span data-i18n="tournament.votes">votos</span>)&nbsp; ${total > 0 ? pct2 + '%' : '—'}</span>
            </div>
            ` : `
            <div class="tournament-vote-bar-guest">
                <i class="fa-solid fa-eye-slash"></i>
                <span data-i18n="tournament.register_to_see_results">Regístrate para ver los resultados en tiempo real</span>
            </div>
            `}
        </div>
    </div>`;
}

    /* ═══════════════════════════════════════════════════════════
       RENDER: GRUPOS
       ═══════════════════════════════════════════════════════════ */
    function renderGroups(groups) {
        const container = document.getElementById('groups-container');
        if (!container) return;

        const letters = Object.keys(groups).sort();
        if (!letters.length) {
            container.innerHTML = `
                <div class="tournament-empty">
                    <i class="fa-solid fa-shuffle"></i>
                    <p data-i18n="tournament.groups_empty">Los grupos se mostrarán tras el sorteo</p>
                </div>`;
            if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
            return;
        }

        container.innerHTML = letters.map(letter => {
            const coasters = groups[letter];
            // Usar la foto del 1er clasificado como banner del grupo
            const bannerImg = coasters[0] && coasters[0].imagen_url ? escapeHtml(coasters[0].imagen_url) : '';
            return `
            <div class="tournament-group-card">
                ${bannerImg
                    ? `<img class="tournament-group-photo" src="${bannerImg}" alt="Grupo ${letter}" loading="lazy">`
                    : `<div class="tournament-group-photo-placeholder"><span style="font-size:2rem;opacity:0.2">${letter}</span></div>`
                }
                <div class="tournament-group-header">
                    <span class="tournament-group-name"><span data-i18n="tournament.group_label">GRUPO</span> ${letter}</span>
                    <div class="tournament-group-badge">${letter}</div>
                </div>
                <div class="tournament-group-table-wrapper">
                    <table class="tournament-group-table">
                        <thead>
                            <tr>
                                <th>Coaster</th>
                                <th>PJ</th>
                                <th>G</th>
                                <th>E</th>
                                <th>P</th>
                                <th>DV</th>
                                <th>Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${coasters.map((c, i) => `
                            <tr class="${i < 2 ? 'qualified' : ''}">
                                <td>
                                    <div class="tournament-group-coaster-cell">
                                        <div class="tournament-group-pos pos-${i + 1}">${i + 1}</div>
                                        <img class="tournament-group-img" src="${escapeHtml(c.imagen_url || '/dummy.jpg')}" alt="${escapeHtml(c.coaster_name)}" loading="lazy">
                                        <div class="tournament-group-info">
                                            <div class="tournament-group-cname">${escapeHtml(c.coaster_name)}</div>
                                            <div class="tournament-group-pname">${getCountryFlag(c.park_country)} ${escapeHtml(c.park_name)}</div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>${c.matches_played || 0}</td>
                            <td>${c.wins || 0}</td>
                            <td>${c.draws || 0}</td>
                            <td>${c.losses || 0}</td>
                            <td>${IS_LOGGED ? ((c.votes_for || 0) - (c.votes_against || 0)) : '—'}</td>
                            <td class="tournament-group-pts">${IS_LOGGED ? (c.points || 0) : '—'}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>
            </div>`;
        }).join('');
        
        if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDER: CALENDARIO (Filtro por Fases)
       ═══════════════════════════════════════════════════════════ */
    async function loadJornadas() {
        if (!_tournamentData) {
            const data = await apiFetch('get_tournament');
            if (data.success) {
                _tournamentData = data;
            }
        }
        
        // Find which phase is active
        const activeBtn = document.querySelector('.tournament-filter-btn.active');
        const phase = activeBtn ? activeBtn.dataset.phase : 'jornada-1';
        renderCalendarioPhase(phase);
    }

    function renderCalendarioPhase(phaseKey) {
        const container = document.getElementById('jornadas-container');
        if (!container || !_tournamentData) return;

        let matchesToRender = [];

        // 1. Filtrar los partidos según la fase seleccionada
        if (phaseKey.startsWith('jornada-')) {
            const jNum = parseInt(phaseKey.split('-')[1]);
            const groupMatches = _tournamentData.group_matches || [];
            
            // Agrupar por grupo para sacar los partidos correspondientes a la jornada
            const byGroup = {};
            groupMatches.forEach(m => {
                const g = m.group_letter || 'A';
                if (!byGroup[g]) byGroup[g] = [];
                byGroup[g].push(m);
            });

            Object.values(byGroup).forEach(matches => {
                matches.sort((a, b) => a.match_number - b.match_number);
                if (jNum === 1) {
                    if (matches[0]) matchesToRender.push(matches[0]);
                    if (matches[5]) matchesToRender.push(matches[5]);
                } else if (jNum === 2) {
                    if (matches[1]) matchesToRender.push(matches[1]);
                    if (matches[4]) matchesToRender.push(matches[4]);
                } else if (jNum === 3) {
                    if (matches[2]) matchesToRender.push(matches[2]);
                    if (matches[3]) matchesToRender.push(matches[3]);
                }
            });
        } else {
            // Fases eliminatorias (octavos, cuartos, etc)
            if (_tournamentData.bracket && _tournamentData.bracket[phaseKey]) {
                matchesToRender = _tournamentData.bracket[phaseKey];
            }
        }

        if (!matchesToRender.length) {
            container.innerHTML = `
                <div class="tournament-empty">
                    <i class="fa-solid fa-calendar-xmark"></i>
                    <p data-i18n="tournament.empty_phase">Aún no hay partidos generados para esta fase.</p>
                </div>`;
            if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
            return;
        }

        let currentGroup = null;

        // 2. Renderizar los partidos (con fondo degradado)
        container.innerHTML = `
        <div class="tournament-calendario-grid">
            ${matchesToRender.map(m => {
                let dividerHtml = '';
                if (m.phase === 'group' && m.group_letter !== currentGroup) {
                    currentGroup = m.group_letter;
                    dividerHtml = `<div class="tournament-calendario-group-divider"><i class="fa-solid fa-layer-group"></i> <span data-i18n="tournament.group_label">GRUPO</span> ${currentGroup}</div>`;
                }

                const isFinished = m.status === 'finished';
                const isActive = m.status === 'active';
                let statusBadge = '';
                if (isFinished) {
                    statusBadge = '<span class="badge bg-success" style="font-size:0.65rem; padding: 0.35rem 0.6rem;" data-i18n="tournament.status_finished">FINALIZADO</span>';
                } else if (isActive) {
                    statusBadge = '<span class="badge bg-warning text-dark" style="font-size:0.65rem; padding: 0.35rem 0.6rem;"><i class="fa-solid fa-bolt"></i> <span data-i18n="tournament.status_active">EN JUEGO</span></span>';
                } else {
                    statusBadge = '<span class="badge bg-secondary" style="font-size:0.65rem; padding: 0.35rem 0.6rem;" data-i18n="tournament.status_pending">PENDIENTE</span>';
                }

                const c1Winner = isFinished && m.winner_id == m.coaster1_id;
                const c2Winner = isFinished && m.winner_id == m.coaster2_id;
                
                // Si es fase de grupos, ya tenemos el divisor de grupo encima.
                // Para las eliminatorias, podemos poner "Octavos - Partido 49", pero por ahora con Partido es suficiente o podemos usar m.phase_name si lo hubiera.
                let titleStr = m.phase === 'group' ? `<span data-i18n="tournament.match_label">Partido</span> ${m.match_number}` : `<span data-i18n="tournament.knockout_match_label">Eliminatoria - Partido</span> ${m.match_number}`;

                const img1 = escapeHtml(m.img1 || '/dummy.jpg');
                const img2 = escapeHtml(m.img2 || '/dummy.jpg');

                return dividerHtml + `
                <div class="tournament-calendario-card">
                    <div class="tournament-calendario-header">
                        <span class="tournament-calendario-title">${titleStr}</span>
                        ${statusBadge}
                    </div>
                    <div class="tournament-calendario-body">
                        <!-- Coaster 1 -->
                        <div class="tournament-calendario-side ${c1Winner ? 'winner' : ''}">
                            <div class="tournament-calendario-photo" style="background-image: url('${img1}')"></div>
                            <div class="tournament-calendario-content">
                                <div class="tournament-calendario-team-info">
                                    <span class="tournament-calendario-flag">${getCountryFlag(m.country1)}</span>
                                    <span class="tournament-calendario-name">${escapeHtml(m.name1)}</span>
                                </div>
                                ${isFinished && IS_LOGGED ? `<span class="tournament-calendario-score">${m.votes_coaster1 || 0}</span>` : ''}
                            </div>
                        </div>
                        
                        <div class="tournament-calendario-vs">VS</div>
                        
                        <!-- Coaster 2 -->
                        <div class="tournament-calendario-side right ${c2Winner ? 'winner' : ''}">
                            <div class="tournament-calendario-photo" style="background-image: url('${img2}')"></div>
                            <div class="tournament-calendario-content">
                                ${isFinished && IS_LOGGED ? `<span class="tournament-calendario-score">${m.votes_coaster2 || 0}</span>` : ''}
                                <div class="tournament-calendario-team-info right-info">
                                    <span class="tournament-calendario-name">${escapeHtml(m.name2)}</span>
                                    <span class="tournament-calendario-flag">${getCountryFlag(m.country2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('')}
        </div>`;
        if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
    }

    /* ═══════════════════════════════════════════════════════════
       RENDER: BRACKET
       ═══════════════════════════════════════════════════════════ */
    function renderBracket(bracket) {
        const container = document.getElementById('bracket-container');
        if (!container) return;

        const phases = [
            { key: 'round16', labelKey: 'tournament.filters.round16', defaultLabel: 'Octavos' },
            { key: 'quarter', labelKey: 'tournament.filters.quarter', defaultLabel: 'Cuartos' },
            { key: 'semi', labelKey: 'tournament.filters.semi', defaultLabel: 'Semis' },
            { key: 'final', labelKey: 'tournament.filters.final', defaultLabel: 'Final' },
            { key: 'third', labelKey: 'tournament.filters.third', defaultLabel: '3° Puesto' }
        ];

        // Check if there are any bracket matches
        const hasMatches = phases.some(p => bracket[p.key] && bracket[p.key].length > 0);
        if (!hasMatches) {
            container.innerHTML = `
                <div class="tournament-empty">
                    <i class="fa-solid fa-sitemap"></i>
                    <p data-i18n="tournament.bracket_empty">El cuadro eliminatorio se mostrará tras la fase de grupos</p>
                </div>`;
            if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
            return;
        }

        container.innerHTML = `
        <div class="tournament-bracket-wrapper">
            ${phases.map(p => {
                const matches = bracket[p.key] || [];
                if (!matches.length) return '';
                return `
                <div class="tournament-bracket-round">
                    <div class="tournament-bracket-round-title" data-i18n="${p.labelKey}">${p.defaultLabel}</div>
                    ${matches.map(m => renderBracketMatch(m)).join('')}
                </div>`;
            }).join('')}
        </div>`;
        if (window.rcwI18n) window.rcwI18n.applyToContainer(container);
    }

    function renderBracketMatch(m) {
        const isActive = m.status === 'active';
        const isFinished = m.status === 'finished';
        const c1Winner = isFinished && m.winner_id == m.coaster1_id;
        const c2Winner = isFinished && m.winner_id == m.coaster2_id;

        return `
        <div class="tournament-bracket-match ${isActive ? 'match-active' : ''}">
            <div class="tournament-bracket-team ${c1Winner ? 'winner' : ''}">
                <img class="tournament-bracket-team-img" src="${escapeHtml(m.img1 || '/dummy.jpg')}" alt="${escapeHtml(m.name1)}" loading="lazy">
                <span class="tournament-bracket-team-name">${escapeHtml(m.name1)}</span>
                <span class="tournament-bracket-team-score">${IS_LOGGED ? (m.votes_coaster1 || 0) : ''}</span>
            </div>
            <div class="tournament-bracket-team ${c2Winner ? 'winner' : ''}">
                <img class="tournament-bracket-team-img" src="${escapeHtml(m.img2 || '/dummy.jpg')}" alt="${escapeHtml(m.name2)}" loading="lazy">
                <span class="tournament-bracket-team-name">${escapeHtml(m.name2)}</span>
                <span class="tournament-bracket-team-score">${IS_LOGGED ? (m.votes_coaster2 || 0) : ''}</span>
            </div>
        </div>`;
    }

    /* ═══════════════════════════════════════════════════════════
       VOTING
       ═══════════════════════════════════════════════════════════ */
    function showLoginModal() {
        const modalEl = document.getElementById('loginModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        }
    }

    async function handleVote(btn) {
        if (!IS_LOGGED) { showLoginModal(); return; }

        const matchId = btn.dataset.match;
        const coasterId = btn.dataset.coaster;

        if (!matchId || !coasterId) return;

        btn.disabled = true;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
            const data = await apiFetch('vote', {
                method: 'POST',
                body: { match_id: parseInt(matchId), coaster_id: parseInt(coasterId) }
            });

            if (data.success) {
                loadActiveMatches();
            } else {
                alert(data.error || 'Error al votar');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }
        } catch (e) {
            alert('Error de conexión');
            btn.innerHTML = originalHTML;
            btn.disabled = false;
        }
    }

    function initShareButton() {
        const copyBtn = document.getElementById('tournament-share-copy');
        const urlInput = document.getElementById('tournament-share-url');
        if (!copyBtn || !urlInput) return;
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(urlInput.value).then(() => {
                const icon = document.getElementById('share-copy-icon');
                if (icon) icon.className = 'fa-solid fa-check';
                copyBtn.style.background = 'var(--rcw-green-neon)';
                copyBtn.style.color = '#000';
                setTimeout(() => {
                    if (icon) icon.className = 'fa-solid fa-copy';
                    copyBtn.style.background = '';
                    copyBtn.style.color = '';
                }, 2000);
            }).catch(() => {
                urlInput.select();
                document.execCommand('copy');
            });
        });
    }


    /* ═══════════════════════════════════════════════════════════
       COUNTDOWN
       ═══════════════════════════════════════════════════════════ */
    function startCountdown(matchId, endsAt) {
        const timerEl = document.querySelector(`#timer-${matchId} .timer-text`);
        if (!timerEl) return;

        const endTime = new Date(endsAt).getTime();

        const interval = setInterval(() => {
            const now = Date.now();
            const diff = endTime - now;

            if (diff <= 0) {
                timerEl.textContent = '¡Finalizado!';
                timerEl.parentElement.classList.add('expired');
                clearInterval(interval);
                // Reload after a short delay
                setTimeout(() => loadActiveMatches(), 2000);
                return;
            }

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const secs = Math.floor((diff % (1000 * 60)) / 1000);

            timerEl.textContent = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
        }, 1000);

        _countdownTimers.push(interval);
    }

    function clearCountdowns() {
        _countdownTimers.forEach(t => clearInterval(t));
        _countdownTimers = [];
    }

    /* ═══════════════════════════════════════════════════════════
       ADMIN
       ═══════════════════════════════════════════════════════════ */
    async function loadAdminData() {
        loadParticipants();
        loadPendingMatches();
        loadSettings();
        bindHeroUpload();
        loadPhotoManager();
        bindAdminEvents();
    }

    function bindAdminEvents() {
        // Search
        const searchBtn = document.getElementById('admin-search-btn');
        const searchInput = document.getElementById('admin-search-input');
        if (searchBtn && !searchBtn._bound) {
            searchBtn._bound = true;
            searchBtn.addEventListener('click', () => adminSearch(searchInput.value));
            searchInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') adminSearch(searchInput.value);
            });
        }

        // Draw
        const drawBtn = document.getElementById('admin-draw-btn');
        if (drawBtn && !drawBtn._bound) {
            drawBtn._bound = true;
            drawBtn.addEventListener('click', () => adminAction('draw_groups', 'POST', '¿Realizar sorteo de grupos?'));
        }

        // Generate matches
        const genBtn = document.getElementById('admin-gen-matches-btn');
        if (genBtn && !genBtn._bound) {
            genBtn._bound = true;
            genBtn.addEventListener('click', () => adminAction('generate_matches', 'POST', '¿Generar todos los partidos de fase de grupos?'));
        }

        // Close expired
        const closeBtn = document.getElementById('admin-close-expired-btn');
        if (closeBtn && !closeBtn._bound) {
            closeBtn._bound = true;
            closeBtn.addEventListener('click', () => adminAction('close_expired', 'POST'));
        }

        // Reset
        const resetBtn = document.getElementById('admin-reset-btn');
        if (resetBtn && !resetBtn._bound) {
            resetBtn._bound = true;
            resetBtn.addEventListener('click', () => adminAction('reset_tournament', 'POST', '⚠️ ¿RESETEAR TODO EL TORNEO? Esta acción no se puede deshacer.'));
        }

        // Advance phase
        document.querySelectorAll('.admin-advance').forEach(link => {
            if (link._bound) return;
            link._bound = true;
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const phase = link.dataset.phase;
                adminAction('advance_phase', 'POST', `¿Generar partidos de ${getPhaseLabel(phase)}?`, { phase });
            });
        });
    }

    async function adminSearch(query) {
        if (!query.trim()) return;
        const container = document.getElementById('admin-search-results');
        container.innerHTML = '<div class="tournament-loading"><i class="fa-solid fa-spinner fa-spin"></i> Buscando...</div>';

        try {
            const data = await apiFetch('search_coasters', { params: `&q=${encodeURIComponent(query)}` });
            if (data.success) {
                container.innerHTML = (data.coasters || []).map(c => `
                    <div class="tournament-admin-result-card ${c.already_added ? 'already-added' : ''}">
                        <img class="tournament-admin-result-img" src="${escapeHtml(c.imagen_url || '/dummy.jpg')}" alt="${escapeHtml(c.coaster_name)}" loading="lazy">
                        <div class="tournament-admin-result-info">
                            <div class="tournament-admin-result-name">${escapeHtml(c.coaster_name)}</div>
                            <div class="tournament-admin-result-park">${getCountryFlag(c.park_country)} ${escapeHtml(c.park_name)}</div>
                        </div>
                        <button class="tournament-admin-add-btn" data-id="${c.id}" ${c.already_added ? 'disabled' : ''}>
                            ${c.already_added ? '✓' : '+ Añadir'}
                        </button>
                    </div>
                `).join('') || '<div class="tournament-empty"><p>Sin resultados</p></div>';

                container.querySelectorAll('.tournament-admin-add-btn:not(:disabled)').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        btn.disabled = true;
                        btn.textContent = '...';
                        const res = await apiFetch('add_coaster', { method: 'POST', body: { coaster_id: parseInt(btn.dataset.id) } });
                        if (res.success) {
                            btn.textContent = '✓';
                            btn.closest('.tournament-admin-result-card').classList.add('already-added');
                            loadParticipants();
                            loadPhotoManager();
                        } else {
                            alert(res.error);
                            btn.disabled = false;
                            btn.textContent = '+ Añadir';
                        }
                    });
                });
            }
        } catch (e) {
            container.innerHTML = '<div class="tournament-empty"><p>Error en la búsqueda</p></div>';
        }
    }

    async function loadParticipants() {
        const container = document.getElementById('admin-participants');
        const countEl = document.getElementById('admin-count');
        if (!container) return;

        try {
            const data = await apiFetch('get_participants');
            if (data.success) {
                const participants = data.participants || [];
                if (countEl) countEl.textContent = participants.length;

                if (!participants.length) {
                    container.innerHTML = '<div class="tournament-empty"><p>No hay participantes aún</p></div>';
                    return;
                }

                container.innerHTML = participants.map(p => `
                    <div class="tournament-admin-participant">
                        <img src="${escapeHtml(p.imagen_url || '/dummy.jpg')}" alt="${escapeHtml(p.coaster_name)}" loading="lazy">
                        <span class="tournament-admin-participant-name" title="${escapeHtml(p.coaster_name)} (${escapeHtml(p.park_name)})">
                            ${getCountryFlag(p.park_country)} ${escapeHtml(p.coaster_name)}
                            ${p.group_letter ? `<small style="color:var(--rcw-green-neon)"> [${p.group_letter}]</small>` : ''}
                        </span>
                        <button class="tournament-admin-remove-btn" data-id="${p.coaster_id}" title="Quitar del torneo">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                `).join('');

                container.querySelectorAll('.tournament-admin-remove-btn').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        if (!confirm('¿Quitar esta coaster del torneo?')) return;
                        const res = await apiFetch('remove_coaster', { method: 'POST', body: { coaster_id: parseInt(btn.dataset.id) } });
                        if (res.success) {
                            loadParticipants();
                            loadPhotoManager();
                        } else {
                            alert(res.error);
                        }
                    });
                });
            }
        } catch (e) {
            container.innerHTML = '<div class="tournament-empty"><p>Error al cargar participantes</p></div>';
        }
    }

    /* ── SETTINGS (hero image) ─────────────────────────────────── */
    async function loadSettings() {
        try {
            const data = await apiFetch('get_settings');
            if (!data.success) return;
            const heroUrl = data.settings.hero_image_url || '';

            // Mostrar imagen actual en el indicador
            const currentDiv = document.getElementById('admin-hero-current');
            const currentUrl = document.getElementById('admin-hero-current-url');
            if (currentDiv && currentUrl) {
                if (heroUrl) {
                    currentDiv.style.display = '';
                    currentUrl.textContent = heroUrl.split('/').pop();
                } else {
                    currentDiv.style.display = 'none';
                }
            }

            // Aplicar en el hero del público
            applyHeroImage(heroUrl);
        } catch (e) {
            // No block
        }
    }

    function applyHeroImage(url) {
        const bg = document.getElementById('tournament-hero-bg');
        if (!bg) return;
        if (url) {
            bg.style.backgroundImage = `url('${url}')`;
            bg.style.backgroundSize = 'cover';
            bg.style.backgroundPosition = 'center';
        } else {
            bg.style.backgroundImage = '';
        }
    }

    /* ── HERO FILE UPLOAD ───────────────────────────────────────── */
    function bindHeroUpload() {
        const fileInput   = document.getElementById('admin-hero-file');
        const dropzone    = document.getElementById('admin-hero-dropzone');
        const previewWrap = document.getElementById('admin-hero-preview-wrap');
        const preview     = document.getElementById('admin-hero-preview');
        const filename    = document.getElementById('admin-hero-filename');
        const uploadBtn   = document.getElementById('admin-hero-upload-btn');
        const clearBtn    = document.getElementById('admin-hero-clear-btn');
        if (!fileInput || fileInput._bound) return; // evita enganchar listeners duplicados cada vez que se abre la pestaña admin
        fileInput._bound = true;

        let selectedFile = null;

        // Drag over / leave
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('drag-over');
            const f = e.dataTransfer.files[0];
            if (f) handleHeroFileSelected(f);
        });

        fileInput.addEventListener('change', e => {
            const file = e.target.files[0];
            if (!file) return;
            
            if (file.size > 2 * 1024 * 1024) {
                alert('La imagen pesa demasiado (máximo 2 MB). Por favor, comprímela o usa otra.');
                fileInput.value = '';
                return;
            }

            handleHeroFileSelected(file);
        });

        function handleHeroFileSelected(file) {
            selectedFile = file;
            if (filename) filename.textContent = file.name;
            if (previewWrap) previewWrap.style.display = '';
            const reader = new FileReader();
            reader.onload = e => {
                if (preview) preview.style.backgroundImage = `url('${e.target.result}')`;
            };
            reader.readAsDataURL(file);
        }

        if (uploadBtn) {
            uploadBtn.addEventListener('click', async () => {
                if (!selectedFile) return;
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo...';

                const fd = new FormData();
                fd.append('image', selectedFile);

                try {
                    const res = await fetch(`${API}?action=upload_hero_image`, { 
                        method: 'POST', 
                        body: fd,
                        headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        applyHeroImage(data.url);
                        uploadBtn.innerHTML = '<i class="fa-solid fa-check"></i> Subida!';
                        // Update current indicator
                        const currentDiv = document.getElementById('admin-hero-current');
                        const currentUrl = document.getElementById('admin-hero-current-url');
                        if (currentDiv) currentDiv.style.display = '';
                        if (currentUrl) currentUrl.textContent = data.url.split('/').pop();
                        setTimeout(() => {
                            uploadBtn.disabled = false;
                            uploadBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Subir imagen';
                        }, 2500);
                    } else {
                        alert('Error: ' + data.error);
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Subir imagen';
                    }
                } catch (err) {
                    alert('Error de conexión');
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Subir imagen';
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                selectedFile = null;
                fileInput.value = '';
                if (previewWrap) previewWrap.style.display = 'none';
                if (preview) preview.style.backgroundImage = '';
            });
        }
    }

    /* ── PHOTO MANAGER (per-coaster) ─────────────────────────────── */
    async function loadPhotoManager() {
        const container = document.getElementById('admin-photo-manager');
        if (!container) return;

        try {
            const data = await apiFetch('get_participants');
            const participants = data.participants || [];
            
            if (!data.success || participants.length === 0) {
                container.innerHTML = '<div class="tournament-empty"><p>Añade coasters primero</p></div>';
                return;
            }

            container.innerHTML = participants.map(p => {
                const imgSrc   = escapeHtml(p.display_img || p.imagen_url || '');
                const hasCustom = !!p.custom_image_url;
                const inputId  = `photo-file-${p.coaster_id}`;
                return `
                <div class="tournament-admin-photo-card" data-cid="${p.coaster_id}">
                    <div class="tournament-admin-photo-img-wrap">
                        ${hasCustom ? '<span class="tournament-admin-photo-custom-badge">★ Custom</span>' : ''}
                        ${imgSrc
                            ? `<img class="tournament-admin-photo-img" src="${imgSrc}" alt="${escapeHtml(p.coaster_name)}" id="photo-img-${p.coaster_id}" loading="lazy">`
                            : `<img class="tournament-admin-photo-img no-img" src="" alt="Sin foto" id="photo-img-${p.coaster_id}">`
                        }
                        <label for="${inputId}" class="tournament-admin-photo-edit-btn" title="Cambiar foto">
                            <i class="fa-solid fa-camera"></i>
                            <input type="file" id="${inputId}" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" data-cid="${p.coaster_id}">
                        </label>
                    </div>
                    <div class="tournament-admin-photo-info">
                        <div class="tournament-admin-photo-name">${getCountryFlag(p.park_country)} ${escapeHtml(p.coaster_name)}</div>
                        <div class="tournament-admin-photo-park">${escapeHtml(p.park_name)}</div>
                    </div>
                    <div class="tournament-admin-photo-status" id="photo-status-${p.coaster_id}" style="padding:.2rem .5rem;font-size:.7rem;min-height:1.5rem"></div>
                </div>`;
            }).join('');

            // Bind file inputs
            container.querySelectorAll('input[type=file][data-cid]').forEach(input => {
                input.addEventListener('change', () => {
                    const file = input.files[0];
                    if (file) uploadCoasterPhoto(parseInt(input.dataset.cid), file, input);
                });
            });
        } catch (e) {
            container.innerHTML = '<div class="tournament-empty"><p>Error al cargar</p></div>';
        }
    }

    async function uploadCoasterPhoto(coasterId, file, inputEl) {
        const imgEl    = document.getElementById(`photo-img-${coasterId}`);
        const statusEl = document.getElementById(`photo-status-${coasterId}`);

        if (file.size > 2 * 1024 * 1024) {
            if (statusEl) statusEl.innerHTML = '<span style="color:#ff6b6b">La imagen pesa demasiado (máximo 2 MB).</span>';
            if (inputEl) inputEl.value = '';
            return;
        }

        if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Subiendo...';

        // Local preview
        const reader = new FileReader();
        reader.onload = e => {
            if (imgEl) { imgEl.src = e.target.result; imgEl.classList.remove('no-img'); }
        };
        reader.readAsDataURL(file);

        const fd = new FormData();
        fd.append('coaster_id', coasterId);
        fd.append('image', file);

        try {
            const res = await fetch(`${API}?action=upload_coaster_image`, {
                method: 'POST',
                body: fd,
                headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' }
            });
            const data = await res.json();

            if (data.success) {
                if (statusEl) statusEl.innerHTML = '<span style="color:var(--rcw-green-neon)"><i class="fa-solid fa-check"></i> Guardada</span>';
                // Mark as custom
                const card = document.querySelector(`[data-cid="${coasterId}"]`);
                if (card && !card.querySelector('.tournament-admin-photo-custom-badge')) {
                    const wrap = card.querySelector('.tournament-admin-photo-img-wrap');
                    const badge = document.createElement('span');
                    badge.className = 'tournament-admin-photo-custom-badge';
                    badge.textContent = '★ Custom';
                    wrap.prepend(badge);
                }
                setTimeout(() => { if (statusEl) statusEl.innerHTML = ''; }, 3000);
            } else {
                if (statusEl) statusEl.innerHTML = `<span style="color:#ff6b6b">${data.error}</span>`;
                if (imgEl) imgEl.src = '';
            }
        } catch (err) {
            if (statusEl) statusEl.innerHTML = '<span style="color:#ff6b6b">Error de conexión</span>';
        }
    }

    async function loadPendingMatches() {
        const container = document.getElementById('admin-pending-matches');
        if (!container) return;
        container.innerHTML = '<div class="tournament-loading"><i class="fa-solid fa-spinner fa-spin"></i> Cargando...</div>';
        loadPendingMatchesList();
    }

    async function loadPendingMatchesList() {
        const container = document.getElementById('admin-pending-matches');
        if (!container) return;

        try {
            let pending = [];

            // Endpoint correcto: get_all_group_matches
            const allRes = await fetch(`${API}?action=get_all_group_matches`);
            if (allRes.ok) {
                const allData = await allRes.json();
                if (allData.success && allData.matches) {
                    allData.matches.forEach(m => {
                        if (m.status === 'pending') pending.push(m);
                    });
                }
            }

            if (!pending.length) {
                // Fallback: try loading from tournament
                container.innerHTML = `
                    <div class="tournament-empty">
                        <i class="fa-solid fa-check-circle"></i>
                        <p>No hay partidos pendientes de abrir</p>
                    </div>`;
                return;
            }

            container.innerHTML = pending.map(m => `
                <div class="tournament-admin-pending-card">
                    <div class="tournament-admin-pending-info">
                        <div class="tournament-admin-pending-names">${escapeHtml(m.name1 || 'Coaster ' + m.coaster1_id)} vs ${escapeHtml(m.name2 || 'Coaster ' + m.coaster2_id)}</div>
                        <div class="tournament-admin-pending-phase">${getPhaseLabel(m.phase, m.group_letter)} · Partido #${m.match_number}</div>
                    </div>
                    <button class="tournament-admin-open-btn" data-id="${m.id}">
                        <i class="fa-solid fa-play"></i> Abrir (24h)
                    </button>
                </div>
            `).join('');

            container.querySelectorAll('.tournament-admin-open-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                    const res = await apiFetch('open_match', { method: 'POST', body: { match_id: parseInt(btn.dataset.id) } });
                    if (res.success) {
                        btn.innerHTML = '✓ Abierto';
                        btn.style.background = '#333';
                        loadActiveMatches();
                    } else {
                        alert(res.error);
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-play"></i> Abrir (24h)';
                    }
                });
            });
        } catch (e) {
            // Silently handle
        }
    }

    async function adminAction(action, method, confirmMsg, body = {}) {
        if (confirmMsg && !confirm(confirmMsg)) return;
        try {
            const data = await apiFetch(action, { method, body });
            if (data.success) {
                alert(data.message || 'Acción completada');
                loadAdminData();
                loadTournament();
            } else {
                alert(data.error || 'Error');
            }
        } catch (e) {
            alert('Error de conexión');
        }
    }

    /* ═══════════════════════════════════════════════════════════
       UTILITIES
       ═══════════════════════════════════════════════════════════ */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function getPhaseLabel(phase, groupLetter) {
        const labels = {
            'group': groupLetter ? `Grupo ${groupLetter}` : 'Fase de Grupos',
            'round16': 'Octavos de Final',
            'quarter': 'Cuartos de Final',
            'semi': 'Semifinal',
            'third': '3° y 4° Puesto',
            'final': 'Gran Final'
        };
        return labels[phase] || phase;
    }

    function getCountryFlag(country) {
        if (!country) return '🌍';
        const flags = {
            'Spain': '🇪🇸', 'España': '🇪🇸',
            'Germany': '🇩🇪', 'Alemania': '🇩🇪',
            'France': '🇫🇷', 'Francia': '🇫🇷',
            'United Kingdom': '🇬🇧', 'Reino Unido': '🇬🇧', 'UK': '🇬🇧',
            'Netherlands': '🇳🇱', 'Países Bajos': '🇳🇱', 'Holanda': '🇳🇱',
            'Italy': '🇮🇹', 'Italia': '🇮🇹',
            'Sweden': '🇸🇪', 'Suecia': '🇸🇪',
            'Denmark': '🇩🇰', 'Dinamarca': '🇩🇰',
            'Belgium': '🇧🇪', 'Bélgica': '🇧🇪',
            'Poland': '🇵🇱', 'Polonia': '🇵🇱',
            'Austria': '🇦🇹',
            'Switzerland': '🇨🇭', 'Suiza': '🇨🇭',
            'Norway': '🇳🇴', 'Noruega': '🇳🇴',
            'Finland': '🇫🇮', 'Finlandia': '🇫🇮',
            'Portugal': '🇵🇹',
            'Ireland': '🇮🇪', 'Irlanda': '🇮🇪',
            'Czech Republic': '🇨🇿', 'República Checa': '🇨🇿',
            'Greece': '🇬🇷', 'Grecia': '🇬🇷',
            'Hungary': '🇭🇺', 'Hungría': '🇭🇺',
            'Croatia': '🇭🇷', 'Croacia': '🇭🇷',
            'Romania': '🇷🇴', 'Rumania': '🇷🇴',
            'Turkey': '🇹🇷', 'Turquía': '🇹🇷',
            'Russia': '🇷🇺', 'Rusia': '🇷🇺',
            'Iceland': '🇮🇸', 'Islandia': '🇮🇸',
        };
        return flags[country] || '🏴';
    }

})();