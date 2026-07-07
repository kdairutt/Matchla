<?php 
    namespace Matchla\Controllers;

    use Matchla\Services\MatchService;
    use Matchla\Models\PlayerModel;
    use Matchla\Models\MatchModel;

    class MatchController {
        private PlayerModel $player;
        private MatchModel $match;

        public function __construct() {
            $this->player = new PlayerModel();
            $this->match = new MatchModel();
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
            
            $result = $this->player->find(columns: ["is_premium"], conditions: ["id" => $userId]);
            $premium = (bool) $result["is_premium"];

            $service = new MatchService();

            $matches = $service->getNearbyMatches($lat, $lng, $premium);

            http_response_code(200);
            echo json_encode([
                "matches" => $matches
            ]);
        }

        public function show(string $matchId): void {
            $result = $this->match->find(conditions: ["id" => $matchId]);

            if(!$result) {
                http_response_code(404);
                echo json_encode([
                    "error" => "match not found"
                ]);

                return;
            }
            
            http_response_code(200);
            echo json_encode($result);
        }

        public function create(): void {
            $authUser = $_REQUEST["auth_user"];
            $matchmakerId = $authUser->id;

            $data = json_decode(file_get_contents("php://input"), true);

            $sportsTypeId = $this->isInputEmpty($data["sports_type_id"] ?? null, "sports_type_id");
            $fieldId = $this->isInputEmpty($data["field_id"] ?? null, "field_id");
            $startedAt = $this->isInputEmpty($data["started_at"] ?? null, "started_at");
            $endedAt = $this->isInputEmpty($data["ended_at"] ?? null, "ended_at");
            $targetParticipant = $this->isInputEmpty($data["target_participant"] ?? null, "target_participant");

            try {
                $newMatchId = $this->match->create([
                        "matchmaker_id" => $matchmakerId,
                        "sports_type_id" => $sportsTypeId,
                        "field_id" => $fieldId,
                        "started_at" => $startedAt,
                        "ended_at" => $endedAt,
                        "target_participant" => $targetParticipant,
                        "min_player_point" => $data["min_player_point"] ?? null,
                        "max_player_point" => $data["max_player_point"] ?? null,
                        "only_licensed_allowed" => $data["only_licensed_allowed"] ?? 0,
                        "description" => $data["description"] ?? null
                    ]);

                http_response_code(201);
                echo json_encode([
                    "message" => "match created successfully",
                    "match" => [
                        "id" => $newMatchId,
                        "matchmaker_id" => $matchmakerId,
                        "sports_type_id" => $sportsTypeId,
                        "field_id" => $fieldId,
                        "started_at" => $startedAt,
                        "status" => "open",
                    ]
                ]);
                return;
                
            } catch(\Exception $e) {
                http_response_code(500);
                error_log($e->getMessage());
                echo json_encode(["error" => "server error"]);
                return;
            }
        }

        public function update(string $matchId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            $data = json_decode(file_get_contents("php://input"), true);
            
            try {
                $matchmakerId = $this->match->getMatchmakerId($matchId);

                if(!$matchmakerId) {
                    http_response_code(404);
                    echo json_encode(["error" => "match not found"]);
                    return;
                }

                if(!((int) $authUserId === (int) $matchmakerId)) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return;
                }

                $updated = $this->match->update(
                    id: $matchId,
                    data: $data
                );

                http_response_code(200);
                echo json_encode([
                    "message" => "match updated successfully"
                ]);

            // client taraflı hataları loglamak pek de gerekli değil
            } catch (\InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(["error" => $e->getMessage()]);
                return;

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
            }
        }

        public function delete(string $matchId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            try {
                $result = $this->match->find(columns: ["matchmaker_id", "match_status"], conditions: ["id" => $matchId]);
                if(!$result) {
                    http_response_code(404);
                    echo json_encode(["error" => "match not found"]);
                    return;
                }

                // getMatchmakerId de kullanılabilirdi ama olsun
                $matchmakerId = $result["matchmaker_id"];
                $matchStatus = $result["match_status"];

                if($matchStatus === "cancelled") {
                    http_response_code(400);
                    echo json_encode(["error" => "match already cancelled"]);
                    return;
                }

                if(!((int) $authUserId === (int) $matchmakerId)) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return;
                }

                $cancelled = $this->match->cancel($matchId);

                if(!$cancelled) {
                    http_response_code(500);
                    echo json_encode(["error" => "server error"]);
                    return;
                }

                http_response_code(200);
                echo json_encode([
                    "message" => "match deleted successfully",
                    "matchmaker_id" => $matchmakerId
                ]);

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }
    }

