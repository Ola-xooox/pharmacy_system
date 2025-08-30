<?php
header('Content-Type: application/json');
require '../db_connect.php';

// Check for a status filter in the request, default to 'available'
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'available';

// Determine the condition for the HAVING clause based on the status
if ($status_filter === 'outOfStock') {
    // This will group products where the total sum of items is zero or less
    $having_clause = "HAVING SUM(p.item_total) <= 0";
} else {
    // This is the default behavior, showing only products with available items
    $having_clause = "HAVING SUM(p.item_total) > 0";
}

// The main query is now dynamic based on the having clause
$products_result = $conn->query("
    SELECT
        p.name,
        SUM(p.stock) AS stock,
        SUM(p.item_total) AS item_total,
        c.name AS category_name,
        SUBSTRING_INDEX(GROUP_CONCAT(p.price ORDER BY p.expiration_date ASC), ',', 1) AS price,
        SUBSTRING_INDEX(GROUP_CONCAT(p.image_path ORDER BY p.expiration_date ASC), ',', 1) AS image_path,
        p.name as product_identifier
    FROM
        products p
    JOIN
        categories c ON p.category_id = c.id
    WHERE
        (p.expiration_date > CURDATE() OR p.expiration_date IS NULL)
    GROUP BY
        p.name, c.name
    {$having_clause} -- The dynamic part of the query
    ORDER BY
        p.name ASC
");

$products = [];
while($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);

$conn->close();
?>