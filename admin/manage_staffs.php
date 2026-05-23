<?php include 'admin_header.php'; ?>

<?php
// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM users WHERE ID = $id AND Role = 'staff'");
    echo "<script>alert('✅ Đã xóa nhân viên!'); window.location.href='manage_staffs.php';</script>";
}

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "WHERE u.Role = 'staff'";
$queryParams = [];
if (!empty($search)) {
    $whereClause .= " AND (u.FirstName LIKE :s OR u.LastName LIKE :s OR u.Email LIKE :s OR u.Phone LIKE :s)";
    $queryParams[':s'] = "%$search%";
}

// Phân trang
$limit  = 10;
$page   = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM users u $whereClause");
foreach ($queryParams as $k => $v) $countStmt->bindValue($k, $v);
$countStmt->execute();
$totalRows  = $countStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("
    SELECT u.*, a.Name AS AreaName 
    FROM users u
    LEFT JOIN areas a ON u.AreaID = a.ID
    $whereClause
    ORDER BY u.ID DESC
    LIMIT :limit OFFSET :offset
");
foreach ($queryParams as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; font-size: 14px; vertical-align: middle; }
    .admin-table th { background: #fcfcfc; color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; }
    .admin-table tbody tr:hover { background: #fafafa; }
    .badge-area { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    .badge-active { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
    .badge-inactive { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 4px; display: inline-block; transition: 0.2s; }
    .btn-edit   { background: #f57c00; } .btn-edit:hover { background: #e65100; }
    .btn-delete { background: #f44336; } .btn-delete:hover { background: #d32f2f; }
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
    .btn-add-new { background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; }
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 280px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #1565c0; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e8eaf6; color: #283593;">
        <i class="fas fa-user-tie"></i> QUẢN LÝ NHÂN VIÊN
    </div>
    <div class="box-body">

        <div class="toolbar">
            <a href="add_staff.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Nhân Viên</a>
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tên, email, SĐT..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_staffs.php" class="search-reset">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($search)): ?>
            <p style="color:#555; margin-bottom:15px;">Tìm thấy <strong><?= $totalRows ?></strong> kết quả cho "<strong><?= htmlspecialchars($search) ?></strong>".</p>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Khu vực làm việc</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($staffs) > 0): ?>
                    <?php foreach ($staffs as $i => $s): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars(trim($s['FirstName'] . ' ' . $s['LastName'])) ?></strong><br>
                            <small style="color:#888;">@<?= htmlspecialchars($s['Username']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($s['Email'] ?? 'Chưa cập nhật') ?></td>
                        <td><?= htmlspecialchars($s['Phone'] ?? 'Chưa cập nhật') ?></td>
                        <td>
                            <?php if (!empty($s['AreaName'])): ?>
                                <span class="badge-area"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($s['AreaName']) ?></span>
                            <?php else: ?>
                                <span style="color:#aaa;">Chưa phân công</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['Status'] == 'active'): ?>
                                <span class="badge-active"><i class="fas fa-check-circle"></i> Đang làm việc</span>
                            <?php else: ?>
                                <span class="badge-inactive"><i class="fas fa-times-circle"></i> Ngừng làm việc</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_staff.php?id=<?= $s['ID'] ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="?action=delete&id=<?= $s['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('Xóa nhân viên này?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#888;">
                            <i class="fas fa-user-slash" style="font-size:30px; color:#ccc; margin-bottom:10px;"></i><br>
                            Chưa có nhân viên nào.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?><?= $searchQuery ?>">&laquo; Trước</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $searchQuery ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?><?= $searchQuery ?>">Sau &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>
</div></div></body></html>