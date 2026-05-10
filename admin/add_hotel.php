<?php include 'admin_header.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $stars = intval($_POST['stars']);
    $price = intval(str_replace(['.', ','], '', $_POST['price']));
    $cityId = intval($_POST['city_id']);
    $image = '';

    // Ưu tiên 1: Nếu có upload ảnh từ máy tính
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $target_dir = "../uploads/hotels/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
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

    // Kiểm tra đã có ảnh chưa (ít nhất một trong hai cách)
    if (empty($image)) {
        echo "<script>alert('❌ Vui lòng chọn hoặc paste hình ảnh!');</script>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO hotels (Name, Description, Stars, Price, Image, CityID) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $stars, $price, $image, $cityId])) {
                echo "<script>alert('✅ Thêm khách sạn thành công!'); window.location.href='manage_hotels.php';</script>";
            }
        } catch (PDOException $e) {
            echo "<script>alert('❌ Lỗi: " . $e->getMessage() . "');</script>";
        }
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
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn-submit:hover { background: #1b5e20; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; display: inline-block; }
    .btn-cancel:hover { background: #757575; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0;"><i class="fas fa-plus-circle"></i> THÊM KHÁCH SẠN MỚI</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Tên khách sạn <span style="color:red;">*</span></label>
                    <input type="text" name="name" required placeholder="VD: Khách sạn Paradise Hà Nội">
                </div>
                <div class="form-group">
                    <label>Thành phố <span style="color:red;">*</span></label>
                    <select name="city_id" required>
                        <option value="">-- Chọn thành phố --</option>
                        <?php foreach($cities as $city): ?>
                            <option value="<?= $city['ID'] ?>"><?= htmlspecialchars($city['Name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Số sao <span style="color:red;">*</span></label>
                    <input type="number" name="stars" min="1" max="5" required placeholder="1-5">
                </div>
                <div class="form-group">
                    <label>Giá mỗi đêm (VNĐ) <span style="color:red;">*</span></label>
                    <input type="number" name="price" required placeholder="VD: 1500000">
                </div>
            </div>

            <div class="form-group">
                <label>Hình ảnh (chọn 1 trong 2 cách) <span style="color:red;">*</span></label>
                <div style="background: #f5f5f5; padding: 15px; border-radius: 5px;">
                    <h4 style="margin-top: 0; color: #333;">Cách 1: Upload file từ máy tính</h4>
                    <input type="file" name="image" accept="image/*" style="margin-bottom: 15px;">
                    
                    <h4 style="color: #333;">Cách 2: Paste URL ảnh từ web</h4>
                    <input type="url" name="image_url" placeholder="VD: https://example.com/hotel.jpg">
                    <small style="display: block; color: #666; margin-top: 5px;">💡 Cách 1 ưu tiên hơn. Nếu có cả 2, chỉ file upload sẽ được lưu</small>
                </div>
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết <span style="color:red;">*</span></label>
                <textarea name="description" required placeholder="Nhập thông tin chi tiết về khách sạn: tiện nghi, dịch vụ, vị trí..."></textarea>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Lưu khách sạn</button>
                <a href="manage_hotels.php" class="btn-cancel"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
        </form>
    </div>
</div>

</div>
</div>
</body>
</html>
