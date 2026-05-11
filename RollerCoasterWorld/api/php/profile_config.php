<?php
require_once __DIR__ . '/utils/SessionManager.php';
require_once __DIR__ . '/../database/db_conexion.php';

header('Content-Type: application/json');

$db = new DBConexion();
require_once __DIR__ . '/utils/ApiRouter.php';

$router = new ApiRouter();
$router->register('search', 'searchParks');
$router->register('save_profile', 'saveProfile', 'POST');
$router->register('update_avatar', 'updateAvatar', 'POST');
$router->register('get_profile', 'getProfile');
$router->register('get_top_coasters', 'getTopCoasters');
$router->register('get_top_parks', 'getTopParks');
$router->register('save_top_coasters', 'saveTopCoasters', 'POST');
$router->register('save_top_parks', 'saveTopParks', 'POST');
$router->register('get_my_reviews', 'getMyReviews');
$router->register('get_map_parks', 'getMapParks');
$router->dispatch();

// ── Helper: obtiene el user_id de la sesión, con fallback por firebase_uid ──────
function getUserId(): ?int
{
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }
    // Fallback: resolver por firebase_uid (sesiones anteriores al fix)
    if (isset($_SESSION['firebase_uid'])) {
        global $db;
        $stmt = $db->prepare("SELECT id FROM users WHERE firebase_uid = :uid LIMIT 1");
        $stmt->execute([':uid' => $_SESSION['firebase_uid']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $_SESSION['user_id'] = (int) $row['id']; // guardar para la próxima vez
            return (int) $row['id'];
        }
    }
    return null;
}

// ── Búsqueda de Parques para el Home Park ─────────────────────────────────────
function searchParks()
{
    global $db;
    $search = $_GET['search'] ?? '';

    if (strlen($search) < 3) {
        echo json_encode([]);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT id AS park_id, park_name, park_country AS country_name FROM parks WHERE unaccent(park_name) ILIKE unaccent(:search) AND park_name NOT IN ('Desconocido', 'Unknown') LIMIT 10");
        $stmt->execute([':search' => '%' . $search . '%']);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    } catch (PDOException $e) {
        echo json_encode([]);
        exit;
    }
}

// ── Guardar Configuración de Perfil ───────────────────────────────────────────
function saveProfile()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás autenticado');
    }

    // Recoger datos
    $fullName = strlen($_POST['fullName'] ?? '') > 0 ? $_POST['fullName'] : null;
    $username = strlen($_POST['username'] ?? '') > 0 ? $_POST['username'] : null;
    // El email NO se actualiza desde el formulario (campo disabled en el front)
    // Se mantiene el valor actual en BD; solo lo usamos para mostrar al usuario
    $birthdate = strlen($_POST['birthday'] ?? '') > 0 ? $_POST['birthday'] : null;
    // Gender: string vacío → null para no violar el check constraint de la BD
    $gender = strlen($_POST['gender'] ?? '') > 0 ? $_POST['gender'] : null;
    $city = strlen($_POST['city'] ?? '') > 0 ? $_POST['city'] : null;
    $country = strlen($_POST['country'] ?? '') > 0 ? $_POST['country'] : null;
    $topCoaster = strlen($_POST['topCoaster'] ?? '') > 0 ? $_POST['topCoaster'] : null;
    $homePark = strlen($_POST['homePark'] ?? '') > 0 ? $_POST['homePark'] : null;

    global $db;
    $stmt = $db->prepare("
        UPDATE users SET
            full_name        = COALESCE(:full_name, full_name),
            username         = COALESCE(:username, username),
            birthdate        = COALESCE(:birthdate, birthdate),
            gender           = COALESCE(:gender, gender),
            city             = COALESCE(:city, city),
            country          = COALESCE(:country, country),
            favorite_coaster = COALESCE(:favorite_coaster, favorite_coaster),
            home_park        = COALESCE(:home_park, home_park)
        WHERE id = :id
    ");

    try {
        $stmt->execute([
            ':full_name' => $fullName,
            ':username' => $username,
            ':birthdate' => $birthdate,
            ':gender' => $gender,
            ':city' => $city,
            ':country' => $country,
            ':favorite_coaster' => $topCoaster,
            ':home_park' => $homePark,
            ':id' => $user_id
        ]);

        // Actualizar username en la sesión para que el header lo refleje sin relogin
        if ($username !== null) {
            $_SESSION['username'] = $username;
        }

        Response::success();
    } catch (PDOException $e) {
        // Manejar duplicados de nombre (postgres error code 23505)
        if ($e->getCode() == 23505) {
            Response::error('El nombre de usuario ya está en uso');
        } else {
            error_log($e->getMessage());
            Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
        }
    }
}

function getProfile()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }
    global $db;
    $stmt = $db->prepare("
        SELECT 
            full_name,
            username,
            email,
            birthdate,
            gender,
            city,
            country,
            favorite_coaster,
            home_park,
            profile_image
        FROM users
        WHERE id = :id
    ");
    try {
        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Extraer estadísticas adicionales
            $stats = [
                'coasters_count' => 0,
                'parks_count' => 0,
                'countries_count' => 0,
                'reviews_count' => 0,
                'ranking' => '—',
                'top_coaster' => '—',
                'top_park' => '—'
            ];

            // --- Ranking Global ---
            // Calcula la posición global del usuario ordenando a todos por cantidad de coasters en user_credits
            $stmtRanking = $db->prepare("
                WITH RankedUsers AS (
                    SELECT u.id, RANK() OVER (ORDER BY COUNT(uc.id) DESC) as rank_pos
                    FROM users u
                    LEFT JOIN user_credits uc ON u.id = uc.user_id
                    GROUP BY u.id
                )
                SELECT rank_pos FROM RankedUsers WHERE id = :id
            ");
            $stmtRanking->execute([':id' => $user_id]);
            $userRank = $stmtRanking->fetchColumn();
            if ($userRank) {
                $stats['ranking'] = '#' . $userRank;
            }

            // Montañas rusas count
            $stmtC = $db->prepare("SELECT COUNT(*) FROM user_credits WHERE user_id = :id");
            $stmtC->execute([':id' => $user_id]);
            $stats['coasters_count'] = $stmtC->fetchColumn();

            // Parques count (Parques distintos deducidos de las coasters montadas + parques rankeados)
            $stmtP = $db->prepare("
                SELECT COUNT(*) FROM (
                    SELECT park_id FROM user_park_credits WHERE user_id = :id
                    UNION
                    SELECT c.park_id FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id
                ) as distinct_parks
            ");
            $stmtP->execute([':id' => $user_id]);
            $stats['parks_count'] = (int) $stmtP->fetchColumn() ?: 0;

            // Paises count (Países distintos deducidos de los parques visitados/rankeados)
            $stmtCountry = $db->prepare("
                SELECT COUNT(DISTINCT park_country) FROM parks WHERE id IN (
                    SELECT park_id FROM user_park_credits WHERE user_id = :id
                    UNION
                    SELECT c.park_id FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id
                )
            ");
            $stmtCountry->execute([':id' => $user_id]);
            $stats['countries_count'] = (int) $stmtCountry->fetchColumn() ?: 0;

            // Reviews count
            // Dependiendo de cómo se llamen las tablas
            $c_reviews = 0;
            $p_reviews = 0;
            try {
                $stR1 = $db->prepare("SELECT COUNT(*) FROM coaster_ratings WHERE user_id = :id");
                if ($stR1->execute([':id' => $user_id]))
                    $c_reviews = $stR1->fetchColumn();
                $stR2 = $db->prepare("SELECT COUNT(*) FROM park_ratings WHERE user_id = :id");
                if ($stR2->execute([':id' => $user_id]))
                    $p_reviews = $stR2->fetchColumn();
            } catch (Exception $e) {
            } // Ignorar si las tablas de ratings no existen aún
            $stats['reviews_count'] = $c_reviews + $p_reviews;

            // Top Coaster (#1 rank)
            $stmtTC = $db->prepare("SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id ORDER BY uc.rank_position ASC LIMIT 1");
            $stmtTC->execute([':id' => $user_id]);
            $tc = $stmtTC->fetchColumn();
            if ($tc)
                $stats['top_coaster'] = $tc;

            // Top Park (#1 rank)
            $stmtTP = $db->prepare("SELECT p.park_name FROM user_park_credits up JOIN parks p ON up.park_id = p.id WHERE up.user_id = :id ORDER BY up.rank_position ASC LIMIT 1");
            $stmtTP->execute([':id' => $user_id]);
            $tp = $stmtTP->fetchColumn();
            if ($tp)
                $stats['top_park'] = $tp;
            // --- Estadísticas Técnicas ---

            // País más visitado (país en el que has visitado más parques diferentes)
            $stmtMainCountry = $db->prepare("
                SELECT p.park_country 
                FROM parks p 
                JOIN (
                    SELECT park_id FROM user_park_credits WHERE user_id = :id
                    UNION
                    SELECT c.park_id FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id
                ) dp ON p.id = dp.park_id
                WHERE p.park_country IS NOT NULL AND p.park_country != ''
                GROUP BY p.park_country 
                ORDER BY COUNT(dp.park_id) DESC 
                LIMIT 1
            ");
            $stmtMainCountry->execute([':id' => $user_id]);
            $stats['main_country'] = $stmtMainCountry->fetchColumn() ?: '—';

            // Fabricante favorito
            $stmtMainManuf = $db->prepare("SELECT c.coaster_manufacter FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id AND c.coaster_manufacter IS NOT NULL GROUP BY c.coaster_manufacter ORDER BY COUNT(*) DESC LIMIT 1");
            $stmtMainManuf->execute([':id' => $user_id]);
            $stats['main_manufacturer'] = $stmtMainManuf->fetchColumn() ?: '—';

            // Fabricantes totales
            $stmtTotalManuf = $db->prepare("SELECT COUNT(DISTINCT c.coaster_manufacter) FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id");
            $stmtTotalManuf->execute([':id' => $user_id]);
            $stats['total_manufacturers'] = $stmtTotalManuf->fetchColumn() ?: 0;

            // Altura total superada
            $stmtHeight = $db->prepare("SELECT SUM(c.height) FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id");
            $stmtHeight->execute([':id' => $user_id]);
            $stats['total_height'] = round((float) $stmtHeight->fetchColumn());

            // Inversiones totales
            $stmtInv = $db->prepare("SELECT SUM(c.inversions) FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id");
            $stmtInv->execute([':id' => $user_id]);
            $stats['total_inversions'] = (int) $stmtInv->fetchColumn();

            // Más rápida
            $stmtFastest = $db->prepare("SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id AND c.speed IS NOT NULL ORDER BY c.speed DESC LIMIT 1");
            $stmtFastest->execute([':id' => $user_id]);
            $stats['fastest_coaster'] = $stmtFastest->fetchColumn() ?: '—';

            // Más larga
            $stmtLongest = $db->prepare("SELECT c.coaster_name FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = :id AND c.coaster_length IS NOT NULL ORDER BY c.coaster_length DESC LIMIT 1");
            $stmtLongest->execute([':id' => $user_id]);
            $stats['longest_coaster'] = $stmtLongest->fetchColumn() ?: '—';

            Response::success(['user' => $user, 'stats' => $stats]);
        } else {
            Response::notFound('No se encontró el usuario');
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

function updateAvatar()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $photoUrl = $input['photo_url'] ?? '';

    if (empty($photoUrl)) {
        Response::error('URL no válida');
    }

    global $db;
    try {
        $stmt = $db->prepare("
        UPDATE users SET profile_image = :photoUrl WHERE id = :id");
        $stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':photoUrl', $photoUrl, PDO::PARAM_STR);
        $stmt->execute();

        // Sincronizar con la sesión PHP para que el header lo cargue globalmente
        $_SESSION['profile_image'] = $photoUrl;

        Response::success();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Obtener Tops de Coasters ─────────────────────────────────────────────────
function getTopCoasters()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    global $db;
    try {
        $stmt = $db->prepare("
            SELECT 
                uc.rank_position, 
                c.id AS coaster_id, 
                c.coaster_name, 
                c.imagen_url,
                c.height,
                c.speed,
                c.inversions,
                c.coaster_length,
                c.opening_year,
                c.coaster_manufacter AS manufacter,
                c.coaster_model AS model,
                p.park_name,
                p.park_country AS country_name
            FROM user_credits uc
            JOIN coasters c ON uc.coaster_id = c.id
            JOIN parks p ON c.park_id = p.id
            WHERE uc.user_id = :uid
            ORDER BY uc.rank_position ASC
        ");
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $tops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['tops' => $tops]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Obtener Tops de Parques ──────────────────────────────────────────────────
function getTopParks()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    global $db;
    try {
        $stmt = $db->prepare("
            SELECT 
                up.rank_position, 
                p.id AS park_id, 
                p.park_name,
                p.park_country AS country_name,
                p.imagen_url,
                p.operating_coasters,
                p.stars
            FROM user_park_credits up
            JOIN parks p ON up.park_id = p.id
            WHERE up.user_id = :uid
            ORDER BY up.rank_position ASC
        ");
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $tops = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['tops' => $tops]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Guardar Top de Coasters ───────────────────────────────────────────────────
function saveTopCoasters()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $items = $body['items'] ?? [];

    if (!is_array($items)) {
        Response::error('Datos inválidos');
    }

    global $db;
    try {
        $db->beginTransaction();

        // Borrar top actual
        $del = $db->prepare("DELETE FROM user_credits WHERE user_id = :uid");
        $del->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $del->execute();

        // Insertar nuevo orden
        $ins = $db->prepare("
            INSERT INTO user_credits (user_id, coaster_id, rank_position)
            VALUES (:uid, :cid, :rank)
        ");
        foreach ($items as $rank => $item) {
            $ins->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $ins->bindValue(':cid', (int) $item['coaster_id'], PDO::PARAM_INT);
            $ins->bindValue(':rank', $rank + 1, PDO::PARAM_INT);
            $ins->execute();
        }

        $db->commit();
        Response::success(['message' => 'Top de coasters guardado']);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Guardar Top de Parques ────────────────────────────────────────────────────
function saveTopParks()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $items = $body['items'] ?? [];

    if (!is_array($items)) {
        Response::error('Datos inválidos');
    }

    global $db;
    try {
        $db->beginTransaction();

        $del = $db->prepare("DELETE FROM user_park_credits WHERE user_id = :uid");
        $del->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $del->execute();

        $ins = $db->prepare("
            INSERT INTO user_park_credits (user_id, park_id, rank_position)
            VALUES (:uid, :pid, :rank)
        ");
        foreach ($items as $rank => $item) {
            $ins->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $ins->bindValue(':pid', (int) $item['park_id'], PDO::PARAM_INT);
            $ins->bindValue(':rank', $rank + 1, PDO::PARAM_INT);
            $ins->execute();
        }

        $db->commit();
        Response::success(['message' => 'Top de parques guardado']);
    } catch (PDOException $e) {
        $db->rollBack();
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}

// ── Obtener parques del mapa (coaster credits + top de parques del usuario) ──
function getMapParks()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    global $db;
    try {
        // Une parques de los coaster credits Y los del top de parques del usuario
        $stmt = $db->prepare("
            SELECT
                p.id          AS park_id,
                p.park_name,
                p.park_location,
                p.park_country,
                p.imagen_url,
                COALESCE(cc.coaster_count, 0) AS coaster_count
            FROM (
                SELECT c.park_id
                FROM user_credits uc
                JOIN coasters c ON uc.coaster_id = c.id
                WHERE uc.user_id = :uid

                UNION

                SELECT park_id
                FROM user_park_credits
                WHERE user_id = :uid
            ) AS all_parks
            JOIN parks p ON p.id = all_parks.park_id
            LEFT JOIN (
                SELECT c.park_id, COUNT(uc.id) AS coaster_count
                FROM user_credits uc
                JOIN coasters c ON uc.coaster_id = c.id
                WHERE uc.user_id = :uid
                GROUP BY c.park_id
            ) AS cc ON cc.park_id = p.id
            WHERE p.park_name NOT IN ('Desconocido', 'Unknown')
            ORDER BY coaster_count DESC
        ");
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $parks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::success(['parks' => $parks]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}


// ── Obtener reseñas propias del usuario logueado ──────────────────────────────
function getMyReviews()
{
    $user_id = getUserId();
    if (!$user_id) {
        Response::unauthorized('No estás logueado');
    }

    global $db;
    try {
        $stmt = $db->prepare("
            SELECT 'coaster' AS type,
                   cr.id,
                   cr.coaster_id AS item_id,
                   c.coaster_name AS title,
                   p.park_name AS subtitle,
                   cr.note,
                   cr.review,
                   cr.created_at,
                   c.imagen_url,
                   (SELECT json_agg(json_build_object('tag', rt.tag, 'type', rt.type))
                        FROM review_tags rt WHERE rt.review_id = cr.id) AS tags
            FROM coaster_ratings cr
            JOIN coasters c ON cr.coaster_id = c.id
            JOIN parks    p ON c.park_id = p.id
            WHERE cr.user_id = :uid AND cr.is_hidden = FALSE

            UNION ALL

            SELECT 'park' AS type,
                   pr.id,
                   pr.park_id AS item_id,
                   p.park_name AS title,
                   p.park_country AS subtitle,
                   pr.note,
                   pr.review,
                   pr.created_at,
                   p.imagen_url,
                   (SELECT json_agg(json_build_object('tag', pt.tag, 'type', pt.type))
                        FROM park_review_tags pt WHERE pt.review_id = pr.id) AS tags
            FROM park_ratings pr
            JOIN parks p ON pr.park_id = p.id
            WHERE pr.user_id = :uid AND pr.is_hidden = FALSE

            ORDER BY created_at DESC
        ");
        $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decodificar los tags de JSON string a array
        foreach ($reviews as &$r) {
            $r['tags'] = $r['tags'] ? json_decode($r['tags'], true) : [];
        }
        unset($r);

        Response::success(['reviews' => $reviews]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        Response::error('Error interno del servidor. Por favor, inténtalo de nuevo.', 500);
    }
}
