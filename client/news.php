<?php
require_once '../config/db.php';

$disableHeaderBanner = true;

// --- LẤY DỮ LIỆU BANNER CHO TRANG NEWS ---
// 1. Gán giá trị mặc định phòng trường hợp không có dữ liệu trong DB
$bannerImage = "https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2074&q=80";
$bannerTitle = "CẨM NANG DU LỊCH";
$bannerSubtitle = "Khám phá những điểm đến mới, kinh nghiệm và mẹo du lịch hữu ích từ VietTravel";

// 2. Truy vấn lấy banner từ bảng 'banners' với Page = 'news'
try {
    $sql_banner = "SELECT Image, Title, Subtitle FROM banners WHERE Page = 'news' AND Status = 1 LIMIT 1";
    $stmt_banner = $conn->prepare($sql_banner);
    $stmt_banner->execute();
    $row_banner = $stmt_banner->fetch(PDO::FETCH_ASSOC);
    
    if ($row_banner) {
        if (!empty($row_banner['Image'])) $bannerImage = $row_banner['Image'];
        if (!empty($row_banner['Title'])) $bannerTitle = $row_banner['Title'];
        if (!empty($row_banner['Subtitle'])) $bannerSubtitle = $row_banner['Subtitle'];
    }
} catch(PDOException $e) {
    echo "<script>console.log('Lỗi tải banner: " . addslashes($e->getMessage()) . "');</script>";
}

// --- 1. CHUẨN BỊ BIẾN TÌM KIẾM ---
$search = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// --- 2. THUẬT TOÁN PHÂN TRANG ---
$limit = 6;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số bài viết (có tính đến từ khóa tìm kiếm)
if ($search != '') {
    $countStmt = $conn->prepare("SELECT COUNT(ID) as total FROM posts WHERE Status = 1 AND Title LIKE ?");
    $countStmt->execute(["%$search%"]);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(ID) as total FROM posts WHERE Status = 1");
    $countStmt->execute();
}
$totalRows = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $limit);

// --- 3. LẤY DỮ LIỆU BÀI VIẾT ---
if ($search != '') {
    $sql = "SELECT * FROM posts WHERE Status = 1 AND Title LIKE :search ORDER BY CreatedAt DESC LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
} else {
    $sql = "SELECT * FROM posts WHERE Status = 1 ORDER BY CreatedAt DESC LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($sql);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin Tức & Cẩm Nang - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="news-hero-banner" style="background-image: url('<?= htmlspecialchars($bannerImage) ?>');">
        <div class="banner-overlay"></div>
        <div class="banner-content">
            <h1><?= htmlspecialchars($bannerTitle) ?></h1>
            <p><?= htmlspecialchars($bannerSubtitle) ?></p>
        </div>
    </div>

    <section class="news-container container" style="max-width: 1200px; margin: 0 auto; padding: 60px 20px;">
        
        <div class="search-section" style="margin-bottom: 40px; text-align: center;">
            <form action="news.php" method="GET" class="search-form">
                <input type="text" name="keyword" placeholder="Bạn muốn tìm tin tức gì?..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit"><i class="fas fa-search"></i> Tìm kiếm</button>
            </form>
            <?php if($search != ''): ?>
                <p style="margin-top: 15px; color: #666;">Kết quả cho từ khóa: <b>"<?= htmlspecialchars($search) ?>"</b> (<?= $totalRows ?> bài viết)</p>
                <a href="news.php" style="color: #9b51e0; font-size: 14px; text-decoration: none;">[Xóa bộ lọc]</a>
            <?php endif; ?>
        </div>

        <div class="news-grid">
            <?php if(count($posts) > 0): ?>
                <?php foreach($posts as $post): ?>
                    <article class="post-card">
                        <div class="post-image">
                            <a href="post_detail.php?id=<?= $post['ID'] ?>">
                                <?php 
                                    $thumb = $post['Thumbnail'];
                                    if (empty($thumb)) {
                                        $imgSrc = 'https://via.placeholder.com/800x600?text=VietTravel+News'; 
                                    } elseif (strpos($thumb, 'http') === 0) {
                                        $imgSrc = $thumb; 
                                    } else {
                                        $imgSrc = 'uploads/' . $thumb; 
                                    }
                                ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" 
                                     alt="<?= htmlspecialchars($post['Title']) ?>"
                                     onerror="this.src='https://via.placeholder.com/800x600?text=Image+Not+Found';">
                            </a>
                            <div class="post-date">
                                <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($post['CreatedAt'])) ?>
                            </div>
                        </div>
                        <div class="post-content">
                            <div class="post-meta">
                                <span><i class="fas fa-user-edit"></i> Admin</span>
                                <span><i class="fas fa-folder-open"></i> Tin tức</span>
                            </div>
                            <h3 class="post-title">
                                <a href="post_detail.php?id=<?= $post['ID'] ?>"><?= htmlspecialchars($post['Title']) ?></a>
                            </h3>
                            <p class="post-excerpt">
                                <?= mb_substr(strip_tags($post['Content']), 0, 120, 'UTF-8') ?>...
                            </p>
                            <a href="post_detail.php?id=<?= $post['ID'] ?>" class="read-more-btn">Đọc tiếp <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-news" style="text-align: center; width: 100%; grid-column: 1 / -1; padding: 50px;">
                    <i class="fas fa-newspaper" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                    <p>Không tìm thấy bài viết nào phù hợp.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="news.php?page=<?= $page - 1 ?>&keyword=<?= urlencode($search) ?>" class="page-link"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>

            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="news.php?page=<?= $i ?>&keyword=<?= urlencode($search) ?>" 
                   class="page-link <?= ($i == $page) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if($page < $totalPages): ?>
                <a href="news.php?page=<?= $page + 1 ?>&keyword=<?= urlencode($search) ?>" class="page-link"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </section>
<?php include 'footer.php'; ?>
</body>
</html>