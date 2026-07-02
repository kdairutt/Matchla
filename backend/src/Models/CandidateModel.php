<?php
    namespace Matchla\Models;

    class CandidateModel extends BaseModel {
        protected $table = "candidates";

        protected array $fillable = [
            "player_id",
            "match_id",
            "status",
            "application_note",
            "attended",
        ];

        protected array $updatable = [
            "player_id",
            "match_id",
            "status",
            "application_note",
            "attended",
        ];
    }