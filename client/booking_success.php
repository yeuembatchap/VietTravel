<?php
session_start();
require_once '../config/db.php';

// Kiểm tra xem có mã code truyền trên URL không
if (!isset($_GET['code'])) {
    header("Location: index.php");
    exit();
}

$bookingCode = $_GET['code'];
$method = isset($_GET['method']) ? $_GET['method'] : 'cash'; // Phương thức thanh toán

// Truy vấn thông tin chi tiết của mã booking này
$sql = "SELECT b.*, t.Name as TourName, v.Name as VehicleName, i.Name as InsuranceName 
        FROM bookings b 
        JOIN tours t ON b.TourID = t.ID 
        LEFT JOIN vehicles v ON b.VehicleID = v.ID 
        LEFT JOIN insurance_packages i ON b.InsuranceID = i.ID
        WHERE b.BookingCode = ?";
        
$stmt = $conn->prepare($sql);
$stmt->execute([$bookingCode]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("<div style='text-align:center; padding: 50px;'><h2>Không tìm thấy thông tin đơn hàng!</h2><a href='index.php'>Về trang chủ</a></div>");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Tour Thành Công - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .success-box { max-width: 600px; margin: 50px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; border-top: 5px solid #4CAF50; }
        .success-icon { color: #4CAF50; font-size: 60px; margin-bottom: 10px; line-height: 1; }
        .invoice-box { background: #f9fbe7; border: 1px dashed #8bc34a; padding: 25px; text-align: left; margin: 30px 0; border-radius: 8px; }
        .invoice-box p { margin: 10px 0; font-size: 16px; color: #444; }
        .btn-link { display: inline-block; padding: 12px 25px; border-radius: 4px; font-weight: bold; text-decoration: none; transition: 0.3s; margin: 5px; }
        .btn-blue { background: #003366; color: white; }
        .btn-blue:hover { background: #002244; }
        .btn-green { background: #8bc34a; color: white; }
        .btn-green:hover { background: #7cb342; }
    </style>
</head>
<body style="background-color: #f4f7f6; margin: 0; font-family: sans-serif;">

<?php include '../header.php'; ?>

<div class="success-box">
    <div class="success-icon">✔</div>
    <h1 style="color: #003366; margin-top: 10px;">ĐẶT TOUR THÀNH CÔNG!</h1>
    <p style="color: #555; font-size: 16px;">Cảm ơn bạn đã tin tưởng và sử dụng dịch vụ của hệ thống.</p>
    
    <div class="invoice-box">
        <h3 style="margin-top: 0; color: #d35400; border-bottom: 1px solid #c5e1a5; padding-bottom: 10px; text-align: center;">THÔNG TIN ĐƠN HÀNG</h3>
        
        <p><strong>Mã Booking:</strong> <span style="color: #003366; font-weight: bold; font-size: 1.2em;"><?= htmlspecialchars($booking['BookingCode']) ?></span></p>
        <p><strong>Tên Tour:</strong> <?= htmlspecialchars($booking['TourName']) ?></p>
        <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($booking['CreatedAt'])) ?></p>
        
        <?php if ($booking['VehicleName']): ?>
            <p><strong>Xe di chuyển:</strong> <?= htmlspecialchars($booking['VehicleName']) ?></p>
        <?php endif; ?>
        
        <?php if ($booking['InsuranceName']): ?>
            <p><strong>Gói Bảo hiểm:</strong> <?= htmlspecialchars($booking['InsuranceName']) ?></p>
        <?php endif; ?>
        
        <p><strong>Phương thức TT:</strong> 
            <span style="background: #e3f2fd; padding: 3px 10px; border-radius: 15px; color: #1565c0; font-weight: bold; font-size: 14px;">
                <?= $method == 'vnpay' ? 'VNPAY' : ($method == 'momo' ? 'Ví MOMO' : 'Thanh toán tại văn phòng') ?>
            </span>
        </p>
        
        <hr style="border: 0; border-top: 1px dashed #c5e1a5; margin: 20px 0;">
        <p style="font-size: 1.2em; text-align: right; margin-bottom: 0;"><strong>Tổng tiền thanh toán:</strong></p>
        <p style="color: red; font-weight: bold; font-size: 2em; text-align: right; margin-top: 5px;"><?= number_format($booking['TotalPrice'], 0, ',', '.') ?> VNĐ</p>
    </div>
    
    <a href="my_bookings.php" class="btn-link btn-blue">Xem lịch sử đặt tour</a>
    <a href="index.php" class="btn-link btn-green">Về trang chủ</a>
</div>

</body>
</html>