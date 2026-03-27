<?php
require_once __DIR__ . '/../../database/db_conexion.php';

class StatsHelper {
    
    /**
     * Recalcula y actualiza las estadísticas de un parque:
     * - num_coasters: total de montañas rusas
     * - operating_coasters: total de montañas rusas con estado 'Operating' o 'Operativa'
     * - stars: promedio de valoraciones en park_ratings
     */
    public static function updateParkStats(int $parkId): void {
        if ($parkId <= 0) return;
        
        $db = new DBConexion();
        
        try {
            // 1. Contar montañas rusas (totales y operativas)
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(*) FILTER (WHERE coaster_status IN ('Operating', 'Operativa', 'Abierta')) as operating
                FROM coasters 
                WHERE park_id = :park_id
            ");
            $stmt->execute([':park_id' => $parkId]);
            $counts = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // 2. Calcular promedio de estrellas y reviews_count
            $stmtStats = $db->prepare("
                SELECT 
                    AVG(note) as avg_stars,
                    COUNT(*) as count_reviews
                FROM park_ratings 
                WHERE park_id = :park_id
            ");
            $stmtStats->execute([':park_id' => $parkId]);
            $rowStats = $stmtStats->fetch(PDO::FETCH_ASSOC);
            $avgStars = $rowStats['avg_stars'] ?? 0;
            $countReviews = $rowStats['count_reviews'] ?? 0;
            
            // 3. Actualizar la tabla parks
            $update = $db->prepare("
                UPDATE parks 
                SET num_coasters = :total,
                    operating_coasters = :operating,
                    stars = :stars,
                    reviews_count = :reviews_count
                WHERE id = :park_id
            ");
            $update->execute([
                ':total'         => $counts['total'] ?? 0,
                ':operating'     => $counts['operating'] ?? 0,
                ':stars'         => round((float)$avgStars, 2),
                ':reviews_count' => (int)$countReviews,
                ':park_id'       => $parkId
            ]);
            
        } catch (Exception $e) {
            error_log("Error en StatsHelper::updateParkStats para ID $parkId: " . $e->getMessage());
        }
    }
    
    /**
     * Recalcula y actualiza la valoración de estrellas de una montaña rusa
     */
    public static function updateCoasterStats(int $coasterId): void {
        if ($coasterId <= 0) return;
        
        $db = new DBConexion();
        
        try {
            $stmt = $db->prepare("
                SELECT 
                    AVG(note) as avg_stars,
                    COUNT(*) as count_reviews
                FROM coaster_ratings 
                WHERE coaster_id = :coaster_id
            ");
            $stmt->execute([':coaster_id' => $coasterId]);
            $rowStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $avgStars = $rowStats['avg_stars'] ?? 0;
            $countReviews = $rowStats['count_reviews'] ?? 0;
            
            $update = $db->prepare("
                UPDATE coasters 
                SET stars = :stars,
                    reviews_count = :reviews_count
                WHERE id = :coaster_id
            ");
            $update->execute([
                ':stars'         => round((float)$avgStars, 2),
                ':reviews_count' => (int)$countReviews,
                ':coaster_id'    => $coasterId
            ]);
            
        } catch (Exception $e) {
            error_log("Error en StatsHelper::updateCoasterStats para ID $coasterId: " . $e->getMessage());
        }
    }
}
