<?php include 'admin_header.php'; ?>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $status = intval($_POST['status']);
    $createdAt = date('Y-m-d H:i:s');
    $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $title)));
    $image = '';

    // Xử lý upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
        }
    }

    try {
        $stmt = $conn->prepare("INSERT INTO posts (Title, Slug, Content, Thumbnail, Status, CreatedAt, Views) VALUES (?, ?, ?, ?, ?, ?, 0)");
        if ($stmt->execute([$title, $slug, $content, $image, $status, $createdAt])) {
            echo "<script>alert('✅ Thêm bài viết thành công!'); window.location.href='manage_posts.php';</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Lỗi: " . $e->getMessage() . "');</script>";
    }
}
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;}
    .form-group input[type="file"] { padding: 5px 0; }
    .btn-submit { background: #2e7d32; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0;"><i class="fas fa-edit"></i> THÊM BÀI VIẾT MỚI</div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tiêu đề bài viết <span style="color:red;">*</span></label>
                <input type="text" name="title" required placeholder="Nhập tiêu đề...">
            </div>
            
            <div class="form-group">
                <label>Ảnh đại diện</label>
                <input type="file" name="image" accept="image/*">
            </div>

            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1">Hiển thị (Đăng ngay)</option>
                    <option value="0">Ẩn (Lưu nháp)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Nội dung <span style="color:red;">*</span></label>
                <textarea name="content" rows="15" required placeholder="Nhập nội dung bài viết..."></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Đăng Bài</button>
                <a href="manage_posts.php" class="btn-cancel">Hủy</a>
            </div>
        </form>
    </div>
</div>
</div> </div> </body> </html>