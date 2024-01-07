<?php

// Include the database connection file
require_once('../../../core/db.php');
require_once('../../../core/ApiResponse.php');
require_once('requestData.php');

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

$data = new CreateSuffRequest($jsonData);


// Check if JSON decoding is successful
if ($data === null) {
    http_response_code(400); // Bad Request
    die('Error: Invalid JSON data.');
}

// Check if the username or email already exists
$sqlCheck = "SELECT stuff_id FROM Stuff WHERE username = ? OR email = ?";
$stmtCheck = $conn->prepare($sqlCheck);

if ($stmtCheck) {
    $stmtCheck->bind_param("ss", $data->username, $data->email);
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
$passwordHash = password_hash($data->password, PASSWORD_BCRYPT);

// Insert user information into the Stuff table
$sqlInsert = "INSERT INTO Stuff (username, password_hash, full_name, email, role) VALUES (?, ?, ?, ?, ?)";
$stmtInsert = $conn->prepare($sqlInsert);

if ($stmtInsert) {
    $stmtInsert->bind_param("sssss", $data->username, $passwordHash, $data->fullname, $data->email, $data->role);
    $stmtInsert->execute();

    // Check if the insertion was successful
    if ($stmtInsert->affected_rows > 0) {
        http_response_code(201); // Created
        $response = new ApiResponse(true, null, null, "Stuff created successfully.");
        echo json_encode($response);
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
