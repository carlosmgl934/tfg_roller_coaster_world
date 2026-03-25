<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

header('Content-Type: application/json');

$db = new DBConexion();

$router = new ApiRouter();
$router->register('searchParks',     'searchParks',     'GET');
$router->register('filterParks',     'filterParks',     'GET');
$router->register('unknownCoasters', 'unknownCoasters', 'GET');
$router->register('addPark',         'addPark',         'POST');
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
// searchParks — búsqueda por nombre, país o localización
// ─────────────────────────────────────────────────────────────
function searchParks(): void
{
    requireAdmin();

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        Response::success(['parks' => [], 'total' => 0]);
        return;
    }

    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 15;
    $offset = ($page - 1) * $limit;
    $like   = '%' . $search . '%';

    try {
        global $db;

        $sql = "SELECT
                    p.id,
                    p.park_name,
                    p.park_country,
                    p.park_location,
                    p.opening_year,
                    p.operating_coasters,
                    p.num_coasters
                FROM parks p
                WHERE
                    p.park_name     ILIKE :like
                    OR p.park_country  ILIKE :like
                    OR p.park_location ILIKE :like
                ORDER BY p.park_name ASC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*) FROM parks p
                      WHERE
                          p.park_name     ILIKE :like
                          OR p.park_country  ILIKE :like
                          OR p.park_location ILIKE :like";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':like',   $like,   PDO::PARAM_STR);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $db->prepare($sql_count);
        $stmt2->bindValue(':like', $like, PDO::PARAM_STR);
        $stmt2->execute();
        $total = (int)$stmt2->fetchColumn();

        Response::success(['parks' => $parks, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error('Error buscando parques: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// filterParks — filtros del sidebar (país, año)
// ─────────────────────────────────────────────────────────────
function filterParks(): void
{
    requireAdmin();

    $conditions = ['1=1'];
    $params     = [];

    // País
    if (isset($_GET['country']) && $_GET['country'] !== '') {
        $conditions[] = "p.park_country = :country";
        $params[':country'] = $_GET['country'];
    }

    // Año exacto
    if (!empty($_GET['year'])) {
        $conditions[] = "p.opening_year = :year";
        $params[':year'] = intval($_GET['year']);
    }

    $where  = implode(' AND ', $conditions);
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 15;
    $offset = ($page - 1) * $limit;

    try {
        global $db;

        $sql = "SELECT
                    p.id,
                    p.park_name,
                    p.park_country,
                    p.park_location,
                    p.opening_year,
                    p.operating_coasters,
                    p.num_coasters
                FROM parks p
                WHERE $where
                ORDER BY p.park_name ASC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*) FROM parks p WHERE $where";

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

        $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int)$stmt2->fetchColumn();

        Response::success(['parks' => $parks, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error('Error filtrando parques: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// unknownCoasters — coasters cuyo parque es "Desconocido" (id=2895)
// ─────────────────────────────────────────────────────────────
function unknownCoasters(): void
{
    requireAdmin();

    $q     = trim($_GET['q'] ?? '');
    $limit = min(1000, max(1, intval($_GET['limit'] ?? 1000)));

    $where = "c.park_id = 2895";
    $bind  = [];

    if ($q !== '') {
        $where .= " AND c.coaster_name ILIKE :q";
        $bind[':q'] = '%' . $q . '%';
    }

    try {
        global $db;

        $sql = "SELECT c.id, c.coaster_name, c.coaster_status
                FROM coasters c
                WHERE $where
                ORDER BY c.coaster_name ASC
                LIMIT 300";

        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['coasters' => $coasters]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo coasters: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// addPark — insertar parque y reasignar coasters seleccionadas
// ─────────────────────────────────────────────────────────────
function addPark(): void
{
    requireAdmin();

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data) {
        Response::error('Datos inválidos.', 400);
        return;
    }

    $name     = trim($data['name']     ?? '');
    $country  = trim($data['country']  ?? '');
    $location = trim($data['location'] ?? '');
    $year     = isset($data['year']) && $data['year'] !== '' ? intval($data['year']) : null;
    // Array de IDs de coasters a reasignar (puede estar vacío)
    $coasterIds = array_filter(array_map('intval', $data['coasterIds'] ?? []), fn($id) => $id > 0);

    if ($name === '') {
        Response::error('El nombre del parque es obligatorio.', 400);
        return;
    }
    if ($country === '') {
        Response::error('El país es obligatorio.', 400);
        return;
    }
    if ($location === '') {
        Response::error('La localización es obligatoria.', 400);
        return;
    }

    try {
        global $db;

        // Calcular totales desde las coasters seleccionadas
        $totalCoasters    = count($coasterIds);
        $operatingCoasters = 0;

        if ($totalCoasters > 0) {
            $placeholders = implode(',', array_fill(0, $totalCoasters, '?'));
            $stmtOp = $db->prepare("SELECT COUNT(*) FROM coasters WHERE id IN ($placeholders) AND coaster_status = 'Operating'");
            $stmtOp->execute(array_values($coasterIds));
            $operatingCoasters = (int)$stmtOp->fetchColumn();
        }

        // Insertar el parque
        $stmtInsert = $db->prepare("
            INSERT INTO parks (park_name, park_country, park_location, opening_year, num_coasters, operating_coasters)
            VALUES (:name, :country, :location, :year, :total, :operating)
            RETURNING id
        ");
        $stmtInsert->execute([
            ':name'      => $name,
            ':country'   => $country,
            ':location'  => $location,
            ':year'      => $year,
            ':total'     => $totalCoasters,
            ':operating' => $operatingCoasters,
        ]);
        $newId = (int)$stmtInsert->fetchColumn();

        // Reasignar coasters seleccionadas al nuevo parque
        if ($totalCoasters > 0) {
            $placeholders = implode(',', array_fill(0, $totalCoasters, '?'));
            $stmtUpdate = $db->prepare("UPDATE coasters SET park_id = $newId WHERE id IN ($placeholders)");
            $stmtUpdate->execute(array_values($coasterIds));
        }

        Response::success(['id' => $newId, 'message' => 'Parque añadido correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al añadir el parque: ' . $e->getMessage());
    }
}
