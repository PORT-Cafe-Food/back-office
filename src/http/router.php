<?php

require_once('response.php');

// Define the Router class
class Router
{
    private $routes = [];
    private $prefix = null;

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
    }

    public function addRoute($method, $path, callable $handler)
    {
        $this->routes[] = ['method' => $method, 'path' => $path, 'handler' => $handler];
    }

    private function getScriptName()
    {
        return basename($_SERVER['SCRIPT_NAME']);
    }

    private function buildRoutePath($path)
    {
        if ($this->prefix) {
            $path = '/' . trim($this->prefix, '/') . $path;
        } else {
            $path = '/' . trim($path, '/');
        }
        return '/' . $this->getScriptName() . $path;
    }

    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $jsonData = json_decode(file_get_contents('php://input'), true);

        foreach ($this->routes as $route) {
            $routePath = $this->buildRoutePath($route['path']);

            if ($route['method'] === $method && $routePath === $path) {
                return call_user_func($route['handler'], $jsonData);
            }
        }

        // If no matching route is found, return a 404 response
        return Response::send(404, ['error' => 'Not Found']);
    }
}
