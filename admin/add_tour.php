<?php include 'admin_header.php'; ?>

<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     $price    = intval(str_replace(['.', ','], '', $_POST['price']));
    $priceKid = intval(str_replace(['.', ','], '', $_POST['price_kid'] ?? 0));

    // Giá không âm
    if ($price < 0 || $priceKid < 0) {
        die("<script>alert('Giá không được là số âm!'); history.back();</script>");
    }

    // Ngày xuất phát >= ngày mai
    $departureDate = !empty(trim($_POST['departure_date'])) ? trim($_POST['departure_date']) : null;
    if (!empty($departureDate)) {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        if ($departureDate < $tomorrow) {
            die("<script>alert('Ngày xuất phát phải từ ngày mai trở đi!'); history.back();</script>");
        }
    }

    // Validate file ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType     = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedMimes)) {
            die("<script>alert('Chỉ được upload file ảnh (JPG, PNG, GIF, WEBP)!'); history.back();</script>");
        }
    }
    $name         = trim($_POST['name']);
    $duration     = trim($_POST['duration']);
    $price        = intval(str_replace(['.', ','], '', $_POST['price']));
    $priceKid     = intval(str_replace(['.', ','], '', $_POST['price_kid'] ?? 0));
    $cityID       = intval($_POST['city_id']);
    $slot         = intval($_POST['slot'] ?? 40);
    $description  = trim($_POST['description']);
    $content      = trim($_POST['description']); // Dùng luôn description cho content
    $departureDate = !empty(trim($_POST['departure_date'])) ? trim($_POST['departure_date']) : null;
    $status       = 1; // Mở bán luôn

    // Xử lý Upload Ảnh
    $bannerPath = 'images/default-tour.jpg';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../images/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $fileName = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $bannerPath = 'images/' . $fileName;
        }
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO tours 
                (Name, CityID, Content, Price, PriceKid, TimeStart, Duration, Status, Slot, Banner, Description) 
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name, $cityID, $content, $price, $priceKid,
            $departureDate, $duration, $status, $slot,
            $bannerPath, $description
        ]);
        echo "<script>alert('✅ Thêm tour mới thành công!'); window.location.href='manage_tours.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('❌ Lỗi: " . addslashes($e->getMessage()) . "');</script>";
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
<form action="" method="POST" enctype="multipart/form-data" id="add-tour-form">

    <div class="form-group">
        <label>Tên Tour Du Lịch: <span style="color:red">*</span></label>
        <input type="text" name="name" required placeholder="VD: Tour Đà Lạt 3 ngày 2 đêm">
    </div>

    <div class="form-group">
        <label>Thành phố / Điểm đến: <span style="color:red">*</span></label>
        <select name="city_id" required style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px;">
            <option value="">-- Chọn thành phố --</option>
            <?php
            $cities = $conn->query("SELECT c.ID, c.Name, a.Name AS AreaName 
                                    FROM cities c LEFT JOIN areas a ON c.AreaID = a.ID 
                                    ORDER BY a.ID, c.Name")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cities as $city): ?>
                <option value="<?= $city['ID'] ?>">
                    <?= htmlspecialchars($city['Name']) ?> (<?= htmlspecialchars($city['AreaName'] ?? '') ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="display:flex; gap:20px;">
        <div class="form-group" style="flex:1;">
            <label>Thời gian: <span style="color:red">*</span></label>
            <input type="text" name="duration" required placeholder="VD: 3 Ngày 2 Đêm">
        </div>
        <div class="form-group" style="flex:1;">
            <label>Số chỗ tối đa: <span style="color:red">*</span></label>
            <input type="number" name="slot" value="40" min="1" required>
        </div>
    </div>

    <div style="display:flex; gap:20px;">
        <div class="form-group" style="flex:1;">
            <label>Giá người lớn (VNĐ): <span style="color:red">*</span></label>
            <!-- min="0" chặn số âm -->
            <input type="number" name="price" required min="0" placeholder="VD: 2500000" id="price">
            <small id="price-err" style="color:red; display:none;">Giá không được là số âm!</small>
        </div>
        <div class="form-group" style="flex:1;">
            <label>Giá trẻ em (VNĐ):</label>
            <input type="number" name="price_kid" min="0" value="0" id="price_kid">
            <small id="price-kid-err" style="color:red; display:none;">Giá không được là số âm!</small>
        </div>
    </div>

    <div class="form-group">
        <label>Ngày xuất phát:</label>
        <!-- min sẽ được set bằng JS = ngày mai -->
        <input type="date" name="departure_date" id="departure_date">
        <small id="date-err" style="color:red; display:none;">Ngày xuất phát phải từ ngày mai trở đi!</small>
    </div>

    <div class="form-group">
        <label>Hình ảnh đại diện: <span style="color:red">*</span></label>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required id="image-input">
        <small id="image-err" style="color:red; display:none;">Chỉ được chọn file ảnh (JPG, PNG, GIF, WEBP)!</small>
    </div>

    <div class="form-group">
        <label>Mô tả chi tiết (có thể chèn ảnh):</label>
        <!-- Summernote sẽ biến textarea này thành editor -->
        <textarea name="description" id="description" style="height:200px;"></textarea>
    </div>

    <button type="submit" class="btn-submit">💾 Lưu Tour</button>
    <a href="manage_tours.php" class="btn-cancel">Hủy</a>
</form>
    </div>
</div>

</div>
</div>
<script>
// 1. Set ngày tối thiểu = ngày mai
const dateInput = document.getElementById('departure_date');
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 1);
dateInput.min = tomorrow.toISOString().split('T')[0];

// 2. Khởi tạo Summernote (editor có chèn ảnh)
$('#description').summernote({
    placeholder: 'Nhập lịch trình hoặc mô tả về tour, có thể chèn ảnh...',
    tabsize: 2,
    height: 300,
    lang: 'vi-VN',
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture']],  // Nút chèn ảnh
        ['view', ['fullscreen', 'codeview', 'help']]
    ]
});

// 3. Validation khi submit
document.getElementById('add-tour-form').addEventListener('submit', function(e) {
    let valid = true;

    // Kiểm tra giá không âm
    const price = parseFloat(document.getElementById('price').value);
    const priceKid = parseFloat(document.getElementById('price_kid').value);
    
    if (isNaN(price) || price < 0) {
        document.getElementById('price-err').style.display = 'block';
        valid = false;
    } else {
        document.getElementById('price-err').style.display = 'none';
    }

    if (!isNaN(priceKid) && priceKid < 0) {
        document.getElementById('price-kid-err').style.display = 'block';
        valid = false;
    } else {
        document.getElementById('price-kid-err').style.display = 'none';
    }

    // Kiểm tra ngày xuất phát >= ngày mai
    const dateVal = document.getElementById('departure_date').value;
    if (dateVal) {
        const selected = new Date(dateVal);
        const minDate = new Date(tomorrow.toISOString().split('T')[0]);
        if (selected < minDate) {
            document.getElementById('date-err').style.display = 'block';
            valid = false;
        } else {
            document.getElementById('date-err').style.display = 'none';
        }
    }

    // Kiểm tra file ảnh
    const imageInput = document.getElementById('image-input');
    if (imageInput.files.length > 0) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(imageInput.files[0].type)) {
            document.getElementById('image-err').style.display = 'block';
            valid = false;
        } else {
            document.getElementById('image-err').style.display = 'none';
        }
    }

    if (!valid) e.preventDefault();
});
</script>
</body></html>