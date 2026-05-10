<?php
require 'config/db.php';

// Kiểm tra xem cột DepartureDate có tồn tại không
$stmt = $conn->prepare("SHOW COLUMNS FROM tours LIKE 'DepartureDate'");
$stmt->execute();
if ($stmt->rowCount() > 0) {
    // Kiểm tra xem có dữ liệu nào trong cột không
    $dataStmt = $conn->query("SELECT COUNT(*) as count FROM tours WHERE DepartureDate IS NOT NULL AND DepartureDate != ''");
    $count = $dataStmt->fetch()['count'];
    
    if ($count > 0) {
        echo "Cột DepartureDate có $count bản ghi dữ liệu. Không thể xóa tự động.\n";
        echo "Hãy sao lưu dữ liệu trước khi xóa thủ công.\n";
    } else {
        // Xóa cột nếu không có dữ liệu
        $conn->exec("ALTER TABLE tours DROP COLUMN DepartureDate");
        echo "Đã xóa cột DepartureDate thành công.\n";
    }
} else {
    echo "Cột DepartureDate không tồn tại hoặc đã bị xóa.\n";
}
?>