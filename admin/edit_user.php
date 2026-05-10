<?php include 'admin_header.php'; ?>

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Lấy thông tin user hiện tại (bắt cả trường hợp database dùng chữ 'id' thường hoặc 'ID' hoa)
$stmt = $conn->prepare("SELECT * FROM users WHERE ID = ? OR id = ?");
$stmt->execute([$id, $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<script>alert('Không tìm thấy người dùng!'); window.location.href='manage_users.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = $_POST['firstname'];
    $lastName = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    
    // Nếu có nhập mật khẩu mới thì mới cập nhật mật khẩu
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET FirstName=?, LastName=?, Email=?, Phone=?, Role=?, Password=? WHERE ID=? OR id=?");
        $result = $updateStmt->execute([$firstName, $lastName, $email, $phone, $role, $password, $id, $id]);
    } else {
        // Nếu để trống ô mật khẩu thì giữ nguyên mật khẩu cũ
        $updateStmt = $conn->prepare("UPDATE users SET FirstName=?, LastName=?, Email=?, Phone=?, Role=? WHERE ID=? OR id=?");
        $result = $updateStmt->execute([$firstName, $lastName, $email, $phone, $role, $id, $id]);
    }

    if ($result) {
        echo "<script>alert('✅ Cập nhật thông tin thành công!'); window.location.href='manage_users.php';</script>";
    }
}
?>

<style>
    /* Dùng chung CSS của trang add_user.php cho đồng bộ */
    .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .btn-submit { background: #0288d1; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 15px; }
    .btn-submit:hover { background: #01579b; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e1f5fe; color: #0277bd;">✏️ CẬP NHẬT THÔNG TIN NGƯỜI DÙNG</div>
    <div class="box-body">
        <form action="" method="POST">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Họ (Last Name):</label>
                    <input type="text" name="lastname" required value="<?= htmlspecialchars($user['LastName'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Tên (First Name):</label>
                    <input type="text" name="firstname" required value="<?= htmlspecialchars($user['FirstName'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email đăng nhập:</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['Email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['Phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Phân quyền:</label>
                <select name="role">
                    <?php $currentRole = $user['Role'] ?? 'user'; ?>
                    <option value="user" <?= $currentRole == 'user' ? 'selected' : '' ?>>Khách hàng thường (User)</option>
                    <option value="admin" <?= $currentRole == 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                </select>
            </div>

            <div class="form-group" style="background: #fff8e1; padding: 15px; border-left: 4px solid #ffc107; border-radius: 4px;">
                <label style="color: #f57f17;">Đổi mật khẩu (Tùy chọn):</label>
                <span style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">* Bỏ trống ô này nếu bạn KHÔNG muốn đổi mật khẩu của tài khoản này.</span>
                <input type="password" name="password" placeholder="Nhập mật khẩu mới (nếu muốn đổi)">
            </div>

            <button type="submit" class="btn-submit">🔄 Lưu thay đổi</button>
            <a href="manage_users.php" class="btn-cancel">Quay lại</a>
        </form>
    </div>
</div>

</div></div></body></html>