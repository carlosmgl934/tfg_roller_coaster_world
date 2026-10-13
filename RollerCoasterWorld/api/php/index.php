<?php
/**
 * api/php/index.php — Endpoint de datos para la página de inicio
 *
 * Acciones disponibles (parámetro GET `action`):
 *   - stats     → Estadísticas globales de la plataforma (público)
 *   - dashboard → Datos personales del usuario logueado (requiere sesión)
 */

require_once __DIR__ . '/utils/SessionManager.php';
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'stats';
$db = new DBConexion();

match ($action) {
    'stats' => getGlobalStats($db),
    'dashboard' => getDashboardData($db),
    'random' => getRandomSpotlight($db),
    default => Response::error("Acción desconocida: $action", 400),
};

// ── Estadísticas globales ──────────────────────────────────────────────────────
function getGlobalStats(DBConexion $db): void
{
    try {
        $coasters = (int) $db->query("SELECT COUNT(*) FROM coasters")->fetchColumn();
        $parks = (int) $db->query("SELECT COUNT(*) FROM parks")->fetchColumn();
        $users = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $photos = (int) $db->query("SELECT COUNT(*) FROM coaster_photos")->fetchColumn();
        $cr = (int) $db->query("SELECT COUNT(*) FROM coaster_ratings")->fetchColumn();
        $pr = (int) $db->query("SELECT COUNT(*) FROM park_ratings")->fetchColumn();

        Response::success([
            'coasters' => $coasters,
            'parks' => $parks,
            'users' => $users,
            'photos' => $photos,
            'reviews' => $cr + $pr,
        ]);
    } catch (Exception $e) {
        error_log("Error en stats index: " . $e->getMessage());
        Response::error("Error cargando estadísticas", 500);
    }
}

// ── Datos del dashboard (usuario logueado) ────────────────────────────────────
function getDashboardData(DBConexion $db): void
{
    $uid = $_SESSION['firebase_uid'] ?? null;

    if (!$uid) {
        Response::error("No autenticado", 401);
    }

    try {
        // Datos básicos del usuario
        $stmt = $db->prepare(
            "SELECT id, username, city, country, favorite_coaster, profile_image
             FROM users WHERE firebase_uid = ?"
        );
        $stmt->execute([$uid]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            Response::error("Usuario no encontrado", 404);
        }

        $userId = (int) $user['id'];

        // Estadísticas personales (todas en una sola query para eficiencia)
        $statsStmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM user_credits      WHERE user_id = :id) AS credits,
                (SELECT COUNT(*) FROM coaster_ratings   WHERE user_id = :id) + 
                (SELECT COUNT(*) FROM park_ratings      WHERE user_id = :id) AS reviews,
                (SELECT COUNT(*) FROM user_park_credits WHERE user_id = :id) AS parks_visited,
                (SELECT COUNT(*) FROM trips             WHERE user_id = :id) AS trips,
                (SELECT COUNT(*) FROM coaster_photos    WHERE user_id = :id) AS photos,
                (SELECT COUNT(*) FROM friendship
                 WHERE estado_solicitud = 'ACEPTADA'
                   AND (solicitante_id = :id OR solicitada_id = :id))        AS friends
        ");
        $statsStmt->execute([':id' => $userId]);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        // Noticias recientes (máx. 3)
        $newsStmt = $db->query(
            "SELECT id, title, description, tag, image_url, external_link, created_at, is_featured
             FROM news
             ORDER BY is_featured DESC, created_at DESC
             LIMIT 3"
        );
        $news = $newsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Construir la ubicación
        $location = '—';
        if ($user['city'] && $user['country']) {
            $location = $user['city'] . ', ' . $user['country'];
        } elseif ($user['country']) {
            $location = $user['country'];
        } elseif ($user['city']) {
            $location = $user['city'];
        }

        Response::success([
            'user' => [
                'username' => $user['username'],
                'profile_image' => $user['profile_image'],
                'location' => $location,
                'favorite_coaster' => $user['favorite_coaster'] ?: 'Ninguna configurada',
            ],
            'stats' => [
                'credits' => (int) $stats['credits'],
                'reviews' => (int) $stats['reviews'],
                'parks_visited' => (int) $stats['parks_visited'],
                'trips' => (int) $stats['trips'],
                'photos' => (int) $stats['photos'],
                'friends' => (int) $stats['friends'],
            ],
            'news' => $news,
        ]);
    } catch (Exception $e) {
        error_log("Error en dashboard index: " . $e->getMessage());
        Response::error("Error cargando datos del dashboard", 500);
    }
}

// ── Spotlight aleatorio: 1 coaster + 1 parque ─────────────────────────────────
function getRandomSpotlight(DBConexion $db): void
{
    try {
        // Coaster aleatorio — incluye si el usuario logueado ya lo ha montado
        $userId = $_SESSION['user_id'] ?? null;
        $coasterStmt = $db->prepare(
            "SELECT c.id, c.coaster_name, c.imagen_url, c.coaster_manufacter AS manufacter,
                    c.coaster_model AS model, c.opening_year, c.height, c.speed,
                    c.coaster_status AS status, c.inversions,
                    p.park_name, p.park_country AS country, p.id AS park_id,
                    CASE WHEN uc.coaster_id IS NOT NULL THEN true ELSE false END AS ridden
             FROM coasters c
             INNER JOIN parks p ON c.park_id = p.id
             LEFT JOIN user_credits uc
               ON uc.coaster_id = c.id AND uc.user_id = :uid
             WHERE c.imagen_url IS NOT NULL AND c.imagen_url != ''
               AND p.park_name NOT IN ('Desconocido','Unknown')
             ORDER BY RANDOM()
             LIMIT 1"
        );
        $coasterStmt->execute([':uid' => $userId]);
        $coaster = $coasterStmt->fetch(PDO::FETCH_ASSOC);

        // Parque aleatorio — operating_coasters calculado en tiempo real mediante CTE
        // (PostgreSQL no permite referenciar alias del SELECT en el WHERE, se usa CTE)
        $parkStmt = $db->query(
            "WITH park_data AS (
                SELECT p.id, p.park_name, p.park_country AS country, p.park_location AS location,
                       p.imagen_url, p.num_coasters, p.opening_year, p.stars, p.precio_entrada,
                       (SELECT COUNT(*) FROM coasters c2
                         WHERE c2.park_id = p.id
                           AND (c2.coaster_status = 'Operating' OR c2.coaster_status = 'Operativa')
                       ) AS operating_coasters
                FROM parks p
                WHERE p.imagen_url IS NOT NULL AND p.imagen_url != ''
                  AND p.park_name NOT IN ('Desconocido','Unknown')
             )
             SELECT * FROM park_data
             WHERE operating_coasters > 0
             ORDER BY RANDOM()
             LIMIT 1"
        );
        $park = $parkStmt->fetch(PDO::FETCH_ASSOC);

        if ($coaster) {
            $coaster['ridden'] = ($coaster['ridden'] === 't' || $coaster['ridden'] === true);
        }

        Response::success([
            'coaster' => $coaster ?: null,
            'park' => $park ?: null,
        ]);
    } catch (Exception $e) {
        error_log("Error en random spotlight: " . $e->getMessage());
        Response::error("Error cargando spotlight aleatorio", 500);
    }
}