<?php
session_start();
require_once '../config/db.php';
$error = '';

// 1. KIỂM TRA NẾU ĐÃ ĐĂNG NHẬP TỪ TRƯỚC
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['Role'] == 'admin') {
        header("Location: ../admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM USERS WHERE Username = ? AND Password = ? AND Status = 'Active'");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Lưu thông tin vào Session
        $_SESSION['user'] = [
            'id' => $user['ID'],
            'username' => $user['Username'],
            'fullname' => $user['FirstName'] . ' ' . $user['LastName'],
            'Role' => $user['Role'] // SỬA Ở ĐÂY: Viết hoa chữ 'R' để khớp với admin_header.php
        ];
        
        // Phân quyền: Nếu là admin thì vào trang quản trị, user thì về trang chủ
        if ($user['Role'] == 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = 'Sai tên đăng nhập, mật khẩu hoặc tài khoản bị khóa!';
    }
}
$disableHeaderBanner = true;
include 'header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - Tour Nội Địa</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <h2>Đăng nhập</h2>
            
            <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-auth">ĐĂNG NHẬP</button>
            </form>
            <div class="auth-links-bottom">
                Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a><br><br>
                <a href="index.php">&larr; Quay lại trang chủ</a>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>