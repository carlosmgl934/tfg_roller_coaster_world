<?php
require_once __DIR__ . '/../utils/SessionManager.php';
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/Response.php';

header('Content-Type: application/json');

$db     = new DBConexion();
$action = $_GET['action'] ?? '';

function requireAdmin(): void
{
    if (
        !isset($_SESSION['firebase_uid']) ||
        !isset($_SESSION['user_rol'])     ||
        $_SESSION['user_rol'] !== 'admin'
    ) {
        Response::error('Acceso denegado.', 403);
        exit;
    }
}

switch ($action) {
    case 'get':    getSlides();    break;
    case 'update': updateSlide(); break;
    case 'clear':  clearSlide();  break;
    default:       Response::error('Acción inválida.', 400);
}

// ─────────────────────────────────────────────────────────────
// getSlides — público, no requiere auth
// ─────────────────────────────────────────────────────────────
function getSlides(): void
{
    global $db;
    try {
        $stmt = $db->query("SELECT position, image_url FROM carousel_slides ORDER BY position");
        Response::success(['slides' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        // Si la tabla no existe aún, devuelve vacío sin error
        Response::success(['slides' => []]);
    }
}

// ─────────────────────────────────────────────────────────────
// updateSlide — POST JSON {position, image_url}
// ─────────────────────────────────────────────────────────────
function updateSlide(): void
{
    requireAdmin();

    $data     = json_decode(file_get_contents('php://input'), true) ?? [];
    $position = intval($data['position'] ?? 0);
    $imageUrl = trim($data['image_url'] ?? '');

    if ($position < 1 || $position > 4 || $imageUrl === '') {
        Response::error('Datos inválidos.', 400);
        return;
    }

    global $db;
    $stmt = $db->prepare("
        INSERT INTO carousel_slides (position, image_url, updated_at)
        VALUES (:pos, :url, NOW())
        ON CONFLICT (position) DO UPDATE SET image_url = :url, updated_at = NOW()
    ");
    $stmt->execute([':pos' => $position, ':url' => $imageUrl]);
    Response::success(['message' => 'Slide actualizado correctamente.']);
}

// ─────────────────────────────────────────────────────────────
// clearSlide — elimina la imagen de un slot (GET ?position=N)
// ─────────────────────────────────────────────────────────────
function clearSlide(): void
{
    requireAdmin();

    $position = intval($_GET['position'] ?? 0);
    if ($position < 1 || $position > 4) {
        Response::error('Posición inválida.', 400);
        return;
    }

    global $db;
    $stmt = $db->prepare("UPDATE carousel_slides SET image_url = NULL, updated_at = NOW() WHERE position = :pos");
    $stmt->execute([':pos' => $position]);
    Response::success(['message' => 'Imagen eliminada.']);
}
