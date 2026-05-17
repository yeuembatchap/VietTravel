<?php
session_start();
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['vehicle_id'])) {
    header("Location: vehicles.php");
    exit();
}

$vehicleID = $_GET['vehicle_id'];
$userID = $_SESSION['user']['id'];
$msg = '';

// Lấy thông tin chiếc xe khách vừa chọn, kèm tên thành phố
$stmtXe = $conn->prepare("SELECT v.*, c.Name as CityName FROM vehicles v LEFT JOIN cities c ON v.CityID = c.ID WHERE v.ID = ?");
$stmtXe->execute([$vehicleID]);
$xe = $stmtXe->fetch(PDO::FETCH_ASSOC);

if (!$xe) {
    die("Không tìm thấy xe!");
}

$vehicleCityName = !empty($xe['CityName']) ? $xe['CityName'] : 'Đang cập nhật';

// Lấy danh sách các Tour khách ĐÃ ĐẶT trong cùng thành phố với xe để khách chọn gắn xe vào
$stmtTour = $conn->prepare("SELECT b.ID as BookingID, t.Name as TourName, b.BookingCode 
                            FROM bookings b 
                            JOIN tours t ON b.TourID = t.ID 
                            WHERE b.UserID = ? AND t.CityID = ?");
$stmtTour->execute([$userID, $xe['CityID'] ?? 0]);
$myTours = $stmtTour->fetchAll(PDO::FETCH_ASSOC);

// XỬ LÝ KHI BẤM NÚT XÁC NHẬN ĐẶT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $bookingID = $_POST['booking_id'];
    
    // Cập nhật VehicleID vào bảng bookings cho Tour được chọn
    $updateSql = "UPDATE bookings SET VehicleID = ? WHERE ID = ? AND UserID = ?";
    $stmtUpdate = $conn->prepare($updateSql);
    
    if($stmtUpdate->execute([$vehicleID, $bookingID, $userID])) {
        // Có thể cộng thêm tiền xe vào TotalPrice ở đây nếu muốn
        $msg = "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>🎉 Đặt xe thành công! Xe đã được gắn vào tour của bạn.</div>";
    } else {
        $msg = "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;'>Có lỗi xảy ra, vui lòng thử lại!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác Nhận Đặt Xe - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php $disableHeaderBanner = true; include '../header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 800px; margin: auto;">
    <h2 style="text-align: center; color: #003366;">XÁC NHẬN ĐẶT XE</h2>
    
    <?= $msg ?>

    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); display: flex; gap: 20px;">
        <div style="flex: 1;">
            <?php
                $vehicleImage = $xe['Image'];
                if (empty($vehicleImage)) {
                    $vehicleImgSrc = 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=600&q=80';
                } elseif (strpos($vehicleImage, 'http') === 0) {
                    $vehicleImgSrc = $vehicleImage;
                } else {
                    $vehicleImgSrc = '../uploads/' . $vehicleImage;
                }
            ?>
            <img src="<?= htmlspecialchars($vehicleImgSrc) ?>" style="width: 100%; border-radius: 8px; object-fit: cover;">
        </div>
        <div style="flex: 1;">
            <h3 style="color: #d35400; margin-top: 0;"><?= htmlspecialchars($xe['Name']) ?></h3>
            <p><strong>Thành phố:</strong> <?= htmlspecialchars($vehicleCityName) ?></p>
            <p><strong>Số chỗ:</strong> <?= $xe['Seats'] ?> chỗ</p>
            <p><strong>Loại:</strong> <?= $xe['HasDriver'] ? 'Có tài xế' : 'Tự lái' ?></p>
            <p><strong>Giá:</strong> <span style="color: red; font-weight: bold;"><?= number_format($xe['PricePerDay'], 0, ',', '.') ?> VNĐ/ngày</span></p>
            
            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
            
            <?php if (count($myTours) > 0): ?>
            <form method="POST" action="">
                <label style="display: block; font-weight: bold; margin-bottom: 10px;">Chọn Tour đã đặt tại <?= htmlspecialchars($vehicleCityName) ?> để dùng xe này:</label>
                <select name="booking_id" required style="width: 100%; padding: 10px; margin-bottom: 20px; border-radius: 4px; border: 1px solid #ccc;">
                    <option value="">-- Chọn Tour đã đặt --</option>
                    <?php foreach($myTours as $t): ?>
                        <option value="<?= $t['BookingID'] ?>"><?= $t['BookingCode'] ?> - <?= htmlspecialchars($t['TourName']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" name="confirm_booking" style="width: 100%; padding: 12px; background: #003366; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold;">XÁC NHẬN GẮN VÀO TOUR</button>
            </form>
            <?php else: ?>
                <div style="background: #fff3cd; color: #856404; padding: 20px; border: 1px solid #ffeeba; border-radius: 8px; margin-top: 10px;">
                    Hiện không có tour đã đặt cùng thành phố <strong><?= htmlspecialchars($vehicleCityName) ?></strong> để gắn xe này. Vui lòng chọn tour phù hợp hoặc quay lại <a href="vehicles.php" style="color: #856404; text-decoration: underline;">danh sách xe</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="text-align: center; margin-top: 20px;">
        <a href="my_bookings.php" style="color: #8bc34a; font-weight: bold; text-decoration: none;">&larr; Xem lịch sử tour của bạn</a>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>