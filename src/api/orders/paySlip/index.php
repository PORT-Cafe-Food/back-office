<?php

include '../../../core/db.php'; // Path to your database connection file

function getCustomer($conn, $customerId)
{
    // Query to retrieve the customer by customerId
    $customerQuery = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $customerQuery->bind_param('i', $customerId);
    $customerQuery->execute();
    $result = $customerQuery->get_result();

    return $result->fetch_assoc();
}
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

function getItem($conn, $itemId)
{
    // Query to retrieve the item by itemId
    $itemQuery = $conn->prepare("SELECT * FROM items WHERE id = ?");
    $itemQuery->bind_param('i', $itemId);
    $itemQuery->execute();
    $result = $itemQuery->get_result();

    return $result->fetch_assoc();
}

function getItemSize($conn, $sizeId)
{
    // Query to retrieve the item size by sizeId
    $sizeQuery = $conn->prepare("SELECT * FROM sizes WHERE id = ?");
    $sizeQuery->bind_param('i', $sizeId);
    $sizeQuery->execute();
    $result = $sizeQuery->get_result();

    return $result->fetch_assoc();
}

function getItemOptions($conn, $orderItemId)
{
    // Query to retrieve the item options by orderItemId
    $itemOptionsQuery = $conn->prepare("SELECT * FROM orderOptions WHERE orderItemId = ?");

    $itemOptionsQuery->bind_param('i', $orderItemId);
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

// Define a variable to store the total amount
$totalAmount = 0;

// Build the payslip text
$payslipText = '';

$customer = getCustomer($conn, $order['customerId']);

$payslipText .= "------------------------------------------------\n";
$payslipText .= "               Slip Pordužbine\n";
$payslipText .= "------------------------------------------------\n";
$payslipText .= "\n";
$payslipText .= "Broj porudžbine: " . $order['id'] . "\n";
$payslipText .= "Korisnik: " . $customer['fullname'] . "\n";
$payslipText .= "Telefon: " . $customer['phonenumber'] . "\n";
$payslipText .= "Email: " . $customer['email'] . "\n";
$payslipText .= "\n";
$payslipText .= "Adresa Dostave: \n";
$payslipText .= $customer['address'] . ", br. " . $customer['houseNumber'] . ", sp." . $customer['floorNumber'] . ", st." . $customer['aptNumber'] . "\n";
$payslipText .= "\n";
$payslipText .= "------------------------------------------------\n";
$payslipText .= "               Detalji porudžbine:\n";
$payslipText .= "------------------------------------------------\n";
$payslipText .= "\n";
$payslipText .= "Poručena u: " . date('F j, Y', strtotime($order['createdAt'])) . "\n";
$payslipText .= "Spremna u: " . date('F j, Y', strtotime($order['fullfilAt'])) . "\n";
$payslipText .= "Tip pordužbine: " . $order['type'] . "\n";
$payslipText .= "Broj stola: " . $order['tableNumber'] . "\n";
$payslipText .= "Napomena: " . $order['instructions'] . "\n";
$payslipText .= "\n";
$payslipText .= "------------------------------------------------\n";
$payslipText .= "               Stavke:\n";
$payslipText .= "------------------------------------------------\n";
$payslipText .= "\n";

// Iterate through the order items
foreach ($orderItems as $item) {
    // Get the item data
    $itemData = getItem($conn, $item['itemId']);
    $itemPrice = $itemData['price'];

    // Get the item size
    $itemSizeData = getItemSize($conn, $item['sizeId']);
    $itemSizePrice = $itemSizeData['price'];

    // Get the item options
    $itemOptions = getItemOptions($conn, $item['id']);
    $itemOptionsTotal = 0;

    foreach ($itemOptions as $option) {
        // Get the option data
        $optionData = getOption($conn, $option['optionId']);
        $itemOptionsTotal += $optionData['price'];
    }

    // Calculate the total price for this item
    $itemTotalPrice = ($itemPrice  + $itemSizePrice + $itemOptionsTotal) * $item['quantity'];

    $payslipText .= "" . $itemData['name'] . "\n";
    $payslipText .= "  - Veličina: " . $itemSizeData['name'] . " (+RSD " . number_format($itemSizePrice, 2) . ")\n";
    $payslipText .= "  - Količina: " . $item['quantity'] . "\n";
    foreach ($itemOptions as $option) {
        // Get the option data
        $optionData = getOption($conn, $option['optionId']);
        $payslipText .= "  - " . $optionData['name'] . " (+RSD " . number_format($optionData['price'], 2) . ")\n";
        $itemOptionsTotal += $optionData['price'];
    }
    $payslipText .= "  - Napomena: " . $item['instructions'] . "\n";
    $payslipText .= "  - Ukupno: RSD " . number_format($itemTotalPrice, 2) . "\n";
    $payslipText .= "\n";

    // Add the item total price to the order's total amount
    $totalAmount += $itemTotalPrice;
}

$payslipText .= "------------------------------------------------\n";
$payslipText .= "Ukupno: RSD " . number_format($totalAmount, 2) . "\n";
$payslipText .= "Troškovi Dostave: RSD " . number_format($order['deliveryFee'], 2) . "\n";
$payslipText .= "------------------------------------------------\n";
$payslipText .= "Za Platiti: RSD " . number_format($totalAmount + $order['deliveryFee'], 2) . "\n";
$payslipText .= "------------------------------------------------\n";


// Output the payslip text
echo $payslipText;

// Close the database connection
$conn->close();
