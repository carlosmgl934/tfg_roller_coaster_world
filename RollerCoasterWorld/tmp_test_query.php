<?php
require_once 'api/database/db_conexion.php';
$db = new DBConexion();

$currentUserId = 74; // Testing for user 74 (has friend 94 with tops)
$filterFriends = true;

try {
    $where = "upc.rank_position IS NOT NULL AND upc.rank_position > 0";
    $join = "";
    $orderBy = "username ASC";
    $limit = 10;
    $params = [];

    if ($filterFriends) {
        $join = "JOIN friendship f ON ((f.solicitante_id = :my_id AND f.solicitada_id = u.id) OR (f.solicitada_id = :my_id AND f.solicitante_id = u.id))";
        $where .= " f.estado_solicitud = 'ACEPTADA'"; // Correcting: I forgot AND here if $where was empty, but it's not empty here
        $params[':my_id'] = $currentUserId;
    }

    $sql = "
        WITH UserTops AS (
            SELECT 
                upc.user_id,
                u.username,
                u.profile_image,
                upc.park_id,
                p.park_name,
                p.park_country,
                p.imagen_url,
                upc.rank_position,
                ROW_NUMBER() OVER(PARTITION BY upc.user_id ORDER BY upc.rank_position ASC) as rn
            FROM user_park_credits upc
            JOIN users u ON upc.user_id = u.id
            JOIN parks p ON upc.park_id = p.id
            $join
            WHERE $where
        )
        SELECT user_id, username, profile_image,
               json_agg(
                   json_build_object(
                       'park_id', park_id,
                       'park_name', park_name,
                       'park_country', park_country,
                       'imagen_url', imagen_url,
                       'rank_position', rank_position
                   ) ORDER BY rank_position ASC
               ) as top_parks
        FROM UserTops
        WHERE rn <= 5
        GROUP BY user_id, username, profile_image
        ORDER BY $orderBy
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "SQL worked, found " . count($users) . " users for ID $currentUserId\n";
    foreach ($users as $u) {
        echo "User: " . $u['username'] . " (ID: " . $u['user_id'] . ")\n";
    }

} catch (Exception $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
