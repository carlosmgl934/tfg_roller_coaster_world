<?php

require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'search':
        searchCoasters();
        break;
    case 'list':
        listCoasters();
        break;
    case 'filter':
        filterCoasters();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        exit;
}

function searchCoasters()
{

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        echo json_encode([]);
        exit;
    }

    try {
        $db = new DBConexion();
        $sql = "SELECT 
    coasters.id, coasters.coaster_name, parks.park_name
    FROM coasters
    INNER JOIN parks ON coasters.park_id = parks.id
    WHERE coasters.coaster_name ILIKE :name 
    LIMIT 5";
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':name', "%" . trim($_GET['search']) . "%", PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($result);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error buscando montaña rusa']);
        exit;
    }
}

function listCoasters()
{
    $page = intval($_GET['page'] ?? 1);
    $limit = 15;
    $offset = ($page - 1) * $limit;

    try {
        $db = new DBConexion();
        $sql = "SELECT
        coasters.id, coasters.coaster_name, coasters.imagen_url, parks.park_name, coasters.coaster_manufacter AS manufacturer,
        coasters.coaster_model AS modelo,
        coasters.opening_year
        FROM coasters
        INNER JOIN parks ON coasters.park_id = parks.id
        LIMIT :limit OFFSET :offset";

        $sql_2 = "SELECT COUNT(*) as total FROM coasters";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt_2 = $db->prepare($sql_2);
        $stmt_2->execute();
        $total = $stmt_2->fetchColumn();

        echo json_encode([
            'success' => true,
            'coasters' => $coasters,
            'total' => $total
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error mostrando montañas rusas']);
        exit;
    }

}

function filterCoasters()
{

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        echo json_encode([]);
        exit;
    }

    $page = intval($_GET['page'] ?? 1);
    $limit = 15;
    $offset = ($page - 1) * $limit;

    try {
        $db = new DBConexion();
        $sql = "SELECT
        coasters.id, coasters.coaster_name, coasters.imagen_url, parks.park_name, coasters.coaster_manufacter AS manufacturer,
        coasters.coaster_model AS modelo,
        coasters.opening_year
        FROM coasters
        INNER JOIN parks ON coasters.park_id = parks.id
        WHERE coasters.coaster_name ILIKE :name
        LIMIT :limit OFFSET :offset";

        $sql_2 = "SELECT COUNT(*) as total FROM coasters WHERE coasters.coaster_name ILIKE :name";

        $stmt = $db->prepare($sql);
        $stmt_2 = $db->prepare($sql_2);
        $stmt->bindValue(':name', "%" . trim($_GET['search']) . "%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_2->bindValue(':name', "%" . trim($_GET['search']) . "%", PDO::PARAM_STR);

        $stmt->execute();
        $stmt_2->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $stmt_2->fetchColumn();
        echo json_encode([
            'success' => true,
            'coasters' => $result,
            'total' => $total
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error mostrando montañas rusas']);
        exit;
    }
}

