<?php
    namespace Matchla;

    class Router
    {
        private array $routes = [];

        public function load(array $routes): void
        {
            $this->routes = $routes;
        }

        public function dispatch(string $method, string $uri): void 
        {
            foreach($this->routes as $route) {
                [$routeMethod, $routeUri, $controller, $action] = $route;

                if($method === $routeMethod && $uri === $routeUri) {
                    $controllerClass = "Matchla\\Controllers\\" . $controller;
                    $instance = new $controllerClass();

                    $instance->$action();
                    return;
                }
            }
        }
    }