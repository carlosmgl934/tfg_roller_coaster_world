<?php
/**
 * tournament.php — API del Torneo Roller Coaster Euro Cup
 * ─────────────────────────────────────────────────────────
 * Endpoints independientes para gestionar el torneo.
 * Se borrará al acabar el evento.
 */
require_once __DIR__ . '/utils/SessionManager.php';

// Asegurar user_id en sesión
if (isset($_SESSION['firebase_uid']) && !isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../database/db_conexion.php';
    $db_init = new DBConexion();
    $stmt_init = $db_init->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
    $stmt_init->execute([':uid' => $_SESSION['firebase_uid']]);
    $user_init = $stmt_init->fetch(PDO::FETCH_ASSOC);
    if ($user_init) {
        $_SESSION['user_id'] = (int) $user_init['id'];
    }
}

require_once __DIR__ . '/../database/db_conexion.php';
header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/utils/ApiRouter.php';

$router = new ApiRouter('get_tournament');

// ── Endpoints públicos (requieren login para votar) ──────────────────────
$router->register('get_tournament', 'getTournament');
$router->register('get_groups', 'getGroups');
$router->register('get_bracket', 'getBracket');
$router->register('get_match', 'getMatch');
$router->register('get_active_matches', 'getActiveMatches');
$router->register('vote', 'castVote', 'POST');

// ── Endpoints admin ──────────────────────────────────────────────────────
$router->register('search_coasters', 'searchCoastersForTournament');
$router->register('add_coaster', 'addCoasterToTournament', 'POST');
$router->register('remove_coaster', 'removeCoasterFromTournament', 'POST');
$router->register('get_participants', 'getParticipants');
$router->register('draw_groups', 'drawGroups', 'POST');
$router->register('generate_matches', 'generateGroupMatches', 'POST');
$router->register('open_match', 'openMatch', 'POST');
$router->register('close_expired', 'closeExpiredMatches', 'POST');
$router->register('advance_phase', 'advancePhase', 'POST');
$router->register('reset_tournament', 'resetTournament', 'POST');
$router->register('get_all_group_matches', 'getAllGroupMatches');
$router->register('get_recent_matches', 'getRecentMatches');

// ── Endpoints de imágenes personalizadas ─────────────────────────────────
$router->register('get_settings', 'getSettings');
$router->register('upload_coaster_image', 'uploadCoasterImage', 'POST');
$router->register('upload_hero_image', 'uploadHeroImage', 'POST');
$router->register('set_coaster_image', 'setCoasterImage', 'POST');  // fallback URL
$router->register('set_hero_image', 'setHeroImage', 'POST');     // fallback URL

$router->dispatch();

/* ═══════════════════════════════════════════════════════════════════════════
   FUNCIONES AUXILIARES
   ═══════════════════════════════════════════════════════════════════════════ */

function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized('Debes iniciar sesión para realizar esta acción');
    }
}

function requireAdmin()
{
    requireLogin();
    if (empty($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
        Response::unauthorized('Acción restringida a administradores');
    }
}

/**
 * ¿Hay un usuario logueado en esta sesión?
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Quita de una fila los campos de recuento de votos / puntos.
 * Se usa para no enviar esos datos a visitantes no logueados,
 * sea cual sea el endpoint por el que los pidan.
 */
function stripVoteFields(array $row): array
{
    unset(
        $row['votes_coaster1'],
        $row['votes_coaster2'],
        $row['votes_for'],
        $row['votes_against'],
        $row['points']
    );
    return $row;
}

/**
 * Auto-cierra partidos cuya ends_at haya pasado.
 * Se llama automáticamente al consultar datos del torneo.
 */
function autoCloseExpired()
{
    global $db;
    try {
        // Buscar partidos activos cuyo tiempo ha expirado
        $stmt = $db->prepare("
            SELECT id, coaster1_id, coaster2_id, votes_coaster1, votes_coaster2, phase, group_letter
            FROM tournament_matches
            WHERE status = 'active' AND ends_at <= NOW()
        ");
        $stmt->execute();
        $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($expired as $match) {
            finalizeMatch($match);
        }
    } catch (PDOException $e) {
        // Silently ignore — non-critical
    }
}

/**
 * Finaliza un partido: calcula ganador, actualiza puntos en grupo.
 */
// Duración de cada prórroga cuando un cruce eliminatorio sigue empatado.
// asumo que también quieres esto en 24h como dice la UI. Si no, cambia
// solo esta constante.)
// Usa define() para constantes globales
define('MATCH_VOTE_INTERVAL', '24 hours');
function finalizeMatch($match)
{
    global $db;

    // 1. GUARD PREVIO: Si el array ya viene marcado como 'finished', cortamos ejecución
    if (($match['status'] ?? '') === 'finished') {
        return;
    }

    $v1 = (int) $match['votes_coaster1'];
    $v2 = (int) $match['votes_coaster2'];
    $total = $v1 + $v2;
    $isKnockout = $match['phase'] !== 'group';

    // En eliminatorias, si hay empate, se prorroga y se detiene la ejecución
    if ($isKnockout && $v1 === $v2) {
        extendTiedMatch($match['id']);
        return;
    }

    $winnerId = null;
    $isDraw = false;

    if ($total === 0 || $v1 === $v2) {
        $isDraw = true;
    } elseif ($v1 > $v2) {
        $winnerId = $match['coaster1_id'];
    } else {
        $winnerId = $match['coaster2_id'];
    }

    // 2. ACTUALIZACIÓN ATÓMICA: Solo pasa a 'finished' si ANTES no lo estaba
    $stmt = $db->prepare("
        UPDATE tournament_matches
        SET status = 'finished', winner_id = :winner, is_draw = :draw
        WHERE id = :id AND status != 'finished'
    ");
    $stmt->execute([
        ':winner' => $winnerId,
        ':draw' => $isDraw ? 'true' : 'false',
        ':id' => $match['id']
    ]);

    // 3. BLOQUEO DE SEGURIDAD: Si no se modificó ninguna fila, significa que
    // otra petición paralela ya procesó este partido milisegundos antes.
    if ($stmt->rowCount() === 0) {
        return;
    }

    // 4. Si llegamos aquí, es la PRIMERA Y ÚNICA vez que se ejecuta la suma
    if ($match['phase'] === 'group') {
        $c1 = $match['coaster1_id'];
        $c2 = $match['coaster2_id'];

        if ($isDraw) {
            updateGroupStats($c1, 1, $v1, $v2, 0, 1, 0);
            updateGroupStats($c2, 1, $v2, $v1, 0, 1, 0);
        } elseif ($winnerId == $c1) {
            updateGroupStats($c1, 3, $v1, $v2, 1, 0, 0);
            updateGroupStats($c2, 0, $v2, $v1, 0, 0, 1);
        } else {
            updateGroupStats($c2, 3, $v2, $v1, 1, 0, 0);
            updateGroupStats($c1, 0, $v1, $v2, 0, 0, 1);
        }
    }
}

function extendTiedMatch($matchId)
{
    global $db;

    // Evitamos concatenaciones en el intervalo para asegurar compatibilidad total con PostgreSQL
    $stmt = $db->prepare("
        UPDATE tournament_matches
        SET ends_at = NOW() + INTERVAL '30 seconds'
        WHERE id = :id
    ");
    $stmt->execute([':id' => $matchId]);
}

function updateGroupStats($coasterId, $pts, $vFor, $vAgainst, $w, $d, $l)
{
    global $db;
    $stmt = $db->prepare("
        UPDATE tournament_coasters
        SET points = points + :pts,
            votes_for = votes_for + :vf,
            votes_against = votes_against + :va,
            matches_played = matches_played + 1,
            wins = wins + :w,
            draws = draws + :d,
            losses = losses + :l
        WHERE coaster_id = :cid
    ");
    $stmt->execute([
        ':pts' => $pts,
        ':vf' => $vFor,
        ':va' => $vAgainst,
        ':w' => $w,
        ':d' => $d,
        ':l' => $l,
        ':cid' => $coasterId
    ]);
}


/* ═══════════════════════════════════════════════════════════════════════════
   ENDPOINTS PÚBLICOS
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * GET — Estado completo del torneo
 */
function getTournament()
{
    global $db;
    autoCloseExpired();

    try {
        // Grupos con coasters
        $groups = fetchGroups();

        // Partidos activos
        $active = fetchActiveMatches();

        // Bracket eliminatorio
        $bracket = fetchBracket();

        // Partidos de fase de grupos (para la pestaña Jornadas)
        $groupMatches = fetchGroupMatches();
        $recent = fetchRecentMatches();

        // Ocultar recuentos de voto a quien no ha iniciado sesión
        if (!isLoggedIn()) {
            foreach ($groups as $letter => $coasters) {
                $groups[$letter] = array_map('stripVoteFields', $coasters);
            }
            $active = array_map('stripVoteFields', $active);
            foreach ($bracket as $phase => $matches) {
                $bracket[$phase] = array_map('stripVoteFields', $matches);
            }
            $groupMatches = array_map('stripVoteFields', $groupMatches);
            $recent = array_map('stripVoteFields', $recent); // ← faltaba también aquí
        }

        // Total participantes
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM tournament_coasters");
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        Response::success([
            'total_coasters' => (int) $total,
            'groups' => $groups,
            'group_matches' => $groupMatches,
            'active_matches' => $active,
            'recent_matches' => $recent, // ← esto es lo que faltaba
            'bracket' => $bracket
        ]);
    } catch (PDOException $e) {
        Response::error('Error al obtener datos del torneo: ' . $e->getMessage());
    }
}

function getGroups()
{
    autoCloseExpired();
    $groups = fetchGroups();
    if (!isLoggedIn()) {
        foreach ($groups as $letter => $coasters) {
            $groups[$letter] = array_map('stripVoteFields', $coasters);
        }
    }
    Response::success(['groups' => $groups]);
}

function getBracket()
{
    autoCloseExpired();
    $bracket = fetchBracket();
    if (!isLoggedIn()) {
        foreach ($bracket as $phase => $matches) {
            $bracket[$phase] = array_map('stripVoteFields', $matches);
        }
    }
    Response::success(['bracket' => $bracket]);
}

function getActiveMatches()
{
    autoCloseExpired();
    $matches = fetchActiveMatches();
    if (!isLoggedIn()) {
        $matches = array_map('stripVoteFields', $matches);
    }
    Response::success(['matches' => $matches]);
}

function getMatch()
{
    global $db;
    autoCloseExpired();

    $matchId = (int) ($_GET['match_id'] ?? 0);
    if (!$matchId)
        Response::error('match_id requerido');

    try {
        $stmt = $db->prepare("
            SELECT m.*,
                   c1.coaster_name AS name1, c1.imagen_url AS img1, c1.height AS h1, c1.speed AS s1, c1.inversions AS inv1, c1.stars AS stars1,
                   p1.park_name AS park1, p1.park_country AS country1,
                   c2.coaster_name AS name2, c2.imagen_url AS img2, c2.height AS h2, c2.speed AS s2, c2.inversions AS inv2, c2.stars AS stars2,
                   p2.park_name AS park2, p2.park_country AS country2
            FROM tournament_matches m
            JOIN coasters c1 ON m.coaster1_id = c1.id
            JOIN parks p1 ON c1.park_id = p1.id
            JOIN coasters c2 ON m.coaster2_id = c2.id
            JOIN parks p2 ON c2.park_id = p2.id
            WHERE m.id = :id
        ");
        $stmt->execute([':id' => $matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match)
            Response::notFound('Partido no encontrado');

        // Comprobar si el usuario ya ha votado
        $userVote = null;
        if (isLoggedIn()) {
            $stmtV = $db->prepare("SELECT voted_coaster_id FROM tournament_votes WHERE match_id = :mid AND user_id = :uid");
            $stmtV->execute([':mid' => $matchId, ':uid' => $_SESSION['user_id']]);
            $v = $stmtV->fetch(PDO::FETCH_ASSOC);
            if ($v)
                $userVote = (int) $v['voted_coaster_id'];
        } else {
            $match = stripVoteFields($match);
        }

        $match['user_vote'] = $userVote;
        Response::success(['match' => $match]);
    } catch (PDOException $e) {
        Response::error('Error al obtener el partido');
    }
}
/**
 * POST — Votar por una coaster en un partido activo
 */
function castVote()
{
    global $db;
    requireLogin();

    $input = json_decode(file_get_contents('php://input'), true);
    $matchId = (int) ($input['match_id'] ?? 0);
    $coasterId = (int) ($input['coaster_id'] ?? 0);

    if (!$matchId || !$coasterId) {
        Response::error('match_id y coaster_id son requeridos');
    }

    try {
        // Verificar que el partido está activo y no ha expirado
        $stmt = $db->prepare("
            SELECT id, coaster1_id, coaster2_id, status, ends_at
            FROM tournament_matches
            WHERE id = :id AND status = 'active' AND ends_at > NOW()
        ");
        $stmt->execute([':id' => $matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            Response::error('El partido no está activo o ha expirado');
        }

        // Verificar que la coaster es una de las del partido
        if ($coasterId != $match['coaster1_id'] && $coasterId != $match['coaster2_id']) {
            Response::error('La coaster no participa en este partido');
        }

        // Verificar que no ha votado ya
        $stmtCheck = $db->prepare("SELECT id FROM tournament_votes WHERE match_id = :mid AND user_id = :uid");
        $stmtCheck->execute([':mid' => $matchId, ':uid' => $_SESSION['user_id']]);
        if ($stmtCheck->fetch()) {
            Response::error('Ya has votado en este partido. No se puede cambiar el voto.');
        }

        // Registrar voto
        $db->beginTransaction();

        $stmtVote = $db->prepare("
            INSERT INTO tournament_votes (match_id, user_id, voted_coaster_id)
            VALUES (:mid, :uid, :cid)
        ");
        $stmtVote->execute([
            ':mid' => $matchId,
            ':uid' => $_SESSION['user_id'],
            ':cid' => $coasterId
        ]);

        // Incrementar contador en el partido
        $col = ($coasterId == $match['coaster1_id']) ? 'votes_coaster1' : 'votes_coaster2';
        $stmtUp = $db->prepare("UPDATE tournament_matches SET $col = $col + 1 WHERE id = :id");
        $stmtUp->execute([':id' => $matchId]);

        $db->commit();

        // -------------------------------------------------------------
        // DETECCIÓN DE MUERTE SÚBITA (GOL DE ORO) EN ELIMINATORIAS
        // -------------------------------------------------------------
        $stmtSuddenDeath = $db->prepare("
            SELECT * 
            FROM tournament_matches 
            WHERE id = :id 
              AND phase != 'group' 
              AND votes_coaster1 != votes_coaster2 
              AND NOW() >= starts_at + INTERVAL '30 seconds'
              AND status = 'active'
        ");
        $stmtSuddenDeath->execute([':id' => $matchId]);
        $matchData = $stmtSuddenDeath->fetch(PDO::FETCH_ASSOC);

        if ($matchData) {
            // 1. Cortamos el tiempo extra de inmediato fijando el final a AHORA
            $db->prepare("UPDATE tournament_matches SET ends_at = NOW() WHERE id = :id")
                ->execute([':id' => $matchId]);

            // 2. Llamamos a tu función para que declare al ganador y cierre el cruce
            finalizeMatch($matchData);
        }
        // -------------------------------------------------------------

        // Devolver votos actualizados
        $stmtResult = $db->prepare("SELECT votes_coaster1, votes_coaster2 FROM tournament_matches WHERE id = :id");
        $stmtResult->execute([':id' => $matchId]);
        $result = $stmtResult->fetch(PDO::FETCH_ASSOC);

        Response::success([
            'message' => '¡Voto registrado!',
            'votes_coaster1' => (int) $result['votes_coaster1'],
            'votes_coaster2' => (int) $result['votes_coaster2']
        ]);
    } catch (PDOException $e) {
        if ($db->inTransaction())
            $db->rollBack();
        // UNIQUE violation = ya votó
        if (strpos($e->getMessage(), 'unique') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
            Response::error('Ya has votado en este partido');
        }
        Response::error('Error al registrar el voto: ' . $e->getMessage());
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   ENDPOINTS ADMIN
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * GET — Buscar coasters para añadir al torneo (admin)
 */
function searchCoastersForTournament()
{
    global $db;
    requireAdmin();

    $q = $_GET['q'] ?? '';
    try {
        $stmt = $db->prepare("
            SELECT c.id, c.coaster_name, c.imagen_url, c.height, c.speed, c.inversions, c.stars,
                   p.park_name, p.park_country,
                   (SELECT 1 FROM tournament_coasters tc WHERE tc.coaster_id = c.id) AS already_added
            FROM coasters c
            JOIN parks p ON c.park_id = p.id
            WHERE (LOWER(c.coaster_name) LIKE LOWER(:q) OR LOWER(p.park_name) LIKE LOWER(:q2))
            ORDER BY c.stars DESC NULLS LAST
            LIMIT 30
        ");
        $stmt->execute([':q' => "%$q%", ':q2' => "%$q%"]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['coasters' => $results]);
    } catch (PDOException $e) {
        Response::error('Error en búsqueda: ' . $e->getMessage());
    }
}

/**
 * POST — Añadir coaster al torneo (max 32)
 */
function addCoasterToTournament()
{
    global $db;
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);
    $coasterId = (int) ($input['coaster_id'] ?? 0);
    if (!$coasterId)
        Response::error('coaster_id requerido');

    try {
        // Verificar que no hay más de 32
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM tournament_coasters");
        $stmt->execute();
        if ((int) $stmt->fetch(PDO::FETCH_ASSOC)['total'] >= 32) {
            Response::error('Ya hay 32 coasters en el torneo (máximo)');
        }

        // Obtener país de la coaster
        $stmtC = $db->prepare("SELECT p.park_country FROM coasters c JOIN parks p ON c.park_id = p.id WHERE c.id = :id");
        $stmtC->execute([':id' => $coasterId]);
        $country = $stmtC->fetch(PDO::FETCH_ASSOC)['park_country'] ?? null;

        $stmtIns = $db->prepare("INSERT INTO tournament_coasters (coaster_id, country) VALUES (:cid, :country)");
        $stmtIns->execute([':cid' => $coasterId, ':country' => $country]);

        Response::success(['message' => 'Coaster añadida al torneo']);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'unique') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
            Response::error('Esta coaster ya está en el torneo');
        }
        Response::error('Error al añadir: ' . $e->getMessage());
    }
}

/**
 * POST — Quitar coaster del torneo
 */
function removeCoasterFromTournament()
{
    global $db;
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);
    $coasterId = (int) ($input['coaster_id'] ?? 0);
    if (!$coasterId)
        Response::error('coaster_id requerido');

    try {
        $stmt = $db->prepare("DELETE FROM tournament_coasters WHERE coaster_id = :cid");
        $stmt->execute([':cid' => $coasterId]);
        Response::success(['message' => 'Coaster eliminada del torneo']);
    } catch (PDOException $e) {
        Response::error('Error al eliminar: ' . $e->getMessage());
    }
}

/**
 * GET — Lista de participantes actuales
 */
function getParticipants()
{
    global $db;
    try {
        $stmt = $db->prepare("
            SELECT tc.id, tc.coaster_id, tc.group_letter, tc.country,
                   tc.points, tc.votes_for, tc.votes_against,
                   tc.matches_played, tc.wins, tc.draws, tc.losses,
                   tc.custom_image_url,
                   c.coaster_name, c.imagen_url, c.height, c.speed, c.inversions, c.stars,
                   COALESCE(tc.custom_image_url, c.imagen_url) AS display_img,
                   p.park_name, p.park_country
            FROM tournament_coasters tc
            JOIN coasters c ON tc.coaster_id = c.id
            JOIN parks p ON c.park_id = p.id
            ORDER BY tc.group_letter ASC NULLS LAST, tc.points DESC, (tc.votes_for - tc.votes_against) DESC
        ");
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['participants' => $participants]);
    } catch (PDOException $e) {
        Response::error('Error al obtener participantes');
    }
}

/**
 * POST — Sorteo de grupos (8 grupos × 4, restricción de país)
 */
function drawGroups()
{
    global $db;
    requireAdmin();

    try {
        // Verificar que hay exactamente 32
        $stmt = $db->prepare("SELECT coaster_id, country FROM tournament_coasters");
        $stmt->execute();
        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($coasters) !== 32) {
            Response::error('Se necesitan exactamente 32 coasters para el sorteo. Hay: ' . count($coasters));
        }

        // Intentar sorteo con restricción de país (máx 200 intentos)
        $groups = null;
        for ($attempt = 0; $attempt < 200; $attempt++) {
            $groups = tryDraw($coasters);
            if ($groups !== null)
                break;
        }

        if ($groups === null) {
            Response::error('No se pudo realizar el sorteo respetando la restricción de país. Revisa que no haya más de 8 coasters del mismo país.');
        }

        // Guardar asignación de grupos
        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($groups as $i => $group) {
            foreach ($group as $coaster) {
                $stmtUp = $db->prepare("UPDATE tournament_coasters SET group_letter = :gl WHERE coaster_id = :cid");
                $stmtUp->execute([':gl' => $letters[$i], ':cid' => $coaster['coaster_id']]);
            }
        }

        Response::success(['message' => 'Sorteo realizado con éxito', 'groups' => $groups]);
    } catch (PDOException $e) {
        Response::error('Error en el sorteo: ' . $e->getMessage());
    }
}

/**
 * Intenta distribuir 32 coasters en 8 grupos de 4 sin repetir país.
 */
function tryDraw($coasters)
{
    shuffle($coasters);

    $groups = array_fill(0, 8, []);
    $groupCountries = array_fill(0, 8, []);

    foreach ($coasters as $coaster) {
        $placed = false;
        $candidates = range(0, 7);
        shuffle($candidates);

        foreach ($candidates as $g) {
            if (count($groups[$g]) >= 4)
                continue;
            if (in_array($coaster['country'], $groupCountries[$g]))
                continue;

            $groups[$g][] = $coaster;
            $groupCountries[$g][] = $coaster['country'];
            $placed = true;
            break;
        }

        if (!$placed)
            return null; // Sorteo inválido
    }

    return $groups;
}

/**
 * POST — Generar todos los partidos de fase de grupos
 */
function generateGroupMatches()
{
    global $db;
    requireAdmin();

    try {
        // Verificar que hay grupos asignados
        $stmt = $db->prepare("SELECT COUNT(*) as c FROM tournament_coasters WHERE group_letter IS NOT NULL");
        $stmt->execute();
        if ((int) $stmt->fetch(PDO::FETCH_ASSOC)['c'] !== 32) {
            Response::error('Primero debes realizar el sorteo de grupos');
        }

        // Eliminar partidos de grupo anteriores
        $db->prepare("DELETE FROM tournament_matches WHERE phase = 'group'")->execute();

        $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $matchNum = 1;

        foreach ($letters as $letter) {
            // Obtener las 4 coasters del grupo
            $stmtG = $db->prepare("SELECT coaster_id FROM tournament_coasters WHERE group_letter = :gl ORDER BY RANDOM()");
            $stmtG->execute([':gl' => $letter]);
            $ids = $stmtG->fetchAll(PDO::FETCH_COLUMN);

            // Generar todos contra todos (6 partidos)
            for ($i = 0; $i < count($ids); $i++) {
                for ($j = $i + 1; $j < count($ids); $j++) {
                    $stmtIns = $db->prepare("
                        INSERT INTO tournament_matches (phase, group_letter, match_number, coaster1_id, coaster2_id)
                        VALUES ('group', :gl, :mn, :c1, :c2)
                    ");
                    $stmtIns->execute([
                        ':gl' => $letter,
                        ':mn' => $matchNum,
                        ':c1' => $ids[$i],
                        ':c2' => $ids[$j]
                    ]);
                    $matchNum++;
                }
            }
        }

        Response::success(['message' => "Generados " . ($matchNum - 1) . " partidos de fase de grupos"]);
    } catch (PDOException $e) {
        Response::error('Error al generar partidos: ' . $e->getMessage());
    }
}

/**
 * POST — Abrir un partido (activarlo con countdown de 24h)
 */
function openMatch()
{
    global $db;
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);
    $matchId = (int) ($input['match_id'] ?? 0);
    if (!$matchId)
        Response::error('match_id requerido');

    try {
        $stmt = $db->prepare("
        UPDATE tournament_matches
        SET status = 'active', 
            starts_at = NOW(), 
            ends_at = NOW() + INTERVAL '30 seconds'
        WHERE id = :id AND status = 'pending'
    ");

        $stmt->execute([':id' => $matchId]);

        if ($stmt->rowCount() === 0) {
            Response::error('Partido no encontrado o ya está activo/finalizado');
        }

        Response::success(['message' => 'Partido abierto (24h para votar)']);
    } catch (PDOException $e) {
        Response::error('Error al abrir partido: ' . $e->getMessage());
    }
}

/**
 * POST — Cerrar partidos expirados manualmente
 */
function closeExpiredMatches()
{
    requireAdmin();
    autoCloseExpired();
    Response::success(['message' => 'Partidos expirados cerrados']);
}

/**
 * POST — Avanzar a la siguiente fase (genera partidos eliminatorios)
 */
function advancePhase()
{
    global $db;
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);
    $nextPhase = $input['phase'] ?? '';

    $validPhases = ['round16', 'quarter', 'semi', 'third', 'final'];
    if (!in_array($nextPhase, $validPhases)) {
        Response::error('Fase no válida. Opciones: ' . implode(', ', $validPhases));
    }

    try {
        if ($nextPhase === 'round16') {
            generateRound16();
        } elseif ($nextPhase === 'quarter') {
            generateNextRound('round16', 'quarter');
        } elseif ($nextPhase === 'semi') {
            generateNextRound('quarter', 'semi');
        } elseif ($nextPhase === 'third') {
            generateThirdPlace();
        } elseif ($nextPhase === 'final') {
            generateFinal();
        }

        Response::success(['message' => "Fase $nextPhase generada con éxito"]);
    } catch (PDOException $e) {
        Response::error('Error al avanzar fase: ' . $e->getMessage());
    }
}
/**
 * Genera octavos de final mediante un sorteo inteligente:
 * 1. Siempre enfrentando a un 1º de grupo contra un 2º de grupo.
 * 2. PROHIBIDO: Enfrentar coasters que provengan del mismo grupo (ej. 1A vs 2A).
 * 3. EVITA: Enfrentar coasters del mismo país (intenta conseguir 0 coincidencias de país).
 */
function generateRound16()
{
    global $db;

    // 1. Verificar que todos los partidos de grupo están finalizados
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM tournament_matches WHERE phase = 'group' AND status != 'finished'");
    $stmt->execute();
    if ((int) $stmt->fetch(PDO::FETCH_ASSOC)['c'] > 0) {
        Response::error('Aún hay partidos de grupo sin finalizar');
    }

    $groups = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    $winners = [];
    $runnersUp = [];

    // 2. Obtener 1ºs y 2ºs de cada grupo junto con su país
    foreach ($groups as $letter) {
        $wId = getGroupPosition($letter, 1);
        $rId = getGroupPosition($letter, 2);

        if (!$wId || !$rId) {
            Response::error("Error obteniendo clasificados del grupo {$letter}");
        }

        // Consultar país de ambas montañas rusas
        $stmtC = $db->prepare("SELECT coaster_id AS id, country FROM tournament_coasters WHERE coaster_id IN (:w, :r)");
        $stmtC->execute([':w' => $wId, ':r' => $rId]);
        $coastersData = $stmtC->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        $winners[] = [
            'id' => $wId,
            'group' => $letter,
            'country' => $coastersData[$wId]['country'] ?? ''
        ];

        $runnersUp[] = [
            'id' => $rId,
            'group' => $letter,
            'country' => $coastersData[$rId]['country'] ?? ''
        ];
    }

    // 3. Algoritmo de Sorteo Inteligente (Monte Carlo)
    $maxAttempts = 2000;
    $bestDraw = null;
    $minCountryCollisions = 999;

    for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
        shuffle($runnersUp); // Aleatorizamos las posiciones de los 2ºs puestos

        $validDraw = true;
        $countryCollisions = 0;
        $currentMatches = [];

        for ($i = 0; $i < 8; $i++) {
            $w = $winners[$i];
            $r = $runnersUp[$i];

            // REGLA 1 (OBLIGATORIA): Prohibido mismo grupo (ej. 1A vs 2A)
            if ($w['group'] === $r['group']) {
                $validDraw = false;
                break;
            }

            // REGLA 2 (PREFERENTE): Contar coincidencias de país
            if (!empty($w['country']) && !empty($r['country']) && strtolower($w['country']) === strtolower($r['country'])) {
                $countryCollisions++;
            }

            $currentMatches[] = [
                'coaster1_id' => $w['id'],
                'coaster2_id' => $r['id']
            ];
        }

        if ($validDraw) {
            // Si logramos 0 coincidencias de país, es el sorteo ideal
            if ($countryCollisions === 0) {
                $bestDraw = $currentMatches;
                break;
            }

            // Si es imposible tener 0 colisiones, guardamos la opción con menos cruces nacionales
            if ($countryCollisions < $minCountryCollisions) {
                $minCountryCollisions = $countryCollisions;
                $bestDraw = $currentMatches;
            }
        }
    }

    if (!$bestDraw) {
        Response::error('No se pudo calcular un sorteo válido para octavos');
    }

    // 4. Eliminar octavos anteriores e insertar el nuevo cuadro en la BD
    $db->prepare("DELETE FROM tournament_matches WHERE phase = 'round16'")->execute();

    $stmtIns = $db->prepare("
        INSERT INTO tournament_matches (phase, match_number, coaster1_id, coaster2_id)
        VALUES ('round16', :mn, :c1, :c2)
    ");

    $matchNum = 1;
    foreach ($bestDraw as $match) {
        $stmtIns->execute([
            ':mn' => $matchNum,
            ':c1' => $match['coaster1_id'],
            ':c2' => $match['coaster2_id']
        ]);
        $matchNum++;
    }
}

/**
 * Obtiene la coaster en la posición N del grupo.
 */
function getGroupPosition($letter, $position)
{
    global $db;
    $stmt = $db->prepare("
        SELECT tc.*, c.coaster_name
        FROM tournament_coasters tc
        JOIN coasters c ON tc.coaster_id = c.id
        WHERE tc.group_letter = :gl
    ");
    $stmt->execute([':gl' => $letter]);
    $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ranked = rankGroupCoasters($coasters, $letter);
    return isset($ranked[$position - 1]) ? (int) $ranked[$position - 1]['coaster_id'] : null;
}

/**
 * Genera la siguiente ronda eliminatoria a partir de los ganadores de la anterior.
 */
function generateNextRound($prevPhase, $nextPhase)
{
    global $db;

    // Verificar que la fase anterior está completa
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM tournament_matches WHERE phase = :p AND status != 'finished'");
    $stmt->execute([':p' => $prevPhase]);
    if ((int) $stmt->fetch(PDO::FETCH_ASSOC)['c'] > 0) {
        Response::error("Aún hay partidos de $prevPhase sin finalizar");
    }

    // Obtener ganadores en orden de match_number
    $stmt = $db->prepare("SELECT winner_id FROM tournament_matches WHERE phase = :p ORDER BY match_number ASC");
    $stmt->execute([':p' => $prevPhase]);
    $winners = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Eliminar ronda anterior
    $db->prepare("DELETE FROM tournament_matches WHERE phase = :p")->execute([':p' => $nextPhase]);

    // Emparejar: 1v2, 3v4, etc.
    $matchNum = 1;
    for ($i = 0; $i < count($winners); $i += 2) {
        $stmtIns = $db->prepare("
            INSERT INTO tournament_matches (phase, match_number, coaster1_id, coaster2_id)
            VALUES (:phase, :mn, :c1, :c2)
        ");
        $stmtIns->execute([
            ':phase' => $nextPhase,
            ':mn' => $matchNum,
            ':c1' => $winners[$i],
            ':c2' => $winners[$i + 1]
        ]);
        $matchNum++;
    }
}

/**
 * Genera el partido de 3° y 4° puesto (perdedores de semis).
 */
function generateThirdPlace()
{
    global $db;

    $stmt = $db->prepare("
        SELECT coaster1_id, coaster2_id, winner_id
        FROM tournament_matches WHERE phase = 'semi' AND status = 'finished'
        ORDER BY match_number ASC
    ");
    $stmt->execute();
    $semis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($semis) !== 2) {
        Response::error('Las semifinales no están completas');
    }

    $losers = [];
    foreach ($semis as $s) {
        $loser = ($s['winner_id'] == $s['coaster1_id']) ? $s['coaster2_id'] : $s['coaster1_id'];
        $losers[] = $loser;
    }

    $db->prepare("DELETE FROM tournament_matches WHERE phase = 'third'")->execute();
    $stmtIns = $db->prepare("
        INSERT INTO tournament_matches (phase, match_number, coaster1_id, coaster2_id)
        VALUES ('third', 1, :c1, :c2)
    ");
    $stmtIns->execute([':c1' => $losers[0], ':c2' => $losers[1]]);
}

/**
 * Genera la final (ganadores de semis).
 */
function generateFinal()
{
    global $db;

    $stmt = $db->prepare("
        SELECT winner_id FROM tournament_matches
        WHERE phase = 'semi' AND status = 'finished'
        ORDER BY match_number ASC
    ");
    $stmt->execute();
    $winners = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($winners) !== 2) {
        Response::error('Las semifinales no están completas');
    }

    $db->prepare("DELETE FROM tournament_matches WHERE phase = 'final'")->execute();
    $stmtIns = $db->prepare("
        INSERT INTO tournament_matches (phase, match_number, coaster1_id, coaster2_id)
        VALUES ('final', 1, :c1, :c2)
    ");
    $stmtIns->execute([':c1' => $winners[0], ':c2' => $winners[1]]);
}

/**
 * POST — Resetear todo el torneo
 */
function resetTournament()
{
    global $db;
    requireAdmin();

    try {
        $db->prepare("DELETE FROM tournament_votes")->execute();
        $db->prepare("DELETE FROM tournament_matches")->execute();
        $db->prepare("UPDATE tournament_coasters SET group_letter = NULL, points = 0, votes_for = 0, votes_against = 0, matches_played = 0, wins = 0, draws = 0, losses = 0, eliminated_at = NULL, final_position = NULL")->execute();
        Response::success(['message' => 'Torneo reseteado']);
    } catch (PDOException $e) {
        Response::error('Error al resetear: ' . $e->getMessage());
    }
}


/* ═══════════════════════════════════════════════════════════════════════════
   FUNCIONES DE DATOS (fetch helpers)
   ═══════════════════════════════════════════════════════════════════════════ */

function fetchGroups()
{
    global $db;
    $stmt = $db->prepare("
        SELECT tc.*, c.coaster_name, c.stars,
               COALESCE(tc.custom_image_url, c.imagen_url) AS imagen_url,
               p.park_name, p.park_country
        FROM tournament_coasters tc
        JOIN coasters c ON tc.coaster_id = c.id
        JOIN parks p ON c.park_id = p.id
        WHERE tc.group_letter IS NOT NULL
        ORDER BY tc.group_letter ASC
    ");
    $stmt->execute();
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $groups = [];
    foreach ($all as $row) {
        $g = $row['group_letter'];
        if (!isset($groups[$g]))
            $groups[$g] = [];
        $groups[$g][] = $row;
    }

    foreach ($groups as $letter => $coasters) {
        $groups[$letter] = rankGroupCoasters($coasters, $letter);
    }

    return $groups;
}

/**
 * Clasifica las coasters de un grupo aplicando el desempate:
 * 1) Puntos  2) Enfrentamientos directos entre empatadas  3) Diferencia de votos general
 */
function rankGroupCoasters(array $coasters, string $groupLetter): array
{
    global $db;
    if (empty($coasters))
        return [];

    // Agrupar por puntos (de más a menos)
    $byPoints = [];
    foreach ($coasters as $c) {
        $byPoints[(int) $c['points']][] = $c;
    }
    krsort($byPoints);

    // Partidos finalizados de este grupo, para los enfrentamientos directos
    $stmt = $db->prepare("
        SELECT coaster1_id, coaster2_id, winner_id, is_draw
        FROM tournament_matches
        WHERE phase = 'group' AND group_letter = :gl AND status = 'finished'
    ");
    $stmt->execute([':gl' => $groupLetter]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ranked = [];
    foreach ($byPoints as $tied) {
        if (count($tied) === 1) {
            $ranked[] = $tied[0];
            continue;
        }

        $tiedIds = array_map(fn($c) => (int) $c['coaster_id'], $tied);

        // Mini-liga: solo puntos de los partidos jugados ENTRE las empatadas
        $miniPoints = array_fill_keys($tiedIds, 0);
        foreach ($matches as $m) {
            $c1 = (int) $m['coaster1_id'];
            $c2 = (int) $m['coaster2_id'];
            if (!in_array($c1, $tiedIds, true) || !in_array($c2, $tiedIds, true))
                continue;

            $isDraw = in_array($m['is_draw'], ['t', true, '1', 1], true);
            if ($isDraw) {
                $miniPoints[$c1] += 1;
                $miniPoints[$c2] += 1;
            } else {
                $winner = (int) $m['winner_id'];
                if (isset($miniPoints[$winner]))
                    $miniPoints[$winner] += 3;
            }
        }

        usort($tied, function ($a, $b) use ($miniPoints) {
            // 1º Criterio: Puntos en mini-liga
            $mp = $miniPoints[(int) $b['coaster_id']] <=> $miniPoints[(int) $a['coaster_id']];
            if ($mp !== 0)
                return $mp;

            // 2º Criterio: Diferencia de votos general
            $diffA = (int) $a['votes_for'] - (int) $a['votes_against'];
            $diffB = (int) $b['votes_for'] - (int) $b['votes_against'];
            if ($diffA !== $diffB)
                return $diffB - $diffA;

            // 3º Criterio: Votos a favor totales
            $vf = (int) $b['votes_for'] - (int) $a['votes_for'];
            if ($vf !== 0)
                return $vf;

            // 4º Criterio (DESEMPATE FINAL): Nombre alfabético A-Z
            $nameA = $a['coaster_name'] ?? '';
            $nameB = $b['coaster_name'] ?? '';
            if ($nameA !== '' && $nameB !== '') {
                $cmp = strcasecmp($nameA, $nameB);
                if ($cmp !== 0)
                    return $cmp;
            }

            // 5º Criterio de seguridad por ID
            return (int) $a['coaster_id'] <=> (int) $b['coaster_id'];
        });

        foreach ($tied as $c)
            $ranked[] = $c;
    }

    return $ranked;
}

function fetchActiveMatches()
{
    global $db;
    $stmt = $db->prepare("
        SELECT m.*,
               c1.coaster_name AS name1, c1.stars AS stars1,
               COALESCE(tc1.custom_image_url, c1.imagen_url) AS img1,
               p1.park_name AS park1, p1.park_country AS country1,
               c2.coaster_name AS name2, c2.stars AS stars2,
               COALESCE(tc2.custom_image_url, c2.imagen_url) AS img2,
               p2.park_name AS park2, p2.park_country AS country2
        FROM tournament_matches m
        JOIN coasters c1 ON m.coaster1_id = c1.id
        JOIN parks p1 ON c1.park_id = p1.id
        LEFT JOIN tournament_coasters tc1 ON tc1.coaster_id = c1.id
        JOIN coasters c2 ON m.coaster2_id = c2.id
        JOIN parks p2 ON c2.park_id = p2.id
        LEFT JOIN tournament_coasters tc2 ON tc2.coaster_id = c2.id
        WHERE m.status = 'active'
        ORDER BY m.ends_at ASC
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Añadir voto del usuario actual si está logueado
    if (isset($_SESSION['user_id'])) {
        foreach ($matches as &$m) {
            $stmtV = $db->prepare("SELECT voted_coaster_id FROM tournament_votes WHERE match_id = :mid AND user_id = :uid");
            $stmtV->execute([':mid' => $m['id'], ':uid' => $_SESSION['user_id']]);
            $v = $stmtV->fetch(PDO::FETCH_ASSOC);
            $m['user_vote'] = $v ? (int) $v['voted_coaster_id'] : null;
        }
    }

    return $matches;
}

function fetchRecentMatches()
{
    global $db;
    $stmt = $db->prepare("
        SELECT m.*,
               c1.coaster_name AS name1, c1.stars AS stars1,
               COALESCE(tc1.custom_image_url, c1.imagen_url) AS img1,
               p1.park_name AS park1, p1.park_country AS country1,
               c2.coaster_name AS name2, c2.stars AS stars2,
               COALESCE(tc2.custom_image_url, c2.imagen_url) AS img2,
               p2.park_name AS park2, p2.park_country AS country2
        FROM tournament_matches m
        JOIN coasters c1 ON m.coaster1_id = c1.id
        JOIN parks p1 ON c1.park_id = p1.id
        LEFT JOIN tournament_coasters tc1 ON tc1.coaster_id = c1.id
        JOIN coasters c2 ON m.coaster2_id = c2.id
        JOIN parks p2 ON c2.park_id = p2.id
        LEFT JOIN tournament_coasters tc2 ON tc2.coaster_id = c2.id
        WHERE m.status = 'finished' AND m.ends_at >= NOW() - INTERVAL '24 hours'
        ORDER BY m.ends_at DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentMatches()
{
    autoCloseExpired();
    $matches = fetchRecentMatches();
    if (!isLoggedIn()) {
        $matches = array_map('stripVoteFields', $matches);
    }
    Response::success(['matches' => $matches]);
}

function fetchBracket()
{
    global $db;
    $phases = ['round16', 'quarter', 'semi', 'third', 'final'];
    $bracket = [];

    foreach ($phases as $phase) {
        $stmt = $db->prepare("
            SELECT m.*,
                   c1.coaster_name AS name1,
                   COALESCE(tc1.custom_image_url, c1.imagen_url) AS img1,
                   p1.park_name AS park1, p1.park_country AS country1,
                   c2.coaster_name AS name2,
                   COALESCE(tc2.custom_image_url, c2.imagen_url) AS img2,
                   p2.park_name AS park2, p2.park_country AS country2
            FROM tournament_matches m
            JOIN coasters c1 ON m.coaster1_id = c1.id
            JOIN parks p1 ON c1.park_id = p1.id
            LEFT JOIN tournament_coasters tc1 ON tc1.coaster_id = c1.id
            JOIN coasters c2 ON m.coaster2_id = c2.id
            JOIN parks p2 ON c2.park_id = p2.id
            LEFT JOIN tournament_coasters tc2 ON tc2.coaster_id = c2.id
            WHERE m.phase = :p
            ORDER BY m.match_number ASC
        ");
        $stmt->execute([':p' => $phase]);
        $bracket[$phase] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $bracket;
}

function fetchGroupMatches()
{
    global $db;
    $stmt = $db->prepare("
        SELECT m.*,
               c1.coaster_name AS name1,
               COALESCE(tc1.custom_image_url, c1.imagen_url) AS img1,
               p1.park_name AS park1, p1.park_country AS country1,
               c2.coaster_name AS name2,
               COALESCE(tc2.custom_image_url, c2.imagen_url) AS img2,
               p2.park_name AS park2, p2.park_country AS country2
        FROM tournament_matches m
        JOIN coasters c1 ON m.coaster1_id = c1.id
        JOIN parks p1 ON c1.park_id = p1.id
        LEFT JOIN tournament_coasters tc1 ON tc1.coaster_id = c1.id
        JOIN coasters c2 ON m.coaster2_id = c2.id
        JOIN parks p2 ON c2.park_id = p2.id
        LEFT JOIN tournament_coasters tc2 ON tc2.coaster_id = c2.id
        WHERE m.phase = 'group'
        ORDER BY m.group_letter ASC, m.match_number ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * GET — Lista todos los partidos (grupo + eliminatorios) para admin
 */
function getAllGroupMatches()
{
    global $db;
    requireAdmin();
    autoCloseExpired();

    try {
        $stmt = $db->prepare("
            SELECT m.*,
                   c1.coaster_name AS name1,
                   COALESCE(tc1.custom_image_url, c1.imagen_url) AS img1,
                   p1.park_name AS park1, p1.park_country AS country1,
                   c2.coaster_name AS name2,
                   COALESCE(tc2.custom_image_url, c2.imagen_url) AS img2,
                   p2.park_name AS park2, p2.park_country AS country2
            FROM tournament_matches m
            JOIN coasters c1 ON m.coaster1_id = c1.id
            JOIN parks p1 ON c1.park_id = p1.id
            LEFT JOIN tournament_coasters tc1 ON tc1.coaster_id = c1.id
            JOIN coasters c2 ON m.coaster2_id = c2.id
            JOIN parks p2 ON c2.park_id = p2.id
            LEFT JOIN tournament_coasters tc2 ON tc2.coaster_id = c2.id
            ORDER BY m.phase ASC, m.group_letter ASC NULLS LAST, m.match_number ASC
        ");
        $stmt->execute();
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['matches' => $matches]);
    } catch (PDOException $e) {
        Response::error('Error al obtener partidos: ' . $e->getMessage());
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   ENDPOINTS DE IMÁGENES PERSONALIZADAS
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * GET — Devuelve la configuración del torneo (hero_image_url, etc.)
 */
function getSettings()
{
    global $db;
    try {
        $stmt = $db->prepare("SELECT key, value FROM tournament_settings");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['key']] = $r['value'];
        }
        Response::success(['settings' => $settings]);
    } catch (PDOException $e) {
        // Si la tabla no existe aún, devolver vacío sin error fatal
        Response::success(['settings' => []]);
    }
}

/**
 * POST — Establece la imagen personalizada de una coaster del torneo (admin)
 * Body: { coaster_id: int, image_url: string }
 */
function setCoasterImage()
{
    global $db;
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);
    $cid = (int) ($input['coaster_id'] ?? 0);
    $imgUrl = trim($input['image_url'] ?? '');

    if (!$cid)
        Response::error('coaster_id requerido');

    try {
        $stmt = $db->prepare("
            UPDATE tournament_coasters
            SET custom_image_url = :url
            WHERE coaster_id = :cid
        ");
        $stmt->execute([':url' => $imgUrl ?: null, ':cid' => $cid]);

        if ($stmt->rowCount() === 0) {
            Response::error('Coaster no encontrada en el torneo');
        }
        Response::success(['message' => 'Imagen actualizada']);
    } catch (PDOException $e) {
        Response::error('Error al guardar imagen: ' . $e->getMessage());
    }
}

/**
 * POST — Establece la imagen del hero/portada (admin)
 * Body: { image_url: string }
 */
function setHeroImage()
{
    global $db;
    requireAdmin();

    $input = json_decode(file_get_contents('php://input'), true);
    $imgUrl = trim($input['image_url'] ?? '');

    try {
        $stmt = $db->prepare("
            INSERT INTO tournament_settings (key, value, updated_at)
            VALUES ('hero_image_url', :url, NOW())
            ON CONFLICT (key) DO UPDATE SET value = :url2, updated_at = NOW()
        ");
        $stmt->execute([':url' => $imgUrl, ':url2' => $imgUrl]);
        Response::success(['message' => 'Imagen del hero actualizada']);
    } catch (PDOException $e) {
        Response::error('Error al guardar imagen del hero: ' . $e->getMessage());
    }
}

/* ───────────────────────────────────────────────────────────────────────────
   UPLOAD DE ARCHIVOS (multipart/form-data)
   ─────────────────────────────────────────────────────────────────────────── */

/**
 * Guarda un fichero subido en /web/img/tournament/ y devuelve la URL pública.
 * @param  array  $file   Entrada de $_FILES
 * @param  string $prefix Prefijo del nombre de fichero (ej: "coaster_42_")
 * @return string  URL pública relativa al dominio
 */
function saveTournamentUpload(array $file, string $prefix): string
{
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 10 * 1024 * 1024; // 10 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        Response::error('Error al recibir el fichero (código ' . $file['error'] . ')');
    }
    if (!in_array($file['type'], $allowed, true)) {
        Response::error('Tipo de imagen no permitido. Usa JPG, PNG, WEBP o GIF.');
    }
    if ($file['size'] > $maxSize) {
        Response::error('La imagen supera los 10 MB.');
    }

    $ext = match ($file['type']) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg',
    };
    $filename = $prefix . uniqid('', true) . '.' . $ext;
    $dir = realpath(__DIR__ . '/../../web/img/') . DIRECTORY_SEPARATOR . 'tournament' . DIRECTORY_SEPARATOR;
    $dest = $dir . $filename;

    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $err = error_get_last();
        Response::error('No se pudo guardar la imagen en el servidor. Tmp: ' . $file['tmp_name'] . ' -> ' . $dest . '. Err: ' . ($err['message'] ?? 'None'));
    }

    // Construir URL pública — mismo esquema que el resto del proyecto
    return '/web/img/tournament/' . $filename;
}

/**
 * POST — Sube imagen personalizada para una coaster del torneo (admin)
 * multipart/form-data: coaster_id (int) + image (file)
 */
function uploadCoasterImage()
{
    global $db;
    requireAdmin();

    $cid = (int) ($_POST['coaster_id'] ?? 0);
    if (!$cid)
        Response::error('coaster_id requerido');
    if (empty($_FILES['image']))
        Response::error('No se recibió ningún fichero');

    $url = saveTournamentUpload($_FILES['image'], "coaster_{$cid}_");

    try {
        $stmt = $db->prepare("
            UPDATE tournament_coasters
            SET custom_image_url = :url
            WHERE coaster_id = :cid
        ");
        $stmt->execute([':url' => $url, ':cid' => $cid]);

        if ($stmt->rowCount() === 0) {
            Response::error('Coaster no encontrada en el torneo');
        }
        Response::success(['message' => 'Imagen subida y guardada', 'url' => $url]);
    } catch (PDOException $e) {
        Response::error('Error al guardar imagen: ' . $e->getMessage());
    }
}

/**
 * POST — Sube imagen del hero/portada del torneo (admin)
 * multipart/form-data: image (file)
 */
function uploadHeroImage()
{
    global $db;
    requireAdmin();

    if (empty($_FILES['image']))
        Response::error('No se recibió ningún fichero');

    $url = saveTournamentUpload($_FILES['image'], 'hero_');

    try {
        $stmt = $db->prepare("
            INSERT INTO tournament_settings (key, value, updated_at)
            VALUES ('hero_image_url', :url, NOW())
            ON CONFLICT (key) DO UPDATE SET value = :url2, updated_at = NOW()
        ");
        $stmt->execute([':url' => $url, ':url2' => $url]);
        Response::success(['message' => 'Imagen del hero subida y guardada', 'url' => $url]);
    } catch (PDOException $e) {
        Response::error('Error al guardar imagen del hero: ' . $e->getMessage());
    }
}