<?php include 'admin_header.php'; ?>

<?php
// Đảm bảo bảng tours có cột TimeStart để lưu ngày xuất phát
function ensureTimeStartColumn(PDO $conn) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM tours LIKE 'TimeStart'");
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $conn->exec("ALTER TABLE tours ADD COLUMN TimeStart DATE NULL AFTER Duration");
        }
    } catch (Exception $e) {
        // Nếu không thể tạo cột tự động, vẫn tiếp tục để tránh lỗi fatal.
    }
}
ensureTimeStartColumn($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form
    $name = $_POST['name'];
    $duration = $_POST['duration'];
    $price = str_replace(['.', ','], '', $_POST['price']); // Xóa dấu chấm/phẩy nếu có
    $departureDate = !empty(trim($_POST['departure_date'])) ? trim($_POST['departure_date']) : null;
    $description = $_POST['description'];
    
    // Xử lý Upload Ảnh
    $bannerPath = 'images/default-tour.jpg'; // Ảnh mặc định nếu không tải lên
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/"; // Lưu ảnh vào thư mục images bên ngoài trang khách
        // Kiểm tra nếu thư mục chưa có thì tạo mới
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Tạo tên file ngẫu nhiên để không bị trùng
        $fileName = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $fileName;
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Lưu đường dẫn vào database (bỏ ../ đi để trang ngoài đọc được)
            $bannerPath = 'images/' . $fileName; 
        }
    }

    // Thêm vào Database (Tên cột: Name, Duration, TimeStart, Price, Description, Banner)
    $stmt = $conn->prepare("INSERT INTO tours (Name, Duration, TimeStart, Price, Description, Banner) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $duration, $departureDate, $price, $description, $bannerPath])) {
        echo "<script>alert('✅ Thêm tour mới thành công!'); window.location.href='manage_tours.php';</script>";
    } else {
        echo "<script>alert('❌ Có lỗi xảy ra, vui lòng thử lại!');</script>";
    }
}
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .form-group textarea { height: 120px; resize: vertical; }
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
    .btn-submit:hover { background: #1b5e20; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; text-decoration: none; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header">➕ THÊM TOUR MỚI</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tên Tour Du Lịch:</label>
                <input type="text" name="name" required placeholder="VD: Tour Đà Lạt 3 ngày 2 đêm">
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label>Thời gian (Duration):</label>
                    <input type="text" name="duration" required placeholder="VD: 3 Ngày 2 Đêm">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Giá Tour (VNĐ):</label>
                    <input type="number" name="price" required placeholder="VD: 2500000">
                </div>
            </div>

            <div class="form-group">
                <label>Ngày xuất phát:</label>
                <input type="date" name="departure_date" required>
            </div>

            <div class="form-group">
                <label>Hình ảnh đại diện:</label>
                <input type="file" name="image" accept="image/*" required>
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết:</label>
                <textarea name="description" placeholder="Nhập lịch trình hoặc mô tả về tour..."></textarea>
            </div>

            <button type="submit" class="btn-submit">💾 Lưu Tour</button>
            <a href="manage_tours.php" class="btn-cancel">Hủy</a>
        </form>
    </div>
</div>

</div></div></body></html>