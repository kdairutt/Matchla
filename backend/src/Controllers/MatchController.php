<?php 
    namespace Matchla\Controllers;

    use Matchla\Config\Database;
    use Matchla\Services\MatchService;

    class MatchController {
        private \PDO $pdo;

        public function __construct() {
            $this->pdo = Database::getInstance()->getPDO();
        }

        private function isInputEmpty(mixed $input, string $fieldName): mixed {
            if(!empty($input)) return $input;

            http_response_code(422);
            echo json_encode([
                "error" => "$fieldName required"
            ]);

            exit;
        }

        public function index(): void {
            $userId = $_REQUEST["auth_user"]->id;

            $lat = $this->isInputEmpty($_GET["lat"] ?? null, "lat");
            $lng = $this->isInputEmpty($_GET["lng"] ?? null, "lng");
            
            $stmt = $this->pdo->prepare("SELECT is_premium FROM players 
            WHERE id = ?");

            $stmt->execute([$userId]);
            
            $premium = (bool) $stmt->fetchColumn();

            $service = new MatchService();

            $matches = $service->getNearbyMatches($lat, $lng, $premium);

            http_response_code(200);
            echo json_encode([
                "matches" => $matches
            ]);
        }

        public function show(string $matchId): void {
            $stmt = $this->pdo->prepare("SELECT * FROM matches WHERE id = ?");
            $stmt->execute([$matchId]);

            $match = $stmt->fetch();

            if(!$match) {
                http_response_code(404);
                echo json_encode([
                    "error" => "match not found"
                ]);

                return;
            }
            
            http_response_code(200);
            echo json_encode($match);
        }

        public function create(): void {
            $authUser = $_REQUEST["auth_user"];

            $data = json_decode(file_get_contents("php://input"), true);

            $matchmakerId = $authUser->id;

            $sportsTypeId = $this->isInputEmpty($data["sports_type_id"] ?? null, "sports_type_id");
            $fieldId = $this->isInputEmpty($data["field_id"] ?? null, "field_id");
            $startedAt = $this->isInputEmpty($data["started_at"] ?? null, "started_at");
            $targetParticipant = $this->isInputEmpty($data["target_participant"] ?? null, "target_participant");

            try {
                $stmt = $this->pdo->prepare("INSERT INTO matches 
                (matchmaker_id, sports_type_id, field_id, started_at,
                ended_at, target_participant, min_player_point,
                max_player_point, only_licensed_allowed, description)
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $matchmakerId,
                    $sportsTypeId,
                    $fieldId,
                    $data["started_at"],
                    $data["ended_at"],
                    $targetParticipant,
                    $data["min_player_point"] ?? null,
                    $data["max_player_point"] ?? null,
                    $data["only_licensed_allowed"] ?? 0,
                    $data["description"] ?? null,
                ]);

                if($stmt->rowCount() < 1) {
                    http_response_code(500);
                    echo json_encode(["error" => "server error"]);
                    return;
                }

                http_response_code(201);
                echo json_encode([
                    "message" => "match created successfully",
                    "match" => [
                        "id" => $this->pdo->lastInsertId(),
                        "matchmaker_id" => $matchmakerId,
                        "sports_type_id" => $sportsTypeId,
                        "field_id" => $fieldId,
                        "started_at" => $startedAt,
                        "status" => "open",
                    ]
                ]);
                return;
                
            } catch(\PDOException $e) {
                error_log($e->getMessage());
                echo json_encode(["error" => "server error"]);
                return;
            }
        }

        public function update(string $matchId): void {}

        public function delete(string $matchId): void {}
    }