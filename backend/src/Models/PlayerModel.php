<?php
    namespace Matchla\Models;

    class PlayerModel extends BaseModel {
        protected $table = "players";

        protected array $fillable = [
            "name",
            "surname",
            "email",
            "date_of_birth",
            "bio",
            "password_sum",
        ];

        protected array $updatable = [
            "name",
            "surname",
            "bio",
            "pp_reference",
            "licensed",
        ];
    }