<?php
    namespace Matchla\Config;

    class Database {
        private static ?Database $instance = null;

        private function __construct(
            private \PDO $pdo,
        ) {}

        public static function getInstance(): static {
            if(static::$instance === null) {
                $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8";
                $pdo = new PDO(
                    $dsn,
                    $_ENV["DB_USERNAME"],
                    $_ENV["DB_PASSWORD"],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );

                static::$instance = new static($pdo);
            }

            return static::$instance;
        }

        public function getPDO(): \PDO {
            return $this->pdo;
        }
    }