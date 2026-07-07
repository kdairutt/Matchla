<?php
    namespace Matchla\Controllers;

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
                    http_response_code(404);
                    echo json_encode(["error" => "match not found"]);
                    return;
                }

                if($result["match_status"] !== "evaluation") {
                    http_response_code(400);
                    echo json_encode(["error" => "match not under evaluation"]);
                    return;
                }

                // değerleme yapan, kendini değerlendirmeye çalışıyor mu?
                if ((int) $evaluatorId === (int) $evaluatedId) {
                    http_response_code(400);
                    echo json_encode(["error" => "cannot evaluate the evaluator"]);
                    return;
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
                    http_response_code(403);
                    echo json_encode(["error" => "forbidden"]);
                    return;
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
                    http_response_code(400);
                    echo json_encode(
                        ["error" => "already rated this player"]);
                    return;
                }
                
                $skillPoint = json_decode(file_get_contents("php://input"), true)["skill_point"] ?? null;
                if($skillPoint === null || $skillPoint < 0 || $skillPoint > 100) {
                    http_response_code(422);
                    echo json_encode(["error" => "invalid skill point"]);
                    return;
                }
                
                $ratingData = [
                    "evaluator_id" => $evaluatorId,
                    "evaluated_id" => $evaluatedId,
                    "match_id" => $matchId,
                    "skill_point" => $skillPoint
                ];

                $this->rating->create($ratingData);

                http_response_code(201);
                echo json_encode([
                    "message" => "player rated successfully",
                    "rating_data" => $ratingData
                ]);

            } catch(\Exception $e) {
                error_log($e->getMessage());
                http_response_code(500);
                echo json_encode(["error" => "server error"]);
                return;
            }
        }
    }