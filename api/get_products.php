<?php
header('Content-Type: application/json');
require '../db_connect.php';

// Check for a status filter in the request, default to 'available'
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'available';

// Determine the condition for the HAVING clause based on the status
if ($status_filter === 'outOfStock') {
    // This will group products where the total sum of stock is zero or less
    $having_clause = "HAVING SUM(p.stock) <= 0";
} else {
    // This is the default behavior, showing only products with available stock
    $having_clause = "HAVING SUM(p.stock) > 0";
}

// The main query is now dynamic based on the having clause, including expiration data
$products_result = $conn->query("
    SELECT
        p.name,
        SUM(p.stock) AS stock,
        c.name AS category_name,
        SUBSTRING_INDEX(GROUP_CONCAT(p.price ORDER BY p.expiration_date ASC), ',', 1) AS price,
        SUBSTRING_INDEX(GROUP_CONCAT(p.image_path ORDER BY p.expiration_date ASC), ',', 1) AS image_path,
        -- Get the earliest expiration date for this product group
        MIN(CASE WHEN p.expiration_date IS NOT NULL THEN p.expiration_date END) AS earliest_expiration,
        -- Count expired lots (stock > 0 and expired)
        SUM(CASE WHEN p.expiration_date <= CURDATE() AND p.expiration_date IS NOT NULL AND p.stock > 0 THEN p.stock ELSE 0 END) AS expired_stock,
        -- Count expiring soon lots (within 60 days, stock > 0)
        SUM(CASE WHEN p.expiration_date > CURDATE() AND p.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY) AND p.stock > 0 THEN p.stock ELSE 0 END) AS expiring_soon_stock,
        p.name as product_identifier
    FROM
        products p
    JOIN
        categories c ON p.category_id = c.id
    -- Removed WHERE clause to include expired products for proper expiration tracking
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