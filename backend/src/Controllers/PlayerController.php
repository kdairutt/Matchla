<?php
    namespace Matchla\Controllers;

    use Matchla\Models\PlayerModel;
    
    class PlayerController {
        
        private PlayerModel $player;

        public function __construct() {
            $this->player = new PlayerModel();
        }

        public function show(string $playerId): void {
            try {
                $result = $this->player->find(
                    columns: [
                        "id",
                        "name",
                        "surname",
                        "email",
                        "bio", 
                        "skill_point", 
                        "loyalty_point", 
                        "general_skill_point", 
                        "pp_reference", 
                        "licensed", 
                        "account_state", 
                        "premium_sub_id", 
                        "created_at", 
                        "is_premium"
                    ],
                    conditions: ["id" => $playerId]);
        
                if(empty($result)) {
                    http_response_code(404);
                    echo json_encode(["error" => "player not found"]);
                    return;
                }

                http_response_code(200);
                echo json_encode($result);

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
            }
        }

        public function update(string $playerId): void {
            $authUserId = $_REQUEST["auth_user"]->id;

            if((int) $authUserId !== (int) $playerId) {
                http_response_code(403);
                echo json_encode(["error" => "forbidden"]);
                return;
            }

            $data = json_decode(file_get_contents("php://input"), true);

            if(empty($data)) {
                http_response_code(400);
                echo json_encode(["error" => "bad request"]);
                return;
            }

            try {
                $updated = $this->player->update(
                    id: $playerId,
                    data: $data
                );

                http_response_code(200);
                echo json_encode([
                    "message" => "player updated successfully",
                ]);

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
            }
        }
    }