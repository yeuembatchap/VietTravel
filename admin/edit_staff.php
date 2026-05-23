<?php include 'admin_header.php'; ?>

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<script>alert('ID không hợp lệ!'); window.location.href='manage_staffs.php';</script>";
    exit;
}

$error = '';

// Xử lý submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $areaID    = intval($_POST['area_id']) ?: null;
    $status    = $_POST['status'];
    $dob       = !empty($_POST['dob']) ? $_POST['dob'] : null;
    $newPw     = trim($_POST['new_password']);

    // Kiểm tra trùng username/email/phone (trừ chính mình)
    $check = $conn->prepare("SELECT ID FROM users WHERE (Username = :u OR Email = :e OR Phone = :p) AND ID != :id");
    $check->execute([':u' => $username, ':e' => $email, ':p' => $phone, ':id' => $id]);
    if ($check->rowCount() > 0) {
        $error = "Tên đăng nhập, Email hoặc Số điện thoại đã được dùng bởi tài khoản khác!";
    } else {
        try {
            // Cập nhật không đổi mật khẩu
            $sql = "UPDATE users SET
                        FirstName = :fn, LastName = :ln, Username = :un,
                        Email = :em, Phone = :ph, DOB = :dob,
                        AreaID = :area, Status = :st
                    WHERE ID = :id AND Role = 'staff'";
            $params = [
                ':fn' => $firstname, ':ln' => $lastname, ':un' => $username,
                ':em' => $email,     ':ph' => $phone,    ':dob' => $dob,
                ':area' => $areaID,  ':st' => $status,   ':id' => $id,
            ];

            // Nếu có nhập mật khẩu mới thì cập nhật luôn
            if (!empty($newPw)) {
                if (strlen($newPw) < 6) {
                    $error = "Mật khẩu mới phải có ít nhất 6 ký tự!";
                    goto skip_update;
                }
                $sql = "UPDATE users SET
                            FirstName = :fn, LastName = :ln, Username = :un,
                            Email = :em, Phone = :ph, DOB = :dob,
                            AreaID = :area, Status = :st, Password = :pw
                        WHERE ID = :id AND Role = 'staff'";
                $params[':pw'] = password_hash($newPw, PASSWORD_DEFAULT);
            }

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            echo "<script>alert('✅ Cập nhật nhân viên thành công!'); window.location.href='manage_staffs.php';</script>";
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi: " . $e->getMessage();
        }
    }
    skip_update:
}

// Lấy thông tin nhân viên
$stmt = $conn->prepare("SELECT u.*, a.Name AS AreaName FROM users u LEFT JOIN areas a ON u.AreaID = a.ID WHERE u.ID = :id AND u.Role = 'staff'");
$stmt->execute([':id' => $id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$s) {
    echo "<script>alert('Không tìm thấy nhân viên!'); window.location.href='manage_staffs.php';</script>";
    exit;
}

// Lấy danh sách khu vực
$areas = $conn->query("SELECT * FROM areas ORDER BY Name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .form-wrap { max-width: 750px; margin: 0 auto; }
    .form-section { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; margin-bottom: 20px; overflow: hidden; }
    .form-section-title { background: #e8eaf6; color: #283593; font-weight: bold; font-size: 14px; padding: 12px 20px; border-bottom: 1px solid #c5cae9; display: flex; align-items: center; gap: 8px; text-transform: uppercase; }
    .form-section-body { padding: 20px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #555; }
    .form-group .req { color: #e53935; }
    .form-group input, .form-group select {
        padding: 10px 13px; border: 1px solid #ddd; border-radius: 6px;
        font-size: 14px; outline: none; transition: border-color 0.2s;
    }
    .form-group input:focus, .form-group select:focus {
        border-color: #3949ab; box-shadow: 0 0 0 3px rgba(57,73,171,0.1);
    }
    .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
    .action-bar { display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px; }
    .btn-save { background: #283593; color: #fff; padding: 12px 30px; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
    .btn-save:hover { background: #1a237e; }
    .btn-back { background: #757575; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 15px; font-weight: bold; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-back:hover { background: #616161; }
    .password-wrap { position: relative; }
    .password-wrap input { width: 100%; padding-right: 40px; box-sizing: border-box; }
    .toggle-pw { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; font-size: 16px; }
    .toggle-pw:hover { color: #333; }
    .info-note { background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px; padding: 10px 14px; font-size: 13px; color: #795548; margin-bottom: 16px; }
    .avatar-circle { width: 60px; height: 60px; border-radius: 50%; background: #e8eaf6; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: bold; color: #283593; margin-bottom: 12px; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e8eaf6; color: #283593;">
        <i class="fas fa-user-edit"></i> CHỈNH SỬA NHÂN VIÊN
        <span style="font-size:13px; font-weight:normal; margin-left:10px;">
            — <strong><?= htmlspecialchars(trim($s['FirstName'] . ' ' . $s['LastName'])) ?></strong>
        </span>
    </div>
    <div class="box-body">
        <div class="form-wrap">

            <?php if (!empty($error)): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="edit-staff-form">

                <!-- THÔNG TIN CÁ NHÂN -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-user"></i> Thông tin cá nhân</div>
                    <div class="form-section-body">
                        <div class="avatar-circle">
                            <?= strtoupper(mb_substr($s['FirstName'], 0, 1)) . strtoupper(mb_substr($s['LastName'], 0, 1)) ?>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Họ <span class="req">*</span></label>
                                <input type="text" name="firstname" required value="<?= htmlspecialchars($s['FirstName']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Tên <span class="req">*</span></label>
                                <input type="text" name="lastname" required value="<?= htmlspecialchars($s['LastName']) ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email <span class="req">*</span></label>
                                <input type="email" name="email" required value="<?= htmlspecialchars($s['Email']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($s['Phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" name="dob" value="<?= htmlspecialchars($s['DOB'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái <span class="req">*</span></label>
                                <select name="status">
                                    <option value="active"   <?= $s['Status'] == 'active'   ? 'selected' : '' ?>>✅ Đang làm việc</option>
                                    <option value="inactive" <?= $s['Status'] == 'inactive' ? 'selected' : '' ?>>❌ Ngừng làm việc</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KHU VỰC LÀM VIỆC -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Khu vực làm việc</div>
                    <div class="form-section-body">
                        <div class="form-group">
                            <label>Khu vực <span class="req">*</span></label>
                            <select name="area_id" required>
                                <option value="">-- Chọn khu vực --</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?= $area['ID'] ?>" <?= $s['AreaID'] == $area['ID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($area['Name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TÀI KHOẢN ĐĂNG NHẬP -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-lock"></i> Tài khoản đăng nhập</div>
                    <div class="form-section-body">
                        <div class="info-note">
                            <i class="fas fa-info-circle"></i>
                            Để trống ô mật khẩu mới nếu không muốn thay đổi mật khẩu hiện tại.
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tên đăng nhập <span class="req">*</span></label>
                                <input type="text" name="username" required value="<?= htmlspecialchars($s['Username']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu mới</label>
                                <div class="password-wrap">
                                    <input type="password" name="new_password" id="new_password" placeholder="Để trống nếu không đổi">
                                    <i class="fas fa-eye toggle-pw" onclick="togglePassword()"></i>
                                </div>
                                <small id="pw-err" style="color:red; display:none;">Mật khẩu phải có ít nhất 6 ký tự!</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-bar">
                    <a href="manage_staffs.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Lưu thay đổi</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('new_password');
    const icon = document.querySelector('.toggle-pw');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('edit-staff-form').addEventListener('submit', function(e) {
    const pw = document.getElementById('new_password').value;
    const pwErr = document.getElementById('pw-err');
    if (pw.length > 0 && pw.length < 6) {
        pwErr.style.display = 'block';
        e.preventDefault();
    } else {
        pwErr.style.display = 'none';
    }
});
</script>

</div></div></body></html>