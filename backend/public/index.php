<?php
    use Matchla\Router;
    
    require "../vendor/autoload.php";

    $routes = require "../routes/api.php";

    $router = new Router();

    $router->load($routes);

    $router->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);