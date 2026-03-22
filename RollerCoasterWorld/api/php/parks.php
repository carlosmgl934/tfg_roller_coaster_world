<?php
session_start();
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/utils/ApiRouter.php';

$router = new ApiRouter('list');

// ── Endpoints públicos ──────────────────────────────────────────────────────────
$router->register('list',      'listParks');
$router->register('country',   'getCountries');
$router->register('details',   'getParkDetails');
$router->register('reviews',   'getParkReviews');
// ── Endpoints protegidos (requieren login y rol admin) ─────────────────────────
$router->register('add',           'addPark',           'POST');
$router->register('update',        'updatePark',        'POST');
$router->register('delete',        'deletePark',        'POST');
$router->register('add_photo',     'addParkPhoto',      'POST');
$router->register('update_stats',  'updateParkStats',   'POST');

$router->dispatch();

// ── Funciones ───────────────────────────────────────────────────────────────────

// Listar todos los parques (público)
function listParks() {
    global $db;
    
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 15;
    $offset = ($page - 1) * $limit;

    $where = ["park_name NOT IN ('Desconocido', 'Unknown')"];
    $bind = [];

    if (!empty($_GET['q'])) {
        $where[] = "(park_name ILIKE :q OR park_location ILIKE :q OR park_country ILIKE :q)";
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
        $bind[':min_year'] = (int)$_GET['opening_year_min'];
    }
    if (!empty($_GET['opening_year_max'])) {
        $where[] = "opening_year <= :max_year";
        $bind[':max_year'] = (int)$_GET['opening_year_max'];
    }
    if (!empty($_GET['min_coasters'])) {
        $where[] = "operating_coasters >= :min_coasters";
        $bind[':min_coasters'] = (int)$_GET['min_coasters'];
    }
    if (!empty($_GET['max_coasters'])) {
        $where[] = "operating_coasters <= :max_coasters";
        $bind[':max_coasters'] = (int)$_GET['max_coasters'];
    }
    if (!empty($_GET['min_stars'])) {
        $where[] = "stars >= :min_stars";
        $bind[':min_stars'] = (float)$_GET['min_stars'];
    }

    $sort = $_GET['sort'] ?? 'coasters';
    $reqDir = strtoupper($_GET['order_dir'] ?? '');

    $sortMap = [
        'name'     => ['col' => 'park_name', 'default' => 'ASC'],
        'coasters' => ['col' => 'operating_coasters', 'default' => 'DESC'],
        'stars'    => ['col' => 'stars', 'default' => 'DESC'],
        'year'     => ['col' => 'NULLIF(opening_year, 0)', 'default' => 'ASC'],
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
function getCountries() {
    global $db;
    $stmt = $db->query("SELECT DISTINCT park_country FROM parks WHERE park_country IS NOT NULL ORDER BY park_country ASC");
    $countries = $stmt->fetchAll(PDO::FETCH_COLUMN);
    Response::success(['data' => $countries]);
}

// Detalle de un parque específico (público)
function getParkDetails() {
    global $db;
    $id = (int)($_GET['id'] ?? 0);

    if ($id <= 0) {
        Response::error("ID de parque inválido", 400);
    }

    $stmt = $db->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM coasters WHERE park_id = p.id) as real_coasters_count
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
function getParkReviews() {
    global $db;
    $id = (int)($_GET['id'] ?? 0);
    $order = $_GET['order'] ?? 'newest';

    if ($id <= 0) {
        Response::error("ID de parque inválido", 400);
    }

    $orderBy = 'pr.created_at DESC';
    if ($order === 'best') $orderBy = 'pr.note DESC, pr.created_at DESC';
    if ($order === 'worst') $orderBy = 'pr.note ASC, pr.created_at DESC';

    $stmt = $db->prepare("
        SELECT pr.review, pr.note, pr.created_at, u.username 
        FROM park_ratings pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.park_id = :id AND pr.review IS NOT NULL AND pr.review != ''
        ORDER BY $orderBy
        LIMIT 50
    ");
    $stmt->execute([':id' => $id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    Response::success(['reviews' => $reviews]);
}

// ── Endpoints protegidos (admin) ───────────────────────────────────────────────

// Añadir nuevo parque (requiere admin)
function addPark() {
    global $db;

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
        ':park_name'          => $data['park_name'],
        ':park_location'      => $data['park_location'],
        ':park_country'       => $data['park_country'] ?? null,
        ':imagen_url'         => $data['imagen_url'] ?? null,
        ':num_coasters'       => $data['num_coasters'] ?? 0,
        ':operating_coasters' => $data['operating_coasters'] ?? 0,
        ':opening_year'       => $data['opening_year'] ?? null,
        ':precio_entrada'     => $data['precio_entrada'] ?? null,
        ':stars'              => $data['stars'] ?? 0,
        ':latitude'           => $data['latitude'] ?? null,
        ':longitude'          => $data['longitude'] ?? null,
    ]);

    $newId = $stmt->fetchColumn();
    Response::success(['id' => $newId, 'message' => 'Parque creado']);
}

// Actualizar parque (requiere admin)
function updatePark() {
    global $db;

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) Response::error("ID inválido", 400);

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
        ':id'                 => $id,
        ':park_name'          => $data['park_name'] ?? null,
        ':park_location'      => $data['park_location'] ?? null,
        ':park_country'       => $data['park_country'] ?? null,
        ':imagen_url'         => $data['imagen_url'] ?? null,
        ':num_coasters'       => $data['num_coasters'] ?? null,
        ':operating_coasters' => $data['operating_coasters'] ?? null,
        ':opening_year'       => $data['opening_year'] ?? null,
        ':precio_entrada'     => $data['precio_entrada'] ?? null,
        ':stars'              => $data['stars'] ?? null,
        ':latitude'           => $data['latitude'] ?? null,
        ':longitude'          => $data['longitude'] ?? null,
    ]);

    if ($stmt->rowCount() > 0) {
        Response::success(['message' => 'Parque actualizado']);
    } else {
        Response::error("Parque no encontrado", 404);
    }
}

// Eliminar parque (requiere admin)
function deletePark() {
    global $db;

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) Response::error("ID inválido", 400);

    $stmt = $db->prepare("DELETE FROM parks WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        Response::success(['message' => 'Parque eliminado']);
    } else {
        Response::error("Parque no encontrado", 404);
    }
}

// Añadir foto a parque (requiere admin)
function addParkPhoto() {
    global $db;

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $parkId = (int)($_POST['park_id'] ?? 0);
    $photoUrl = $_POST['photo_url'] ?? '';
    $caption = $_POST['caption'] ?? '';

    if ($parkId <= 0 || empty($photoUrl)) {
        Response::error("park_id y photo_url son obligatorios", 400);
    }

    $stmt = $db->prepare("
        INSERT INTO park_photos (park_id, photo_url, caption)
        VALUES (:park_id, :photo_url, :caption)
        RETURNING id
    ");
    $stmt->execute([
        ':park_id'    => $parkId,
        ':photo_url'  => $photoUrl,
        ':caption'    => $caption
    ]);

    Response::success(['message' => 'Foto añadida']);
}

// Actualizar estadísticas de parque (requiere admin)
function updateParkStats() {
    global $db;

    if (!isset($_SESSION['firebase_uid']) || $_SESSION['rol'] !== 'admin') {
        Response::error("Acceso denegado. Requiere rol admin.", 403);
    }

    $parkId = (int)($_GET['park_id'] ?? $_POST['park_id'] ?? 0);
    if ($parkId <= 0) Response::error("park_id inválido", 400);

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
        ':num_coasters'       => $numCoasters,
        ':operating_coasters' => $numCoasters, // simplificado
        ':id'                 => $parkId
    ]);

    Response::success(['message' => 'Estadísticas actualizadas']);
}