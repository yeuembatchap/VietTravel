<?php include 'admin_header.php'; ?>

<?php
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Lấy thông tin khách hàng
$userStmt = $conn->prepare("SELECT * FROM users WHERE ID = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('Không tìm thấy khách hàng này!'); window.location.href='manage_users.php';</script>";
    exit;
}

// 2. Lấy danh sách đơn hàng từ bảng 'bookings' chuẩn theo Database của bạn
$orderStmt = $conn->prepare("
    SELECT b.*, t.Name as TourName 
    FROM bookings b 
    LEFT JOIN tours t ON b.TourID = t.ID 
    WHERE b.UserID = ? 
    ORDER BY b.ID DESC
");
$orderStmt->execute([$userId]);
$bookings = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    .status-pending { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
    .status-paid { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .status-failed { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    
    .btn-back { display: inline-block; background: #607d8b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 15px; transition: 0.3s; }
    .btn-back:hover { background: #455a64; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e0f2f1; color: #00695c;">
        <i class="fas fa-history"></i> LỊCH SỬ ĐẶT TOUR CỦA: 
        <span style="color: #d32f2f; text-transform: uppercase;">
            <?= htmlspecialchars(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? '')) ?>
        </span>
    </div>
    <div class="box-body">
        
        <a href="manage_users.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Tên Tour Đã Đặt</th>
                    <th>Ngày Đặt</th>
                    <th>Số Lượng Khách</th>
                    <th>Thành Tiền</th>
                    <th>Thanh Toán</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($bookings) > 0): ?>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;"><?= htmlspecialchars($b['BookingCode'] ?? '#'.$b['ID']) ?></td>
                        
                        <td style="font-weight: bold; color: #0277bd; max-width: 250px;">
                            <?= htmlspecialchars($b['TourName'] ?? 'Tour đã bị xóa') ?>
                        </td>
                        
                        <td>
                            <?= date('d/m/Y H:i', strtotime($b['CreatedAt'])) ?>
                        </td>
                        
                        <td>
                            <?= $b['Slot'] ?> Người lớn
                            <?php if($b['SlotKid'] > 0) echo "<br>".$b['SlotKid']." Trẻ em"; ?>
                        </td>
                        
                        <td style="color: #c62828; font-weight: bold;">
                            <?= number_format($b['FinalPrice'], 0, ',', '.') ?>đ
                        </td>
                        
                        <td>
                            <?php 
                                $payStatus = $b['PaymentStatus'] ?? 'pending';
                                if($payStatus == 'paid') {
                                    echo '<span class="status-badge status-paid">Đã thanh toán ('.strtoupper($b['PaymentMethod']).')</span>';
                                } elseif($payStatus == 'failed') {
                                    echo '<span class="status-badge status-failed">Thất bại</span>';
                                } else {
                                    echo '<span class="status-badge status-pending">Chờ xử lý ('.strtoupper($b['PaymentMethod']).')</span>';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #888;">
                            <i class="fas fa-box-open" style="font-size: 40px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                            Khách hàng này chưa đặt tour nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
    </div>
</div>

</div></div></body></html>