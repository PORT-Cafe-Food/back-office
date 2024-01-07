<?php

try {
    $url = "https://pos.globalfoodsoft.com/pos/menu";

    // Set headers
    $headers = [
        "http" => [
            "header" => "Authorization: E67nlcOGmU1Y6jwXOx\r\n" .
                "Accept: application/json\r\n" .
                "Glf-Api-Version: 2\r\n",
        ],
    ];

    // Create a stream context with headers
    $context = stream_context_create($headers);

    // Make the HTTP request and get the response
    $response = file_get_contents($url, false, $context);

    // Check for errors during the HTTP request
    if ($response === false) {
        throw new Exception('Error making HTTP request');
    }

    // Decode the JSON response
    $jsonResponse = json_decode($response, true);

    // Check if JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error decoding JSON: ' . json_last_error_msg());
    }

    // Output the JSON response
    echo json_encode($jsonResponse, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // Handle exceptions
    $errorResponse = [
        'status' => 'error',
        'message' => 'Exception: ' . $e->getMessage(),
    ];

    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}
