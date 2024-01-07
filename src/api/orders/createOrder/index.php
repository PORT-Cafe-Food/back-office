<?php

// Include the database connection file
// require_once('../../core/auth.php');
require_once('../../../core/db.php');
require_once('../../../core/ApiResponse.php');
require_once('requestData.php');
// Get the JSON data from the POST request
$jsonData = file_get_contents('php://input');


$data = new OrderCreate($jsonData);

// Insert data into the database
$conn->begin_transaction();

try {
    // Check if the customer already exists
    $result = $conn->query("SELECT customer_id FROM Customers WHERE phonenumber = '{$data->customer['phonenumber']}'");

    if ($result->num_rows > 0) {
        // Customer exists, retrieve customer_id
        $customerId = $result->fetch_assoc()['customer_id'];
    } else {
        // Insert new customer
        $stmtCustomer = $conn->prepare("INSERT INTO Customers (fullname, phonenumber, address, houseNumber, floorNumber, apartmentNumber, addressDescription) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtCustomer->bind_param(
            "sssssss",
            $data->customer['fullname'],
            $data->customer['phonenumber'],
            $data->customer['address'],
            $data->customer['houseNumber'],
            $data->customer['floorNumber'],
            $data->customer['apartmentNumber'],
            $data->customer['addressDescription']
        );
        $stmtCustomer->execute();

        $customerId = $stmtCustomer->insert_id;

        // Close the customer statement
        $stmtCustomer->close();
    }

    // Insert order data into Orders table
    $stmtOrder = $conn->prepare("INSERT INTO Orders (orderType, smsNotify, deliveryFee, timeToPrepare, customer_id, createdAt) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtOrder->bind_param(
        "sdiiss",
        $data->orderType,
        $data->smsNotify,
        $data->deliveryFee,
        $data->timeToPrepare,
        $data->customer['customer_id'],
        $data->deliveryTime
    );
    $stmtOrder->execute();

    $orderId = $stmtOrder->insert_id;

    // Insert or ignore items into Items table and order details into OrderDetails table
    $stmtItem = $conn->prepare("INSERT IGNORE INTO Items (name, price) VALUES (?, ?)");
    $stmtOrderDetails = $conn->prepare("INSERT INTO OrderDetails (order_id, item_id, quantity, total_price, size, options, description, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    $items = $data->items;

    foreach ($items as $item) {
        // Check if the item already exists
        $result = $conn->query("SELECT item_id FROM Items WHERE name = '{$item['name']}'");

        if ($result->num_rows > 0) {
            // Item exists, retrieve item_id
            $itemId = $result->fetch_assoc()['item_id'];
        } else {
            // Insert new item
            $stmtItem->bind_param("sd", $item['name'], $item['price']);
            $stmtItem->execute();

            $itemId = $stmtItem->insert_id;

            // Close the item statement
            $stmtItem->close();
        }

        // Insert the order details with the obtained item_id
        $stmtOrderDetails->bind_param("iiissssi", $orderId, $itemId, $item['quantity'], $item['total_price'], $item['size'], implode(',', $item['options']), $item['description'], $item['category_id']);
        $stmtOrderDetails->execute();
    }

    // Commit the transaction if all queries succeed
    $conn->commit();

    // Close statements and connection
    $stmtOrder->close();
    $stmtOrderDetails->close();
    $conn->close();

    http_response_code(200);
    // return order id
    $response = new ApiResponse(
        true,
        json_decode(json_encode(array("orderId" => $orderId))),
        null,
        "Order created successfully."
    );
    echo $response->toJson();
} catch (Exception $e) {
    // Rollback the transaction if any query fails
    $conn->rollback();

    http_response_code(500);
    die('Error processing order: ' . $e->getMessage());
}
