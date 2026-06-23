<?php
    namespace Matchla\Controllers;
    
    use Matchla\Config\Database;
    use Firebase\JWT\JWT;

    class AuthController {

        // register için helper metotlar
        private function isEmailUnique(string $email): bool {

            $pdo = Database::getInstance()->getPDO();
            $stmt = $pdo->prepare("SELECT id FROM players WHERE email = ?");
            
            $stmt->execute([$email]);

            if($stmt->fetch()) return false;

            return true;
        }
        private function validate(array $data): ?string {
            
            if(empty($data["email"])) return "email required";
            if(!filter_var($data["email"], FILTER_VALIDATE_EMAIL)) return "invalid email";
            
            if(empty($data["password"])) return "password required";
            if(strlen($data["password"]) < 8) return "password less than minimum";

            return null;
        }

        public function register(): void {

            $pdo = Database::getInstance()->getPDO();

            // request body'i al ve decode et
            $body = file_get_contents("php://input");
            $data = json_decode($body, true);

            // bazı validasyon işlemleri
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
            
            $stmt = $pdo->prepare(
                "INSERT INTO players 
                (name, surname, email, date_of_birth, bio, password_sum)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $data["name"],
                $data["surname"],
                $data["email"],
                $data["date_of_birth"],
                $data["bio"],
                $pw_hashed]);

            http_response_code(201);
            echo json_encode([
                "message" => "user successfully registered",
            ]);
        }

        public function login(): void {
            $body = file_get_contents("php://input");
            $user = json_decode($body, true);

            try {
                $pdo = Database::getInstance()->getPDO();

                $stmt = $pdo->prepare("SELECT id, name, surname, email, password_sum FROM players WHERE email = ?");
                $stmt->execute([$user["email"]]);

                $userCredentials = $stmt->fetch();

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

            } catch(PDOException $e) {
                http_response_code(500);
                echo json_encode(["error" => "database error"]);
                return;

            } catch(Exception $e) {
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }
    }