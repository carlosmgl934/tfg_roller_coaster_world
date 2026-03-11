<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
$postActions = ['save_profile', 'update_avatar'];

$action = in_array($_GET['action'] ?? '', $postActions)
    ? ($_GET['action'] ?? '')
    : ($_GET['action'] ?? '');

$actions = [
    'search' => 'searchParks',
    'save_profile' => 'saveProfile',
    'update_avatar' => 'updateAvatar',
    'get_profile' => 'getProfile',
];

if (!array_key_exists($action, $actions)) {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    exit;
}

call_user_func($actions[$action]);

// ── Búsqueda de Parques para el Home Park ─────────────────────────────────────
function searchParks()
{
    global $db;
    $search = $_GET['search'] ?? '';

    if (strlen($search) < 3) {
        echo json_encode([]);
        return;
    }

    try {
        $stmt = $db->prepare("SELECT id AS park_id, park_name FROM parks WHERE park_name ILIKE :search LIMIT 10");
        $stmt->execute([':search' => '%' . $search . '%']);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
}

// ── Guardar Configuración de Perfil ───────────────────────────────────────────
function saveProfile()
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No estás autenticado']);
        exit;
    }

    $user_id = $_SESSION['user_id'];

    // Recoger datos
    $fullName = $_POST['fullName'] ?? null;
    $username = $_POST['username'] ?? null;
    $email = $_POST['email'] ?? null;
    $birthdate = strlen($_POST['birthday'] ?? '') > 0 ? $_POST['birthday'] : null;
    $gender = $_POST['gender'] ?? null;
    $city = $_POST['city'] ?? null;
    $country = $_POST['country'] ?? null;
    $topCoaster = strlen($_POST['topCoaster'] ?? '') > 0 ? $_POST['topCoaster'] : null;
    $homePark = strlen($_POST['homePark'] ?? '') > 0 ? $_POST['homePark'] : null;

    if (!$username || !$email) {
        echo json_encode(['success' => false, 'error' => 'Usuario y Email son obligatorios']);
        exit;
    }

    global $db;
    $stmt = $db->prepare("
        UPDATE users SET
            full_name = :full_name,
            username = :username,
            email = :email,
            birthdate = :birthdate,
            gender = :gender,
            city = :city,
            country = :country,
            favorite_coaster = :favorite_coaster,
            home_park = :home_park
        WHERE id = :id
    ");

    try {
        $stmt->execute([
            ':full_name' => $fullName,
            ':username' => $username,
            ':email' => $email,
            ':birthdate' => $birthdate,
            ':gender' => $gender,
            ':city' => $city,
            ':country' => $country,
            ':favorite_coaster' => $topCoaster,
            ':home_park' => $homePark,
            ':id' => $user_id
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Manejar duplicados de username/email (postgres error code 23505)
        if ($e->getCode() == 23505) {
            echo json_encode(['success' => false, 'error' => 'El nombre de usuario o email ya están en uso']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al guardar los datos: ' . $e->getMessage()]);
        }
    }
}

function getProfile()
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No estás logueado']);
        exit;
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
        $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró el usuario']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al obtener los datos: ' . $e->getMessage()]);
    }
    exit;
}

function updateAvatar()
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No estás logueado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $photoUrl = $input['photo_url'] ?? '';

    if (empty($photoUrl)) {
        echo json_encode(['success' => false, 'error' => 'URL no válida']);
        exit;
    }

    global $db;
    try {
        $stmt = $db->prepare("
        UPDATE users SET profile_image = :photoUrl WHERE id = :id");
        $stmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':photoUrl', $photoUrl, PDO::PARAM_STR);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el avatar: ' . $e->getMessage()]);
    }
    exit;
}