<?php
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

// Solo admins
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    Response::unauthorized('Solo administradores');
}

$db = new DBConexion();
$router = new ApiRouter('list_reviews');
$router->register('list_reviews', 'adminListReviews');
$router->register('delete_review', 'adminDeleteReview', 'POST');
$router->register('toggle_visibility', 'adminToggleVisibility', 'POST');
$router->register('destroy_review', 'adminDestroyReview', 'POST');
$router->dispatch();

function adminListReviews()
{
    global $db;

    $search = trim($_GET['search'] ?? '');
    $type = trim($_GET['type'] ?? ''); // 'coaster', 'park' o '' para ambos
    $sort = trim($_GET['sort'] ?? 'recent');

    $coasterConditions = ["1=1"];
    $parkConditions = ["1=1"];
    $params = [];

    if ($search !== '') {
        // ILIKE para PostgreSQL (case-insensitive)
        // Usamos placeholders diferentes para evitar errores en el UNION
        $coasterConditions[] = '(cr.review ILIKE :search1 OR u.username ILIKE :search1 OR c.coaster_name ILIKE :search1)';
        $parkConditions[] = '(pr.review ILIKE :search2 OR u.username ILIKE :search2 OR p.park_name ILIKE :search2)';
        $params[':search1'] = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    $cWhere = implode(' AND ', $coasterConditions);
    $pWhere = implode(' AND ', $parkConditions);

    // Configurar ORDER BY
    $orderBy = "ORDER BY created_at DESC"; // por defecto 'recent'
    if ($sort === 'oldest') {
        $orderBy = "ORDER BY created_at ASC";
    } elseif ($sort === 'best') {
        $orderBy = "ORDER BY note DESC, created_at DESC";
    } elseif ($sort === 'worst') {
        $orderBy = "ORDER BY note ASC, created_at DESC";
    }

    try {
        $sqlCoasters = "
            SELECT 
                'coaster' AS type,
                cr.id,
                cr.user_id,
                u.username,
                u.profile_image,
                cr.coaster_id AS item_id,
                c.coaster_name AS item_name,
                c.imagen_url AS item_image,
                cr.review,
                cr.note,
                cr.created_at,
                cr.is_hidden
            FROM coaster_ratings cr
            JOIN users u ON cr.user_id = u.id
            JOIN coasters c ON cr.coaster_id = c.id
            WHERE $cWhere
        ";

        $sqlParks = "
            SELECT 
                'park' AS type,
                pr.id,
                pr.user_id,
                u.username,
                u.profile_image,
                pr.park_id AS item_id,
                p.park_name AS item_name,
                p.imagen_url AS item_image,
                pr.review,
                pr.note,
                pr.created_at,
                pr.is_hidden
            FROM park_ratings pr
            JOIN users u ON pr.user_id = u.id
            JOIN parks p ON pr.park_id = p.id
            WHERE $pWhere
        ";

        if ($type === 'coaster') {
            $sql = "$sqlCoasters $orderBy";
        } elseif ($type === 'park') {
            $sql = "$sqlParks $orderBy";
        } else {
            $sql = "$sqlCoasters UNION ALL $sqlParks $orderBy";
        }

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['reviews' => $reviews]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function adminToggleVisibility()
{
    global $db;

    // Validación CSRF
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$csrf || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        Response::error('Token CSRF inválido', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($input['id'] ?? 0);
    $type = $input['type'] ?? '';
    $hide = isset($input['hide']) ? (bool) $input['hide'] : true;

    if (!$id || !in_array($type, ['coaster', 'park'])) {
        Response::error('ID o tipo inválido');
    }

    try {
        $table = $type === 'coaster' ? 'coaster_ratings' : 'park_ratings';

        $stmt = $db->prepare("UPDATE $table SET is_hidden = :hide WHERE id = :id");
        $stmt->bindValue(':hide', $hide, PDO::PARAM_BOOL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            Response::error('Reseña no encontrada', 404);
        }

        $msg = $hide ? 'Reseña ocultada correctamente' : 'Reseña restaurada correctamente';
        Response::success(['message' => $msg, 'is_hidden' => $hide]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function adminDeleteReview()
{
    global $db;

    // Validación CSRF
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$csrf || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        Response::error('Token CSRF inválido', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($input['id'] ?? $_POST['id'] ?? 0);
    $type = $input['type'] ?? $_POST['type'] ?? '';

    if (!$id || !in_array($type, ['coaster', 'park'])) {
        Response::error('ID o tipo inválido');
    }

    try {
        $table = $type === 'coaster' ? 'coaster_ratings' : 'park_ratings';

        // Solo borramos el texto; la puntuación (note) se mantiene para el ranking
        $stmt = $db->prepare("UPDATE $table SET review = NULL WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            Response::error('Reseña no encontrada', 404);
        }

        Response::success(['message' => 'El texto de la reseña ha sido eliminado (la puntuación se mantiene)']);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Elimina la reseña COMPLETA y recalcula el stars del coaster/parque ────────
function adminDestroyReview()
{
    global $db;

    // Validación CSRF
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$csrf || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        Response::error('Token CSRF inválido', 403);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int) ($input['id'] ?? 0);
    $type = $input['type'] ?? '';

    if (!$id || !in_array($type, ['coaster', 'park'])) {
        Response::error('ID o tipo inválido');
    }

    try {
        $db->beginTransaction();

        if ($type === 'coaster') {
            // 1. Obtener coaster_id antes de borrar
            $stmtGet = $db->prepare("SELECT coaster_id FROM coaster_ratings WHERE id = :id");
            $stmtGet->execute([':id' => $id]);
            $coasterId = (int) $stmtGet->fetchColumn();

            if (!$coasterId) {
                $db->rollBack();
                Response::error('Reseña no encontrada', 404);
                return;
            }

            // 2. Borrar la fila
            $db->prepare("DELETE FROM coaster_ratings WHERE id = :id")->execute([':id' => $id]);

            // 3. Recalcular stars del coaster (media de notas visibles restantes)
            $db->prepare(
                "UPDATE coasters SET stars = (
                    SELECT COALESCE(AVG(note), 0)
                    FROM coaster_ratings
                    WHERE coaster_id = :cid AND (is_hidden IS NOT TRUE)
                ) WHERE id = :cid"
            )->execute([':cid' => $coasterId]);

        } else {
            // 1. Obtener park_id antes de borrar
            $stmtGet = $db->prepare("SELECT park_id FROM park_ratings WHERE id = :id");
            $stmtGet->execute([':id' => $id]);
            $parkId = (int) $stmtGet->fetchColumn();

            if (!$parkId) {
                $db->rollBack();
                Response::error('Reseña no encontrada', 404);
                return;
            }

            // 2. Borrar la fila
            $db->prepare("DELETE FROM park_ratings WHERE id = :id")->execute([':id' => $id]);

            // 3. Recalcular stars del parque (media de notas visibles restantes)
            $db->prepare(
                "UPDATE parks SET stars = (
                    SELECT COALESCE(AVG(note), 0)
                    FROM park_ratings
                    WHERE park_id = :pid AND (is_hidden IS NOT TRUE)
                ) WHERE id = :pid"
            )->execute([':pid' => $parkId]);
        }

        $db->commit();
        Response::success(['message' => 'Reseña eliminada permanentemente y ranking recalculado.']);

    } catch (PDOException $e) {
        try {
            $db->rollBack();
        } catch (Exception $re) {
        }
        error_log('[adminDestroyReview] ' . $e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}
