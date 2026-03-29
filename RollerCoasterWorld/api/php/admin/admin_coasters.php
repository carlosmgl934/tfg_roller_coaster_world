<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/StatsHelper.php';
require_once __DIR__ . '/../../utils/ImageHelper.php';

header('Content-Type: application/json');

$db = new DBConexion();

$router = new ApiRouter();
$router->register('searchCoasters', 'searchCoasters', 'GET');
$router->register('filterCoasters', 'filterCoasters', 'GET');
$router->register('listModels', 'listModels', 'GET');
$router->register('addCoaster', 'addCoaster', 'POST');
$router->register('deleteCoaster', 'deleteCoaster', 'POST');
$router->register('updateCoaster', 'updateCoaster', 'POST');
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
                    c.park_id,
                    c.coaster_name,
                    c.coaster_manufacter,
                    c.coaster_status,
                    c.opening_year,
                    c.height,
                    c.speed,
                    c.coaster_length,
                    c.inversions,
                    c.coaster_model,
                    c.imagen_url,
                    COALESCE(p.park_name, 'Desconocido') AS park_name,
                    p.park_country
                FROM coasters c
                LEFT JOIN parks p ON c.park_id = p.id
                WHERE c.coaster_name ILIKE :like
                ORDER BY c.coaster_name ASC
                LIMIT :limit OFFSET :offset";

        $sql_count = "SELECT COUNT(*) FROM coasters c
                      LEFT JOIN parks p ON c.park_id = p.id
                      WHERE c.coaster_name ILIKE :like";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':like', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $coasters = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt2 = $db->prepare($sql_count);
        $stmt2->bindValue(':like', $like, PDO::PARAM_STR);
        $stmt2->execute();
        $total = (int) $stmt2->fetchColumn();

        Response::success(['coasters' => $coasters, 'total' => $total]);
    } catch (PDOException $e) {
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

    // Búsqueda por nombre (buscador principal)
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $conditions[] = "c.coaster_name ILIKE :search";
        $params[':search'] = '%' . trim($_GET['search']) . '%';
    }

    // Solo operativas
    if (!empty($_GET['opened']) && $_GET['opened'] === 'true') {
        $conditions[] = "c.coaster_status = 'Operating'";
    }

    // Fabricante — valor especial '__null__' → IS NULL en BD
    if (isset($_GET['manufacter']) && $_GET['manufacter'] !== '') {
        if ($_GET['manufacter'] === '__null__') {
            $conditions[] = "c.coaster_manufacter IS NULL";
        } else {
            $conditions[] = "c.coaster_manufacter = :manufacter";
            $params[':manufacter'] = $_GET['manufacter'];
        }
    }

    // País — valor especial '__null__'
    if (isset($_GET['country']) && $_GET['country'] !== '') {
        if ($_GET['country'] === '__null__') {
            $conditions[] = "p.park_country IS NULL";
        } else {
            $conditions[] = "p.park_country = :country";
            $params[':country'] = $_GET['country'];
        }
    }

    // Parque — valor especial '__null__'
    if (isset($_GET['park']) && $_GET['park'] !== '') {
        if ($_GET['park'] === '__null__') {
            $conditions[] = "(c.park_id IS NULL OR p.id IS NULL OR c.park_id = 2895)";
        } else {
            $conditions[] = "c.park_id = :park";
            $params[':park'] = intval($_GET['park']);
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
                    c.park_id,
                    c.coaster_name,
                    c.coaster_manufacter,
                    c.coaster_status,
                    c.opening_year,
                    c.height,
                    c.speed,
                    c.coaster_length,
                    c.inversions,
                    c.coaster_model,
                    c.imagen_url,
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
        $total = (int) $stmt2->fetchColumn();

        Response::success(['coasters' => $coasters, 'total' => $total]);
    } catch (PDOException $e) {
        Response::error('Error filtrando coasters: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// listModels — modelos únicos para el autocomplete del formulario
// ─────────────────────────────────────────────────────────────
function listModels(): void
{
    requireAdmin();

    $q = trim($_GET['q'] ?? '');
    $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 50;

    $where = "coaster_model IS NOT NULL AND coaster_model <> ''";
    $bind = [];

    if ($q !== '') {
        $where .= ' AND coaster_model ILIKE :q';
        $bind[':q'] = '%' . $q . '%';
    }

    try {
        global $db;
        $sql = "SELECT DISTINCT coaster_model
                FROM coasters
                WHERE $where
                ORDER BY coaster_model ASC
                LIMIT :limit";

        $stmt = $db->prepare($sql);
        foreach ($bind as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $models = $stmt->fetchAll(PDO::FETCH_COLUMN);
        Response::success(['models' => array_map(fn($m) => ['coaster_model' => $m], $models)]);
    } catch (PDOException $e) {
        Response::error('Error obteniendo modelos: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// deleteCoaster — eliminar una coaster por ID
// ─────────────────────────────────────────────────────────────
function deleteCoaster(): void
{
    requireAdmin();

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $id = isset($data['coasterId']) ? intval($data['coasterId']) : 0;
    if ($id <= 0) {
        Response::error('ID de coaster inválido.');
        return;
    }

    try {
        global $db;

        // Antes de borrar, obtenemos el park_id y el nombre para actualizar sus stats y la respuesta
        $stmtPark = $db->prepare("SELECT park_id, coaster_name FROM coasters WHERE id = :id");
        $stmtPark->execute([':id' => $id]);
        $coasterRow = $stmtPark->fetch(PDO::FETCH_ASSOC);
        $parkId = $coasterRow['park_id'] ?? null;
        $coasterName = $coasterRow['coaster_name'] ?? '';

        // Eliminar
        $stmt = $db->prepare("DELETE FROM coasters WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // Actualizar estadísticas del parque
        if ($parkId) {
            StatsHelper::updateParkStats((int) $parkId);
        }

        Response::success(['coaster_name' => $coasterName]);
    } catch (PDOException $e) {
        Response::error('Error eliminando coaster: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// addCoaster — añadir una coaster 
// ─────────────────────────────────────────────────────────────
function addCoaster(): void
{
    requireAdmin();

    $data = $_POST;

    $name = trim($data['name'] ?? '');
    // Replace "Desconocido" with null for manufacturer and model
    $model = trim($data['model'] ?? '');
    $model = ($model === 'Desconocido' || $model === '') ? null : $model;

    $manufacter = trim($data['manufacturer'] ?? '');
    $manufacter = ($manufacter === 'Desconocido' || $manufacter === '') ? null : $manufacter;

    // Park comes from hidden ID input or autocomplete
    $parkId = (isset($data['parkId']) && $data['parkId'] !== '') ? intval($data['parkId']) : null;

    $status = trim($data['status'] ?? '') ?: null;
    $height = trim($data['height'] ?? '') ?: null;
    $speed = trim($data['speed'] ?? '') ?: null;
    $year = (isset($data['year']) && $data['year'] !== '') ? intval($data['year']) : null;
    $length = trim($data['length'] ?? '') ?: null;
    $inversions = (isset($data['inversions']) && $data['inversions'] !== '') ? intval($data['inversions']) : null;
    $rcdbId = (isset($data['rcdbId']) && $data['rcdbId'] !== '') ? intval($data['rcdbId']) : null;

    $imagenUrl = $_POST['imagenUrl'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../web/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid('coaster_') . '-' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.webp';
        $optimized = ImageHelper::optimizeAndConvertToWebP($_FILES['image']['tmp_name'], 1920, 80);
        if ($optimized && rename($optimized, $uploadDir . $fileName)) {
            $imagenUrl = '/web/img/' . $fileName;
        } else {
            // Fallback
            $fileNameFallback = uniqid('coaster_') . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileNameFallback)) {
                $imagenUrl = '/web/img/' . $fileNameFallback;
            }
        }
    }

    if ($name === '') {
        Response::error('El nombre es obligatorio.');
        return;
    }

    try {
        global $db;

        // Coasters agregadas con ID >= 100000 para no interferir con las de RCDB
        $stmt_id = $db->query("SELECT COALESCE(MAX(id), 99999) + 1 FROM coasters WHERE id >= 100000");
        $nextId = (int) $stmt_id->fetchColumn();

        $sql = "INSERT INTO coasters (
                    id, coaster_name, coaster_model, coaster_manufacter,
                    park_id, coaster_status, height, speed,
                    opening_year, coaster_length, inversions,
                    rcdb_id, imagen_url
                ) VALUES (
                    :id, :name, :model, :manufacter,
                    :parkId, :status, :height, :speed,
                    :year, :length, :inversions,
                    :rcdbId, :imagenUrl
                )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id' => $nextId,
            ':name' => $name,
            ':model' => $model,
            ':manufacter' => $manufacter,
            ':parkId' => $parkId,
            ':status' => $status,
            ':height' => $height,
            ':speed' => $speed,
            ':year' => $year,
            ':length' => $length,
            ':inversions' => $inversions,
            ':rcdbId' => $rcdbId,
            ':imagenUrl' => $imagenUrl,
        ]);

        $newId = $nextId;

        $stmt = $db->prepare("
            SELECT c.*, p.park_name, p.park_country
            FROM coasters c
            LEFT JOIN parks p ON c.park_id = p.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $newId]);
        $newCoaster = $stmt->fetch(PDO::FETCH_ASSOC);

        // Actualizar estadísticas del parque beneficiado
        if ($parkId) {
            StatsHelper::updateParkStats((int) $parkId);
        }

        Response::success(['coaster' => $newCoaster]);
    } catch (PDOException $e) {
        Response::error('Error añadiendo coaster: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// updateCoaster — editar una coaster existente
// ─────────────────────────────────────────────────────────────
function updateCoaster(): void
{
    requireAdmin();

    $data = $_POST;

    $id = isset($data['id']) ? intval($data['id']) : 0;
    if ($id <= 0) {
        Response::error('ID de coaster inválido.');
        return;
    }

    $name = trim($data['name'] ?? '');
    $model = trim($data['model'] ?? '');
    $model = ($model === 'Desconocido' || $model === '') ? null : $model;

    $manufacter = trim($data['manufacturer'] ?? '');
    $manufacter = ($manufacter === 'Desconocido' || $manufacter === '') ? null : $manufacter;

    $parkId = (isset($data['parkId']) && $data['parkId'] !== '') ? intval($data['parkId']) : null;
    if ($parkId === null) {
        Response::error('El parque es obligatorio.');
        return;
    }

    $status = trim($data['status'] ?? '') ?: null;
    $height = trim($data['height'] ?? '') ?: null;
    $speed = trim($data['speed'] ?? '') ?: null;
    $length = trim($data['length'] ?? '') ?: null;
    $year = (isset($data['year']) && $data['year'] !== '') ? intval($data['year']) : null;
    $inversions = (isset($data['inversions']) && $data['inversions'] !== '') ? intval($data['inversions']) : null;
    $rcdbId = (isset($data['rcdbId']) && $data['rcdbId'] !== '') ? intval($data['rcdbId']) : null;

    $imagenUrl = $_POST['imagenUrl'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../web/img/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid('coaster_') . '-' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.webp';
        $optimized = ImageHelper::optimizeAndConvertToWebP($_FILES['image']['tmp_name'], 1920, 80);
        if ($optimized && rename($optimized, $uploadDir . $fileName)) {
            $imagenUrl = '/web/img/' . $fileName;
        } else {
            // Fallback
            $fileNameFallback = uniqid('coaster_') . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileNameFallback)) {
                $imagenUrl = '/web/img/' . $fileNameFallback;
            }
        }
    }

    if ($name === '') {
        Response::error('El nombre es obligatorio.');
        return;
    }

    try {
        global $db;

        // Si no hay nueva imagen, no tocamos imagen_url
        $updateImagenSql = '';
        $params = [
            ':id' => $id,
            ':name' => $name,
            ':model' => $model,
            ':manufacter' => $manufacter,
            ':parkId' => $parkId,
            ':status' => $status,
            ':height' => $height,
            ':speed' => $speed,
            ':year' => $year,
            ':length' => $length,
            ':inversions' => $inversions,
            ':rcdbId' => $rcdbId,
        ];

        if ($imagenUrl !== null) {
            $updateImagenSql = ', imagen_url = :imagenUrl';
            $params[':imagenUrl'] = $imagenUrl;
        }

        $sql = "UPDATE coasters
                SET coaster_name = :name,
                    coaster_model = :model,
                    coaster_manufacter = :manufacter,
                    park_id = :parkId,
                    coaster_status = :status,
                    height = :height,
                    speed = :speed,
                    opening_year = :year,
                    coaster_length = :length,
                    inversions = :inversions,
                    rcdb_id = :rcdbId
                    $updateImagenSql
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        // Devolver la coaster actualizada
        $stmt = $db->prepare("
            SELECT c.*, p.park_name, p.park_country
            FROM coasters c
            LEFT JOIN parks p ON c.park_id = p.id
            WHERE c.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $updatedCoaster = $stmt->fetch(PDO::FETCH_ASSOC);

        // Actualizar estadísticas de parques (el nuevo, y el viejo si cambió)
        // NOTA: Para simplificar, actualizamos el actual. 
        // Si queremos ser súper precisos, deberíamos haber guardado el OLD park_id antes del UPDATE.
        if ($parkId) {
            StatsHelper::updateParkStats((int) $parkId);
        }

        Response::success(['coaster' => $updatedCoaster]);
    } catch (PDOException $e) {
        Response::error('Error actualizando coaster: ' . $e->getMessage());
    }
}