<?php include 'admin_header.php'; ?>

<?php
// XỬ LÝ XÓA XE
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Lấy tên ảnh để xóa file vật lý (Đã sửa tên bảng thành vehicles)
    $stmt = $conn->prepare("SELECT Image FROM vehicles WHERE ID = ?");
    $stmt->execute([$id]);
    $car = $stmt->fetch();
    if ($car && !empty($car['Image']) && file_exists("../uploads/" . $car['Image'])) {
        unlink("../uploads/" . $car['Image']); 
    }

    $conn->query("DELETE FROM vehicles WHERE ID = $id");
    echo "<script>alert('🗑️ Đã xóa xe thành công!'); window.location.href='manage_cars.php';</script>";
}

// === XỬ LÝ TÌM KIẾM ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    // Tìm theo Tên Xe hoặc Tên Thành Phố
    $whereClause = " WHERE v.Name LIKE :search OR c.Name LIKE :search";
    $queryParams[':search'] = "%$search%";
}

// === THUẬT TOÁN PHÂN TRANG (PAGINATION) ===
$limit = 10; // Chỉ hiển thị 10 chiếc xe trên 1 trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số xe để tính ra số trang (có kèm bộ lọc tìm kiếm)
$countSql = "SELECT COUNT(v.ID) as total FROM vehicles v LEFT JOIN cities c ON v.CityID = c.ID" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// LẤY DANH SÁCH XE (Có LIMIT và OFFSET để chống lag)
$sql = "SELECT v.*, c.Name as CityName 
        FROM vehicles v 
        LEFT JOIN cities c ON v.CityID = c.ID 
        $whereClause
        ORDER BY v.ID DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
// PDO cần bindValue để hiểu đây là số nguyên (INT)
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuỗi query dùng để giữ lại từ khóa khi bấm chuyển trang
$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-edit { background: #0288d1; }
    .btn-edit:hover { background: #0277bd; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
    
    .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
    .badge-active { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
    .badge-inactive { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    
    /* Tối ưu hiển thị ảnh để không vỡ khung */
    .car-img { width: 80px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; background: #f9f9f9; }

    /* Thanh công cụ và Tìm kiếm */
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;}
    .btn-add-new { display: inline-block; background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #1565c0; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s;}
    .search-btn:hover { background: #0d47a1; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; display: flex; align-items: center;}

    /* CSS Cho phân trang */
    .pagination { display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 5px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0; padding: 15px;">
        <i class="fas fa-car"></i> QUẢN LÝ XE CHO THUÊ
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <a href="add_car.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Xe Mới</a>
            
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tên xe hoặc khu vực..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_cars.php" class="search-reset" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
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
                    <th>Tên Xe</th>
                    <th>Khu Vực</th>
                    <th>Số Chỗ</th>
                    <th>Giá / Ngày</th>
                    <th>Trạng Thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($cars) > 0): ?>
                    <?php foreach ($cars as $c): ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;">#<?= $c['ID'] ?></td>
                        <td>
                            <?php if(!empty($c['Image'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($c['Image']) ?>" class="car-img" loading="lazy" alt="<?= htmlspecialchars($c['Name']) ?>" onerror="this.src='https://via.placeholder.com/80x50?text=No+Image'">
                            <?php else: ?>
                                <span style="font-size: 12px; color: #999;">Chưa có ảnh</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold; color: #003366;"><?= htmlspecialchars($c['Name']) ?></td>
                        <td><?= htmlspecialchars($c['CityName'] ?? 'Chưa chọn') ?></td>
                        <td><?= $c['Seats'] ?> chỗ</td>
                        <td style="color: #c62828; font-weight: bold;"><?= number_format($c['PricePerDay'], 0, ',', '.') ?>đ</td>
                        <td>
                            <?= $c['Status'] ? '<span class="badge-status badge-active">Hoạt động</span>' : '<span class="badge-status badge-inactive">Tạm ngưng</span>' ?>
                        </td>
                        <td>
                            <a href="edit_car.php?id=<?= $c['ID'] ?>" class="btn-action btn-edit" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="?action=delete&id=<?= $c['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('CẢNH BÁO: Xóa xe này?');" title="Xóa"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px; color: #888;">
                            <i class="fas fa-car-crash" style="font-size: 30px; margin-bottom: 10px; color: #ccc;"></i><br>
                            Không tìm thấy xe nào khớp với yêu cầu.
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

</div></div></body></html>