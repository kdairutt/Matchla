<?php
    namespace Matchla\Controllers;
    
    use Matchla\Config\Database;

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
            
        }
    }