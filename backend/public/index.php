<?php
    use Matchla\Router;
    
    require "../vendor/autoload.php";

    // .env bilgilerini yükle
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/../");
    $dotenv->load();

    $routes = require "../routes/api.php";
    
    $uri = strtok($_SERVER["REQUEST_URI"], "?");
    $method = $_SERVER["REQUEST_METHOD"];

    $router = new Router();
    $router->load($routes);
    $router->dispatch($method, $uri);