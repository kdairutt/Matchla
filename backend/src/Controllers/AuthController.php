<?php
    namespace Matchla\Controllers;
    
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
                http_response_code(422);
                echo json_encode([
                        "error" => $error
                    ]);
                return;
            }

            // email unique mi?
            if(!$this->isEmailUnique($data["email"])) {
                http_response_code(422);
                echo json_encode([
                        "error" => "email must be unique"
                    ]);
                return;
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
                http_response_code(201);
                echo json_encode([
                    "message" => "user successfully registered",
                ]);
            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
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
                    http_response_code(401);
                    echo json_encode(["error" => "invalid credentials"]);
                    return;
                }  

                $payload = [
                    "id" => $userCredentials["id"],
                    "email" => $userCredentials["email"],
                    "exp" => time() + (60 * 60 * 24)
                ];

                $token = JWT::encode($payload, $_ENV["JWT_SECRET"], "HS256");

                http_response_code(200);
                echo json_encode([
                    "token" => $token,
                    "user" => [
                        "id" => $userCredentials["id"],
                        "name" => $userCredentials["name"],
                        "surname" => $userCredentials["surname"],
                        "email" => $userCredentials["email"]
                    ]
                ]);

            } catch (\Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }
    }