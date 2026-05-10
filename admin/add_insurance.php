<?php include 'admin_header.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $price = intval($_POST['price']);
    $description = $_POST['description'];
    $status = intval($_POST['status']);

    try {
        $stmt = $conn->prepare("INSERT INTO insurance_packages (Name, PricePerPerson, Description, Status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $price, $description, $status])) {
            echo "<script>alert('✅ Đã thêm gói bảo hiểm thành công!'); window.location.href='manage_insurance.php';</script>";
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
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0;"><i class="fas fa-plus-circle"></i> THÊM BẢO HIỂM MỚI</div>
    <div class="box-body">
        <form action="" method="POST">
            <div class="form-group">
                <label>Tên gói bảo hiểm <span style="color:red;">*</span></label>
                <input type="text" name="name" required placeholder="VD: Bảo hiểm du lịch cao cấp...">
            </div>
            <div class="form-group">
                <label>Mức phí / Người (VNĐ) <span style="color:red;">*</span></label>
                <input type="number" name="price" required min="0" placeholder="VD: 150000">
            </div>
            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1">Đang áp dụng (Hiển thị)</option>
                    <option value="0">Ngừng áp dụng (Ẩn)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Mô tả chi tiết quyền lợi</label>
                <textarea name="description" rows="5" placeholder="Nhập mô tả quyền lợi bảo hiểm..."></textarea>
            </div>
            <div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Lưu Dữ Liệu</button>
                <a href="manage_insurance.php" class="btn-cancel">Hủy</a>
            </div>
        </form>
    </div>
</div>
</div> </div> </body> </html>