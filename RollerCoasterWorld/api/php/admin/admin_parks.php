<?php
session_start();
require_once __DIR__ . '/../../database/db_conexion.php';
require_once __DIR__ . '/../utils/ApiRouter.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../../utils/ImageHelper.php';

if (function_exists('opcache_reset')) opcache_reset();

header('Content-Type: application/json');

$db = new DBConexion();

$router = new ApiRouter();
$router->register('searchParks',     'searchParks',     'GET');
$router->register('filterParks',     'filterParks',     'GET');
$router->register('unknownCoasters', 'unknownCoasters', 'GET');
$router->register('addPark',         'addPark',         'POST');
$router->register('getPark',         'getPark',         'GET');
$router->register('getParkCoasters', 'getParkCoasters', 'GET');
$router->register('deletePark',      'deletePark',      'POST');
$router->register('editPark',        'editPark',        'POST');
$router->register('duplicatePark',   'duplicatePark',   'POST');
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

    $data = $_POST;

    $name     = trim($data['name']     ?? '');
    $country  = trim($data['country']  ?? '');
    $location = trim($data['location'] ?? '');
    $year     = isset($data['year']) && $data['year'] !== '' ? intval($data['year']) : null;
    $website  = trim($data['website']  ?? '') ?: null;
    $precio   = isset($data['precio_entrada']) && $data['precio_entrada'] !== '' ? floatval($data['precio_entrada']) : null;
    
    // Si viene como string separado por comas (desde FormData)
    $coasterIdsRaw = $data['coasterIds'] ?? [];
    if (is_string($coasterIdsRaw) && !empty($coasterIdsRaw)) {
        $coasterIds = array_filter(array_map('intval', explode(',', $coasterIdsRaw)), fn($id) => $id > 0);
    } else {
        $coasterIds = array_filter(array_map('intval', (array)$coasterIdsRaw), fn($id) => $id > 0);
    }

    $imagenUrl = $data['imagenUrl'] ?? null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../web/img/uploads/parks/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = uniqid('park_') . '-' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.webp';
        $optimized = ImageHelper::optimizeAndConvertToWebP($_FILES['image']['tmp_name'], 1920, 80);
        if ($optimized && rename($optimized, $uploadDir . $fileName)) {
            $imagenUrl = '/web/img/uploads/parks/' . $fileName;
        } else {
            // Fallback
            $fileNameFallback = uniqid('park_') . '-' . basename($_FILES['image']['name']);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileNameFallback)) {
                $imagenUrl = '/web/img/uploads/parks/' . $fileNameFallback;
            }
        }
    }

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
            INSERT INTO parks (park_name, park_country, park_location, opening_year, num_coasters, operating_coasters, imagen_url, website, precio_entrada)
            VALUES (:name, :country, :location, :year, :total, :operating, :imagenUrl, :website, :precio)
            RETURNING id
        ");
        $stmtInsert->execute([
            ':name'      => $name,
            ':country'   => $country,
            ':location'  => $location,
            ':year'      => $year,
            ':total'     => $totalCoasters,
            ':operating' => $operatingCoasters,
            ':imagenUrl' => $imagenUrl,
            ':website'   => $website,
            ':precio'    => $precio,
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

// ─────────────────────────────────────────────────────────────
// getPark — devuelve todos los campos de un parque por ID
// ─────────────────────────────────────────────────────────────
function getPark(): void
{
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    if (!$id) { Response::error('ID requerido.', 400); return; }

    try {
        global $db;
        $stmt = $db->prepare("
            SELECT id, park_name, park_country, park_location, opening_year,
                   imagen_url, website, precio_entrada
            FROM parks WHERE id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $park = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$park) { Response::error('Parque no encontrado.', 404); return; }
        Response::success(['park' => $park]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// deletePark — elimina un parque por ID
// ─────────────────────────────────────────────────────────────
function deletePark(): void
{
    requireAdmin();
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { Response::error('ID requerido.', 400); return; }

    try {
        global $db;
        // Reasignar coasters al parque "Desconocido" antes de eliminar
        $db->prepare("UPDATE coasters SET park_id = 2895 WHERE park_id = :id")
           ->execute([':id' => $id]);
        $stmt = $db->prepare("DELETE FROM parks WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        Response::success(['message' => 'Parque eliminado correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al eliminar: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// editPark — actualiza los campos del parque
// ─────────────────────────────────────────────────────────────
function editPark(): void
{
    requireAdmin();
    $data = $_POST;

    $id       = intval($data['id'] ?? 0);
    $name     = trim($data['name']     ?? '');
    $country  = trim($data['country']  ?? '');
    $location = trim($data['location'] ?? '');
    $year     = isset($data['year']) && $data['year'] !== '' ? intval($data['year']) : null;
    $website  = trim($data['website']  ?? '') ?: null;
    $precio   = isset($data['precio_entrada']) && $data['precio_entrada'] !== '' ? floatval($data['precio_entrada']) : null;

    if (!$id)       { Response::error('ID requerido.',                400); return; }
    if (!$name)     { Response::error('El nombre es obligatorio.',    400); return; }
    if (!$country)  { Response::error('El país es obligatorio.',      400); return; }
    if (!$location) { Response::error('La localización es obligatoria.', 400); return; }

    try {
        global $db;

        // Gestión de imagen (igual que addPark)
        $imagenUrl = $data['imagenUrl'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../web/img/uploads/parks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName  = uniqid('park_') . '-' . pathinfo($_FILES['image']['name'], PATHINFO_FILENAME) . '.webp';
            $optimized = ImageHelper::optimizeAndConvertToWebP($_FILES['image']['tmp_name'], 1920, 80);
            if ($optimized && rename($optimized, $uploadDir . $fileName)) {
                $imagenUrl = '/web/img/uploads/parks/' . $fileName;
            } else {
                $fb = uniqid('park_') . '-' . basename($_FILES['image']['name']);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fb)) {
                    $imagenUrl = '/web/img/uploads/parks/' . $fb;
                }
            }
        }

        $sql = "UPDATE parks SET
                    park_name      = :name,
                    park_country   = :country,
                    park_location  = :location,
                    opening_year   = :year,
                    website        = :website,
                    precio_entrada = :precio
                    " . ($imagenUrl !== null ? ", imagen_url = :img" : "") . "
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':name',    $name);
        $stmt->bindValue(':country', $country);
        $stmt->bindValue(':location',$location);
        $stmt->bindValue(':year',    $year,    $year    === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':website', $website, $website === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':precio',  $precio,  PDO::PARAM_STR);
        if ($imagenUrl !== null) $stmt->bindValue(':img', $imagenUrl);
        $stmt->bindValue(':id',      $id, PDO::PARAM_INT);
        $stmt->execute();

        // ── Reasignación de coasters ──
        // coasterIds = IDs que deben pertenecer a este parque
        $coasterIdsRaw = $data['coasterIds'] ?? '';
        $coasterIds = [];
        if (is_string($coasterIdsRaw) && $coasterIdsRaw !== '') {
            $coasterIds = array_values(array_filter(array_map('intval', explode(',', $coasterIdsRaw)), fn($x) => $x > 0));
        }

        // 1) Coasters que estaban en este parque y NO están en la nueva lista → vuelven a Desconocido
        $db->prepare("UPDATE coasters SET park_id = 2895 WHERE park_id = :pid")
           ->execute([':pid' => $id]);

        // 2) Asignar los seleccionados a este parque
        if (!empty($coasterIds)) {
            $ph = implode(',', array_fill(0, count($coasterIds), '?'));
            $stmtUp = $db->prepare("UPDATE coasters SET park_id = $id WHERE id IN ($ph)");
            $stmtUp->execute(array_values($coasterIds));
        }

        // Recalcular contadores del parque
        $stmtCount = $db->prepare("
            UPDATE parks SET
                num_coasters       = (SELECT COUNT(*) FROM coasters WHERE park_id = :pid),
                operating_coasters = (SELECT COUNT(*) FROM coasters WHERE park_id = :pid AND coaster_status = 'Operating')
            WHERE id = :pid
        ");
        $stmtCount->execute([':pid' => $id]);

        Response::success(['message' => 'Parque actualizado correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al editar el parque: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// duplicatePark — crea una copia del parque
// ─────────────────────────────────────────────────────────────
function duplicatePark(): void
{
    requireAdmin();
    $id = intval($_POST['id'] ?? 0);
    if (!$id) { Response::error('ID requerido.', 400); return; }

    try {
        global $db;
        $stmt = $db->prepare("
            INSERT INTO parks (park_name, park_country, park_location, opening_year, website, precio_entrada, imagen_url)
            SELECT park_name || ' (Copia)', park_country, park_location, opening_year, website, precio_entrada, imagen_url
            FROM parks WHERE id = :id
            RETURNING id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $newId = (int)$stmt->fetchColumn();
        Response::success(['id' => $newId, 'message' => 'Parque duplicado correctamente.']);
    } catch (PDOException $e) {
        Response::error('Error al duplicar: ' . $e->getMessage());
    }
}

// ─────────────────────────────────────────────────────────────
// getParkCoasters — coasters del parque (checked) + desconocidas (unchecked)
// ─────────────────────────────────────────────────────────────
function getParkCoasters(): void
{
    requireAdmin();
    $parkId = intval($_GET['park_id'] ?? 0);
    $q      = trim($_GET['q'] ?? '');
    if (!$parkId) { Response::error('park_id requerido.', 400); return; }

    try {
        global $db;

        // Coasters ya asignadas a este parque
        $sqlPark = "SELECT id, coaster_name, coaster_status, TRUE as in_park
                    FROM coasters
                    WHERE park_id = :pid
                    " . ($q ? "AND coaster_name ILIKE :q" : "") . "
                    ORDER BY coaster_name ASC
                    LIMIT 500";
        $stmtPark = $db->prepare($sqlPark);
        $stmtPark->bindValue(':pid', $parkId, PDO::PARAM_INT);
        if ($q) $stmtPark->bindValue(':q', '%'.$q.'%');
        $stmtPark->execute();
        $parkCoasters = $stmtPark->fetchAll(PDO::FETCH_ASSOC);

        // Coasters desconocidas (park_id = 2895)
        $sqlUnk = "SELECT id, coaster_name, coaster_status, FALSE as in_park
                   FROM coasters
                   WHERE park_id = 2895
                   " . ($q ? "AND coaster_name ILIKE :q" : "") . "
                   ORDER BY coaster_name ASC
                   LIMIT 300";
        $stmtUnk = $db->prepare($sqlUnk);
        if ($q) $stmtUnk->bindValue(':q', '%'.$q.'%');
        $stmtUnk->execute();
        $unknownCoasters = $stmtUnk->fetchAll(PDO::FETCH_ASSOC);

        // Mezclar: primero los del parque (checked), luego desconocidos
        $all = array_merge($parkCoasters, $unknownCoasters);
        Response::success(['coasters' => $all]);
    } catch (PDOException $e) {
        Response::error('Error: ' . $e->getMessage());
    }
}
