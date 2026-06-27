<?php
    namespace Matchla\Controllers;

    use Matchla\Config\Database;

    class PlayerController {
        private \PDO $pdo;

        public function __construct() {
            $this->pdo = Database::getInstance()->getPDO();
        }

        public function show(string $playerId): void {

            // normalde, maçları herkes görüntüleyebiliyordu. ama bir oyuncuyu görüntüleyebilmen 
            // için, Matchla'ya kayıt olmuş bir Oyuncu olmalısın

            try {
                $stmt = $this->pdo->prepare("SELECT 
                
                id, name, surname, email, bio, skill_point, loyalty_point, general_skill_point,
                pp_reference, licensed, account_state, premium_sub_id, created_at, is_premium 
                
                FROM players WHERE id = ?");

                $stmt->execute([$playerId]);

                $player = $stmt->fetch();

                if(empty($player)) {
                    http_response_code(404);
                    echo json_encode(["error" => "player not found"]);
                    return;
                }

                http_response_code(200);
                echo json_encode($player);

            } catch(\PDOException $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
            }
        }
    }