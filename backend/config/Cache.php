<?php
    namespace Matchla\Config;

    use Predis\Client as Redis; 

    class Cache {
        private static ?Cache $instance = null;

        private function __construct(private Redis $redis,) {

        }

        public static function getInstance(): static {
            if(static::$instance === null) {
                $redis = new Redis([
                    "scheme" => "tcp",
                    "host" => $_ENV["REDIS_HOST"],
                    "port" => $_ENV["REDIS_PORT"],
                ]);

                static::$instance = new static($redis);
            }

            return static::$instance;
        }

        public function getRedis(): Redis {
            return $this->redis;
        }
    }