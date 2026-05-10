<?php include 'admin_header.php'; ?>

<?php
// === XỬ LÝ XÓA BÀI VIẾT ===
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Lấy tên ảnh để xóa file vật lý trong thư mục uploads (nếu có)
    $stmt = $conn->prepare("SELECT Thumbnail FROM posts WHERE ID = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if ($post && !empty($post['Thumbnail']) && file_exists("../uploads/" . $post['Thumbnail'])) {
        unlink("../uploads/" . $post['Thumbnail']); 
    }

    // Xóa record trong DB
    $conn->query("DELETE FROM posts WHERE ID = $id");
    echo "<script>alert('🗑️ Đã xóa bài viết thành công!'); window.location.href='manage_posts.php';</script>";
}

// === XỬ LÝ TÌM KIẾM ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    $whereClause = " WHERE Title LIKE :search";
    $queryParams[':search'] = "%$search%";
}

// === THUẬT TOÁN PHÂN TRANG ===
$limit = 10; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(ID) as total FROM posts" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// === LẤY DANH SÁCH BÀI VIẾT ===
$sql = "SELECT * FROM posts $whereClause ORDER BY ID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .post-img { width: 90px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; }
    
    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-edit { background: #ff9800; }
    .btn-edit:hover { background: #f57c00; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
    
    .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .badge-active { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .badge-inactive { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }

    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;}
    .btn-add-new { display: inline-block; background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; }
    
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;}
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; display: flex; align-items: center;}

    .pagination { display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 5px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0; padding: 15px;">
        <i class="fas fa-newspaper"></i> QUẢN LÝ BÀI VIẾT
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <a href="add_post.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Bài Viết</a>
            
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tiêu đề bài viết..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_posts.php" class="search-reset" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(!empty($search)): ?>
            <p style="color: #555; margin-top: -5px; margin-bottom: 15px;">Tìm thấy <strong><?= $totalRows ?></strong> bài viết.</p>
        <?php endif; ?>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Tiêu đề</th>
                    <th>Ngày đăng</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($posts) > 0): ?>
                    <?php foreach ($posts as $p): ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;">#<?= $p['ID'] ?></td>
                        <td>
                            <?php if(!empty($p['Thumbnail'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($p['Thumbnail']) ?>" class="post-img" loading="lazy" onerror="this.src='https://via.placeholder.com/90x60?text=No+Image'">
                            <?php else: ?>
                                <span style="font-size: 12px; color: #999;">Chưa có ảnh</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color: #003366; max-width: 300px; line-height: 1.4;">
                            <?= htmlspecialchars($p['Title']) ?>
                        </td>
                        <td><?= !empty($p['CreatedAt']) ? date('d/m/Y H:i', strtotime($p['CreatedAt'])) : 'N/A' ?></td>
                        <td>
                            <?= (isset($p['Status']) && $p['Status'] == 1) ? '<span class="badge-status badge-active">Hiển thị</span>' : '<span class="badge-status badge-inactive">Đang ẩn</span>' ?>
                        </td>
                        <td>
                            <a href="edit_post.php?id=<?= $p['ID'] ?>" class="btn-action btn-edit" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="?action=delete&id=<?= $p['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này không?');" title="Xóa"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #888;">
                            Không tìm thấy bài viết nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?><a href="?page=<?= $page - 1 ?><?= $searchQuery ?>">&laquo; Trước</a><?php endif; ?>
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $searchQuery ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if($page < $totalPages): ?><a href="?page=<?= $page + 1 ?><?= $searchQuery ?>">Sau &raquo;</a><?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

</div> </div> </body> </html>