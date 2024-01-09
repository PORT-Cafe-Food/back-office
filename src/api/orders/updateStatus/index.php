<?php

include '../../../core/db.php'; // Path to your database connection file

// Function to update the order status
function updateOrderStatus($conn, $orderId, $newStatus, $declineReason = null, $fullfilIn = null)
{
    // Check if the order exists
    $checkOrderQuery = $conn->prepare("SELECT id FROM orders WHERE id = ?");
    $checkOrderQuery->bind_param('i', $orderId);
    $checkOrderQuery->execute();
    $result = $checkOrderQuery->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404); // Set response status code to 404 (Not Found)
        die('Order not found');
    }

    // Prepare the SQL statement to update the order status based on the provided status
    $updateOrderQuery = null;

    switch ($newStatus) {
        case 'accepted':
            if ($fullfilIn === null) {
                http_response_code(400); // Set response status code to 400 (Bad Request)
                die('Missing fullfilAt');
            }
            // calculate fullfilAt date from fullfilIn in minutes
            $fullfilAt = date('Y-m-d H:i:s', strtotime('+' . $fullfilIn . ' minutes'));
            $updateOrderQuery = $conn->prepare("UPDATE orders SET acceptedAt = NOW(), fullfilAt = ?, status = 'accepted' WHERE id = ?");
            $updateOrderQuery->bind_param('si', $fullfilAt, $orderId);
            break;
        case 'declined':
            if ($declineReason === null) {
                http_response_code(400); // Set response status code to 400 (Bad Request)
                die('Missing declineReason');
            }
            $updateOrderQuery = $conn->prepare("UPDATE orders SET declinedAt = NOW(), status = 'declined', declineReason = ? WHERE id = ?");
            $updateOrderQuery->bind_param('si', $declineReason, $orderId);
            break;
        case 'pending':
            $updateOrderQuery = $conn->prepare("UPDATE orders SET status = 'pending' WHERE id = ?");
            $updateOrderQuery->bind_param('i', $orderId);
            break;
            // Add more cases for other status values as needed

        case 'fulfilled':
            // update kitchenDoneAt to current time
            $updateOrderQuery = $conn->prepare("UPDATE orders SET kitchenDoneAt = NOW(), status = 'fulfilled' WHERE id = ?");
            $updateOrderQuery->bind_param('i', $orderId);

            // check if order type is delivery
            $checkOrderTypeQuery = $conn->prepare("SELECT type FROM orders WHERE id = ?");
            $checkOrderTypeQuery->bind_param('i', $orderId);
            $checkOrderTypeQuery->execute();
            $result = $checkOrderTypeQuery->get_result();
            $orderType = $result->fetch_assoc()['type'];

            if ($orderType === 'delivery') {
                // Check if orderId already exists in orderDeliveryStatus table
                $checkDeliveryStatusQuery = $conn->prepare("SELECT orderId FROM orderDeliveryStatus WHERE orderId = ?");
                $checkDeliveryStatusQuery->bind_param('i', $orderId);
                $checkDeliveryStatusQuery->execute();
                $result = $checkDeliveryStatusQuery->get_result();

                if ($result->num_rows === 0) {
                    // update deliveryDoneAt to current time
                    $updateDeliveryDoneQuery = $conn->prepare("UPDATE orders SET deliveryDoneAt = NOW() WHERE id = ?");
                    $updateDeliveryDoneQuery->bind_param('i', $orderId);
                    $updateDeliveryDoneQuery->execute();

                    // Insert a row in orderDeliveryStatus
                    $insertDeliveryStatusQuery = $conn->prepare("INSERT INTO orderDeliveryStatus (orderId, status, createdAt) VALUES (?, ?, NOW())");
                    $insertDeliveryStatusQuery->bind_param('is', $orderId, $newStatus);
                    $insertDeliveryStatusQuery->execute();
                } else {
                    http_response_code(400); // Set response status code to 400 (Bad Request)
                    die('Order already dispatched');
                }
            }
            break;
        default:
            http_response_code(400); // Set response status code to 400 (Bad Request)
            die('Invalid status. Supported values: "accepted", "declined", "pending", "fulfilled"');
            break;
    }

    // Execute the SQL statement


    if ($updateOrderQuery->execute()) {
        return ['success' => true, 'message' => 'Order status updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Error updating order status'];
    }
}

// Get orderId, newStatus, and declineReason from the query parameters
$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : null;
$newStatus = isset($_GET['newStatus']) ? $_GET['newStatus'] : null;
$declineReason = isset($_GET['declineReason']) ? $_GET['declineReason'] : null;
$fullfilIn = isset($_GET['fullfilIn']) ? $_GET['fullfilIn'] : null;

if ($orderId === null || $newStatus === null) {
    http_response_code(400); // Set response status code to 400 (Bad Request)
    die('Missing orderId or newStatus');
}

// Update the order status
$result = updateOrderStatus($conn, $orderId, $newStatus, $declineReason, $fullfilIn);

// Respond with JSON
echo json_encode($result, JSON_PRETTY_PRINT);

// Close the database connection
$conn->close();
