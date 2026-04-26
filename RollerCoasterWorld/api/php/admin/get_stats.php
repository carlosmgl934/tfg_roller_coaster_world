<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

header('Content-Type: application/json');

// ── Security Check ───────────────────────────────────────────────────────────
function requireAdmin(): void
{
    if (
        !isset($_SESSION['firebase_uid']) ||
        !isset($_SESSION['user_rol']) ||
        $_SESSION['user_rol'] !== 'admin'
    ) {
        Response::error('Acceso denegado.', 403);
        exit;
    }
}

$db = new DBConexion();

$router = new ApiRouter('getSummary');
$router->register('getSummary',      'getSummary',      'GET');
$router->register('getGrowth',       'getGrowth',       'GET');
$router->register('getDistribution', 'getDistribution', 'GET');
$router->register('getRecentTrips',  'getRecentTrips',  'GET');
$router->dispatch();

// ─────────────────────────────────────────────────────────────
// getSummary — KPI Cards totals
// ─────────────────────────────────────────────────────────────
function getSummary(): void
{
    requireAdmin();
    global $db;

    try {
        $sql = "SELECT 
            (SELECT COUNT(*) FROM users)          AS total_users,
            (SELECT COUNT(*) FROM coasters)       AS total_coasters,
            (SELECT COUNT(*) FROM parks)          AS total_parks,
            (SELECT COUNT(*) FROM coaster_ratings) +
            (SELECT COUNT(*) FROM park_ratings)   AS total_reviews,
            (SELECT COUNT(*) FROM coaster_photos) AS total_photos,
            (SELECT COUNT(*) FROM forum_messages) AS total_forum_posts,
            (SELECT COUNT(*) FROM trips)          AS total_trips";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $stats]);
        exit;
    } catch (PDOException $e) {
        Response::error('Error obteniendo resumen: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// getGrowth — Time-series for charts (Day, Week, Month)
// PostgreSQL / Supabase compatible
// ─────────────────────────────────────────────────────────────
function getGrowth(): void
{
    requireAdmin();
    global $db;

    $period = $_GET['period'] ?? 'month';
    $type   = $_GET['type']   ?? 'users';

    // Whitelist allowed types
    $allowedTypes = ['users', 'reviews', 'coasters', 'parks', 'photos', 'forum_posts', 'trips'];
    if (!in_array($type, $allowedTypes)) {
        Response::error('Tipo no válido');
        return;
    }

    // Regular table mapping (reviews handled separately as a UNION)
    $tableMap = [
        'users'       => 'users',
        'coasters'    => 'coasters',
        'parks'       => 'parks',
        'photos'      => 'coaster_photos',
        'forum_posts' => 'forum_messages',
        'trips'       => 'trips',
    ];
    $isReviews = ($type === 'reviews');
    $dbTable   = $tableMap[$type] ?? null;

    // Whitelist period values and build SQL literals directly (safe — not user interpolated)
    $periodConfig = [
        'day'   => ['trunc' => 'day',   'interval' => '14 days',   'format' => 'YYYY-MM-DD'],
        'week'  => ['trunc' => 'week',  'interval' => '12 weeks',  'format' => 'IYYY-"W"IW'],
        'month' => ['trunc' => 'month', 'interval' => '12 months', 'format' => 'YYYY-MM'],
    ];

    // Soporte para rango personalizado
    if ($period === 'custom') {
        $from = $_GET['from'] ?? null;
        $to   = $_GET['to']   ?? null;
        // Validar formato de fecha (YYYY-MM-DD)
        if (!$from || !$to || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            Response::error('Parámetros from/to inválidos para periodo personalizado');
            return;
        }
        // Máximo 1 año de rango por seguridad
        $daysDiff = (strtotime($to) - strtotime($from)) / 86400;
        if ($daysDiff < 0 || $daysDiff > 366) {
            Response::error('El rango máximo es de 1 año');
            return;
        }
        try {
            if ($isReviews) {
                $sql = "
                    WITH date_range AS (
                        SELECT generate_series(:from_date::date, :to_date::date, INTERVAL '1 day') AS bucket
                    ),
                    all_reviews AS (
                        SELECT id, created_at FROM coaster_ratings
                        UNION ALL
                        SELECT id, created_at FROM park_ratings
                    )
                    SELECT
                        to_char(dr.bucket, 'YYYY-MM-DD') AS label,
                        COUNT(r.id)::int                 AS count
                    FROM date_range dr
                    LEFT JOIN all_reviews r ON date_trunc('day', r.created_at) = dr.bucket
                    GROUP BY dr.bucket
                    ORDER BY dr.bucket ASC
                ";
            } else {
                $sql = "
                    WITH date_range AS (
                        SELECT generate_series(:from_date::date, :to_date::date, INTERVAL '1 day') AS bucket
                    )
                    SELECT
                        to_char(dr.bucket, 'YYYY-MM-DD') AS label,
                        COUNT(t.id)::int                 AS count
                    FROM date_range dr
                    LEFT JOIN {$dbTable} t ON date_trunc('day', t.created_at) = dr.bucket
                    GROUP BY dr.bucket
                    ORDER BY dr.bucket ASC
                ";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute([':from_date' => $from, ':to_date' => $to]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        } catch (PDOException $e) {
            Response::error('Error obteniendo rango personalizado: ' . $e->getMessage());
        }
    }

    if (!array_key_exists($period, $periodConfig)) {
        Response::error('Periodo no válido');
        return;
    }

    $cfg      = $periodConfig[$period];
    $trunc    = $cfg['trunc'];
    $interval = $cfg['interval'];
    $format   = $cfg['format'];

    try {
        // Safely interpolate whitelisted values — no user input reaches SQL
        if ($isReviews) {
            // Combine coaster_ratings + park_ratings for total reviews growth
            $sql = "
                WITH date_range AS (
                    SELECT generate_series(
                        date_trunc('{$trunc}', CURRENT_DATE - INTERVAL '{$interval}'),
                        date_trunc('{$trunc}', CURRENT_DATE),
                        INTERVAL '1 {$trunc}'
                    ) AS bucket
                ),
                all_reviews AS (
                    SELECT id, created_at FROM coaster_ratings
                    UNION ALL
                    SELECT id, created_at FROM park_ratings
                )
                SELECT
                    to_char(dr.bucket, '{$format}') AS label,
                    COUNT(r.id)::int                AS count
                FROM date_range dr
                LEFT JOIN all_reviews r
                       ON date_trunc('{$trunc}', r.created_at) = dr.bucket
                GROUP BY dr.bucket
                ORDER BY dr.bucket ASC
            ";
        } else {
            $sql = "
                WITH date_range AS (
                    SELECT generate_series(
                        date_trunc('{$trunc}', CURRENT_DATE - INTERVAL '{$interval}'),
                        date_trunc('{$trunc}', CURRENT_DATE),
                        INTERVAL '1 {$trunc}'
                    ) AS bucket
                )
                SELECT
                    to_char(dr.bucket, '{$format}') AS label,
                    COUNT(t.id)::int                AS count
                FROM date_range dr
                LEFT JOIN {$dbTable} t
                       ON date_trunc('{$trunc}', t.created_at) = dr.bucket
                GROUP BY dr.bucket
                ORDER BY dr.bucket ASC
            ";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    } catch (PDOException $e) {
        Response::error('Error obteniendo crecimiento: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// getDistribution — Data for Pie/Bar charts
// ─────────────────────────────────────────────────────────────
function getDistribution(): void
{
    requireAdmin();
    global $db;

    $type = $_GET['type'] ?? 'status';

    try {
        if ($type === 'status') {
            $sql = "SELECT coaster_status AS label, COUNT(*)::int AS count
                    FROM coasters
                    WHERE coaster_status IS NOT NULL
                    GROUP BY coaster_status
                    ORDER BY count DESC";
        } elseif ($type === 'country') {
            $sql = "SELECT country AS label, COUNT(*)::int AS count
                    FROM users
                    WHERE country IS NOT NULL AND country <> ''
                    GROUP BY country
                    ORDER BY count DESC
                    LIMIT 10";
        } else {
            Response::error('Tipo de distribución no válido');
            return;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    } catch (PDOException $e) {
        Response::error('Error obteniendo distribución: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// getRecentTrips — Data for latest booked trips table
// ─────────────────────────────────────────────────────────────
function getRecentTrips(): void
{
    requireAdmin();
    global $db;

    try {
        $sql = "SELECT t.id, t.title, t.start_date, t.end_date, t.parks_visited, t.created_at, u.username
                FROM trips t
                LEFT JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC
                LIMIT 10";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    } catch (PDOException $e) {
        Response::error('Error obteniendo últimos viajes: ' . $e->getMessage());
    }
}
