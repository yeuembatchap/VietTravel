<?php include 'admin_header.php'; ?>

<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname']);
    $lastname  = trim($_POST['lastname']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $areaID    = intval($_POST['area_id']) ?: null;
    $status    = $_POST['status'];
    $dob       = !empty($_POST['dob']) ? $_POST['dob'] : null;

    // Validate
    if (empty($firstname) || empty($lastname) || empty($username) || empty($email) || empty($password)) {
        $error = "Vui lòng điền đầy đủ các trường bắt buộc!";
    } else {
        // Kiểm tra trùng username/email/phone
        $check = $conn->prepare("SELECT ID FROM users WHERE Username = :u OR Email = :e OR Phone = :p");
        $check->execute([':u' => $username, ':e' => $email, ':p' => $phone]);
        if ($check->rowCount() > 0) {
            $error = "Tên đăng nhập, Email hoặc Số điện thoại đã tồn tại!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $conn->prepare("
                    INSERT INTO users (FirstName, LastName, Username, Email, Phone, Password, Role, Status, DOB, AreaID)
                    VALUES (:fn, :ln, :un, :em, :ph, :pw, 'staff', :st, :dob, :area)
                ");
                $stmt->execute([
                    ':fn'   => $firstname,
                    ':ln'   => $lastname,
                    ':un'   => $username,
                    ':em'   => $email,
                    ':ph'   => $phone,
                    ':pw'   => $hashed,
                    ':st'   => $status,
                    ':dob'  => $dob,
                    ':area' => $areaID,
                ]);
                echo "<script>alert('✅ Thêm nhân viên thành công!'); window.location.href='manage_staffs.php';</script>";
                exit;
            } catch (PDOException $e) {
                $error = "Lỗi: " . $e->getMessage();
            }
        }
    }
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
</style>

<div class="content-box">
    <div class="box-header" style="background: #e8eaf6; color: #283593;">
        <i class="fas fa-user-plus"></i> THÊM NHÂN VIÊN MỚI
    </div>
    <div class="box-body">
        <div class="form-wrap">

            <?php if (!empty($error)): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" id="staff-form">

                <!-- THÔNG TIN CÁ NHÂN -->
                <div class="form-section">
                    <div class="form-section-title"><i class="fas fa-user"></i> Thông tin cá nhân</div>
                    <div class="form-section-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Họ <span class="req">*</span></label>
                                <input type="text" name="firstname" required placeholder="Nguyễn" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Tên <span class="req">*</span></label>
                                <input type="text" name="lastname" required placeholder="Văn A" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email <span class="req">*</span></label>
                                <input type="email" name="email" required placeholder="nhanvien@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="phone" placeholder="0901234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" name="dob" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái <span class="req">*</span></label>
                                <select name="status">
                                    <option value="active"    <?= ($_POST['status'] ?? '') == 'active'   ? 'selected' : '' ?>>✅ Đang làm việc</option>
                                    <option value="inactive"  <?= ($_POST['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>❌ Ngừng làm việc</option>
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
                                    <option value="<?= $area['ID'] ?>" <?= ($_POST['area_id'] ?? '') == $area['ID'] ? 'selected' : '' ?>>
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
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tên đăng nhập <span class="req">*</span></label>
                                <input type="text" name="username" required placeholder="nhanvien01" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu <span class="req">*</span></label>
                                <div class="password-wrap">
                                    <input type="password" name="password" id="password" required placeholder="Tối thiểu 6 ký tự">
                                    <i class="fas fa-eye toggle-pw" onclick="togglePassword()"></i>
                                </div>
                                <small id="pw-err" style="color:red; display:none;">Mật khẩu phải có ít nhất 6 ký tự!</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="action-bar">
                    <a href="manage_staffs.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Lưu nhân viên</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const pw = document.getElementById('password');
    const icon = document.querySelector('.toggle-pw');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('staff-form').addEventListener('submit', function(e) {
    const pw = document.getElementById('password').value;
    const pwErr = document.getElementById('pw-err');
    if (pw.length < 6) {
        pwErr.style.display = 'block';
        e.preventDefault();
    } else {
        pwErr.style.display = 'none';
    }
});
</script>

</div></div></body></html>