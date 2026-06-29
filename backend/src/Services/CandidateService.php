<?php
    namespace Matchla\Services;

    use Matchla\Config\Database;

    class CandidateService {
        private array $candidate;
        private ?array $acceptedMatches;
        private array $appliedMatch;

        public function __construct(array $data) {
            $this->candidate = [
                "loyalty_point" => $data["loyalty_point"],
                "general_skill_point" => $data["general_skill_point"],
                "licensed" => $data["licensed"],
            ];
            $playerPoint = $this->candidate["general_skill_point"] * 0.5 + $this->candidate["loyalty_point"] * 0.5;  
            $this->candidate["player_point"] = $playerPoint;

            $this->acceptedMatches = $data["accepted_started_at"] ? [
                "started_at" => $data["accepted_started_at"],
                "ended_at" => $data["accepted_ended_at"],
            ] : null;

            $this->appliedMatch = [
                "started_at" => $data["applied_started_at"],
                "ended_at" => $data["applied_ended_at"],
                "only_licensed_allowed" => $data["only_licensed_allowed"],
                "min_player_point" => $data["min_player_point"],
                "max_player_point" => $data["max_player_point"],
            ];
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
            /*
                başvurduğum maçın başlangıç tarihi, kabul aldığım maçın bitiş 
                tarihinden önce ise VE
                başvurduğum maçın bitiş tarihi, kabul aldığım maçın başlangıç 
                tarihinden sonra ise
            */
            $conflicts = array_filter($acceptedMatches, fn($acceptedMatch) =>
                $appliedMatch["started_at"] < $acceptedMatch["ended_at"] &&
                $appliedMatch["ended_at"] > $acceptedMatch["started_at"] 
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