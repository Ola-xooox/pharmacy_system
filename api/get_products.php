<?php
header('Content-Type: application/json');
require '../db_connect.php';

// This query gets all individual products that are not expired and have stock.
$products_result = $conn->query("
    SELECT
        p.id,
        p.name,
        p.stock,
        p.item_total,
        c.name AS category_name,
        p.price,
        p.image_path
    FROM
        products p
    JOIN
        categories c ON p.category_id = c.id
    WHERE
        (p.expiration_date > CURDATE() OR p.expiration_date IS NULL) AND p.stock > 0
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