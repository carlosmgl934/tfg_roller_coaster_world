<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/utils/ApiRouter.php';

$router = new ApiRouter('list');

$router->register('search',        'searchCoasters');
$router->register('list',          'listCoasters');
$router->register('filter',        'filterCoasters');
$router->register('manufacter',    'getManufacturers');
$router->register('country',       'getCountries');
$router->register('ridden',        'getRidden');
$router->register('apply_filters', 'applyFilters');
$router->register('coaster',       'getCoasters');
$router->register('photos',        'getCoasterPhotos');
$router->register('reviews',       'getCoasterReviews');
$router->register('save_review',   'saveReview', 'POST');
$router->register('save_photo',    'savePhoto', 'POST');
$router->register('like_photo',    'likePhoto', 'POST');

$router->dispatch();

function getManufacturers()
{
    try {
        global $db;
        $sql = "SELECT DISTINCT coaster_manufacter FROM coasters ORDER BY coaster_manufacter ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $manufacturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['manufacters' => $manufacturers]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo fabricantes');
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
        Response::success(['countries' => $countries]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo países');
    }
}

function getRidden()
{
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized();
    }

    try {
        global $db;
        $sql = "SELECT DISTINCT coaster_id FROM user_credits WHERE user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $ridden = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['ridden' => $ridden]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo montañas rusas');
    }
}

function searchCoasters()
{

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        Response::success([]);
    }

    try {
        global $db;
        // Límite por defecto para el buscador del menú
        $limitClause = "LIMIT 5";

        // Si el usuario envía un límite numérico por GET (via profile.js) 
        if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
            $limitNum = (int)$_GET['limit'];
            $limitClause = "LIMIT " . $limitNum;
        }

        $sql = "SELECT 
    coasters.id, coasters.coaster_name, parks.park_name
    FROM coasters
    INNER JOIN parks ON coasters.park_id = parks.id
    WHERE coasters.coaster_name ILIKE :name " . $limitClause;

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':name', "%" . trim($_GET['search']) . "%", PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } catch (PDOException $e) {
        Response::error('Error buscando montaña rusa');
    }
}

function listCoasters()
{
    $page = intval($_GET['page'] ?? 1);
    $limit = 15;
    $offset = ($page - 1) * $limit;

    $sort = $_GET['sort'] ?? 'id';
    $reqDir = strtoupper($_GET['order_dir'] ?? '');

    $sortMap = [
        'name'   => ['col' => 'coasters.coaster_name', 'default' => 'ASC'],
        'stars'  => ['col' => '(SELECT AVG(note) FROM coaster_ratings WHERE coaster_id = coasters.id)', 'default' => 'DESC'],
        'height' => ['col' => 'coasters.height', 'default' => 'DESC'],
        'speed'  => ['col' => 'coasters.speed', 'default' => 'DESC'],
        'year'   => ['col' => 'NULLIF(coasters.opening_year, 0)', 'default' => 'ASC'],
        'id'     => ['col' => 'coasters.id', 'default' => 'ASC'],
    ];

    $config = $sortMap[$sort] ?? $sortMap['id'];
    $column = $config['col'];
    $direction = in_array($reqDir, ['ASC', 'DESC']) ? $reqDir : $config['default'];

    $orderBy = "$column $direction NULLS LAST";

    try {
        global $db;
        $sql = "SELECT
    coasters.id, coasters.coaster_name, coasters.imagen_url, parks.park_name, coasters.coaster_manufacter AS manufacter,
    coasters.coaster_model AS modelo,
    coasters.opening_year, coasters.stars
    FROM coasters
    INNER JOIN parks ON coasters.park_id = parks.id
    ORDER BY $orderBy
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

        Response::success([
            'coasters' => $coasters,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        Response::error('Error mostrando montañas rusas: ' . $e->getMessage());
    }
}

function filterCoasters()
{

    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        Response::success([]);
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
        Response::success([
            'coasters' => $result,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        Response::error('Error mostrando montañas rusas');
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

    if (!empty($_GET['park_id'])) {
        $conditions[] = "coasters.park_id = :park_id";
        $params[':park_id'] = intval($_GET['park_id']);
    }

    $sort = $_GET['sort'] ?? 'id';
    $reqDir = strtoupper($_GET['order_dir'] ?? '');

    $sortMap = [
        'name'   => ['col' => 'coasters.coaster_name', 'default' => 'ASC'],
        'stars'  => ['col' => '(SELECT AVG(note) FROM coaster_ratings WHERE coaster_id = coasters.id)', 'default' => 'DESC'],
        'height' => ['col' => 'coasters.height', 'default' => 'DESC'],
        'speed'  => ['col' => 'coasters.speed', 'default' => 'DESC'],
        'year'   => ['col' => 'NULLIF(coasters.opening_year, 0)', 'default' => 'ASC'],
        'id'     => ['col' => 'coasters.id', 'default' => 'ASC'],
    ];

    $config = $sortMap[$sort] ?? $sortMap['id'];
    $column = $config['col'];
    $direction = in_array($reqDir, ['ASC', 'DESC']) ? $reqDir : $config['default'];

    $orderBy = "$column $direction NULLS LAST";

    $page = intval($_GET['page'] ?? 1);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
    if ($limit > 100) $limit = 100;
    if ($limit <= 0) $limit = 15;
    $offset = ($page - 1) * $limit;
    $where = implode(" AND ", $conditions);

    try {
        global $db;
        $sql = "SELECT coasters.id, coasters.coaster_name, coasters.imagen_url, 
                parks.park_name, coasters.coaster_manufacter AS manufacter,
                coasters.coaster_model AS modelo, coasters.opening_year, coasters.stars,
                coasters.coaster_status
                FROM coasters
                INNER JOIN parks ON coasters.park_id = parks.id
                WHERE $where
                ORDER BY $orderBy
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

        Response::success(['coasters' => $result, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error($e->getMessage());
    }
}

function getCoasters()
{

    $id = intval($_GET['id'] ?? 0);
    $userId = $_SESSION['user_id'] ?? 0; // Para saber si el usuario ha montado la coaster

    if ($id <= 0) {
        Response::error('ID no válido');
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
            Response::success(['coaster' => $result]);
        } else {
            Response::notFound('Montaña rusa no encontrada');
        }
    } catch (PDOException $e) {
        Response::error('Error en la base de datos');
    }
}

function getCoasterReviews()
{
    $id = intval($_GET['id'] ?? 0);
    $order = $_GET['order'] ?? 'default';

    if ($id <= 0) {
        Response::error('ID no válido');
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

        Response::success(['reviews' => $reviews, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error($e->getMessage());
    }
}

function getCoasterPhotos()
{
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        Response::error('ID no válido');
    }

    try {
        global $db;
        $sql = "SELECT coaster_photos.*, users.username, users.profile_image 
                FROM coaster_photos
                INNER JOIN users ON coaster_photos.user_id = users.id
                WHERE coaster_photos.coaster_id = :id AND coaster_photos.status = 'approved'
                ORDER BY coaster_photos.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'photos' => $photos,
            'total' => count($photos)
        ]);
    } catch (PDOException $e) {
        Response::error('Error al cargar fotos');
    }
}

function saveReview()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized();
    }

    $userId = $_SESSION['user_id'];
    $coasterId = intval($_POST['coaster_id'] ?? 0);
    $note = floatval($_POST['note'] ?? 0);
    $reviewText = trim($_POST['review'] ?? '');
    $pros = $_POST['pros'] ?? [];
    $contras = $_POST['contras'] ?? [];

    if ($coasterId <= 0 || $note <= 0) {
        Response::error('Debes seleccionar una puntuación válida');
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
        Response::success();
    } catch (PDOException $e) {
        $db->rollBack();
        Response::error('Error al guardar reseña: ' . $e->getMessage());
    }
}

function savePhoto()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized();
    }

    $userId = $_SESSION['user_id'];
    $coasterId = intval($_POST['coaster_id'] ?? 0);
    $photoUrl = $_POST['photo_url'] ?? '';
    $caption = $_POST['caption'] ?? null;

    if ($coasterId <= 0 || empty($photoUrl)) {
        Response::error('Datos inválidos');
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

        Response::success();
    } catch (PDOException $e) {
        // Log the actual error internally
        error_log("Error guardando foto: " . $e->getMessage());
        Response::error('Error al guardar la foto en la base de datos');
    }
}

function likePhoto()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $photo_id = intval($_POST['photo_id'] ?? 0);
    $unlike = isset($_POST['unlike']) && $_POST['unlike'] === 'true';

    if ($photo_id <= 0) {
        Response::error('ID de foto no válido');
        return;
    }

    try {
        global $db;
        if ($unlike) {
            $stmt = $db->prepare("UPDATE coaster_photos SET likes = GREATEST(COALESCE(likes, 0) - 1, 0) WHERE id = :id");
        } else {
            $stmt = $db->prepare("UPDATE coaster_photos SET likes = COALESCE(likes, 0) + 1 WHERE id = :id");
        }
        $stmt->bindValue(':id', $photo_id, PDO::PARAM_INT);
        $stmt->execute();

        // Devolver los likes actualizados
        $stmt_likes = $db->prepare("SELECT likes FROM coaster_photos WHERE id = :id");
        $stmt_likes->bindValue(':id', $photo_id, PDO::PARAM_INT);
        $stmt_likes->execute();
        $likes = $stmt_likes->fetchColumn();

        Response::success(['likes' => $likes]);
    } catch (PDOException $e) {
        Response::error('Error al modificar like de la foto');
    }
}
