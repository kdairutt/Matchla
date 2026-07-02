<?php
    namespace Matchla\Models;

    class RatingModel extends BaseModel {
        protected string $table = "ratings";

        protected array $fillable = [
            "evaluator_id",
            "evaluated_id",
            "match_id",
            "skill_point",
        ];
        
        protected array $updatable = [
            "skill_point"
        ];
    }