<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

header('Content-Type: application/json');

$db = new DBConexion();

$router = new ApiRouter();
$router->register('list',   'listUsers',   'GET');
$router->register('update', 'updateUser', 'POST');
$router->register('delete', 'deleteUser', 'POST');
$router->dispatch();

function requireAdmin(): void
{
    if (
        !isset($_SESSION['firebase_uid']) ||
        !isset($_SESSION['user_rol']) ||
        $_SESSION['user_rol'] !== 'admin'
    ) {
        Response::error('Acceso denegado.', 403);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// listUsers — lista de usuarios con búsqueda y paginación
// ─────────────────────────────────────────────────────────────
function listUsers(): void
{
    requireAdmin();

    $search  = trim($_GET['search']  ?? '');
    $rolFilt = trim($_GET['rol']     ?? '');
    $cntFilt = trim($_GET['country'] ?? '');
    $page    = max(1, intval($_GET['page'] ?? 1));
    $limit   = 15;
    $offset  = ($page - 1) * $limit;

    $conditions = ['1=1'];
    $params     = [];

    if ($search !== '') {
        $conditions[] = "(username ILIKE :search OR email ILIKE :search OR full_name ILIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if ($rolFilt !== '') {
        $conditions[] = "rol = :rol";
        $params[':rol'] = $rolFilt;
    }

    if ($cntFilt !== '') {
        $conditions[] = "country ILIKE :country";
        $params[':country'] = '%' . $cntFilt . '%';
    }

    $where = implode(' AND ', $conditions);

    try {
        global $db;

        $sql = "SELECT id, username, email, full_name, birthdate, gender, city, country, rol, created_at, profile_image
                FROM users
                WHERE $where
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*) FROM users WHERE $where";

        $stmt = $db->prepare($sql);
        $stmt2 = $db->prepare($sql_count);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
            $stmt2->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $stmt2->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int)$stmt2->fetchColumn();

        Response::success(['users' => $users, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    } catch (PDOException $e) {
        Response::error('Error al obtener usuarios: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// updateUser — actualizar datos del usuario
// ─────────────────────────────────────────────────────────────
function updateUser(): void
{
    requireAdmin();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data || !isset($data['id'])) {
        Response::error('Datos inválidos.', 400);
        return;
    }

    $id = intval($data['id']);
    
    // Evitar que un admin se quite el rol a sí mismo (opcional pero recomendado)
    if ($id === (int)($_SESSION['user_id'] ?? 0) && isset($data['rol']) && $data['rol'] !== 'admin') {
        Response::error('No puedes quitarte el rol de administrador a ti mismo.', 400);
        return;
    }

    try {
        global $db;

        $sql = "UPDATE users SET 
                    username = :username,
                    email = :email,
                    full_name = :full_name,
                    birthdate = :birthdate,
                    gender = :gender,
                    city = :city,
                    country = :country,
                    rol = :rol,
                    profile_image = COALESCE(:profile_image, profile_image)
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':username'      => trim($data['username']),
            ':email'         => trim($data['email']),
            ':full_name'     => trim($data['full_name'] ?? ''),
            ':birthdate'     => !empty($data['birthdate']) ? $data['birthdate'] : null,
            ':gender'        => $data['gender'] ?? null,
            ':city'          => trim($data['city'] ?? ''),
            ':country'       => trim($data['country'] ?? ''),
            ':rol'           => $data['rol'] ?? 'user',
            ':profile_image' => $data['profile_image'] ?? null,
            ':id'            => $id
        ]);

        Response::success(['message' => 'Usuario actualizado correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al actualizar usuario: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// deleteUser — eliminar usuario
// ─────────────────────────────────────────────────────────────
function deleteUser(): void
{
    requireAdmin();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $id   = intval($data['id'] ?? 0);

    if ($id <= 0) {
        Response::error('ID de usuario inválido.');
        return;
    }

    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        Response::error('No puedes eliminar tu propia cuenta desde el panel de administración.');
        return;
    }

    try {
        global $db;
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        Response::success(['message' => 'Usuario eliminado correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al eliminar usuario: ' . $e->getMessage());
    }
}
