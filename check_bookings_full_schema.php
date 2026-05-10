<?php
include 'config/db.php';

try {
    $stmt = $conn->query("DESCRIBE bookings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== BOOKINGS TABLE COLUMNS ===\n";
    echo json_encode($columns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
