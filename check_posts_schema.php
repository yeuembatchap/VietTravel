<?php
require 'config/db.php';
echo "Schema bảng posts:\n";
try {
    $stmt = $conn->query('SHOW COLUMNS FROM posts');
    foreach ($stmt as $row) {
        echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>