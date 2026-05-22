<?php
/**
 * api/php/recommendations.php
 * Motor de Recomendación Inteligente Autónomo ("Zero-Click")
 *
 * Acciones GET:
 *   ?action=get                → Devuelve las 3 recomendaciones (cacheadas o generadas)
 *   ?action=refresh            → Fuerza regeneración aunque el caché sea válido
 *
 * Acciones POST:
 *   ?action=book               → Pre-configura el pedido desde una recomendación
 *   ?action=create_trip_session → Crea una Stripe Checkout Session para el viaje
 *   ?action=confirm            → Verifica el pago Stripe y genera evento en la agenda
 */

require_once __DIR__ . '/utils/SessionManager.php';
require_once __DIR__ . '/../database/db_conexion.php';
require_once __DIR__ . '/utils/Response.php';

// ── Cargar Stripe SDK ────────────────────────────────────────────────────────
$autoloadPath = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    $autoloadPath = __DIR__ . '/../../../vendor/autoload.php';
}
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// ── Leer .env ────────────────────────────────────────────────────────────────
function loadEnvRec(): array
{
    $envFile = __DIR__ . '/../../.env';
    if (!file_exists($envFile))
        $envFile = __DIR__ . '/../../../.env';
    $env = [];
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '='))
                continue;
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v);
        }
    }
    return $env;
}
$_rcwEnv = loadEnvRec();
if (!empty($_rcwEnv['STRIPE_SECRET_KEY']) && class_exists('\Stripe\Stripe')) {
    \Stripe\Stripe::setApiKey($_rcwEnv['STRIPE_SECRET_KEY']);
}

header('Content-Type: application/json');

$uid = $_SESSION['firebase_uid'] ?? null;
if (!$uid) {
    Response::error('No autenticado', 401);
    exit;
}

$db = new DBConexion();
$action = $_GET['action'] ?? 'get';
$method = $_SERVER['REQUEST_METHOD'];

// ── Resuelve el user_id numérico desde firebase_uid ───────────────────────────
$stmtU = $db->prepare("SELECT id FROM users WHERE firebase_uid = ? LIMIT 1");
$stmtU->execute([$uid]);
$userId = (int) ($stmtU->fetchColumn() ?: 0);
if (!$userId) {
    Response::error('Usuario no encontrado', 404);
    exit;
}

match (true) {
    ($action === 'get' && $method === 'GET') => getRecommendations($db, $userId),
    ($action === 'refresh' && $method === 'GET') => getRecommendations($db, $userId, true),
    ($action === 'book' && $method === 'POST') => bookRecommendation($db, $userId),
    ($action === 'create_trip_session' && $method === 'POST') => createTripStripeSession($db, $userId),
    ($action === 'confirm' && $method === 'POST') => confirmAndSchedule($db, $userId),
    default => Response::error("Acción no soportada: $action", 400),
};

// ═════════════════════════════════════════════════════════════════════════════
// GET RECOMMENDATIONS — devuelve caché o genera nuevas
// ═════════════════════════════════════════════════════════════════════════════
function getRecommendations(DBConexion $db, int $userId, bool $forceRefresh = false): void
{
    // 1. Intentar caché válido
    if (!$forceRefresh) {
        $cached = fetchCached($db, $userId);
        if (!empty($cached)) {
            Response::success(['data' => $cached, 'source' => 'cache']);
            return;
        }
    }

    // 2. Construir perfil del usuario
    $profile = buildUserProfile($db, $userId);

    // 3. Generar recomendaciones
    $recs = generateRecommendations($db, $profile);

    // 4. Guardar en caché (borrar las antiguas primero)
    $db->prepare("DELETE FROM ai_recommendations WHERE user_id = ?")->execute([$userId]);
    foreach ($recs as $rec) {
        saveRecommendation($db, $userId, $rec);
    }

    // 5. Devolver
    Response::success(['data' => $recs, 'source' => 'generated']);
}

// ── Caché ─────────────────────────────────────────────────────────────────────
function fetchCached(DBConexion $db, int $userId): array
{
    $stmt = $db->prepare(
        "SELECT * FROM ai_recommendations
         WHERE user_id = ? AND expires_at > NOW()
         ORDER BY affinity_score DESC
         LIMIT 3"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── Perfil del usuario ────────────────────────────────────────────────────────
function buildUserProfile(DBConexion $db, int $userId): array
{
    // Datos básicos
    $stmt = $db->prepare(
        "SELECT id, username, city, country, favorite_coaster
         FROM users WHERE id = ?"
    );
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Velocidad máxima de coasters montadas (intensidad preferida)
    $stmt = $db->prepare(
        "SELECT COALESCE(MAX(c.speed), 0) as max_speed
         FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id
         WHERE uc.user_id = ?"
    );
    $stmt->execute([$userId]);
    $maxSpeed = (float) $stmt->fetchColumn();

    // Parques ya visitados
    $stmt = $db->prepare(
        "SELECT DISTINCT park_id FROM user_park_credits WHERE user_id = ?
         UNION
         SELECT DISTINCT c.park_id FROM user_credits uc
         JOIN coasters c ON uc.coaster_id = c.id WHERE uc.user_id = ?"
    );
    $stmt->execute([$userId, $userId]);
    $visitedIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    // País más frecuentado
    $stmt = $db->prepare(
        "SELECT p.park_country, COUNT(*) as cnt
         FROM parks p
         JOIN user_park_credits upc ON p.id = upc.park_id
         WHERE upc.user_id = ? AND p.park_country IS NOT NULL
         GROUP BY p.park_country ORDER BY cnt DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    $favCountry = $stmt->fetchColumn() ?: ($user['country'] ?? null);

    // Gasto medio en pedidos completados
    $stmt = $db->prepare(
        "SELECT COALESCE(AVG(price), 50) FROM pedidos
         WHERE user_id = ? AND status = 'confirmado'"
    );
    $stmt->execute([$userId]);
    $avgSpend = (float) $stmt->fetchColumn();

    // Fabricante favorito
    $stmt = $db->prepare(
        "SELECT c.coaster_manufacter, COUNT(*) as cnt
         FROM user_credits uc JOIN coasters c ON uc.coaster_id = c.id
         WHERE uc.user_id = ? AND c.coaster_manufacter IS NOT NULL AND c.coaster_manufacter != ''
         GROUP BY c.coaster_manufacter ORDER BY cnt DESC LIMIT 1"
    );
    $stmt->execute([$userId]);
    $favManu = $stmt->fetchColumn() ?: null;

    // Top parques del usuario (para justificación)
    $stmt = $db->prepare(
        "SELECT p.park_name FROM user_park_credits upc
         JOIN parks p ON upc.park_id = p.id
         WHERE upc.user_id = ? AND upc.rank_position > 0
         ORDER BY upc.rank_position ASC LIMIT 1"
    );
    $stmt->execute([$userId]);
    $topPark = $stmt->fetchColumn() ?: null;

    // Total coasters montadas
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_credits WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalCredits = (int) $stmt->fetchColumn();

    // Número de viajes
    $stmt = $db->prepare("SELECT COUNT(*) FROM trips WHERE user_id = ?");
    $stmt->execute([$userId]);
    $tripsCount = (int) $stmt->fetchColumn();

    return [
        'user_id' => $userId,
        'username' => $user['username'] ?? 'Usuario',
        'city' => $user['city'] ?? null,
        'country' => $user['country'] ?? null,
        'fav_coaster' => $user['favorite_coaster'] ?? null,
        'max_speed' => $maxSpeed,
        'visited_ids' => $visitedIds,
        'fav_country' => $favCountry,
        'avg_spend' => max($avgSpend, 30),
        'fav_manu' => $favManu,
        'top_park' => $topPark,
        'total_credits' => $totalCredits,
        'trips_count' => $tripsCount,
    ];
}

// ── Motor de recomendación ────────────────────────────────────────────────────
function generateRecommendations(DBConexion $db, array $profile): array
{
    // Candidatos: parques que aún NO ha visitado
    $placeholders = !empty($profile['visited_ids'])
        ? implode(',', array_fill(0, count($profile['visited_ids']), '?'))
        : '0';

    $params = $profile['visited_ids'];

    $stmt = $db->prepare(
        "SELECT id, park_name, park_country, imagen_url, stars,
                precio_entrada, num_coasters, operating_coasters,
                latitude, longitude
         FROM parks
         WHERE id NOT IN ($placeholders)
           AND operating_coasters > 0
         ORDER BY stars DESC, operating_coasters DESC
         LIMIT 50"
    );
    $stmt->execute($params);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        // Si ya los ha visitado todos, abrir a todos
        $stmt = $db->query(
            "SELECT id, park_name, park_country, imagen_url, stars,
                    precio_entrada, num_coasters, operating_coasters
             FROM parks WHERE operating_coasters > 0
             ORDER BY stars DESC LIMIT 50"
        );
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Puntuar cada candidato
    $scored = [];
    foreach ($candidates as $park) {
        $score = scorePark($park, $profile, $db);
        $park['_score'] = $score;
        $scored[] = $park;
    }

    // Ordenar por puntuación
    usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);

    // Tomar top 2 (match) + 1 wildcard (diferente al perfil)
    $matches = array_slice($scored, 0, 2);
    $wildcards = array_slice(array_reverse($scored), 0, 10);
    // Wildcard: el mejor puntuado de los últimos (diferente país al fav)
    $wildcard = null;
    foreach ($wildcards as $w) {
        if ($w['park_country'] !== $profile['fav_country']) {
            $wildcard = $w;
            break;
        }
    }
    if (!$wildcard && isset($wildcards[0])) {
        $wildcard = $wildcards[0];
    }

    $result = [];
    foreach ($matches as $park) {
        $result[] = buildRecItem($park, $profile, 'match');
    }
    if ($wildcard) {
        $result[] = buildRecItem($wildcard, $profile, 'wildcard');
    }

    return $result;
}

// ── Puntuación de un parque respecto al perfil ────────────────────────────────
function scorePark(array $park, array $profile, DBConexion $db): float
{
    $score = 0.0;

    // Valoración global del parque (hasta 40 pts)
    $score += (float) ($park['stars'] ?? 0) * 8.0;

    // Mismo país que el favorito del usuario (hasta 20 pts)
    if ($park['park_country'] && $park['park_country'] === $profile['fav_country']) {
        $score += 20.0;
    }

    // País de residencia del usuario (10 pts)
    if ($park['park_country'] && $park['park_country'] === $profile['country']) {
        $score += 10.0;
    }

    // Número de coasters operativas — premia parques grandes (hasta 15 pts)
    $score += min((float) ($park['operating_coasters'] ?? 0) * 0.5, 15.0);

    // Precio dentro del presupuesto medio del usuario (hasta 10 pts)
    $parkPrice = (float) ($park['precio_entrada'] ?? 0);
    if ($parkPrice > 0 && $parkPrice <= $profile['avg_spend'] * 2) {
        $score += 10.0;
    }

    // El parque tiene coasters del fabricante favorito (5 pts)
    if ($profile['fav_manu']) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM coasters
             WHERE park_id = ? AND coaster_manufacter LIKE ?"
        );
        $stmt->execute([$park['id'], $profile['fav_manu']]);
        if ($stmt->fetchColumn() > 0) {
            $score += 5.0;
        }
    }

    return $score;
}

// ── Construye el item de recomendación con hotel y razón ─────────────────────
function buildRecItem(array $park, array $profile, string $type): array
{
    $avgSpend = $profile['avg_spend'];
    $parkPrice = (float) ($park['precio_entrada'] ?? 50);
    $duration = ($profile['trips_count'] > 2) ? 3 : 2;

    // Hotel: entre 60-80% del gasto diario restante
    $dailyBudget = max($avgSpend / $duration, 40);
    $hotelNight = round($dailyBudget * 0.65, 2);
    $hotelStars = $hotelNight < 60 ? 2 : ($hotelNight < 120 ? 3 : 4);
    $hotelNames = [
        2 => "Ibis Styles {$park['park_country']}",
        3 => "Holiday Inn {$park['park_name']}",
        4 => "Marriott Premium {$park['park_country']}",
    ];
    $hotelName = $hotelNames[$hotelStars] ?? "Hotel cerca de {$park['park_name']}";

    // Razón personalizada
    $reason = buildReason($park, $profile, $type);

    return [
        'park_id' => $park['id'],
        'park_name' => $park['park_name'],
        'park_country' => $park['park_country'] ?? '—',
        'park_image_url' => $park['imagen_url'] ?? null,
        'stars' => $park['stars'] ?? 0,
        'price_estimate' => $parkPrice,
        'hotel_name' => $hotelName,
        'hotel_stars' => $hotelStars,
        'hotel_price_night' => $hotelNight,
        'duration_days' => $duration,
        'affinity_score' => round($park['_score'] / 100, 4),
        'reason' => $reason,
        'rec_type' => $type,
    ];
}

// ── Genera la frase justificativa ─────────────────────────────────────────────
function buildReason(array $park, array $profile, string $type): string
{
    $name = $park['park_name'];
    $country = $park['park_country'] ?? '';

    if ($type === 'wildcard') {
        return "💡 Algo nuevo para ti: {$name} está en {$country} y su comunidad lo valora muy alto. ¡Sal de tu zona de confort!";
    }

    // Razones basadas en datos reales del perfil
    if ($profile['top_park'] && $park['park_country'] === $profile['fav_country']) {
        return "⭐ Te recomendamos {$name} porque disfrutaste de {$profile['top_park']} y ambos están en {$country}.";
    }
    if ($profile['fav_manu']) {
        return "🎢 {$name} tiene coasters de {$profile['fav_manu']}, tu fabricante favorito según tu historial.";
    }
    if ($profile['total_credits'] > 20) {
        return "🏆 Con {$profile['total_credits']} coasters en tu historial, {$name} tiene {$park['operating_coasters']} atracciones que aún no has probado.";
    }
    return "✨ {$name} tiene una valoración de {$park['stars']}/10 en la comunidad y encaja con tus gustos.";
}

// ── Guarda una recomendación en caché ─────────────────────────────────────────
function saveRecommendation(DBConexion $db, int $userId, array $rec): void
{
    $stmt = $db->prepare(
        "INSERT INTO ai_recommendations
         (user_id, park_id, park_name, park_country, park_image_url,
          price_estimate, hotel_name, hotel_stars, hotel_price_night,
          duration_days, affinity_score, reason, rec_type)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $userId,
        $rec['park_id'],
        $rec['park_name'],
        $rec['park_country'],
        $rec['park_image_url'],
        $rec['price_estimate'],
        $rec['hotel_name'],
        $rec['hotel_stars'],
        $rec['hotel_price_night'],
        $rec['duration_days'],
        $rec['affinity_score'],
        $rec['reason'],
        $rec['rec_type'],
    ]);
}

// ═════════════════════════════════════════════════════════════════════════════
// POST /book — Pre-configura carrito con la recomendación
// ═════════════════════════════════════════════════════════════════════════════
function bookRecommendation(DBConexion $db, int $userId): void
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $parkId = (int) ($body['park_id'] ?? 0);
    $qty = max(1, (int) ($body['quantity'] ?? 1));

    if (!$parkId) {
        Response::error('park_id requerido', 422);
        return;
    }

    try {
        // Obtener precio entrada del parque
        $stmtP = $db->prepare("SELECT park_name, precio_entrada FROM parks WHERE id = ?");
        $stmtP->execute([$parkId]);
        $park = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$park) {
            Response::error('Parque no encontrado', 404);
            return;
        }

        $unitPrice = (float) ($park['precio_entrada'] ?? 50);
        $total = round($unitPrice * $qty, 2);

        // Insertar o actualizar en carrito (pedidos en estado "pendiente")
        $stmtExist = $db->prepare(
            "SELECT id FROM pedidos WHERE user_id = ? AND park_id = ? AND status = 'pendiente' LIMIT 1"
        );
        $stmtExist->execute([$userId, $parkId]);
        $existing = $stmtExist->fetchColumn();

        // Fecha por defecto: hoy + 7 días para viajes sugeridos
        $visitDate = date('Y-m-d', strtotime('+7 days'));

        if ($existing) {
            $db->prepare("UPDATE pedidos SET quantity = ?, price = ?, unit_price = ?, visit_date = ? WHERE id = ?")
                ->execute([$qty, $total, $unitPrice, $visitDate, $existing]);
            $orderId = $existing;
        } else {
            $db->prepare(
                "INSERT INTO pedidos (user_id, park_id, quantity, price, unit_price, ticket_type, visit_date, status)
                 VALUES (?,?,?,?,?,'entrada',?,'pendiente')"
            )->execute([$userId, $parkId, $qty, $total, $unitPrice, $visitDate]);
            $orderId = $db->lastInsertId();
        }

        Response::success([
            'data' => [
                'order_id' => $orderId,
                'park_name' => $park['park_name'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total' => $total,
                'message' => 'Carrito pre-configurado correctamente',
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error bookRecommendation: " . $e->getMessage());
        Response::error('Error al preparar el carrito', 500);
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// POST /create_trip_session — Crea una Stripe Checkout Session para un viaje IA
// ═════════════════════════════════════════════════════════════════════════════
function createTripStripeSession(DBConexion $db, int $userId): void
{
    global $_rcwEnv;

    if (empty($_rcwEnv['STRIPE_SECRET_KEY']) || !class_exists('\Stripe\Stripe')) {
        Response::error('Stripe no configurado', 500);
        return;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $parkId = (int) ($body['park_id'] ?? 0);
    $orderId = (int) ($body['order_id'] ?? 0);
    $quantity = max(1, (int) ($body['quantity'] ?? 1));
    $durationDays = max(1, (int) ($body['duration_days'] ?? 2));
    $startDate = $body['start_date'] ?? date('Y-m-d', strtotime('+14 days'));

    if (!$parkId || !$orderId) {
        Response::error('park_id y order_id son requeridos', 422);
        return;
    }

    // Obtener datos del parque y precio
    $stmtP = $db->prepare("SELECT park_name, precio_entrada FROM parks WHERE id = ?");
    $stmtP->execute([$parkId]);
    $park = $stmtP->fetch(PDO::FETCH_ASSOC);
    if (!$park) {
        Response::error('Parque no encontrado', 404);
        return;
    }

    $unitPrice = max(0.50, (float) ($park['precio_entrada'] ?? 50));
    $amountCents = (int) round($unitPrice * 100);
    $label = "Entrada — {$park['park_name']} ({$quantity} pers.)";

    // Construir URLs de retorno
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($script, '/RollerCoasterWorld/')) {
        $base = preg_replace('#/RollerCoasterWorld/.*$#', '/RollerCoasterWorld', $script) ?? '';
    } else {
        $base = '';
    }
    $baseUrl = $proto . '://' . $host . $base;

    $successUrl = $baseUrl . '/web/views/public/trips/trip_generator.php'
        . '?payment=success'
        . '&session_id={CHECKOUT_SESSION_ID}'
        . '&order_id=' . $orderId
        . '&park_id=' . $parkId
        . '&duration_days=' . $durationDays
        . '&start_date=' . urlencode($startDate);
    $cancelUrl = $baseUrl . '/web/views/public/trips/trip_generator.php?payment=cancel';

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'unit_amount' => $amountCents,
                        'product_data' => ['name' => $label],
                    ],
                    'quantity' => $quantity,
                ]
            ],
            'metadata' => [
                'order_id' => (string) $orderId,
                'park_id' => (string) $parkId,
                'user_id' => (string) $userId,
                'duration_days' => (string) $durationDays,
                'start_date' => $startDate,
                'source' => 'trip_generator',
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        Response::success([
            'url' => $session->url,
            'session_id' => $session->id,
        ]);
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('[createTripStripeSession] ' . $e->getMessage());
        Response::error('Error al crear la sesión de pago. Inténtalo de nuevo.', 500);
    }
}

// ═════════════════════════════════════════════════════════════════════════════
// POST /confirm — Verifica pago Stripe y genera evento en la Agenda de Parques
// ═════════════════════════════════════════════════════════════════════════════
function confirmAndSchedule(DBConexion $db, int $userId): void
{
    global $_rcwEnv;

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $orderId = (int) ($body['order_id'] ?? 0);
    $parkId = (int) ($body['park_id'] ?? 0);
    $days = max(1, (int) ($body['duration_days'] ?? 2));
    $start = $body['start_date'] ?? date('Y-m-d', strtotime('+14 days'));
    $stripeSessionId = trim($body['stripe_session_id'] ?? '');

    if (!$orderId || !$parkId) {
        Response::error('order_id y park_id son requeridos', 422);
        return;
    }

    // ── Verificar pago con Stripe si se proporcionó session_id ───────────────
    if ($stripeSessionId !== '' && !empty($_rcwEnv['STRIPE_SECRET_KEY']) && class_exists('\Stripe\Stripe')) {
        // Evitar doble procesamiento
        if (isset($_SESSION['rcw_trip_stripe_processed'][$stripeSessionId])) {
            // Ya fue procesado: devolver los datos guardados
            $saved = $_SESSION['rcw_trip_stripe_processed'][$stripeSessionId];
            Response::success(['data' => $saved]);
            return;
        }

        try {
            $session = \Stripe\Checkout\Session::retrieve($stripeSessionId);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[confirmAndSchedule] Stripe retrieve: ' . $e->getMessage());
            Response::error('No se pudo verificar el pago con Stripe.', 500);
            return;
        }

        if ($session->payment_status !== 'paid') {
            Response::error('El pago no está completado (status: ' . $session->payment_status . ')', 402);
            return;
        }
    }

    try {
        $db->beginTransaction();

        // 1. Marcar pedido como confirmado (guardar stripe_session_id si existe)
        if ($stripeSessionId !== '') {
            $db->prepare("UPDATE pedidos SET status = 'confirmado', stripe_session_id = ? WHERE id = ? AND user_id = ?")
                ->execute([$stripeSessionId, $orderId, $userId]);
        } else {
            $db->prepare("UPDATE pedidos SET status = 'confirmado' WHERE id = ? AND user_id = ?")
                ->execute([$orderId, $userId]);
        }

        // 2. Obtener datos del parque
        $stmtP = $db->prepare("SELECT park_name, park_country, imagen_url FROM parks WHERE id = ?");
        $stmtP->execute([$parkId]);
        $park = $stmtP->fetch(PDO::FETCH_ASSOC);

        if (!$park) {
            $db->rollBack();
            Response::error('Parque no encontrado', 404);
            return;
        }

        // 3. Obtener top coaster del parque para itinerario
        $stmtC = $db->prepare(
            "SELECT coaster_name FROM coasters WHERE park_id = ? ORDER BY stars DESC LIMIT 1"
        );
        $stmtC->execute([$parkId]);
        $topCoaster = $stmtC->fetchColumn() ?: 'la coaster principal';

        // 4. Calcular fechas
        $startDate = date('Y-m-d', strtotime($start));
        $endDate = date('Y-m-d', strtotime($start . " +{$days} days"));

        // 5. Generar título e itinerario
        $tripTitle = "Viaje a {$park['park_name']}";
        $itinerary = buildItinerary($park['park_name'], $topCoaster, $days);

        // 6. Insertar en trips
        $stmtT = $db->prepare(
            "INSERT INTO trips (user_id, title, description, start_date, end_date, cover_image, trip_type, status)
             VALUES (?,?,?,?,?,?,?, 'planned')"
        );
        $desc = "Viaje generado automáticamente por el motor de recomendación de RCW.";
        $stmtT->execute([$userId, $tripTitle, $desc, $startDate, $endDate, $park['imagen_url'], 'ai']);
        $tripId = $db->lastInsertId();

        // 7. Asociar el parque a cada día del viaje
        for ($i = 0; $i < $days; $i++) {
            $visitDate = date('Y-m-d', strtotime($startDate . " +{$i} days"));
            $db->prepare(
                "INSERT INTO trip_parks (trip_id, park_id, visit_date, visit_order) VALUES (?, ?, ?, ?)"
            )->execute([$tripId, $parkId, $visitDate, $i + 1]);
        }

        $db->commit();

        $result = [
            'trip_id' => $tripId,
            'trip_title' => $tripTitle,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'itinerary' => $itinerary,
            'message' => '¡Reserva confirmada! Tu agenda ha sido actualizada.',
        ];

        // Guardar en sesión para evitar doble procesamiento
        if ($stripeSessionId !== '') {
            if (!isset($_SESSION['rcw_trip_stripe_processed'])) {
                $_SESSION['rcw_trip_stripe_processed'] = [];
            }
            $_SESSION['rcw_trip_stripe_processed'][$stripeSessionId] = $result;
        }

        Response::success(['data' => $result]);

    } catch (Exception $e) {
        try {
            $db->rollBack();
        } catch (Exception $re) {
        }
        error_log("[confirmAndSchedule] " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        Response::error('Error al confirmar la reserva: ' . $e->getMessage(), 500);
    }
}

// ── Itinerario sugerido basado en duración ────────────────────────────────────
function buildItinerary(string $parkName, string $topCoaster, int $days): array
{
    $itinerary = [];
    for ($d = 1; $d <= $days; $d++) {
        if ($d === 1) {
            $itinerary[] = [
                'day' => "Día 1",
                'title' => "Llegada y primera jornada en {$parkName}",
                'items' => [
                    "Check-in en el hotel y descanso",
                    "Primera visita al parque por la tarde",
                    "No te pierdas {$topCoaster}, la atracción estrella",
                    "Cena en los restaurantes del parque",
                ],
            ];
        } elseif ($d === $days) {
            $itinerary[] = [
                'day' => "Día {$d}",
                'title' => "Últimas atracciones y regreso",
                'items' => [
                    "Jornada completa de madrugada para evitar colas",
                    "Repite tus atracciones favoritas",
                    "Compras en las tiendas oficiales del parque",
                    "Regreso al hogar",
                ],
            ];
        } else {
            $itinerary[] = [
                'day' => "Día {$d}",
                'title' => "Jornada completa en {$parkName}",
                'items' => [
                    "Desayuno en el hotel incluido",
                    "Apertura del parque — aprovecha las primeras horas sin colas",
                    "Zona de thrills y coasters principales",
                    "Tarde: zonas temáticas y espectáculos",
                ],
            ];
        }
    }
    return $itinerary;
}
