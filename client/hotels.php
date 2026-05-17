<?php
// 1. Nhúng file kết nối database và header
require_once '../config/db.php';
$disableHeaderBanner = true;
include 'header.php';

// 2. Kiểm tra kết nối
if (!$conn) {
    die("Kết nối database thất bại!");
}

// 3. Lấy danh sách Tour và phân loại theo 3 miền
// Dựa vào CityID hiện tại trong DB:
// - Miền Bắc: 1 (Hà Nội), 2 (Sapa), 8 (Hạ Long), 9 (Hà Giang)
// - Miền Trung: 3 (Đà Nẵng), 4 (Nha Trang), 7 (Đà Lạt/Bảo Lộc), 10 (Huế)
// - Miền Nam: 6 (Phú Quốc), 11 (Cần Thơ)
$sql_tours = "SELECT ID, Name, 
                CASE 
                    WHEN CityID IN (1, 2, 8, 9) THEN 'Miền Bắc'
                    WHEN CityID IN (3, 4, 7, 10) THEN 'Miền Trung'
                    WHEN CityID IN (6, 11) THEN 'Miền Nam'
                    ELSE 'Khác'
                END as Region
              FROM tours 
              ORDER BY FIELD(Region, 'Miền Bắc', 'Miền Trung', 'Miền Nam', 'Khác'), Name ASC";

$result_tours = $conn->query($sql_tours);

// Khởi tạo mảng để gom nhóm tour theo miền
$tours_by_region = [
    'Miền Bắc' => [],
    'Miền Trung' => [],
    'Miền Nam' => [],
    'Khác' => []
];

if ($result_tours && $result_tours->rowCount() > 0) {
    while ($t = $result_tours->fetch(PDO::FETCH_ASSOC)) {
        $tours_by_region[$t['Region']][] = $t;
    }
}

// 4. Xử lý Logic Lọc và Truy vấn Khách sạn
$filter_tour_id = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;

if ($filter_tour_id > 0) {
    // Nếu có chọn Tour -> Lọc khách sạn theo Tour
    $sql = "SELECT h.*, c.Name as CityName 
            FROM hotels h 
            LEFT JOIN cities c ON h.CityID = c.ID 
            INNER JOIN tour_hotel th ON h.ID = th.HotelID
            WHERE th.TourID = $filter_tour_id
            ORDER BY h.Stars DESC";
} else {
    // Nếu không chọn -> Lấy toàn bộ
    $sql = "SELECT h.*, c.Name as CityName 
            FROM hotels h 
            LEFT JOIN cities c ON h.CityID = c.ID 
            ORDER BY h.Stars DESC";
}

$stmt = $conn->query($sql);

$hotels = [];
if ($stmt && $stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hotels[] = $row;
    }
}
?>

<link rel="stylesheet" href="../css/style.css">

<div class="hotel-section">
    <div class="container">
        <h2 class="section-title">Khách Sạn <span>Nổi Bật</span></h2>
        
<div class="filter-container">
    <form method="GET" action="hotels.php" class="filter-form">
        <select name="tour_id" onchange="this.form.submit()">
            <option value="">-- Tất cả khách sạn --</option>
            
            <?php 
            foreach ($tours_by_region as $region => $tours) {
                if (!empty($tours)) {
                    echo "<optgroup label='--- {$region} ---'>";
                    foreach ($tours as $t) {
                        $selected = ($filter_tour_id == $t['ID']) ? 'selected' : '';
                        echo "<option value='{$t['ID']}' {$selected}>".htmlspecialchars($t['Name'])."</option>";
                    }
                    echo "</optgroup>";
                }
            }
            ?>
        </select>
        
        <noscript>
            <button type="submit"><i class="fa-solid fa-filter"></i> Lọc</button>
        </noscript>
    </form>
</div>

        <div class="hotel-grid">
            <?php 
            // Hiển thị danh sách khách sạn
            if (!empty($hotels)): 
                foreach ($hotels as $row): 
            ?>
                <div class="hotel-card">
                    <div class="hotel-image">
                        <?php 
                            $imgSrc = 'assets/img/default-hotel.jpg';
                            
                            if (!empty($row['Image'])) {
                                if (strpos($row['Image'], 'http') === 0) {
                                    $imgSrc = $row['Image'];
                                } else {
                                    $imgSrc = 'uploads/hotels/' . $row['Image'];
                                }
                            }
                        ?>
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($row['Name']); ?>">
                        
                        <?php if(!empty($row['CityName'])): ?>
                            <div class="hotel-tag">
                                <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($row['CityName']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hotel-content">
                        <div class="hotel-stars">
                            <?php 
                            $stars = intval($row['Stars']);
                            for($i=1; $i<=5; $i++) {
                                echo $i <= $stars ? '<i class="fa-solid fa-star"></i> ' : '<i class="fa-regular fa-star"></i> ';
                            }
                            ?>
                        </div>
                        
                        <h3 class="hotel-name" title="<?php echo htmlspecialchars($row['Name']); ?>">
                            <?php echo htmlspecialchars($row['Name']); ?>
                        </h3>
                        
                        <p class="hotel-desc">
                            <?php echo mb_strimwidth(strip_tags($row['Description']), 0, 95, "..."); ?>
                        </p>
                        
                        <div class="hotel-footer">
                            <div class="hotel-price">
                                <span class="label">Giá mỗi đêm từ:</span>
                                <span class="amount"><?php echo number_format($row['Price'], 0, ',', '.'); ?>đ</span>
                            </div>
                            <a href="hotel_detail.php?id=<?php echo $row['ID']; ?>" class="btn-view">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach; 
            else:
            ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 50px 0; color: #666;">
                    <i class="fa-solid fa-hotel" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                    <p style="font-size: 1.1rem;">Không tìm thấy khách sạn nào phù hợp.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>