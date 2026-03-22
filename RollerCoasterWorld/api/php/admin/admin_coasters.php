<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';

header('Content-Type: application/json');

$db = new DBConexion();

$router = new ApiRouter();
$router->register('searchCoasters', 'searchCoasters', 'GET');
$router->register('filterCoasters', 'filterCoasters', 'GET');
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
// searchCoasters — búsqueda por nombre, fabricante o parque
// ─────────────────────────────────────────────────────────────
function searchCoasters(): void
{
    requireAdmin();

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        Response::success(['coasters' => [], 'total' => 0]);
        return;
    }

    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $like = '%' . $search . '%';

    try {
        global $db;

        $sql = "SELECT
                    c.id,
                    c.coaster_name,
                    c.coaster_manufacter,
                    c.coaster_status,
                    c.opening_year,
                    COALESCE(p.park_name, 'Desconocido') AS park_name,
                    p.park_country
                FROM coasters c
                LEFT JOIN parks p ON c.park_id = p.id
                WHERE
                    c.coaster_name       ILIKE :like
                    OR c.coaster_manufacter ILIKE :like
                    OR p.park_name          ILIKE :like
                ORDER BY c.coaster_name ASC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*) FROM coasters c
                      LEFT JOIN parks p ON c.park_id = p.id
                      WHERE
                          c.coaster_name       ILIKE :like
                          OR c.coaster_manufacter ILIKE :like
                          OR p.park_name          ILIKE :like";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':like', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $db->prepare($sql_count);
        $stmt2->bindValue(':like', $like, PDO::PARAM_STR);
        $stmt2->execute();
        $total = (int)$stmt2->fetchColumn();

        Response::success(['coasters' => $coasters, 'total' => $total]);
    }
    catch (PDOException $e) {
        Response::error('Error buscando coasters: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// filterCoasters — filtros del sidebar (fabricante, país, parque,
//                  año, altura, velocidad, solo operativas)
// ─────────────────────────────────────────────────────────────
function filterCoasters(): void
{
    requireAdmin();

    $conditions = ['1=1'];
    $params = [];

    // Solo operativas
    if (!empty($_GET['opened']) && $_GET['opened'] === 'true') {
        $conditions[] = "c.coaster_status = 'Operating'";
    }

    // Fabricante — valor especial '__null__' → IS NULL en BD
    if (isset($_GET['manufacter']) && $_GET['manufacter'] !== '') {
        if ($_GET['manufacter'] === '__null__') {
            $conditions[] = "c.coaster_manufacter IS NULL";
        }
        else {
            $conditions[] = "c.coaster_manufacter = :manufacter";
            $params[':manufacter'] = $_GET['manufacter'];
        }
    }

    // País — valor especial '__null__'
    if (isset($_GET['country']) && $_GET['country'] !== '') {
        if ($_GET['country'] === '__null__') {
            $conditions[] = "p.park_country IS NULL";
        }
        else {
            $conditions[] = "p.park_country = :country";
            $params[':country'] = $_GET['country'];
        }
    }

    // Parque — valor especial '__null__'
    if (isset($_GET['park']) && $_GET['park'] !== '') {
        if ($_GET['park'] === '__null__') {
            $conditions[] = "(c.park_id IS NULL OR p.id IS NULL OR c.park_id = 2895)";
        }
        else {
            $conditions[] = "p.park_name ILIKE :park";
            $params[':park'] = '%' . $_GET['park'] . '%';
        }
    }

    // Año exacto
    if (!empty($_GET['year'])) {
        $conditions[] = "c.opening_year = :year";
        $params[':year'] = intval($_GET['year']);
    }

    // Altura mínima
    if (!empty($_GET['height']) && intval($_GET['height']) > 0) {
        $conditions[] = "COALESCE(CAST(NULLIF(SUBSTRING(TRIM(c.height::text) FROM '^[0-9]+'), '') AS integer), 0) >= :height";
        $params[':height'] = intval($_GET['height']);
    }

    // Velocidad mínima
    if (!empty($_GET['speed']) && intval($_GET['speed']) > 0) {
        $conditions[] = "COALESCE(CAST(NULLIF(SUBSTRING(TRIM(c.speed::text) FROM '^[0-9]+'), '') AS integer), 0) >= :speed";
        $params[':speed'] = intval($_GET['speed']);
    }

    $where = implode(' AND ', $conditions);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 15;
    $offset = ($page - 1) * $limit;

    try {
        global $db;

        $sql = "SELECT
                    c.id,
                    c.coaster_name,
                    c.coaster_manufacter,
                    c.coaster_status,
                    c.opening_year,
                    COALESCE(p.park_name, 'Desconocido') AS park_name,
                    p.park_country
                FROM coasters c
                LEFT JOIN parks p ON c.park_id = p.id
                WHERE $where
                ORDER BY c.coaster_name ASC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*)
                      FROM coasters c
                      LEFT JOIN parks p ON c.park_id = p.id
                      WHERE $where";

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

        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = (int)$stmt2->fetchColumn();

        Response::success(['coasters' => $coasters, 'total' => $total]);
    }
    catch (PDOException $e) {
        Response::error('Error filtrando coasters: ' . $e->getMessage());
    }
}