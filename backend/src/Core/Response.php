<?php
    namespace Matchla\Core;

    class Response {
        
        public static function error(int $code = 400, string $message = "bad request"): void {
            http_response_code($code);
            echo json_encode(["error" => $message]);
            exit;
        }

        public static function serverError(string $errorMessage): void {
            error_log($errorMessage);
            self::error(500, "server error");
        }

        public static function success(int $code, string $message, array $data = []): void {
            http_response_code($code);
            $json = array("message" => $message, "data" => $data);
            echo json_encode($json);
            exit;
        }
    }