<?php
    namespace Matchla\Middleware;
    
    use Matchla\Core\Response;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class authMiddleware {
        public function handle(): bool {
            $authHeader = $_SERVER["HTTP_AUTHORIZATION"] ?? null;

            if(!$authHeader || !\str_contains($authHeader, "Bearer")) {
                Response::error(401, "unauthorized");
                return false;
            }

            $token = str_replace("Bearer ", "", $authHeader);

            try {
                $decoded = JWT::decode($token, new Key($_ENV["JWT_SECRET"], "HS256"));
                
                $_REQUEST["auth_user"] = $decoded;
                return true;

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
                return false;
            }
        }
    }