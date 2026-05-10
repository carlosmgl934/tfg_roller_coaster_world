<?php
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/Response.php';

header('Content-Type: application/json');

// Verificar permisos de admin
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    Response::error('No tienes permisos de administrador', 403);
    exit;
}

$db = new DBConexion();
$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];
$body = json_decode(file_get_contents('php://input'), true);

try {
    if ($action === 'list' && $method === 'GET') {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? ''; // 'read' or 'unread'
        $reason = $_GET['reason'] ?? '';

        $whereParams = [];
        $whereClauses = [];

        if ($search) {
            $whereClauses[] = "(user_name ILIKE :search OR user_email ILIKE :search OR subject ILIKE :search)";
            $whereParams[':search'] = "%$search%";
        }

        if ($status === 'read') {
            $whereClauses[] = "is_read = TRUE";
        } elseif ($status === 'unread') {
            $whereClauses[] = "is_read = FALSE";
        }

        if ($reason) {
            $whereClauses[] = "reason = :reason";
            $whereParams[':reason'] = $reason;
        }

        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = "WHERE " . implode(" AND ", $whereClauses);
        }

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM contact_messages $whereSql");
        foreach ($whereParams as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        // Get rows
        $stmt = $db->prepare("SELECT * FROM contact_messages $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        foreach ($whereParams as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]);
    } 
    elseif ($action === 'update_status' && $method === 'POST') {
        $id = $body['id'] ?? 0;
        $is_read = isset($body['is_read']) ? (bool)$body['is_read'] : true;

        if (!$id) Response::error('ID inválido', 400);

        $stmt = $db->prepare("UPDATE contact_messages SET is_read = :is_read WHERE id = :id");
        $stmt->execute([':is_read' => $is_read ? 'true' : 'false', ':id' => $id]);
        
        Response::success(['message' => 'Estado actualizado']);
    } 
    elseif ($action === 'delete' && $method === 'POST') {
        $id = $body['id'] ?? 0;
        
        if (!$id) Response::error('ID inválido', 400);

        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        Response::success(['message' => 'Mensaje eliminado']);
    } 
    else {
        Response::error('Ruta no encontrada o método incorrecto', 404);
    }
} catch (PDOException $e) {
    error_log($e->getMessage()); Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
}
