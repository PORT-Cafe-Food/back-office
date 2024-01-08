<?php
include '../../../core/db.php'; // Path to your database connection file

function insertCustomer($conn, $customerData)
{
    $insertCustomer = $conn->prepare("INSERT INTO customers (fullname, phonenumber, email, address, houseNumber, floorNumber, aptNumber, addressDescription, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    if ($insertCustomer === false) {
        throw new Exception($conn->error);
    }
    $insertCustomer->bind_param('ssssssss', $customerData['fullname'], $customerData['phonenumber'], $customerData['email'], $customerData['address'], $customerData['houseNumber'], $customerData['floorNumber'], $customerData['aptNumber'], $customerData['addressDescription']);
    $insertCustomer->execute();
    return $conn->insert_id; // Return the ID of the newly created customer
}

function insertOrder($conn, $orderData, $customerId)
{
    $insertOrder = $conn->prepare("INSERT INTO orders (createdAt, type, tableNumber, customerId, status) VALUES (NOW(), ?, ?, ?, 'pending')");
    $insertOrder->bind_param('ssi', $orderData['type'], $orderData['tableNumber'], $customerId);
    $insertOrder->execute();
    return $conn->insert_id; // Return the ID of the newly created order
}

function insertOrderItem($conn, $orderId, $item)
{
    $itemId = $item['itemId'];
    $sizeId = $item['sizeId'];
    $quantity = $item['quantity'];


    $insertOrderItem = $conn->prepare("INSERT INTO orderItems (orderId, itemId, sizeId, quantity) VALUES (?, ?, ?, ?)");
    if ($insertOrderItem === false) {
        echo ("Error preparing insertOrderItem statement: " . $conn->error); // Debugging line
        return null;
    }
    $insertOrderItem->bind_param('iiii', $orderId, $itemId, $sizeId, $quantity);

    if ($insertOrderItem->execute()) {
        $orderItemId = $conn->insert_id;
        return $orderItemId;
    } else {
        error_log("Error executing insertOrderItem statement: " . $insertOrderItem->error); // Debugging line
        return null;
    }
}

function insertOrderOption($conn, $orderItemId, $optionId)
{
    $insertOrderOption = $conn->prepare("INSERT INTO orderOptions (orderItemId, optionId) VALUES (?, ?)");
    if ($insertOrderOption === false) {
        throw new Exception("Order option insert failed: " . $conn->error);
    }
    $insertOrderOption->bind_param('ii', $orderItemId, $optionId);
    $insertOrderOption->execute();
    if ($insertOrderOption->affected_rows === 0) {
        throw new Exception("No rows affected when inserting order option");
    }
}

function placeOrder($conn, $orderData)
{
    $customerData = $orderData['customer'];
    $order = $orderData['order'];
    $orderItems = $orderData['orderItems'];

    $conn->autocommit(FALSE); // Start transaction

    try {
        // Check if customer exists
        $customerQuery = $conn->prepare("SELECT id FROM customers WHERE phonenumber = ?");
        $customerQuery->bind_param('s', $customerData['phonenumber']);
        $customerQuery->execute();
        $result = $customerQuery->get_result();
        $customer = $result->fetch_assoc();

        $customerId = $customer ? $customer['id'] : insertCustomer($conn, $customerData);

        // Create the order
        $orderId = insertOrder($conn, $order, $customerId);

        // Insert order items and options
        foreach ($orderItems as $item) {
            $orderItemId = insertOrderItem($conn, $orderId, $item);

            foreach ($item['options'] as $optionId) {
                insertOrderOption($conn, $orderItemId, $optionId);
            }
        }


        $conn->commit(); // Commit the transaction
        return ['success' => true, 'message' => 'Order placed successfully', 'orderId' => $orderId];
    } catch (Exception $e) {
        $conn->rollback(); // Rollback the transaction on error
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Example usage
$orderData = json_decode(file_get_contents('php://input'), true); // Assuming JSON is sent via POST
$result = placeOrder($conn, $orderData);

echo json_encode($result, JSON_PRETTY_PRINT);
