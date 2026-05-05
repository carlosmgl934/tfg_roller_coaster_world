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
$router->register('addNews', 'addNews', 'POST');
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
// uploadNewsImage — convierte a WebP y sube a Supabase Storage (bucket: news-covers)
// Devuelve la URL pública o null si falla.
// ─────────────────────────────────────────────────────────────
function uploadNewsImage(array $fileEntry): ?string
{
    $supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
    $supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;

    if (!$supabaseUrl || !$supabaseKey) {
        error_log('uploadNewsImage: SUPABASE_URL o SUPABASE_SERVICE_KEY no configurados.');
        return null;
    }

    // Convertir a WebP optimizado (se guarda junto al tmp original)
    $webpPath = ImageHelper::optimizeAndConvertToWebP($fileEntry['tmp_name'], 1920, 82);
    $readPath = $webpPath ?: $fileEntry['tmp_name'];

    $fileData = file_get_contents($readPath);
    if ($fileData === false)
        return null;

    $fileName = uniqid('news_') . '.webp';
    $bucket = 'news-covers';
    $uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucket}/{$fileName}";

    $ch = curl_init($uploadUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => $fileData,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$supabaseKey}",
            'Content-Type: image/webp',
            'x-upsert: true',
        ],
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Limpiar WebP temporal
    if ($webpPath && file_exists($webpPath))
        @unlink($webpPath);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log("uploadNewsImage: Error subiendo a Supabase. HTTP {$httpCode}");
        return null;
    }

    return rtrim($supabaseUrl, '/') . "/storage/v1/object/public/{$bucket}/{$fileName}";
}

// Elimina una imagen de Supabase Storage (solo si la URL apunta al bucket news-covers)
function deleteNewsImage(?string $imageUrl): void
{
    if (!$imageUrl || !str_contains($imageUrl, '/news-covers/'))
        return;

    $supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
    $supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;
    if (!$supabaseUrl || !$supabaseKey)
        return;

    // Extraer el nombre de archivo del final de la URL
    $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
    if (!$filename)
        return;

    $deleteUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/news-covers/{$filename}";
    $ch = curl_init($deleteUrl);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$supabaseKey}",
        ],
    ]);
    curl_exec($ch);
}

// ─────────────────────────────────────────────────────────────
// filterNews — lista noticias con filtros y búsqueda
// ─────────────────────────────────────────────────────────────
function filterNews(): void
{
    requireAdmin();

    $search = trim($_GET['search'] ?? '');
    $tag = trim($_GET['tag'] ?? '');
    $featured = filter_var($_GET['featured'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $conditions = ['1=1'];
    $params = [];

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

        $stmt = $db->prepare($sql);
        $stmt2 = $db->prepare($sql_count);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
            $stmt2->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $stmt2->execute();

        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int) $stmt2->fetchColumn();

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

    $title = trim($data['title'] ?? '');
    $tag = trim($data['tag'] ?? '');
    $ext_link = trim($data['external_link'] ?? '');
    $desc = trim($data['description'] ?? '');
    $is_featured = filter_var($data['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($title === '') {
        Response::error('El título es obligatorio.', 400);
        return;
    }
    if ($desc === '') {
        Response::error('La descripción es obligatoria.', 400);
        return;
    }

    $imagenUrl = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imagenUrl = uploadNewsImage($_FILES['image']);
        if (!$imagenUrl) {
            Response::error('Error al subir la imagen a Supabase. Comprueba el bucket news-covers.', 500);
            return;
        }
    }

    try {
        global $db;

        if ($is_featured) {
            $db->prepare("UPDATE news SET is_featured = false")->execute();
        }

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
        $newId = (int) $stmt->fetchColumn();

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

    $id = intval($data['id']);
    $title = trim($data['title'] ?? '');
    $tag = trim($data['tag'] ?? '');
    $ext_link = trim($data['external_link'] ?? '');
    $desc = trim($data['description'] ?? '');
    $is_featured = filter_var($data['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($title === '') {
        Response::error('El título es obligatorio.', 400);
        return;
    }
    if ($desc === '') {
        Response::error('La descripción es obligatoria.', 400);
        return;
    }

    // Imagen: si viene nueva, subir a Supabase; si no, conservar la actual
    $imagenUrl = $data['image_url'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $newUrl = uploadNewsImage($_FILES['image']);
        if (!$newUrl) {
            Response::error('Error al subir la imagen a Supabase. Comprueba el bucket news-covers.', 500);
            return;
        }
        // Borrar imagen anterior de Supabase si la hay
        deleteNewsImage($imagenUrl);
        $imagenUrl = $newUrl;
    }

    try {
        global $db;

        if ($is_featured) {
            $stmtReset = $db->prepare("UPDATE news SET is_featured = false WHERE id != :id");
            $stmtReset->execute([':id' => $id]);
        }

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
        // Obtener la imagen antes de borrar para eliminarla de Supabase
        $stmtImg = $db->prepare("SELECT image_url FROM news WHERE id = :id");
        $stmtImg->execute([':id' => $id]);
        $row = $stmtImg->fetch(PDO::FETCH_ASSOC);
        deleteNewsImage($row['image_url'] ?? null);

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
