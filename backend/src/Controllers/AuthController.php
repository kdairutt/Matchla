<?php
    namespace Matchla\Controllers;
    
    use Matchla\Core\Response;
    use Matchla\Models\PlayerModel;
    use Firebase\JWT\JWT;

    class AuthController {
        private PlayerModel $player;

        public function __construct() {
            $this->player = new PlayerModel();
        }

        private function isEmailUnique(string $email): bool {
            $exists = $this->player->find(columns: ["id"], conditions: ["email" => $email]);
            return empty($exists);
        }

        private function validate(array $data): ?string {
            
            if(empty($data["email"])) return "email required";
            if(!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) return "invalid email";
            
            if(empty($data["password"])) return "password required";
            if(strlen($data["password"]) < 8) return "password less than minimum";

            return null;
        }

        public function register(): void {
            $body = file_get_contents("php://input");
            $data = json_decode($body, true);

            $error = $this->validate($data);

            if($error !== null) {
                Response::error(422, $error);
            }

            // email unique mi?
            if(!$this->isEmailUnique($data["email"])) {
                Response::error(422, "email must be unique");
            }

            // şifre'yi gizleme işlemleri
            $pw = $data["password"];
            $pw_hashed = password_hash($pw, PASSWORD_BCRYPT);

            try {
                $this->player
                ->create([
                    "name" => $data["name"],
                    "surname" => $data["surname"],
                    "email" => $data["email"],
                    "date_of_birth" => $data["date_of_birth"],
                    "bio" => $data["bio"],
                    "password_sum" => $pw_hashed
                ]);
                Response::success(201, "user registered successfully");

            } catch (\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function login(): void {
            $body = file_get_contents("php://input");
            $user = json_decode($body, true);

            try {   
                $userCredentials = $this->player->find(
                    columns: ["id", "name", "surname", "email", "password_sum"],
                    conditions: ["email" => $user["email"]]
                );

                // böyle bir user yoksa veya var ama şifre yanlışsa
                if(!$userCredentials || !password_verify($user["password"], $userCredentials["password_sum"])) {
                    Response::error(401, "invalid credentials");
                }  

                $payload = [
                    "id" => $userCredentials["id"],
                    "email" => $userCredentials["email"],
                    "exp" => time() + (60 * 60 * 24)
                ];

                $token = JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256");

                $json = [
                    "token" => $token,
                    "user" => [
                        "id" => $userCredentials["id"],
                        "name" => $userCredentials["name"],
                        "surname" => $userCredentials["surname"],
                        "email" => $userCredentials["email"]
                    ]
                ];
                Response::success(200, "player logged in successfully", $json);

            } catch (\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }
    }