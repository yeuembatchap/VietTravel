<?php
// config/db.php
$host = 'localhost';
$dbname = 'tourNoiDia';
$username = 'root'; // Mặc định của XAMPP
$password = '';     // Mặc định của XAMPP không có mật khẩu

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Thiết lập chế độ lỗi PDO thành ngoại lệ
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>