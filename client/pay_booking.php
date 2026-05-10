<?php
session_start();
require_once '../config/db.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// 2. XỬ LÝ KHI KHÁCH BẤM NÚT "XÁC NHẬN ĐÃ CHUYỂN KHOẢN" (Phương thức POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $bookingID = $_POST['booking_id'];
    $userID = $_SESSION['user']['id'];

    $stmt = $conn->prepare("SELECT PaymentStatus, BookingCode FROM bookings WHERE ID = ? AND UserID = ?");
    $stmt->execute([$bookingID, $userID]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking && $booking['PaymentStatus'] == 'pending') {
        // Cập nhật trạng thái thành 'paid' (Trong thực tế có thể là 'chờ admin duyệt')
        $updateStmt = $conn->prepare("UPDATE bookings SET PaymentStatus = 'paid' WHERE ID = ?");
        $updateStmt->execute([$bookingID]);
        
        $_SESSION['msg_success'] = "Tuyệt vời! Hệ thống đã ghi nhận thanh toán cho mã đơn: " . $booking['BookingCode'];
    }
    
    header("Location: my_bookings.php");
    exit();
}

// 3. HIỂN THỊ TRANG QUÉT MÃ QR (Phương thức GET)
if (!isset($_GET['id'])) {
    header("Location: my_bookings.php");
    exit();
}

$bookingID = $_GET['id'];
$userID = $_SESSION['user']['id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT b.*, t.Name as TourName FROM bookings b JOIN tours t ON b.TourID = t.ID WHERE b.ID = ? AND b.UserID = ?");
$stmt->execute([$bookingID, $userID]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy hoặc không phải trạng thái chờ thanh toán thì đuổi về
if (!$booking || $booking['PaymentStatus'] != 'pending') {
    $_SESSION['msg_error'] = "Đơn hàng không tồn tại hoặc đã được xử lý!";
    header("Location: my_bookings.php");
    exit();
}

// ==========================================
// THÔNG TIN TÀI KHOẢN NGÂN HÀNG CỦA BẠN (CÔNG TY)
// ==========================================
$bankID = "VCB"; // Mã ngân hàng (VD: MB, VCB, TCB...)
$accountNo = "1027969285"; // Số tài khoản của bạn
$accountName = "LE TIEN DAT"; // Tên chủ tài khoản
$amount = $booking['TotalPrice']; // Số tiền cần thanh toán
$description = $booking['BookingCode']; // Nội dung: Mã Booking

// Tạo URL gọi API VietQR
$qrUrl = "https://img.vietqr.io/image/{$bankID}-{$accountNo}-compact2.png?amount={$amount}&addInfo={$description}&accountName=" . urlencode($accountName);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Mã QR - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #f4f7f6; font-family: sans-serif; }
        .payment-container { max-width: 800px; margin: 50px auto; display: flex; background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .info-col { flex: 1; padding: 30px; background: #fafafa; border-right: 1px solid #eee; }
        .qr-col { flex: 1; padding: 30px; text-align: center; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .qr-image { width: 100%; max-width: 300px; border-radius: 8px; border: 1px solid #ddd; padding: 10px; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .info-row { margin-bottom: 15px; font-size: 15px; }
        .info-row strong { color: #333; display: inline-block; width: 130px; }
        .total-price { color: #d35400; font-size: 24px; font-weight: bold; margin-top: 10px; }
        .btn-confirm { background: #003366; color: white; border: none; padding: 12px 20px; font-size: 16px; font-weight: bold; border-radius: 5px; cursor: pointer; width: 100%; margin-top: 20px; transition: 0.3s; }
        .btn-confirm:hover { background: #002244; }
        .btn-back { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
        .btn-back:hover { color: #d35400; text-decoration: underline; }
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="payment-container">
    <div class="info-col">
        <h2 style="color: #003366; margin-top: 0; border-bottom: 2px solid #003366; padding-bottom: 10px;">Chi Tiết Thanh Toán</h2>
        
        <div class="info-row">
            <strong>Mã Booking:</strong> 
            <span style="background: #e3f2fd; color: #1565c0; padding: 3px 8px; border-radius: 4px; font-weight: bold;"><?= htmlspecialchars($booking['BookingCode']) ?></span>
        </div>
        <div class="info-row">
            <strong>Tên Tour:</strong> <br><span style="color: #555;"><?= htmlspecialchars($booking['TourName']) ?></span>
        </div>
        <div class="info-row">
            <strong>Ngân hàng:</strong> <?= $bankID ?>
        </div>
        <div class="info-row">
            <strong>Chủ tài khoản:</strong> <?= $accountName ?>
        </div>
        <div class="info-row">
            <strong>Số tài khoản:</strong> <span style="font-weight: bold; color: #000; font-size: 18px;"><?= $accountNo ?></span>
        </div>
        <div class="info-row">
            <strong>Nội dung CK:</strong> <span style="color: red; font-weight: bold;"><?= htmlspecialchars($booking['BookingCode']) ?></span>
        </div>
        
        <div style="margin-top: 30px; padding-top: 15px; border-top: 1px dashed #ccc;">
            <strong>Số tiền cần thanh toán:</strong>
            <div class="total-price"><?= number_format($booking['TotalPrice'], 0, ',', '.') ?> VNĐ</div>
        </div>
    </div>

    <div class="qr-col">
        <h3 style="color: #333; margin-top: 0;">Quét mã QR để chuyển khoản</h3>
        <p style="color: #777; font-size: 13px; margin-bottom: 20px;">Mở App ngân hàng và quét mã dưới đây. Nội dung và số tiền đã được tự động điền.</p>
        
        <img src="<?= $qrUrl ?>" alt="Mã QR Thanh Toán" class="qr-image">

        <form action="pay_booking.php" method="POST" style="width: 100%;">
            <input type="hidden" name="booking_id" value="<?= $booking['ID'] ?>">
            <button type="submit" name="confirm_payment" class="btn-confirm" onclick="return confirm('Bạn chắc chắn đã chuyển khoản thành công chứ?');">
                Tôi Đã Chuyển Khoản
            </button>
        </form>
        
        <a href="my_bookings.php" class="btn-back">Quay lại lịch sử đặt tour</a>
    </div>
</div>

</body>
</html>