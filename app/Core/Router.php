<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function add($method, $uri, $controllerAction) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'controllerAction' => $controllerAction
        ];
    }

    public function dispatch($uri, $method) {
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        foreach ($this->routes as $route) {
            if ($route['uri'] === $uri && $route['method'] === strtoupper($method)) {
                if (is_array($route['controllerAction'])) {
                    $controller = new $route['controllerAction'][0]();
                    $action = $route['controllerAction'][1];
                    return $controller->$action();
                }
                
                if (is_callable($route['controllerAction'])) {
                    return call_user_func($route['controllerAction']);
                }
            }
        }
        
        http_response_code(404);
        echo "404 Not Found";
    }
}
