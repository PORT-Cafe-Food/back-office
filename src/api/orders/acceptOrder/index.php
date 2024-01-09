<?php

include '../../../core/db.php'; // Path to your database connection file

// Function to accept an order
function acceptOrder($conn, $orderId)
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

    // Update the order with the current timestamp and status
    $updateOrderQuery = $conn->prepare("UPDATE orders SET acceptedAt = NOW(), status = 'accepted' WHERE id = ?");
    $updateOrderQuery->bind_param('i', $orderId);

    if ($updateOrderQuery->execute()) {
        return ['success' => true, 'message' => 'Order accepted successfully'];
    } else {
        return ['success' => false, 'message' => 'Error accepting the order'];
    }
}

// Get orderId from the query parameters
$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : null;

if ($orderId === null) {
    http_response_code(400); // Set response status code to 400 (Bad Request)
    die('Missing orderId');
}

// Accept the order
$result = acceptOrder($conn, $orderId);

// Respond with JSON
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);

// Close the database connection
$conn->close();
