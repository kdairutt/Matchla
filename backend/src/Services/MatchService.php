<?php
    namespace Matchla\Services;

    use Matchla\Models\MatchModel;

    class MatchService {
        private MatchModel $match;

        public function __construct() {
            $this->match = new MatchModel;
        }

        public function getNearbyMatches(float $lat, float $lng, bool $isPremium): array {

            $radius = $isPremium ? 100.0 : 50.0;

            $matches = $this->match->findNearby($lat, $lng, $radius);

            return $matches;
        }
    }