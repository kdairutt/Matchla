<?php

    $routes = [
        // kayıt ol
        [
            "POST", 
            "/api/auth/register", 
            "AuthController", 
            "register", 
            ["rateLimitMiddleware"]
        ],

        // giriş yap
        [
            "POST", 
            "/api/auth/login", 
            "AuthController", 
            "login", 
            ["rateLimitMiddleware"]
        ], 

        // yakın çevredeki tüm maçlar
        [
            "GET", 
            "/api/matches", 
            "MatchController", 
            "index", 
            ["authMiddleware", "rateLimitMiddleware"]
        ],

        // spesifik bir maç
        [
            "GET", 
            "/api/matches/{id}", 
            "MatchController", 
            "show", 
            ["authMiddleware", "rateLimitMiddleware"]
        ],

        // maç oluştur
        [
            "POST", 
            "/api/matches", 
            "MatchController", 
            "create", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 
        
        // maçı güncelle
        [
            "PUT", 
            "/api/matches/{id}", 
            "MatchController", 
            "update", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 

        // maçı sil
        [
            "DELETE", 
            "/api/matches/{id}", 
            "MatchController", 
            "delete", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 
        
        // spesifik bir oyuncuyu görüntüle
        [
            "GET", 
            "/api/players/{id}", 
            "PlayerController", 
            "show", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 
        
        // maçın adaylarını görüntüle
        [
            "GET",
            "/api/matches/{id}/candidates",
            "CandidateController", 
            "index", 
            ["authMiddleware", "rateLimitMiddleware"]
        ],

        // maça başvur
        [
            "POST",
            "/api/matches/{id}/apply", 
            "CandidateController", 
            "apply", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 
        
        // ilgili maçın adayını reddet/kabul et
        [
            "PUT", 
            "/api/matches/{matchId}/candidates/{candidateId}", 
            "CandidateController", 
            "decide", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 

        // ilgili maçın ilgili oyuncusunu rate'le
        [
            "POST", 
            "/api/matches/{matchId}/players/{playerId}", 
            "RatingController", 
            "rate", 
            ["authMiddleware", "rateLimitMiddleware"]
        ], 
    ];

    return $routes;