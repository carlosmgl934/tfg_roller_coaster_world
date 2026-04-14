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
$router->register('delete_avatar', 'deleteAvatar', 'POST');
$router->register('update_avatar', 'updateAvatar', 'POST');
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

        // Validar si el usuario existe para evitar errores
        $stmtFetch = $db->prepare("SELECT id FROM users WHERE id = :id");
        $stmtFetch->execute([':id' => $id]);
        if (!$stmtFetch->fetch()) {
            Response::error('Usuario no encontrado.', 404);
            return;
        }

        // Actualizar la base de datos local (SIN cambiar el email)
        $sql = "UPDATE users SET 
                    username = :username,
                    rol      = :rol
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':username' => trim($data['username']),
            ':rol'      => $data['rol'] ?? 'user',
            ':id'       => $id
        ]);

        Response::success(['message' => 'Usuario actualizado correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al actualizar usuario: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// deleteUser — eliminar usuario (BD + Supabase avatar)
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

        // 1. Obtener datos del usuario antes de borrar (avatar + firebase_uid)
        $stmtFetch = $db->prepare("SELECT profile_image, firebase_uid FROM users WHERE id = :id");
        $stmtFetch->execute([':id' => $id]);
        $user = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        $firebaseUid  = $user['firebase_uid']  ?? null;
        $profileImage = $user['profile_image'] ?? null;

        // 2. Borrar avatar de Supabase Storage (si existe y es una URL de Supabase)
        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
        $supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;
        $supabaseError = null;

        if ($profileImage && $supabaseUrl && $supabaseKey) {
            // Extraer el nombre de archivo del avatar (guardado como solo nombre de archivo o URL completa)
            $filename = null;
            if (str_starts_with($profileImage, 'http')) {
                // URL completa → extraer la parte tras /avatars/
                if (preg_match('#/avatars/(.+)$#', $profileImage, $matches)) {
                    $filename = $matches[1];
                }
            } elseif (!str_starts_with($profileImage, '/')) {
                // Solo nombre de archivo
                $filename = $profileImage;
            }

            if ($filename) {
                // La API de Supabase Storage para borrar usa POST /object/{bucket} con {"prefixes":[...]}
                $deleteUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/avatars";
                $bodyJson  = json_encode(['prefixes' => [$filename]]);
                $ch = curl_init($deleteUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST  => 'DELETE',
                    CURLOPT_POSTFIELDS     => $bodyJson,
                    CURLOPT_HTTPHEADER     => [
                        "Authorization: Bearer {$supabaseKey}",
                        "Content-Type: application/json",
                    ],
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpCode < 200 || $httpCode >= 300) {
                    $supabaseError = "Avatar no eliminado de Supabase (HTTP {$httpCode})";
                }
            }
        }

        // 3. Borrar de la base de datos
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // 4. Borrar de Firebase Auth mediante la Service Account
        $firebaseWarn = null;
        if ($firebaseUid) {
            require_once __DIR__ . '/../utils/FirebaseAuthAdmin.php';
            $firebaseAdmin = new FirebaseAuthAdmin();
            $firebaseRes = $firebaseAdmin->deleteUser($firebaseUid);
            if (!$firebaseRes['success']) {
                $firebaseWarn = "Firebase Auth no eliminado: " . $firebaseRes['error'];
            }
        }

        // 5. Preparar advertencias combinadas
        $warnings = array_filter([$supabaseError, $firebaseWarn]);
        $warnText = !empty($warnings) ? implode(' | ', $warnings) : null;

        Response::success([
            'message'       => 'Usuario eliminado correctamente.',
            // Ya no es necesario que el front maneje a Firebase porque lo hemos hecho nosotros
            'supabase_warn' => $warnText,
        ]);

    } catch (PDOException $e) {
        Response::error('Error al eliminar usuario: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// deleteAvatar — eliminar avatar de usuario (BD + Supabase)
// ─────────────────────────────────────────────────────────────
function deleteAvatar(): void
{
    requireAdmin();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $id   = intval($data['id'] ?? 0);

    if ($id <= 0) {
        Response::error('ID de usuario inválido.');
        return;
    }

    try {
        global $db;

        // 1. Obtener profile_image
        $stmtFetch = $db->prepare("SELECT profile_image FROM users WHERE id = :id");
        $stmtFetch->execute([':id' => $id]);
        $user = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        $profileImage = $user['profile_image'] ?? null;

        if (!$profileImage) {
            Response::success(['message' => 'El usuario no tiene foto de perfil.']);
            return;
        }

        // 2. Borrar de Supabase Storage
        $supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
        $supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;
        $supabaseError = null;

        if ($supabaseUrl && $supabaseKey) {
            $filename = null;
            if (str_starts_with($profileImage, 'http')) {
                if (preg_match('#/avatars/(.+)$#', $profileImage, $matches)) {
                    $filename = $matches[1];
                }
            } elseif (!str_starts_with($profileImage, '/')) {
                $filename = $profileImage;
            }

            if ($filename) {
                // La API de Supabase Storage para borrar usa DELETE /object/{bucket} con {"prefixes":[...]}
                $deleteUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/avatars";
                $bodyJson  = json_encode(['prefixes' => [$filename]]);
                $ch = curl_init($deleteUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST  => 'DELETE',
                    CURLOPT_POSTFIELDS     => $bodyJson,
                    CURLOPT_HTTPHEADER     => [
                        "Authorization: Bearer {$supabaseKey}",
                        "Content-Type: application/json",
                    ],
                ]);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if ($httpCode < 200 || $httpCode >= 300) {
                    $supabaseError = "Avatar no eliminado de Supabase (HTTP {$httpCode})";
                }
            }
        }

        // 3. Quitar la referencia en la BD
        $stmt = $db->prepare("UPDATE users SET profile_image = NULL WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$id) {
            unset($_SESSION['profile_image']);
        }

        if ($supabaseError) {
            Response::success([
                'message' => 'Referencia de la foto eliminada, pero hubo un error en Supabase.',
                'supabase_warn' => $supabaseError
            ]);
        } else {
            Response::success(['message' => 'Foto de perfil eliminada correctamente.']);
        }

    } catch (PDOException $e) {
        Response::error('Error al eliminar foto de perfil: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// updateAvatar — actualiza el avatar de un usuario (recibe URL desde frontend tras usar upload.php)
// ─────────────────────────────────────────────────────────────
function updateAvatar(): void
{
    requireAdmin();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $id        = intval($data['id'] ?? 0);
    $photo_url = trim($data['photo_url'] ?? '');

    if ($id <= 0) {
        Response::error('ID de usuario inválido.');
        return;
    }

    if (!$photo_url) {
        Response::error('No se proporcionó la URL de la foto.');
        return;
    }

    try {
        global $db;

        // Validar si el usuario existe para evitar errores
        $stmtFetch = $db->prepare("SELECT id FROM users WHERE id = :id");
        $stmtFetch->execute([':id' => $id]);
        if (!$stmtFetch->fetch()) {
            Response::error('Usuario no encontrado.', 404);
            return;
        }

        $stmt = $db->prepare("UPDATE users SET profile_image = :img WHERE id = :id");
        $stmt->execute([
            ':img' => $photo_url,
            ':id'  => $id
        ]);

        if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$id) {
            $_SESSION['profile_image'] = $photo_url;
        }

        Response::success(['message' => 'Foto de perfil actualizada correctamente.']);

    } catch (PDOException $e) {
        Response::error('Error al actualizar foto de perfil: ' . $e->getMessage());
    }
}
