<?php
    namespace Matchla\Controllers;

    class AuthController
    {
        public function login(): void 
        {
            echo json_encode([
                "message" => "login çalıştı!",
            ]);
        }
    }