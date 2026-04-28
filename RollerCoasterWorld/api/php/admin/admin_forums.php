<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

// Solo admins
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    Response::unauthorized('Solo administradores');
}

$db     = new DBConexion();
$router = new ApiRouter('list_forums');
$router->register('list_forums',  'adminListForums');
$router->register('delete_forum', 'adminDeleteForum', 'POST');
$router->dispatch();

/* ═══════════════════════════════════════════════════════════════
   LISTA COMPLETA DE FOROS CON STATS
═══════════════════════════════════════════════════════════════ */
function adminListForums()
{
    global $db;

    $search  = trim($_GET['search']  ?? '');
    $privacy = trim($_GET['privacy'] ?? '');

    $conditions = [];
    $params     = [];

    if ($search !== '') {
        $conditions[] = '(f.title ILIKE :search OR f.forum_subject ILIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }
    if ($privacy === 'public' || $privacy === 'private') {
        $conditions[] = 'f.privacy = :privacy';
        $params[':privacy'] = $privacy;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    try {
        $sql = "
            SELECT
                f.id,
                f.title,
                f.forum_subject,
                f.privacy,
                f.created_at,
                u.id        AS author_id,
                u.username  AS author_name,
                u.profile_image AS author_image,
                (SELECT COUNT(*) FROM forum_messages  fm WHERE fm.forum_id = f.id)             AS msg_count,
                (SELECT COUNT(*) FROM forum_messages  fm WHERE fm.forum_id = f.id AND fm.is_hidden = TRUE) AS hidden_count,
                (SELECT COUNT(*) FROM forum_collaborators fc WHERE fc.forum_id = f.id)         AS collab_count,
                (SELECT COUNT(*) FROM forum_banned    fb WHERE fb.forum_id = f.id)             AS ban_count
            FROM forums f
            JOIN users u ON f.author_id = u.id
            $where
            ORDER BY f.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['forums' => $forums]);
    } catch (PDOException $e) {
        Response::error('Error listando foros: ' . $e->getMessage());
    }
}

/* ═══════════════════════════════════════════════════════════════
   ELIMINAR FORO COMPLETO (CASCADE elimina mensajes, bans, etc.)
═══════════════════════════════════════════════════════════════ */
function adminDeleteForum()
{
    global $db;

    $input   = json_decode(file_get_contents('php://input'), true) ?? [];
    $forumId = (int)($input['forum_id'] ?? $_POST['forum_id'] ?? 0);

    if (!$forumId) Response::error('forum_id requerido');

    try {
        $stmt = $db->prepare('DELETE FROM forums WHERE id = :fid');
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            Response::error('Foro no encontrado', 404);
        }

        Response::success(['message' => 'Foro eliminado correctamente']);
    } catch (PDOException $e) {
        Response::error('Error eliminando foro: ' . $e->getMessage());
    }
}
