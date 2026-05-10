<?php include 'admin_header.php'; ?>

<?php
// --- XỬ LÝ XÓA KHÁCH SẠN ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Lấy thông tin ảnh để xóa file vật lý
    $stmt = $conn->prepare("SELECT Image FROM hotels WHERE ID = ?");
    $stmt->execute([$id]);
    $hotel = $stmt->fetch();
    
    if ($hotel && !empty($hotel['Image']) && file_exists("../uploads/hotels/" . $hotel['Image'])) {
        unlink("../uploads/hotels/" . $hotel['Image']);
    }
    
    // Xóa khách sạn khỏi database
    $stmt = $conn->prepare("DELETE FROM hotels WHERE ID = ?");
    if ($stmt->execute([$id])) {
        echo "<script>alert('🗑️ Đã xóa khách sạn thành công!'); window.location.href='manage_hotels.php';</script>";
    } else {
        echo "<script>alert('❌ Có lỗi xảy ra khi xóa!');</script>";
    }
}

// === XỬ LÝ TÌM KIẾM ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    $whereClause = " WHERE h.Name LIKE :search_name";
    $queryParams[':search_name'] = "%$search%";
    
    if (is_numeric($search)) {
        $whereClause .= " OR h.ID = :search_id";
        $queryParams[':search_id'] = $search;
    }
}

// === PHÂN TRANG ===
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số khách sạn
$countSql = "SELECT COUNT(h.ID) as total FROM hotels h" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// Lấy danh sách khách sạn
$sql = "SELECT h.*, c.Name as CityName FROM hotels h LEFT JOIN cities c ON h.CityID = c.ID" . $whereClause . " ORDER BY h.ID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .hotel-img { width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
    
    .stars { color: #ffc107; font-size: 14px; }
    
    .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-edit { background: #ff9800; }
    .btn-edit:hover { background: #f57c00; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
    
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
    .btn-add-new { display: inline-block; background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #1565c0; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s; }
    .search-btn:hover { background: #0d47a1; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; display: flex; align-items: center; }
    
    .pagination { display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 5px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0; padding: 15px;">
        <i class="fas fa-hotel"></i> QUẢN LÝ KHÁCH SẠN
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <a href="add_hotel.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Khách Sạn</a>
            
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm theo tên khách sạn hoặc ID..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_hotels.php" class="search-reset" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
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
                    <th>Tên khách sạn</th>
                    <th>Thành phố</th>
                    <th>Sao</th>
                    <th>Giá mỗi đêm</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($hotels) > 0): ?>
                    <?php foreach ($hotels as $h): ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;">#<?= $h['ID'] ?></td>
                        <td>
                            <?php if(!empty($h['Image'])): ?>
                                <?php 
                                    // Nếu là URL web (bắt đầu với http) thì dùng trực tiếp, không thì thêm đường dẫn uploads
                                    $imgSrc = (strpos($h['Image'], 'http') === 0) ? htmlspecialchars($h['Image']) : '../uploads/hotels/' . htmlspecialchars($h['Image']);
                                ?>
                                <img src="<?= $imgSrc ?>" class="hotel-img" onerror="this.src='../assets/img/default-hotel.jpg'">
                            <?php else: ?>
                                <span style="font-size: 12px; color: #999;">Chưa có ảnh</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color: #003366; max-width: 250px;">
                            <?= htmlspecialchars($h['Name']) ?>
                        </td>
                        <td><?= htmlspecialchars($h['CityName'] ?? 'N/A') ?></td>
                        <td>
                            <span class="stars">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <?= $i <= intval($h['Stars']) ? '★' : '☆' ?>
                                <?php endfor; ?>
                            </span>
                        </td>
                        <td style="color: #c62828; font-weight: bold;">
                            <?= number_format($h['Price'] ?? 0, 0, ',', '.') ?>đ
                        </td>
                        <td>
                            <a href="edit_hotel.php?id=<?= $h['ID'] ?>" class="btn-action btn-edit" title="Sửa"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="?action=delete&id=<?= $h['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa khách sạn này không?');" title="Xóa"><i class="fas fa-trash"></i> Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #888;">
                            Không tìm thấy khách sạn nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php if($page > 1): ?>
                    <a href="?page=1<?= $searchQuery ?>">« Đầu</a>
                    <a href="?page=<?= $page - 1 ?><?= $searchQuery ?>">‹ Trước</a>
                <?php endif; ?>

                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if($start > 1) echo '<a href="#">...</a>';
                
                for($i = $start; $i <= $end; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo "<a href='?page=$i{$searchQuery}' class='{$active}'>$i</a>";
                }
                
                if($end < $totalPages) echo '<a href="#">...</a>';
                ?>

                <?php if($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $searchQuery ?>">Sau ›</a>
                    <a href="?page=<?= $totalPages ?><?= $searchQuery ?>">Cuối »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

</div>
</div>
</body>
</html>
