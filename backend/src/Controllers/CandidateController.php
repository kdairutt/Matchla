<?php
    namespace Matchla\Controllers;
    use Matchla\Config\Database;
    use Matchla\Services\CandidateService;

    class CandidateController {
        
        private \PDO $pdo;
        public function __construct() {
            $this->pdo = Database::getInstance()->getPDO();
        }

        private function getData(string $candidateId, string $matchId): array {
            $data = [];

            try {
                // zaman çakışmasının kontrolü için gereken, kullanıcının kabul aldığı tüm maçların
                // kontrolünü yapmaya yarayacak sorgu
                $stmt = $this->pdo->prepare("SELECT
                m.started_at, m.ended_at
                FROM candidates c
                JOIN matches m ON m.id = c.match_id AND m.match_status = 'open'
                WHERE c.player_id = ? AND c.status = 'accepted'");

                $stmt->execute([$candidateId]);

                $data["accepted_matches"] = $stmt->fetchAll();

                // sadece başvurulan maçı getiren sorgu
                $stmt = $this->pdo->prepare("SELECT started_at, ended_at,
                only_licensed_allowed, min_player_point, max_player_point 
                FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);

                $data["applied_match"] = $stmt->fetch();

                // başvurulan maç yoksa (?) boş dön
                if(empty($data["applied_match"])) return [];

                // başvuran player bilgilerini getiren sorgu
                $stmt = $this->pdo->prepare("SELECT loyalty_point, general_skill_point, licensed FROM players
                WHERE id = ?");
                $stmt->execute([$candidateId]);

                $data["candidate"] = $stmt->fetch();
                
                return $data;

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return [];
            }
        }

        public function apply(string $matchId): void {
            $authUser = $_REQUEST["auth_user"];
            $authUserId = $authUser->id;

            // zaten başvurmuş mu?
            try {
                $stmt = $this->pdo->prepare("SELECT match_id, player_id
                FROM candidates WHERE match_id = ? and player_id = ?");
                $stmt->execute([$matchId, $authUserId]);

                if(!empty($stmt->fetch())) {
                    http_response_code(422);
                    echo json_encode(["error" => "already applied"]);
                    return;
                }

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }

            $data = $this->getData($authUserId, $matchId);

            // Adayın bilgileri eksiksiz var mı?
            if(empty($data)) {
                http_response_code(422);
                echo json_encode(["error" => "information not found"]);
                return;
            }

            // oyuncu, matchmaker'ın belirlediği gereksinimlere uyuyor mu?
            $service = new CandidateService($data);

            if(!$service->canApply()) {
                http_response_code(422);
                echo json_encode(["error" => "does not satisfy requirements"]);
                return;
            }

            // ekle
            $postData = json_decode(file_get_contents("php://input"), true);
            $applicationNote = $postData["application_note"] ?? null;

            try {
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
                    "message" => "candidate applied successfully",
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
            }
        }

        public function decide(string $matchId, string $candidateId): void {
            $authUser = $_REQUEST["auth_user"];
            $authUserId = $authUser->id;

            try {
                // authUser, matchmaker mı?
                $stmt = $this->pdo->prepare("SELECT matchmaker_id FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);

                $matchmakerId = $stmt->fetchColumn();

                if(!$matchmakerId || (int) $matchmakerId !== (int) $authUserId) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return; 
                }

                $stmt = $this->pdo->prepare("SELECT status FROM candidates WHERE id = ? AND match_id = ?");
                $stmt->execute([$candidateId, $matchId]);
                
                $status = $stmt->fetchColumn();

                // böyle bir candidate var mı? 
                if(!$status) {
                    http_response_code(404);
                    echo json_encode(["error" => "candidate not found"]);
                    return;
                }

                // candidate hakkında karar verilmiş mi?
                if($status !== "pending") {
                    http_response_code(400);
                    echo json_encode(["error" => "already decided"]);
                    return;
                }
                $postData = json_decode(file_get_contents("php://input"), true);
                $decision = $postData["decision"] ?? null;

                if(!in_array($decision, ["accept", "reject"])) {
                    http_response_code(422);
                    echo json_encode(["error" => "invalid decision"]);
                    return;
                }

                $status = $decision === "accept" ? "accepted" : "denied";

                $stmt = $this->pdo->prepare("UPDATE candidates SET status = ? WHERE id = ?");
                $stmt->execute([$status, $candidateId]);

                if($stmt->rowCount() < 1) {
                    http_response_code(500);
                    echo json_encode(["error" => "server error"]);
                    return;
                }

                http_response_code(200);
                echo json_encode(
                    [
                        "message" => "decision made successfully",
                        "participant_info" => [
                            "id" => $candidateId,
                            "match_id" => $matchId,
                            "matchmaker_id" => $authUserId
                        ]
                    ]
                );

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }
    }