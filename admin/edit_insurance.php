<?php include 'admin_header.php'; ?>

<?php
if (!isset($_GET['id'])) {
    echo "<script>window.location.href='manage_insurance.php';</script>";
    exit;
}

$id = intval($_GET['id']);

// Lấy dữ liệu cũ để hiển thị ra form
$stmt = $conn->prepare("SELECT * FROM insurance_packages WHERE ID = ?");
$stmt->execute([$id]);
$ins = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ins) {
    echo "<script>alert('Không tìm thấy dữ liệu!'); window.location.href='manage_insurance.php';</script>";
    exit;
}

// Xử lý khi bấm Lưu Cập Nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = intval($_POST['price']);
    $description = $_POST['description'];
    $status = intval($_POST['status']);

    try {
        $updateStmt = $conn->prepare("UPDATE insurance_packages SET Name = ?, PricePerPerson = ?, Description = ?, Status = ? WHERE ID = ?");
        if ($updateStmt->execute([$name, $price, $description, $status, $id])) {
            echo "<script>alert('✅ Cập nhật thành công!'); window.location.href='manage_insurance.php';</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
    }
}
?>

<style>
    .form-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
    .btn-submit { background: #ff9800; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-submit:hover { background: #f57c00; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #fff3e0; color: #e65100;"><i class="fas fa-edit"></i> SỬA BẢO HIỂM: #<?= $ins['ID'] ?></div>
    <div class="box-body">
        <form action="" method="POST">
            <div class="form-group">
                <label>Tên gói bảo hiểm <span style="color:red;">*</span></label>
                <input type="text" name="name" required value="<?= htmlspecialchars($ins['Name']) ?>">
            </div>
            <div class="form-group">
                <label>Mức phí / Người (VNĐ) <span style="color:red;">*</span></label>
                <input type="number" name="price" required min="0" value="<?= $ins['PricePerPerson'] ?>">
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1" <?= $ins['Status'] == 1 ? 'selected' : '' ?>>Đang áp dụng (Hiển thị)</option>
                    <option value="0" <?= $ins['Status'] == 0 ? 'selected' : '' ?>>Ngừng áp dụng (Ẩn)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Mô tả chi tiết quyền lợi</label>
                <textarea name="description" rows="5"><?= htmlspecialchars($ins['Description'] ?? '') ?></textarea>
            </div>
            <div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Cập Nhật</button>
                <a href="manage_insurance.php" class="btn-cancel">Hủy</a>
            </div>
        </form>
    </div>
</div>
</div> </div> </body> </html>