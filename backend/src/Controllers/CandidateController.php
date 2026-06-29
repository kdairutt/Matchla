<?php
    namespace Matchla\Controllers;
    use Matchla\Config\Database;
    use Matchla\Services\CandidateService;

    class CandidateController {
        
        private \PDO $pdo;
        public function __construct() {
            $this->pdo = Database::getInstance()->getPDO();
        }

        public function apply(string $matchId): void {
            $authUser = $_REQUEST["auth_user"];
            $authUserId = $authUser->id;

            $data = json_decode(file_get_contents("php://input"), true);
            $applicationNote = $data["application_note"] ?? null;

            // maç var mı?
            try {
                $stmt = $this->pdo->prepare("SELECT id
                FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);
                
                if(empty($stmt->fetchColumn())) {
                    http_response_code(404);
                    echo json_encode(["error" => "match not found"]);
                    return;
                }

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
            
            // oyuncu, matchmaker'ın belirlediği gereksinimlere uyuyor mu?
            $service = new CandidateService();

            $playerSatisfies = $service->checkUp($authUserId, $matchId);

            if(!$playerSatisfies) {
                http_response_code(422);
                echo json_encode(["error" => "does not satisfy requirements"]);
                return;
            }
            
            try {

                // zaten başvurmuş mu?
                $stmt = $this->pdo->prepare("SELECT match_id, player_id
                FROM candidates WHERE match_id = ? and player_id = ?");
                $stmt->execute([$matchId, $authUserId]);

                if(!empty($stmt->fetch())) {
                    http_response_code(422);
                    echo json_encode(["error" => "already applied"]);
                    return;
                }

                // ekle
                $stmt = $this->pdo->prepare("INSERT INTO candidates (player_id, match_id, application_note)
                VALUES (?, ?, ?)");

                $stmt->execute([$authUserId, $matchId, $applicationNote]);

                if($stmt->rowCount() < 1) {
                    http_response_code(500);
                    echo json_encode(["error" => "server error"]);
                    return;
                }

                http_response_code(201);
                echo json_encode([
                    "message" => "candidate created successfully",
                    "candidate_data" => [
                        "candidate_id" => $this->pdo->lastInsertId(),
                        "match_id" => $matchId,
                        "application_note" => $applicationNote
                    ]
                ]);

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }

        
    }