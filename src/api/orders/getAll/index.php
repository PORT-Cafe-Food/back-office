<?php

// Include the database connection file
// require_once('../../core/auth.php');
require_once('../../../core/db.php');
require_once('../../../core/Response.php');
require_once('orderResponse.php');


try {

    // Get filter parameters from the query string
    $isReady = isset($_GET['isReady']) ? filter_var($_GET['isReady'], FILTER_VALIDATE_BOOLEAN) : null;
    $startDate = isset($_GET['startDate']) ? $_GET['startDate'] : null;
    $endDate = isset($_GET['endDate']) ? $_GET['endDate'] : null;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(1, intval($_GET['pageSize'])) : 10;
    $orderType = isset($_GET['orderType']) ? $_GET['orderType'] : null;
    // enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Calculate the offset for pagination
    $offset = ($page - 1) * $pageSize;

    // Build the SQL query with dynamic filters and pagination
    $sql = "SELECT
            Orders.order_id,
            Orders.orderType,
            Orders.smsNotify,
            Orders.deliveryFee,
            Orders.timeToPrepare,
            Orders.isReady,
            Orders.createdAt AS orderCreatedAt,
            Customers.fullname AS customerFullname,
            Customers.phonenumber AS customerPhonenumber,
            Customers.address AS customerAddress,
            Customers.houseNumber AS customerHouseNumber,
            Customers.floorNumber AS customerFloorNumber,
            Customers.apartmentNumber AS customerApartmentNumber,
            Customers.addressDescription AS customerAddressDescription,
            Items.item_id AS itemId,
            Items.name AS itemName,
            Items.price AS itemPrice,
            OrderDetails.quantity,
            OrderDetails.total_price AS orderDetailsTotalPrice,
            OrderDetails.size,
            OrderDetails.options,
            OrderDetails.description,
            OrderDetails.category_id
        FROM Orders
        JOIN Customers ON Orders.customer_id = Customers.customer_id
        JOIN OrderDetails ON Orders.order_id = OrderDetails.order_id
        JOIN Items ON OrderDetails.item_id = Items.item_id
        WHERE 1";

    // Apply filters
    if ($isReady !== null) {
        $sql .= " AND Orders.isReady = ?";
    }

    if ($startDate !== null) {
        $sql .= " AND Orders.createdAt >= ?";
    }

    if ($endDate !== null) {
        $sql .= " AND Orders.createdAt <= ?";
    }

    if ($orderType !== null) {
        $sql .= " AND Orders.orderType = ?";
    }

    // Apply pagination
    $sql .= " ORDER BY Orders.createdAt DESC LIMIT ?, ?";

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters based on their types
        $bindTypes = '';
        $bindValues = [];

        if ($isReady !== null) {
            $bindTypes .= 'i';
            $bindValues[] = $isReady;
        }

        if ($startDate !== null) {
            $bindTypes .= 's';
            $bindValues[] = $startDate;
        }

        if ($endDate !== null) {
            $bindTypes .= 's';
            $bindValues[] = $endDate;
        }

        $bindTypes .= 'ii';
        $bindValues[] = $offset;
        $bindValues[] = $pageSize;

        $stmt->bind_param($bindTypes, ...$bindValues);
        $stmt->execute();

        // Get result set
        $result = $stmt->get_result();

        // Fetch the results
        $data = $result->fetch_all(MYSQLI_ASSOC);

        // Format the response
        $formattedResponse = array_map(function ($order) {
            return [new OrderResponse($order)];
        }, $data);

        echo new Response(true, $formattedResponse, null, 'Request successful');
    } else {
        // Handle the case where the query fails
        http_response_code(500);
        die('Error preparing or executing the database query');
    }
} catch (Exception $e) {
    // Handle exceptions
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}

// Close the statement and the database connection
$stmt->close();
$conn->close();
