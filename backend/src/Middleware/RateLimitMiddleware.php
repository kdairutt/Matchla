<?php 
    namespace Matchla\Middleware;

    class RateLimitMiddleware {
        public function handle(): bool {
            return true;
        }
    }