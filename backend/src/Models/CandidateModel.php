<?php
    namespace Matchla\Models;

    class CandidateModel extends BaseModel {
        protected string $table = "candidates";

        protected array $fillable = [
            "player_id",
            "match_id",
            "application_note",
        ];

        protected array $updatable = [
            "player_id",
            "match_id",
            "status",
            "application_note",
            "attended",
        ];

        public function findAllAcceptedMatches(string $playerId): array {
            $stmt = $this->pdo->prepare("SELECT m.started_at, m.ended_at
                FROM candidates c
                JOIN matches m ON m.id = c.match_id AND m.match_status = 'open'
                WHERE c.player_id = ? AND c.status = 'accepted'"
            );

            $stmt->execute([$playerId]);

            return $stmt->fetchAll();
        }

        public function findAllCandidatesOf(string $matchId): array {
           $stmt = $this->pdo->prepare("SELECT c.id AS candidate_id, c.status, c.application_note, c.created_at,
                p.id AS player_id, p.name, p.surname, p.general_skill_point, p.loyalty_point FROM 
                candidates c
                JOIN players p ON p.id = c.player_id
                WHERE c.match_id = ? ORDER BY c.created_at DESC");
            
            $stmt->execute([$matchId]);
            return $stmt->fetchAll(); 
        }
    }