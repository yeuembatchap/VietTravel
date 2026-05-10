<?php 
session_start();

// MỚI: Thêm dòng gọi file kết nối database trực tiếp vào đây
// Lưu ý: Nếu file db.php của bạn nằm ở thư mục khác, hãy sửa lại đường dẫn cho đúng nhé
require_once '../config/db.php'; 

$disableHeaderBanner = true;
include 'header.php'; 

// 1. Giá trị mặc định (Fallback)
$bannerImage = "https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=1920&q=80";
$bannerTitle = "HÀNH TRÌNH TẠO NÊN THƯƠNG HIỆU";
$bannerSubtitle = "VietTravel - Kết nối những tâm hồn đồng điệu qua từng chuyến đi";

// 2. Truy vấn Database thông minh
if (isset($conn)) {
    try {
        $sql_banner = "SELECT Image, Title, Subtitle FROM banners WHERE Page = 'about' AND Status = 1 LIMIT 1";
        $row = null;

        // Nếu file kết nối của bạn dùng PDO
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare($sql_banner);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } 
        // Nếu file kết nối của bạn dùng MySQLi
        elseif ($conn instanceof mysqli) {
            $result = $conn->query($sql_banner);
            if ($result) {
                $row = $result->fetch_assoc();
            }
        }

        // Cập nhật dữ liệu nếu tìm thấy trong CSDL
        if (!empty($row)) {
            if (!empty($row['Image'])) $bannerImage = $row['Image'];
            if (!empty($row['Title'])) $bannerTitle = $row['Title'];
            if (!empty($row['Subtitle'])) $bannerSubtitle = $row['Subtitle'];
        }
    } catch(Exception $e) {
        echo "<script>console.log('Lỗi truy vấn SQL: " . addslashes($e->getMessage()) . "');</script>";
    }
} else {
    echo "<script>console.log('Vẫn lỗi: Chưa tìm thấy biến \$conn. Hãy kiểm tra lại file db.php của bạn!');</script>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Về Chúng Tôi - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <div class="news-hero-banner" style="background-image: url('<?= htmlspecialchars($bannerImage) ?>');">
        <div class="banner-overlay"></div>
        <div class="banner-content">
            <h1><?= htmlspecialchars($bannerTitle) ?></h1>
            <p><?= htmlspecialchars($bannerSubtitle) ?></p>
        </div>
    </div>

    <section class="about-page container" style="padding: 80px 20px;">
        <div class="about-section">
            <div class="about-text">
                <span>Về VietTravel</span>
                <h2>Sứ mệnh kết nối di sản Việt</h2>
                <p>Khởi nguồn từ niềm đam mê xê dịch và tình yêu quê hương đất nước, VietTravel không chỉ đơn thuần là đơn vị cung cấp tour du lịch. Chúng tôi là những người kể chuyện, đưa bạn đến gần hơn với những giá trị văn hóa bản địa.</p>
                <p>Với hệ thống dịch vụ đa dạng từ thuê xe, bảo hiểm đến các tour trải nghiệm cao cấp, chúng tôi cam kết mang lại sự an tâm tuyệt đối cho khách hàng trên mọi cung đường.</p>
            </div>
            <div class="about-image">
                <img src="https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=1000&q=80" alt="Về VietTravel">
            </div>
        </div>

        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-top: 60px; text-align: center;">
            <div class="stat-item" style="background: #f9f6ff; padding: 40px; border-radius: 20px; border: 1px solid #eee;">
                <h3 style="color: #9b51e0; font-size: 2.5rem; margin-bottom: 10px;">10+</h3>
                <p style="font-weight: bold; color: #555;">Năm kinh nghiệm</p>
            </div>
            <div class="stat-item" style="background: #f9f6ff; padding: 40px; border-radius: 20px; border: 1px solid #eee;">
                <h3 style="color: #f2994a; font-size: 2.5rem; margin-bottom: 10px;">500+</h3>
                <p style="font-weight: bold; color: #555;">Điểm đến Việt Nam</p>
            </div>
            <div class="stat-item" style="background: #f9f6ff; padding: 40px; border-radius: 20px; border: 1px solid #eee;">
                <h3 style="color: #9b51e0; font-size: 2.5rem; margin-bottom: 10px;">50k+</h3>
                <p style="font-weight: bold; color: #555;">Khách hàng tin tưởng</p>
            </div>
            <div class="stat-item" style="background: #f9f6ff; padding: 40px; border-radius: 20px; border: 1px solid #eee;">
                <h3 style="color: #f2994a; font-size: 2.5rem; margin-bottom: 10px;">24/7</h3>
                <p style="font-weight: bold; color: #555;">Hỗ trợ tận tâm</p>
            </div>
        </div>
    </section>

    <section style="background: linear-gradient(135deg, #4b0082, #9b51e0); color: #fff; padding: 80px 20px; text-align: center;">
        <div class="container" style="max-width: 800px; margin: 0 auto;">
            <h2 style="font-size: 2rem; margin-bottom: 20px;">CAM KẾT CỦA CHÚNG TÔI</h2>
            <p style="font-size: 1.1rem; line-height: 1.8; opacity: 0.9;">"VietTravel không chỉ bán tour, chúng tôi bán những trải nghiệm thay đổi cuộc sống. Sự hài lòng của bạn là kim chỉ nam cho mọi hành động của chúng tôi."</p>
            <br>
            <a href="index.php" class="btn-discover" style="background: #f2994a; box-shadow: none;">KHÁM PHÁ TOUR NGAY</a>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>