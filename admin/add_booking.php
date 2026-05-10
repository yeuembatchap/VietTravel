<?php include 'admin_header.php'; ?>

<?php
// Lấy danh sách Tour để đưa vào Select Box
$tours = [];
try {
    $stmt = $conn->query("SELECT ID, Name, Price, PriceKid FROM tours WHERE Status = 1 ORDER BY ID DESC");
    $tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { }

// XỬ LÝ LƯU ĐƠN HÀNG
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Đảm bảo bảng bookings có cột TimeStartStore
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM bookings LIKE 'TimeStartStore'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $conn->exec("ALTER TABLE bookings ADD COLUMN TimeStartStore DATE NULL");
        }
    } catch (Exception $e) {
        // Bỏ qua nếu không thể tạo cột
    }
    // 1. Tạo mã BookingCode tự động (VD: BKG-1689234)
    $bookingCode = 'BKG-' . strtoupper(substr(uniqid(), -6));
    
    // 2. Nhận dữ liệu từ form
    $customerName = $_POST['customer_name'];
    $customerPhone = $_POST['customer_phone'];
    $customerEmail = $_POST['customer_email'];
    $tourId = !empty($_POST['tour_id']) ? intval($_POST['tour_id']) : NULL;
    $timeStartStore = $_POST['time_start']; // Lưu ngày đi vào trường TimeStartStore
    
    $slot = intval($_POST['slot']);
    $slotKid = intval($_POST['slot_kid']);
    
    $totalPrice = intval($_POST['total_price']);
    $finalPrice = intval($_POST['final_price']); // Giá sau khi giảm (nếu có)
    
    $paymentMethod = $_POST['payment_method'];
    $paymentStatus = $_POST['payment_status'];
    $status = $_POST['status'];
    $note = $_POST['note'];

    try {
        // Chuẩn bị câu lệnh SQL - chỉ dùng các cột thực tế trong bookings table
        $sql = "INSERT INTO bookings 
                (BookingCode, TourID, CustomerName, CustomerPhone, TotalPrice, PaymentStatus, TimeStartStore) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $bookingCode, $tourId, $customerName, $customerPhone, 
            $totalPrice, $paymentStatus, $timeStartStore
        ]);
        
        if ($result) {
            echo "<script>alert('✅ Đã tạo đơn hàng thành công! Mã: $bookingCode'); window.location.href='manage_bookings.php';</script>";
        } else {
            echo "<script>alert('❌ Thực thi thất bại, không có lỗi cụ thể.');</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi CSDL: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<style>
    .form-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; font-size: 14px; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="email"], .form-group input[type="date"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px;}
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; font-size: 15px;}
    .row { display: flex; gap: 20px; flex-wrap: wrap; }
    .col { flex: 1; min-width: 250px; }
    .section-title { margin-bottom: 15px; color: #1565c0; font-size: 16px; border-bottom: 2px solid #e3f2fd; padding-bottom: 5px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0; text-align: left; padding-left: 20px;">
        <i class="fas fa-plus-circle"></i> TẠO ĐƠN ĐẶT TOUR (BOOKING)
    </div>
    <div class="box-body">
        <form action="" method="POST">
            
            <h3 class="section-title"><i class="fas fa-user"></i> Thông tin khách hàng</h3>
            <div class="row">
                <div class="form-group col">
                    <label>Họ và tên khách <span style="color:red;">*</span></label>
                    <input type="text" name="customer_name" required placeholder="Nhập tên khách...">
                </div>
                <div class="form-group col">
                    <label>Số điện thoại <span style="color:red;">*</span></label>
                    <input type="text" name="customer_phone" required placeholder="09xx...">
                </div>
                <div class="form-group col">
                    <label>Email liên hệ</label>
                    <input type="email" name="customer_email" placeholder="khach@email.com">
                </div>
            </div>

            <h3 class="section-title" style="margin-top: 10px;"><i class="fas fa-map-marked-alt"></i> Thông tin Dịch vụ & Thời gian</h3>
            <div class="row">
                <div class="form-group col" style="flex: 2;">
                    <label>Chọn Tour</label>
                    <select name="tour_id">
                        <option value="">-- Khách yêu cầu tour riêng / Khác --</option>
                        <?php foreach($tours as $t): ?>
                            <option value="<?= $t['ID'] ?>">
                                <?= htmlspecialchars($t['Name']) ?> (NL: <?= number_format($t['Price'], 0, ',', '.') ?>đ)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col">
                    <label>Ngày khởi hành <span style="color:red;">*</span></label>
                    <input type="date" name="time_start" required>
                </div>
            </div>

            <div class="row">
                <div class="form-group col">
                    <label>Số người lớn (Slot)</label>
                    <input type="number" name="slot" required value="1" min="1">
                </div>
                <div class="form-group col">
                    <label>Số trẻ em (SlotKid)</label>
                    <input type="number" name="slot_kid" required value="0" min="0">
                </div>
            </div>

            <h3 class="section-title" style="margin-top: 10px;"><i class="fas fa-money-bill-wave"></i> Thanh toán & Trạng thái</h3>
            <div class="row">
                <div class="form-group col">
                    <label>Tổng tiền (VNĐ) <span style="color:red;">*</span></label>
                    <input type="number" name="total_price" required placeholder="VD: 5000000">
                </div>
                <div class="form-group col">
                    <label>Tiền thu thực tế (Final Price) <span style="color:red;">*</span></label>
                    <input type="number" name="final_price" required placeholder="Thường bằng Tổng tiền">
                </div>
            </div>

            <div class="row">
                <div class="form-group col">
                    <label>Phương thức thanh toán</label>
                    <select name="payment_method">
                        <option value="cod">Tiền mặt / Thu hộ (COD)</option>
                        <option value="vnpay">Chuyển khoản / VNPay</option>
                    </select>
                </div>
                <div class="form-group col">
                    <label>Trạng thái thanh toán</label>
                    <select name="payment_status">
                        <option value="pending">⏳ Chưa thanh toán (Pending)</option>
                        <option value="paid">✅ Đã thanh toán (Paid)</option>
                        <option value="failed">❌ Thất bại (Failed)</option>
                    </select>
                </div>
                <div class="form-group col">
                    <label>Trạng thái Tour</label>
                    <select name="status">
                        <option value="Chờ xác nhận">Chờ xác nhận</option>
                        <option value="Đã xác nhận">Đã xác nhận</option>
                        <option value="Đã hoàn thành">Đã hoàn thành</option>
                        <option value="Đã hủy">Đã hủy</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Ghi chú (Note):</label>
                <textarea name="note" rows="3" placeholder="Yêu cầu đặc biệt: đón sân bay, dị ứng thức ăn..."></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Tạo Đơn Hàng</button>
                <a href="manage_bookings.php" class="btn-cancel">Hủy bỏ</a>
            </div>
        </form>
    </div>
</div>

</div></div></body></html>