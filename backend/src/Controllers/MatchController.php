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
            $matchmakerId = $authUser->id;

            $data = json_decode(file_get_contents("php://input"), true);

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

        public function update(string $matchId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            $data = json_decode(file_get_contents("php://input"), true);

            // değiştirilebilecek kolonlar
            $allowedFields = ["started_at", "ended_at", "target_participant",
            "min_player_point", "max_player_point", "only_licensed_allowed", "description"];

            // değiştirilmesi istenen kolonlar
            $filteredFields = array_filter($allowedFields, fn($f) => isset($data[$f]));

            // SQL'e uygun hale getir
            $fields = array_map(fn($f) => "$f = ?", $filteredFields);

            // değerler
            // $data["field"], $filteredFields içindeki her bir değer için.
            $values = array_map(fn($f) => $data[$f], $filteredFields);
            $values[] = $matchId;

            if(empty($fields)) {
                http_response_code(400);
                echo json_encode(["error" => "bad request"]);
                return;
            }
            
            try {
                $stmt = $this->pdo->prepare("SELECT matchmaker_id FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);

                $matchmakerId = $stmt->fetchColumn();

                if(!$matchmakerId) {
                    http_response_code(404);
                    echo json_encode(["error" => "match not found"]);
                    return;
                }

                if(!($authUserId === $matchmakerId)) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);

                    return;
                }

                $stmt = $this->pdo->prepare("UPDATE matches SET " . implode(", ", $fields) . " WHERE id = ?");
                $stmt->execute($values);

                http_response_code(200);
                echo json_encode([
                    "message" => "match updated successfully",
                    "updated_columns" => array_values($filteredFields)
                ]);

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
            }
        }

        public function delete(string $matchId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            try {
                $stmt = $this->pdo->prepare("SELECT matchmaker_id, match_status FROM matches WHERE id = ?");
                $stmt->execute([$matchId]);
                
                $match = $stmt->fetch();

                if(!$match) {
                    http_response_code(404);
                    echo json_encode(["error" => "match not found"]);
                    return;
                }
            
                $matchmakerId = $match["matchmaker_id"];
                $matchStatus = $match["match_status"];

                if($matchStatus === "cancelled") {
                    http_response_code(400);
                    echo json_encode(["error" => "match already cancelled"]);

                    return;
                }

                if(!($authUserId === $matchmakerId)) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);

                    return;
                }

                $stmt = $this->pdo->prepare("UPDATE matches SET match_status = 'cancelled' WHERE id = ?");
                $stmt->execute([$matchId]);

                http_response_code(200);
                echo json_encode([
                    "message" => "match deleted successfully",
                    "matchmaker_id" => $matchmakerId
                ]);

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);

                return;
            }
        }
    }

