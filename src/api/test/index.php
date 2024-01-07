<?php

require_once('../../http/response.php');
require_once('../../http/router.php');

// Create an instance of the Router
$router = new Router();

// Define routes
$router->addRoute('GET', '/route1', function ($jsonData) {
    return Response::send(200, ['message' => 'GET Route 1', 'data' => $jsonData]);
});

$router->addRoute('POST', '/route2', function ($jsonData) {
    return Response::send(200, ['message' => 'POST Route 2', 'data' => $jsonData]);
});


echo $router->handleRequest($method, $path, $jsonData);
