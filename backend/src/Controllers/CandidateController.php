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
                m.started_at AS accepted_started_at, m.ended_at AS accepted_ended_at
                FROM candidates c
                JOIN matches m ON m.id = c.match_id AND m.match_status = 'open'
                WHERE c.player_id = ? c.status = 'accepted'");

                $stmt->execute([$candidateId]);

                $data[] = $stmt->fetchAll();

                // sadece başvurulan maçı getiren sorgu
                $stmt = $this->pdo->prepare("SELECT started_at AS applied_started_at, ended_at AS applied_ended_at,
                only_licensed_allowed, min_player_point, max_player_point 
                FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);

                $data[] = $stmt->fetch();

                // başvuran player bilgilerini getiren sorgu
                $stmt = $this->pdo->prepare("SELECT loyalty_point, general_skill_point, licensed FROM players
                WHERE id = ?");
                $stmt->execute([$candidateId]);

                $data[] = $stmt->fetch();
                
                return $data;

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return [];
            }

            // Oyuncunun güncel olarak başvurduğu maçın bilgileri
            try {
                $stmt = $this->pdo->prepare("SELECT 
                min_player_point, max_player_point, only_licensed_allowed, match_status FROM matches
                WHERE id = ?");
                $stmt->execute([$matchId]);

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

            $postData = json_decode(file_get_contents("php://input"), true);
            $applicationNote = $postData["application_note"] ?? null;

            $data = $this->getData($authUserId);

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
                return;
            }
        } 
    }