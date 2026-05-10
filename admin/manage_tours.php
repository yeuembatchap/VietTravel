<?php include 'admin_header.php'; ?>

<?php
// --- XỬ LÝ XÓA TOUR ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Xóa tour khỏi database
    $stmt = $conn->prepare("DELETE FROM tours WHERE ID = ?");
    if ($stmt->execute([$id])) {
        echo "<script>alert('🗑️ Đã xóa tour thành công!'); window.location.href='manage_tours.php';</script>";
    } else {
        echo "<script>alert('❌ Có lỗi xảy ra khi xóa!');</script>";
    }
}

// === XỬ LÝ TÌM KIẾM ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    // Tìm theo Tên Tour hoặc ID Tour
    $whereClause = " WHERE Name LIKE :search_name";
    $queryParams[':search_name'] = "%$search%";
    
    // Nếu từ khóa là số, tìm thêm theo ID
    if (is_numeric($search)) {
        $whereClause .= " OR ID = :search_id";
        $queryParams[':search_id'] = $search;
    }
}

// === THUẬT TOÁN PHÂN TRANG (PAGINATION) ===
$limit = 10; // Số tour hiển thị trên 1 trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số tour (có tính cả bộ lọc tìm kiếm)
$countSql = "SELECT COUNT(ID) as total FROM tours" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    if ($key == ':search_id') {
        $totalStmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// === LẤY DANH SÁCH TOUR ===
$sql = "SELECT * FROM tours $whereClause ORDER BY ID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    if ($key == ':search_id') {
        $stmt->bindValue($key, $val, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuỗi query dùng để giữ lại từ khóa khi bấm chuyển trang
$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .tour-img { width: 80px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
    
    .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-edit { background: #ff9800; }
    .btn-edit:hover { background: #f57c00; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
    
    /* Giao diện Thanh công cụ (Nút Thêm + Thanh Tìm kiếm) */
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;}
    .btn-add-new { display: inline-block; background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #1565c0; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s;}
    .search-btn:hover { background: #0d47a1; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; display: flex; align-items: center;}

    /* Phân trang */
    .pagination { display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 5px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="display: flex; justify-content: space-between; align-items: center; background: #e3f2fd; color: #1565c0; padding: 15px;">
        <span><i class="fas fa-plane-departure"></i> QUẢN LÝ TOUR DU LỊCH</span>
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <a href="add_tour.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Tour Mới</a>
            
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm theo tên tour hoặc ID..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_tours.php" class="search-reset" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if(!empty($search)): ?>
            <p style="color: #555; margin-top: -5px; margin-bottom: 15px;">
                Tìm thấy <strong><?= $totalRows ?></strong> kết quả cho từ khóa "<strong><?= htmlspecialchars($search) ?></strong>".
            </p>
        <?php endif; ?>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Hình ảnh</th>
                    <th>Tên Tour</th>
                    <th>Thời gian</th>
                    <th>Ngày xuất phát</th>
                    <th>Giá Tour</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($tours) > 0): ?>
                    <?php foreach ($tours as $t): ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;">#<?= $t['ID'] ?></td>
                        <td>
                            <?php 
                                // Bắt theo cả 2 trường Banner (trong DB) hoặc Image (như code cũ)
                                $img = $t['Banner'] ?? $t['Image'] ?? 'images/default-tour.jpg';
                                $src = (strpos($img, 'http') === 0) ? htmlspecialchars($img) : '../' . htmlspecialchars($img);
                            ?>
                            <img src="<?= $src ?>" class="tour-img" alt="Tour Image" onerror="this.src='../images/default-tour.jpg'">
                        </td>
                        <td style="font-weight: bold; color: #003366; max-width: 250px; line-height: 1.4;">
                            <?= htmlspecialchars($t['Name'] ?? 'Chưa cập nhật tên') ?>
                        </td>
                        <td><?= htmlspecialchars($t['Duration'] ?? 'N/A') ?></td>
                        <td>
                            <?= !empty($t['TimeStart']) ? date('d/m/Y', strtotime($t['TimeStart'])) : '<span style="color:#888;">Chưa đặt</span>' ?>
                        </td>
                        <td style="color: #c62828; font-weight: bold;">
                            <?= number_format($t['Price'] ?? 0, 0, ',', '.') ?>đ
                        </td>
                        <td>
                            <a href="edit_tour.php?id=<?= $t['ID'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="?action=delete&id=<?= $t['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('CẢNH BÁO: Xóa tour này có thể ảnh hưởng đến các đơn hàng cũ. Bạn có chắc chắn muốn xóa?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #888;">
                            <i class="fas fa-box-open" style="font-size: 30px; margin-bottom: 10px; color: #ccc;"></i><br>
                            Không tìm thấy tour nào khớp với yêu cầu.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page - 1 ?><?= $searchQuery ?>">&laquo; Trước</a>
            <?php endif; ?>

            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $searchQuery ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?><?= $searchQuery ?>">Sau &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

</div> </div> </body>
</html>