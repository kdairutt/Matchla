<?php
    namespace Matchla;

    class Router {
        private array $routes = [];

        public function load(array $routes): void {
            $this->routes = $routes;
        }

        public function dispatch(string $method, string $uri): void {
            foreach($this->routes as $route) {
                [$routeMethod, $routeUri, $controller, $action] = $route;

                if($method !== $routeMethod) continue;

                $pattern = preg_replace("/\{[^}]+\}/", "([^/]+)", $routeUri);
                $pattern = "#^" . $pattern . "$#";

                if(preg_match($pattern, $uri, $matches)) {
                    $controllerClass = "Matchla\\Controllers\\" . $controller;
                    $instance = new $controllerClass();

                    array_shift($matches);
                    $instance->$action(...$matches);
                    return;
                }
            }

            http_response_code(404);
            echo json_encode([
                "error" => "page not found"
            ]);
        }
    }