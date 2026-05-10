<?php
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

// Solo admins
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    Response::unauthorized('Solo administradores');
}

$db     = new DBConexion();
$router = new ApiRouter('list_reviews');
$router->register('list_reviews',  'adminListReviews');
$router->register('delete_review', 'adminDeleteReview', 'POST');
$router->register('toggle_visibility', 'adminToggleVisibility', 'POST');
$router->dispatch();

function adminListReviews()
{
    global $db;

    $search = trim($_GET['search'] ?? '');
    $type   = trim($_GET['type'] ?? ''); // 'coaster', 'park' o '' para ambos
    $sort   = trim($_GET['sort'] ?? 'recent');

    $coasterConditions = ["cr.review IS NOT NULL AND cr.review != ''"];
    $parkConditions    = ["pr.review IS NOT NULL AND pr.review != ''"];
    $params = [];

    if ($search !== '') {
        $coasterConditions[] = '(cr.review ILIKE :search OR u.username ILIKE :search OR c.coaster_name ILIKE :search)';
        $parkConditions[]    = '(pr.review ILIKE :search OR u.username ILIKE :search OR p.park_name ILIKE :search)';
        $params[':search'] = '%' . $search . '%';
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
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function adminToggleVisibility()
{
    global $db;

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($input['id'] ?? 0);
    $type  = $input['type'] ?? '';
    $hide  = isset($input['hide']) ? (bool)$input['hide'] : true;

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
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function adminDeleteReview()
{
    global $db;

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id    = (int)($input['id'] ?? $_POST['id'] ?? 0);
    $type  = $input['type'] ?? $_POST['type'] ?? '';

    if (!$id || !in_array($type, ['coaster', 'park'])) {
        Response::error('ID o tipo inválido');
    }

    try {
        $table = $type === 'coaster' ? 'coaster_ratings' : 'park_ratings';
        
        // En lugar de borrar la fila entera, solo borramos el texto de la reseña
        // para que no se pierda la puntuación (estrellas) que le dio el usuario.
        $stmt = $db->prepare("UPDATE $table SET review = NULL WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            Response::error('Reseña no encontrada', 404);
        }

        Response::success(['message' => 'El texto de la reseña ha sido eliminado (la puntuación se mantiene)']);
    } catch (PDOException $e) {
        error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}
