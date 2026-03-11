<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
// Acciones que requieren POST
$postActions = ['save_review', 'save_photo'];

// Leer la acción: POST para escritura, GET para lectura
$action = in_array($_GET['action'] ?? '', $postActions)
    ? ($_GET['action'] ?? '')
    : ($_GET['action'] ?? 'list');

// Mapa de acciones → funciones
$actions = [
    'search'        => 'searchCoasters',
    'list'          => 'listCoasters',
    'filter'        => 'filterCoasters',
    'manufacter'    => 'getManufacturers',
    'country'       => 'getCountries',
    'ridden'        => 'getRidden',
    'apply_filters' => 'applyFilters',
    'coaster'       => 'getCoasters',
    'photos'        => 'getCoasterPhotos',
    'reviews'       => 'getCoasterReviews',
    'save_review'   => 'saveReview',
    'save_photo'    => 'savePhoto',
];

if (!array_key_exists($action, $actions)) {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    exit;
}

call_user_func($actions[$action]);

function getManufacturers()
{
    try {
        global $db;
        $sql = "SELECT DISTINCT coaster_manufacter FROM coasters ORDER BY coaster_manufacter ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $manufacturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'manufacters' => $manufacturers]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error obteniendo fabricantes']);
        exit;
    }
}

function getCountries()
{
    try {
        global $db;
        $sql = "SELECT DISTINCT park_country FROM parks ORDER BY park_country ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'countries' => $countries]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error obteniendo países']);
        exit;
    }
}

function getRidden()
{
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autenticado']);
        exit;
    }

    try {
        global $db;
        $sql = "SELECT DISTINCT coaster_id FROM user_credits WHERE user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $ridden = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'ridden' => $ridden]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error obteniendo montañas rusas']);
        exit;
    }
}

function searchCoasters()
{

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        echo json_encode([]);
        exit;
    }

    try {
        global $db;
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
        global $db;
        $sql = "SELECT
        coasters.id, coasters.coaster_name, coasters.imagen_url, parks.park_name, coasters.coaster_manufacter AS manufacter,
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
        echo json_encode(['success' => false, 'error' => 'Error mostrando montañas rusas: ' . $e->getMessage()]);
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
        global $db;
        $sql = "SELECT
        coasters.id, coasters.coaster_name, coasters.imagen_url, parks.park_name, coasters.coaster_manufacter AS manufacter,
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

function applyFilters()
{
    $conditions = ["1=1"];
    $params = [];

    // Search por nombre
    if (!empty($_GET['search'])) {
        $conditions[] = "coasters.coaster_name ILIKE :search";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }

    // Rangos
    $rangeFilters = [
        'height' => 'coasters.height',
        'speed' => 'coasters.speed',
        'length' => 'coasters.coaster_length',
        'inversions' => 'coasters.inversions',
    ];
    foreach ($rangeFilters as $param => $column) {
        if (!empty($_GET[$param]) && intval($_GET[$param]) > 0) {
            // Extrae los primeros dígitos numéricos, los convierte a entero, y si es null o texto inválido lo trata como 0
            $conditions[] = "COALESCE(CAST(NULLIF(SUBSTRING(TRIM($column::text) FROM '^[0-9]+'), '') AS integer), 0) >= :$param";
            $params[":$param"] = intval($_GET[$param]);
        }
    }

    if (!empty($_GET['manufacter'])) {
        $conditions[] = "coasters.coaster_manufacter = :manufacter";
        $params[':manufacter'] = $_GET['manufacter'];
    }

    if (!empty($_GET['country'])) {
        $conditions[] = "parks.park_country = :country";
        $params[':country'] = $_GET['country'];
    }

    if (isset($_GET['ridden']) && $_GET['ridden'] === 'true') {
        if (isset($_SESSION['user_id'])) {
            $conditions[] = "coasters.id IN (SELECT coaster_id FROM user_credits WHERE user_id = :user_id)";
            $params[':user_id'] = $_SESSION['user_id'];
        } else {
            $conditions[] = "1 = 0"; // Sin sesión, no tiene montañas rusas montadas
        }
    }

    if (!empty($_GET['opened']) && $_GET['opened'] === 'true') {
        $conditions[] = "coasters.coaster_status = 'Operating'";
    }

    if (!empty($_GET['year'])) {
        $conditions[] = "coasters.opening_year = :year";
        $params[':year'] = intval($_GET['year']);
    }

    $page = intval($_GET['page'] ?? 1);
    $limit = 15;
    $offset = ($page - 1) * $limit;
    $where = implode(" AND ", $conditions);

    try {
        global $db;
        $sql = "SELECT coasters.id, coasters.coaster_name, coasters.imagen_url, 
                parks.park_name, coasters.coaster_manufacter AS manufacter,
                coasters.coaster_model AS modelo, coasters.opening_year
                FROM coasters
                INNER JOIN parks ON coasters.park_id = parks.id
                WHERE $where
                LIMIT :limit OFFSET :offset";

        $sql_2 = "SELECT COUNT(*) as total FROM coasters
                  INNER JOIN parks ON coasters.park_id = parks.id
                  WHERE $where";

        $stmt = $db->prepare($sql);
        $stmt_2 = $db->prepare($sql_2);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
            $stmt_2->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $stmt_2->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = $stmt_2->fetchColumn();

        echo json_encode(['success' => true, 'coasters' => $result, 'total' => $total]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

function getCoasters()
{

    $id = intval($_GET['id'] ?? 0);
    $userId = $_SESSION['user_id'] ?? 0; // Para saber si el usuario ha montado la coaster

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID no válido']);
        exit;
    }

    try {
        global $db;
        $sql = "SELECT coasters.*, parks.park_name,
        parks.park_country, parks.id AS park_id,
        (SELECT ROUND(AVG(note) * 20) FROM coaster_ratings WHERE coaster_id = coasters.id) AS score,
        (SELECT COUNT(*) + 1 FROM coasters AS c2 
            WHERE (SELECT AVG(note) FROM coaster_ratings WHERE coaster_id = c2.id) 
                > (SELECT AVG(note) FROM coaster_ratings WHERE coaster_id = coasters.id)) AS global_rank,
        (SELECT rank_position FROM user_credits WHERE coaster_id = coasters.id AND user_id = :user_id LIMIT 1) AS personal_ranking
    FROM coasters
    INNER JOIN parks ON coasters.park_id = parks.id
    WHERE coasters.id = :id";
        $stmt = $db->prepare($sql);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo json_encode([
                'success' => true,
                'coaster' => $result
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Montaña rusa no encontrada']);
        }
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
        exit;
    }
}

function getCoasterReviews()
{
    $id = intval($_GET['id'] ?? 0);
    $order = $_GET['order'] ?? 'default';

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID no válido']);
        exit;
    }

    $orderSql = match ($order) {
        'recent' => 'cr.created_at DESC',
        'best' => 'cr.note DESC',
        'worst' => 'cr.note ASC',
        default => 'cr.created_at DESC'
    };

    try {
        global $db;
        $sql = "SELECT cr.id, cr.note, cr.review, cr.created_at, users.username, users.profile_image, 
                       (SELECT json_agg(json_build_object('tag', rt.tag, 'type', rt.type))
                        FROM review_tags rt WHERE rt.review_id = cr.id) AS tags
                FROM coaster_ratings AS cr
                INNER JOIN users ON cr.user_id = users.id
                WHERE cr.coaster_id = :id
                ORDER BY $orderSql";

        $sql_count = "SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = :id";

        $stmt = $db->prepare($sql);
        $stmt_count = $db->prepare($sql_count);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt_count->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();
        $stmt_count->execute();

        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reviews as &$r) {
            $r['tags'] = $r['tags'] ? json_decode($r['tags'], true) : [];
        }
        $total = $stmt_count->fetchColumn();

        echo json_encode(['success' => true, 'reviews' => $reviews, 'total' => $total]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

function getCoasterPhotos()
{
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID no válido']);
        exit;
    }

    try {
        global $db;
        $sql = "SELECT cp.*, u.username 
                FROM coaster_photos cp
                INNER JOIN users u ON cp.user_id = u.id
                WHERE cp.coaster_id = :id AND cp.status = 'approved'
                ORDER BY cp.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'photos' => $photos,
            'total' => count($photos)
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al cargar fotos']);
        exit;
    }
}

function saveReview()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $coasterId = intval($_POST['coaster_id'] ?? 0);
    $note = floatval($_POST['note'] ?? 0);
    $reviewText = trim($_POST['review'] ?? '');
    $pros = $_POST['pros'] ?? [];
    $contras = $_POST['contras'] ?? [];

    if ($coasterId <= 0 || $note <= 0) {
        echo json_encode(['success' => false, 'error' => 'Debes seleccionar una puntuación válida']);
        exit;
    }

    try {
        global $db;
        $db->beginTransaction();

        $sql = "INSERT INTO coaster_ratings (user_id, coaster_id, review, note)
                VALUES (:user_id, :coaster_id, :review, :note)
                RETURNING id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':coaster_id', $coasterId, PDO::PARAM_INT);
        $stmt->bindValue(':review', empty($reviewText) ? null : $reviewText, PDO::PARAM_STR);
        $stmt->bindValue(':note', $note);
        $stmt->execute();

        $reviewId = $stmt->fetchColumn();

        $tagSql = "INSERT INTO review_tags (review_id, tag, type) VALUES (:review_id, :tag, :type)";
        $tagStmt = $db->prepare($tagSql);

        if (is_array($pros)) {
            foreach ($pros as $pro) {
                $tagStmt->execute([
                    ':review_id' => $reviewId,
                    ':tag' => $pro,
                    ':type' => 'pro'
                ]);
            }
        }

        if (is_array($contras)) {
            foreach ($contras as $con) {
                $tagStmt->execute([
                    ':review_id' => $reviewId,
                    ':tag' => $con,
                    ':type' => 'con'
                ]);
            }
        }

        $db->commit();
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Error al guardar reseña: ' . $e->getMessage()]);
        exit;
    }
}

function savePhoto()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $coasterId = intval($_POST['coaster_id'] ?? 0);
    $photoUrl = $_POST['photo_url'] ?? '';
    $caption = $_POST['caption'] ?? null;

    if ($coasterId <= 0 || empty($photoUrl)) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }

    try {
        global $db;
        // Insertamos con estado pending (esto cuadra con el ALTER TABLE que hicimos)
        $sql = "INSERT INTO coaster_photos (user_id, coaster_id, photo_url, caption, status) 
                VALUES (:user_id, :coaster_id, :photo_url, :caption, 'pending')";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':coaster_id', $coasterId, PDO::PARAM_INT);
        $stmt->bindValue(':photo_url', $photoUrl);
        $stmt->bindValue(':caption', $caption);
        $stmt->execute();

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Log the actual error internally
        error_log("Error guardando foto: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error al guardar la foto en la base de datos']);
    }
}