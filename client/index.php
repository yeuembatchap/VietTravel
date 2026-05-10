<?php
// index.php
session_start();
require_once '../config/db.php'; // Nhớ giữ đúng đường dẫn file kết nối của bạn

// Nếu chưa có ngôn ngữ, nạp ngôn ngữ mặc định để tránh thông báo biến chưa định nghĩa
if (!isset($lang)) {
    require_once '../lang/vi.php';
}

// 1. Lấy danh sách tour đang mở bán (Status = 1) - Dùng PDO
$sql = "SELECT tours.*, cities.AreaID 
        FROM tours 
        LEFT JOIN cities ON tours.CityID = cities.ID 
        WHERE tours.Status = 1 
        ORDER BY tours.ID DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy danh sách Banner cho trang chủ
$sqlBanners = "SELECT * FROM banners WHERE Page = 'home' AND Status = 1 ORDER BY DisplayOrder ASC";
$stmtBanners = $conn->prepare($sqlBanners);
$stmtBanners->execute();
$banners = $stmtBanners->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Chủ - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php $disableHeaderBanner = true; include 'header.php'; ?>

    <div class="hero-banner">
        <button class="slider-btn prev-btn" onclick="moveSlide(-1)"><i class="fas fa-chevron-left"></i></button>
        
        <div class="slides-container" style="width: 100%; height: 100%;">
            <?php if (count($banners) > 0): ?>
                <?php foreach ($banners as $index => $banner): ?>
                    <div class="slide fade" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                        <a href="<?php echo !empty($banner['Link']) ? htmlspecialchars($banner['Link']) : '#'; ?>">
                            <img src="<?php echo htmlspecialchars($banner['Image']); ?>" alt="<?php echo htmlspecialchars($banner['Title']); ?>">
                        </a>
                        
                        </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide fade" style="display: block;">
                    <img src="https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=2070&q=80" alt="Banner Default">
                </div>
            <?php endif; ?>
        </div>

        <button class="slider-btn next-btn" onclick="moveSlide(1)"><i class="fas fa-chevron-right"></i></button>
    </div>

    <div class="search-container-wrapper">
        <div class="search-tour-box">
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input type="text" id="tour-search-input" placeholder="Nhập địa điểm bạn muốn khám phá... (Vd: Nha Trang, Phú Quốc)" autocomplete="off">
            </div>
            <div id="search-suggestions" class="suggestions-list"></div>
        </div>
    </div>

    <div class="about-section">
        <div class="about-text">
            <h2>VỀ TOUR NỘI ĐỊA</h2>
            <p>Đại diện cho sự khắng khít song hành, tô điểm cho đời sống tinh thần của khách hàng bằng những sản phẩm tour du lịch chất lượng và dịch vụ chu đáo được nuôi dưỡng bởi đam mê.</p>
            <p>Chọn giá trị cội nguồn làm sứ mệnh dấn thân và chinh phục, chúng tôi mong muốn lan tỏa vẻ đẹp thiên nhiên và văn hóa Việt Nam đến với mọi người.</p>
            <a href="about.php" class="btn-discover">KHÁM PHÁ</a>
        </div>
        <div class="about-image">
            <!-- <img src="https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=800&q=80" alt="Về chúng tôi"> -->
            <img src="<?php echo htmlspecialchars($banner['Image']); ?>" alt="<?php echo htmlspecialchars($banner['Title']); ?>">
        </div>
    </div>

    <div class="tour-section" style="max-width: 1200px; margin: 0 auto; padding: 60px 20px;">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2 class="section-title"><?= $lang['ecosystem'] ?></h2>
            <ul class="filter-menu" id="tour-filter" style="margin-bottom: 0;">
                <li class="active" data-filter="all"><?= $lang['all'] ?></li>
                <li data-filter="1"><?= $lang['north'] ?></li>
                <li data-filter="2"><?= $lang['center'] ?></li>
                <li data-filter="3"><?= $lang['south'] ?></li>
            </ul>
        </div>

        <div class="tour-carousel-container" style="position: relative;">
            <button class="tour-nav-btn prev" id="tour-prev"><i class="fas fa-chevron-left"></i></button>
            
            <div class="tour-carousel-wrapper" style="overflow: hidden; padding: 10px 5px;">
                <div class="tour-grid-4" id="tour-track" style="display: flex; gap: 20px; transition: transform 0.5s ease; will-change: transform;">
                    <?php if(count($tours) > 0): ?>
                        <?php foreach($tours as $tour): ?>
                            <a href="tour_detail.php?id=<?= $tour['ID'] ?>" class="tour-item" data-area="<?= isset($tour['AreaID']) ? $tour['AreaID'] : '' ?>">
                                <div class="tour-img-wrap">
                                    <img src="<?= htmlspecialchars($tour['Banner']) ?>" alt="<?= htmlspecialchars($tour['Name']) ?>">
                                    <div class="tour-overlay">
                                        <span class="view-detail">Xem chi tiết</span>
                                    </div>
                                </div>
                                <div class="tour-info">
                                    <span class="tour-tag">Khám phá</span>
                                    <h3><?= htmlspecialchars($tour['Name']) ?></h3>
                                    <div class="tour-meta">
                                        <span class="tour-price">
                                            <small>Từ</small> <?= number_format($tour['Price'], 0, ',', '.') ?> <small>VNĐ</small>
                                        </span>
                                        <i class="fas fa-arrow-right"></i>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; width: 100%;">Hiện chưa có tour nào.</p>
                    <?php endif; ?>
                </div>
            </div>

            <button class="tour-nav-btn next" id="tour-next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        // ==========================================
        // 1. HIỆU ỨNG BANNER TRANG CHỦ (AUTO SLIDE)
        // ==========================================
        let slideIndex = 1;
        let slideInterval;
        let slides = document.getElementsByClassName("slide");

        if (slides.length > 0) {
            showSlides(slideIndex);
            startAutoSlide();
        }

        // Đưa hàm moveSlide ra phạm vi toàn cục (window) để nút HTML có thể bấm được
        window.moveSlide = function(n) {
            showSlides(slideIndex += n);
            resetAutoSlide(); 
        };

        function showSlides(n) {
            if (n > slides.length) { slideIndex = 1 }    
            if (n < 1) { slideIndex = slides.length }
            
            // Ẩn tất cả ảnh
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";  
            }
            
            // Hiện đúng ảnh được yêu cầu
            if(slides[slideIndex - 1]) {
                slides[slideIndex - 1].style.display = "block";  
            }
        }

        function startAutoSlide() {
            slideInterval = setInterval(function() {
                showSlides(slideIndex += 1);
            }, 4000); // Tự động trượt sau mỗi 4 giây
        }

        function resetAutoSlide() {
            clearInterval(slideInterval);
            startAutoSlide();
        }

        // ==========================================
// ==========================================
    // 2. CAROUSEL TRƯỢT DANH SÁCH TOUR (CÓ AUTO-SLIDE)
    // ==========================================
    const track = document.getElementById('tour-track');
    const nextBtn = document.getElementById('tour-next');
    const prevBtn = document.getElementById('tour-prev');

    if (track && nextBtn && prevBtn) { 
        let scrollPosition = 0;
        let tourInterval; // Biến lưu bộ đếm thời gian cho Tour

        // Tính khoảng cách mỗi lần trượt (1 item + gap)
        const getScrollStep = () => {
            const tourItem = document.querySelector('.tour-item');
            return tourItem ? (tourItem.offsetWidth + 20) : 0;
        };

        // Hàm xử lý trượt sang phải
        const slideTourNext = () => {
            const maxScroll = track.scrollWidth - track.clientWidth;
            
            // Nếu đã trượt tới item cuối cùng, thì reset quay về item đầu tiên
            if (scrollPosition >= maxScroll - 10) { // trừ hao 10px tránh sai số thập phân
                scrollPosition = 0;
            } else {
                scrollPosition = Math.min(scrollPosition + getScrollStep(), maxScroll);
            }
            track.style.transform = `translateX(-${scrollPosition}px)`;
        };

        // Hàm xử lý trượt sang trái
        const slideTourPrev = () => {
            scrollPosition = Math.max(scrollPosition - getScrollStep(), 0);
            track.style.transform = `translateX(-${scrollPosition}px)`;
        };

        // Lắng nghe sự kiện click nút bấm
        nextBtn.addEventListener('click', () => {
            slideTourNext();
            resetTourAutoSlide(); // Khách bấm tay thì reset bộ đếm tự động
        });

        prevBtn.addEventListener('click', () => {
            slideTourPrev();
            resetTourAutoSlide();
        });

        // --- TÍNH NĂNG MỚI: TỰ ĐỘNG TRƯỢT ---
        function startTourAutoSlide() {
            tourInterval = setInterval(slideTourNext, 2500); // 3.5 giây tự lướt 1 tour
        }

        function resetTourAutoSlide() {
            clearInterval(tourInterval);
            startTourAutoSlide();
        }

        // Tạm dừng trượt khi khách hàng rê chuột vào danh sách Tour để đọc thông tin
        track.addEventListener('mouseenter', () => clearInterval(tourInterval));
        // Tiếp tục trượt khi khách hàng bỏ chuột ra ngoài
        track.addEventListener('mouseleave', startTourAutoSlide);

        // Kích hoạt chạy tự động ngay khi web load xong
        startTourAutoSlide();
    }

        // ==========================================
        // 3. LỌC TOUR THEO MIỀN (BẮC, TRUNG, NAM)
        // ==========================================
        const filterBtns = document.querySelectorAll('#tour-filter li');
        const tourItems = document.querySelectorAll('.tour-item');

        if (filterBtns.length > 0 && tourItems.length > 0) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(li => li.classList.remove('active'));
                    this.classList.add('active');
                    const val = this.dataset.filter;
                    
                    // Trả thanh cuộn tour về đầu khi lọc
                    if (track) {
                        track.style.transform = `translateX(0)`;
                    }

                    tourItems.forEach(item => {
                        item.style.display = (val === 'all' || item.dataset.area === val) ? 'flex' : 'none';
                    });
                });
            });
        }

        // ==========================================
        // 4. TÌM KIẾM TOUR (AJAX REAL-TIME)
        // ==========================================
        const searchInput = document.getElementById('tour-search-input');
        const suggestionBox = document.getElementById('search-suggestions');

        if (searchInput && suggestionBox) {
            searchInput.addEventListener('input', function() {
                let keyword = this.value.trim();
                if (keyword.length >= 2) {
                    fetch('ajax_search_tours.php?key=' + encodeURIComponent(keyword))
                        .then(res => res.text())
                        .then(data => {
                            suggestionBox.innerHTML = data;
                            suggestionBox.style.display = 'block';
                        })
                        .catch(err => console.error("Lỗi search:", err)); 
                } else {
                    suggestionBox.style.display = 'none';
                }
            });

            // Ẩn box gợi ý khi bấm ra ngoài
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target)) {
                    suggestionBox.style.display = 'none';
                }
            });
        }
    });
</script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById('tour-search-input');
        const suggestionBox = document.getElementById('search-suggestions');

        searchInput.addEventListener('input', function() {
            let keyword = this.value.trim();
            
            if (keyword.length >= 2) {
                // Gửi yêu cầu AJAX
                fetch('ajax_search_tours.php?key=' + encodeURIComponent(keyword))
                    .then(response => response.text())
                    .then(data => {
                        suggestionBox.innerHTML = data;
                        suggestionBox.style.display = 'block';
                    });
            } else {
                suggestionBox.style.display = 'none';
            }
        });

        // Ẩn gợi ý khi click ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                suggestionBox.style.display = 'none';
            }
        });
    });
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>