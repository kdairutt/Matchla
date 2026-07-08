<?php
    namespace Matchla\Models;

    class MatchModel extends BaseModel {

        protected string $table = "matches";

        protected array $fillable = [
            "matchmaker_id",
            "sports_type_id",
            "field_id",
            "started_at",
            "ended_at",
            "target_participant", 
            "min_player_point",
            "max_player_point",
            "only_licensed_allowed",
            "match_status",
            "description",
        ];

        protected array $updatable = [
            "started_at",
            "ended_at", 
            "target_participant",
            "min_player_point", 
            "max_player_point", 
            "only_licensed_allowed", 
            "description"
        ];

        // kullanıcının yarıçapına göre yakınında bulunan açık veya dolmuş tüm maçları görüntüle
        public function findNearby(float $lat, float $lng, float $radius): array {
            $stmt = $this->pdo->prepare(
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
        
        public function cancel(string $matchId): bool {
            $stmt = $this->pdo->prepare("UPDATE matches SET match_status = ? WHERE id = ?");
            $stmt->execute(["cancelled", $matchId]);

            return $stmt->rowCount() > 0;
        }

        public function getMatchmakerId(string $matchId): ?string {
            $stmt = $this->pdo->prepare("SELECT matchmaker_id
            FROM matches WHERE id = ?");

            $stmt->execute([$matchId]);

            $result = $stmt->fetchColumn();

            return $result === false ? null : $result;
        }
    }