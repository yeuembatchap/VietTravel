<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['insurance_id'])) {
    header("Location: insurance.php");
    exit();
}

$insuranceID = $_GET['insurance_id'];
$userID = $_SESSION['user']['id'];
$msg = '';

// Lấy thông tin gói bảo hiểm khách vừa chọn
$stmtIns = $conn->prepare("SELECT * FROM insurance_packages WHERE ID = ?");
$stmtIns->execute([$insuranceID]);
$bh = $stmtIns->fetch(PDO::FETCH_ASSOC);

if (!$bh) {
    die("Không tìm thấy gói bảo hiểm!");
}

// Lấy danh sách Tour đã đặt của khách hàng
$stmtTour = $conn->prepare("SELECT b.ID as BookingID, t.Name as TourName, b.BookingCode 
                            FROM bookings b 
                            JOIN tours t ON b.TourID = t.ID 
                            WHERE b.UserID = ?");
$stmtTour->execute([$userID]);
$myTours = $stmtTour->fetchAll(PDO::FETCH_ASSOC);

// XỬ LÝ KHI BẤM XÁC NHẬN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_insurance'])) {
    $bookingID = $_POST['booking_id'];
    
    // Cập nhật InsuranceID vào bảng bookings
    $updateSql = "UPDATE bookings SET InsuranceID = ? WHERE ID = ? AND UserID = ?";
    $stmtUpdate = $conn->prepare($updateSql);
    
    if($stmtUpdate->execute([$insuranceID, $bookingID, $userID])) {
        $msg = "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>🎉 Mua bảo hiểm thành công! Gói bảo hiểm đã được áp dụng cho tour của bạn.</div>";
    } else {
        $msg = "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>Có lỗi xảy ra, vui lòng thử lại!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác Nhận Mua Bảo Hiểm - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 800px; margin: auto;">
    <h2 style="text-align: center; color: #003366;">XÁC NHẬN MUA BẢO HIỂM DU LỊCH</h2>
    
    <?= $msg ?>

    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-top: 4px solid #8bc34a;">
        <h3 style="color: #33691e; margin-top: 0; font-size: 1.5rem;"><?= htmlspecialchars($bh['Name']) ?></h3>
        <p style="color: #555; font-size: 1.1rem;"><?= htmlspecialchars($bh['Description']) ?></p>
        <p><strong>Phí bảo hiểm:</strong> <span style="color: red; font-weight: bold; font-size: 1.3rem;"><?= number_format($bh['PricePerPerson'], 0, ',', '.') ?> VNĐ</span> /người</p>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin: 25px 0;">
        
        <form method="POST" action="">
            <label style="display: block; font-weight: bold; margin-bottom: 10px;">Chọn Tour cần áp dụng bảo hiểm này:</label>
            <select name="booking_id" required style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #ccc; font-size: 16px;">
                <option value="">-- Chọn Tour đã đặt --</option>
                <?php foreach($myTours as $t): ?>
                    <option value="<?= $t['BookingID'] ?>"><?= $t['BookingCode'] ?> - <?= htmlspecialchars($t['TourName']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" name="confirm_insurance" style="width: 100%; padding: 14px; background: #e65100; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">XÁC NHẬN MUA BẢO HIỂM</button>
        </form>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="my_bookings.php" style="color: #8bc34a; font-weight: bold; text-decoration: none;">&larr; Xem lịch sử tour của bạn</a>
    </div>
</div>

</body>
</html>