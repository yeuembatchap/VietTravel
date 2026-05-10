<?php
session_start();
// Nhúng file kết nối Database
require_once '../config/db.php'; 

// Chỉ cần kiểm tra có gửi tin nhắn lên là được, KHÔNG ép buộc phải có $_SESSION['user'] nữa
if (isset($_POST['message'])) {
    
    // 1. Xác định người gửi (User đã đăng nhập hoặc Khách vãng lai)
    if (isset($_SESSION['user'])) {
        $sender_id = (string)$_SESSION['user']['id']; 
    } else {
        // Nếu chưa đăng nhập, tự tạo mã guest và lưu vào session để nhớ
        if (!isset($_SESSION['guest_id'])) {
            // Tạo mã dạng: guest_thoigian_số_ngẫu_nhiên
            $_SESSION['guest_id'] = 'guest_' . time() . '_' . rand(1000, 9999);
        }
        $sender_id = $_SESSION['guest_id'];
    }
    
    $message = trim($_POST['message']);
    
    if ($message === '') {
        exit;
    }

    try {
        // Gửi cho Admin (receiver_id mặc định là '0' - chú ý để trong nháy đơn vì nó đã thành chuỗi)
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                VALUES (:sender_id, '0', :message, NOW())";
                
        $stmt = $conn->prepare($sql);
        // QUAN TRỌNG: Đổi PARAM_INT thành PARAM_STR vì sender_id giờ có thể là chữ
        $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        
        $stmt->execute();
        echo "success";
    } catch (PDOException $e) {
        echo "Lỗi: " . $e->getMessage();
    }
}
?>