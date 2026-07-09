<?php
    namespace Matchla\Controllers;
    
    use Matchla\Core\Request;
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
            $postData = Request::getPostData();

            $error = $this->validate($postData);

            if($error !== null) {
                Response::error(422, $error);
            }

            // email unique mi?
            if(!$this->isEmailUnique($postData["email"])) {
                Response::error(422, "email must be unique");
            }

            // şifre'yi gizleme işlemleri
            $pw = $postData["password"];
            $pw_hashed = password_hash($pw, PASSWORD_BCRYPT);

            try {
                $this->player
                ->create([
                    "name" => $postData["name"],
                    "surname" => $postData["surname"],
                    "email" => $postData["email"],
                    "date_of_birth" => $postData["date_of_birth"],
                    "bio" => $postData["bio"],
                    "password_sum" => $pw_hashed
                ]);
                Response::success(201, "user registered successfully");

            } catch (\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function login(): void {
            $postData = Request::getPostData();

            try {   
                $userCredentials = $this->player->find(
                    columns: ["id", "name", "surname", "email", "password_sum"],
                    conditions: ["email" => $postData["email"]]
                );

                // böyle bir user yoksa veya var ama şifre yanlışsa
                if(!$userCredentials || !password_verify($postData["password"], $userCredentials["password_sum"])) {
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