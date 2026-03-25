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
    if (!$id)
        return Response::error("ID inválido");

    global $db;
    $stmt = $db->prepare("UPDATE coaster_photos SET status = 'rejected' WHERE id = :id");
    $stmt->execute(['id' => $id]);
    Response::success(['message' => 'Foto rechazada']);
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