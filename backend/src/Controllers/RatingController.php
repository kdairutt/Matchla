<?php
    namespace Matchla\Controllers;

    use Matchla\Config\Database;
    
    class RatingController {
        private \PDO $pdo;

        public function __construct() {
            $this->pdo = Database::getInstance()->getPDO();
        }

        public function rate(string $matchId, string $evaluatedId): void {
            $authUser = $_REQUEST["auth_user"];
            // değerleme yapan
            $evaluatorId = $authUser->id;

            try {
                // maç, değerleme aşamasında mı?
                $stmt = $this->pdo->prepare("SELECT match_status 
                FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);
                if($stmt->fetchColumn() !== "evaluation") {
                    http_response_code(400);
                    echo json_encode(
                        ["error" => "match not under evaluation"]
                        );
                    return;
                }

                // Değerleme yapan ve değerlendirilen, bir katılımcı mı?
                $stmt = $this->pdo->prepare("SELECT status FROM candidates
                WHERE player_id = ? AND match_id = ? AND status = 'accepted'");

                $stmt->execute([$evaluatorId, $matchId]);
                $evaluatorIsParticipant = $stmt->rowCount() > 0;

                $stmt->execute([$evaluatedId, $matchId]);
                $evaluatedIsParticipant = $stmt->rowCount() > 0;

                if(!$evaluatorIsParticipant || !$evaluatedIsParticipant) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return;
                }
            
                // değerleme yapan, kendini değerlendirmeye çalışıyor mu?
                if ((int)$evaluatorId === (int)$evaluatedId) {
                    http_response_code(400);
                    echo json_encode(["error" => "cannot evaluate the evaluator"]);
                    return;
                }

                // zaten değerleme yapılmış mı? (zaten unique key var ama olsun)
                $stmt = $this->pdo->prepare("SELECT id FROM ratings WHERE
                evaluator_id = ? AND evaluated_id = ? AND match_id = ?");
                $stmt->execute([$evaluatorId, $evaluatedId, $matchId]);
                $alreadyRated = $stmt->rowCount() > 0;
                if($alreadyRated) {
                    http_response_code(400);
                    echo json_encode(
                        ["error" => "already rated this player"]);
                    return;
                }
                
                $skillPoint = json_decode(file_get_contents("php://input"), true)["skill_point"] ?? null;
                if($skillPoint === null || $skillPoint < 0 || $skillPoint > 100) {
                    http_response_code(422);
                    echo json_encode(["error" => "invalid skill point"]);
                    return;
                }

                $stmt = $this->pdo->prepare("INSERT INTO ratings
                (evaluator_id, evaluated_id, match_id, skill_point)
                VALUES (?, ?, ?, ?)");

                $stmt->execute([$evaluatorId, $evaluatedId, $matchId, $skillPoint]);

                if(!$stmt->rowCount() > 0) {
                    http_response_code(500);
                    echo json_encode(["error" => "server error"]);
                    return;
                }

                http_response_code(201);
                echo json_encode([
                    "message" => "player rated successfully",
                    "rating_data" => [
                        "evaluator_id" => $evaluatorId,
                        "evaluated_id" => $evaluatedId,
                        "match_id" => $matchId,
                        "skill_point" => $skillPoint,
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