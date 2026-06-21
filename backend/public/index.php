<?php
    use Matchla\Router;
    
    require "../vendor/autoload.php";

    // .env bilgilerini yükle
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();

    $routes = require "../routes/api.php";

    $router = new Router();
    $router->load($routes);
    $router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);