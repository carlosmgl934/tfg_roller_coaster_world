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
    'stats'     => getGlobalStats($db),
    'dashboard' => getDashboardData($db),
    default     => Response::error("Acción desconocida: $action", 400),
};

// ── Estadísticas globales ──────────────────────────────────────────────────────
function getGlobalStats(DBConexion $db): void
{
    try {
        $coasters = (int) $db->query("SELECT COUNT(*) FROM coasters")->fetchColumn();
        $parks    = (int) $db->query("SELECT COUNT(*) FROM parks")->fetchColumn();
        $users    = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $photos   = (int) $db->query("SELECT COUNT(*) FROM coaster_photos")->fetchColumn();
        $cr       = (int) $db->query("SELECT COUNT(*) FROM coaster_ratings")->fetchColumn();
        $pr       = (int) $db->query("SELECT COUNT(*) FROM park_ratings")->fetchColumn();

        Response::success([
            'coasters' => $coasters,
            'parks'    => $parks,
            'users'    => $users,
            'photos'   => $photos,
            'reviews'  => $cr + $pr,
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
                'username'         => $user['username'],
                'profile_image'    => $user['profile_image'],
                'location'         => $location,
                'favorite_coaster' => $user['favorite_coaster'] ?: 'Ninguna configurada',
            ],
            'stats' => [
                'credits'       => (int) $stats['credits'],
                'reviews'       => (int) $stats['reviews'],
                'parks_visited' => (int) $stats['parks_visited'],
                'trips'         => (int) $stats['trips'],
                'photos'        => (int) $stats['photos'],
                'friends'       => (int) $stats['friends'],
            ],
            'news' => $news,
        ]);
    } catch (Exception $e) {
        error_log("Error en dashboard index: " . $e->getMessage());
        Response::error("Error cargando datos del dashboard", 500);
    }
}