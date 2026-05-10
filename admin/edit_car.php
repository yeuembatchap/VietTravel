<?php include 'admin_header.php'; ?>

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE ID = ?");
$stmt->execute([$id]);
$car = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$car) {
    echo "<script>alert('Không tìm thấy xe!'); window.location.href='manage_cars.php';</script>";
    exit;
}

$cities = $conn->query("SELECT * FROM cities ORDER BY Name ASC")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $seats = intval($_POST['seats']);
    $cityId = intval($_POST['city_id']);
    $price = intval($_POST['price']);
    $hasDriver = isset($_POST['has_driver']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $description = $_POST['description'];
    
    $imageName = $car['Image']; // Mặc định giữ ảnh cũ
    
    // Nếu có upload ảnh mới
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "../uploads/";
        $imageName = time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $targetDir . $imageName);
        
        // Xóa ảnh cũ đi cho nhẹ server
        if (!empty($car['Image']) && file_exists($targetDir . $car['Image'])) {
            unlink($targetDir . $car['Image']);
        }
    }

    $updateStmt = $conn->prepare("UPDATE vehicles SET Name=?, Seats=?, CityID=?, PricePerDay=?, HasDriver=?, Image=?, Description=?, Status=? WHERE ID=?");
    if ($updateStmt->execute([$name, $seats, $cityId, $price, $hasDriver, $imageName, $description, $status, $id])) {
        echo "<script>alert('✅ Cập nhật thành công!'); window.location.href='manage_cars.php';</script>";
    }
}
?>

<style>
    /* Dùng chung CSS với form add_car.php */
    .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .btn-submit { background: #0288d1; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
    .checkbox-group { display: flex; gap: 20px; align-items: center; background: #f9f9f9; padding: 15px; border-radius: 4px; }
    .checkbox-group label { display: inline-flex; align-items: center; font-weight: normal; margin-bottom: 0; cursor: pointer; gap: 8px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e1f5fe; color: #0277bd;">✏️ CẬP NHẬT THÔNG TIN XE</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 2;">
                    <label>Tên xe:</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($car['Name']) ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Số chỗ ngồi:</label>
                    <input type="number" name="seats" required value="<?= $car['Seats'] ?>" min="2" max="50">
                </div>
            </div>

            <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                    <label>Khu vực (Thành phố):</label>
                    <select name="city_id" required>
                        <option value="">-- Chọn thành phố --</option>
                        <?php foreach($cities as $city): ?>
                            <option value="<?= $city['ID'] ?>" <?= $car['CityID'] == $city['ID'] ? 'selected' : '' ?>><?= htmlspecialchars($city['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Giá thuê / Ngày (VNĐ):</label>
                    <input type="number" name="price" required value="<?= $car['PricePerDay'] ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Hình ảnh hiện tại:</label>
                <?php if(!empty($car['Image'])): ?>
                    <img src="../uploads/<?= $car['Image'] ?>" style="height: 100px; border-radius: 4px; margin-bottom: 10px; display: block;">
                <?php endif; ?>
                <label>Thay đổi hình ảnh (Bỏ trống nếu giữ nguyên):</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-group checkbox-group">
                <label><input type="checkbox" name="has_driver" value="1" <?= $car['HasDriver'] ? 'checked' : '' ?>> <strong>Xe có kèm Tài xế</strong></label>
                <label><input type="checkbox" name="status" value="1" <?= $car['Status'] ? 'checked' : '' ?>> <strong>Đang hoạt động</strong></label>
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" rows="5"><?= htmlspecialchars($car['Description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">🔄 Lưu thay đổi</button>
            <a href="manage_cars.php" class="btn-cancel">Quay lại</a>
        </form>
    </div>
</div>

</div></div></body></html>