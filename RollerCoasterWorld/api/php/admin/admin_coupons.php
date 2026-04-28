<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/ApiRouter.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    Response::error('No autorizado', 403);
}

$db = null;
function getDb() {
    global $db;
    if ($db === null) $db = new DBConexion();
    return $db;
}

$router = new ApiRouter('list');
$router->register('list',   'listCoupons');
$router->register('create', 'createCoupon', 'POST');
$router->register('toggle', 'toggleCoupon', 'POST');
$router->register('delete', 'deleteCoupon', 'POST');
$router->dispatch();

function listCoupons() {
    $db = getDb();
    try {
        $stmt = $db->query("SELECT * FROM coupons ORDER BY created_at DESC");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['data' => $coupons]);
    } catch (Exception $e) {
        Response::error('Error al obtener cupones: ' . $e->getMessage(), 500);
    }
}

function createCoupon() {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $value = (float)($_POST['value'] ?? 0);
    $maxUses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $expires = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $active = isset($_POST['active']) && $_POST['active'] === 'true' ? 'true' : 'false';

    if (empty($code) || $value <= 0) {
        Response::error('Datos inválidos');
    }
    
    // Validar solo letras y números
    if (!preg_match('/^[A-Z0-9]+$/', $code)) {
        Response::error('El código solo puede contener letras y números');
    }

    $db = getDb();
    try {
        $stmt = $db->prepare("
            INSERT INTO coupons (code, description, discount_type, discount_value, max_uses, expires_at, active)
            VALUES (:code, :desc, 'percent', :val, :max_uses, :expires, :active)
        ");
        $stmt->execute([
            ':code' => $code,
            ':desc' => $desc,
            ':val' => $value,
            ':max_uses' => $maxUses,
            ':expires' => $expires,
            ':active' => $active
        ]);
        Response::success(['message' => 'Cupón creado']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23505) { // Unique violation Postgres
            Response::error('El código de cupón ya existe');
        }
        Response::error('Error: ' . $e->getMessage(), 500);
    }
}

function toggleCoupon() {
    $id = (int)($_POST['id'] ?? 0);
    $active = isset($_POST['active']) && $_POST['active'] === 'true' ? 'true' : 'false';
    if ($id <= 0) Response::error('ID inválido');

    $db = getDb();
    try {
        $stmt = $db->prepare("UPDATE coupons SET active = :active WHERE id = :id");
        $stmt->execute([':active' => $active, ':id' => $id]);
        Response::success(['message' => 'Estado actualizado']);
    } catch (Exception $e) {
        Response::error('Error: ' . $e->getMessage(), 500);
    }
}

function deleteCoupon() {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) Response::error('ID inválido');

    $db = getDb();
    try {
        $stmt = $db->prepare("DELETE FROM coupons WHERE id = :id");
        $stmt->execute([':id' => $id]);
        Response::success(['message' => 'Cupón eliminado']);
    } catch (Exception $e) {
        Response::error('Error: ' . $e->getMessage(), 500);
    }
}
