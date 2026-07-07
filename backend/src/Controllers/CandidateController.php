<?php
    namespace Matchla\Controllers;
    use Matchla\Services\CandidateService;
    use Matchla\Models\CandidateModel;
    use Matchla\Models\PlayerModel;
    use Matchla\Models\MatchModel;

    class CandidateController {
        private CandidateModel $candidate;
        private MatchModel $match;
        private PlayerModel $player;

        public function __construct() {
            $this->candidate = new CandidateModel();
            $this->match = new MatchModel();
            $this->player = new PlayerModel();
        }

        private function getData(string $playerId, string $matchId): array {
            $data = [];
            // zaman çakışmasının kontrolü için gereken,
            // kullanıcının kabul aldığı tüm maçların
            // kontrolünü yapmaya yarayacak sorgu
            $data["accepted_matches"] = $this->candidate->findAllAcceptedMatches($playerId);

            // sadece başvurulan maçı getiren sorgu
            $data["applied_match"] = $this->match->find(
                columns: ["started_at", "ended_at", "only_licensed_allowed", "min_player_point", "max_player_point"],
                conditions: ["id" => $matchId]
            );

            // başvurulan maç yoksa (?) boş dön
            if(empty($data["applied_match"])) return [];

            // başvuran player bilgilerini getiren sorgu
            $data["candidate"] = $this->player->find(
                columns: ["loyalty_point", "general_skill_point", "licensed"],
                conditions: ["id" => $playerId]
            );

            return $data;
        }

        public function apply(string $matchId): void {
            $authUser = $_REQUEST["auth_user"];
            $authUserId = $authUser->id;

            // zaten başvurmuş mu?
            try {
                $alreadyApplied = $this->candidate->find(columns: ["match_id", "player_id"],
                conditions: ["match_id" => $matchId, "player_id" => $authUserId]);

                if(!empty($alreadyApplied)) {
                    http_response_code(422);
                    echo json_encode(["error" => "already applied"]);
                    return;
                }

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }

            $data = $this->getData($authUserId, $matchId);

            // Adayın bilgileri eksiksiz var mı?
            if(empty($data)) {
                http_response_code(422);
                echo json_encode(["error" => "information not found"]);
                return;
            }

            // oyuncu, matchmaker'ın belirlediği gereksinimlere uyuyor mu?
            $service = new CandidateService($data);

            if(!$service->canApply()) {
                http_response_code(422);
                echo json_encode(["error" => "does not satisfy requirements"]);
                return;
            }

            // ekle
            $postData = json_decode(file_get_contents("php://input"), true);
            $applicationNote = $postData["application_note"] ?? null;

            try {
                $newCandidateId = $this->candidate->create([
                    "player_id" => $authUserId,
                    "match_id" => $matchId,
                    "application_note" => $applicationNote
                ]);

                http_response_code(201);
                echo json_encode([
                    "message" => "candidate applied successfully",
                    "candidate_data" => [
                        "candidate_id" => $newCandidateId,
                        "match_id" => $matchId,
                        "application_note" => $applicationNote
                    ]
                ]);

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
            }
        }

        public function decide(string $matchId, string $candidateId): void {
            $authUser = $_REQUEST["auth_user"];
            $authUserId = $authUser->id;

            try {
                // authUser, matchmaker mı?
                $matchmakerId = $this->match->getMatchmakerId($matchId);

                if(!$matchmakerId || (int) $matchmakerId !== (int) $authUserId) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return; 
                }

                $result = $this->candidate->find(
                    columns: ["status"],
                    conditions: ["id" => $candidateId]
                );

                // aday var mı?
                if(!$result) {
                    http_response_code(404);
                    echo json_encode(["error" => "candidate not found"]);
                    return;
                }
                
                $status = $result["status"];

                // candidate hakkında karar verilmiş mi?
                if($status !== "pending") {
                    http_response_code(400);
                    echo json_encode(["error" => "already decided"]);
                    return;
                }

                $postData = json_decode(file_get_contents("php://input"), true);
                $decision = $postData["decision"] ?? null;

                if(!in_array($decision, ["accept", "reject"])) {
                    http_response_code(422);
                    echo json_encode(["error" => "invalid decision"]);
                    return;
                }

                $newStatus = $decision === "accept" ? "accepted" : "denied";
                
                $result = $this->candidate->update(
                    id: $candidateId,
                    data: ["status" => $newStatus]
                );

                if(!$result) {
                    http_response_code(500);
                    echo json_encode(["error" => "server error"]);
                    return;
                }

                http_response_code(200);
                echo json_encode(
                    [
                        "message" => "decision made successfully",
                        "participant_info" => [
                            "candidate_id" => $candidateId,
                            "match_id" => $matchId,
                            "matchmaker_id" => $authUserId
                        ]
                    ]
                );

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }

        public function index(string $matchId): void {
            $authUser = $_REQUEST["auth_user"];
            $authUserId = $authUser->id;

            try {
                // matchmaker mı adayları görüntülemek istiyor?
                $matchmakerId = $this->match->getMatchmakerId($matchId);
                $isMatchmaker = (int) $matchmakerId === (int) $authUserId;

                // başka bir katılımcı mı adayları görüntülemek istiyor?
                $result = $this->candidate->find(
                    columns: ["status"],
                    conditions: ["match_id" => $matchId, "player_id" => $authUserId]
                );

                $isParticipant = $result && $result["status"] === "accepted";

                if(!$isMatchmaker && !$isParticipant) {
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return;
                }

                // candidate'ları al
                $candidates = $this->candidate->findAllCandidatesOf($matchId);

                http_response_code(200);
                echo json_encode([
                    "match_id" => $matchId,
                    "matchmaker_id" => $matchmakerId,
                    "candidates" => $candidates
                ]);

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }
    }