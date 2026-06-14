<?php
    $routes = [
        ["POST", "/api/auth/register", "AuthController", "register"], // kayıt ol
        ["POST", "/api/auth/login", "AuthController", "login"], // giriş yap

        ["GET", "/api/matches", "MatchController", "index"], // yakın çevredeki tüm maçlar
        ["GET", "/api/matches/{id}", "MatchController", "show"], // spesifik bir maç
        ["POST", "/api/matches", "MatchController", "create"], // maç oluştur
        ["PUT", "/api/matches/{id}", "MatchController", "update"], // maçı güncelle
        ["DELETE", "/api/matches/{id}", "MatchController", "delete"], // maçı sil
        
        ["GET", "/api/players/{id}", "PlayerController", "show"], // spesifik bir oyuncuyu görüntüle

        ["GET", "/api/matches/{id}/candidates", "CandidateController", "index"], // maçın adaylarını görüntüle
        ["POST", "/api/matches/{id}/apply", "CandidateController", "apply"], // maça başvur

        ["PUT", "/api/matches/{matchId}/candidates/{candidateId}", "CandidateController", "decide"], // ilgili maçın adayını reddet/kabul et

        ["POST", "/api/matches/{matchId}/players/{playerId}", "RatingController", "rate"], // ilgili maçın ilgili oyuncusunu rate'le
    ];