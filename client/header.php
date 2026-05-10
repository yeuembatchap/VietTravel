<?php
// Bật session nếu chưa có
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem người dùng có bấm nút đổi ngôn ngữ không
if (isset($_GET['lang'])) {
    if ($_GET['lang'] == 'vi' || $_GET['lang'] == 'en') {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

// Nếu chưa chọn ngôn ngữ, mặc định là Tiếng Việt
$current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'vi';

// Nhúng file từ điển tương ứng
require_once __DIR__ . "/../lang/" . $current_lang . ".php";

// Lấy ảnh banner từ bảng areas
require_once __DIR__ . '/../config/db.php';
$bannerImage = '';
try {
    $stmt = $conn->query("SELECT Banner FROM areas ORDER BY ID ASC LIMIT 1");
    $area = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($area && !empty($area['Banner'])) {
        $bannerImage = $area['Banner'];
    }
} catch (PDOException $e) {
    // Nếu không lấy được banner thì không làm gì cả
}
?>
<div class="top-header">
    <div class="container">
        
       <a href="index.php" class="logo footer-logo">VIET<span>TRAVEL</span></a>
        
        <ul class="main-nav">
            <li><a href="about.php">THƯƠNG HIỆU</a></li>
            <li><a href="news.php">TIN TỨC </a></li>
            <li class="nav-item has-dropdown">
                <a href="#" class="nav-link">DỊCH VỤ </a>
                <ul class="sub-menu">
                    <li><a href="vehicles.php">Thuê xe du lịch</a></li>
                    <li><a href="insurance.php">Gói bảo hiểm</a></li>
                    <li><a href="hotels.php">Khách sạn</a></li>
                </ul>
            </li>
        </ul>

        <div class="top-right">
            
            <div class="auth-links">
                <?php if(isset($_SESSION['user'])): ?>
                    <a href="profile.php" style="color: #ffffff; font-weight: bold;">
                        Chào mừng, <?= htmlspecialchars($_SESSION['user']['fullname']) ?>
                    </a>
                    
                    <?php if(isset($_SESSION['user']['Role']) && $_SESSION['user']['Role'] == 'admin'): ?>
                        <span class="divider" style="margin: 0 10px;">|</span>
                        <a href="../admin/index.php" style="background: #c62828; color: white; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold;">
                            ⚙️ Quản trị viên
                        </a>
                    <?php endif; ?>

                    <span class="divider" style="margin: 0 10px;">|</span>
                    <a href="../logout.php" style="color: #f69c00;"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                <?php else: ?>
                    <a href="login.php" style="font-size: 1.0rem;"><i class="fas fa-user-circle" style="font-size: 1.1rem;"></i> Đăng nhập</a>
                    <span class="divider">/</span>
                    <a href="register.php" style="font-size: 1.0rem;">Đăng ký</a>
                <?php endif; ?>
            </div>

            <div class="lang-selector" style="margin-top: 5px;">
                <div id="google_translate_element"></div>
            </div>

            <div class="custom-lang-selector notranslate" translate="no">
                <button onclick="changeLanguage('vi')" class="lang-btn" style="color: #ffffff;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/21/Flag_of_Vietnam.svg" alt="VN" width="24" height="16"> VN
                </button>
                <!-- <span class="divider" style="color: #f69c00;">|</span> -->
                <button onclick="changeLanguage('en')" class="lang-btn" style="color: #ffffff;">
                    <img src="https://upload.wikimedia.org/wikipedia/en/a/a4/Flag_of_the_United_States.svg" alt="EN" width="24" height="16"> EN
                </button>
            </div>

            <div id="google_translate_element" style="display:none;"></div>

            <script type="text/javascript">
                // Khởi tạo Google Translate ngầm
                function googleTranslateElementInit() {
                    new google.translate.TranslateElement({
                        pageLanguage: 'vi',
                        includedLanguages: 'vi,en', // Chỉ dịch Việt và Anh
                        autoDisplay: false
                    }, 'google_translate_element');
                }

                // Hàm bắt sự kiện khi click vào nút Cờ
                function changeLanguage(lang) {
                    var selectField = document.querySelector(".goog-te-combo");
                    if (selectField) {
                        selectField.value = lang;
                        selectField.dispatchEvent(new Event('change'));
                    }
                }
            </script>
            <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
        </div>
    </div>
</div>

<?php if (!empty($bannerImage) && empty($disableHeaderBanner)): ?>
    <div class="header-banner" style="background-image: url('<?= htmlspecialchars($bannerImage) ?>'); background-size: cover; background-position: center; min-height: 260px; position: relative;">
        <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.2);"></div>
    </div>
<?php endif; ?>
