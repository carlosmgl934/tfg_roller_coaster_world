<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/../utils/ApiRouter.php';

$router = new ApiRouter();
$router->register('getPendingPhotos', 'getPendingPhotos', 'GET');
$router->register('approvePhoto', 'approvePhoto', 'POST');
$router->register('rejectPhoto', 'rejectPhoto', 'POST');
$router->register('clearCaption', 'clearCaption', 'POST');

$router->dispatch();

function getPendingPhotos()
{
    if (!isset($_SESSION['firebase_uid']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
        Response::error("Acceso denegado.", 403);
        return;
    }
    global $db;
    $stmt = $db->prepare("SELECT coaster_photos.id, coaster_photos.photo_url as url,
    coaster_photos.caption, users.username, coasters.coaster_name
                          FROM coaster_photos
                          JOIN users ON coaster_photos.user_id = users.id
                          JOIN coasters ON coaster_photos.coaster_id = coasters.id
                          WHERE coaster_photos.status = 'pending'
                          ORDER BY coaster_photos.created_at ASC");
    $stmt->execute();
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    Response::success(['photos' => $photos]);
}

function approvePhoto()
{
    if (!isset($_SESSION['firebase_uid']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
        Response::error("Acceso denegado.", 403);
        return;
    }
    $id = intval($_GET['id'] ?? 0);
    if (!$id)
        return Response::error("ID inválido");

    global $db;
    $stmt = $db->prepare("UPDATE coaster_photos SET status = 'approved' WHERE id = :id");
    $stmt->execute(['id' => $id]);
    Response::success(['message' => 'Foto aprobada']);
}

function rejectPhoto()
{
    if (!isset($_SESSION['firebase_uid']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
        Response::error("Acceso denegado.", 403);
        return;
    }
    $id = intval($_GET['id'] ?? 0);
    if (!$id) return Response::error("ID inválido");

    global $db;

    // 1. Obtener la URL de la foto antes de borrar
    $stmtFetch = $db->prepare("SELECT photo_url FROM coaster_photos WHERE id = :id");
    $stmtFetch->execute([':id' => $id]);
    $row = $stmtFetch->fetch(PDO::FETCH_ASSOC);
    $photoUrl = $row['photo_url'] ?? null;

    // 2. Borrar de Supabase Storage si la URL apunta al bucket
    if ($photoUrl) {
        $supabaseUrl = $_ENV['SUPABASE_URL']         ?? null;
        $supabaseKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;

        if ($supabaseUrl && $supabaseKey) {
            // Extraer "{bucket}/{object_path}" desde la URL pública
            // Formato: https://xxx.supabase.co/storage/v1/object/public/{bucket}/{path}
            $marker = '/storage/v1/object/public/';
            $pos = strpos($photoUrl, $marker);
            if ($pos !== false) {
                $objectWithBucket = substr($photoUrl, $pos + strlen($marker));
                $deleteUrl = rtrim($supabaseUrl, '/') . '/storage/v1/object/' . $objectWithBucket;

                $ch = curl_init($deleteUrl);
                curl_setopt_array($ch, [
                    CURLOPT_CUSTOMREQUEST  => 'DELETE',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER     => [
                        "Authorization: Bearer {$supabaseKey}",
                        'Content-Type: application/json',
                    ],
                ]);
                curl_exec($ch);
            }
        }
    }

    // 3. Eliminar el registro de la BD
    $stmt = $db->prepare("DELETE FROM coaster_photos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    Response::success(['message' => 'Foto rechazada y eliminada']);
}

function clearCaption()
{
    if (!isset($_SESSION['firebase_uid']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
        Response::error("Acceso denegado.", 403);
        return;
    }
    $id = intval($_GET['id'] ?? 0);
    if (!$id)
        return Response::error("ID inválido");

    global $db;
    $stmt = $db->prepare("UPDATE coaster_photos SET caption = NULL WHERE id = :id");
    $stmt->execute(['id' => $id]);
    Response::success(['message' => 'Descripción eliminada']);
}