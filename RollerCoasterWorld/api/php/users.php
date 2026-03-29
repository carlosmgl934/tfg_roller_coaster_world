<?php
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/ApiRouter.php';

session_start();

header('Content-Type: application/json');

$db = new DBConexion();
$router = new ApiRouter();

$router->register('search_users', 'searchUsers');
$router->register('friend_request', 'sendFriendRequest', 'POST');
$router->register('accept_friend', 'acceptFriendRequest', 'POST');
$router->register('reject_remove_friend', 'rejectOrRemoveFriend', 'POST');
$router->register('get_friends_data', 'getFriendsData');
$router->register('get_public_profile', 'getPublicProfile');

$router->dispatch();

function getUserId(): ?int
{
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($_SESSION['firebase_uid'])) {
        global $db;
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int)$row['id'];
            return (int)$row['id'];
        }
    }
    return null;
}

function searchUsers() {
    global $db;
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
            SELECT u.id, u.username, u.profile_image, 
                   f.estado_solicitud,
                   f.solicitante_id,
                   f.solicitada_id
            FROM users u
            LEFT JOIN friendship f ON 
                (f.solicitante_id = u.id AND f.solicitada_id = :cid) 
                OR 
                (f.solicitante_id = :cid AND f.solicitada_id = u.id)
            WHERE u.id != :cid AND u.username ILIKE :q
            ORDER BY u.username ASC
            LIMIT 10
        ");
        $stmt->execute([
            ':cid' => $current_user_id,
            ':q' => '%' . $q . '%'
        ]);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear resultados
        $data = array_map(function($row) use ($current_user_id) {
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
                'profile_image' => $row['profile_image'],
                'friendship_status' => $status
            ];
        }, $results);
        
        Response::success(['data' => $data]);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function sendFriendRequest() {
    global $db;
    $current_user_id = getUserId();
    if (!$current_user_id) Response::error("No autorizado", 401);
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $target_id = $data['target_id'] ?? null;
    
    if (!$target_id || $target_id == $current_user_id) Response::error("ID inválido");
    
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

function acceptFriendRequest() {
    global $db;
    $current_user_id = getUserId();
    if (!$current_user_id) Response::error("No autorizado", 401);
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $target_id = $data['target_id'] ?? null;
    
    if (!$target_id) Response::error("ID inválido");
    
    try {
        $stmt = $db->prepare("UPDATE friendship SET estado_solicitud = 'ACEPTADA', accepted_at = NOW() WHERE solicitante_id = ? AND solicitada_id = ? AND estado_solicitud = 'PENDIENTE'");
        $stmt->execute([$target_id, $current_user_id]); // Target was the sender
        Response::success(['message' => 'Solicitud aceptada']);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function rejectOrRemoveFriend() {
    global $db;
    $current_user_id = getUserId();
    if (!$current_user_id) Response::error("No autorizado", 401);
    
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $target_id = $data['target_id'] ?? null;
    
    if (!$target_id) Response::error("ID inválido");
    
    try {
        $stmt = $db->prepare("DELETE FROM friendship WHERE (solicitante_id = ? AND solicitada_id = ?) OR (solicitante_id = ? AND solicitada_id = ?)");
        $stmt->execute([$current_user_id, $target_id, $target_id, $current_user_id]);
        Response::success(['message' => 'Amistad o solicitud eliminada']);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function getFriendsData() {
    global $db;
    $current_user_id = getUserId();
    if (!$current_user_id) Response::error("No autorizado", 401);
    
    try {
        // Amigos aceptados
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.profile_image, f.accepted_at as since 
            FROM friendship f
            JOIN users u ON u.id = CASE WHEN f.solicitante_id = :uid THEN f.solicitada_id ELSE f.solicitante_id END
            WHERE (f.solicitante_id = :uid OR f.solicitada_id = :uid) AND f.estado_solicitud = 'ACEPTADA'
        ");
        $stmt->execute([':uid' => $current_user_id]);
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Solicitudes recibidas
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.profile_image, f.created_at
            FROM friendship f
            JOIN users u ON u.id = f.solicitante_id
            WHERE f.solicitada_id = :uid AND f.estado_solicitud = 'PENDIENTE'
        ");
        $stmt->execute([':uid' => $current_user_id]);
        $received = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Solicitudes enviadas
        $stmt = $db->prepare("
            SELECT u.id, u.username, u.profile_image, f.created_at
            FROM friendship f
            JOIN users u ON u.id = f.solicitada_id
            WHERE f.solicitante_id = :uid AND f.estado_solicitud = 'PENDIENTE'
        ");
        $stmt->execute([':uid' => $current_user_id]);
        $sent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        Response::success([
            'data' => [
                'friends' => $friends,
                'received_requests' => $received,
                'sent_requests' => $sent
            ]
        ]);
    } catch (Exception $e) {
        Response::error("Error en servidor", 500);
    }
}

function getPublicProfile() {
    global $db;
    $target_id = $_GET['id'] ?? null;
    $current_user_id = getUserId();
    
    if (!$target_id) Response::error("ID de usuario no especificado");
    
    try {
        // Datos básicos del usuario
        $stmt = $db->prepare("SELECT id, username, profile_image, city, country, favorite_coaster, home_park, created_at FROM users WHERE id = :tid");
        $stmt->execute([':tid' => $target_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) Response::notFound("Usuario no encontrado");
        
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
        
        $stmtP = $db->prepare("SELECT COUNT(DISTINCT c.park_id) FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :tid");
        $stmtP->execute([':tid' => $target_id]);
        $parks_count = $stmtP->fetchColumn();
        
        // Top 5 Parques del usuario
        $stmtTop = $db->prepare("
            SELECT up.rank_position, p.id, p.park_name, p.park_country, p.imagen_url
            FROM user_park_credits up
            JOIN parks p ON up.park_id = p.id
            WHERE up.user_id = :tid
            ORDER BY up.rank_position ASC
            LIMIT 5
        ");
        $stmtTop->execute([':tid' => $target_id]);
        $top_parks = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
        
        Response::success([
            'data' => [
                'user' => $user,
                'friendship_status' => $friendship,
                'stats' => [
                    'coasters' => $coasters_count,
                    'parks' => $parks_count
                ],
                'top_parks' => $top_parks
            ]
        ]);
        
    } catch (Exception $e) {
        Response::error("Error cargando perfil: " . $e->getMessage(), 500);
    }
}
