<?php 
    namespace Matchla\Controllers;

    use Matchla\Core\Response;
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
            Response::error(422, "{$fieldName} required");
            return null;
        }

        public function index(): void {
            $userId = $_REQUEST["auth_user"]->id;

            $lat = $this->isInputEmpty($_GET["lat"] ?? null, "lat");
            $lng = $this->isInputEmpty($_GET["lng"] ?? null, "lng");
            
            $result = $this->player->find(columns: ["is_premium"], conditions: ["id" => $userId]);
            $premium = (bool) $result["is_premium"];

            $service = new MatchService();

            $matches = $service->getNearbyMatches($lat, $lng, $premium);
            
            Response::success(200, "success", $matches);
        }

        public function show(string $matchId): void {
            $result = $this->match->find(conditions: ["id" => $matchId]);

            if(!$result) {
                Response::error(404, "match not found");
            }
            
            Response::success(200, "success", $result);
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

                $json = [
                    "match" => [
                        "id" => $newMatchId,
                        "matchmaker_id" => $matchmakerId,
                        "sports_type_id" => $sportsTypeId,
                        "field_id" => $fieldId,
                        "started_at" => $startedAt,
                        "status" => "open",
                    ]];

                Response::success(201, "match created successfully", $json);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function update(string $matchId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            $data = json_decode(file_get_contents("php://input"), true);
            
            try {
                $matchmakerId = $this->match->getMatchmakerId($matchId);

                if(!$matchmakerId) {
                    Response::error(404, "match not found");
                }

                if(!((int) $authUserId === (int) $matchmakerId)) {
                    Response::error(403, "forbidden");
                }

                $this->match->update(
                    id: $matchId,
                    data: $data
                );

                Response::success(200, "match updated successfully");

            // client taraflı hataları loglamak pek de gerekli değil
            } catch (\InvalidArgumentException $e) {
                Response::error(400, $e->getMessage());

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function delete(string $matchId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            try {
                $result = $this->match->find(columns: ["matchmaker_id", "match_status"], conditions: ["id" => $matchId]);
                
                if(!$result) {
                    Response::error(404, "match not found");
                }

                // getMatchmakerId de kullanılabilirdi ama olsun
                $matchmakerId = $result["matchmaker_id"];
                $matchStatus = $result["match_status"];

                if(!((int) $authUserId === (int) $matchmakerId)) {
                    Response::error(403, "forbidden");
                }
                
                if($matchStatus === "cancelled") {
                    Response::error(400, "match already cancelled");
                }

                $this->match->cancel($matchId);

                $json = ["matchmaker_id" => $matchmakerId];

                Response::success(200, "match deleted successfully", $json);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }
    }

