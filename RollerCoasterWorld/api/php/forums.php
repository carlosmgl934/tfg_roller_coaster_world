<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/ApiRouter.php';
require_once __DIR__ . '/utils/Response.php';

$db = new DBConexion();

$router = new ApiRouter('list');
$router->register('create_forum', 'createForum', 'POST');
$router->register('list', 'listForums');
$router->register('get_friends', 'getFriends');
$router->dispatch();

function createForum()
{
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized('No estás logueado');
    }

    $privacy = trim($_POST['privacy'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $subject = trim($_POST['form_subject'] ?? '');
    $collaborators = $_POST['collaborators'] ?? [];
    $userId = $_SESSION['user_id'];

    if ($privacy !== 'public' && $privacy !== 'private') {
        Response::error('Privacidad no válida');
    }
    if (empty($title))
        Response::error('No se ha especificado un título');
    if (empty($subject))
        Response::error('No se ha especificado un asunto');

    global $db;
    try {
        //Comprobar si el usuario ha creado un foro en los últimos 5 días
        $stmt = $db->prepare("SELECT created_at FROM forums WHERE author_id = :user_id ORDER BY created_at DESC LIMIT 1");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $lastForumDate = $stmt->fetchColumn();

        if ($lastForumDate) {
            $lastDate = new DateTime($lastForumDate);
            $now = new DateTime();
            $daysPassed = $now->diff($lastDate)->days;
            if ($daysPassed < 5) {
                Response::error('Solo puedes crear un foro cada 5 días, te quedan ' . (5 - $daysPassed) . ' días para crear otro');
            }
        }
    }
    catch (PDOException $e) {
        Response::error('Error al comprobar la fecha del último foro: ' . $e->getMessage());
    }

    try {
        // Usar una transacción para asegurar que tanto el foro como los colaboradores se guardan
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO forums (title, forum_subject, author_id, privacy) 
            VALUES (:title, :subject, :author_id, :privacy) 
            RETURNING id
        ");
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindParam(':author_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':privacy', $privacy, PDO::PARAM_STR);
        $stmt->execute();
        $forumId = $stmt->fetchColumn();

        if ($privacy === 'private' && is_array($collaborators) && count($collaborators) > 0) {
            $collaborators = array_diff($collaborators, [$userId]);

            if (count($collaborators) > 0) {
                $placeholders = implode(',', array_fill(0, count($collaborators), '?'));

                // Solo inserta los que realmente son amigos aceptados
                $stmtCollab = $db->prepare("
                    INSERT INTO forum_collaborators (forum_id, user_id)
                    SELECT ?, id FROM users
                    WHERE id IN ($placeholders)
                    AND id IN (
                        SELECT CASE WHEN user_id1 = ? THEN user_id2 ELSE user_id1 END
                        FROM friendships
                        WHERE status = 'accepted' AND (user_id1 = ? OR user_id2 = ?)
                    )
                ");
                $stmtCollab->execute(array_merge([$forumId], $collaborators, [$userId, $userId]));
            }
        }

        $db->commit();
        Response::success(['message' => 'Foro creado correctamente', 'forum_id' => $forumId]);

    }
    catch (PDOException $e) {
        $db->rollBack();
        Response::error('Error al crear el foro: ' . $e->getMessage());
    }
}

function listForums()
{
    Response::success(['forums' => []]);
}

function getFriends()
{
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized('No estás logueado');
    }

    $userId = $_SESSION['user_id'];

    try {
        global $db;
        $sql = "    
    SELECT users.id, users.username, users.profile_image, users.city, users.country
    FROM users
    INNER JOIN friendship f 
        ON (f.solicitante_id = users.id OR f.solicitada_id = users.id)
    WHERE f.estado_solicitud = 'ACEPTADA'
    AND (f.solicitante_id = :user_id OR f.solicitada_id = :user_id)
    AND users.id != :user_id
";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['friends' => $friends]);
    }
    catch (PDOException $e) {
        Response::error('Error obteniendo amigos: ' . $e->getMessage());
    }
}