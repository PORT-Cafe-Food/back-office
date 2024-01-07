<?php

// Include the ApiResponse class file
require_once '../../core/ApiResponse.php';

// Sample usage
$response = new ApiResponse(true, ['key' => 'value'], null, 'Request successful');

// Output the JSON-formatted response

echo $response->toJson();
