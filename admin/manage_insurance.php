<?php include 'admin_header.php'; ?>

<?php
// --- XỬ LÝ XÓA BẢO HIỂM ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM insurance_packages WHERE ID = ?");
    if ($stmt->execute([$id])) {
        echo "<script>alert('🗑️ Đã xóa gói bảo hiểm thành công!'); window.location.href='manage_insurance.php';</script>";
    }
}

// === XỬ LÝ TÌM KIẾM & PHÂN TRANG ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    $whereClause = " WHERE Name LIKE :search";
    $queryParams[':search'] = "%$search%";
}

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số lượng
$countSql = "SELECT COUNT(ID) as total FROM insurance_packages" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// Lấy danh sách
$sql = "SELECT * FROM insurance_packages $whereClause ORDER BY ID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    .badge.active { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;}
    .badge.inactive { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2;}
    .btn-action { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 13px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-edit { background: #ff9800; }
    .btn-edit:hover { background: #f57c00; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
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
        <i class="fas fa-shield-alt"></i> QUẢN LÝ GÓI BẢO HIỂM
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <a href="add_insurance.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Bảo Hiểm Mới</a>
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tên gói bảo hiểm..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_insurance.php" class="search-reset" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên gói bảo hiểm</th>
                    <th>Giá / Người</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($insurances) > 0): ?>
                    <?php foreach ($insurances as $ins): ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;">#<?= $ins['ID'] ?></td>
                        <td style="font-weight: bold; color: #003366;"><?= htmlspecialchars($ins['Name']) ?></td>
                        <td style="color: #c62828; font-weight: bold;"><?= number_format($ins['PricePerPerson'] ?? 0, 0, ',', '.') ?>đ</td>
                        <td>
                            <?php if ($ins['Status'] == 1): ?>
                                <span class="badge active"><i class="fas fa-check"></i> Đang áp dụng</span>
                            <?php else: ?>
                                <span class="badge inactive"><i class="fas fa-eye-slash"></i> Đã ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_insurance.php?id=<?= $ins['ID'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="?action=delete&id=<?= $ins['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('CẢNH BÁO: Bạn có chắc chắn muốn xóa gói bảo hiểm này?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: #888;">Chưa có dữ liệu bảo hiểm.</td></tr>
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