<?php
session_start();

// Asegurar que tenemos el user_id de la BD si hay sesión de Firebase
if (isset($_SESSION['firebase_uid']) && !isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../database/db_conexion.php';
    $db_init = new DBConexion();
    $stmt_init = $db_init->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
    $stmt_init->execute([':uid' => $_SESSION['firebase_uid']]);
    $user_init = $stmt_init->fetch(PDO::FETCH_ASSOC);
    if ($user_init) {
        $_SESSION['user_id'] = (int)$user_init['id'];
    }
}
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/utils/ApiRouter.php';
require_once __DIR__ . '/utils/StatsHelper.php';

header('Content-Type: application/json');

$db = null;

function getDb() {
    global $db;
    if ($db === null) {
        $db = new DBConexion();
    }
    return $db;
}

/**
 * Obtiene el ID numérico del usuario actual desde la base de datos
 * sincronizando la sesión si es necesario.
 */
function getUserId(): ?int
{
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($_SESSION['firebase_uid'])) {
        $db = getDb();
        $stmt = $db->prepare("SELECT id, rol, email, profile_image FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int)$row['id'];
            if (!isset($_SESSION['user_rol'])) $_SESSION['user_rol'] = $row['rol'] ?: 'user';
            if (!isset($_SESSION['user_email'])) $_SESSION['user_email'] = $row['email'];
            if (!isset($_SESSION['profile_image'])) $_SESSION['profile_image'] = $row['profile_image'];
            return (int)$row['id'];
        }
    }
    return null;
}

$router = new ApiRouter('list');

// ── Endpoints públicos ──────────────────────────────────────────────────────────
$router->register('list', 'listParks');
$router->register('country', 'getCountries');
$router->register('details', 'getParkDetails');
$router->register('reviews', 'getParkReviews');
$router->register('save_review', 'saveReview', 'POST');
$router->register('update_review', 'updateReview', 'POST');
$router->register('check_review', 'checkReview');
$router->register('top_global', 'getTopGlobalParks');
$router->register('ranking', 'getRanking');
$router->register('user_tops', 'getUserParkTops');
$router->register('get_photos', 'getParkPhotos');

// ── Endpoints protegidos ─────────────────────────
$router->register('add_photo', 'addParkPhoto', 'POST');

// ── Endpoints de administración ─────────────────────────
$router->register('add', 'addPark', 'POST');
$router->register('update', 'updatePark', 'POST');
$router->register('delete', 'deletePark', 'POST');
$router->register('update_stats', 'updateParkStats', 'POST');

$router->dispatch();

// ── Funciones ───────────────────────────────────────────────────────────────────

// Listar todos los parques (público)
function listParks()
{
    $db = getDb();

    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(2000, max(1, (int) $_GET['limit'])) : 15;
    $offset = ($page - 1) * $limit;

    $where = ["park_name NOT IN ('Desconocido', 'Unknown')"];
    $bind = [];

    if (!empty($_GET['q'])) {
        $where[] = "(unaccent(park_name) ILIKE unaccent(:q) OR unaccent(park_location) ILIKE unaccent(:q) OR unaccent(park_country) ILIKE unaccent(:q))";
        $bind[':q'] = '%' . trim($_GET['q']) . '%';
    }

    if (!empty($_GET['country'])) {
        $where[] = "park_country = :country";
        $bind[':country'] = $_GET['country'];
    }
    if (!empty($_GET['location'])) {
        $where[] = "park_location ILIKE :location";
        $bind[':location'] = '%' . trim($_GET['location']) . '%';
    }
    if (!empty($_GET['opening_year_min'])) {
        $where[] = "opening_year >= :min_year";
        $bind[':min_year'] = (int) $_GET['opening_year_min'];
    }
    if (!empty($_GET['opening_year_max'])) {
        $where[] = "opening_year <= :max_year";
        $bind[':max_year'] = (int) $_GET['opening_year_max'];
    }
    if (!empty($_GET['min_coasters'])) {
        $where[] = "operating_coasters >= :min_coasters";
        $bind[':min_coasters'] = (int) $_GET['min_coasters'];
    }
    if (!empty($_GET['max_coasters'])) {
        $where[] = "operating_coasters <= :max_coasters";
        $bind[':max_coasters'] = (int) $_GET['max_coasters'];
    }
    if (!empty($_GET['min_stars'])) {
        $where[] = "stars >= :min_stars";
        $bind[':min_stars'] = (float) $_GET['min_stars'];
    }

    $sort = $_GET['sort'] ?? 'coasters';
    $reqDir = strtoupper($_GET['order_dir'] ?? '');

    $sortMap = [
        'name' => ['col' => 'park_name', 'default' => 'ASC'],
        'coasters' => ['col' => 'operating_coasters', 'default' => 'DESC'],
        'stars' => ['col' => 'stars', 'default' => 'DESC'],
        'year' => ['col' => 'NULLIF(opening_year, 0)', 'default' => 'ASC'],
    ];

    $config = $sortMap[$sort] ?? $sortMap['coasters'];
    $column = $config['col'];
    $direction = in_array($reqDir, ['ASC', 'DESC']) ? $reqDir : $config['default'];

    $orderBy = "$column $direction NULLS LAST";

    $whereClause = implode(" AND ", $where);

    $sql = "SELECT id, park_name, park_location, park_country, imagen_url, 
                   num_coasters, operating_coasters, opening_year, precio_entrada, stars,
                   latitude, longitude
            FROM parks
            WHERE $whereClause
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset";

    $sqlCount = "SELECT COUNT(*) FROM parks WHERE $whereClause";

    $stmt = $db->prepare($sql);
    $stmtCount = $db->prepare($sqlCount);

    foreach ($bind as $key => $val) {
        $stmt->bindValue($key, $val);
        $stmtCount->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $stmtCount->execute();

    $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = $stmtCount->fetchColumn();

    Response::success(['data' => $parks, 'total' => $total]);
}

// Obtener lista de países únicos (público)
function getCountries()
{
    $db = getDb();
    $stmt = $db->query("SELECT DISTINCT park_country FROM parks WHERE park_country IS NOT NULL ORDER BY park_country ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    Response::success(['data' => $countries]);
}

// Detalle de un parque específico (público)
function getParkDetails()
{
    $db = getDb();
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        Response::error("ID de parque inválido", 400);
    }

    $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM coasters WHERE park_id = p.id) as real_coasters_count,
               CASE WHEN p.stars IS NULL OR p.stars = 0 THEN NULL
                    ELSE (SELECT COUNT(*) + 1 FROM parks AS p2
                          WHERE p2.stars > 0 
                            AND (p2.stars > p.stars OR
                                (p2.stars = p.stars AND
                                 (SELECT COUNT(*) FROM park_ratings WHERE park_id = p2.id AND note = 5) >
                                 (SELECT COUNT(*) FROM park_ratings WHERE park_id = p.id AND note = 5)) OR
                                (p2.stars = p.stars AND
                                 (SELECT COUNT(*) FROM park_ratings WHERE park_id = p2.id AND note = 5) =
                                 (SELECT COUNT(*) FROM park_ratings WHERE park_id = p.id AND note = 5) AND
                                 p2.reviews_count > p.reviews_count) OR
                                (p2.stars = p.stars AND
                                 (SELECT COUNT(*) FROM park_ratings WHERE park_id = p2.id AND note = 5) =
                                 (SELECT COUNT(*) FROM park_ratings WHERE park_id = p.id AND note = 5) AND
                                 p2.reviews_count = p.reviews_count AND
                                 p2.id < p.id)
                                )
                         )
               END AS ranking
        FROM parks p
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $park = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$park) {
        Response::error("Parque no encontrado", 404);
    }

    Response::success($park);
}


// Obtener reseñas de un parque (público)
function getParkReviews()
{
    $db = getDb();
    $id = (int) ($_GET['id'] ?? 0);
    $order = $_GET['order'] ?? 'newest';

    if ($id <= 0) {
        Response::error("ID de parque inválido", 400);
    }

    $currentUserId = $_SESSION['user_id'] ?? 0;

    $orderBy = 'pr.created_at DESC';
    if ($order === 'best')
        $orderBy = 'pr.note DESC, pr.created_at DESC';
    if ($order === 'worst')
        $orderBy = 'pr.note ASC, pr.created_at DESC';

    $stmt = $db->prepare("
        SELECT pr.id, pr.review, pr.note, pr.created_at,
               pr.user_id,
               u.username, u.profile_image,
               (SELECT json_agg(json_build_object('tag', pt.tag, 'type', pt.type))
                FROM park_review_tags pt WHERE pt.review_id = pr.id) AS tags
        FROM park_ratings pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.park_id = :id AND pr.is_hidden = FALSE
        ORDER BY (pr.user_id = :current_user) DESC, $orderBy
        LIMIT 50
    ");
    $stmt->execute([':id' => $id, ':current_user' => $currentUserId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reviews as &$r) {
        $r['tags'] = $r['tags'] ? json_decode($r['tags'], true) : [];
    }

    Response::success(['reviews' => $reviews]);
}

// ── Endpoints protegidos (admin) ───────────────────────────────────────────────

// Añadir nuevo parque (requiere admin)
function addPark()
{
    $db = getDb();

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $data = $_POST; // o $_REQUEST si usas GET para pruebas

    if (empty($data['park_name']) || empty($data['park_location'])) {
        Response::error("park_name y park_location son obligatorios", 400);
    }

    $stmt = $db->prepare("
        INSERT INTO parks (
            park_name, park_location, park_country, imagen_url,
            num_coasters, operating_coasters, opening_year,
            precio_entrada, stars, latitude, longitude
        ) VALUES (
            :park_name, :park_location, :park_country, :imagen_url,
            :num_coasters, :operating_coasters, :opening_year,
            :precio_entrada, :stars, :latitude, :longitude
        ) RETURNING id
    ");

    $stmt->execute([
        ':park_name' => $data['park_name'],
        ':park_location' => $data['park_location'],
        ':park_country' => $data['park_country'] ?? null,
        ':imagen_url' => $data['imagen_url'] ?? null,
        ':num_coasters' => $data['num_coasters'] ?? 0,
        ':operating_coasters' => $data['operating_coasters'] ?? 0,
        ':opening_year' => $data['opening_year'] ?? null,
        ':precio_entrada' => $data['precio_entrada'] ?? null,
        ':stars' => $data['stars'] ?? 0,
        ':latitude' => $data['latitude'] ?? null,
        ':longitude' => $data['longitude'] ?? null,
    ]);

    $newId = $stmt->fetchColumn();
    Response::success(['id' => $newId, 'message' => 'Parque creado']);
}

// Actualizar parque (requiere admin)
function updatePark()
{
    $db = getDb();

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0)
        Response::error("ID inválido", 400);

    $data = $_POST;

    $stmt = $db->prepare("
        UPDATE parks SET
            park_name = COALESCE(:park_name, park_name),
            park_location = COALESCE(:park_location, park_location),
            park_country = COALESCE(:park_country, park_country),
            imagen_url = COALESCE(:imagen_url, imagen_url),
            num_coasters = COALESCE(:num_coasters, num_coasters),
            operating_coasters = COALESCE(:operating_coasters, operating_coasters),
            opening_year = COALESCE(:opening_year, opening_year),
            precio_entrada = COALESCE(:precio_entrada, precio_entrada),
            stars = COALESCE(:stars, stars),
            latitude = COALESCE(:latitude, latitude),
            longitude = COALESCE(:longitude, longitude)
        WHERE id = :id
        RETURNING id
    ");

    $stmt->execute([
        ':id' => $id,
        ':park_name' => $data['park_name'] ?? null,
        ':park_location' => $data['park_location'] ?? null,
        ':park_country' => $data['park_country'] ?? null,
        ':imagen_url' => $data['imagen_url'] ?? null,
        ':num_coasters' => $data['num_coasters'] ?? null,
        ':operating_coasters' => $data['operating_coasters'] ?? null,
        ':opening_year' => $data['opening_year'] ?? null,
        ':precio_entrada' => $data['precio_entrada'] ?? null,
        ':stars' => $data['stars'] ?? null,
        ':latitude' => $data['latitude'] ?? null,
        ':longitude' => $data['longitude'] ?? null,
    ]);

    if ($stmt->rowCount() > 0) {
        Response::success(['message' => 'Parque actualizado']);
    } else {
        Response::error("Parque no encontrado", 404);
    }
}

// Borrar parque (admin)
function deletePark()
{
    $db = getDb();

    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        Response::error("ID de parque inválido.", 400);
    }

    $stmt = $db->prepare("DELETE FROM parks WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        Response::success(['message' => 'Parque eliminado']);
    } else {
        Response::error("Parque no encontrado", 404);
    }
}

// Actualizar estadísticas de parque (requiere admin)
function updateParkStats()
{
    $db = getDb();

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $parkId = (int) ($_GET['park_id'] ?? $_POST['park_id'] ?? 0);
    if ($parkId <= 0)
        Response::error("park_id inválido", 400);

    // Recalcular desde coasters (ejemplo simple)
    $countStmt = $db->prepare("SELECT COUNT(*) FROM coasters WHERE park_id = :id AND coaster_status IN ('Operating', 'Operativa', 'Abierta')");
    $countStmt->execute([':id' => $parkId]);
    $numCoasters = $countStmt->fetchColumn();

    $stmt = $db->prepare("
        UPDATE parks 
        SET num_coasters = :num_coasters,
            operating_coasters = :operating_coasters
        WHERE id = :id
    ");
    $stmt->execute([
        ':num_coasters' => $numCoasters,
        ':operating_coasters' => $numCoasters, // simplificado
        ':id' => $parkId
    ]);

    Response::success(['message' => 'Estadísticas actualizadas']);
}

function saveReview()
{
    $db = getDb();
    
    $userId = getUserId();
    if (!$userId) {
        Response::error("Debe iniciar sesión para dejar una reseña", 401);
    }

    $userId = $_SESSION['user_id'];
    $parkId = (int) ($_POST['park_id'] ?? 0);
    $note = (float) ($_POST['note'] ?? 0);
    $reviewText = trim($_POST['review'] ?? '');
    $pros = $_POST['pros'] ?? [];
    $contras = $_POST['contras'] ?? [];

    if ($parkId <= 0 || $note <= 0) {
        Response::error("Datos de reseña inválidos", 400);
    }

    try {
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM park_ratings WHERE user_id = :user_id AND park_id = :park_id");
        $checkStmt->execute([':user_id' => $userId, ':park_id' => $parkId]);
        if ($checkStmt->fetchColumn() > 0) {
            Response::error("Ya has publicado una reseña para este parque anteriormente.", 400);
        }

        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO park_ratings (user_id, park_id, review, note)
            VALUES (:user_id, :park_id, :review, :note)
            RETURNING id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':park_id' => $parkId,
            ':review' => !empty($reviewText) ? $reviewText : null,
            ':note' => $note
        ]);
        $reviewId = $stmt->fetchColumn();

        if ($reviewId) {
            $tagStmt = $db->prepare("INSERT INTO park_review_tags (review_id, tag, type) VALUES (:review_id, :tag, :type)");

            if (is_array($pros)) {
                foreach ($pros as $tag) {
                    $tagStmt->execute([':review_id' => $reviewId, ':tag' => $tag, ':type' => 'pro']);
                }
            }
            if (is_array($contras)) {
                foreach ($contras as $tag) {
                    $tagStmt->execute([':review_id' => $reviewId, ':tag' => $tag, ':type' => 'con']);
                }
            }
        }

        $db->commit();

        // Actualizar estadísticas del parque (estrellas)
        StatsHelper::updateParkStats($parkId);

        Response::success(['message' => 'Reseña guardada correctamente']);
    } catch (Exception $e) {
        if ($db->inTransaction())
            $db->rollBack();
        Response::error("Error al guardar la reseña: " . $e->getMessage(), 500);
    }
}

function checkReview()
{
    $parkId = (int) ($_GET['id'] ?? 0);
    if ($parkId <= 0)
        Response::error('ID inválido', 400);

    if (!isset($_SESSION['user_id'])) {
        Response::success(['hasReviewed' => false]);
        return;
    }

    try {
        $db = getDb();
        $stmt = $db->prepare("
            SELECT pr.id, pr.note, pr.review,
                   (SELECT json_agg(json_build_object('tag', pt.tag, 'type', pt.type))
                    FROM park_review_tags pt WHERE pt.review_id = pr.id) AS tags
            FROM park_ratings pr
            WHERE pr.user_id = :user_id AND pr.park_id = :park_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => getUserId() ?: 0, ':park_id' => $parkId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['tags'] = $row['tags'] ? json_decode($row['tags'], true) : [];
            Response::success(['hasReviewed' => true, 'review' => $row]);
        } else {
            Response::success(['hasReviewed' => false]);
        }
    } catch (Exception $e) {
        Response::error("Error comprobando estado de reseña");
    }
}

function updateReview()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('Método no permitido', 405);
    }
    $userId = getUserId();
    if (!$userId) {
        Response::error('No autorizado', 401);
    }

    $reviewId  = (int) ($_POST['review_id'] ?? 0);
    $note      = (float) ($_POST['note'] ?? 0);
    $reviewTxt = trim($_POST['review'] ?? '');
    $pros      = $_POST['pros'] ?? [];
    $contras   = $_POST['contras'] ?? [];

    if ($reviewId <= 0 || $note <= 0) {
        Response::error('Datos inválidos', 400);
    }

    try {
        $db = getDb();
        $check = $db->prepare("SELECT park_id FROM park_ratings WHERE id = :id AND user_id = :uid");
        $check->execute([':id' => $reviewId, ':uid' => $userId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            Response::error('Reseña no encontrada o sin permiso', 403);
        }
        $parkId = (int) $row['park_id'];

        $db->beginTransaction();

        $db->prepare("UPDATE park_ratings SET note = :note, review = :review WHERE id = :id")
           ->execute([':note' => $note, ':review' => empty($reviewTxt) ? null : $reviewTxt, ':id' => $reviewId]);

        $db->prepare("DELETE FROM park_review_tags WHERE review_id = :id")->execute([':id' => $reviewId]);
        $tagStmt = $db->prepare("INSERT INTO park_review_tags (review_id, tag, type) VALUES (:review_id, :tag, :type)");
        foreach ((array)$pros as $tag) {
            $tagStmt->execute([':review_id' => $reviewId, ':tag' => $tag, ':type' => 'pro']);
        }
        foreach ((array)$contras as $tag) {
            $tagStmt->execute([':review_id' => $reviewId, ':tag' => $tag, ':type' => 'con']);
        }

        $db->commit();
        StatsHelper::updateParkStats($parkId);
        Response::success();
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        Response::error('Error al actualizar reseña: ' . $e->getMessage());
    }
}

// ── Endpoints de Tops ─────────────────────────────────────────────────────────

// Obtener Top 10 Global de Parques (Público)
function getTopGlobalParks()
{
    $db = getDb();
    try {
        $stmt = $db->prepare("
            SELECT id, park_name, park_location, park_country, imagen_url, stars, num_coasters
            FROM parks
            WHERE stars > 0
            ORDER BY stars DESC, 
                     (SELECT COUNT(*) FROM park_ratings WHERE park_id = parks.id AND note = 5) DESC,
                     id ASC
            LIMIT 10
        ");
        $stmt->execute();
        $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['data' => $parks]);
    } catch (Exception $e) {
        Response::error("Error al obtener el top global de parques: " . $e->getMessage(), 500);
    }
}

// Obtener Tops Personales de Usuarios (Público / Amigos)
function getUserParkTops()
{
    $db = getDb();

    // Leer parámetros GET
    $filterFriends = (isset($_GET['filter']) && $_GET['filter'] === 'friends');

    // Si piden filtro de amigos pero no están logueados → error
    if ($filterFriends && !isset($_SESSION['firebase_uid'])) {
        Response::error('Debes iniciar sesión para ver los tops de tus amigos', 401);
        return;
    }

    $currentUserId = getUserId();

    // Ordenación
    $sort = $_GET['sort'] ?? 'date_desc';
    $orderBy = match ($sort) {
        'alpha_asc'  => 'username ASC',
        'parks_desc' => 'total_parks DESC',
        default      => 'RANDOM()',   // date_desc u otros → aleatorio (no hay timestamp en user_park_credits)
    };

    // Sin límite real — mostrar TODOS los usuarios que tengan un top de parques
    $limit  = $filterFriends ? 9999 : 9999;
    $join   = '';
    $where  = 'upc.rank_position IS NOT NULL AND upc.rank_position > 0';
    $params = [];

    if ($filterFriends && $currentUserId !== null) {
        $join = "JOIN friendship f ON (
                    (f.solicitante_id = :my_id AND f.solicitada_id = u.id)
                 OR (f.solicitada_id = :my_id AND f.solicitante_id = u.id)
                 )";
        $where  = " AND f.estado_solicitud = 'ACEPTADA'";
        $params[':my_id'] = $currentUserId;
    } else {
        $where = "";
    }

    try {
        $stmt = $db->prepare("
            WITH BaseParks AS (
                -- 1. Usuarios con user_park_credits explícitos
                SELECT 
                    upc.user_id,
                    upc.park_id,
                    upc.rank_position
                FROM user_park_credits upc
                WHERE upc.rank_position > 0 AND upc.rank_position IS NOT NULL

                UNION ALL

                -- 2. Fallback: Usuarios sin user_park_credits, con top generado desde sus credits (alfabético)
                SELECT 
                    uc.user_id,
                    c.park_id,
                    ROW_NUMBER() OVER(PARTITION BY uc.user_id ORDER BY pp.park_name ASC) as rank_position
                FROM user_credits uc
                JOIN coasters c ON uc.coaster_id = c.id
                JOIN parks pp ON c.park_id = pp.id
                WHERE NOT EXISTS (
                    SELECT 1 FROM user_park_credits upc2 WHERE upc2.user_id = uc.user_id AND upc2.rank_position > 0
                )
                GROUP BY uc.user_id, c.park_id, pp.park_name
            ),
            UserTops AS (
                SELECT
                    bp.user_id,
                    u.username,
                    u.profile_image,
                    -- Conteo total real_total dependiendo del source de BaseParks
                    (SELECT COUNT(*) FROM BaseParks bp2 WHERE bp2.user_id = u.id) AS real_total,
                    bp.park_id,
                    p.park_name,
                    p.park_country,
                    p.imagen_url,
                    bp.rank_position,
                    ROW_NUMBER() OVER(PARTITION BY bp.user_id ORDER BY bp.rank_position ASC) AS rn
                FROM BaseParks bp
                JOIN users u ON bp.user_id = u.id
                JOIN parks p ON bp.park_id = p.id
                $join
                WHERE 1=1 $where
            )
            SELECT
                user_id,
                username,
                profile_image,
                MAX(real_total) AS total_parks,
                json_agg(
                    json_build_object(
                        'park_id',       park_id,
                        'park_name',     park_name,
                        'park_country',  park_country,
                        'imagen_url',    imagen_url,
                        'rank_position', rank_position
                    ) ORDER BY rank_position ASC
                ) AS top_parks
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

        foreach ($users as &$u) {
            $u['top_parks']   = $u['top_parks'] ? json_decode($u['top_parks'], true) : [];
            $u['total_parks'] = (int)($u['total_parks'] ?? 0);
        }

        Response::success(['data' => $users]);
    } catch (Exception $e) {
        error_log('getUserParkTops error: ' . $e->getMessage());
        Response::error('Error al obtener los tops de parques: ' . $e->getMessage(), 500);
    }
}


// Obtener fotos de un parque
function getParkPhotos()
{
    $db = getDb();
    $parkId = (int)($_GET['park_id'] ?? 0);
    if ($parkId === 0) {
        Response::error('ID de parque inválido.');
        return;
    }

    try {
        $stmt = $db->prepare("
            SELECT pp.*, u.username, u.profile_image 
            FROM park_photos pp
            JOIN users u ON pp.user_id = u.id
            WHERE pp.park_id = :park_id AND pp.status = 'approved'
            ORDER BY pp.created_at DESC
        ");
        $stmt->execute([':park_id' => $parkId]);
        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success(['data' => $photos]);
    } catch (Exception $e) {
        Response::error('Error al obtener fotos: ' . $e->getMessage());
    }
}

function addParkPhoto()
{
    $currentUserId = getUserId();
    if (!$currentUserId) {
        Response::error('No autorizado.', 401);
        return;
    }

    $parkId = (int)($_POST['park_id'] ?? 0);
    $url = $_POST['photo_url'] ?? '';
    $caption = trim($_POST['caption'] ?? '');

    if ($parkId === 0 || empty($url)) {
        Response::error('Datos incompletos.', 400);
        return;
    }

    try {
        global $db;
        $stmt = $db->prepare("
            INSERT INTO park_photos (park_id, user_id, photo_url, caption, status)
            VALUES (:park_id, :user_id, :url, :caption, 'approved')
        ");
        // Nota: Por ahora las pongo como 'approved' para facilitar, 
        // normalmente irían como 'pending'
        $stmt->execute([
            ':park_id' => $parkId,
            ':user_id' => $_SESSION['user_id'],
            ':url'     => $url,
            ':caption' => $caption
        ]);

        Response::success(['message' => 'Foto guardada correctamente.']);
    } catch (Exception $e) {
        Response::error('Error al guardar la foto: ' . $e->getMessage());
    }
}

// Obtener Ranking Paginado de Parques (Público)
function getRanking()
{
    $limit = 15;
    $page = (max(1, intval($_GET['page'] ?? 1)));
    $offset = ($page - 1) * $limit;

    try {
        $db = getDb();

        $stmtTotal = $db->prepare("
            SELECT COUNT(*) FROM parks
            WHERE stars IS NOT NULL AND stars > 0
        ");
        $stmtTotal->execute();
        $total = min((int) $stmtTotal->fetchColumn(), 1000);

        $sql = "SELECT
        id, park_name, park_location, park_country, imagen_url, stars, num_coasters, operating_coasters, opening_year
        FROM parks
        WHERE stars IS NOT NULL AND stars > 0
        ORDER BY 
            stars DESC, 
            (SELECT COUNT(*) FROM park_ratings WHERE park_id = parks.id AND note = 5) DESC,
            reviews_count DESC,
            id ASC
        LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        Response::success([
            'parks' => $parks,
            'total' => $total
        ]);
    } catch (Exception $e) {
        Response::error('Error al obtener el ranking global: ' . $e->getMessage(), 500);
    }
}

$router->dispatch();