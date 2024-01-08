<?php

// Assuming $conn is your mysqli connection object
include '../../../core/db.php';

// Function to get categories, items, options, and sizes
function getCategoriesAndItems($conn)
{
    $result = ['categories' => []];

    try {
        // Fetch categories
        $categoriesQuery = $conn->query("SELECT * FROM categories");
        if ($categoriesQuery) {
            while ($category = $categoriesQuery->fetch_assoc()) {
                $categoryId = $category['id'];

                // Fetch items for each category
                $itemsQuery = $conn->prepare("SELECT * FROM items WHERE categoryId = ?");
                $itemsQuery->bind_param('i', $categoryId);

                if ($itemsQuery->execute()) {
                    $itemsResult = $itemsQuery->get_result();

                    if ($itemsResult) {
                        $items = $itemsResult->fetch_all(MYSQLI_ASSOC);

                        // Loop through items
                        $categoryItems = [];
                        foreach ($items as $item) {
                            $itemId = $item['id'];

                            // Fetch options for each item
                            $optionsQuery = $conn->prepare("SELECT * FROM options WHERE itemId = ?");
                            $optionsQuery->bind_param('i', $itemId);

                            if ($optionsQuery->execute()) {
                                $optionsResult = $optionsQuery->get_result();

                                if ($optionsResult) {
                                    $options = $optionsResult->fetch_all(MYSQLI_ASSOC);

                                    // Convert prices to float
                                    foreach ($options as &$option) {
                                        $option['price'] = floatval($option['price']);
                                    }
                                    unset($option); // Unset the reference to the last item

                                    // Fetch sizes for each item
                                    $sizesQuery = $conn->prepare("SELECT * FROM sizes WHERE itemId = ?");
                                    $sizesQuery->bind_param('i', $itemId);

                                    if ($sizesQuery->execute()) {
                                        $sizesResult = $sizesQuery->get_result();

                                        if ($sizesResult) {
                                            $sizes = $sizesResult->fetch_all(MYSQLI_ASSOC);

                                            // Build item structure with options and sizes
                                            $itemStructure = [
                                                'id' => $item['id'],
                                                'name' => $item['name'],
                                                'price' => floatval($item['price']),
                                                'discount' => intval($item['discount']),
                                                'availableDineIn' => boolval($item['availableDineIn']),
                                                'availableDelivery' => boolval($item['availableDelivery']),
                                                'availableTakeAway' => boolval($item['availableTakeAway']),
                                                'description' => $item['description'],
                                                'options' => $options,
                                                'sizes' => $sizes
                                            ];

                                            $categoryItems[] = $itemStructure;
                                        } else {
                                            throw new Exception($sizesQuery->error);
                                        }
                                    } else {
                                        throw new Exception($sizesQuery->error);
                                    }
                                } else {
                                    throw new Exception($optionsQuery->error);
                                }
                            } else {
                                throw new Exception($optionsQuery->error);
                            }
                        }

                        // Build category structure
                        $categoryStructure = [
                            'id' => intval($categoryId),
                            'name' => $category['name'],
                            'items' => $categoryItems
                        ];

                        $result['categories'][] = $categoryStructure;
                    } else {
                        throw new Exception($itemsQuery->error);
                    }
                } else {
                    throw new Exception($itemsQuery->error);
                }

                $itemsQuery->close();  // Close the prepared statement
            }
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        // Handle the exception (e.g., log, return an error response, etc.)
        $result['error'] = $e->getMessage();
    }

    return $result;
}

// Get categories and items
$data = getCategoriesAndItems($conn);

// Respond with JSON
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
