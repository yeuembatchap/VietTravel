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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $conn->prepare("SELECT * FROM tours WHERE ID = ?");
$stmt->execute([$id]);
$tour = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tour) {
    echo "<script>alert('Không tìm thấy tour!'); window.location.href='manage_tours.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $duration = $_POST['duration'];
    $price = $_POST['price'];
    $departureDate = !empty(trim($_POST['departure_date'])) ? trim($_POST['departure_date']) : null;
    $description = $_POST['description'];
    
    // Mặc định giữ nguyên banner/link cũ
    $bannerPath = $tour['Banner'] ?? ''; 

    // Ưu tiên 1: Nếu có Upload File từ máy tính
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $fileName = time() . '_' . basename($_FILES["image"]["name"]);
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $fileName)) {
            $bannerPath = 'images/' . $fileName; 
        }
    } 
    // Ưu tiên 2: Nếu không Upload File mà có dán Link ảnh vào ô Link
    elseif (!empty(trim($_POST['image_url']))) {
        $bannerPath = trim($_POST['image_url']);
    }

    $updateStmt = $conn->prepare("UPDATE tours SET Name=?, Duration=?, Price=?, TimeStart=?, Description=?, Banner=? WHERE ID=?");
    if ($updateStmt->execute([$name, $duration, $price, $departureDate, $description, $bannerPath, $id])) {
        echo "<script>alert('✅ Cập nhật tour thành công!'); window.location.href='manage_tours.php';</script>";
    } else {
        echo "<script>alert('❌ Có lỗi xảy ra khi cập nhật!');</script>";
    }
}
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group input[type="url"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; }
    .form-group textarea { height: 120px; resize: vertical; }
    .btn-submit { background: #ff9800; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.3s; font-size: 15px; }
    .btn-submit:hover { background: #f57c00; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; margin-left: 10px; font-size: 15px; }
    .current-img { max-width: 150px; border-radius: 5px; margin-bottom: 10px; display: block; border: 1px solid #ddd; }
    .image-options { background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; border-radius: 5px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #fff3e0; color: #e65100;">✏️ CẬP NHẬT TOUR DU LỊCH</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tên Tour Du Lịch:</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($tour['Name'] ?? '') ?>">
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div class="form-group" style="flex: 1;">
                    <label>Thời gian (Duration):</label>
                    <input type="text" name="duration" required value="<?= htmlspecialchars($tour['Duration'] ?? '') ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Giá Tour (VNĐ):</label>
                    <input type="number" name="price" required value="<?= htmlspecialchars($tour['Price'] ?? 0) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Ngày xuất phát:</label>
                <input type="date" name="departure_date" required value="<?= htmlspecialchars($tour['TimeStart'] ?? '') ?>">
            </div>

            <div class="form-group image-options">
                <label>Hình ảnh hiện tại:</label>
                <?php 
                    $currentBanner = $tour['Banner'] ?? 'images/default-tour.jpg'; 
                    // Kiểm tra xem nó có phải là Link Web không
                    $isUrl = (strpos($currentBanner, 'http') === 0);
                    $imgSrc = $isUrl ? htmlspecialchars($currentBanner) : '../' . htmlspecialchars($currentBanner);
                ?>
                <img src="<?= $imgSrc ?>" class="current-img" alt="Ảnh hiện tại">
                
                <label style="margin-top: 15px; color: #d35400;">Lựa chọn 1: Tải ảnh mới lên từ máy tính (Ghi đè)</label>
                <input type="file" name="image" accept="image/*">
                
                <div style="text-align: center; margin: 10px 0; font-weight: bold; color: #888;">--- HOẶC ---</div>
                
                <label style="color: #2980b9;">Lựa chọn 2: Dán Link ảnh từ Web/Google (Ưu tiên File upload nếu có cả 2)</label>
                <input type="url" name="image_url" placeholder="Ví dụ: https://picsum.photos/800/600" value="<?= $isUrl ? htmlspecialchars($currentBanner) : '' ?>">
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết:</label>
                <textarea name="description"><?= htmlspecialchars($tour['Description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-submit">🔄 Cập nhật ngay</button>
            <a href="manage_tours.php" class="btn-cancel">Quay lại</a>
        </form>
    </div>
</div>

</div></div></body></html>