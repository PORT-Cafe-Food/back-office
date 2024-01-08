<?php

include '../../../core/db.php'; // Path to your database connection file

// Function to get an order by orderId or return a 404 error
function getOrderById($conn, $orderId)
{
    // Query to retrieve the order by orderId
    $orderQuery = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $orderQuery->bind_param('i', $orderId);
    $orderQuery->execute();
    $result = $orderQuery->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404); // Set response status code to 404 (Not Found)
        die('Order not found');
    }

    return $result->fetch_assoc();
}

function getOrderItems($conn, $orderId)
{
    // Query to retrieve the order items by orderId
    $orderItemsQuery = $conn->prepare("SELECT * FROM orderItems WHERE orderId = ?");

    $orderItemsQuery->bind_param('i', $orderId);
    $orderItemsQuery->execute();
    $result = $orderItemsQuery->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getItemSizes($conn, $itemId)
{
    // Query to retrieve the item sizes by itemId
    $itemSizesQuery = $conn->prepare("SELECT * FROM sizes WHERE itemId = ?");

    $itemSizesQuery->bind_param('i', $itemId);
    $itemSizesQuery->execute();
    $result = $itemSizesQuery->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getItemOptions($conn, $itemId)
{
    // Query to retrieve the item options by itemId
    $itemOptionsQuery = $conn->prepare("SELECT * FROM orderOptions WHERE orderItemId = ?");

    $itemOptionsQuery->bind_param('i', $itemId);
    $itemOptionsQuery->execute();
    $result = $itemOptionsQuery->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getOption($conn, $optionId)
{
    // Query to retrieve the option by optionId
    $optionQuery = $conn->prepare("SELECT * FROM options WHERE id = ?");

    $optionQuery->bind_param('i', $optionId);
    $optionQuery->execute();
    $result = $optionQuery->get_result();

    return $result->fetch_assoc();
}
// Find orderId from the URL
$orderId = isset($_GET['orderId']) ? intval($_GET['orderId']) : null;
if ($orderId === null) {
    http_response_code(400); // Set response status code to 400 (Bad Request)
    die('Missing orderId');
}



// Get the order by orderId
$order = getOrderById($conn, $orderId);

// Get the order items by orderId
$orderItems = getOrderItems($conn, $orderId);

// Iterate through the order items
foreach ($orderItems as &$item) {
    // Get the item sizes
    $itemSizes = getItemSizes($conn, $item['sizeId']);
    $item['sizes'] = $itemSizes;

    // Get the item options
    $itemOptions = getItemOptions($conn, $orderId);

    // define empty array for options to be pushed into
    $options = array();
    foreach ($itemOptions as $option) {
        // Get the option
        $optionData = getOption($conn, $option['optionId']);
        // Push the option into the options array
        array_push($options, $optionData);
    }
    // Push the options array into the item
    $item['options'] = $options;
}

echo json_encode(array(
    'order' => $order,
    'orderItems' => $orderItems,
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Close the database connection
$conn->close();
