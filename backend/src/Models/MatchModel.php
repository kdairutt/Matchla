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