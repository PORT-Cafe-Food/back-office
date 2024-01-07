<?php

// Include the database connection file with the correct relative path
require_once('../../../core/db.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Extract login information from JSON data
$username = isset($data['username']) ? $data['username'] : '';
$password = isset($data['password']) ? $data['password'] : '';

// Validate required fields
if (empty($username) || empty($password)) {
    http_response_code(400); // Bad Request
    die('Error: Required fields are missing.');
}

// Check if the user with the given username exists
$sql = "SELECT stuff_id, password_hash FROM Stuff WHERE username = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // If a user with the username exists, verify the password
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($stuffId, $storedPasswordHash);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $storedPasswordHash)) {
            // Password is correct, create session and set cookie
            session_start();

            // Generate random JWT (bearer token)
            $token = bin2hex(random_bytes(32));

            // Set session variables
            $_SESSION['stuff_id'] = $stuffId;
            $_SESSION['username'] = $username;
            $_SESSION['last_login_at'] = date('Y-m-d H:i:s');
            $_SESSION['last_login_ip'] = $_SERVER['REMOTE_ADDR'];

            // Set expiresAt NOW() + Interval 8 hours
            $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours'));

            // Update Session table with session information
            $updateSessionSQL = "INSERT INTO Sessions (stuff_id, jwt_token, lastLoginAt, lastLoginIp, expiresAt) VALUES (?, ?, NOW(), ?, ?) ON DUPLICATE KEY UPDATE jwt_token = VALUES(jwt_token), lastLoginAt = VALUES(lastLoginAt), lastLoginIp = VALUES(lastLoginIp), expiresAt = VALUES(expiresAt)";
            $stmtUpdateSession = $conn->prepare($updateSessionSQL);

            if ($stmtUpdateSession) {
                $stmtUpdateSession->bind_param("isss", $stuffId, $token, $_SESSION['last_login_ip'], $expiresAt);
                $stmtUpdateSession->execute();
                $stmtUpdateSession->close();
            }

            // Set bearer token in cookie
            setcookie("bearer", $token, strtotime($expiresAt), "/", "", false, true); // Set cookie until expiresAt

            // Respond with success and token
            http_response_code(200); // OK
            echo json_encode(array("message" => "Login successful.", "token" => $token));
        } else {
            // Password is incorrect
            http_response_code(401); // Unauthorized
            echo 'Error: Incorrect password.';
        }
    } else {
        // User with the given username does not exist
        http_response_code(404); // Not Found
        echo 'Error: User not found.';
    }

    $stmt->close();
} else {
    http_response_code(500); // Internal Server Error
    echo 'Error preparing the database statement: ' . $conn->error;
}

// Close the database connection
$conn->close();
