<?php
// 1. Nhúng kết nối và header
require_once '../config/db.php';
include 'header.php';

// 2. Lấy ID từ URL và bảo mật
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "<div class='container' style='padding:100px 20px; text-align:center;'><h2>Không tìm thấy khách sạn!</h2><a href='hotels.php'>Quay lại danh sách</a></div>";
    include 'footer.php';
    exit;
}

// 3. Truy vấn thông tin chi tiết khách sạn
$sql = "SELECT h.*, c.Name as CityName 
        FROM hotels h 
        LEFT JOIN cities c ON h.CityID = c.ID 
        WHERE h.ID = $id";
$result = $conn->query($sql);

$hotel = $result ? $result->fetch(PDO::FETCH_ASSOC) : false;

if (!$hotel) {
    echo "<div class='container' style='padding:100px 20px; text-align:center;'><h2>Khách sạn này không tồn tại trên hệ thống!</h2></div>";
    include 'footer.php';
    exit;
}

// 4. Truy vấn các Tour có sử dụng khách sạn này (Dựa trên bảng tour_hotel)
$sql_tours = "SELECT t.ID, t.Name, t.Price 
              FROM tours t 
              INNER JOIN tour_hotel th ON t.ID = th.TourID 
              WHERE th.HotelID = $id AND t.Status = 1";
$result_tours = $conn->query($sql_tours);
$tours = $result_tours ? $result_tours->fetchAll(PDO::FETCH_ASSOC) : [];

// 5. Xử lý logic ảnh (Link web hoặc file uploads)
$imgSrc = 'assets/img/default-hotel.jpg';
if (!empty($hotel['Image'])) {
    if (strpos($hotel['Image'], 'http') === 0) {
        $imgSrc = $hotel['Image'];
    } else {
        $imgSrc = 'uploads/hotels/' . $hotel['Image'];
    }
}
?>

<link rel="stylesheet" href="../css/style.css">

<div class="hotel-detail-section">
    <div class="container">
        <div class="detail-grid">
            
            <div class="detail-image-box">
                <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($hotel['Name']); ?>">
            </div>

            <div class="detail-info-box">
                <h1><?php echo htmlspecialchars($hotel['Name']); ?></h1>
                
                <div class="detail-stars">
                    <?php 
                    $stars = intval($hotel['Stars']);
                    for($i=1; $i<=5; $i++) {
                        echo $i <= $stars ? '<i class="fa-solid fa-star"></i> ' : '<i class="fa-regular fa-star"></i> ';
                    }
                    ?>
                </div>

                <div class="detail-location">
                    <i class="fa-solid fa-map-marker-alt"></i> 
                    <strong>Địa điểm:</strong> <?php echo htmlspecialchars($hotel['CityName'] ?? 'Chưa cập nhật'); ?>
                </div>

                <div class="price-card">
                    <span>Giá phòng chỉ từ</span>
                    <strong><?php echo number_format($hotel['Price'], 0, ',', '.'); ?>đ <small style="font-size: 1rem; color: #666; font-weight: 400;">/ đêm</small></strong>
                </div>

                <div class="detail-description">
                    <h3>Giới thiệu</h3>
                    <p><?php echo nl2br(htmlspecialchars($hotel['Description'])); ?></p>
                </div>

                <div class="related-tours">
                    <h4><i class="fa-solid fa-suitcase-rolling"></i> Các Tour có khách sạn này</h4>
                    <?php if (!empty($tours)): ?>
                        <div class="tour-list-mini">
                            <?php foreach($tours as $tour): ?>
                                <a href="tour_detail.php?id=<?php echo $tour['ID']; ?>" class="tour-item">
                                    <span class="t-name"><?php echo htmlspecialchars($tour['Name']); ?></span>
                                    <span class="t-price"><?php echo number_format($tour['Price'], 0, ',', '.'); ?>đ</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 0.9rem; color: #888;">Hiện chưa có tour nào sử dụng dịch vụ tại đây.</p>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 30px;">
                    <a href="hotels.php" class="btn-back" style="color: #6a11cb; text-decoration: none; font-weight: 600;">
                        <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>