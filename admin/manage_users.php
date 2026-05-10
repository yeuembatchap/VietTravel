<?php include 'admin_header.php'; ?>

<?php
$currentUserId = $_SESSION['user']['ID'] ?? $_SESSION['user']['id'] ?? $_SESSION['user']['UserID'] ?? 0;

if (isset($_GET['action']) && isset($_GET['id'])) {
    $targetId = intval($_GET['id']);
    
    if ($targetId == $currentUserId && $currentUserId != 0 && $_GET['action'] != 'edit') {
        echo "<script>alert('⚠️ Bạn không thể tự thay đổi quyền hoặc xóa chính mình tại đây!'); window.location.href='manage_users.php';</script>";
    } else {
        if ($_GET['action'] == 'delete') {
            $conn->query("DELETE FROM users WHERE ID = $targetId OR id = $targetId");
            echo "<script>alert('🗑️ Đã xóa người dùng!'); window.location.href='manage_users.php';</script>";
        } elseif ($_GET['action'] == 'make_admin') {
            $conn->query("UPDATE users SET Role = 'admin' WHERE ID = $targetId OR id = $targetId");
            echo "<script>alert('👑 Đã thăng cấp thành Quản trị viên!'); window.location.href='manage_users.php';</script>";
        } elseif ($_GET['action'] == 'remove_admin') {
            $conn->query("UPDATE users SET Role = 'user' WHERE ID = $targetId OR id = $targetId");
            echo "<script>alert('⬇️ Đã hạ cấp xuống Khách hàng thường!'); window.location.href='manage_users.php';</script>";
        }
    }
}

$stmt = $conn->query("SELECT * FROM users ORDER BY Role ASC, ID DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-flex; align-items: center; gap: 5px; }
    .badge.admin { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    .badge.user { background: #e3f2fd; color: #1565c0; border: 1px solid #bbdefb; }
    
    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-edit { background: #0288d1; }
    .btn-edit:hover { background: #01579b; }
    .btn-make-admin { background: #9c27b0; }
    .btn-remove-admin { background: #ff9800; }
    .btn-delete { background: #f44336; }
    
    .btn-add-new { display: inline-block; background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 15px; transition: 0.3s; }
    .btn-add-new:hover { background: #1b5e20; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0; text-transform: uppercase;">
        <i class="fas fa-users-cog"></i> QUẢN LÝ KHÁCH HÀNG & TÀI KHOẢN
    </div>
    <div class="box-body">
        
        <a href="add_user.php" class="btn-add-new"><i class="fas fa-user-plus"></i> Thêm Người Dùng Mới</a>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ và Tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Quyền Hạn</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($users) > 0): ?>
                    <?php foreach ($users as $u): 
                        $rowId = $u['ID'] ?? $u['id'] ?? 0;
                    ?>
                    <tr>
                        <td style="font-weight: bold; color: #555;">#<?= $rowId ?></td>
                        <td style="font-weight: bold; color: #003366;">
                            <?= htmlspecialchars(($u['FirstName'] ?? '') . ' ' . ($u['LastName'] ?? '')) ?>
                        </td>
                        <td><?= htmlspecialchars($u['Email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($u['Phone'] ?? '') ?></td>
                        <td>
                            <?php if(isset($u['Role']) && $u['Role'] == 'admin'): ?>
                                <span class="badge admin"><i class="fas fa-crown"></i> Admin</span>
                            <?php else: ?>
                                <span class="badge user"><i class="fas fa-user"></i> Khách hàng</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view_user_bookings.php?id=<?= $rowId ?>" class="btn-action" style="background: #00897b;"><i class="fas fa-shopping-cart"></i> Lịch sử Đặt</a>
                            <a href="edit_user.php?id=<?= $rowId ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Sửa</a>

                            <?php if ($rowId != $currentUserId && $rowId != 0): ?>
                                <?php if (isset($u['Role']) && $u['Role'] == 'admin'): ?>
                                    <a href="?action=remove_admin&id=<?= $rowId ?>" class="btn-action btn-remove-admin" onclick="return confirm('Hạ cấp người này?');">Hạ cấp</a>
                                <?php else: ?>
                                    <a href="?action=make_admin&id=<?= $rowId ?>" class="btn-action btn-make-admin" onclick="return confirm('Lên Admin?');">Lên Admin</a>
                                <?php endif; ?>

                                <a href="?action=delete&id=<?= $rowId ?>" class="btn-action btn-delete" onclick="return confirm('CẢNH BÁO: Xóa tài khoản này?');"><i class="fas fa-trash"></i> Xóa</a>
                            <?php else: ?>
                                <span style="color: #4caf50; font-size: 13px; font-weight: bold; margin-left: 5px;">(Bạn)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
    </div>
</div>

</div></div></body></html>