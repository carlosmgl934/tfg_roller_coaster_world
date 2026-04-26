<?php
/**
 * api/php/trips.php
 * API de Viajes — Agenda de Parques
 *
 * Acciones GET:
 *   ?action=list  → Lista todos los viajes del usuario autenticado
 *
 * Acciones POST:
 *   ?action=delete → Elimina un viaje por trip_id
 */

session_start();
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';

header('Content-Type: application/json');

$uid = $_SESSION['firebase_uid'] ?? null;
if (!$uid) {
    Response::error('No autenticado', 401);
    exit;
}

$db     = new DBConexion();
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

// Resuelve user_id numérico
$stmtU = $db->prepare("SELECT id FROM users WHERE firebase_uid = ? LIMIT 1");
$stmtU->execute([$uid]);
$userId = (int) ($stmtU->fetchColumn() ?: 0);

if (!$userId) {
    Response::error('Usuario no encontrado', 404);
    exit;
}

match (true) {
    ($action === 'list'   && $method === 'GET')  => listTrips($db, $userId),
    ($action === 'delete' && $method === 'POST') => deleteTrip($db, $userId),
    default => Response::error("Acción no soportada: $action", 400),
};

// ── Listar viajes del usuario ─────────────────────────────────────────────────
function listTrips(DBConexion $db, int $userId): void
{
    try {
        $stmt = $db->prepare(
            "SELECT id, title, start_date, end_date, parks_visited, new_credits, created_at
             FROM trips
             WHERE user_id = ?
             ORDER BY start_date DESC"
        );
        $stmt->execute([$userId]);
        $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['data' => $trips]);
    } catch (Exception $e) {
        error_log("Error listTrips: " . $e->getMessage());
        Response::error('Error al cargar viajes', 500);
    }
}

// ── Eliminar un viaje ─────────────────────────────────────────────────────────
function deleteTrip(DBConexion $db, int $userId): void
{
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $tripId = (int) ($body['trip_id'] ?? 0);

    if (!$tripId) {
        Response::error('trip_id requerido', 422);
        return;
    }

    try {
        // Verificar que el viaje pertenece al usuario
        $stmt = $db->prepare("SELECT id FROM trips WHERE id = ? AND user_id = ?");
        $stmt->execute([$tripId, $userId]);
        if (!$stmt->fetchColumn()) {
            Response::error('Viaje no encontrado o sin permisos', 404);
            return;
        }

        $db->prepare("DELETE FROM trips WHERE id = ? AND user_id = ?")
           ->execute([$tripId, $userId]);

        Response::success(['message' => 'Viaje eliminado correctamente']);
    } catch (Exception $e) {
        error_log("Error deleteTrip: " . $e->getMessage());
        Response::error('Error al eliminar el viaje', 500);
    }
}
