<?php
require 'config/db.php';
echo "Schema bảng bookings:\n";
$stmt = $conn->query('SHOW COLUMNS FROM bookings');
foreach ($stmt as $row) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . ($row['Null'] == 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}
?>