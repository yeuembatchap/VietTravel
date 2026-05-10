<?php include 'admin_header.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['firstname'];
    $lastName = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    // Mã hóa mật khẩu an toàn
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Kiểm tra xem email đã tồn tại chưa
    $checkEmail = $conn->prepare("SELECT Email FROM users WHERE Email = ?");
    $checkEmail->execute([$email]);
    
    if ($checkEmail->rowCount() > 0) {
        echo "<script>alert('❌ Lỗi: Email này đã được sử dụng!');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (FirstName, LastName, Email, Phone, Password, Role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$firstName, $lastName, $email, $phone, $password, $role])) {
            echo "<script>alert('✅ Thêm người dùng thành công!'); window.location.href='manage_users.php';</script>";
        } else {
            echo "<script>alert('❌ Có lỗi xảy ra khi lưu vào database!');</script>";
        }
    }
}
?>

<style>
    .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; }
    .btn-submit:hover { background: #1b5e20; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header">➕ THÊM NGƯỜI DÙNG MỚI</div>
    <div class="box-body">
        <form action="" method="POST">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Họ (Last Name):</label>
                    <input type="text" name="lastname" required placeholder="VD: Nguyễn Văn">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Tên (First Name):</label>
                    <input type="text" name="firstname" required placeholder="VD: A">
                </div>
            </div>

            <div class="form-group">
                <label>Email đăng nhập:</label>
                <input type="email" name="email" required placeholder="VD: email@example.com">
            </div>

            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="text" name="phone" required placeholder="VD: 0912345678">
            </div>

            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required placeholder="Nhập mật khẩu cho tài khoản này">
            </div>

            <div class="form-group">
                <label>Phân quyền:</label>
                <select name="role">
                    <option value="user">Khách hàng thường (User)</option>
                    <option value="admin">Quản trị viên (Admin)</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">💾 Tạo tài khoản</button>
            <a href="manage_users.php" class="btn-cancel">Hủy</a>
        </form>
    </div>
</div>

</div></div></body></html>