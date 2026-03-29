<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../../utils/ImageHelper.php';

header('Content-Type: application/json');

$db = new DBConexion();

$router = new ApiRouter();
$router->register('filterNews', 'filterNews', 'GET');
$router->register('addNews',    'addNews',    'POST');
$router->register('updateNews', 'updateNews', 'POST');
$router->register('deleteNews', 'deleteNews', 'POST');
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
// filterNews — lista noticias con filtros y búsqueda
// ─────────────────────────────────────────────────────────────
function filterNews(): void
{
    requireAdmin();

    $search     = trim($_GET['search'] ?? '');
    $tag        = trim($_GET['tag'] ?? '');
    $featured   = filter_var($_GET['featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $page       = max(1, intval($_GET['page'] ?? 1));
    $limit      = 15;
    $offset     = ($page - 1) * $limit;

    $conditions = ['1=1'];
    $params     = [];

    if ($search !== '') {
        $conditions[] = "n.title ILIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    if ($tag !== '') {
        $conditions[] = "n.tag = :tag";
        $params[':tag'] = $tag;
    }

    if ($featured) {
        $conditions[] = "n.is_featured = true";
    }

    $where = implode(' AND ', $conditions);

    try {
        global $db;

        $sql = "SELECT id, title, description, image_url, tag, external_link, is_featured, created_at 
                FROM news n
                WHERE $where
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*) FROM news n WHERE $where";

        $stmt  = $db->prepare($sql);
        $stmt2 = $db->prepare($sql_count);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
            $stmt2->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $stmt2->execute();

        $news  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int)$stmt2->fetchColumn();

        Response::success(['news' => $news, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error('Error buscando noticias: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// addNews
// ─────────────────────────────────────────────────────────────
function addNews(): void
{
    requireAdmin();

    $data = $_POST;
    if (!$data) {
        Response::error('Datos inválidos.', 400);
        return;
    }

    $title       = trim($data['title'] ?? '');
    $tag         = trim($data['tag'] ?? '');
    $ext_link    = trim($data['external_link'] ?? '');
    $desc        = trim($data['description'] ?? '');
    $is_featured = filter_var($data['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);

    $imagenUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../web/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid('news_') . '-' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.webp';
        $optimized = ImageHelper::optimizeAndConvertToWebP($_FILES['image']['tmp_name'], 1920, 80);
        if ($optimized && rename($optimized, $uploadDir . $fileName)) {
            $imagenUrl = '/web/img/' . $fileName;
        } else {
            $fileNameFallback = uniqid('news_') . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileNameFallback)) {
                $imagenUrl = '/web/img/' . $fileNameFallback;
            }
        }
    }

    if ($title === '') {
        Response::error('El título es obligatorio.', 400);
        return;
    }
    if ($desc === '') {
        Response::error('La descripción es obligatoria.', 400);
        return;
    }

    try {
        global $db;
        
        $sql = "INSERT INTO news (title, tag, external_link, description, image_url, is_featured) 
                VALUES (:title, :tag, :ext_link, :desc, :image, :is_featured) RETURNING id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':tag', $tag);
        $stmt->bindValue(':ext_link', $ext_link);
        $stmt->bindValue(':desc', $desc);
        $stmt->bindValue(':image', $imagenUrl);
        $stmt->bindValue(':is_featured', $is_featured, PDO::PARAM_BOOL);
        
        $stmt->execute();
        $newId = (int)$stmt->fetchColumn();

        Response::success(['id' => $newId, 'message' => 'Noticia añadida correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al añadir noticia: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// updateNews
// ─────────────────────────────────────────────────────────────
function updateNews(): void
{
    requireAdmin();

    $data = $_POST;
    if (!$data || empty($data['id'])) {
        Response::error('Datos inválidos.', 400);
        return;
    }

    $id          = intval($data['id']);
    $title       = trim($data['title'] ?? '');
    $tag         = trim($data['tag'] ?? '');
    $ext_link    = trim($data['external_link'] ?? '');
    $desc        = trim($data['description'] ?? '');
    $is_featured = filter_var($data['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // Imagen previa si no se sube una nueva
    $imagenUrl = $data['image_url'] ?? null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../web/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid('news_') . '-' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.webp';
        $optimized = ImageHelper::optimizeAndConvertToWebP($_FILES['image']['tmp_name'], 1920, 80);
        if ($optimized && rename($optimized, $uploadDir . $fileName)) {
            $imagenUrl = '/web/img/' . $fileName;
        } else {
            $fileNameFallback = uniqid('news_') . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileNameFallback)) {
                $imagenUrl = '/web/img/' . $fileNameFallback;
            }
        }
    }

    if ($title === '') {
        Response::error('El título es obligatorio.', 400);
        return;
    }
    if ($desc === '') {
        Response::error('La descripción es obligatoria.', 400);
        return;
    }

    try {
        global $db;
        
        $sql = "UPDATE news 
                SET title = :title, 
                    tag = :tag, 
                    external_link = :ext_link, 
                    description = :desc, 
                    image_url = :image, 
                    is_featured = :is_featured 
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':tag', $tag);
        $stmt->bindValue(':ext_link', $ext_link);
        $stmt->bindValue(':desc', $desc);
        $stmt->bindValue(':image', $imagenUrl);
        $stmt->bindValue(':is_featured', $is_featured, PDO::PARAM_BOOL);
        
        $stmt->execute();

        Response::success(['message' => 'Noticia actualizada correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al actualizar noticia: ' . $e->getMessage());
    }
}


// ─────────────────────────────────────────────────────────────
// deleteNews
// ─────────────────────────────────────────────────────────────
function deleteNews(): void
{
    requireAdmin();

    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        Response::error('ID inválido.', 400);
        return;
    }

    try {
        global $db;
        $stmt = $db->prepare("DELETE FROM news WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            Response::success(['message' => 'Noticia eliminada.']);
        } else {
            Response::error('La noticia no existe.', 404);
        }
    } catch (PDOException $e) {
        Response::error('Error al eliminar: ' . $e->getMessage());
    }
}
