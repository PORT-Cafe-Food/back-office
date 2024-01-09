<?php

include '../../../core/db.php'; // Path to your database connection file

// Function to get all orders with pagination and filtering
function getAllOrders($conn, $page = 1, $pageSize = 10, $statusFilter = null, $typeFilter = null, $kitchenDone = null)
{
    // Calculate the offset based on the page and pageSize
    $offset = ($page - 1) * $pageSize;

    // Build the SQL query with filters
    $sql = "SELECT * FROM orders WHERE 1=1";

    $bindParams = [];

    if ($statusFilter !== null) {
        $sql .= " AND status = ?";
        $bindParams[] = $statusFilter;
    }

    if ($typeFilter !== null) {
        $sql .= " AND type = ?";
        $bindParams[] = $typeFilter;
    }

    if ($kitchenDone !== null) {
        $sql .= " AND kitchenDone = 1";
    }

    // Add sorting by createdAt
    $sql .= " ORDER BY createdAt DESC";

    // Add LIMIT and OFFSET for pagination
    $sql .= " LIMIT ? OFFSET ?";
    $bindParams[] = $pageSize;
    $bindParams[] = $offset;

    $stmt = $conn->prepare($sql);

    // Bind parameters dynamically
    $paramTypes = str_repeat('s', count($bindParams));
    $stmt->bind_param($paramTypes, ...$bindParams);

    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate the total number of orders (unfiltered)
    $totalOrdersQuery = $conn->query("SELECT COUNT(*) AS totalOrders FROM orders");
    $totalOrders = $totalOrdersQuery->fetch_assoc()['totalOrders'];

    // Calculate the number of pages
    $totalPages = ceil($totalOrders / $pageSize);

    // Create an array to store the orders
    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    return [
        'currentPage' => $page,
        'pageSize' => $pageSize,
        'totalPages' => $totalPages,
        'orders' => $orders,
    ];
}

// Get page, pageSize, status filter, and type filter from query parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 10;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$typeFilter = isset($_GET['type']) ? $_GET['type'] : null;
$kitchenDone = isset($_GET['kitchenDone']) ? $_GET['kitchenDone'] : null;
// Get the list of orders based on the parameters
$ordersData = getAllOrders($conn, $page, $pageSize, $statusFilter, $typeFilter, $kitchenDone);

// Respond with JSON
header('Content-Type: application/json');
echo json_encode($ordersData, JSON_PRETTY_PRINT);

// Close the database connection
$conn->close();
