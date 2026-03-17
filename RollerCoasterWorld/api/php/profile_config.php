<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/utils/ApiRouter.php';

$router = new ApiRouter();
$router->register('search', 'searchParks');
$router->register('save_profile', 'saveProfile', 'POST');
$router->register('update_avatar', 'updateAvatar', 'POST');
$router->register('get_profile', 'getProfile');

$router->dispatch();

// ── Helper: obtiene el user_id de la sesión, con fallback por firebase_uid ──────
function getUserId(): ?int
{
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }
    // Fallback: resolver por firebase_uid (sesiones anteriores al fix)
    if (isset($_SESSION['firebase_uid'])) {
        global $db;
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int) $row['id']; // guardar para la próxima vez
            return (int) $row['id'];
        }
    }
    return null;
}

// ── Búsqueda de Parques para el Home Park ─────────────────────────────────────
function searchParks()
{
    global $db;
    $search = $_GET['search'] ?? '';

    if (strlen($search) < 3) {
        echo json_encode([]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id AS park_id, park_name FROM parks WHERE park_name ILIKE :search LIMIT 10");
        $stmt->execute([':search' => '%' . $search . '%']);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }
    catch (PDOException $e) {
        echo json_encode([]);
        exit;
    }
}

// ── Guardar Configuración de Perfil ───────────────────────────────────────────
function saveProfile()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás autenticado');
    }

    // Recoger datos
    $fullName = strlen($_POST['fullName'] ?? '') > 0 ? $_POST['fullName'] : null;
    $username = strlen($_POST['username'] ?? '') > 0 ? $_POST['username'] : null;
    // El email NO se actualiza desde el formulario (campo disabled en el front)
    // Se mantiene el valor actual en BD; solo lo usamos para mostrar al usuario
    $birthdate = strlen($_POST['birthday'] ?? '') > 0 ? $_POST['birthday'] : null;
    // Gender: string vacío → null para no violar el check constraint de la BD
    $gender = strlen($_POST['gender'] ?? '') > 0 ? $_POST['gender'] : null;
    $city = strlen($_POST['city'] ?? '') > 0 ? $_POST['city'] : null;
    $country = strlen($_POST['country'] ?? '') > 0 ? $_POST['country'] : null;
    $topCoaster = strlen($_POST['topCoaster'] ?? '') > 0 ? $_POST['topCoaster'] : null;
    $homePark = strlen($_POST['homePark'] ?? '') > 0 ? $_POST['homePark'] : null;

    global $db;
    $stmt = $db->prepare("
        UPDATE users SET
            full_name        = COALESCE(:full_name, full_name),
            username         = COALESCE(:username, username),
            birthdate        = COALESCE(:birthdate, birthdate),
            gender           = COALESCE(:gender, gender),
            city             = COALESCE(:city, city),
            country          = COALESCE(:country, country),
            favorite_coaster = COALESCE(:favorite_coaster, favorite_coaster),
            home_park        = COALESCE(:home_park, home_park)
        WHERE id = :id
    ");

    try {
        $stmt->execute([
            ':full_name'        => $fullName,
            ':username'         => $username,
            ':birthdate'        => $birthdate,
            ':gender'           => $gender,
            ':city'             => $city,
            ':country'          => $country,
            ':favorite_coaster' => $topCoaster,
            ':home_park'        => $homePark,
            ':id'               => $user_id
        ]);

        Response::success();
    }
    catch (PDOException $e) {
        // Manejar duplicados de nombre (postgres error code 23505)
        if ($e->getCode() == 23505) {
            Response::error('El nombre de usuario ya está en uso');
        }
        else {
            Response::error('Error al guardar los datos: ' . $e->getMessage());
        }
    }
}

function getProfile()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }
    global $db;
    $stmt = $db->prepare("
        SELECT 
            full_name,
            username,
            email,
            birthdate,
            gender,
            city,
            country,
            favorite_coaster,
            home_park,
            profile_image
        FROM users
        WHERE id = :id
    ");
    try {
        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            Response::success(['user' => $user]);
        }
        else {
            Response::notFound('No se encontró el usuario');
        }
    }
    catch (PDOException $e) {
        Response::error('Error al obtener los datos: ' . $e->getMessage());
    }
}

function updateAvatar()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $photoUrl = $input['photo_url'] ?? '';

    if (empty($photoUrl)) {
        Response::error('URL no válida');
    }

    global $db;
    try {
        $stmt = $db->prepare("
        UPDATE users SET profile_image = :photoUrl WHERE id = :id");
        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':photoUrl', $photoUrl, PDO::PARAM_STR);
        $stmt->execute();
        Response::success();
    }
    catch (PDOException $e) {
        Response::error('Error al actualizar el avatar: ' . $e->getMessage());
    }
}