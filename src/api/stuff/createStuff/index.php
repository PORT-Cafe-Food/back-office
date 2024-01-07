<?php

// Include the database connection file
require_once('../../../core/db.php');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    die('Error: Only POST requests are allowed.');
}

// Get JSON data from the request body
$jsonData = file_get_contents("php://input");

// Check if JSON data is present
if (!$jsonData) {
    http_response_code(400); // Bad Request
    die('Error: JSON data is missing.');
}

// Decode JSON data
$data = json_decode($jsonData, true);

// Check if JSON decoding is successful
if ($data === null) {
    http_response_code(400); // Bad Request
    die('Error: Invalid JSON data.');
}

// Extract user information from JSON data
$username = isset($data['username']) ? $data['username'] : '';
$password = isset($data['password']) ? $data['password'] : '';
$fullName = isset($data['fullName']) ? $data['fullName'] : '';
$email = isset($data['email']) ? $data['email'] : '';
$role = isset($data['role']) ? $data['role'] : 'staff';  // Default role is 'staff'

// Validate required fields
if (empty($username) || empty($password) || empty($fullName) || empty($email)) {
    http_response_code(400); // Bad Request
    die('Error: Required fields are missing.');
}

// Check if the username or email already exists
$sqlCheck = "SELECT stuff_id FROM Stuff WHERE username = ? OR email = ?";
$stmtCheck = $conn->prepare($sqlCheck);

if ($stmtCheck) {
    $stmtCheck->bind_param("ss", $username, $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    // If a user with the same username or email already exists, return an error
    if ($stmtCheck->num_rows > 0) {
        http_response_code(409); // Conflict
        echo 'Error: User with the same username or email already exists.';
        $stmtCheck->close();
        $conn->close();
        exit;
    }

    $stmtCheck->close();
} else {
    http_response_code(500); // Internal Server Error
    echo 'Error preparing the database statement: ' . $conn->error;
    $conn->close();
    exit;
}

// Hash the password (use a secure hashing algorithm like bcrypt)
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// Insert user information into the Stuff table
$sqlInsert = "INSERT INTO Stuff (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);

if ($stmtInsert) {
    $stmtInsert->bind_param("sssss", $username, $passwordHash, $fullName, $email, $role);
    $stmtInsert->execute();

    // Check if the insertion was successful
    if ($stmtInsert->affected_rows > 0) {
        http_response_code(201); // Created
        echo 'User created successfully.';
    } else {
        http_response_code(500); // Internal Server Error
        echo 'Error: User creation failed.';
    }

    $stmtInsert->close();
} else {
    http_response_code(500); // Internal Server Error
    echo 'Error preparing the database statement: ' . $conn->error;
}

// Close the database connection
$conn->close();
