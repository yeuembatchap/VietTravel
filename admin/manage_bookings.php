<?php include 'admin_header.php'; ?>

<?php
// --- XỬ LÝ CÁC NÚT BẤM (DUYỆT / XÓA ĐƠN HÀNG) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] == 'approve') {
        $conn->query("UPDATE bookings SET PaymentStatus = 'paid' WHERE ID = $id");
        echo "<script>alert('✅ Đã duyệt đơn hàng thành công!'); window.location.href='manage_bookings.php';</script>";
    } elseif ($_GET['action'] == 'delete') {
        $conn->query("DELETE FROM bookings WHERE ID = $id");
        echo "<script>alert('🗑️ Đã xóa đơn hàng!'); window.location.href='manage_bookings.php';</script>";
    }
}

// === XỬ LÝ TÌM KIẾM ===
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = "";
$queryParams = [];

if (!empty($search)) {
    // Tìm theo Mã Booking, Tên khách tự nhập, Tên user, SĐT tự nhập, SĐT user
    $whereClause = " WHERE b.BookingCode LIKE :search 
                     OR b.CustomerName LIKE :search 
                     OR CONCAT(u.FirstName, ' ', u.LastName) LIKE :search 
                     OR b.CustomerPhone LIKE :search 
                     OR u.Phone LIKE :search";
    $queryParams[':search'] = "%$search%";
}

// === THUẬT TOÁN PHÂN TRANG (PAGINATION) ===
$limit = 10; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số đơn hàng (có tính cả bộ lọc tìm kiếm)
$countSql = "SELECT COUNT(b.ID) as total FROM bookings b LEFT JOIN users u ON b.UserID = u.ID" . $whereClause;
$totalStmt = $conn->prepare($countSql);
foreach ($queryParams as $key => $val) {
    $totalStmt->bindValue($key, $val, PDO::PARAM_STR);
}
$totalStmt->execute();
$totalRows = $totalStmt->fetch()['total'];
$totalPages = ceil($totalRows / $limit);

// === LẤY DANH SÁCH ĐƠN HÀNG ===
$sql = "SELECT b.*, u.FirstName, u.LastName, u.Phone as UserPhone 
        FROM bookings b 
        LEFT JOIN users u ON b.UserID = u.ID 
        $whereClause 
        ORDER BY b.ID DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
foreach ($queryParams as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Chuỗi query dùng để giữ lại từ khóa khi bấm chuyển trang
$searchQuery = !empty($search) ? "&search=" . urlencode($search) : "";
?>

<style>
    .admin-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .admin-table th, .admin-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; text-align: left; font-size: 14px; vertical-align: middle;}
    .admin-table th { color: #555; font-weight: bold; text-transform: uppercase; font-size: 13px; background-color: #fcfcfc; }
    .admin-table tbody tr:hover { background-color: #fafafa; }
    
    .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
    .badge.paid { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;}
    .badge.pending { background: #fff3e0; color: #e65100; border: 1px solid #ffe0b2;}
    .badge.failed { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2;}
    
    .btn-action { padding: 6px 10px; border: none; border-radius: 4px; cursor: pointer; color: white; text-decoration: none; font-size: 12px; margin-right: 5px; display: inline-block; transition: 0.2s; }
    .btn-approve { background: #0288d1; }
    .btn-approve:hover { background: #0277bd; }
    .btn-delete { background: #f44336; }
    .btn-delete:hover { background: #d32f2f; }
    
    /* Giao diện Thanh công cụ (Nút Thêm + Thanh Tìm kiếm) */
    .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;}
    .btn-add-new { background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: 0.3s; display: inline-block;}
    .btn-add-new:hover { background: #1b5e20; }
    
    .search-form { display: flex; gap: 5px; }
    .search-input { padding: 10px 15px; width: 300px; border: 1px solid #ccc; border-radius: 5px; font-size: 14px; outline: none; }
    .search-input:focus { border-color: #1565c0; }
    .search-btn { padding: 10px 15px; background: #1565c0; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s;}
    .search-btn:hover { background: #0d47a1; }
    .search-reset { padding: 10px 15px; background: #9e9e9e; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px;}

    .pagination { display: flex; justify-content: center; align-items: center; margin-top: 20px; gap: 5px; }
    .pagination a { padding: 8px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; font-size: 14px; transition: 0.2s; }
    .pagination a:hover { background: #f0f0f0; }
    .pagination a.active { background: #1565c0; color: white; border-color: #1565c0; }
</style>

<div class="content-box">
    <div class="box-header" style="background: #e3f2fd; color: #1565c0;">
        <i class="fas fa-file-invoice-dollar"></i> QUẢN LÝ ĐẶT TOUR (BOOKING TOUR)
    </div>
    <div class="box-body">
        
        <div class="toolbar">
            <a href="add_booking.php" class="btn-add-new"><i class="fas fa-plus-circle"></i> Thêm Đơn Mới</a>
            
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Tìm tên khách, SĐT, mã đơn..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Tìm</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_bookings.php" class="search-reset">Xóa bộ lọc</a>
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
                    <th>Mã Booking</th>
                    <th>Khách hàng</th>
                    <th>Điện thoại</th>
                    <th>Ngày đi</th>
                    <th>Tổng tiền</th>
                    <th>Thanh toán</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($bookings) > 0): ?>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="font-weight: bold; color: #003366;"><?= htmlspecialchars($b['BookingCode'] ?? 'N/A') ?></td>
                        <td>
                            <?php 
                                $name = !empty($b['CustomerName']) ? $b['CustomerName'] : trim(($b['FirstName'] ?? '') . ' ' . ($b['LastName'] ?? ''));
                                echo htmlspecialchars($name ?: 'Khách vãng lai');
                            ?>
                        </td>
                        <td style="font-weight: bold;">
                            <?php 
                                $phone = !empty($b['CustomerPhone']) ? $b['CustomerPhone'] : ($b['UserPhone'] ?? '');
                                echo htmlspecialchars($phone ?: 'Chưa cập nhật');
                            ?>
                        </td>
                        <td>
                            <?= !empty($b['TimeStartStore']) ? date('d/m/Y', strtotime($b['TimeStartStore'])) : 'N/A' ?>
                        </td>
                        <td style="color: #c62828; font-weight: bold;">
                            <?= number_format($b['TotalPrice'] ?? 0, 0, ',', '.') ?>đ
                        </td>
                        <td>
                            <?php
                            $status = $b['PaymentStatus'] ?? '';
                            if ($status == 'paid') echo '<span class="badge paid"><i class="fas fa-check-circle"></i> Đã thanh toán</span>';
                            elseif ($status == 'failed') echo '<span class="badge failed"><i class="fas fa-times-circle"></i> Thất bại</span>';
                            else echo '<span class="badge pending"><i class="fas fa-clock"></i> Chờ xử lý</span>';
                            ?>
                        </td>
                        
<td>
    <!-- Thêm nút Sửa vào đây -->
    <a href="edit_booking.php?id=<?= $b['ID'] ?>" class="btn-action" style="background:#f57c00;">
        <i class="fas fa-edit"></i> Sửa
    </a>
    
    <?php if ($status != 'paid'): ?>
        <a href="?action=approve&id=<?= $b['ID'] ?>" class="btn-action btn-approve" onclick="return confirm('Xác nhận khách đã thanh toán đơn này?');"><i class="fas fa-check"></i> Duyệt</a>
    <?php endif; ?>
    <a href="?action=delete&id=<?= $b['ID'] ?>" class="btn-action btn-delete" onclick="return confirm('CẢNH BÁO: Xóa vĩnh viễn đơn hàng này?');"><i class="fas fa-trash"></i></a>
</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #888;">
                            <i class="fas fa-box-open" style="font-size: 30px; margin-bottom: 10px; color: #ccc;"></i><br>
                            Không tìm thấy đơn hàng nào khớp với yêu cầu.
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