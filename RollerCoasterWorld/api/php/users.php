<?php
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/ApiRouter.php';

session_start();

header('Content-Type: application/json');

$db = null;

function getDb()
{
    global $db;
    if ($db === null) {
        $db = new DBConexion();
    }
    return $db;
}

$router = new ApiRouter();

$router->register('search_users', 'searchUsers');
$router->register('friend_request', 'sendFriendRequest', 'POST');
$router->register('accept_friend', 'acceptFriendRequest', 'POST');
$router->register('reject_remove_friend', 'rejectOrRemoveFriend', 'POST');
$router->register('get_friends_data', 'getFriendsData');
$router->register('get_public_profile', 'getPublicProfile');
$router->register('accept_forum_invite', 'acceptForumInvite', 'POST');
$router->register('decline_forum_invite', 'declineForumInvite', 'POST');

$router->dispatch();

function getUserId(): ?int
{
    if (isset($_SESSION['user_id']))
        return (int) $_SESSION['user_id'];
    if (isset($_SESSION['firebase_uid'])) {
        $db = getDb();
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int) $row['id'];
            return (int) $row['id'];
        }
    }
    return null;
}

function searchUsers()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id) {
        Response::error("No autorizado", 401);
    }

    $q = $_GET['q'] ?? '';

    if (empty($q)) {
        Response::success(['data' => []]);
    }

    try {
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.full_name, u.city, u.country, u.profile_image, 
                   f.estado_solicitud,
                   f.solicitante_id,
                   f.solicitada_id
            FROM users u
            LEFT JOIN friendship f ON 
                (f.solicitante_id = u.id AND f.solicitada_id = :cid) 
                OR 
                (f.solicitante_id = :cid AND f.solicitada_id = u.id)
            WHERE u.id != :cid AND (u.username ILIKE :q OR u.full_name ILIKE :q)
            ORDER BY u.username ASC
            LIMIT 10
        ");
        $stmt->execute([
            ':cid' => $current_user_id,
            ':q' => '%' . $q . '%'
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear resultados
        $data = array_map(function ($row) use ($current_user_id) {
            $status = 'none';
            if ($row['estado_solicitud'] === 'PENDIENTE') {
                if ($row['solicitante_id'] == $current_user_id) {
                    $status = 'pending_sent';
                } else {
                    $status = 'pending_received';
                }
            } else if ($row['estado_solicitud'] === 'ACEPTADA') {
                $status = 'accepted';
            }

            return [
                'id' => $row['id'],
                'username' => $row['username'],
                'full_name' => $row['full_name'],
                'city' => $row['city'],
                'country' => $row['country'],
                'profile_image' => $row['profile_image'],
                'friendship_status' => $status
            ];
        }, $results);

        Response::success(['data' => $data]);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function sendFriendRequest()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id)
        Response::error("No autorizado", 401);

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $target_id = $data['target_id'] ?? null;

    if (!$target_id || $target_id == $current_user_id)
        Response::error("ID inválido");

    try {
        $stmt = $db->prepare("SELECT id FROM friendship WHERE (solicitante_id = ? AND solicitada_id = ?) OR (solicitante_id = ? AND solicitada_id = ?)");
        $stmt->execute([$current_user_id, $target_id, $target_id, $current_user_id]);
        if ($stmt->fetch()) {
            Response::error("Ya existe una relación o solicitud con este usuario");
        }

        $stmt = $db->prepare("INSERT INTO friendship (estado_solicitud, solicitante_id, solicitada_id, created_at) VALUES ('PENDIENTE', ?, ?, NOW())");
        $stmt->execute([$current_user_id, $target_id]);
        Response::success(['message' => 'Solicitud enviada']);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function acceptFriendRequest()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id)
        Response::error("No autorizado", 401);

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $target_id = $data['target_id'] ?? null;

    if (!$target_id)
        Response::error("ID inválido");

    try {
        $stmt = $db->prepare("UPDATE friendship SET estado_solicitud = 'ACEPTADA', accepted_at = NOW() WHERE solicitante_id = ? AND solicitada_id = ? AND estado_solicitud = 'PENDIENTE'");
        $stmt->execute([$target_id, $current_user_id]); // Target was the sender
        Response::success(['message' => 'Solicitud aceptada']);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function rejectOrRemoveFriend()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id)
        Response::error("No autorizado", 401);

    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $target_id = $data['target_id'] ?? null;

    if (!$target_id)
        Response::error("ID inválido");

    try {
        $stmt = $db->prepare("DELETE FROM friendship WHERE (solicitante_id = ? AND solicitada_id = ?) OR (solicitante_id = ? AND solicitada_id = ?)");
        $stmt->execute([$current_user_id, $target_id, $target_id, $current_user_id]);
        Response::success(['message' => 'Amistad o solicitud eliminada']);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function getFriendsData()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id)
        Response::error("No autorizado", 401);

    try {
        // Amigos aceptados
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.profile_image, f.accepted_at as since, u.city, u.country, u.created_at as joined_at,
                   (SELECT COUNT(*) FROM user_credits uc WHERE uc.user_id = u.id) as credits,
                   (SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = u.id AND uc.rank_position > 0 ORDER BY uc.rank_position ASC LIMIT 1) as favorite_coaster
            FROM friendship f
            JOIN users u ON u.id = CASE WHEN f.solicitante_id = :uid THEN f.solicitada_id ELSE f.solicitante_id END
            WHERE (f.solicitante_id = :uid OR f.solicitada_id = :uid) AND f.estado_solicitud = 'ACEPTADA'
        ");
        $stmt->execute([':uid' => $current_user_id]);
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Solicitudes de amistad recibidas
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.profile_image, f.created_at
            FROM friendship f
            JOIN users u ON u.id = f.solicitante_id
            WHERE f.solicitada_id = :uid AND f.estado_solicitud = 'PENDIENTE'
        ");
        $stmt->execute([':uid' => $current_user_id]);
        $received = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Solicitudes de amistad enviadas
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.profile_image, f.created_at
            FROM friendship f
            JOIN users u ON u.id = f.solicitada_id
            WHERE f.solicitante_id = :uid AND f.estado_solicitud = 'PENDIENTE'
        ");
        $stmt->execute([':uid' => $current_user_id]);
        $sent = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Invitaciones de colaboración de foro recibidas (pendientes)
        $stmtFI = $db->prepare("
            SELECT fi.id as invite_id, fi.forum_id, fo.title as forum_title, fo.forum_subject,
                   fo.forum_subject as forum_description,
                   u.id as sender_id, u.username as sender_username, u.profile_image as sender_image,
                   fi.created_at,
                   (SELECT COUNT(*) FROM forum_collaborators fc WHERE fc.forum_id = fo.id) + 1 AS member_count
            FROM forum_invitations fi
            JOIN forums  fo ON fi.forum_id  = fo.id
            JOIN users    u ON fi.sender_id  = u.id
            WHERE fi.receiver_id = :uid AND fi.status = 'pending'
            ORDER BY fi.created_at DESC
        ");
        $stmtFI->execute([':uid' => $current_user_id]);
        $forum_invitations = $stmtFI->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'data' => [
                'friends'           => $friends,
                'received_requests' => $received,
                'sent_requests'     => $sent,
                'forum_invitations' => $forum_invitations
            ]
        ]);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function acceptForumInvite()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id) Response::error("No autorizado", 401);

    $body      = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $invite_id = (int)($body['invite_id'] ?? 0);
    if (!$invite_id) Response::error("ID de invitación inválido");

    try {
        // Verificar que la invitación es para este usuario
        $stmt = $db->prepare("SELECT forum_id FROM forum_invitations WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$invite_id, $current_user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) Response::error("Invitación no encontrada o ya procesada");

        $forum_id = $row['forum_id'];

        // Marcar invitación como aceptada
        $db->prepare("UPDATE forum_invitations SET status = 'accepted' WHERE id = ?")->execute([$invite_id]);

        // Añadir al usuario como colaborador
        $db->prepare("INSERT INTO forum_collaborators (forum_id, user_id) VALUES (?, ?) ON CONFLICT DO NOTHING")
           ->execute([$forum_id, $current_user_id]);

        Response::success(['message' => 'Invitación aceptada']);
    } catch (Exception $e) {
        Response::error("Error al aceptar la invitación", 500);
    }
}

function declineForumInvite()
{
    $db = getDb();
    $current_user_id = getUserId();
    if (!$current_user_id) Response::error("No autorizado", 401);

    $body      = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $invite_id = (int)($body['invite_id'] ?? 0);
    if (!$invite_id) Response::error("ID de invitación inválido");

    try {
        $stmt = $db->prepare("UPDATE forum_invitations SET status = 'declined' WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$invite_id, $current_user_id]);
        if ($stmt->rowCount() === 0) Response::error("Invitación no encontrada o ya procesada");
        Response::success(['message' => 'Invitación rechazada']);
    } catch (Exception $e) {
        Response::error("Error al rechazar la invitación", 500);
    }
}

function getPublicProfile()
{
    $db = getDb();
    $target_id = $_GET['id'] ?? null;
    $current_user_id = getUserId();

    if (!$target_id)
        Response::error("ID de usuario no especificado");

    try {
        // Datos básicos del usuario
        $stmt = $db->prepare("SELECT id, username, profile_image, city, country, home_park, created_at FROM users WHERE id = :tid");
        $stmt->execute([':tid' => $target_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user)
            Response::notFound("Usuario no encontrado");

        // Coaster favorita (Top 1)
        $stmtFC = $db->prepare("SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid AND uc.rank_position > 0 ORDER BY uc.rank_position ASC LIMIT 1");
        $stmtFC->execute([':tid' => $target_id]);
        $fav_coaster = $stmtFC->fetchColumn();
        $user['favorite_coaster'] = $fav_coaster ?: null;

        // Estado de amistad con el visor
        $friendship = null;
        if ($current_user_id && $current_user_id != $target_id) {
            $stmtF = $db->prepare("SELECT estado_solicitud, solicitante_id FROM friendship WHERE (solicitante_id = :cid AND solicitada_id = :tid) OR (solicitante_id = :tid AND solicitada_id = :cid)");
            $stmtF->execute([':cid' => $current_user_id, ':tid' => $target_id]);
            $f_row = $stmtF->fetch(PDO::FETCH_ASSOC);
            if ($f_row) {
                if ($f_row['estado_solicitud'] === 'PENDIENTE') {
                    $friendship = ($f_row['solicitante_id'] == $current_user_id) ? 'pending_sent' : 'pending_received';
                } else {
                    $friendship = 'accepted';
                }
            } else {
                $friendship = 'none';
            }
        }

        // Estadísticas básicas (Coasters y Parques)
        $stmtC = $db->prepare("SELECT COUNT(*) FROM user_credits WHERE user_id = :tid");
        $stmtC->execute([':tid' => $target_id]);
        $coasters_count = $stmtC->fetchColumn();

        // Parques Visitados (Unión de parques con coasters subidas y parques rankeados)
        $stmtP = $db->prepare("
            SELECT COUNT(*) FROM (
                SELECT park_id FROM user_park_credits WHERE user_id = :tid
                UNION
                SELECT c.park_id FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid
            ) as distinct_parks
        ");
        $stmtP->execute([':tid' => $target_id]);
        $parks_count = $stmtP->fetchColumn() ?: 0;

        // Países visitados basados en esa misma unión de parques
        $stmtCtryCount = $db->prepare("
            SELECT COUNT(DISTINCT park_country) FROM parks WHERE id IN (
                SELECT park_id FROM user_park_credits WHERE user_id = :tid
                UNION
                SELECT c.park_id FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid
            )
        ");
        $stmtCtryCount->execute([':tid' => $target_id]);
        $countries_count = $stmtCtryCount->fetchColumn() ?: 0;

        $stmtFriends = $db->prepare("SELECT COUNT(*) FROM friendship WHERE (solicitante_id = :tid OR solicitada_id = :tid) AND estado_solicitud = 'ACEPTADA'");
        $stmtFriends->execute([':tid' => $target_id]);
        $friends_count = $stmtFriends->fetchColumn() ?: 0;

        $stmtPhotosCount = $db->prepare("SELECT COUNT(*) FROM coaster_photos WHERE user_id = :tid AND status = 'approved'");
        $stmtPhotosCount->execute([':tid' => $target_id]);
        $photos_count = $stmtPhotosCount->fetchColumn() ?: 0;

        // Top Parques del usuario (ranked explicitly)
        $stmtTop = $db->prepare("
            SELECT up.rank_position, p.id, p.park_name, p.park_country, p.imagen_url
            FROM user_park_credits up
            JOIN parks p ON up.park_id = p.id
            WHERE up.user_id = :tid AND up.rank_position > 0
            ORDER BY up.rank_position ASC
        ");
        $stmtTop->execute([':tid' => $target_id]);
        $top_parks = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        // Fallback: Buscar parques en sus coasters si el usuario no los ha rankeado
        if (empty($top_parks)) {
            $stmtFallback = $db->prepare("
                SELECT ROW_NUMBER() OVER (ORDER BY p.park_name ASC) as rank_position,
                       p.id, p.park_name, p.park_country, p.imagen_url
                FROM (
                    SELECT DISTINCT c.park_id 
                    FROM user_credits uc 
                    JOIN coasters c ON uc.coaster_id = c.id 
                    WHERE uc.user_id = :tid
                ) ucp
                JOIN parks p ON ucp.park_id = p.id
                ORDER BY p.park_name ASC
            ");
            $stmtFallback->execute([':tid' => $target_id]);
            $top_parks = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
        }

        // Top Coasters del usuario
        $stmtTopCoasters = $db->prepare("
            SELECT uc.rank_position, c.id, c.coaster_name, c.coaster_manufacter as manufacturer, p.park_name as location, p.park_country, c.imagen_url
            FROM user_credits uc 
            JOIN coasters c ON uc.coaster_id = c.id 
            JOIN parks p ON c.park_id = p.id
            WHERE uc.user_id = :tid AND uc.rank_position > 0
            ORDER BY uc.rank_position ASC
        ");
        $stmtTopCoasters->execute([':tid' => $target_id]);
        $top_coasters = $stmtTopCoasters->fetchAll(PDO::FETCH_ASSOC);

        // Fallback para coasters: Si no hay, extraer todas alfabéticamente de sus credits list
        if (empty($top_coasters)) {
            $stmtFallCoasters = $db->prepare("
                SELECT ROW_NUMBER() OVER (ORDER BY c.coaster_name ASC) as rank_position,
                       c.id, c.coaster_name, c.coaster_manufacter as manufacturer, p.park_name as location, p.park_country, c.imagen_url
                FROM user_credits uc
                JOIN coasters c ON uc.coaster_id = c.id
                JOIN parks p ON c.park_id = p.id
                WHERE uc.user_id = :tid
                ORDER BY c.coaster_name ASC
            ");
            $stmtFallCoasters->execute([':tid' => $target_id]);
            $top_coasters = $stmtFallCoasters->fetchAll(PDO::FETCH_ASSOC);
        }

        // Estadísticas Técnicas Acumuladas
        $stmtTech = $db->prepare("
            SELECT 
                COALESCE(SUM(c.inversions), 0) as total_inversions,
                COALESCE(SUM(c.height), 0) as total_height,
                COUNT(DISTINCT c.coaster_manufacter) as total_manufacturers
            FROM user_credits uc
            JOIN coasters c ON uc.coaster_id = c.id
            WHERE uc.user_id = :tid
        ");
        $stmtTech->execute([':tid' => $target_id]);
        $tech_stats = $stmtTech->fetch(PDO::FETCH_ASSOC);

        // Más rápida
        $stmtFastest = $db->prepare("SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid AND c.speed IS NOT NULL ORDER BY c.speed DESC LIMIT 1");
        $stmtFastest->execute([':tid' => $target_id]);
        $tech_stats['fastest_coaster'] = $stmtFastest->fetchColumn() ?: '—';
        
        // Más larga
        $stmtLongest = $db->prepare("SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid AND c.coaster_length IS NOT NULL ORDER BY c.coaster_length DESC LIMIT 1");
        $stmtLongest->execute([':tid' => $target_id]);
        $tech_stats['longest_coaster'] = $stmtLongest->fetchColumn() ?: '—';

        // Fabricante más probado
        $stmtMan = $db->prepare("
            SELECT c.coaster_manufacter as manufacturer, COUNT(uc.coaster_id) as rides
            FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id
            WHERE uc.user_id = :tid AND c.coaster_manufacter IS NOT NULL AND c.coaster_manufacter != '' AND c.coaster_manufacter != 'Unknown'
            GROUP BY c.coaster_manufacter ORDER BY rides DESC LIMIT 1
        ");
        $stmtMan->execute([':tid' => $target_id]);
        $most_ridden_manu = $stmtMan->fetchColumn() ?: 'Ninguno';
        $tech_stats['favorite_manufacturer'] = $most_ridden_manu;

        // País más visitado
        $stmtCtry = $db->prepare("
            SELECT p.park_country 
            FROM parks p 
            JOIN (
                SELECT park_id FROM user_park_credits WHERE user_id = :tid
                UNION
                SELECT c.park_id FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid
            ) dp ON p.id = dp.park_id
            WHERE p.park_country IS NOT NULL AND p.park_country != ''
            GROUP BY p.park_country 
            ORDER BY COUNT(dp.park_id) DESC 
            LIMIT 1
        ");
        $stmtCtry->execute([':tid' => $target_id]);
        $most_visited_country = $stmtCtry->fetchColumn() ?: 'Ninguno';
        $tech_stats['most_visited_country'] = $most_visited_country;

        // Fotos del usuario
        $stmtPhotos = $db->prepare("
            SELECT cp.id, cp.photo_url, cp.caption, c.coaster_name, p.park_name 
            FROM coaster_photos cp 
            JOIN coasters c ON cp.coaster_id = c.id 
            JOIN parks p ON c.park_id = p.id
            WHERE cp.user_id = :tid AND cp.status = 'approved'
            ORDER BY cp.created_at DESC
        ");
        $stmtPhotos->execute([':tid' => $target_id]);
        $user_photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);

        // Lista de amigos del usuario
        $stmtFriendList = $db->prepare("
            SELECT u.id, u.username, u.profile_image, u.city, u.country, u.created_at, f.accepted_at as since,
                   (SELECT COUNT(*) FROM user_credits uc WHERE uc.user_id = u.id) as credits
            FROM friendship f
            JOIN users u ON u.id = CASE WHEN f.solicitante_id = :tid THEN f.solicitada_id ELSE f.solicitante_id END
            WHERE (f.solicitante_id = :tid OR f.solicitada_id = :tid) AND f.estado_solicitud = 'ACEPTADA'
            ORDER BY u.username ASC
        ");
        $stmtFriendList->execute([':tid' => $target_id]);
        $friend_list = $stmtFriendList->fetchAll(PDO::FETCH_ASSOC);

        // Reseñas del usuario (coaster_ratings y park_ratings)
        $stmtReviews = $db->prepare("
            SELECT 'coaster' as type, cr.coaster_id as item_id, c.coaster_name as title, p.park_name as subtitle, 
                   cr.note, cr.review, cr.created_at, c.imagen_url
            FROM coaster_ratings cr
            JOIN coasters c ON cr.coaster_id = c.id
            JOIN parks p ON c.park_id = p.id
            WHERE cr.user_id = :tid
            UNION ALL
            SELECT 'park' as type, pr.park_id as item_id, p.park_name as title, p.park_country as subtitle, 
                   pr.note, pr.review, pr.created_at, p.imagen_url
            FROM park_ratings pr
            JOIN parks p ON pr.park_id = p.id
            WHERE pr.user_id = :tid
            ORDER BY created_at DESC
        ");
        $stmtReviews->execute([':tid' => $target_id]);
        $user_reviews = $stmtReviews->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'data' => [
                'user' => $user,
                'friendship_status' => $friendship,
                'stats' => [
                    'coasters' => $coasters_count,
                    'parks' => $parks_count,
                    'countries' => $countries_count,
                    'friends' => $friends_count,
                    'photos' => $photos_count
                ],
                'tech_stats' => $tech_stats,
                'top_parks' => $top_parks,
                'top_coasters' => $top_coasters,
                'photos' => $user_photos,
                'friends' => $friend_list,
                'reviews' => $user_reviews
            ]
        ]);

    } catch (Exception $e) {
        Response::error("Error cargando perfil: " . $e->getMessage(), 500);
    }
}
