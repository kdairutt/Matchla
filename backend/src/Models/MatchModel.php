<?php
    namespace Matchla\Models;

    class MatchModel extends BaseModel {

        protected string $table = "matches";

        protected array $fillable = [
            "matchmaker_id",
            "sports_type_id",
            "field_id",
            "started_at",
            "ended_at",
            "target_participant", 
            "min_player_point",
            "max_player_point",
            "only_licensed_allowed",
            "match_status",
            "description",
        ];

        protected array $updatable = [
            "started_at",
            "ended_at", 
            "target_participant",
            "min_player_point", 
            "max_player_point", 
            "only_licensed_allowed", 
            "description"
        ];
    }