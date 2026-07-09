<?php
    namespace Matchla\Controllers;

    use Matchla\Core\Request;
    use Matchla\Core\Response;
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
                    Response::error(404, "player not found");
                }

                Response::success(200, "success", $result);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function update(string $playerId): void {
            $authUserId = Request::getAuthUserId();

            if((int) $authUserId !== (int) $playerId) {
                Response::error(403, "forbidden");
            }

            $postData = Request::getPostData();

            if(empty($postData)) {
                Response::error();
            }

            try {
                $this->player->update(
                    id: $playerId,
                    data: $postData
                );
                Response::success(200, "player updated successfully");

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }
    }