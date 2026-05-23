<?php include 'admin_header.php'; ?>

<?php
$currentUserId = $_SESSION['user']['ID'] ?? $_SESSION['user']['id'] ?? 0;

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $targetId = intval($_GET['id']);
    if ($targetId == $currentUserId) {
        echo "<script>alert('⚠️ Không thể tự xóa chính mình!'); window.location.href='manage_users.php';</script>";
    } else {
        $conn->query("DELETE FROM users WHERE ID = $targetId AND Role = 'user'");
        echo "<script>alert('🗑️ Đã xóa khách hàng!'); window.location.href='manage_users.php';</script>";
    }
}

// Tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "WHERE u.Role = 'user'";
$queryParams = [];
if (!empty($search)) {
    $whereClause .= " AND (u.FirstName LIKE :s OR u.LastName LIKE :s OR u.Email LIKE :s OR u.Phone LIKE :s OR u.Username LIKE :s)";
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
    SELECT u.* FROM users u
    $whereClause
    ORDER BY u.ID DESC
    LIMIT :limit OFFSET :offset
");
foreach ($queryParams as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }

    .badge-tier { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
    .badge-tier.Bronze   { background: #fbe9e7; color: #bf360c; border: 1px solid #ffccbc; }
    .badge-tier.Silver   { background: #eceff1; color: #455a64; border: 1px solid #cfd8dc; }
    .badge-tier.Gold     { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }
    .badge-tier.Platinum { background: #e8eaf6; color: #283593; border: 1px solid #c5cae9; }

    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 4px; display: inline-block; transition: 0.2s; }
    .btn-history { background: #00897b; } .btn-history:hover { background: #00695c; }
    .btn-edit    { background: #0288d1; } .btn-edit:hover    { background: #01579b; }
    .btn-delete  { background: #f44336; } .btn-delete:hover  { background: #d32f2f; }

    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
    .btn-add-new { background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; }
    .search-form  { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 280px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #1565c0; }
    .search-btn   { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0;">
        <i class="fas fa-users"></i> QUẢN LÝ KHÁCH HÀNG
    </div>
    <div class="box-body">

        <div class="toolbar">
            <a href="add_user.php" class="btn-add-new"><i class="fas fa-user-plus"></i> Thêm Khách Hàng</a>
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tên, email, SĐT..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if (!empty($search)): ?>
                    <a href="manage_users.php" class="search-reset">Xóa bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($search)): ?>
            <p style="color:#555; margin-bottom:15px;">
                Tìm thấy <strong><?= $totalRows ?></strong> kết quả cho "<strong><?= htmlspecialchars($search) ?></strong>".
            </p>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Hạng thành viên</th>
                    <th>Tổng chi tiêu</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $i => $u):
                        $rowId = $u['ID'] ?? 0;
                        $tier  = $u['CustomerTier'] ?? 'Bronze';
                    ?>
                    <tr>
                        <td style="color:#888;"><?= $offset + $i + 1 ?></td>
                        <td>
                            <strong style="color:#003366;">
                                <?= htmlspecialchars(trim(($u['FirstName'] ?? '') . ' ' . ($u['LastName'] ?? ''))) ?>
                            </strong><br>
                            <small style="color:#aaa;">@<?= htmlspecialchars($u['Username'] ?? '') ?></small>
                        </td>
                        <td><?= htmlspecialchars($u['Email'] ?? 'Chưa cập nhật') ?></td>
                        <td><?= htmlspecialchars($u['Phone'] ?? 'Chưa cập nhật') ?></td>
                        <td>
                            <span class="badge-tier <?= htmlspecialchars($tier) ?>">
                                <?php
                                $icons = ['Bronze' => '🥉', 'Silver' => '🥈', 'Gold' => '🥇', 'Platinum' => '💎'];
                                echo ($icons[$tier] ?? '') . ' ' . $tier;
                                ?>
                            </span>
                        </td>
                        <td style="color:#c62828; font-weight:bold;">
                            <?= number_format($u['TotalSpent'] ?? 0, 0, ',', '.') ?>đ
                        </td>
                        <td>
                            <a href="view_user_bookings.php?id=<?= $rowId ?>" class="btn-action btn-history">
                                <i class="fas fa-history"></i> Lịch sử
                            </a>
                            <a href="edit_user.php?id=<?= $rowId ?>" class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="?action=delete&id=<?= $rowId ?>" class="btn-action btn-delete"
                               onclick="return confirm('CẢNH BÁO: Xóa tài khoản khách hàng này?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:30px; color:#888;">
                            <i class="fas fa-user-slash" style="font-size:30px; color:#ccc; margin-bottom:10px;"></i><br>
                            Không tìm thấy khách hàng nào.
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