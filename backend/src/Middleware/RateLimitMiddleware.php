<?php 
    namespace Matchla\Middleware;
    
    use Matchla\Config\Cache;
use Matchla\Core\Response;

    class RateLimitMiddleware {
        public function handle(): bool {
            $redis = Cache::getInstance()->getRedis();

            $key = "rate_limit:" . $_SERVER["REMOTE_ADDR"];
            

            # rate key'ini oluştur ve rate'i bir artır
            $rate =$redis->incr($key);
            
            if($rate === 1) {
                $redis->expire($key, 60);
            }

            if($rate > 60) {
                Response::error(429, "too many requests");
                return false;
            }

            return true;
        }
    }