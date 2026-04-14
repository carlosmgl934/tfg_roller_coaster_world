<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/ApiRouter.php';
require_once __DIR__ . '/utils/Response.php';

$db = new DBConexion();

$router = new ApiRouter('list');
$router->register('create_forum',        'createForum',        'POST');
$router->register('list',                'listForums');
$router->register('get_friends',         'getFriends');
$router->register('search_forums',       'searchForums');
$router->register('accept_forum_invite', 'acceptForumInvite',  'POST');
$router->register('decline_forum_invite','declineForumInvite', 'POST');
// ── Chat interno ──────────────────────────────────────────────────
$router->register('get_forum',           'getForum');
$router->register('get_messages',        'getMessages');
$router->register('send_message',        'sendMessage',        'POST');
$router->register('delete_message',      'deleteMessage',      'POST');
$router->register('hide_message',        'hideMessage',        'POST');
$router->register('ban_user',            'banUser',            'POST');
$router->register('unban_user',          'unbanUser',          'POST');
$router->register('get_banned',          'getBanned');
$router->register('get_collaborators',   'getCollaborators');
$router->register('remove_collaborator', 'removeCollaborator', 'POST');
$router->register('invite_collaborator', 'inviteCollaborator', 'POST');
$router->register('get_participants',    'getParticipants');
$router->dispatch();

/* ═══════════════════════════════════════════════════════════════
   FUNCIONES EXISTENTES
═══════════════════════════════════════════════════════════════ */

function createForum()
{
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized('No estás logueado');
    }

    $privacy      = trim($_POST['privacy'] ?? '');
    $title        = trim($_POST['title'] ?? '');
    $subject      = trim($_POST['form_subject'] ?? '');
    $collaborators = $_POST['collaborators'] ?? [];
    $userId       = $_SESSION['user_id'];

    if ($privacy !== 'public' && $privacy !== 'private') {
        Response::error('Privacidad no válida');
    }
    if (strlen($title) < 5)
        Response::error('El título debe tener al menos 5 caracteres');
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
            $lastDate   = new DateTime($lastForumDate);
            $now        = new DateTime();
            $daysPassed = $now->diff($lastDate)->days;
            if ($daysPassed < 5) {
                Response::error('Solo puedes crear un foro cada 5 días, te quedan ' . (5 - $daysPassed) . ' días para crear otro');
            }
        }
    } catch (PDOException $e) {
        Response::error('Error al comprobar la fecha del último foro: ' . $e->getMessage());
    }

    try {
        // Usar una transacción para asegurar que tanto el foro como los colaboradores se guardan
        $db->beginTransaction();

        // Mantener como máximo 50 foros en total en la BBDD (dejamos los 49 más recientes antes de insertar el nuevo)
        $stmtLimit = $db->prepare("
            DELETE FROM forums 
            WHERE id NOT IN (
                SELECT id FROM forums ORDER BY created_at DESC LIMIT 49
            )
        ");
        $stmtLimit->execute();

        $stmt = $db->prepare("
            INSERT INTO forums (title, forum_subject, author_id, privacy) 
            VALUES (:title, :subject, :author_id, :privacy) 
            RETURNING id
        ");
        $stmt->bindParam(':title',     $title,   PDO::PARAM_STR);
        $stmt->bindParam(':subject',   $subject, PDO::PARAM_STR);
        $stmt->bindParam(':author_id', $userId,  PDO::PARAM_INT);
        $stmt->bindParam(':privacy',   $privacy, PDO::PARAM_STR);
        $stmt->execute();
        $forumId = $stmt->fetchColumn();

        if (is_array($collaborators) && count($collaborators) > 0) {
            $collaborators = array_diff($collaborators, [$userId]);

            if (count($collaborators) > 0) {
                $placeholders = implode(',', array_fill(0, count($collaborators), '?'));

                // Enviar invitaciones a los colaboradores (solo si son amigos aceptados)
                $stmtInvite = $db->prepare("
                    INSERT INTO forum_invitations (forum_id, sender_id, receiver_id)
                    SELECT ?, ?, id FROM users
                    WHERE id IN ($placeholders)
                    AND id IN (
                        SELECT CASE WHEN solicitante_id = ? THEN solicitada_id ELSE solicitante_id END
                        FROM friendship
                        WHERE estado_solicitud = 'ACEPTADA' AND (solicitante_id = ? OR solicitada_id = ?)
                    )
                    ON CONFLICT (forum_id, receiver_id) DO NOTHING
                ");
                $stmtInvite->execute(array_merge([$forumId, $userId], $collaborators, [$userId, $userId, $userId]));
            }
        }

        $db->commit();
        Response::success(['message' => 'Foro creado correctamente', 'forum_id' => $forumId]);

    } catch (PDOException $e) {
        $db->rollBack();
        Response::error('Error al crear el foro: ' . $e->getMessage());
    }
}

function listForums()
{
    $mine = isset($_GET['mine']) && $_GET['mine'] === 'true';
    if ($mine && empty($_SESSION['user_id'])) {
        Response::unauthorized('Debes iniciar sesión para usar este filtro');
    }

    try {
        global $db;
        
        $where = "";
        if ($mine) {
            $userId = (int)$_SESSION['user_id'];
            $where = "WHERE f.author_id = $userId OR f.id IN (SELECT forum_id FROM forum_collaborators WHERE user_id = $userId)";
        }
        
        $sql = "
            SELECT f.id, f.title, f.forum_subject, f.privacy, f.created_at,
                   u.username AS author_name, u.profile_image AS author_image,
                   (
                       SELECT json_agg(json_build_object('username', u2.username, 'profile_image', u2.profile_image)) 
                       FROM forum_collaborators fc 
                       JOIN users u2 ON fc.user_id = u2.id 
                       WHERE fc.forum_id = f.id
                   ) AS collaborators_json
            FROM forums f
            JOIN users u ON f.author_id = u.id
            $where
            ORDER BY f.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['forums' => $forums]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo foros: ' . $e->getMessage());
    }
}

function searchForums()
{
    if (!isset($_GET['search'])) {
        Response::error('No se ha especificado un término de búsqueda');
    }

    $search = '%' . $_GET['search'] . '%';
    
    $mine = isset($_GET['mine']) && $_GET['mine'] === 'true';
    if ($mine && empty($_SESSION['user_id'])) {
        Response::unauthorized('Debes iniciar sesión para usar este filtro');
    }

    try {
        global $db;
        
        $where = "WHERE (f.title ILIKE :search OR f.forum_subject ILIKE :search)";
        if ($mine) {
            $userId = (int)$_SESSION['user_id'];
            $where .= " AND (f.author_id = $userId OR f.id IN (SELECT forum_id FROM forum_collaborators WHERE user_id = $userId))";
        }
        
        $sql = "
            SELECT f.id, f.title, f.forum_subject, f.privacy, f.created_at,
                   u.username AS author_name, u.profile_image AS author_image,
                   (
                       SELECT json_agg(json_build_object('username', u2.username, 'profile_image', u2.profile_image)) 
                       FROM forum_collaborators fc 
                       JOIN users u2 ON fc.user_id = u2.id 
                       WHERE fc.forum_id = f.id
                   ) AS collaborators_json
            FROM forums f
            JOIN users u ON f.author_id = u.id
            $where
            ORDER BY f.created_at DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
        $stmt->execute();
        $forums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['forums' => $forums]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo foros: ' . $e->getMessage());
    }
}

function getFriends()
{
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized('No estás logueado');
    }

    $userId = $_SESSION['user_id'];

    try {
        global $db;
        // NOTA: PDO no permite el mismo named param más de una vez.
        // Se usan :uid1, :uid2, :uid3 con el mismo valor.
        $sql = "
            SELECT u.id, u.username, u.profile_image, u.city, u.country
            FROM users u
            INNER JOIN friendship f
                ON (f.solicitante_id = u.id OR f.solicitada_id = u.id)
            WHERE f.estado_solicitud = 'ACEPTADA'
            AND (f.solicitante_id = :uid1 OR f.solicitada_id = :uid2)
            AND u.id != :uid3
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid3', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['friends' => $friends]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo amigos: ' . $e->getMessage());
    }
}

function acceptForumInvite()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId = (int)($_POST['forum_id'] ?? 0);
    $userId  = $_SESSION['user_id'];

    if (!$forumId) Response::error('forum_id requerido');

    global $db;
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            UPDATE forum_invitations
            SET status = 'accepted'
            WHERE forum_id = :fid AND receiver_id = :uid AND status = 'pending'
        ");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmt->execute();

        // Añadir como colaborador
        $stmt2 = $db->prepare("
            INSERT INTO forum_collaborators (forum_id, user_id)
            VALUES (:fid, :uid)
            ON CONFLICT DO NOTHING
        ");
        $stmt2->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt2->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmt2->execute();

        $db->commit();
        Response::success(['message' => 'Invitación aceptada']);
    } catch (PDOException $e) {
        $db->rollBack();
        Response::error('Error: ' . $e->getMessage());
    }
}

function declineForumInvite()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId = (int)($_POST['forum_id'] ?? 0);
    $userId  = $_SESSION['user_id'];

    if (!$forumId) Response::error('forum_id requerido');

    global $db;
    try {
        $stmt = $db->prepare("
            UPDATE forum_invitations
            SET status = 'declined'
            WHERE forum_id = :fid AND receiver_id = :uid AND status = 'pending'
        ");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmt->execute();
        Response::success(['message' => 'Invitación rechazada']);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/* ═══════════════════════════════════════════════════════════════
   CHAT INTERNO DEL FORO
═══════════════════════════════════════════════════════════════ */

/**
 * Devuelve info del foro + rol del usuario actual (owner/collaborator/banned/reader)
 */
function getForum()
{
    $forumId = (int)($_GET['forum_id'] ?? 0);
    if (!$forumId) Response::error('forum_id requerido');

    $userId = $_SESSION['user_id'] ?? null;

    global $db;
    try {
        $stmt = $db->prepare("
            SELECT f.id, f.title, f.forum_subject, f.privacy, f.created_at,
                   f.author_id,
                   u.username AS author_name, u.profile_image AS author_image,
                   (SELECT COUNT(*) FROM forum_collaborators fc WHERE fc.forum_id = f.id) + 1 AS member_count,
                   (
                       SELECT json_agg(json_build_object('id', u2.id, 'username', u2.username, 'profile_image', u2.profile_image)) 
                       FROM forum_collaborators fc 
                       JOIN users u2 ON fc.user_id = u2.id 
                       WHERE fc.forum_id = f.id
                   ) AS collaborators_json
            FROM forums f
            JOIN users u ON f.author_id = u.id
            WHERE f.id = :fid
        ");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->execute();
        $forum = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$forum) Response::error('Foro no encontrado', 404);

        $role = 'reader'; // por defecto
        if ($userId) {
            if ((int)$forum['author_id'] === (int)$userId) {
                $role = 'owner';
            } else {
                // ¿es colaborador?
                $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
                $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
                $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
                $stmtC->execute();
                if ($stmtC->fetchColumn()) {
                    $role = 'collaborator';
                }

                // ¿está baneado?
                $stmtB = $db->prepare("SELECT 1 FROM forum_banned WHERE forum_id = :fid AND user_id = :uid");
                $stmtB->bindValue(':fid', $forumId, PDO::PARAM_INT);
                $stmtB->bindValue(':uid', $userId,  PDO::PARAM_INT);
                $stmtB->execute();
                if ($stmtB->fetchColumn()) {
                    $role = 'banned';
                }
            }
        }

        Response::success(['forum' => $forum, 'role' => $role, 'current_user_id' => $userId]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Lista de mensajes del foro (paginados, con info de autor y reply)
 */
function getMessages()
{
    $forumId = (int)($_GET['forum_id'] ?? 0);
    if (!$forumId) Response::error('forum_id requerido');

    $since   = $_GET['since']  ?? null; // timestamp ISO para polling
    $limit   = min((int)($_GET['limit'] ?? 50), 100);
    $userId  = $_SESSION['user_id'] ?? null;

    global $db;
    try {
        // Verificar si el usuario está baneado
        if ($userId) {
            $stmtB = $db->prepare("SELECT 1 FROM forum_banned WHERE forum_id = :fid AND user_id = :uid");
            $stmtB->bindValue(':fid', $forumId, PDO::PARAM_INT);
            $stmtB->bindValue(':uid', $userId,  PDO::PARAM_INT);
            $stmtB->execute();
            if ($stmtB->fetchColumn()) {
                Response::error('Estás baneado de este foro', 403);
            }
        }

        // Comprobar si el usuario es owner o admin (pueden ver mensajes ocultos)
        $isOwnerOrAdmin = false;
        if ($userId) {
            $stmtO = $db->prepare("SELECT author_id FROM forums WHERE id = :fid");
            $stmtO->bindValue(':fid', $forumId, PDO::PARAM_INT);
            $stmtO->execute();
            $authorId = $stmtO->fetchColumn();
            $isOwnerOrAdmin = ((int)$authorId === (int)$userId)
                || (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin');
        }

        $hiddenClause = $isOwnerOrAdmin ? '' : 'AND m.is_hidden = FALSE';
        $sinceClause  = $since ? 'AND m.created_at > :since' : '';

        $sql = "
            SELECT m.id, m.forum_id, m.user_id, m.content,
                   m.reply_to_id, m.attachment_url, m.file_name,
                   m.is_hidden, m.created_at,
                   u.username, u.profile_image,
                   -- Snippet del mensaje original al que se responde
                   rm.content   AS reply_content,
                   ru.username  AS reply_username
            FROM forum_messages m
            JOIN users u ON m.user_id = u.id
            LEFT JOIN forum_messages rm ON m.reply_to_id = rm.id
            LEFT JOIN users ru ON rm.user_id = ru.id
            WHERE m.forum_id = :fid
            $hiddenClause
            $sinceClause
            ORDER BY m.created_at ASC
            LIMIT :lim
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit,   PDO::PARAM_INT);
        if ($since) $stmt->bindValue(':since', $since, PDO::PARAM_STR);
        $stmt->execute();
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['messages' => $messages]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Enviar un mensaje al foro (con rate-limit 1/min para usuarios normales)
 */
function sendMessage()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId    = (int)($_POST['forum_id'] ?? 0);
    $content    = trim($_POST['content'] ?? '');
    $replyToId  = (int)($_POST['reply_to_id'] ?? 0) ?: null;
    $attachUrl  = trim($_POST['attachment_url'] ?? '') ?: null;
    $fileName   = trim($_POST['file_name'] ?? '') ?: null;
    $userId     = $_SESSION['user_id'];

    if (!$forumId) Response::error('forum_id requerido');
    if (empty($content) && !$attachUrl) Response::error('El mensaje no puede estar vacío');
    if (strlen($content) > 2000) Response::error('Mensaje demasiado largo (máx. 2000 caracteres)');

    global $db;
    try {
        // Comprobar que existe el foro
        $stmtF = $db->prepare("SELECT author_id, privacy FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $forum = $stmtF->fetch(PDO::FETCH_ASSOC);
        if (!$forum) Response::error('Foro no encontrado', 404);

        // Comprobar ban
        $stmtB = $db->prepare("SELECT 1 FROM forum_banned WHERE forum_id = :fid AND user_id = :uid");
        $stmtB->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtB->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtB->execute();
        if ($stmtB->fetchColumn()) Response::error('Estás baneado de este foro', 403);

        $isOwner        = (int)$forum['author_id'] === (int)$userId;
        $isAdmin        = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
        
        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if (!$isOwner && !$isAdmin) {
            // Verificar colaborador en foros privados
            if ($forum['privacy'] === 'private') {
                if (!$isCollaborator) {
                    Response::error('No tienes permiso para escribir en este foro', 403);
                }
            }

            // Rate-limit: 1 mensaje por minuto (excepto owner/admin/collaborator)
            if (!$isCollaborator) {
                $stmtR = $db->prepare("
                    SELECT created_at FROM forum_messages
                    WHERE forum_id = :fid AND user_id = :uid
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmtR->bindValue(':fid', $forumId, PDO::PARAM_INT);
                $stmtR->bindValue(':uid', $userId,  PDO::PARAM_INT);
                $stmtR->execute();
                $lastMsg = $stmtR->fetchColumn();
                if ($lastMsg) {
                    $secondsElapsed = (new DateTime())->getTimestamp() - (new DateTime($lastMsg))->getTimestamp();
                    if ($secondsElapsed < 60) {
                        $remaining = 60 - $secondsElapsed;
                        Response::error("Debes esperar {$remaining}s para enviar otro mensaje", 429);
                    }
                }
            }
        }

        $stmt = $db->prepare("
            INSERT INTO forum_messages (forum_id, user_id, content, reply_to_id, attachment_url, file_name)
            VALUES (:fid, :uid, :content, :reply_to_id, :attachment_url, :file_name)
            RETURNING id, created_at
        ");
        $stmt->bindValue(':fid',            $forumId,   PDO::PARAM_INT);
        $stmt->bindValue(':uid',            $userId,    PDO::PARAM_INT);
        $stmt->bindValue(':content',        $content,   PDO::PARAM_STR);
        $stmt->bindValue(':reply_to_id',    $replyToId, $replyToId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':attachment_url', $attachUrl, $attachUrl ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':file_name',      $fileName,  $fileName  ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        Response::success(['message_id' => $row['id'], 'created_at' => $row['created_at']]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Borrar un mensaje (solo el autor o el owner del foro)
 */
function deleteMessage()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $msgId   = (int)($_POST['message_id'] ?? 0);
    $userId  = $_SESSION['user_id'];

    if (!$msgId) Response::error('message_id requerido');

    global $db;
    try {
        $stmt = $db->prepare("
            SELECT m.user_id, m.forum_id, f.author_id
            FROM forum_messages m
            JOIN forums f ON m.forum_id = f.id
            WHERE m.id = :mid
        ");
        $stmt->bindValue(':mid', $msgId, PDO::PARAM_INT);
        $stmt->execute();
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$msg) Response::error('Mensaje no encontrado', 404);

        $isAuthor  = (int)$msg['user_id']   === (int)$userId;
        $isOwner   = (int)$msg['author_id'] === (int)$userId;
        $isAdmin   = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $msg['forum_id'], PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if (!$isAuthor && !$isOwner && !$isAdmin && !$isCollaborator) {
            Response::error('Sin permiso para borrar este mensaje', 403);
        }

        $stmtD = $db->prepare("DELETE FROM forum_messages WHERE id = :mid");
        $stmtD->bindValue(':mid', $msgId, PDO::PARAM_INT);
        $stmtD->execute();

        Response::success(['message' => 'Mensaje borrado']);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Ocultar / mostrar un mensaje (solo owner del foro o admin)
 */
function hideMessage()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $msgId   = (int)($_POST['message_id'] ?? 0);
    $hidden  = (int)($_POST['hidden'] ?? 1); // 1 = ocultar, 0 = mostrar
    $userId  = $_SESSION['user_id'];

    if (!$msgId) Response::error('message_id requerido');

    global $db;
    try {
        $stmt = $db->prepare("
            SELECT f.author_id, f.id as forum_id FROM forum_messages m
            JOIN forums f ON m.forum_id = f.id
            WHERE m.id = :mid
        ");
        $stmt->bindValue(':mid', $msgId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) Response::error('Mensaje no encontrado', 404);

        $isOwner = (int)$row['author_id'] === (int)$userId;
        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $row['forum_id'], PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if (!$isOwner && !$isAdmin && !$isCollaborator) Response::error('Sin permiso', 403);

        $stmtU = $db->prepare("UPDATE forum_messages SET is_hidden = :h WHERE id = :mid");
        $stmtU->bindValue(':h',   (bool)$hidden, PDO::PARAM_BOOL);
        $stmtU->bindValue(':mid', $msgId,        PDO::PARAM_INT);
        $stmtU->execute();

        Response::success(['message' => $hidden ? 'Mensaje ocultado' : 'Mensaje visible']);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Banear un usuario del foro (solo owner)
 */
function banUser()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId      = (int)($_POST['forum_id']   ?? 0);
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $userId       = $_SESSION['user_id'];

    if (!$forumId || !$targetUserId) Response::error('forum_id y target_user_id requeridos');
    if ($targetUserId === $userId)   Response::error('No puedes banearte a ti mismo');

    global $db;
    try {
        $stmtF = $db->prepare("SELECT author_id FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $authorId = $stmtF->fetchColumn();

        if (!$authorId) Response::error('Foro no encontrado', 404);

        $isOwner = (int)$authorId === (int)$userId;
        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if (!$isOwner && !$isAdmin && !$isCollaborator) Response::error('Sin permiso para banear', 403);

        if ((int)$targetUserId === (int)$authorId) Response::error('No puedes banear al propietario del foro');

        if ($isCollaborator && !$isOwner && !$isAdmin) {
            $stmtC->bindValue(':uid', $targetUserId, PDO::PARAM_INT);
            $stmtC->execute();
            if ($stmtC->fetchColumn()) Response::error('No puedes banear a otro colaborador');
        }

        $stmtB = $db->prepare("
            INSERT INTO forum_banned (forum_id, user_id)
            VALUES (:fid, :uid)
            ON CONFLICT (forum_id, user_id) DO NOTHING
        ");
        $stmtB->bindValue(':fid', $forumId,      PDO::PARAM_INT);
        $stmtB->bindValue(':uid', $targetUserId, PDO::PARAM_INT);
        $stmtB->execute();

        // Borrar todos los mensajes del usuario baneado en este foro
        $stmtDel = $db->prepare("DELETE FROM forum_messages WHERE forum_id = :fid AND user_id = :uid");
        $stmtDel->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtDel->bindValue(':uid', $targetUserId, PDO::PARAM_INT);
        $stmtDel->execute();

        Response::success(['message' => 'Usuario baneado']);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Desbanear a un usuario del foro (solo owner)
 */
function unbanUser()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId      = (int)($_POST['forum_id']       ?? 0);
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $userId       = $_SESSION['user_id'];

    if (!$forumId || !$targetUserId) Response::error('forum_id y target_user_id requeridos');

    global $db;
    try {
        $stmtF = $db->prepare("SELECT author_id FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $authorId = $stmtF->fetchColumn();

        $isOwner = (int)$authorId === (int)$userId;
        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if (!$isOwner && !$isAdmin && !$isCollaborator) Response::error('Sin permiso', 403);

        $stmtD = $db->prepare("DELETE FROM forum_banned WHERE forum_id = :fid AND user_id = :uid");
        $stmtD->bindValue(':fid', $forumId,      PDO::PARAM_INT);
        $stmtD->bindValue(':uid', $targetUserId, PDO::PARAM_INT);
        $stmtD->execute();

        Response::success(['message' => 'Usuario desbaneado']);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Lista de usuarios baneados en un foro (solo owner)
 */
function getBanned()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId = (int)($_GET['forum_id'] ?? 0);
    $userId  = $_SESSION['user_id'];

    if (!$forumId) Response::error('forum_id requerido');

    global $db;
    try {
        $stmtF = $db->prepare("SELECT author_id FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $authorId = $stmtF->fetchColumn();

        $isOwner = (int)$authorId === (int)$userId;
        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
        
        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if (!$isOwner && !$isAdmin && !$isCollaborator) Response::error('Sin permiso', 403);

        $stmt = $db->prepare("
            SELECT fb.user_id, u.username, u.profile_image, fb.banned_at
            FROM forum_banned fb
            JOIN users u ON fb.user_id = u.id
            WHERE fb.forum_id = :fid
            ORDER BY fb.banned_at DESC
        ");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->execute();
        $banned = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['banned' => $banned]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Obtener la lista de colaboradores del foro (solo owner/admin/collab)
 */
function getCollaborators()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId = (int)($_GET['forum_id'] ?? 0);
    $userId  = $_SESSION['user_id'];
    if (!$forumId) Response::error('forum_id requerido');

    global $db;
    try {
        $stmtF = $db->prepare("SELECT author_id, privacy FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $forum = $stmtF->fetch(PDO::FETCH_ASSOC);

        if (!$forum) Response::error('Foro no encontrado', 404);

        $authorId = $forum['author_id'];
        $privacy = $forum['privacy'];

        $isOwner = (int)$authorId === (int)$userId;
        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
        $stmtC->execute();
        $isCollaborator = (bool)$stmtC->fetchColumn();

        if ($privacy !== 'public' && !$isOwner && !$isAdmin && !$isCollaborator) {
            Response::error('Sin permiso', 403);
        }
        $stmt = $db->prepare("
            SELECT u.id AS user_id, u.username, u.profile_image 
            FROM forum_collaborators fc
            JOIN users u ON fc.user_id = u.id
            WHERE fc.forum_id = :fid
        ");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->execute();
        $collaborators = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['collaborators' => $collaborators]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Eliminar a un colaborador del foro (solo owner/admin)
 */
function removeCollaborator()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId      = (int)($_POST['forum_id'] ?? 0);
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $userId       = $_SESSION['user_id'];

    if (!$forumId || !$targetUserId) Response::error('Datos incompletos');

    global $db;
    try {
        // Verificar permisos
        $stmtA = $db->prepare("SELECT author_id FROM forums WHERE id = :fid");
        $stmtA->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtA->execute();
        $authorId = $stmtA->fetchColumn();

        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
        if ((int)$authorId !== (int)$userId && !$isAdmin) {
            Response::error('No tienes permiso para gestionar colaboradores', 403);
        }

        $stmt = $db->prepare("DELETE FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $targetUserId, PDO::PARAM_INT);
        $stmt->execute();

        Response::success(['message' => 'Colaborador eliminado']);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

/**
 * Enviar invitación a un amigo para participar en el foro (solo owner/admin)
 */
function inviteCollaborator()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId      = (int)($_POST['forum_id'] ?? 0);
    $targetUserId = (int)($_POST['target_user_id'] ?? 0);
    $userId       = $_SESSION['user_id'];

    if (!$forumId || !$targetUserId) Response::error('Datos incompletos');
    if ($userId === $targetUserId) Response::error('No puedes invitarte a ti mismo');

    global $db;
    try {
        // 1. Verificar si el foro existe y es del autor (o es admin)
        $stmtF = $db->prepare("SELECT author_id, privacy FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $forum = $stmtF->fetch(PDO::FETCH_ASSOC);

        if (!$forum) Response::error('Foro no encontrado', 404);

        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
        if ((int)$forum['author_id'] !== (int)$userId && !$isAdmin) {
            Response::error('Sin permiso para invitar a este foro', 403);
        }

        // 2. Verificar que son amigos (a menos que sea admin invitando)
        if (!$isAdmin) {
            $stmtFriend = $db->prepare("
                SELECT 1 FROM friendship 
                WHERE estado_solicitud = 'ACEPTADA' 
                  AND ((solicitante_id = :u1 AND solicitada_id = :u2) OR (solicitante_id = :u2 AND solicitada_id = :u1))
            ");
            $stmtFriend->execute(['u1' => $userId, 'u2' => $targetUserId]);
            if (!$stmtFriend->fetchColumn()) {
                Response::error('Solo puedes invitar a usuarios de tu lista de amigos');
            }
        }

        // 3. Verificar límite de colaboradores (solo importa cuántos aceptados hay realmente en forum_collaborators)
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM forum_collaborators WHERE forum_id = :fid");
        $stmtCount->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtCount->execute();
        $collabCount = (int)$stmtCount->fetchColumn();

        if ($collabCount >= 5) {
            Response::error('El foro ya tiene 5 colaboradores, el límite máximo permitido.');
        }

        // 4. Verificar si ya es colaborador o ya hay invitación pendiente
        $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
        $stmtC->execute(['fid' => $forumId, 'uid' => $targetUserId]);
        if ($stmtC->fetchColumn()) Response::error('Este usuario ya es colaborador del foro');

        // 5. Insertar invitación (ON CONFLICT actualiza a pending si hubiese rechazado antes)
        $stmtI = $db->prepare("
            INSERT INTO forum_invitations (forum_id, sender_id, receiver_id, status)
            VALUES (:fid, :sender, :receiver, 'pending')
            ON CONFLICT (forum_id, receiver_id) DO UPDATE SET status = 'pending'
        ");
        $stmtI->execute([
            'fid' => $forumId,
            'sender' => $userId,
            'receiver' => $targetUserId
        ]);

        Response::success(['message' => 'Invitación enviada al usuario con éxito']);
    } catch (PDOException $e) {
        Response::error('Error de base de datos: ' . $e->getMessage());
    }
}

/**
 * Lista de usuarios que han participado en el foro, con conteo de mensajes.
 * Accesible para owner/admin (que pueden banear), y colaboradores/cualquiera si es público (solo lectura).
 */
function getParticipants()
{
    if (!isset($_SESSION['user_id'])) Response::unauthorized('No autenticado');

    $forumId = (int)($_GET['forum_id'] ?? 0);
    $userId  = (int)$_SESSION['user_id'];
    if (!$forumId) Response::error('forum_id requerido');

    global $db;
    try {
        // Verificar que el foro existe y obtener privacidad
        $stmtF = $db->prepare("SELECT author_id, privacy FROM forums WHERE id = :fid");
        $stmtF->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmtF->execute();
        $forum = $stmtF->fetch(PDO::FETCH_ASSOC);
        if (!$forum) Response::error('Foro no encontrado', 404);

        $isOwner = (int)$forum['author_id'] === $userId;
        $isAdmin = isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';

        // Solo foros públicos se pueden ver sin ser owner/admin
        if ($forum['privacy'] !== 'public' && !$isOwner && !$isAdmin) {
            $stmtC = $db->prepare("SELECT 1 FROM forum_collaborators WHERE forum_id = :fid AND user_id = :uid");
            $stmtC->bindValue(':fid', $forumId, PDO::PARAM_INT);
            $stmtC->bindValue(':uid', $userId,  PDO::PARAM_INT);
            $stmtC->execute();
            if (!$stmtC->fetchColumn()) Response::error('Sin permiso', 403);
        }

        // Participantes con conteo de mensajes (excluir mensajes ocultos)
        $stmt = $db->prepare("
            SELECT u.id AS user_id, u.username, u.profile_image,
                   COUNT(fm.id) AS message_count,
                   MAX(fm.created_at) AS last_message_at
            FROM forum_messages fm
            JOIN users u ON fm.user_id = u.id
            WHERE fm.forum_id = :fid AND fm.is_hidden = FALSE
            GROUP BY u.id, u.username, u.profile_image
            ORDER BY message_count DESC
        ");
        $stmt->bindValue(':fid', $forumId, PDO::PARAM_INT);
        $stmt->execute();
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Incluir si el solicitante puede banear (owner o admin)
        Response::success([
            'participants' => $participants,
            'can_ban'      => $isOwner || $isAdmin,
            'author_id'    => (int)$forum['author_id'],
        ]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}