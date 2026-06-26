<?php
    namespace Matchla\Services;

    use Matchla\Config\Database;

    // Haversine formülü ile, kullanıcının, sahalara olan 
    // mesafelerini hesaplayacağız. 

    class MatchService {
        public function getNearbyMatches(float $lat, float $lng, bool $isPremium): array {
            $pdo = Database::getInstance()->getPDO();

            $radius = $isPremium ? 100 : 50;

            // kullanıcının yarıçapına göre yakınında bulunan açık veya dolmuş
            // tüm maçları görüntüle
            $stmt = $pdo->prepare(
                'SELECT m.*, (
                    6371 * ACOS(
                    COS(RADIANS(:lat)) * COS(RADIANS(f.latitude)) * 
                    COS(RADIANS(f.longitude) - RADIANS(:lng)) +
                    SIN(RADIANS(:lat)) * SIN(RADIANS(f.latitude))
                    )
                ) AS distance
                FROM matches m
                JOIN fields f ON m.field_id = f.id 
                WHERE m.match_status IN ("open", "full")
                HAVING distance <= :radius
                ORDER BY distance ASC'
            );

            $stmt->execute([
                ":lat" => $lat,
                ":lng" => $lng,
                ":radius" => $radius
            ]);

            $matches = $stmt->fetchAll();

            return $matches;
        }
    }