<?php
session_start();
require_once '../config/db.php';

// Kiểm tra nếu chưa đăng nhập thì đẩy về trang login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Lấy ID user từ session
$userID = $_SESSION['user']['id'];

// Truy vấn danh sách tour đã đặt
$sql = "SELECT b.*, t.Name as TourName, v.Name as VehicleName, i.Name as InsuranceName 
        FROM bookings b 
        JOIN tours t ON b.TourID = t.ID 
        LEFT JOIN vehicles v ON b.VehicleID = v.ID 
        LEFT JOIN insurance_packages i ON b.InsuranceID = i.ID
        WHERE b.UserID = :userid 
        ORDER BY b.CreatedAt DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([':userid' => $userID]);
$my_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đặt Tour - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 1200px; margin: auto;">
    <h2 style="color: #003366; margin-bottom: 20px;">LỊCH SỬ ĐẶT TOUR CỦA BẠN</h2>
    <?php if (isset($_SESSION['msg_success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
            <?= $_SESSION['msg_success'] ?>
        </div>
        <?php unset($_SESSION['msg_success']); // Xóa thông báo sau khi hiển thị ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg_error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; font-weight: bold;">
            <?= $_SESSION['msg_error'] ?>
        </div>
        <?php unset($_SESSION['msg_error']); ?>
    <?php endif; ?>

    <?php if (count($my_bookings) > 0): ?>
        <table style="width: 100%; border-collapse: collapse; box-shadow: 0 0 10px rgba(0,0,0,0.1); background: #fff;">
            <thead>
                <tr style="background-color: #003366; color: white; text-align: left;">
                    <th style="padding: 12px; border: 1px solid #ddd;">Mã Booking</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Tên Tour</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Xe di chuyển</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Bảo hiểm</th> <th style="padding: 12px; border: 1px solid #ddd;">Ngày đặt</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Tổng tiền</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Trạng thái / Thao tác</th> </tr>
            </thead>
            <tbody>
                <?php foreach ($my_bookings as $bk): ?>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold;"><?= htmlspecialchars($bk['BookingCode']) ?></td>
                        <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($bk['TourName']) ?></td>
                        
                        <td style="padding: 12px; border: 1px solid #ddd;">
                            <?php if(!empty($bk['VehicleName'])): ?>
                                <span style="color: #d35400; font-weight: bold;">🚗 <?= htmlspecialchars($bk['VehicleName']) ?></span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">Chưa đặt xe</span>
                                <br><a href="add_service.php?type=vehicle&id=<?= $bk['ID'] ?>" style="font-size: 12px; color: #003366; text-decoration: none; font-weight: bold;">+ Đặt xe ngay</a>
                            <?php endif; ?>
                        </td>
                        
                        <td style="padding: 12px; border: 1px solid #ddd;">
                            <?php if(!empty($bk['InsuranceName'])): ?>
                                <span style="color: #2e7d32; font-weight: bold;">🛡️ <?= htmlspecialchars($bk['InsuranceName']) ?></span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">Chưa mua</span>
                                <br><a href="add_service.php?type=insurance&id=<?= $bk['ID'] ?>" style="font-size: 12px; color: #e65100; text-decoration: none; font-weight: bold;">+ Mua bảo hiểm</a>
                            <?php endif; ?>
                        </td>

                        <td style="padding: 12px; border: 1px solid #ddd;"><?= date('d/m/Y H:i', strtotime($bk['CreatedAt'])) ?></td>
                        
                        <td style="padding: 12px; border: 1px solid #ddd; color: red; font-weight: bold;">
                            <?= number_format($bk['TotalPrice'], 0, ',', '.') ?>đ
                        </td>
                        
                        <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                            <?php if ($bk['PaymentStatus'] == 'pending'): ?>
                                <span style="color: #e65100; font-weight: bold; background: #fff3e0; padding: 5px 10px; border-radius: 15px; font-size: 13px;">🕒 Chờ thanh toán</span>
                                
                                <div style="margin-top: 10px; display: flex; flex-direction: column; gap: 8px; align-items: center;">
                                    <a href="pay_booking.php?id=<?= $bk['ID'] ?>" 
                                    style="display: inline-block; background: #003366; color: white; font-size: 13px; font-weight: bold; text-decoration: none; padding: 6px 12px; border-radius: 4px; transition: 0.3s; width: 100px; text-align: center;">
                                    💳 Thanh toán
                                    </a>
                                    
                                    <a href="cancel_booking.php?id=<?= $bk['ID'] ?>" 
                                    onclick="return confirm('Bạn có chắc chắn muốn hủy đặt tour này không? Việc này không thể hoàn tác.');" 
                                    style="display: inline-block; color: #c62828; font-size: 13px; font-weight: bold; text-decoration: none; border: 1px solid #c62828; padding: 5px 11px; border-radius: 4px; transition: 0.3s; width: 100px; text-align: center;">
                                    ✖ Hủy Tour
                                    </a>
                                </div>

                            <?php elseif ($bk['PaymentStatus'] == 'cancelled'): ?>
                                <span style="color: #c62828; font-weight: bold; background: #ffebee; padding: 5px 10px; border-radius: 15px; font-size: 13px;">🚫 Đã hủy</span>

                            <?php elseif ($bk['PaymentStatus'] == 'paid' || $bk['PaymentStatus'] == 'success'): ?>
                                <span style="color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 5px 10px; border-radius: 15px; font-size: 13px;">✔ Đã thanh toán</span>
                                
                            <?php else: ?>
                                <span style="background: #ffebee; color: #c62828; padding: 5px 10px; border-radius: 15px; font-size: 13px;">Thất bại</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="padding: 20px; background: #f9f9f9; border-left: 5px solid #ff9800;">
            Bạn chưa đặt tour nào. <a href="index.php" style="color: #003366; font-weight: bold;">Khám phá các tour ngay!</a>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>