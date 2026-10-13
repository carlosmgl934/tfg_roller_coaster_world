<?php
/**
 * tournament.php — Vista del Torneo Roller Coaster Euro Cup
 * ─────────────────────────────────────────────────────────
 * Página independiente — se borrará al acabar el evento.
 */
$page_css = ['web/css/tournament.css'];
require_once __DIR__ . '/../partials/header.php';
$is_admin = !empty($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
?>
<script>
    window.TOURNAMENT_IS_LOGGED = <?= $is_logged ? 'true' : 'false' ?>;
</script>

<main class="tournament-page">

    <!-- ══════════════════════ HERO BANNER ══════════════════════ -->
    <section class="tournament-hero" id="tournament-hero">
        <div class="tournament-hero-bg" id="tournament-hero-bg"></div>
        <div class="tournament-hero-content container-fluid px-3 px-md-4">
            <div class="tournament-hero-eyebrow">
                <i class="fa-solid fa-trophy"></i> <span data-i18n="tournament.edition">RollerCoaster World — Edición
                    Europa</span>
            </div>
            <h1 class="tournament-title">
                <span class="tournament-title-main" data-i18n="tournament.title_main">ROLLER COASTER</span>
                <span class="tournament-title-accent" data-i18n="tournament.title_accent">EUROPEAN TOURNAMENT</span>
                <span class="tournament-title-year" data-i18n="tournament.title_year">2026</span>
            </h1>
        </div>
    </section>

    <!-- ══════════════════════ INFO Y COMPARTIR ══════════════════════ -->
    <div class="tournament-info-bar container-fluid px-3 px-md-4 mt-3 mb-4">
        <p class="tournament-subtitle" data-i18n="tournament.subtitle">32 montañas rusas. Buscamos la mejor coaster de
            Europa. Tú decides.</p>

        <!-- Barra de compartir -->
        <div class="tournament-share-bar mt-3">
            <span class="tournament-share-label"><i class="fa-solid fa-link"></i> <span
                    data-i18n="tournament.share_label">Comparte el torneo:</span></span>
            <div class="tournament-share-input-wrap">
                <input type="text" class="tournament-share-input" id="tournament-share-url"
                    value="<?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>"
                    readonly>
                <button class="tournament-share-copy" id="tournament-share-copy" title="Copiar enlace">
                    <i class="fa-solid fa-copy" id="share-copy-icon"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════════════════ NAV TABS ══════════════════════ -->
    <div class="container-fluid px-3 px-md-4">
        <nav class="tournament-nav" id="tournament-nav">
            <button class="tournament-nav-btn active" data-tab="matches">
                <i class="fa-solid fa-bolt"></i> <span data-i18n="tournament.matches">Partidos</span>
            </button>
            <button class="tournament-nav-btn" data-tab="groups">
                <i class="fa-solid fa-layer-group"></i> <span data-i18n="tournament.groups">Grupos</span>
            </button>
            <button class="tournament-nav-btn" data-tab="jornadas">
                <i class="fa-solid fa-calendar-days"></i> <span data-i18n="tournament.jornadas">Jornadas</span>
            </button>
            <button class="tournament-nav-btn" data-tab="bracket">
                <i class="fa-solid fa-sitemap"></i> <span data-i18n="tournament.bracket">Cuadro</span>
            </button>
            <button class="tournament-nav-btn" data-tab="rules">
                <i class="fa-solid fa-book"></i> <span data-i18n="tournament.rules">Normas</span>
            </button>
            <?php if ($is_admin): ?>
                <button class="tournament-nav-btn tournament-nav-admin" data-tab="admin">
                    <i class="fa-solid fa-shield-halved"></i> Admin
                </button>
            <?php endif; ?>
        </nav>

        <!-- ══════════════════ TAB: PARTIDOS ACTIVOS ══════════════════ -->
        <section class="tournament-tab active" id="tab-matches">
            <h2 class="tournament-section-title">
                <i class="fa-solid fa-bolt text-neon"></i> <span data-i18n="tournament.active_matches">Partidos en
                    juego</span>
            </h2>
            <div id="active-matches-container" class="tournament-matches-grid">
                <div class="tournament-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> <span data-i18n="tournament.loading_matches">Cargando
                        partidos...</span>
                </div>
            </div>

            <!-- Partidos recientes finalizados -->
            <h2 class="tournament-section-title mt-5">
                <i class="fa-solid fa-flag-checkered"></i> <span data-i18n="tournament.recent_results">Resultados
                    recientes</span>
            </h2>
            <div id="recent-matches-container" class="tournament-matches-grid">
                <div class="tournament-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> <span data-i18n="tournament.loading_results">Cargando
                        resultados...</span>
                </div>
            </div>
        </section>

        <!-- ══════════════════ TAB: JORNADAS ══════════════════ -->
        <section class="tournament-tab" id="tab-jornadas">
            <h2 class="tournament-section-title">
                <i class="fa-solid fa-calendar-days text-neon"></i> <span
                    data-i18n="tournament.calendar">Calendario</span>
            </h2>

            <div class="tournament-calendario-filters">
                <button class="tournament-filter-btn active" data-phase="jornada-1"
                    data-i18n="tournament.filters.j1">J1</button>
                <button class="tournament-filter-btn" data-phase="jornada-2"
                    data-i18n="tournament.filters.j2">J2</button>
                <button class="tournament-filter-btn" data-phase="jornada-3"
                    data-i18n="tournament.filters.j3">J3</button>
                <button class="tournament-filter-btn" data-phase="round16"
                    data-i18n="tournament.filters.round16">Octavos</button>
                <button class="tournament-filter-btn" data-phase="quarter"
                    data-i18n="tournament.filters.quarter">Cuartos</button>
                <button class="tournament-filter-btn" data-phase="semi"
                    data-i18n="tournament.filters.semi">Semis</button>
                <button class="tournament-filter-btn" data-phase="third" data-i18n="tournament.filters.third">3º
                    Puesto</button>
                <button class="tournament-filter-btn" data-phase="final"
                    data-i18n="tournament.filters.final">Final</button>
            </div>

            <div id="jornadas-container">
                <div class="tournament-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> <span data-i18n="tournament.loading_calendar">Cargando
                        calendario...</span>
                </div>
            </div>
        </section>

        <!-- ══════════════════ TAB: GRUPOS ══════════════════ -->
        <section class="tournament-tab" id="tab-groups">
            <h2 class="tournament-section-title">
                <i class="fa-solid fa-layer-group text-neon"></i> <span data-i18n="tournament.group_stage">Fase de
                    Grupos</span>
            </h2>
            <div id="groups-container" class="tournament-groups-grid">
                <div class="tournament-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> <span data-i18n="tournament.loading_groups">Cargando
                        grupos...</span>
                </div>
            </div>
        </section>

        <!-- ══════════════════ TAB: CUADRO ══════════════════ -->
        <section class="tournament-tab" id="tab-bracket">
            <h2 class="tournament-section-title">
                <i class="fa-solid fa-sitemap text-neon"></i> <span data-i18n="tournament.bracket_stage">Cuadro
                    Eliminatorio</span>
            </h2>
            <div id="bracket-container" class="tournament-bracket">
                <div class="tournament-loading">
                    <i class="fa-solid fa-spinner fa-spin"></i> <span data-i18n="tournament.loading_bracket">Cargando
                        cuadro...</span>
                </div>
            </div>
        </section>

        <!-- ══════════════════ TAB: NORMAS ══════════════════ -->
        <section class="tournament-tab" id="tab-rules">
            <div class="tournament-rules-card">
                <h2 class="tournament-rules-title">
                    <i class="fa-solid fa-book text-neon"></i> <span data-i18n="tournament.rules_title">Normas del
                        Torneo</span>
                </h2>
                <div class="tournament-rules-grid">
                    <div class="tournament-rule">
                        <div class="tournament-rule-icon">📋</div>
                        <h4 data-i18n="tournament.rules_format_title">Formato</h4>
                        <p data-i18n-html="tournament.rules_format_desc">32 montañas rusas europeas compiten en
                            <strong>8
                                grupos de 4</strong>. Las 2 primeras de cada grupo pasan a la fase eliminatoria:
                            octavos, cuartos, semifinales, partido por el 3er puesto y gran final.
                        </p>
                    </div>
                    <div class="tournament-rule">
                        <div class="tournament-rule-icon">🌍</div>
                        <h4 data-i18n="tournament.rules_draw_title">Sorteo de Grupos</h4>
                        <p data-i18n-html="tournament.rules_draw_desc">El sorteo se ha hecho con una única condición:
                            <strong>en el mismo grupo no pueden estar 2 montañas rusas del mismo país</strong>. Hemos
                            intentado que todos los países tengan al menos una representante. A partir de ahí, el azar
                            ha decidido todo.
                        </p>
                    </div>
                    <div class="tournament-rule">
                        <div class="tournament-rule-icon">🗳️</div>
                        <h4 data-i18n="tournament.rules_vote_title">Cómo votar</h4>
                        <p data-i18n-html="tournament.rules_vote_desc">Cada partido es una <strong>votación de 24
                                horas</strong>. Necesitas estar registrado e iniciar sesión. Solo puedes votar
                            <strong>una vez por partido</strong> y <strong>no se puede cambiar</strong> el voto.
                        </p>
                    </div>
                    <div class="tournament-rule">
                        <div class="tournament-rule-icon">⚽</div>
                        <h4 data-i18n="tournament.rules_points_title">Puntuación</h4>
                        <p data-i18n-html="tournament.rules_points_desc"><strong>Victoria</strong> (más votos): 3
                            puntos.<br><strong>Empate</strong> (mismo número exacto de votos): 1 punto cada
                            una.<br><strong>Derrota</strong>: 0 puntos.<br>Todos los enfrentamientos son a
                            <strong>jornada única</strong>.
                        </p>
                    </div>
                    <div class="tournament-rule">
                        <div class="tournament-rule-icon">🏅</div>
                        <h4 data-i18n="tournament.rules_tie_title">Desempate en Grupos</h4>
                        <p data-i18n-html="tournament.rules_tie_desc">En caso de <strong>triple empate a
                                puntos</strong>:
                            primero se miran los <strong>enfrentamientos directos</strong> entre las implicadas; si no
                            se resuelve, gana quien tenga mejor <strong>diferencia de votos</strong> (votos a favor −
                            votos en contra).</p>
                    </div>
                    <div class="tournament-rule">
                        <div class="tournament-rule-icon">🏆</div>
                        <h4 data-i18n="tournament.rules_knockout_title">Sorteo de Octavos</h4>
                        <p data-i18n-html="tournament.rules_knockout_desc">En octavos, <strong>1°s clasificados vs 2°s
                                clasificados</strong> (primeros contra segundos en todos los cruces). Además, <strong>no
                                pueden enfrentarse montañas rusas del mismo país</strong> en octavos ni en cuartos. A
                            partir de ahí, el azar decide.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════ TAB: ADMIN ══════════════════ -->
        <?php if ($is_admin): ?>
            <section class="tournament-tab" id="tab-admin">
                <h2 class="tournament-section-title">
                    <i class="fa-solid fa-shield-halved text-neon"></i> <span data-i18n="tournament.admin_panel">Panel de
                        Administración</span>
                </h2>

                <!-- Seleccionar coasters -->
                <div class="tournament-admin-card">
                    <h4><i class="fa-solid fa-magnifying-glass"></i> <span data-i18n="tournament.add_coasters">Añadir
                            Coasters al Torneo</span></h4>
                    <p class="text-muted"><span data-i18n="tournament.participants">Participantes</span>: <span
                            id="admin-count">0</span>/32</p>
                    <div class="input-group mb-3">
                        <input type="text" id="admin-search-input" class="form-control"
                            placeholder="Buscar coaster por nombre o parque..."
                            data-i18n-placeholder="tournament.search_placeholder">
                        <button class="btn btn-success" id="admin-search-btn">
                            <i class="fa-solid fa-search"></i> <span data-i18n="tournament.search_btn">Buscar</span>
                        </button>
                    </div>
                    <div id="admin-search-results" class="tournament-admin-results"></div>
                </div>

                <!-- Imagen del Hero/Portada -->
                <div class="tournament-admin-card">
                    <h4><i class="fa-solid fa-image"></i> Imagen de Portada (Hero)</h4>
                    <p class="text-muted" style="font-size:.82rem;margin-bottom:.75rem;">
                        Sube una imagen desde tu escritorio para el banner principal. Recomendado: formato horizontal, mín.
                        1920×600 px. JPG, PNG o WEBP.
                    </p>
                    <!-- Drop zone del hero -->
                    <label for="admin-hero-file" class="tournament-upload-zone" id="admin-hero-dropzone">
                        <i class="fa-solid fa-cloud-arrow-up tournament-upload-icon"></i>
                        <span class="tournament-upload-text" data-i18n="tournament.drop_zone">Haz clic o arrastra una imagen
                            aquí</span>
                        <span class="tournament-upload-hint" data-i18n="tournament.drop_hint">JPG · PNG · WEBP · GIF — máx.
                            10 MB</span>
                        <input type="file" id="admin-hero-file" accept="image/jpeg,image/png,image/webp,image/gif"
                            style="display:none">
                    </label>
                    <!-- Preview + botón eliminar -->
                    <div id="admin-hero-preview-wrap" style="display:none;margin-top:.75rem">
                        <div id="admin-hero-preview" class="tournament-admin-hero-preview"></div>
                        <div style="display:flex;gap:.5rem;margin-top:.5rem">
                            <span id="admin-hero-filename"
                                style="font-size:.75rem;color:var(--rcw-text-muted);flex:1;align-self:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
                            <button class="btn btn-success btn-sm" id="admin-hero-upload-btn" style="border-radius:0">
                                <i class="fa-solid fa-upload"></i> <span data-i18n="tournament.upload_btn">Subir
                                    imagen</span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="admin-hero-clear-btn"
                                style="border-radius:0">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Imagen actual en servidor -->
                    <div id="admin-hero-current"
                        style="margin-top:.5rem;font-size:.75rem;color:var(--rcw-text-muted);display:none">
                        <i class="fa-solid fa-check-circle" style="color:var(--rcw-green-neon)"></i> <span
                            data-i18n="tournament.current_image">Imagen actual en servidor</span>: <span
                            id="admin-hero-current-url"></span>
                    </div>
                </div>

                <!-- Participantes actuales + gestión de fotos -->
                <div class="tournament-admin-card">
                    <h4><i class="fa-solid fa-images"></i> Fotos de las Coasters</h4>
                    <p class="text-muted" style="font-size:.82rem;margin-bottom:.75rem;">
                        Haz clic en el icono <i class="fa-solid fa-camera"></i> de cada coaster para subir una foto desde tu
                        escritorio. Si no hay foto personalizada, se usa la imagen de la base de datos.
                    </p>
                    <div id="admin-photo-manager" class="tournament-admin-photo-grid"></div>
                </div>


                <!-- Participantes actuales (lista clásica) -->
                <div class="tournament-admin-card">
                    <h4><i class="fa-solid fa-users"></i> Participantes Actuales</h4>
                    <div id="admin-participants" class="tournament-admin-participants"></div>
                </div>


                <!-- Acciones del torneo -->
                <div class="tournament-admin-card">
                    <h4><i class="fa-solid fa-gears"></i> Acciones del Torneo</h4>
                    <div class="tournament-admin-actions">
                        <button class="btn btn-success" id="admin-draw-btn">
                            <i class="fa-solid fa-shuffle"></i> Sortear Grupos
                        </button>
                        <button class="btn btn-success" id="admin-gen-matches-btn">
                            <i class="fa-solid fa-calendar-plus"></i> <span data-i18n="tournament.generate_btn">Generar Fase
                                de Grupos</span>
                        </button>
                        <button class="btn btn-primary" id="admin-close-expired-btn">
                            <i class="fa-solid fa-clock"></i> Cerrar Partidos Expirados
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-forward"></i> Avanzar Fase
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item admin-advance" data-phase="round16" href="#">→ Octavos de
                                        Final</a></li>
                                <li><a class="dropdown-item admin-advance" data-phase="quarter" href="#">→ Cuartos de
                                        Final</a></li>
                                <li><a class="dropdown-item admin-advance" data-phase="semi" href="#">→ Semifinales</a></li>
                                <li><a class="dropdown-item admin-advance" data-phase="third" href="#">→ 3° y 4° Puesto</a>
                                </li>
                                <li><a class="dropdown-item admin-advance" data-phase="final" href="#">→ Final</a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline-danger" id="admin-reset-btn">
                            <i class="fa-solid fa-trash"></i> <span data-i18n="tournament.reset_tournament">Resetear
                                Torneo</span>
                        </button>
                    </div>
                </div>

                <!-- Gestión de partidos pendientes -->
                <div class="tournament-admin-card">
                    <h4><i class="fa-solid fa-list-check"></i> <span data-i18n="tournament.pending_matches">Partidos
                            Pendientes (Abrir para votar)</span></h4>
                    <div id="admin-pending-matches" class="tournament-admin-pending"></div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<script src="<?= Router::asset('web/js/tournament/tournament.js') ?>"></script>

<?php require_once __DIR__ . '/../partials/login_modal.php'; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>