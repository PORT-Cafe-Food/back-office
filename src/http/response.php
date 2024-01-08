<?php
// Define the Response class
class Response
{
    public static function send(int $statusCode, $data = null, $headers = [], $cookies = [])
    {
        // Send headers
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }

        // Set cookies
        foreach ($cookies as $name => $value) {
            setcookie($name, $value, time() + 3600, '/'); // adjust parameters as needed
        }

        // Set status code
        http_response_code($statusCode);

        // Output JSON response
        echo json_encode($data, JSON_PRETTY_PRINT);

        // Terminate script
        exit();
    }
}
