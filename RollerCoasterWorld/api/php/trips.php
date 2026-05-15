<?php
/**
 * api/php/trips.php — API completa de Viajes / Agenda
 */
require_once __DIR__ . '/utils/SessionManager.php';
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/RateLimiter.php';
header('Content-Type: application/json');

RateLimiter::check('trips_api', 120, 60);

$uid = $_SESSION['firebase_uid'] ?? null;
if (!$uid) {
    Response::error('No autenticado', 401);
    exit;
}

$db = new DBConexion();
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

$stmtU = $db->prepare("SELECT id FROM users WHERE firebase_uid = ? LIMIT 1");
$stmtU->execute([$uid]);
$userId = (int) ($stmtU->fetchColumn() ?: 0);
if (!$userId) {
    Response::error('Usuario no encontrado', 404);
    exit;
}

$body = ($method === 'POST') ? (json_decode(file_get_contents('php://input'), true) ?? []) : [];

try {
    match (true) {
        // === GET ===
        $action === 'list' && $method === 'GET' => listTrips($db, $userId),
        $action === 'detail' && $method === 'GET' => tripDetail($db, $userId),
        $action === 'calendar' && $method === 'GET' => calendarEvents($db, $userId),
        $action === 'stats' && $method === 'GET' => rideStats($db, $userId),
        $action === 'ride_ranking' && $method === 'GET' => rideRanking($db, $userId),
        $action === 'park_ranking' && $method === 'GET' => parkRanking($db, $userId),
        $action === 'search_parks' && $method === 'GET' => searchParks($db),
        $action === 'search_coasters' && $method === 'GET' => searchCoasters($db),
        $action === 'day_detail' && $method === 'GET' => dayDetail($db, $userId),
        $action === 'collaborators' && $method === 'GET' => listCollaborators($db, $userId),
        $action === 'pending_invites' && $method === 'GET' => pendingInvites($db, $userId),
        // === POST ===
        $action === 'create' && $method === 'POST' => createTrip($db, $userId, $body),
        $action === 'update' && $method === 'POST' => updateTrip($db, $userId, $body),
        $action === 'delete' && $method === 'POST' => deleteTrip($db, $userId, $body),
        $action === 'update_status' && $method === 'POST' => updateStatus($db, $userId, $body),
        $action === 'add_park_day' && $method === 'POST' => addParkDay($db, $userId, $body),
        $action === 'remove_park_day' && $method === 'POST' => removeParkDay($db, $userId, $body),
        $action === 'log_ride' && $method === 'POST' => logRide($db, $userId, $body),
        $action === 'delete_ride' && $method === 'POST' => deleteRide($db, $userId, $body),
        $action === 'add_daily_visit' && $method === 'POST' => addDailyVisit($db, $userId, $body),
        $action === 'remove_daily_visit' && $method === 'POST' => removeDailyVisit($db, $userId, $body),
        $action === 'invite_collaborator' && $method === 'POST' => inviteCollaborator($db, $userId, $body),
        $action === 'respond_invite' && $method === 'POST' => respondInvite($db, $userId, $body),
        $action === 'remove_collaborator' && $method === 'POST' => removeCollaborator($db, $userId, $body),
        $action === 'add_daily_note' && $method === 'POST' => addDailyNote($db, $userId, $body),
        $action === 'delete_daily_note' && $method === 'POST' => deleteDailyNote($db, $userId, $body),
        default => Response::error("Acción no soportada: $action", 400),
    };
} catch (Exception $e) {
    error_log("trips.php error [$action]: " . $e->getMessage());
    Response::error('Error interno del servidor', 500);
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function canEditTrip(DBConexion $db, int $tripId, int $userId): bool
{
    // Corregido: la sesión usa 'user_rol', no 'is_admin'
    $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
    if ($isAdmin)
        return true;

    $s = $db->prepare("SELECT id FROM trips WHERE id=? AND user_id=?");
    $s->execute([$tripId, $userId]);
    if ($s->fetchColumn())
        return true;
    $s2 = $db->prepare("SELECT id FROM trip_collaborators WHERE trip_id=? AND user_id=? AND status='accepted'");
    $s2->execute([$tripId, $userId]);
    return (bool) $s2->fetchColumn();
}

function isTripParticipant(DBConexion $db, int $tripId, int $userId): bool
{
    $s = $db->prepare("SELECT id FROM trips WHERE id=? AND user_id=?");
    $s->execute([$tripId, $userId]);
    if ($s->fetchColumn())
        return true;
    $s2 = $db->prepare("SELECT id FROM trip_collaborators WHERE trip_id=? AND user_id=? AND status='accepted'");
    $s2->execute([$tripId, $userId]);
    return (bool) $s2->fetchColumn();
}

// ── LIST ──────────────────────────────────────────────────────────────────────
function listTrips(DBConexion $db, int $userId): void
{
    $targetUser = isset($_GET['target_user_id']) ? (int) $_GET['target_user_id'] : $userId;
    $sql = "SELECT t.*, u.username as owner_name,
            (SELECT COUNT(DISTINCT tp.park_id) FROM trip_parks tp WHERE tp.trip_id=t.id) as park_count,
            (SELECT COUNT(*) FROM ride_log rl WHERE rl.trip_id=t.id AND rl.user_id=?) as ride_count,
            (SELECT COUNT(*) FROM trip_collaborators tc WHERE tc.trip_id=t.id AND tc.status='accepted') as collab_count,
            (
                SELECT COALESCE(
                    NULLIF(p.imagen_url, ''),
                    (SELECT NULLIF(c.imagen_url, '') FROM coasters c WHERE c.park_id = p.id AND c.imagen_url IS NOT NULL AND c.imagen_url != '' LIMIT 1)
                )
                FROM trip_parks tp
                JOIN parks p ON p.id=tp.park_id
                WHERE tp.trip_id=t.id
                ORDER BY tp.visit_date ASC, tp.visit_order ASC
                LIMIT 1
            ) as cover_image,
            (SELECT STRING_AGG(DISTINCT p.park_name, ', ') FROM trip_parks tp JOIN parks p ON p.id=tp.park_id WHERE tp.trip_id=t.id) as park_names
            FROM trips t JOIN users u ON u.id=t.user_id
            WHERE t.user_id=? OR t.id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')
            ORDER BY t.start_date DESC";
    $s = $db->prepare($sql);
    $s->execute([$targetUser, $targetUser, $targetUser]);
    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── DETAIL ────────────────────────────────────────────────────────────────────
function tripDetail(DBConexion $db, int $userId): void
{
    $tripId = (int) ($_GET['trip_id'] ?? 0);
    if (!$tripId) {
        Response::error('trip_id requerido', 422);
        return;
    }

    $s = $db->prepare("SELECT t.*, u.username as owner_name, u.profile_image as owner_image FROM trips t JOIN users u ON u.id=t.user_id WHERE t.id=?");
    $s->execute([$tripId]);
    $trip = $s->fetch(PDO::FETCH_ASSOC);
    if (!$trip) {
        Response::error('Viaje no encontrado', 404);
        return;
    }

    // Parques por día
    $s2 = $db->prepare("SELECT tp.*, p.park_name, p.park_location, p.imagen_url
        FROM trip_parks tp JOIN parks p ON p.id=tp.park_id WHERE tp.trip_id=? ORDER BY tp.visit_date, tp.visit_order");
    $s2->execute([$tripId]);
    $trip['parks_by_day'] = $s2->fetchAll(PDO::FETCH_ASSOC);

    // Rides
    $s3 = $db->prepare("SELECT rl.*, c.coaster_name, p.park_name
        FROM ride_log rl JOIN coasters c ON c.id=rl.coaster_id JOIN parks p ON p.id=rl.park_id
        WHERE rl.trip_id=? AND rl.user_id=? ORDER BY rl.visit_date, rl.ridden_at");
    $s3->execute([$tripId, $userId]);
    $trip['rides'] = $s3->fetchAll(PDO::FETCH_ASSOC);

    // Países visitados
    $s4 = $db->prepare("SELECT DISTINCT p.park_country FROM trip_parks tp JOIN parks p ON p.id=tp.park_id WHERE tp.trip_id=? AND p.park_country IS NOT NULL AND p.park_country != ''");
    $s4->execute([$tripId]);
    $trip['countries'] = $s4->fetchAll(PDO::FETCH_COLUMN);

    // Total coasters operativas
    $s5 = $db->prepare("SELECT SUM(p.operating_coasters) FROM parks p WHERE p.id IN (SELECT DISTINCT park_id FROM trip_parks WHERE trip_id=?)");
    $s5->execute([$tripId]);
    $trip['total_open_coasters'] = (int) $s5->fetchColumn();

    // Colaboradores aceptados
    $s6 = $db->prepare("SELECT u.username, u.profile_image FROM trip_collaborators tc JOIN users u ON u.id=tc.user_id WHERE tc.trip_id=? AND tc.status='accepted'");
    $s6->execute([$tripId]);
    $trip['collaborators'] = $s6->fetchAll(PDO::FETCH_ASSOC);

    // Coasters del viaje (solo operativas)
    $s7 = $db->prepare("SELECT c.id, c.coaster_name, c.imagen_url, c.speed, c.height, c.inversions, p.park_name, p.id as park_id FROM coasters c JOIN parks p ON c.park_id = p.id WHERE c.park_id IN (SELECT DISTINCT park_id FROM trip_parks WHERE trip_id=?) AND (c.coaster_status = 'Operating' OR c.coaster_status = 'Operativa') ORDER BY p.park_name, c.coaster_name");
    $s7->execute([$tripId]);
    $trip['park_coasters'] = $s7->fetchAll(PDO::FETCH_ASSOC);

    $trip['can_edit'] = canEditTrip($db, $tripId, $userId);
    $trip['is_participant'] = isTripParticipant($db, $tripId, $userId);
    Response::success(['data' => $trip]);
}

// ── CALENDAR ──────────────────────────────────────────────────────────────────
function calendarEvents(DBConexion $db, int $userId): void
{
    $events = [];
    // 1. Obtener los viajes para rellenar los días vacíos
    $tripsStmt = $db->prepare("SELECT id, title, start_date, end_date FROM trips WHERE user_id=? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')");
    $tripsStmt->execute([$userId, $userId]);
    $trips = $tripsStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Trip parks: un evento por día por parque
    $filledDays = [];
    $s = $db->prepare("SELECT tp.id, tp.visit_date, tp.visit_order, p.park_name, p.imagen_url, t.id as trip_id, t.title as trip_title
        FROM trip_parks tp
        JOIN parks p ON p.id=tp.park_id
        JOIN trips t ON t.id=tp.trip_id
        WHERE t.user_id=? OR t.id IN(SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')
        ORDER BY tp.visit_date, tp.visit_order");
    $s->execute([$userId, $userId]);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $tp) {
        $filledDays[$tp['trip_id']][$tp['visit_date']] = true;
        $events[] = [
            'id' => 'tp_' . $tp['id'],
            'title' => $tp['park_name'],
            'start' => $tp['visit_date'],
            'end' => $tp['visit_date'],
            'type' => 'trip_park',
            'trip_id' => (int) $tp['trip_id'],
            'trip_title' => $tp['trip_title'],
            'visit_order' => (int) $tp['visit_order'],
            'imagen_url' => $tp['imagen_url']
        ];
    }

    // 3. Rellenar los días del viaje que no tienen parque
    foreach ($trips as $t) {
        $start = new DateTime($t['start_date']);
        $end = new DateTime($t['end_date']);
        $end->modify('+1 day'); // Para incluir el último día en DatePeriod
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        foreach ($period as $dt) {
            $dateStr = $dt->format('Y-m-d');
            if (!isset($filledDays[$t['id']][$dateStr])) {
                $events[] = [
                    'id' => 'empty_' . $t['id'] . '_' . $dateStr,
                    'title' => 'Día de viaje',
                    'start' => $dateStr,
                    'end' => $dateStr,
                    'type' => 'trip_empty',
                    'trip_id' => (int) $t['id'],
                    'trip_title' => $t['title'],
                    'imagen_url' => ''
                ];
            }
        }
    }
    // Visitas sueltas
    $s2 = $db->prepare("SELECT dv.*, p.park_name, p.imagen_url FROM daily_visits dv JOIN parks p ON p.id=dv.park_id WHERE dv.user_id=?");
    $s2->execute([$userId]);
    foreach ($s2->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $events[] = [
            'id' => 'visit_' . $v['id'],
            'title' => $v['park_name'],
            'start' => $v['visit_date'],
            'end' => $v['visit_date'],
            'type' => 'daily_visit',
            'visit_id' => (int) $v['id'],
            'park_id' => (int) $v['park_id'],
            'imagen_url' => $v['imagen_url']
        ];
    }
    Response::success(['data' => $events]);
}

// ── STATS ─────────────────────────────────────────────────────────────────────
function rideStats(DBConexion $db, int $userId): void
{
    $period = $_GET['period'] ?? 'year';
    $dateFilter = '';
    switch ($period) {
        case 'week':
            $dateFilter = " AND visit_date >= CURRENT_DATE - INTERVAL '7 days'";
            break;
        case 'month':
            $dateFilter = " AND visit_date >= DATE_TRUNC('month', CURRENT_DATE)";
            break;
        case 'year':
            $dateFilter = " AND visit_date >= DATE_TRUNC('year', CURRENT_DATE)";
            break;
        default:
            $dateFilter = '';
            break; // all
    }
    $tripDateFilter = str_replace('visit_date', 'start_date', $dateFilter);

    $s1 = $db->prepare("SELECT COUNT(*) FROM ride_log WHERE user_id=?" . $dateFilter);
    $s1->execute([$userId]);
    $s2 = $db->prepare("SELECT COUNT(*) FROM ride_log WHERE user_id=? AND first_time=true" . $dateFilter);
    $s2->execute([$userId]);
    $s3 = $db->prepare("SELECT COUNT(DISTINCT park_id) FROM ride_log WHERE user_id=?" . $dateFilter);
    $s3->execute([$userId]);
    // Also count parks from daily_visits
    $s3b = $db->prepare("SELECT COUNT(DISTINCT park_id) FROM daily_visits WHERE user_id=?" . str_replace('visit_date', 'visit_date', $dateFilter));
    $s3b->execute([$userId]);
    $s4 = $db->prepare("SELECT COUNT(*) FROM trips WHERE (user_id=? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted'))" . $tripDateFilter);
    $s4->execute([$userId, $userId]);

    $totalParks = (int) $s3->fetchColumn() + (int) $s3b->fetchColumn();
    Response::success([
        'data' => [
            'total_rides' => (int) $s1->fetchColumn(),
            'new_credits' => (int) $s2->fetchColumn(),
            'parks_visited' => $totalParks,
            'total_trips' => (int) $s4->fetchColumn()
        ]
    ]);
}

// ── RIDE RANKING ──────────────────────────────────────────────────────────────
function rideRanking(DBConexion $db, int $userId): void
{
    $targetUser = isset($_GET['target_user_id']) ? (int) $_GET['target_user_id'] : $userId;
    $period = $_GET['period'] ?? 'all';
    $start = $_GET['start'] ?? '';
    $end = $_GET['end'] ?? '';
    $params = [$targetUser];
    $dateFilter = '';

    if ($start && $end) {
        $dateFilter = " AND rl.visit_date >= ? AND rl.visit_date <= ?";
        $params[] = $start;
        $params[] = $end;
    } else {
        if ($period === 'week') {
            $dateFilter = " AND rl.visit_date >= CURRENT_DATE - INTERVAL '7 days'";
        } elseif ($period === 'month') {
            $dateFilter = " AND rl.visit_date >= DATE_TRUNC('month', CURRENT_DATE)";
        } elseif ($period === 'year') {
            $dateFilter = " AND rl.visit_date >= DATE_TRUNC('year', CURRENT_DATE)";
        } elseif (is_numeric($period) && strlen($period) === 4) {
            $dateFilter = " AND EXTRACT(YEAR FROM rl.visit_date) = ?";
            $params[] = (int) $period;
        }
    }
    // Total trips count
    $tripDateFilter = '';
    $tParams = [$targetUser];
    if ($start && $end) {
        $tripDateFilter = " AND start_date <= ? AND end_date >= ?";
        $tParams[] = $end;
        $tParams[] = $start;
    } else {
        if ($period === 'week')
            $tripDateFilter = " AND start_date <= CURRENT_DATE AND end_date >= CURRENT_DATE - INTERVAL '7 days'";
        elseif ($period === 'month')
            $tripDateFilter = " AND start_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month' AND end_date >= DATE_TRUNC('month', CURRENT_DATE)";
        elseif ($period === 'year')
            $tripDateFilter = " AND start_date < DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year' AND end_date >= DATE_TRUNC('year', CURRENT_DATE)";
        elseif (is_numeric($period) && strlen($period) === 4) {
            $tripDateFilter = " AND EXTRACT(YEAR FROM start_date) <= ? AND EXTRACT(YEAR FROM end_date) >= ?";
            $tParams[] = (int) $period;
            $tParams[] = (int) $period;
        }
    }

    $st = $db->prepare("SELECT COUNT(*) FROM trips WHERE (user_id=? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')){$tripDateFilter}");
    $st->execute(array_merge([$targetUser, $targetUser], array_slice($tParams, 1)));
    $totalTrips = (int) $st->fetchColumn();

    $sql = "SELECT c.id as coaster_id, c.coaster_name, c.imagen_url, p.park_name,
        COUNT(*) as times_ridden, MAX(rl.ridden_at) as last_ridden, BOOL_OR(rl.first_time) as was_new
        FROM ride_log rl JOIN coasters c ON c.id=rl.coaster_id JOIN parks p ON p.id=rl.park_id
        WHERE rl.user_id=?{$dateFilter}
        GROUP BY c.id,c.coaster_name,c.imagen_url,p.park_name ORDER BY times_ridden DESC LIMIT 100";
    $s = $db->prepare($sql);
    $s->execute($params);
    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC), 'total_trips' => $totalTrips]);
}

// ── PARK RANKING ──────────────────────────────────────────────────────────────
function parkRanking(DBConexion $db, int $userId): void
{
    $targetUser = isset($_GET['target_user_id']) ? (int) $_GET['target_user_id'] : $userId;
    $period = $_GET['period'] ?? 'all';
    $start = $_GET['start'] ?? '';
    $end = $_GET['end'] ?? '';

    $dateFilterDV = '';
    $dateFilterTP = '';
    $params = [];

    if ($start && $end) {
        $dateFilterDV = " AND dv.visit_date >= ? AND dv.visit_date <= ?";
        $dateFilterTP = " AND tp.visit_date >= ? AND tp.visit_date <= ?";
        $params = [$targetUser, $start, $end, $targetUser, $start, $end];
    } else {
        $params = [$targetUser, $targetUser];
        if ($period === 'week') {
            $dateFilterDV = " AND dv.visit_date >= CURRENT_DATE - INTERVAL '7 days'";
            $dateFilterTP = " AND tp.visit_date >= CURRENT_DATE - INTERVAL '7 days'";
        } elseif ($period === 'month') {
            $dateFilterDV = " AND dv.visit_date >= DATE_TRUNC('month', CURRENT_DATE)";
            $dateFilterTP = " AND tp.visit_date >= DATE_TRUNC('month', CURRENT_DATE)";
        } elseif ($period === 'year') {
            $dateFilterDV = " AND dv.visit_date >= DATE_TRUNC('year', CURRENT_DATE)";
            $dateFilterTP = " AND tp.visit_date >= DATE_TRUNC('year', CURRENT_DATE)";
        } elseif (is_numeric($period) && strlen($period) === 4) {
            $dateFilterDV = " AND EXTRACT(YEAR FROM dv.visit_date) = ?";
            $dateFilterTP = " AND EXTRACT(YEAR FROM tp.visit_date) = ?";
            $params = [$userId, (int) $period, $userId, (int) $period];
        }
    }

    $sql = "SELECT p.id as park_id, p.park_name, p.imagen_url, p.park_location, COUNT(*) as times_visited
            FROM (
                SELECT dv.park_id, dv.visit_date FROM daily_visits dv WHERE dv.user_id=? {$dateFilterDV}
                UNION ALL
                SELECT tp.park_id, tp.visit_date FROM trip_parks tp JOIN trips t ON t.id=tp.trip_id 
                WHERE (t.user_id=? OR t.id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')) {$dateFilterTP}
            ) visits
            JOIN parks p ON p.id = visits.park_id
            GROUP BY p.id, p.park_name, p.imagen_url, p.park_location
            ORDER BY times_visited DESC LIMIT 100";

    // Ajustar parámetros para el nuevo UNION ALL (ahora hay 3 user placeholders: dv.user_id, t.user_id, collaborator.user_id)
    $finalParams = [];
    if ($start && $end) {
        $finalParams = [$targetUser, $start, $end, $targetUser, $targetUser, $start, $end];
    } else {
        if (is_numeric($period) && strlen($period) === 4) {
            $finalParams = [$targetUser, (int) $period, $targetUser, $targetUser, (int) $period];
        } else {
            $finalParams = [$targetUser, $targetUser, $targetUser];
        }
    }

    $s = $db->prepare($sql);
    $s->execute($finalParams);

    // Total trips logic
    $tParams = [$targetUser];
    $tripDateFilter = '';
    if ($start && $end) {
        $tripDateFilter = " AND start_date <= ? AND end_date >= ?";
        $tParams[] = $end;
        $tParams[] = $start;
    } else {
        if ($period === 'week')
            $tripDateFilter = " AND start_date <= CURRENT_DATE AND end_date >= CURRENT_DATE - INTERVAL '7 days'";
        elseif ($period === 'month')
            $tripDateFilter = " AND start_date < DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month' AND end_date >= DATE_TRUNC('month', CURRENT_DATE)";
        elseif ($period === 'year')
            $tripDateFilter = " AND start_date < DATE_TRUNC('year', CURRENT_DATE) + INTERVAL '1 year' AND end_date >= DATE_TRUNC('year', CURRENT_DATE)";
        elseif (is_numeric($period) && strlen($period) === 4) {
            $tripDateFilter = " AND EXTRACT(YEAR FROM start_date) <= ? AND EXTRACT(YEAR FROM end_date) >= ?";
            $tParams[] = (int) $period;
            $tParams[] = (int) $period;
        }
    }
    $st = $db->prepare("SELECT COUNT(*) FROM trips WHERE (user_id=? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')) {$tripDateFilter}");
    $st->execute(array_merge([$targetUser, $targetUser], array_slice($tParams, 1)));
    $totalTrips = (int) $st->fetchColumn();

    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC), 'total_trips' => $totalTrips]);
}

// ── DAY DETAIL ────────────────────────────────────────────────────────────────
function dayDetail(DBConexion $db, int $userId): void
{
    $date = $_GET['date'] ?? '';
    if (!$date) {
        Response::error('date requerido', 422);
        return;
    }
    $tripId = (int) ($_GET['trip_id'] ?? 0);
    // Si hay trip_id, mandan los permisos del viaje. Si no lo hay, es el calendario personal (siempre editable).
    $canEdit = $tripId ? false : true;

    // Visitas sueltas (siempre del usuario logueado)
    $s1 = $db->prepare("SELECT dv.*,p.park_name,p.imagen_url, p.num_coasters, p.operating_coasters, p.opening_year, p.stars FROM daily_visits dv JOIN parks p ON p.id=dv.park_id WHERE dv.user_id=? AND dv.visit_date=?");
    $s1->execute([$userId, $date]);
    $visits = $s1->fetchAll(PDO::FETCH_ASSOC);

    // Trip parks ese día
    if ($tripId) {
        $canEdit = canEditTrip($db, $tripId, $userId);
        $s2 = $db->prepare("SELECT tp.*,p.park_name,p.park_location,p.imagen_url, p.num_coasters, p.operating_coasters, p.opening_year, p.stars, t.title as trip_title,t.id as trip_id
            FROM trip_parks tp JOIN parks p ON p.id=tp.park_id JOIN trips t ON t.id=tp.trip_id
            WHERE tp.trip_id=? AND tp.visit_date=? ORDER BY tp.visit_order");
        $s2->execute([$tripId, $date]);
    } else {
        // Fallback: Si no hay trip_id, devolver los de viajes donde el usuario participa
        $s2 = $db->prepare("SELECT tp.*,p.park_name,p.park_location,p.imagen_url, p.num_coasters, p.operating_coasters, p.opening_year, p.stars, t.title as trip_title,t.id as trip_id
            FROM trip_parks tp JOIN parks p ON p.id=tp.park_id JOIN trips t ON t.id=tp.trip_id
            WHERE tp.visit_date=? AND (t.user_id=? OR t.id IN(SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted'))
            ORDER BY tp.visit_order");
        $s2->execute([$date, $userId, $userId]);
    }
    $tripParks = $s2->fetchAll(PDO::FETCH_ASSOC);

    // Rides ese día (del usuario logueado)
    $s3 = $db->prepare("SELECT rl.*,c.coaster_name,p.park_name FROM ride_log rl
        JOIN coasters c ON c.id=rl.coaster_id JOIN parks p ON p.id=rl.park_id WHERE rl.user_id=? AND rl.visit_date=? ORDER BY rl.ridden_at");
    $s3->execute([$userId, $date]);
    $rides = $s3->fetchAll(PDO::FETCH_ASSOC);

    // Coasters de cada parque activo ese día (trip_parks + daily_visits)
    $parkIds = array_values(array_unique(array_merge(
        array_column($tripParks, 'park_id'),
        array_column($visits, 'park_id')
    )));
    $coastersByPark = [];
    $notesByPark = [];
    if (!empty($parkIds)) {
        $placeholders = implode(',', array_fill(0, count($parkIds), '?'));

        // Coasters
        $sc = $db->prepare("SELECT id,coaster_name,imagen_url,park_id FROM coasters
            WHERE park_id IN($placeholders) AND coaster_status = 'Operating' ORDER BY coaster_name");
        $sc->execute($parkIds);
        foreach ($sc->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $coastersByPark[(int) $c['park_id']][] = $c;
        }

        // Notes
        $sn = $db->prepare("SELECT id, note_text, created_at, park_id FROM daily_notes 
            WHERE user_id = ? AND visit_date = ? AND park_id IN($placeholders) ORDER BY created_at ASC");
        $sn->execute(array_merge([$userId, $date], $parkIds));
        foreach ($sn->fetchAll(PDO::FETCH_ASSOC) as $n) {
            $notesByPark[(int) $n['park_id']][] = $n;
        }
    }

    Response::success([
        'data' => [
            'daily_visits' => $visits,
            'trip_parks' => $tripParks,
            'rides' => $rides,
            'coasters_by_park' => $coastersByPark,
            'notes_by_park' => $notesByPark,
            'can_edit' => $canEdit
        ]
    ]);
}

// ── SEARCH PARKS (diacritic-insensitive via unaccent) ────────────────────────
function searchParks(DBConexion $db): void
{
    $raw = $_GET['q'] ?? '';
    $q = '%' . $raw . '%';
    // unaccent() normalizes accented chars so 'kolmarden' matches 'Kölmarden'
    $s = $db->prepare("SELECT id,park_name,park_location,park_country,imagen_url FROM parks
        WHERE unaccent(LOWER(park_name)) LIKE unaccent(LOWER(?)) ORDER BY park_name LIMIT 20");
    $s->execute([$q]);
    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── SEARCH COASTERS (diacritic-insensitive via unaccent) ──────────────────────
function searchCoasters(DBConexion $db): void
{
    $parkId = (int) ($_GET['park_id'] ?? 0);
    $q = '%' . ($_GET['q'] ?? '') . '%';
    if ($parkId) {
        $s = $db->prepare("SELECT id,coaster_name,imagen_url FROM coasters WHERE park_id=? AND unaccent(LOWER(coaster_name)) LIKE unaccent(LOWER(?)) ORDER BY coaster_name LIMIT 30");
        $s->execute([$parkId, $q]);
    } else {
        $s = $db->prepare("SELECT c.id,c.coaster_name,c.imagen_url,p.park_name FROM coasters c JOIN parks p ON p.id=c.park_id WHERE unaccent(LOWER(c.coaster_name)) LIKE unaccent(LOWER(?)) ORDER BY c.coaster_name LIMIT 20");
        $s->execute([$q]);
    }
    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}

// ── CREATE ────────────────────────────────────────────────────────────────────
function createTrip(DBConexion $db, int $userId, array $body): void
{
    $title = trim($body['title'] ?? '');
    $desc = trim($body['description'] ?? '');
    $start = $body['start_date'] ?? '';
    $end = $body['end_date'] ?? '';
    $countries = trim($body['countries'] ?? '');
    if (!$title || !$start || !$end) {
        Response::error('Faltan campos obligatorios', 422);
        return;
    }

    // Verificar solapamiento
    $chk = $db->prepare("SELECT COUNT(*) FROM trips WHERE (user_id=? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')) AND start_date <= ? AND end_date >= ?");
    $chk->execute([$userId, $userId, $end, $start]);
    if ((int) $chk->fetchColumn() > 0) {
        Response::error('Ya tienes otro viaje programado que coincide con estas fechas', 409);
        return;
    }

    $db->beginTransaction();
    $s = $db->prepare("INSERT INTO trips(user_id,title,description,start_date,end_date,trip_type,parks_visited) VALUES(?,?,?,?,?,'manual',?) RETURNING id");
    $s->execute([$userId, $title, $desc, $start, $end, $countries]);
    $tripId = (int) $s->fetchColumn();

    // Parques por día (opcional en creación)
    if (!empty($body['parks']) && is_array($body['parks'])) {
        $ps = $db->prepare("INSERT INTO trip_parks(trip_id,park_id,visit_date,visit_order) VALUES(?,?,?,?)");
        foreach ($body['parks'] as $p) {
            $ps->execute([$tripId, (int) $p['park_id'], $p['visit_date'], (int) ($p['visit_order'] ?? 1)]);
        }
    }
    $db->commit();
    Response::success(['data' => ['trip_id' => $tripId], 'message' => 'Viaje creado']);
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
function updateTrip(DBConexion $db, int $userId, array $body): void
{
    $tripId = (int) ($body['trip_id'] ?? 0);
    if (!$tripId || !canEditTrip($db, $tripId, $userId)) {
        Response::error('Sin permisos', 403);
        return;
    }

    if (isset($body['start_date']) || isset($body['end_date'])) {
        $currS = $db->prepare("SELECT start_date, end_date FROM trips WHERE id=?");
        $currS->execute([$tripId]);
        $curr = $currS->fetch(PDO::FETCH_ASSOC);

        $newStart = $body['start_date'] ?? $curr['start_date'];
        $newEnd = $body['end_date'] ?? $curr['end_date'];

        $chk = $db->prepare("SELECT COUNT(*) FROM trips WHERE (user_id=? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id=? AND status='accepted')) AND id != ? AND start_date <= ? AND end_date >= ?");
        $chk->execute([$userId, $userId, $tripId, $newEnd, $newStart]);
        if ((int) $chk->fetchColumn() > 0) {
            Response::error('Ya tienes otro viaje programado que coincide con estas nuevas fechas', 409);
            return;
        }
    }
    $fields = [];
    $params = [];
    foreach (['title', 'description', 'start_date', 'end_date', 'status', 'parks_visited'] as $f) {
        if (isset($body[$f])) {
            $fields[] = "$f=?";
            $params[] = $body[$f];
        }
    }
    if (empty($fields)) {
        Response::error('Nada que actualizar', 422);
        return;
    }
    $params[] = $tripId;
    $db->prepare("UPDATE trips SET " . implode(',', $fields) . " WHERE id=?")->execute($params);
    Response::success(['message' => 'Viaje actualizado']);
}

// ── DELETE ────────────────────────────────────────────────────────────────────
function deleteTrip(DBConexion $db, int $userId, array $body): void
{
    $tripId = (int) ($body['trip_id'] ?? 0);
    if (!$tripId) {
        Response::error('trip_id requerido', 422);
        return;
    }

    $s = $db->prepare("SELECT user_id FROM trips WHERE id=?");
    $s->execute([$tripId]);
    $trip = $s->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        Response::error('El viaje no existe o ya ha sido eliminado', 404);
        return;
    }

    $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
    if ((int) $trip['user_id'] !== $userId && !$isAdmin) {
        Response::error('No tienes permisos para eliminar este viaje', 403);
        return;
    }

    $db->prepare("DELETE FROM trips WHERE id=?")->execute([$tripId]);
    Response::success(['message' => 'Viaje eliminado']);
}

// ── UPDATE STATUS ─────────────────────────────────────────────────────────────
function updateStatus(DBConexion $db, int $userId, array $body): void
{
    $tripId = (int) ($body['trip_id'] ?? 0);
    $status = $body['status'] ?? '';
    if (!in_array($status, ['planned', 'active', 'completed'])) {
        Response::error('Estado inválido', 422);
        return;
    }
    if (!canEditTrip($db, $tripId, $userId)) {
        Response::error('Sin permisos', 403);
        return;
    }
    $db->prepare("UPDATE trips SET status=? WHERE id=?")->execute([$status, $tripId]);
    Response::success(['message' => 'Estado actualizado']);
}

// ── ADD PARK DAY ──────────────────────────────────────────────────────────────
function addParkDay(DBConexion $db, int $userId, array $body): void
{
    $tripId = (int) ($body['trip_id'] ?? 0);
    $parkId = (int) ($body['park_id'] ?? 0);
    $date = $body['visit_date'] ?? '';
    $order = (int) ($body['visit_order'] ?? 1);
    if (!$tripId || !$parkId || !$date) {
        Response::error('Campos requeridos', 422);
        return;
    }
    if (!canEditTrip($db, $tripId, $userId)) {
        Response::error('Sin permisos', 403);
        return;
    }
    $db->prepare("INSERT INTO trip_parks(trip_id,park_id,visit_date,visit_order) VALUES(?,?,?,?) ON CONFLICT(trip_id,visit_date,visit_order) DO UPDATE SET park_id=EXCLUDED.park_id")
        ->execute([$tripId, $parkId, $date, $order]);
    Response::success(['message' => 'Parque añadido al día']);
}

// ── REMOVE PARK DAY ───────────────────────────────────────────────────────────
function removeParkDay(DBConexion $db, int $userId, array $body): void
{
    $id = (int) ($body['id'] ?? 0);
    if (!$id) {
        Response::error('id requerido', 422);
        return;
    }
    $s = $db->prepare("SELECT tp.trip_id, tp.park_id, tp.visit_date FROM trip_parks tp WHERE tp.id=?");
    $s->execute([$id]);
    $tp = $s->fetch(PDO::FETCH_ASSOC);
    if (!$tp || !canEditTrip($db, $tp['trip_id'], $userId)) {
        Response::error('Sin permisos', 403);
        return;
    }
    $db->prepare("DELETE FROM ride_log WHERE user_id=? AND park_id=? AND visit_date=? AND trip_id=?")->execute([$userId, $tp['park_id'], $tp['visit_date'], $tp['trip_id']]);
    $db->prepare("DELETE FROM daily_notes WHERE user_id=? AND park_id=? AND visit_date=?")->execute([$userId, $tp['park_id'], $tp['visit_date']]);
    $db->prepare("DELETE FROM trip_parks WHERE id=?")->execute([$id]);
    Response::success(['message' => 'Parque eliminado del día']);
}

// ── LOG RIDE ──────────────────────────────────────────────────────────────────
function logRide(DBConexion $db, int $userId, array $body): void
{
    $parkId = (int) ($body['park_id'] ?? 0);
    $coasterId = (int) ($body['coaster_id'] ?? 0);
    $date = $body['visit_date'] ?? date('Y-m-d');
    $tripId = !empty($body['trip_id']) ? (int) $body['trip_id'] : null;
    $seatRow = !empty($body['seat_row']) ? (int) $body['seat_row'] : null;
    $notes = trim($body['notes'] ?? '');
    $firstTime = isset($body['first_time']) ? (bool) $body['first_time'] : true;
    $riddenAt = !empty($body['ridden_at']) ? $body['ridden_at'] : null;

    if (!$parkId || !$coasterId) {
        Response::error('park_id y coaster_id requeridos', 422);
        return;
    }
    if ($tripId && !canEditTrip($db, $tripId, $userId)) {
        Response::error('No tienes permisos para registrar en este viaje', 403);
        return;
    }

    // Auto-detect first_time
    $chk = $db->prepare("SELECT COUNT(*) FROM ride_log WHERE user_id=? AND coaster_id=?");
    $chk->execute([$userId, $coasterId]);
    if ((int) $chk->fetchColumn() > 0)
        $firstTime = false;

    if ($riddenAt) {
        $s = $db->prepare("INSERT INTO ride_log(trip_id,park_id,coaster_id,user_id,visit_date,ridden_at,seat_row,first_time,notes) VALUES(?,?,?,?,?,?,?,?,?) RETURNING id");
        $s->execute([$tripId, $parkId, $coasterId, $userId, $date, $riddenAt, $seatRow, $firstTime ? 'true' : 'false', $notes]);
    } else {
        $s = $db->prepare("INSERT INTO ride_log(trip_id,park_id,coaster_id,user_id,visit_date,seat_row,first_time,notes) VALUES(?,?,?,?,?,?,?,?) RETURNING id");
        $s->execute([$tripId, $parkId, $coasterId, $userId, $date, $seatRow, $firstTime ? 'true' : 'false', $notes]);
    }

    $id = (int) $s->fetchColumn();

    // AUTO-ADD A TOP PERSONAL (user_credits) SI NO EXISTE
    $chkCred = $db->prepare("SELECT COUNT(*) FROM user_credits WHERE user_id=? AND coaster_id=?");
    $chkCred->execute([$userId, $coasterId]);
    if ((int) $chkCred->fetchColumn() === 0) {
        $sRank = $db->prepare("SELECT COALESCE(MAX(rank_position), 0) FROM user_credits WHERE user_id=?");
        $sRank->execute([$userId]);
        $maxRank = (int) $sRank->fetchColumn();

        $insCred = $db->prepare("INSERT INTO user_credits(user_id, coaster_id, rank_position) VALUES(?, ?, ?)");
        $insCred->execute([$userId, $coasterId, $maxRank + 1]);
    }

    // AUTO-ADD A TOP DE PARQUES (user_park_credits) SI NO EXISTE
    $chkParkTop = $db->prepare("SELECT COUNT(*) FROM user_park_credits WHERE user_id=? AND park_id=?");
    $chkParkTop->execute([$userId, $parkId]);
    if ((int) $chkParkTop->fetchColumn() === 0) {
        $sRankP = $db->prepare("SELECT COALESCE(MAX(rank_position), 0) FROM user_park_credits WHERE user_id=?");
        $sRankP->execute([$userId]);
        $maxRankP = (int) $sRankP->fetchColumn();

        $insPark = $db->prepare("INSERT INTO user_park_credits(user_id, park_id, rank_position) VALUES(?, ?, ?)");
        $insPark->execute([$userId, $parkId, $maxRankP + 1]);
    }

    Response::success(['data' => ['ride_id' => $id, 'first_time' => $firstTime], 'message' => 'Ride registrado']);
}

// ── DELETE RIDE ───────────────────────────────────────────────────────────────
function deleteRide(DBConexion $db, int $userId, array $body): void
{
    $id = (int) ($body['ride_id'] ?? 0);
    if (!$id) {
        Response::error('ride_id requerido', 422);
        return;
    }
    $db->prepare("DELETE FROM ride_log WHERE id=? AND user_id=?")->execute([$id, $userId]);
    Response::success(['message' => 'Ride eliminado']);
}

// ── ADD DAILY VISIT ───────────────────────────────────────────────────────────
function addDailyVisit(DBConexion $db, int $userId, array $body): void
{
    $parkId = (int) ($body['park_id'] ?? 0);
    $date = $body['visit_date'] ?? date('Y-m-d');
    $notes = trim($body['notes'] ?? '');
    if (!$parkId) {
        Response::error('park_id requerido', 422);
        return;
    }

    // Comprobar si la fecha cae dentro de un viaje activo (propio o como colaborador)
    $stmt = $db->prepare("SELECT id FROM trips WHERE start_date <= ? AND end_date >= ? AND (user_id = ? OR id IN (SELECT trip_id FROM trip_collaborators WHERE user_id = ? AND status='accepted')) ORDER BY start_date DESC LIMIT 1");
    $stmt->execute([$date, $date, $userId, $userId]);
    $activeTripId = $stmt->fetchColumn();

    if ($activeTripId) {
        // Añadir al viaje en lugar de daily_visits
        $so = $db->prepare("SELECT COALESCE(MAX(visit_order), 0) + 1 FROM trip_parks WHERE trip_id = ? AND visit_date = ?");
        $so->execute([$activeTripId, $date]);
        $order = $so->fetchColumn();

        $db->prepare("INSERT INTO trip_parks(trip_id, park_id, visit_date, visit_order) VALUES(?,?,?,?)")
            ->execute([$activeTripId, $parkId, $date, $order]);

        Response::success(['message' => 'Parque añadido al viaje activo']);
        return;
    }

    // Si no hay viaje, registrar como visita diaria normal
    $db->prepare("INSERT INTO daily_visits(user_id,park_id,visit_date,notes) VALUES(?,?,?,?) ON CONFLICT(user_id,park_id,visit_date) DO NOTHING")
        ->execute([$userId, $parkId, $date, $notes]);
    Response::success(['message' => 'Visita registrada']);
}

// ── REMOVE DAILY VISIT ───────────────────────────────────────────────────────
function removeDailyVisit(DBConexion $db, int $userId, array $body): void
{
    $id = (int) ($body['visit_id'] ?? 0);
    if (!$id) {
        Response::error('visit_id requerido', 422);
        return;
    }

    $s = $db->prepare("SELECT park_id, visit_date FROM daily_visits WHERE id=? AND user_id=?");
    $s->execute([$id, $userId]);
    $v = $s->fetch(PDO::FETCH_ASSOC);
    if ($v) {
        $db->prepare("DELETE FROM ride_log WHERE user_id=? AND park_id=? AND visit_date=? AND trip_id IS NULL")->execute([$userId, $v['park_id'], $v['visit_date']]);
        $db->prepare("DELETE FROM daily_notes WHERE user_id=? AND park_id=? AND visit_date=?")->execute([$userId, $v['park_id'], $v['visit_date']]);
        $db->prepare("DELETE FROM daily_visits WHERE id=? AND user_id=?")->execute([$id, $userId]);
    }
    Response::success(['message' => 'Visita eliminada']);
}

// ── COLLABORATORS ─────────────────────────────────────────────────────────────
function listCollaborators(DBConexion $db, int $userId): void
{
    $tripId = (int) ($_GET['trip_id'] ?? 0);
    $s = $db->prepare("SELECT tc.*,u.username,u.profile_image FROM trip_collaborators tc JOIN users u ON u.id=tc.user_id WHERE tc.trip_id=? ORDER BY tc.joined_at");
    $s->execute([$tripId]);
    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}

function pendingInvites(DBConexion $db, int $userId): void
{
    $s = $db->prepare("SELECT tc.*,t.title as trip_title,u.username as invited_by_name
        FROM trip_collaborators tc JOIN trips t ON t.id=tc.trip_id JOIN users u ON u.id=tc.invited_by
        WHERE tc.user_id=? AND tc.status='pending' ORDER BY tc.joined_at DESC");
    $s->execute([$userId]);
    Response::success(['data' => $s->fetchAll(PDO::FETCH_ASSOC)]);
}

function inviteCollaborator(DBConexion $db, int $userId, array $body): void
{
    $tripId = (int) ($body['trip_id'] ?? 0);
    $username = trim($body['username'] ?? '');
    if (!$tripId || !$username) {
        Response::error('Campos requeridos', 422);
        return;
    }
    $s = $db->prepare("SELECT id FROM trips WHERE id=? AND user_id=?");
    $s->execute([$tripId, $userId]);
    if (!$s->fetchColumn()) {
        Response::error('Solo el creador puede invitar', 403);
        return;
    }
    $s2 = $db->prepare("SELECT id FROM users WHERE username=?");
    $s2->execute([$username]);
    $targetId = (int) $s2->fetchColumn();
    if (!$targetId) {
        Response::error('Usuario no encontrado', 404);
        return;
    }
    if ($targetId === $userId) {
        Response::error('No puedes invitarte a ti mismo', 422);
        return;
    }
    $db->prepare("INSERT INTO trip_collaborators(trip_id,user_id,invited_by,status) VALUES(?,?,?,'pending') ON CONFLICT(trip_id,user_id) DO NOTHING")
        ->execute([$tripId, $targetId, $userId]);
    Response::success(['message' => 'Invitación enviada']);
}

function respondInvite(DBConexion $db, int $userId, array $body): void
{
    $id = (int) ($body['invite_id'] ?? 0);
    $accept = (bool) ($body['accept'] ?? false);
    if (!$id) {
        Response::error('invite_id requerido', 422);
        return;
    }
    $status = $accept ? 'accepted' : 'rejected';
    $db->prepare("UPDATE trip_collaborators SET status=? WHERE id=? AND user_id=?")->execute([$status, $id, $userId]);
    Response::success(['message' => $accept ? 'Invitación aceptada' : 'Invitación rechazada']);
}

function removeCollaborator(DBConexion $db, int $userId, array $body): void
{
    $tripId = (int) ($body['trip_id'] ?? 0);
    $targetId = (int) ($body['user_id'] ?? 0);
    $s = $db->prepare("SELECT user_id FROM trips WHERE id=?");
    $s->execute([$tripId]);
    $ownerId = (int) $s->fetchColumn();
    if ($userId !== $ownerId && $userId !== $targetId) {
        Response::error('Sin permisos', 403);
        return;
    }
    $db->prepare("DELETE FROM trip_collaborators WHERE trip_id=? AND user_id=?")->execute([$tripId, $targetId]);
    Response::success(['message' => 'Colaborador eliminado']);
}

// ── DAILY NOTES ──────────────────────────────────────────────────────────────
function addDailyNote(DBConexion $db, int $userId, array $body): void
{
    $parkId = (int) ($body['park_id'] ?? 0);
    $date = $body['visit_date'] ?? '';
    $text = trim($body['note_text'] ?? '');
    if (!$parkId || !$date || !$text) {
        Response::error('Campos requeridos', 422);
        return;
    }
    $db->prepare("INSERT INTO daily_notes(user_id,park_id,visit_date,note_text) VALUES(?,?,?,?)")
        ->execute([$userId, $parkId, $date, $text]);
    Response::success(['message' => 'Nota añadida']);
}

function deleteDailyNote(DBConexion $db, int $userId, array $body): void
{
    $noteId = (int) ($body['note_id'] ?? 0);
    if (!$noteId) {
        Response::error('note_id requerido', 422);
        return;
    }
    $db->prepare("DELETE FROM daily_notes WHERE id=? AND user_id=?")->execute([$noteId, $userId]);
    Response::success(['message' => 'Nota eliminada']);
}
