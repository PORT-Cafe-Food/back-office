<?php

// Include the database connection file with the correct relative path
require_once('../../../core/db.php');

// Function to destroy the session and clear the bearer token cookie
function logout()
{
    global $conn; // Include the global connection variable

    // Start or resume the session
    session_start();

    // Get the JWT token from the session or cookie
    $token = isset($_SESSION['bearer']) ? $_SESSION['bearer'] : (isset($_COOKIE['bearer']) ? $_COOKIE['bearer'] : null);

    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Clear the bearer token cookie
    setcookie("bearer", "", time() - 3600, "/", "", false, true); // Set cookie in the past to expire it

    // If a token is available, delete the corresponding session entry
    if ($token !== null) {
        $deleteSessionSQL = "DELETE FROM Sessions WHERE jwt_token = ?";
        $stmtDeleteSession = $conn->prepare($deleteSessionSQL);

        if ($stmtDeleteSession) {
            $stmtDeleteSession->bind_param("s", $token);
            $stmtDeleteSession->execute();
            $stmtDeleteSession->close();
        }
    }

    // Respond with a success message
    http_response_code(200); // OK
    echo json_encode(array("message" => "Logout successful."));
}

// Check if the request method is DELETE
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Call the logout function
    logout();
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("message" => "Error: Only DELETE requests are allowed."));
}

// Close the database connection
$conn->close();
