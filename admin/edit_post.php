<?php include 'admin_header.php'; ?>

<?php
if (!isset($_GET['id'])) {
    echo "<script>window.location.href='manage_posts.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM posts WHERE ID = ?");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    echo "<script>alert('Không tìm thấy bài viết!'); window.location.href='manage_posts.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $status = intval($_POST['status']);
    $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9\-]/', '', $title)));
    $image = $post['Thumbnail']; // Giữ nguyên ảnh cũ mặc định

    // Nếu có upload ảnh mới
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $image = time() . '_' . $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
            
            // Xóa ảnh cũ đi cho nhẹ server
            if (!empty($post['Thumbnail']) && file_exists("../uploads/" . $post['Thumbnail'])) {
                unlink("../uploads/" . $post['Thumbnail']);
            }
        }
    }

    try {
        $updateStmt = $conn->prepare("UPDATE posts SET Title = ?, Slug = ?, Content = ?, Thumbnail = ?, Status = ? WHERE ID = ?");
        if ($updateStmt->execute([$title, $slug, $content, $image, $status, $id])) {
            echo "<script>alert('✅ Cập nhật bài viết thành công!'); window.location.href='manage_posts.php';</script>";
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
    .btn-submit { background: #ff9800; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-submit:hover { background: #f57c00; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
    .current-img { max-width: 150px; margin-top: 10px; border-radius: 4px; border: 1px solid #ddd; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #fff3e0; color: #e65100;"><i class="fas fa-edit"></i> SỬA BÀI VIẾT: #<?= $post['ID'] ?></div>
    <div class="box-body">
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Tiêu đề bài viết <span style="color:red;">*</span></label>
                <input type="text" name="title" required value="<?= htmlspecialchars($post['Title']) ?>">
            </div>
            
            <div class="form-group">
                <label>Ảnh đại diện (Bỏ trống nếu không muốn đổi ảnh)</label>
                <input type="file" name="image" accept="image/*">
                <?php if(!empty($post['Image'])): ?>
                    <div>
                        <img src="../uploads/<?= htmlspecialchars($post['Image']) ?>" class="current-img" alt="Current Image">
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Trạng thái</label>
                <select name="status">
                    <option value="1" <?= (isset($post['Status']) && $post['Status'] == 1) ? 'selected' : '' ?>>Hiển thị</option>
                    <option value="0" <?= (isset($post['Status']) && $post['Status'] == 0) ? 'selected' : '' ?>>Đang ẩn</option>
                </select>
            </div>

            <div class="form-group">
                <label>Nội dung <span style="color:red;">*</span></label>
                <textarea name="content" rows="15" required><?= htmlspecialchars($post['Content']) ?></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Cập Nhật</button>
                <a href="manage_posts.php" class="btn-cancel">Hủy</a>
            </div>
        </form>
    </div>
</div>
</div> </div> </body> </html>