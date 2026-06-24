<?php
    namespace Matchla\Middleware;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class authMiddleware {
        public function handle(): bool {
            $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? null;

            if(!$authHeader || !\str_contains($authHeader, "Bearer")) {

                http_response_code(401);
                echo json_encode(["error" => "unauthorized"]);
                return false;
            }

            $token = str_replace("Bearer ", "", $authHeader);

            try {
                $decoded = JWT::decode($token, new Key($_ENV["JWT_SECRET"], "HS256"));
                return true;

            } catch(\Exception $e) {
                http_response_code(401);
                echo json_encode(["error" => "unauthorized"]);
                return false;
            }
        }
    }