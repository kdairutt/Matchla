<?php
    namespace Matchla\Controllers;

    use Matchla\Core\Response;
    use Matchla\Models\MatchModel;
    use Matchla\Models\CandidateModel;
    use Matchla\Models\RatingModel;

    class RatingController {
        private MatchModel $match;
        private CandidateModel $candidate;
        private RatingModel $rating;

        public function __construct() {
            $this->match = new MatchModel();
            $this->candidate = new CandidateModel();
            $this->rating = new RatingModel();
        }

        public function rate(string $matchId, string $evaluatedId): void {
            $authUser = $_REQUEST["auth_user"];
            // değerleme yapan
            $evaluatorId = $authUser->id;

            try {
                // maç, değerleme aşamasında mı?
                $result = $this->match->find(columns: ["match_status"], conditions: ["id" => $matchId]);

                if(!$result) {
                    Response::error(404, "match not found");
                }

                if($result["match_status"] !== "evaluation") {
                    Response::error(message: "match not under evaluation");
                }

                // değerleme yapan, kendini değerlendirmeye çalışıyor mu?
                if ((int) $evaluatorId === (int) $evaluatedId) {
                    Response::error(message: "cannot evaluate the evaluator");
                }

                // Değerleme yapan ve değerlendirilen, bir katılımcı mı?
                $evaluatorIsParticipant = $this->candidate->find(
                    columns: ["status"],
                    conditions: [
                        "player_id" => $evaluatorId,
                        "match_id" => $matchId,
                        "status" => "accepted"
                    ]);

                $evaluatedIsParticipant = $this->candidate->find(
                    columns: ["status"],
                    conditions: [
                        "player_id" => $evaluatedId,
                        "match_id" => $matchId,
                        "status" => "accepted"
                    ]);

                if(!$evaluatorIsParticipant || !$evaluatedIsParticipant) {
                    Response::error(403, "forbidden");
                }

                // zaten değerleme yapılmış mı? (zaten unique key var ama olsun)
                $alreadyRated = $this->rating->find(
                    columns: ["id"],
                    conditions: [
                        "evaluator_id" => $evaluatorId, 
                        "evaluated_id" => $evaluatedId,
                        "match_id" => $matchId
                    ]);
                    
                if($alreadyRated) {
                    Response::error(message: "already rated this player");
                }
                
                $skillPoint = json_decode(file_get_contents("php://input"), true)["skill_point"] ?? null;
                if($skillPoint === null || $skillPoint < 0 || $skillPoint > 100) {
                    Response::error(422, "insufficient skill point");
                }
                
                $ratingData = [
                    "evaluator_id" => $evaluatorId,
                    "evaluated_id" => $evaluatedId,
                    "match_id" => $matchId,
                    "skill_point" => $skillPoint
                ];

                $this->rating->create($ratingData);

                Response::success(201, "player rated successfully", $ratingData);

            } catch(\Exception $e) {
                Response::serverError($e->getMessage());
            }
        }
    }