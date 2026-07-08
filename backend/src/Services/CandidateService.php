<?php
    namespace Matchla\Services;

    class CandidateService {
        private array $candidate;
        private ?array $acceptedMatches;
        private array $appliedMatch;

        public function __construct(array $data) {
            $this->candidate = $data["candidate"];

            $playerPoint = $this->candidate["general_skill_point"] * 0.5 + $this->candidate["loyalty_point"] * 0.5;  
            $this->candidate["player_point"] = $playerPoint;

            $this->acceptedMatches = $data["accepted_matches"];

            $this->appliedMatch = $data["applied_match"];
        }

        private function satisfiesPlayerPoint(): bool {
            $playerPoint = $this->candidate["player_point"];
            $minPlayerPoint = $this->appliedMatch["min_player_point"];
            $maxPlayerPoint = $this->appliedMatch["max_player_point"];

            if($playerPoint < $minPlayerPoint || $playerPoint > $maxPlayerPoint) return false;   
            
            return true;
        }

        private function checkLicenseRequirement(): bool {
            $candidateLicensed = (bool) $this->candidate["licensed"];
            $onlyLicensedAllowed = (bool) $this->appliedMatch["only_licensed_allowed"];

            if(!$onlyLicensedAllowed) return true;

            return $candidateLicensed;
        }
        
        // eş zamanlı tek aktif rol kuralı gereği, diğer maçlarla olan olası çakışmaları kontrol et
        private function conflictsWithOtherMatches(): bool {

            if($this->acceptedMatches === null) return true;
            /*
                başvurduğum maçın başlangıç tarihi, kabul aldığım maçın bitiş 
                tarihinden önce ise VE
                başvurduğum maçın bitiş tarihi, kabul aldığım maçın başlangıç 
                tarihinden sonra ise
            */
            $conflicts = array_filter($this->acceptedMatches, fn($acceptedMatch) =>
                $this->appliedMatch["started_at"] < $acceptedMatch["ended_at"] &&
                $this->appliedMatch["ended_at"] > $acceptedMatch["started_at"] 
            );

            return empty($conflicts);
        }

        public function canApply(): bool {
            return 
                $this->checkLicenseRequirement() &&
                $this->satisfiesPlayerPoint() &&
                $this->conflictsWithOtherMatches();
        }
    }