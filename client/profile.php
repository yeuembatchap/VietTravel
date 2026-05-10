<?php
session_start();
require_once '../config/db.php';
// 1. Tính tổng tiền thực tế từ các đơn hàng ĐÃ THANH TOÁN
$userID = $_SESSION['user']['id'];
$stmtSpent = $conn->prepare("SELECT SUM(TotalPrice) as RealTotal FROM bookings WHERE UserID = ? AND PaymentStatus = 'paid'");
$stmtSpent->execute([$userID]);
$realTotal = $stmtSpent->fetch(PDO::FETCH_ASSOC)['RealTotal'];

// Nếu chưa có đơn nào thanh toán thì gán là 0
$totalSpent = $realTotal ? $realTotal : 0;

// 2. Tự động tính Hạng thành viên và Gán Mã Voucher
$customerTier = 'Thành viên mới';
$voucherCode = ''; // Khởi tạo biến rỗng
$voucherDesc = '';

if ($totalSpent >= 50000000) {
    $customerTier = '💎 Kim Cương';
    $voucherCode = 'VIP20';
    $voucherDesc = 'Giảm 20% tổng hóa đơn';
} elseif ($totalSpent >= 20000000) {
    $customerTier = '🥇 Vàng';
    $voucherCode = 'GOLD15'; // Mã giảm 15% (Bạn có thể thêm mã này vào file tour_detail.php sau)
    $voucherDesc = 'Giảm 15% tổng hóa đơn';
} elseif ($totalSpent >= 10000000) {
    $customerTier = '🥈 Bạc';
    $voucherCode = 'SALE10';
    $voucherDesc = 'Giảm 10% tổng hóa đơn';
} elseif ($totalSpent > 0) {
    $customerTier = '🥉 Đồng';
    // Hạng Đồng chưa có mã giảm giá, biến $voucherCode vẫn rỗng
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['user']['id'];
$success_msg = '';
$error_msg = '';

// XỬ LÝ KHI NGƯỜI DÙNG BẤM "CẬP NHẬT THÔNG TIN"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['firstname']);
    $lastName = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;

    // Cập nhật vào Database
    try {
        $updateSql = "UPDATE users SET FirstName = ?, LastName = ?, Email = ?, Phone = ?, DOB = ? WHERE ID = ?";
        $stmtUpdate = $conn->prepare($updateSql);
        $stmtUpdate->execute([$firstName, $lastName, $email, $phone, $dob, $userID]);
        
        $success_msg = "Cập nhật thông tin cá nhân thành công!";
        
        // Cập nhật lại Session tên hiển thị
        $_SESSION['user']['fullname'] = $firstName . ' ' . $lastName;
        
    } catch (PDOException $e) {
        // Bắt lỗi nếu Email hoặc Số điện thoại bị trùng với người khác (do cài đặt UNIQUE trong DB)
        if ($e->getCode() == 23000) {
            $error_msg = "Email hoặc Số điện thoại này đã được sử dụng bởi tài khoản khác!";
        } else {
            $error_msg = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}

// XỬ LÝ KHI NGƯỜI DÙNG BẤM "THAY ĐỔI MẬT KHẨU"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Kiểm tra mật khẩu mới có trùng nhau không
    if ($newPassword !== $confirmPassword) {
        $error_msg = "Mật khẩu mới không trùng khớp!";
    } elseif (strlen($newPassword) < 6) {
        $error_msg = "Mật khẩu phải có ít nhất 6 ký tự!";
    } else {
        // Lấy mật khẩu hiện tại từ DB
        $stmtCheck = $conn->prepare("SELECT Password FROM users WHERE ID = ?");
        $stmtCheck->execute([$userID]);
        $userPassword = $stmtCheck->fetch(PDO::FETCH_ASSOC)['Password'];
        
        // Kiểm tra mật khẩu hiện tại (so sánh trực tiếp vì DB lưu plain text)
        if ($currentPassword !== $userPassword) {
            $error_msg = "Mật khẩu hiện tại không đúng!";
        } else {
            // Cập nhật mật khẩu mới (lưu plain text như cách DB lưu trữ)
            try {
                $stmtUpdatePassword = $conn->prepare("UPDATE users SET Password = ? WHERE ID = ?");
                $stmtUpdatePassword->execute([$newPassword, $userID]);
                $success_msg = "Thay đổi mật khẩu thành công!";
            } catch (PDOException $e) {
                $error_msg = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    }
}

// LẤY THÔNG TIN HIỆN TẠI CỦA NGƯỜI DÙNG TỪ DB ĐỂ IN RA FORM
$stmt = $conn->prepare("SELECT * FROM users WHERE ID = ?");
$stmt->execute([$userID]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ Sơ Cá Nhân - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* CSS Dành riêng cho bố cục trang cá nhân */
        .profile-container { display: flex; max-width: 1200px; margin: 40px auto; gap: 30px; padding: 0 20px; }
        .profile-sidebar { flex: 1; background: #f9fbe7; padding: 20px; border-radius: 8px; border: 1px solid #8bc34a; height: fit-content; }
        .profile-sidebar ul { list-style: none; padding: 0; margin: 0; }
        .profile-sidebar li a { display: block; padding: 12px 15px; color: #333; text-decoration: none; border-bottom: 1px solid #ddd; transition: 0.3s; }
        .profile-sidebar li a:hover, .profile-sidebar li a.active { background: #8bc34a; color: white; border-radius: 4px; border-bottom: none; }
        .profile-main { flex: 3; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .tier-badge { display: inline-block; padding: 5px 15px; background: #ffd700; color: #333; font-weight: bold; border-radius: 20px; font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>

<?php include '../header.php'; ?>

<div class="profile-container">
    <div class="profile-sidebar">
        <h3 style="text-align: center; color: #003366; margin-top: 0;">Xin chào,<br><?= htmlspecialchars($userInfo['FirstName']) ?>!</h3>
        <ul>
            <li><a href="profile.php" class="active">Tài khoản của tôi</a></li>
            <li><a href="my_bookings.php">Lịch sử đặt Tour</a></li>
            <li><a href="logout.php" style="color: red; font-weight: bold;">Đăng xuất</a></li>
        </ul>
    </div>

    <div class="profile-main">
        <h2 style="margin-top: 0; color: #003366; border-bottom: 2px solid #8bc34a; padding-bottom: 10px;">HỒ SƠ CÁ NHÂN</h2>
        
        <div>
            <span class="tier-badge">Hạng thành viên: <strong style="color: #003366;"><?= $customerTier ?></strong></span>
            <p style="margin-top: 0; color: #555;">
                <strong>Tổng tiền đã chi tiêu:</strong> 
                <span style="color: red; font-size: 1.2rem; font-weight: bold;">
                    <?= number_format($totalSpent, 0, ',', '.') ?> đ
                </span>
            </p>

            <?php if (!empty($voucherCode)): ?>
                <div style="background: #fff8e1; border: 1px dashed #ffb300; padding: 15px; border-radius: 8px; margin-top: 15px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <p style="margin: 0 0 10px 0; color: #d35400; font-weight: bold;">🎁 Ưu đãi độc quyền cho hạng <?= $customerTier ?>:</p>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="background: #fff; border: 2px solid #e65100; color: #e65100; padding: 6px 15px; font-size: 1.3rem; font-weight: bold; border-radius: 4px; letter-spacing: 2px;">
                            <?= $voucherCode ?>
                        </span>
                        <span style="color: #555; font-size: 0.95rem;">( <?= $voucherDesc ?> )</span>
                    </div>
                    <p style="margin: 10px 0 0 0; font-size: 0.85rem; color: #777;">
                        * Nhập mã này ở bước Đặt Tour để được giảm giá trực tiếp.
                    </p>
                </div>
            <?php else: ?>
                <div style="background: #f5f5f5; border: 1px dashed #ccc; padding: 15px; border-radius: 8px; margin-top: 15px; display: inline-block;">
                    <p style="margin: 0; color: #666; font-size: 0.95rem;">
                        🔒 Chăm chỉ vi vu thêm để thăng hạng và nhận mã giảm giá VIP bạn nhé!
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <?php if($success_msg): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="grid-2">
                <div class="form-group">
                    <label>Tên (First Name)</label>
                    <input type="text" name="firstname" value="<?= htmlspecialchars($userInfo['FirstName']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Họ (Last Name)</label>
                    <input type="text" name="lastname" value="<?= htmlspecialchars($userInfo['LastName']) ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($userInfo['Email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($userInfo['Phone']) ?>" required>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars($userInfo['DOB']) ?>">
                </div>
                <div class="form-group">
                    <label>Tên đăng nhập (Không thể đổi)</label>
                    <input type="text" value="<?= htmlspecialchars($userInfo['Username']) ?>" disabled style="background: #f1f1f1;">
                </div>
            </div>


            <button type="submit" name="update_profile" style="background: #003366; color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 10px;">LƯU THAY ĐỔI</button>
        </form>

        <hr style="margin: 40px 0; border: none; border-top: 2px solid #8bc34a;">

        <h3 style="color: #003366; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;">🔐 THAY ĐỔI MẬT KHẨU</h3>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Mật khẩu hiện tại</label>
                <input type="password" name="current_password" required>
            </div>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu mới</label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
            </div>
            
            <button type="submit" name="change_password" style="background: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 10px;">THAY ĐỔI MẬT KHẨU</button>
        </form>
    </div>
</div>

</body>
</html>