<?php
// Test file to check if API is working
echo "<h2>Testing Product API</h2>";

// Test 1: Check database connection
require 'db_connect.php';
if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error);
} else {
    echo "✅ Database connected successfully<br><br>";
}

// Test 2: Check products in database
echo "<h3>All Products in Database:</h3>";
$all_products = $conn->query("SELECT id, name, stock, expiration_date FROM products ORDER BY id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Stock</th><th>Expiration Date</th><th>Status</th></tr>";
while($row = $all_products->fetch_assoc()) {
    $expired = (strtotime($row['expiration_date']) <= strtotime(date('Y-m-d'))) ? '❌ EXPIRED/TODAY' : '✅ Valid';
    $stock_status = ($row['stock'] <= 0) ? '❌ No Stock' : '✅ ' . $row['stock'];
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$stock_status}</td>";
    echo "<td>{$row['expiration_date']}</td>";
    echo "<td>{$expired}</td>";
    echo "</tr>";
}
echo "</table><br><br>";

// Test 3: Test the actual API query
echo "<h3>Products Returned by API Query (Available Products):</h3>";
$products_result = $conn->query("
    SELECT
        p.name,
        SUM(p.stock) AS stock,
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
    HAVING SUM(p.stock) > 0
    ORDER BY
        p.name ASC
");

if ($products_result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Name</th><th>Category</th><th>Total Stock</th><th>Price</th></tr>";
    while($row = $products_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['category_name']}</td>";
        echo "<td>{$row['stock']}</td>";
        echo "<td>₱{$row['price']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ <strong>NO PRODUCTS FOUND!</strong> This is why your POS shows nothing.<br>";
    echo "Reasons:<br>";
    echo "- Products have expired or expire today<br>";
    echo "- Products have 0 stock<br>";
}

echo "<br><br>";

// Test 4: Check categories
echo "<h3>Categories:</h3>";
$cat_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
echo "<ul>";
while($row = $cat_result->fetch_assoc()) {
    echo "<li>{$row['name']}</li>";
}
echo "</ul>";

$conn->close();
?>
