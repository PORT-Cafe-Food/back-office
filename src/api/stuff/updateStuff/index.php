<?php

// updateStuff.php

require_once('../../../core/db.php');
require_once('authDepends.php');

function updateStuff($stuffId, $data)
{
    global $conn;

    $checkStuffSQL = "SELECT * FROM Stuff WHERE stuff_id = ?";
    $stmtCheckStuff = $conn->prepare($checkStuffSQL);

    if ($stmtCheckStuff) {
        $stmtCheckStuff->bind_param("i", $stuffId);
        $stmtCheckStuff->execute();
        $result = $stmtCheckStuff->get_result();

        if ($result->num_rows > 0) {
            $stuffData = $result->fetch_assoc();
            $currentPassword = isset($data['currentPassword']) ? $data['currentPassword'] : null;
            $newPassword = isset($data['newPassword']) ? password_hash($data['newPassword'], PASSWORD_DEFAULT) : null;

            if (!password_verify($currentPassword, $stuffData['password_hash'])) {
                http_response_code(401); // Unauthorized
                echo json_encode(array("message" => "Error: Incorrect current password."));
                return;
            }

            $updateStuffSQL = "UPDATE Stuff SET";
            $updateParams = array();

            if (isset($data['username'])) {
                $updateStuffSQL .= " username = ?,";
                $updateParams[] = $data['username'];
            }

            if ($newPassword !== null) {
                $updateStuffSQL .= " password_hash = ?,";
                $updateParams[] = $newPassword;
            }

            if (isset($data['fullName'])) {
                $updateStuffSQL .= " full_name = ?,";
                $updateParams[] = $data['fullName'];
            }

            if (isset($data['email'])) {
                $updateStuffSQL .= " email = ?,";
                $updateParams[] = $data['email'];
            }

            if (isset($data['role'])) {
                $updateStuffSQL .= " role = ?,";
                $updateParams[] = $data['role'];
            }

            $updateStuffSQL = rtrim($updateStuffSQL, ',');
            $updateStuffSQL .= " WHERE stuff_id = ?";
            $updateParams[] = $stuffId;

            $stmtUpdateStuff = $conn->prepare($updateStuffSQL);

            if ($stmtUpdateStuff) {
                $bindTypes = str_repeat('s', count($updateParams));
                $stmtUpdateStuff->bind_param($bindTypes, ...$updateParams);

                $stmtUpdateStuff->execute();
                $stmtUpdateStuff->close();

                http_response_code(200); // OK
                echo json_encode(array("message" => "Stuff updated successfully."));
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode(array("message" => "Error preparing the UPDATE query: " . $conn->error));
            }
        } else {
            http_response_code(404); // Not Found
            echo json_encode(array("message" => "Error: Stuff not found."));
        }

        $stmtCheckStuff->close();
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("message" => "Error preparing the SELECT query: " . $conn->error));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $jsonData = file_get_contents("php://input");

    if (!$jsonData) {
        http_response_code(400); // Bad Request
        die('Error: JSON data is missing.');
    }

    $data = json_decode($jsonData, true);

    if ($data === null) {
        http_response_code(400); // Bad Request
        die('Error: Invalid JSON data.');
    }

    $stuffId = getStuffIdFromBearerToken();

    if ($stuffId === null || $stuffId <= 0) {
        http_response_code(401); // Unauthorized
        die('Error: Invalid or missing stuff_id.');
    }

    updateStuff($stuffId, $data);
} else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(array("message" => "Error: Only PUT requests are allowed."));
}

$conn->close();
