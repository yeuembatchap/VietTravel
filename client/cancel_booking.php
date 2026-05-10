<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Kiểm tra xem có nhận được ID đơn hàng không
if (isset($_GET['id'])) {
    $bookingID = $_GET['id'];
    $userID = $_SESSION['user']['id'];

    // Truy vấn để kiểm tra xem đơn hàng này có đúng là của người dùng hiện tại không 
    // và chỉ cho phép hủy nếu trạng thái đang là 'pending' (chờ thanh toán)
    $stmt = $conn->prepare("SELECT PaymentStatus FROM bookings WHERE ID = ? AND UserID = ?");
    $stmt->execute([$bookingID, $userID]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        if ($booking['PaymentStatus'] == 'pending') {
            // Thực hiện cập nhật trạng thái thành 'cancelled'
            $updateStmt = $conn->prepare("UPDATE bookings SET PaymentStatus = 'cancelled' WHERE ID = ?");
            $updateStmt->execute([$bookingID]);
            
            $_SESSION['msg_success'] = "Đã hủy tour thành công!";
        } else {
            $_SESSION['msg_error'] = "Không thể hủy tour này do đã được xử lý hoặc thanh toán!";
        }
    } else {
        $_SESSION['msg_error'] = "Không tìm thấy thông tin đơn hàng!";
    }
}

// Chuyển hướng về lại trang Lịch sử đặt tour
header("Location: my_bookings.php");
exit();
?>