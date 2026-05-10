<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$bookingID = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : ''; 
$userID = $_SESSION['user']['id'];

// 1. Kiểm tra xem đơn hàng có tồn tại và đang ở trạng thái chờ thanh toán không
$stmt = $conn->prepare("SELECT * FROM bookings WHERE ID = ? AND UserID = ? AND PaymentStatus = 'pending'");
$stmt->execute([$bookingID, $userID]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['msg_error'] = "Đơn hàng không tồn tại hoặc đã thanh toán nên không thể thêm dịch vụ!";
    header("Location: my_bookings.php");
    exit();
}

// 2. XỬ LÝ KHI NGƯỜI DÙNG BẤM "CẬP NHẬT"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $serviceID = (int)$_POST['service_id'];
    
    if ($serviceID > 0) {
        if ($type == 'vehicle') {
            // Lấy giá xe và cập nhật
            $vStmt = $conn->prepare("SELECT PricePerDay FROM vehicles WHERE ID = ?");
            $vStmt->execute([$serviceID]);
            $veh = $vStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($veh) {
                $sql = "UPDATE bookings SET VehicleID = ?, TotalPrice = TotalPrice + ? WHERE ID = ?";
                $update = $conn->prepare($sql);
                $update->execute([$serviceID, $veh['PricePerDay'], $bookingID]);
            }
            
        } elseif ($type == 'insurance') {
            // Lấy giá bảo hiểm và cập nhật
            $iStmt = $conn->prepare("SELECT PricePerPerson FROM insurance_packages WHERE ID = ?");
            $iStmt->execute([$serviceID]);
            $ins = $iStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($ins) {
                $sql = "UPDATE bookings SET InsuranceID = ?, TotalPrice = TotalPrice + ? WHERE ID = ?";
                $update = $conn->prepare($sql);
                $update->execute([$serviceID, $ins['PricePerPerson'], $bookingID]);
            }
        }
        
        $_SESSION['msg_success'] = "Đã thêm dịch vụ thành công! Giá tour đã được cập nhật.";
    }
    
    header("Location: my_bookings.php");
    exit();
}

// 3. LẤY DANH SÁCH DỊCH VỤ ĐỂ HIỂN THỊ RA FORM
$items = [];
if ($type == 'vehicle') {
    $items = $conn->query("SELECT ID, Name, PricePerDay as Price FROM vehicles")->fetchAll(PDO::FETCH_ASSOC);
    $pageTitle = "Thêm Xe Di Chuyển";
} elseif ($type == 'insurance') {
    $items = $conn->query("SELECT ID, Name, PricePerPerson as Price FROM insurance_packages")->fetchAll(PDO::FETCH_ASSOC);
    $pageTitle = "Thêm Gói Bảo Hiểm";
} else {
    header("Location: my_bookings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?> - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-container { max-width: 500px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        select { width: 100%; padding: 10px; margin: 15px 0; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; }
        .btn-submit { background: #003366; color: white; border: none; padding: 12px 20px; width: 100%; font-size: 16px; border-radius: 4px; cursor: pointer; }
        .btn-submit:hover { background: #002244; }
    </style>
</head>
<body style="background: #f4f7f6;">

<?php include '../header.php'; ?>

<div class="form-container">
    <h2 style="color: #003366; text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px;"><?= $pageTitle ?></h2>
    
    <p>Đơn hàng: <strong><?= $booking['BookingCode'] ?></strong></p>
    
    <form action="" method="POST">
        <label>Chọn dịch vụ bạn muốn thêm:</label>
        <select name="service_id" required>
            <option value="">-- Vui lòng chọn --</option>
            <?php foreach ($items as $item): ?>
                <option value="<?= $item['ID'] ?>">
                    <?= htmlspecialchars($item['Name']) ?> - <?= number_format($item['Price'], 0, ',', '.') ?> VNĐ
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn-submit">Xác nhận thêm & Cập nhật giá</button>
        <div style="text-align: center; margin-top: 15px;">
            <a href="my_bookings.php" style="color: #d35400; text-decoration: none;">← Quay lại</a>
        </div>
    </form>
</div>

</body>
</html>