<?php include 'admin_header.php'; ?>

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<script>alert('ID không hợp lệ!'); window.location.href='manage_bookings.php';</script>";
    exit;
}

// === XỬ LÝ KHI SUBMIT FORM ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName  = trim($_POST['CustomerName']);
    $customerEmail = trim($_POST['CustomerEmail']);
    $customerPhone = trim($_POST['CustomerPhone']);
    $slot          = intval($_POST['Slot']);
    $slotKid       = intval($_POST['SlotKid']);
    $totalPrice    = intval($_POST['TotalPrice']);
    $finalPrice    = intval($_POST['FinalPrice']);
    $paymentMethod = $_POST['PaymentMethod'];
    $paymentStatus = $_POST['PaymentStatus'];
    $status        = trim($_POST['Status']);
    $timeStart     = trim($_POST['TimeStartStore']);
    $voucherCode   = trim($_POST['VoucherCode']);
    $discountAmt   = intval($_POST['DiscountAmount']);

    $sql = "UPDATE bookings SET
                CustomerName    = :customerName,
                CustomerEmail   = :customerEmail,
                CustomerPhone   = :customerPhone,
                Slot            = :slot,
                SlotKid         = :slotKid,
                TotalPrice      = :totalPrice,
                FinalPrice      = :finalPrice,
                PaymentMethod   = :paymentMethod,
                PaymentStatus   = :paymentStatus,
                Status          = :status,
                TimeStartStore  = :timeStart,
                VoucherCode     = :voucherCode,
                DiscountAmount  = :discountAmt
            WHERE ID = :id";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':customerName'  => $customerName,
            ':customerEmail' => $customerEmail,
            ':customerPhone' => $customerPhone,
            ':slot'          => $slot,
            ':slotKid'       => $slotKid,
            ':totalPrice'    => $totalPrice,
            ':finalPrice'    => $finalPrice,
            ':paymentMethod' => $paymentMethod,
            ':paymentStatus' => $paymentStatus,
            ':status'        => $status,
            ':timeStart'     => $timeStart ?: null,
            ':voucherCode'   => $voucherCode ?: null,
            ':discountAmt'   => $discountAmt,
            ':id'            => $id,
        ]);
        echo "<script>alert('✅ Cập nhật đơn hàng thành công!'); window.location.href='manage_bookings.php';</script>";
        exit;
    } catch (PDOException $e) {
        $errorMsg = "Lỗi cập nhật: " . $e->getMessage();
    }
}

// === LẤY THÔNG TIN BOOKING ===
$stmt = $conn->prepare("
    SELECT b.*, 
           t.Name AS TourName,
           CONCAT(u.FirstName, ' ', u.LastName) AS UserFullName,
           u.Email AS UserEmail,
           u.Phone AS UserPhone
    FROM bookings b
    LEFT JOIN tours t ON b.TourID = t.ID
    LEFT JOIN users u ON b.UserID = u.ID
    WHERE b.ID = :id
");
$stmt->execute([':id' => $id]);
$b = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$b) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='manage_bookings.php';</script>";
    exit;
}
?>

<style>
    .edit-booking-wrap {
        max-width: 900px;
        margin: 0 auto;
    }

    .form-section {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        margin-bottom: 24px;
        overflow: hidden;
    }

    .form-section-title {
        background: #e3f2fd;
        color: #1565c0;
        font-weight: bold;
        font-size: 14px;
        padding: 12px 20px;
        border-bottom: 1px solid #bbdefb;
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
    }

    .form-section-body {
        padding: 20px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-row.three-col {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-group label {
        font-size: 13px;
        font-weight: 600;
        color: #555;
    }

    .form-group .required {
        color: #e53935;
    }

    .form-group input,
    .form-group select {
        padding: 10px 13px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s;
        background: #fff;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #1565c0;
        box-shadow: 0 0 0 3px rgba(21,101,192,0.1);
    }

    .form-group input[readonly] {
        background: #f5f5f5;
        color: #888;
        cursor: not-allowed;
    }

    .info-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
    }
    .info-badge.paid    { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .info-badge.pending { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2; }
    .info-badge.failed  { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

    .action-bar {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 10px;
    }

    .btn-save {
        background: #1565c0;
        color: #fff;
        padding: 12px 30px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-save:hover { background: #0d47a1; }

    .btn-back {
        background: #757575;
        color: #fff;
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 15px;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
    }
    .btn-back:hover { background: #616161; }

    .alert-error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ffcdd2;
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
        font-size: 14px;
    }

    .readonly-info {
        background: #f5f5f5;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 10px 13px;
        font-size: 14px;
        color: #444;
    }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0;">
        <i class="fas fa-edit"></i> CHỈNH SỬA ĐƠN ĐẶT TOUR
        <span style="font-size: 13px; font-weight: normal; margin-left: 10px;">
            — Mã booking: <strong><?= htmlspecialchars($b['BookingCode'] ?? 'N/A') ?></strong>
        </span>
    </div>
    <div class="box-body">
        <div class="edit-booking-wrap">

            <?php if (!empty($errorMsg)): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">

                <!-- PHẦN 1: THÔNG TIN ĐƠN HÀNG (READONLY) -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-info-circle"></i> Thông tin đơn hàng
                    </div>
                    <div class="form-section-body">
                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Mã Booking</label>
                                <div class="readonly-info" style="font-weight: bold; color: #003366;">
                                    <?= htmlspecialchars($b['BookingCode'] ?? 'N/A') ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Tour</label>
                                <div class="readonly-info"><?= htmlspecialchars($b['TourName'] ?? 'N/A') ?></div>
                            </div>
                            <div class="form-group">
                                <label>Ngày tạo đơn</label>
                                <div class="readonly-info">
                                    <?= !empty($b['CreatedAt']) ? date('d/m/Y H:i', strtotime($b['CreatedAt'])) : 'N/A' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PHẦN 2: THÔNG TIN KHÁCH HÀNG -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user"></i> Thông tin khách hàng
                    </div>
                    <div class="form-section-body">
                        <div class="form-row" style="margin-bottom: 16px;">
                            <div class="form-group">
                                <label>Họ tên khách <span class="required">*</span></label>
                                <input type="text" name="CustomerName" value="<?= htmlspecialchars($b['CustomerName'] ?? $b['UserFullName'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="CustomerEmail" value="<?= htmlspecialchars($b['CustomerEmail'] ?? $b['UserEmail'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="CustomerPhone" value="<?= htmlspecialchars($b['CustomerPhone'] ?? $b['UserPhone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Ngày khởi hành</label>
                                <input type="date" name="TimeStartStore" value="<?= htmlspecialchars($b['TimeStartStore'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PHẦN 3: SỐ LƯỢNG & GIÁ TIỀN -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-calculator"></i> Số lượng & Giá tiền
                    </div>
                    <div class="form-section-body">
                        <div class="form-row three-col" style="margin-bottom: 16px;">
                            <div class="form-group">
                                <label>Số người lớn <span class="required">*</span></label>
                                <input type="number" name="Slot" min="1" value="<?= intval($b['Slot'] ?? 1) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Số trẻ em</label>
                                <input type="number" name="SlotKid" min="0" value="<?= intval($b['SlotKid'] ?? 0) ?>">
                            </div>
                            <div class="form-group">
                                <label>Mã Voucher</label>
                                <input type="text" name="VoucherCode" value="<?= htmlspecialchars($b['VoucherCode'] ?? '') ?>" placeholder="Để trống nếu không có">
                            </div>
                        </div>
                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Tổng tiền (VNĐ) <span class="required">*</span></label>
                                <input type="number" name="TotalPrice" min="0" value="<?= intval($b['TotalPrice'] ?? 0) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Giảm giá (VNĐ)</label>
                                <input type="number" name="DiscountAmount" min="0" value="<?= intval($b['DiscountAmount'] ?? 0) ?>">
                            </div>
                            <div class="form-group">
                                <label>Thành tiền (VNĐ) <span class="required">*</span></label>
                                <input type="number" name="FinalPrice" min="0" value="<?= intval($b['FinalPrice'] ?? 0) ?>" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PHẦN 4: TRẠNG THÁI THANH TOÁN -->
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-credit-card"></i> Thanh toán & Trạng thái
                    </div>
                    <div class="form-section-body">
                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Phương thức thanh toán</label>
                                <select name="PaymentMethod">
                                    <option value="cod"   <?= ($b['PaymentMethod'] ?? '') == 'cod'   ? 'selected' : '' ?>>COD (Tiền mặt)</option>
                                    <option value="vnpay" <?= ($b['PaymentMethod'] ?? '') == 'vnpay' ? 'selected' : '' ?>>VNPay</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái thanh toán</label>
                                <select name="PaymentStatus">
                                    <option value="pending" <?= ($b['PaymentStatus'] ?? '') == 'pending' ? 'selected' : '' ?>>⏳ Chờ xử lý</option>
                                    <option value="paid"    <?= ($b['PaymentStatus'] ?? '') == 'paid'    ? 'selected' : '' ?>>✅ Đã thanh toán</option>
                                    <option value="failed"  <?= ($b['PaymentStatus'] ?? '') == 'failed'  ? 'selected' : '' ?>>❌ Thất bại</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái đơn hàng</label>
                                <input type="text" name="Status" value="<?= htmlspecialchars($b['Status'] ?? '') ?>" placeholder="VD: Đã xác nhận, Đang xử lý...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NÚT HÀNH ĐỘNG -->
                <div class="action-bar">
                    <a href="manage_bookings.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

</div></div></body>
</html>