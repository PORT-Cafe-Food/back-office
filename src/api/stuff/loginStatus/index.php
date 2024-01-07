<?php

// Include the database connection file with the correct relative path
require_once('../../../core/db.php');

// Function to check if the user is logged in
function isLoggedIn()
{
    global $conn; // Include the global connection variable
    // Start or resume the session
    session_start();

    // Check if the user has an active session
    if (isset($_SESSION['stuff_id']) && isset($_SESSION['username'])) {
        return true;
    }

    // If no active session, check for a bearer token in the cookie
    if (isset($_COOKIE['bearer'])) {
        $token = $_COOKIE['bearer'];

        // Check if the token exists in the Session table
        $checkTokenSQL = "SELECT stuff_id FROM Sessions WHERE jwt_token = ?";
        $stmtCheckToken = $conn->prepare($checkTokenSQL);

        if ($stmtCheckToken) {
            $stmtCheckToken->bind_param("s", $token);
            $stmtCheckToken->execute();
            $stmtCheckToken->store_result();

            if ($stmtCheckToken->num_rows > 0) {
                return true;
            }
        }

        $stmtCheckToken->close();
    }

    return false;
}

// Check login status
if (isLoggedIn()) {
    http_response_code(200); // OK
    echo json_encode(array("loggedIn" => true));
} else {
    http_response_code(401); // Unauthorized
    echo json_encode(array("loggedIn" => false));
}

// Close the database connection
$conn->close();
