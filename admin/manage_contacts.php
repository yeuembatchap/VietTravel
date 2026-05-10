<?php include 'admin_header.php'; ?>

<?php
// === XỬ LÝ XÓA ===
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM contacts WHERE ID = $id");
        echo "<script>alert('🗑️ Đã xóa tin nhắn liên hệ!'); window.location.href='manage_contacts.php';</script>";
    }
}

// === XỬ LÝ TÌM KIẾM ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    $whereClause = " WHERE Name LIKE :search OR Email LIKE :search OR Phone LIKE :search OR Subject LIKE :search";
    $queryParams[':search'] = "%$search%";
}

// === PHÂN TRANG ===
$limit = 10; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(ID) as total FROM contacts" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// === LẤY DANH SÁCH LIÊN HỆ ===
$sql = "SELECT * FROM contacts $whereClause ORDER BY ID DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: top; }
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-reply { background: #2e7d32; }
    .btn-reply:hover { background: #1b5e20; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
    
    .toolbar { display: flex; justify-content: flex-end; align-items: center; margin-bottom: 15px; }
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; display: flex; align-items: center;}
    
    .content-box-text { max-height: 80px; overflow-y: auto; color: #555; font-size: 13px; line-height: 1.5; padding-right: 5px;}
    .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0; padding: 15px;">
        <i class="fas fa-envelope"></i> QUẢN LÝ LIÊN HỆ
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tên, email, SĐT..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_contacts.php" class="search-reset" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="20%">Khách hàng</th>
                    <th width="20%">Chủ đề</th>
                    <th width="35%">Nội dung</th>
                    <th width="20%">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($contacts) > 0): ?>
                    <?php foreach ($contacts as $c): ?>
                    <tr>
                        <td style="color: #555; font-weight: bold;">#<?= $c['ID'] ?></td>
                        <td>
                            <strong style="color: #003366;"><?= htmlspecialchars($c['Name']) ?></strong><br>
                            <i class="fas fa-envelope" style="color: #888; font-size: 11px;"></i> <?= htmlspecialchars($c['Email']) ?><br>
                            <i class="fas fa-phone" style="color: #888; font-size: 11px;"></i> <?= htmlspecialchars($c['Phone']) ?>
                        </td>
                        <td style="font-weight: bold; color: #333;">
                            <?= htmlspecialchars($c['Subject'] ?? 'Không có chủ đề') ?>
                        </td>
                        <td>
                            <div class="content-box-text">
                                <?= nl2br(htmlspecialchars($c['Content'] ?? '')) ?>
                            </div>
                        </td>
                        <td>
                            <a href="reply_contact.php?id=<?= $c['ID'] ?>" class="btn-action btn-reply"><i class="fas fa-reply"></i> Phản hồi</a>
                            <a href="?action=delete&id=<?= $c['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('Xóa tin nhắn này?');"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">Chưa có liên hệ nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?><?= $searchQuery ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div>
</div>
</div> </div> </body> </html>