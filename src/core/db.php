<?php

// Function to load variables from a .env file

// include loadEnv from utils/loadEnv.php
require_once('../../../utils/loadEnv.php');
// Specify the path to your .env file
$envFilePath = '../../../.env';

try {
    // Load environment variables from the .env file
    loadEnv($envFilePath);

    // Database connection parameters
    $host = getenv('DB_HOST');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    $database = getenv('DB_DATABASE');

    // Create a connection
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . " Host: $host, Username: $username, Database: $database");
    }

    // Now you can use $conn for database operations

} catch (Exception $e) {
    // Handle exceptions, e.g., file not found or error reading the file
    echo "Error: " . $e->getMessage() . "\n";
}
