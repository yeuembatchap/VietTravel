<?php
session_start();
require_once '../config/db.php'; // Đảm bảo đường dẫn tới file kết nối CSDL đúng

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form và làm sạch
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    // 1. Kiểm tra xem Username, Email hoặc Phone đã tồn tại chưa
    $checkSql = "SELECT ID FROM users WHERE Username = :username OR Email = :email OR Phone = :phone";
    $stmtCheck = $conn->prepare($checkSql);
    $stmtCheck->execute([
        ':username' => $username,
        ':email' => $email,
        ':phone' => $phone
    ]);

    if ($stmtCheck->rowCount() > 0) {
        $error = "Tên đăng nhập, Email hoặc Số điện thoại đã được sử dụng!";
    } else {
        // 2. Mã hóa mật khẩu để bảo mật
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Cài đặt mặc định cho user mới
        $role = 'customer'; 
        $status = 'active';

        // 3. Thêm vào database
        $insertSql = "INSERT INTO users (Username, FirstName, LastName, Email, Phone, Password, Role, Status) 
                      VALUES (:username, :firstname, :lastname, :email, :phone, :password, :role, :status)";
        
        $stmtInsert = $conn->prepare($insertSql);
        
        try {
            $stmtInsert->execute([
                ':username' => $username,
                ':firstname' => $firstname,
                ':lastname' => $lastname,
                ':email' => $email,
                ':phone' => $phone,
                ':password' => $hashed_password,
                ':role' => $role,
                ':status' => $status
            ]);
            $success = "Đăng ký thành công! Đang chuyển hướng đến trang đăng nhập...";
            
            // Tự động chuyển hướng sau 2 giây
            header("refresh:2;url=login.php");
        } catch (PDOException $e) {
            $error = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}
$disableHeaderBanner = true; include 'header.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản - Tour Nội Địa</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-container">
            <h2>Đăng ký tài khoản</h2>
            
            <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>
            <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

            <form action="" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Họ</label>
                        <input type="text" name="firstname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tên</label>
                        <input type="text" name="lastname" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-auth">ĐĂNG KÝ NGAY</button>
            </form>
            <div class="auth-links-bottom">
                Đã có tài khoản? <a href="login.php">Đăng nhập tại đây</a><br><br>
                <a href="index.php">&larr; Quay lại trang chủ</a>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>