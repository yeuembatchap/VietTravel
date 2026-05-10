<?php include 'admin_header.php'; ?>

<?php
// Lấy danh sách Thành phố để đưa vào Select Box
$cities = $conn->query("SELECT * FROM cities ORDER BY Name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $seats = intval($_POST['seats']);
    $cityId = intval($_POST['city_id']);
    $price = intval($_POST['price']);
    $hasDriver = isset($_POST['has_driver']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $description = $_POST['description'];
    
    // Xử lý Upload Ảnh
    $imageName = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../uploads/";
        // Tạo thư mục nếu chưa có
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $imageName = time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $targetDir . $imageName);
    }

    $stmt = $conn->prepare("INSERT INTO vehicles (Name, Seats, CityID, PricePerDay, HasDriver, Image, Description, Status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $seats, $cityId, $price, $hasDriver, $imageName, $description, $status])) {
        echo "<script>alert('✅ Đã thêm xe thành công!'); window.location.href='manage_cars.php';</script>";
    }
}
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
    .checkbox-group { display: flex; gap: 20px; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 4px; }
    .checkbox-group label { display: inline-flex; align-items: center; font-weight: normal; margin-bottom: 0; cursor: pointer; gap: 8px; }
</style>

<div class="content-box form-container">
    <div class="box-header">➕ THÊM XE MỚI</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 2;">
                    <label>Tên xe (VD: Toyota Vios 2023):</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Số chỗ ngồi:</label>
                    <input type="number" name="seats" required value="4" min="2" max="50">
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Khu vực (Thành phố):</label>
                    <select name="city_id" required>
                        <option value="">-- Chọn thành phố --</option>
                        <?php foreach($cities as $city): ?>
                            <option value="<?= $city['ID'] ?>"><?= htmlspecialchars($city['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Giá thuê / Ngày (VNĐ):</label>
                    <input type="number" name="price" required placeholder="VD: 800000">
                </div>
            </div>

            <div class="form-group">
                <label>Hình ảnh xe:</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-group checkbox-group">
                <label><input type="checkbox" name="has_driver" value="1"> <strong>Xe có kèm Tài xế</strong></label>
                <label><input type="checkbox" name="status" value="1" checked> <strong>Đang hoạt động (Cho phép thuê)</strong></label>
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" rows="5" placeholder="Nhập thông tin chi tiết về xe..."></textarea>
            </div>

            <button type="submit" class="btn-submit">💾 Lưu dữ liệu</button>
            <a href="manage_cars.php" class="btn-cancel">Hủy</a>
        </form>
    </div>
</div>

</div></div></body></html>