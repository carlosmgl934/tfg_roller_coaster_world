<?php
require_once __DIR__ . '/utils/SessionManager.php';

// Asegurar que tenemos el user_id de la BD si hay sesión de Firebase
if (isset($_SESSION['firebase_uid']) && !isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../database/db_conexion.php';
    $db_init = new DBConexion();
    $stmt_init = $db_init->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
    $stmt_init->execute([':uid' => $_SESSION['firebase_uid']]);
    $user_init = $stmt_init->fetch(PDO::FETCH_ASSOC);
    if ($user_init) {
        $_SESSION['user_id'] = (int) $user_init['id'];
    }
}
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/utils/ApiRouter.php';
require_once __DIR__ . '/utils/StatsHelper.php';

$router = new ApiRouter('list');

$router->register('search', 'searchCoasters');
$router->register('list', 'listCoasters');
$router->register('filter', 'filterCoasters');
$router->register('manufacter', 'getManufacturers');
$router->register('country', 'getCountries');
$router->register('ridden', 'getRidden');
$router->register('apply_filters', 'applyFilters');
$router->register('coaster', 'getCoasters');
$router->register('photos', 'getCoasterPhotos');
$router->register('reviews', 'getCoasterReviews');
$router->register('save_review', 'saveReview', 'POST');
$router->register('save_photo', 'savePhoto', 'POST');
$router->register('like_photo', 'likePhoto', 'POST');
$router->register('check_review', 'checkReview');
$router->register('update_review', 'updateReview', 'POST');
$router->register('user_tops', 'getUserCoasterTops');
$router->register('ranking', 'getRanking');
$router->register('all_reviews', 'getAllReviews');


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
            $limitNum = (int) $_GET['limit'];
            $limitClause = "LIMIT " . $limitNum;
        }

        $sql = "SELECT 
    coasters.id, coasters.coaster_name, coasters.coaster_status, parks.park_name, parks.park_country
    FROM coasters
    INNER JOIN parks ON coasters.park_id = parks.id
    WHERE unaccent(coasters.coaster_name) ILIKE unaccent(:name) " . $limitClause;

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
        'name' => ['col' => 'coasters.coaster_name', 'default' => 'ASC'],
        'height' => ['col' => 'coasters.height', 'default' => 'DESC'],
        'speed' => ['col' => 'coasters.speed', 'default' => 'DESC'],
        'year' => ['col' => 'NULLIF(coasters.opening_year, 0)', 'default' => 'ASC'],
        'id' => ['col' => 'coasters.id', 'default' => 'ASC'],
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
    coasters.opening_year, coasters.stars,
    (SELECT ROUND(AVG(note)::numeric, 1) FROM coaster_ratings WHERE coaster_id = coasters.id) AS score
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
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
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
        coasters.opening_year,
        (SELECT ROUND(AVG(note)::numeric, 1) FROM coaster_ratings WHERE coaster_id = coasters.id) AS score
        FROM coasters
        INNER JOIN parks ON coasters.park_id = parks.id
        WHERE unaccent(coasters.coaster_name) ILIKE unaccent(:name)
        LIMIT :limit OFFSET :offset";

        $sql_2 = "SELECT COUNT(*) as total FROM coasters WHERE unaccent(coasters.coaster_name) ILIKE unaccent(:name)";

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
        $conditions[] = "unaccent(coasters.coaster_name) ILIKE unaccent(:search)";
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
        'name' => ['col' => 'coasters.coaster_name', 'default' => 'ASC'],
        'height' => ['col' => 'coasters.height', 'default' => 'DESC'],
        'speed' => ['col' => 'coasters.speed', 'default' => 'DESC'],
        'year' => ['col' => 'NULLIF(coasters.opening_year, 0)', 'default' => 'ASC'],
        'id' => ['col' => 'coasters.id', 'default' => 'ASC'],
    ];

    $config = $sortMap[$sort] ?? $sortMap['id'];
    $column = $config['col'];
    $direction = in_array($reqDir, ['ASC', 'DESC']) ? $reqDir : $config['default'];

    $orderBy = "$column $direction NULLS LAST";

    $page = intval($_GET['page'] ?? 1);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
    if ($limit > 100)
        $limit = 100;
    if ($limit <= 0)
        $limit = 15;
    $offset = ($page - 1) * $limit;
    $where = implode(" AND ", $conditions);

    try {
        global $db;
        $sql = "SELECT coasters.id, coasters.coaster_name, coasters.imagen_url, 
                parks.park_name, coasters.coaster_manufacter AS manufacter,
                coasters.coaster_model AS modelo, coasters.opening_year, coasters.stars,
                coasters.coaster_status, coasters.height, coasters.speed,
                coasters.coaster_length, coasters.inversions,
                (SELECT ROUND(AVG(note)::numeric, 1) FROM coaster_ratings WHERE coaster_id = coasters.id) AS score
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
        coasters.stars AS score,
        CASE WHEN coasters.stars IS NULL OR coasters.stars = 0 THEN NULL
             ELSE (SELECT COUNT(*) + 1 FROM coasters AS c2 
                   WHERE c2.stars > 0 
                     AND (c2.stars > coasters.stars OR 
                         (c2.stars = coasters.stars AND 
                          (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = c2.id AND note = 5) > 
                          (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = coasters.id AND note = 5)
                         ) OR
                         (c2.stars = coasters.stars AND 
                          (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = c2.id AND note = 5) = 
                          (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = coasters.id AND note = 5) AND
                          c2.reviews_count > coasters.reviews_count
                         ) OR
                         (c2.stars = coasters.stars AND 
                          (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = c2.id AND note = 5) = 
                          (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = coasters.id AND note = 5) AND
                          c2.reviews_count = coasters.reviews_count AND
                          c2.id < coasters.id
                         )
                     )
                  )
        END AS global_rank,
        (SELECT rank_position FROM user_credits WHERE coaster_id = coasters.id AND user_id = :user_id LIMIT 1) AS personal_ranking,
        coasters.reviews_count
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

    $currentUserId = $_SESSION['user_id'] ?? 0;

    $orderSql = match ($order) {
        'recent' => 'cr.created_at DESC',
        'best' => 'cr.note DESC',
        'worst' => 'cr.note ASC',
        default => 'cr.created_at DESC'
    };

    try {
        global $db;
        // La reseña del usuario actual siempre aparece la primera
        $sql = "SELECT cr.id, cr.note, cr.review, cr.created_at,
                       cr.user_id,
                       users.username, users.profile_image,
                       (SELECT json_agg(json_build_object('tag', rt.tag, 'type', rt.type))
                        FROM review_tags rt WHERE rt.review_id = cr.id) AS tags
                FROM coaster_ratings AS cr
                INNER JOIN users ON cr.user_id = users.id
                WHERE cr.coaster_id = :id AND cr.is_hidden = FALSE
                ORDER BY (cr.user_id = :current_user) DESC, $orderSql";

        $sql_count = "SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = :id AND is_hidden = FALSE";

        $stmt = $db->prepare($sql);
        $stmt_count = $db->prepare($sql_count);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':current_user', $currentUserId, PDO::PARAM_INT);
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

    $current_user_id = $_SESSION['user_id'] ?? 0;

    try {
        global $db;
        $sql = "SELECT coaster_photos.*, users.username, users.profile_image,
                       (SELECT 1 FROM coaster_photo_likes cpl WHERE cpl.photo_id = coaster_photos.id AND cpl.user_id = :current_user) AS user_has_liked
                FROM coaster_photos
                INNER JOIN users ON coaster_photos.user_id = users.id
                WHERE coaster_photos.coaster_id = :id AND coaster_photos.status = 'approved'
                ORDER BY coaster_photos.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':current_user', $current_user_id, PDO::PARAM_INT);
        $stmt->execute();

        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($photos as &$p) {
            $p['user_has_liked'] = !empty($p['user_has_liked']);
        }

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
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM coaster_ratings WHERE user_id = :user_id AND coaster_id = :coaster_id");
        $checkStmt->execute([':user_id' => $userId, ':coaster_id' => $coasterId]);
        if ($checkStmt->fetchColumn() > 0) {
            Response::error('Ya has publicado una reseña para esta atracción anteriormente.', 400);
        }

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

        // Actualizar estadísticas de la montaña rusa (estrellas)
        StatsHelper::updateCoasterStats($coasterId);

        Response::success();
    } catch (PDOException $e) {
        $db->rollBack();
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function checkReview()
{
    $coasterId = intval($_GET['id'] ?? 0);
    if ($coasterId <= 0) {
        Response::error('ID inválido');
    }

    if (!isset($_SESSION['user_id'])) {
        Response::success(['hasReviewed' => false]);
        return;
    }

    try {
        global $db;
        $stmt = $db->prepare("
            SELECT cr.id, cr.note, cr.review,
                   (SELECT json_agg(json_build_object('tag', rt.tag, 'type', rt.type))
                    FROM review_tags rt WHERE rt.review_id = cr.id) AS tags
            FROM coaster_ratings cr
            WHERE cr.user_id = :user_id AND cr.coaster_id = :coaster_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id'], ':coaster_id' => $coasterId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['tags'] = $row['tags'] ? json_decode($row['tags'], true) : [];
            Response::success(['hasReviewed' => true, 'review' => $row]);
        } else {
            Response::success(['hasReviewed' => false]);
        }
    } catch (PDOException $e) {
        Response::error('Error comprobando estado de reseña');
    }
}

function updateReview()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }
    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized();
    }

    $userId = (int) $_SESSION['user_id'];
    $reviewId = (int) ($_POST['review_id'] ?? 0);
    $note = (float) ($_POST['note'] ?? 0);
    $reviewTxt = trim($_POST['review'] ?? '');
    $pros = $_POST['pros'] ?? [];
    $contras = $_POST['contras'] ?? [];

    if ($reviewId <= 0 || $note <= 0) {
        Response::error('Datos inválidos', 400);
    }

    try {
        global $db;
        // Verificar que la reseña pertenece al usuario
        $check = $db->prepare("SELECT coaster_id FROM coaster_ratings WHERE id = :id AND user_id = :uid");
        $check->execute([':id' => $reviewId, ':uid' => $userId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            Response::error('Reseña no encontrada o sin permiso', 403);
        }
        $coasterId = (int) $row['coaster_id'];

        $db->beginTransaction();

        // Actualizar nota y texto
        $db->prepare("UPDATE coaster_ratings SET note = :note, review = :review WHERE id = :id")
            ->execute([':note' => $note, ':review' => empty($reviewTxt) ? null : $reviewTxt, ':id' => $reviewId]);

        // Regenerar tags
        $db->prepare("DELETE FROM review_tags WHERE review_id = :id")->execute([':id' => $reviewId]);
        $tagStmt = $db->prepare("INSERT INTO review_tags (review_id, tag, type) VALUES (:review_id, :tag, :type)");
        foreach ((array) $pros as $tag) {
            $tagStmt->execute([':review_id' => $reviewId, ':tag' => $tag, ':type' => 'pro']);
        }
        foreach ((array) $contras as $tag) {
            $tagStmt->execute([':review_id' => $reviewId, ':tag' => $tag, ':type' => 'con']);
        }

        $db->commit();
        StatsHelper::updateCoasterStats($coasterId);
        Response::success();
    } catch (PDOException $e) {
        if ($db->inTransaction())
            $db->rollBack();
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
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

    if (!isset($_SESSION['user_id'])) {
        Response::unauthorized();
    }
    $user_id = $_SESSION['user_id'];

    if ($photo_id <= 0) {
        Response::error('ID de foto no válido');
        return;
    }

    try {
        global $db;
        if ($unlike) {
            $stmt = $db->prepare("DELETE FROM coaster_photo_likes WHERE photo_id = :id AND user_id = :uid");
            $stmt->bindValue(':id', $photo_id, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $db->prepare("UPDATE coaster_photos SET likes = GREATEST(COALESCE(likes, 0) - 1, 0) WHERE id = :id")->execute([':id' => $photo_id]);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO coaster_photo_likes (photo_id, user_id) VALUES (:id, :uid) ON CONFLICT DO NOTHING");
            $stmt->bindValue(':id', $photo_id, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $db->prepare("UPDATE coaster_photos SET likes = COALESCE(likes, 0) + 1 WHERE id = :id")->execute([':id' => $photo_id]);
            }
        }

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

function getUserCoasterTops()
{
    global $db;

    // Leer parámetros GET
    $filterFriends = (isset($_GET['filter']) && $_GET['filter'] === 'friends');

    // Si piden filtro de amigos pero no están logueados → error
    if ($filterFriends && !isset($_SESSION['user_id'])) {
        Response::error('Debes iniciar sesión para ver los tops de tus amigos', 401);
        return;
    }

    $currentUserId = $_SESSION['user_id'] ?? null;

    // Ordenación
    $sort = $_GET['sort'] ?? 'random';
    $orderBy = match ($sort) {
        'alpha_asc' => 'username ASC',
        'credits_desc' => 'total_coasters DESC',
        'date_desc' => 'last_modified DESC',
        default => 'RANDOM()',
    };

    $limit = 9999;
    $join = '';
    $where = 'uc.rank_position IS NOT NULL AND uc.rank_position > 0';
    $params = [];

    if ($filterFriends) {
        $join = "JOIN friendship f ON (
                    (f.solicitante_id = :my_id AND f.solicitada_id = u.id)
                 OR (f.solicitada_id = :my_id AND f.solicitante_id = u.id)
                 )";
        $where .= " AND f.estado_solicitud = 'ACEPTADA'";
        $params[':my_id'] = $currentUserId;
    }

    try {
        $stmt = $db->prepare("
            WITH UserTops AS (
                SELECT
                    uc.user_id,
                    u.username,
                    u.profile_image,
                    (SELECT COUNT(*) FROM user_credits WHERE user_id = u.id) AS real_total,
                    uc.coaster_id,
                    c.coaster_name,
                    p.park_name,
                    c.imagen_url,
                    uc.rank_position,
                    uc.created_at,
                    ROW_NUMBER() OVER(PARTITION BY uc.user_id ORDER BY uc.rank_position ASC) AS rn
                FROM user_credits uc
                JOIN users u    ON uc.user_id    = u.id
                JOIN coasters c ON uc.coaster_id = c.id
                JOIN parks p    ON c.park_id     = p.id
                $join
                WHERE $where
            )
            SELECT
                user_id,
                username,
                profile_image,
                MAX(real_total) AS total_coasters,
                MAX(created_at) AS last_modified,
                json_agg(
                    json_build_object(
                        'coaster_id',   coaster_id,
                        'coaster_name', coaster_name,
                        'park_name',    park_name,
                        'imagen_url',   imagen_url,
                        'rank_position', rank_position
                    ) ORDER BY rank_position ASC
                ) AS top_coasters
            FROM UserTops
            WHERE rn <= 5
            GROUP BY user_id, username, profile_image
            ORDER BY $orderBy
            LIMIT :limit
        ");

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar el JSON de top_coasters que devuelve PostgreSQL
        foreach ($users as &$u) {
            $u['top_coasters'] = $u['top_coasters'] ? json_decode($u['top_coasters'], true) : [];
        }

        Response::success(['data' => $users]);
    } catch (Exception $e) {
        error_log('getUserCoasterTops error: ' . $e->getMessage());
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function getRanking()
{

    $limit = 15;
    $page = (max(1, intval($_GET['page'] ?? 1)));
    $offset = ($page - 1) * $limit;

    try {
        global $db;

        $stmtTotal = $db->prepare("
            SELECT COUNT(*) FROM coasters
            INNER JOIN parks ON coasters.park_id = parks.id
            WHERE coasters.stars IS NOT NULL AND coasters.stars > 0
        ");
        $stmtTotal->execute();
        $total = min((int) $stmtTotal->fetchColumn(), 1000);


        $sql = "SELECT
        coasters.id, coasters.coaster_name, coasters.imagen_url, parks.park_name, coasters.coaster_manufacter AS manufacter,
        coasters.coaster_model AS modelo,
        coasters.opening_year, coasters.stars
        FROM coasters
        INNER JOIN parks ON coasters.park_id = parks.id
        WHERE coasters.stars IS NOT NULL AND coasters.stars > 0
        ORDER BY 
            coasters.stars DESC, 
            (SELECT COUNT(*) FROM coaster_ratings WHERE coaster_id = coasters.id AND note = 5) DESC,
            coasters.reviews_count DESC,
            coasters.id ASC
        LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'coasters' => $coasters,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function getAllReviews()
{
    $limit = 10;
    $page = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['search'] ?? '');

    // Controles de Ordenación y Filtros
    $sort = $_GET['sort'] ?? 'date';
    $order = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    $friends_only = filter_var($_GET['friends_only'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

    if ($friends_only && !isset($_SESSION['user_id'])) {
        Response::error("Debes iniciar sesión para filtrar reseñas de amigos.", 401);
        return;
    }

    try {
        global $db;
        $whereClause = "WHERE coaster_ratings.is_hidden = FALSE";
        $params = [];

        // Si han escrito algo en el buscador
        if ($search !== "") {
            $whereClause .= " AND (unaccent(coasters.coaster_name) ILIKE unaccent(:search) 
                                OR unaccent(users.username) ILIKE unaccent(:search))";
            $params[':search'] = "%" . $search . "%";
        }

        $joinClause = "INNER JOIN users ON coaster_ratings.user_id = users.id 
                       INNER JOIN coasters ON coaster_ratings.coaster_id = coasters.id 
                       INNER JOIN parks ON coasters.park_id = parks.id";

        // Filtro de Solo Amigos
        if ($friends_only) {
            $joinClause .= " INNER JOIN friendship f ON (
                        (f.solicitante_id = :my_id AND f.solicitada_id = users.id)
                     OR (f.solicitada_id = :my_id AND f.solicitante_id = users.id)
                     )";
            $whereClause .= " AND f.estado_solicitud = 'ACEPTADA'";
            $params[':my_id'] = $_SESSION['user_id'];
        }

        // 1) Calculamos el TOTAL exacto de elementos para Paginación
        $sqlTotal = "SELECT COUNT(*) FROM coaster_ratings $joinClause $whereClause";
        $stmtTotal = $db->prepare($sqlTotal);
        foreach ($params as $param => $val) {
            $stmtTotal->bindValue($param, $val);
        }
        $stmtTotal->execute();
        $total = (int) $stmtTotal->fetchColumn();

        // 2) Consulta Normal para la "Página"
        $orderBy = "ORDER BY coaster_ratings.created_at $order";
        if ($sort === 'rating') {
            $orderBy = "ORDER BY coaster_ratings.note $order, coaster_ratings.created_at DESC";
        }

        $sql = "SELECT coaster_ratings.id, coaster_ratings.note, coaster_ratings.review, coaster_ratings.created_at, 
                       users.id AS user_id, users.username, users.profile_image, 
                       coasters.coaster_name, coasters.id AS coaster_id, coasters.imagen_url, parks.park_name
                FROM coaster_ratings 
                $joinClause
                $whereClause 
                $orderBy 
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $param => $val) {
            $stmt->bindValue($param, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'reviews' => $reviews,
            'total' => $total
        ]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}
