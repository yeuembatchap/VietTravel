<?php include 'admin_header.php'; ?>

<?php
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM hotels WHERE ID = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    echo "<script>alert('Không tìm thấy khách sạn!'); window.location.href='manage_hotels.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $stars = intval($_POST['stars']);
    $price = intval(str_replace(['.', ','], '', $_POST['price']));
    $cityId = intval($_POST['city_id']);
    $image = $hotel['Image']; // Giữ nguyên ảnh cũ mặc định

    // Ưu tiên 1: Nếu có upload ảnh từ máy tính
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $target_dir = "../uploads/hotels/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $newImage = time() . '_' . $_FILES['image']['name'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $newImage)) {
                // Xóa ảnh cũ (chỉ xóa file upload, không xóa URL web)
                if (!empty($hotel['Image']) && strpos($hotel['Image'], 'http') !== 0 && file_exists("../uploads/hotels/" . $hotel['Image'])) {
                    unlink("../uploads/hotels/" . $hotel['Image']);
                }
                $image = $newImage;
            }
        }
    } 
    // Ưu tiên 2: Nếu không upload file mà có paste URL ảnh từ web
    elseif (!empty(trim($_POST['image_url']))) {
        $imageUrl = trim($_POST['image_url']);
        // Kiểm tra URL hợp lệ
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $image = $imageUrl;
        }
    }

    try {
        $stmt = $conn->prepare("UPDATE hotels SET Name = ?, Description = ?, Stars = ?, Price = ?, Image = ?, CityID = ? WHERE ID = ?");
        if ($stmt->execute([$name, $description, $stars, $price, $image, $cityId, $id])) {
            echo "<script>alert('✅ Cập nhật khách sạn thành công!'); window.location.href='manage_hotels.php';</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('❌ Lỗi: " . $e->getMessage() . "');</script>";
    }
}

// Lấy danh sách thành phố
$citiesStmt = $conn->query("SELECT ID, Name FROM cities ORDER BY Name ASC");
$cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .form-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="url"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .form-group textarea { height: 150px; resize: vertical; font-family: inherit; }
    .form-row { display: flex; gap: 20px; }
    .form-row .form-group { flex: 1; }
    .current-img { max-width: 150px; border-radius: 5px; margin-bottom: 10px; display: block; border: 1px solid #ddd; }
    .btn-submit { background: #ff9800; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn-submit:hover { background: #f57c00; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; display: inline-block; }
    .btn-cancel:hover { background: #757575; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #fff3e0; color: #e65100;"><i class="fas fa-edit"></i> CẬP NHẬT KHÁCH SẠN</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Tên khách sạn <span style="color:red;">*</span></label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($hotel['Name']) ?>">
                </div>
                <div class="form-group">
                    <label>Thành phố <span style="color:red;">*</span></label>
                    <select name="city_id" required>
                        <option value="">-- Chọn thành phố --</option>
                        <?php foreach($cities as $city): ?>
                            <option value="<?= $city['ID'] ?>" <?= ($city['ID'] == $hotel['CityID']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($city['Name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Số sao <span style="color:red;">*</span></label>
                    <input type="number" name="stars" min="1" max="5" required value="<?= intval($hotel['Stars']) ?>">
                </div>
                <div class="form-group">
                    <label>Giá mỗi đêm (VNĐ) <span style="color:red;">*</span></label>
                    <input type="number" name="price" required value="<?= intval($hotel['Price']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Hình ảnh hiện tại</label>
                <?php if(!empty($hotel['Image'])): ?>
                    <?php 
                        $imgSrc = (strpos($hotel['Image'], 'http') === 0) ? htmlspecialchars($hotel['Image']) : '../uploads/hotels/' . htmlspecialchars($hotel['Image']);
                    ?>
                    <img src="<?= $imgSrc ?>" class="current-img" onerror="this.src='../assets/img/default-hotel.jpg'">
                <?php else: ?>
                    <p style="color: #999;">Chưa có hình ảnh</p>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Thay đổi hình ảnh (chọn 1 trong 2 cách)</label>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 10px;">
                    <h4 style="margin-top: 0; color: #333;">Cách 1: Upload file từ máy tính</h4>
                    <input type="file" name="image" accept="image/*" style="margin-bottom: 15px;">
                    
                    <h4 style="color: #333;">Cách 2: Paste URL ảnh từ web</h4>
                    <input type="url" name="image_url" placeholder="VD: https://example.com/hotel.jpg">
                    <small style="display: block; color: #666; margin-top: 5px;">💡 Để trống cả hai nếu không muốn thay đổi ảnh</small>
                </div>
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết <span style="color:red;">*</span></label>
                <textarea name="description" required><?= htmlspecialchars($hotel['Description']) ?></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Cập nhật</button>
                <a href="manage_hotels.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
</body>
</html>
