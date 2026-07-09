<?php
    namespace Matchla\Controllers;

    use Matchla\Core\Request;
    use Matchla\Core\Response;
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
            $authUserId = Request::getAuthUserId();
            
            // maç var mı? varsa, 'open' statüsünde mi?
            try {
                $result = $this->match->find(columns: ["match_status"], conditions: ["id" => $matchId]);
                
                if(!$result) {
                    Response::error(404, "match not found");
                }

                $matchStatus = $result["match_status"];
                
                if($matchStatus !== "open") {
                    Response::error(422, "match not open for applications");
                }

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }

            // zaten başvurmuş mu?
            try {
                $alreadyApplied = $this->candidate->find(columns: ["match_id", "player_id"],
                conditions: ["match_id" => $matchId, "player_id" => $authUserId]);

                if(!empty($alreadyApplied)) {
                    Response::error(422, "already applied");
                }

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }

            $data = $this->getData($authUserId, $matchId);

            // Adayın bilgileri eksiksiz var mı?
            if(empty($data)) {
                Response::error(422, "information not found");
            }

            // oyuncu, matchmaker'ın belirlediği gereksinimlere uyuyor mu?
            $service = new CandidateService($data);

            if(!$service->canApply()) {
                Response::error(422, "does not satisfy requirements");
            }

            // ekle
            $postData = Request::getPostData();
            $applicationNote = $postData["application_note"] ?? null;

            try {
                $newCandidateId = $this->candidate->create([
                    "player_id" => $authUserId,
                    "match_id" => $matchId,
                    "application_note" => $applicationNote
                ]);

                $json = [
                    "candidate_data" => [
                        "candidate_id" => $newCandidateId,
                        "match_id" => $matchId,
                        "application_note" => $applicationNote
                    ]
                ];
                Response::success(201, "candidate applied successfully", $json);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function decide(string $matchId, string $candidateId): void {
            $authUserId = Request::getAuthUserId();

            try {
                // authUser, matchmaker mı?
                $matchmakerId = $this->match->getMatchmakerId($matchId);

                if(!$matchmakerId || (int) $matchmakerId !== (int) $authUserId) {
                    Response::error(403, "forbidden");
                }

                $result = $this->candidate->find(
                    columns: ["status"],
                    conditions: ["id" => $candidateId]
                );

                // aday var mı?
                if(!$result) {
                    Response::error(404, "candidate not found");
                }
                
                $status = $result["status"];

                // candidate hakkında karar verilmiş mi?
                if($status !== "pending") {
                    Response::error(message: "already decided");
                }

                $postData = Request::getPostData();
                $decision = $postData["decision"] ?? null;

                if(!in_array($decision, ["accept", "reject"])) {
                    Response::error(422, "invalid decision");
                }

                $newStatus = $decision === "accept" ? "accepted" : "denied";
                
                $result = $this->candidate->update(
                    id: $candidateId,
                    data: ["status" => $newStatus]
                );

                $json = [
                    "participant_info" => [
                            "candidate_id" => $candidateId,
                            "match_id" => $matchId,
                            "matchmaker_id" => $authUserId
                        ]
                ];
                Response::success(200, "decision made successfully", $json);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }

        public function index(string $matchId): void {
            $authUserId = Request::getAuthUserId();

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
                    Response::error(403, "forbidden");
                }

                // candidate'ları al
                $candidates = $this->candidate->findAllCandidatesOf($matchId);

                $json = [
                    "match_id" => $matchId,
                    "matchmaker_id" => $matchmakerId,
                    "candidates" => $candidates
                ];
                Response::success(200, "success", $json);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }
    }