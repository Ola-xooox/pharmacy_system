<?php
// Run payment fields migration
require_once '../db_connect.php';

try {
    // Read the SQL file
    $sql = file_get_contents('add_payment_fields.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read SQL file");
    }
    
    // Execute the SQL
    if ($conn->multi_query($sql)) {
        echo "Payment fields migration completed successfully!\n";
        
        // Process all results
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->next_result());
        
    } else {
        throw new Exception("Error executing migration: " . $conn->error);
    }
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}

$conn->close();
?>
