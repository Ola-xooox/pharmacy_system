<?php
require 'db_connect.php';

if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['image_path'] && file_exists($row['image_path'])) {
            $imagePath = $row['image_path'];
            $imageInfo = getimagesize($imagePath);
            $mimeType = $imageInfo['mime'];
            
            header("Content-Type: " . $mimeType);
            header("Content-Length: " . filesize($imagePath));
            readfile($imagePath);
        } else {
            header("HTTP/1.0 404 Not Found");
        }
    } else {
        header("HTTP/1.0 404 Not Found");
    }
    
    $stmt->close();
} else {
    header("HTTP/1.0 400 Bad Request");
}

$conn->close();
?>
