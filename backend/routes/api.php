<?php

    $routes = [
        // kayıt ol
        [
            "POST", 
            "/api/auth/register", 
            "AuthController", 
            "register", 
            ["RateLimitMiddleware"]
        ],

        // giriş yap
        [
            "POST", 
            "/api/auth/login", 
            "AuthController", 
            "login", 
            ["RateLimitMiddleware"]
        ], 

        // yakın çevredeki tüm maçlar
        [
            "GET", 
            "/api/matches", 
            "MatchController", 
            "index", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ],

        // spesifik bir maç
        [
            "GET", 
            "/api/matches/{id}", 
            "MatchController", 
            "show", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ],

        // maç oluştur
        [
            "POST", 
            "/api/matches", 
            "MatchController", 
            "create", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 
        
        // maçı güncelle
        [
            "PUT", 
            "/api/matches/{id}", 
            "MatchController", 
            "update", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 

        // maçı sil
        [
            "DELETE", 
            "/api/matches/{id}", 
            "MatchController", 
            "delete", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 
        
        // spesifik bir oyuncuyu görüntüle
        [
            "GET", 
            "/api/players/{id}", 
            "PlayerController", 
            "show", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 
        
        [
            "PATCH",
            "/api/players/{id}",
            "PlayerController",
            "update",
            ["AuthMiddleware", "RateLimitMiddleware"]
        ],

        // maçın adaylarını görüntüle
        [
            "GET",
            "/api/matches/{id}/candidates",
            "CandidateController", 
            "index", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ],

        // maça başvur
        [
            "POST",
            "/api/matches/{id}/apply", 
            "CandidateController", 
            "apply", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 
        
        // ilgili maçın adayını reddet/kabul et
        [
            "PUT", 
            "/api/matches/{matchId}/candidates/{candidateId}", 
            "CandidateController", 
            "decide", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 

        // ilgili maçın ilgili oyuncusunu rate'le
        [
            "POST", 
            "/api/matches/{matchId}/players/{playerId}", 
            "RatingController", 
            "rate", 
            ["AuthMiddleware", "RateLimitMiddleware"]
        ], 
    ];

    return $routes;