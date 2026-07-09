<?php
    namespace Matchla\Core;

    class Request {
        public static function getAuthUserId(): string {
            $authUserId = $_REQUEST["auth_user"]->id;
            return (string) $authUserId;
        }

        public static function getPostData(): ?array {
            $data = json_decode(file_get_contents("php://input"), true);

            return $data;
        }
    }