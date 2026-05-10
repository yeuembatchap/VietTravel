<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

// 1. Kiểm tra ID bài viết
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: news.php");
    exit();
}

$id = intval($_GET['id']);

// 2. Tăng lượt xem (Views) - Cột này có sẵn trong DB của bạn
$updateViews = $conn->prepare("UPDATE posts SET Views = Views + 1 WHERE ID = ?");
$updateViews->execute([$id]);

// 3. Lấy nội dung chi tiết bài viết
$sql = "SELECT * FROM posts WHERE ID = ? AND Status = 1";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy bài viết
if (!$post) {
    echo "Bài viết không tồn tại hoặc đã bị ẩn.";
    exit();
}

// 4. Lấy các bài viết liên quan (Gợi ý thêm 3 bài khác)
$relatedSql = "SELECT ID, Title, Thumbnail, CreatedAt FROM posts WHERE ID != ? AND Status = 1 ORDER BY RAND() LIMIT 3";
$relatedStmt = $conn->prepare($relatedSql);
$relatedStmt->execute([$id]);
$relatedPosts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['Title']) ?> - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <?php include '../header.php'; ?>

    <div class="post-detail-wrapper container">
        
        <div class="breadcrumb">
            <a href="index.php">Trang chủ</a> <i class="fas fa-angle-right"></i>
            <a href="news.php">Tin tức</a> <i class="fas fa-angle-right"></i>
            <span>Chi tiết bài viết</span>
        </div>

        <div class="post-detail-layout">
            <main class="post-main-content">
                <header class="post-header">
                    <h1 class="post-full-title"><?= htmlspecialchars($post['Title']) ?></h1>
                    <div class="post-full-meta">
                        <span><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($post['CreatedAt'])) ?></span>
                        <span><i class="far fa-eye"></i> <?= number_format($post['Views']) ?> lượt xem</span>
                        <span><i class="fas fa-user-circle"></i> Tác giả: Admin</span>
                    </div>
                </header>

                <div class="post-featured-image">
                    <?php
                        $thumb = $post['Thumbnail'];
                        if (empty($thumb)) {
                            $imgSrc = 'https://via.placeholder.com/800x400?text=No+Image';
                        } elseif (strpos($thumb, 'http') === 0) {
                            $imgSrc = $thumb;
                        } else {
                            $imgSrc = '../uploads/' . $thumb;
                        }
                    ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($post['Title']) ?>" onerror="this.src='https://via.placeholder.com/800x400?text=No+Image'">
                </div>

                <div class="post-body-text">
                    <?= $post['Content'] ?>
                </div>

                <div class="post-footer-share">
                    <span>Chia sẻ bài viết:</span>
                    <a href="#" class="share-btn fb"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="share-btn tw"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="share-btn ln"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </main>

            <aside class="post-sidebar">
                <div class="sidebar-widget">
                    <h3 class="widget-title">Bài viết liên quan</h3>
                    <div class="related-list">
                        <?php foreach($relatedPosts as $rPost): ?>
                        <div class="related-item">
                            <?php
                                $relatedThumb = $rPost['Thumbnail'];
                                if (empty($relatedThumb)) {
                                    $relatedImgSrc = 'https://via.placeholder.com/90x60?text=No+Image';
                                } elseif (strpos($relatedThumb, 'http') === 0) {
                                    $relatedImgSrc = $relatedThumb;
                                } else {
                                    $relatedImgSrc = '../uploads/' . $relatedThumb;
                                }
                            ?>
                            <a href="post_detail.php?id=<?= $rPost['ID'] ?>" class="related-thumb">
                                <img src="<?= htmlspecialchars($relatedImgSrc) ?>" alt="" onerror="this.src='https://via.placeholder.com/90x60?text=No+Image'">
                            </a>
                            <div class="related-info">
                                <h4><a href="post_detail.php?id=<?= $rPost['ID'] ?>"><?= htmlspecialchars($rPost['Title']) ?></a></h4>
                                <span><?= date('d/m/Y', strtotime($rPost['CreatedAt'])) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="sidebar-promo">
                    <img src="https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=500&q=80" alt="Promo">
                    <div class="promo-text">
                        <h4>Bạn muốn đi du lịch?</h4>
                        <p>Đặt tour ngay hôm nay để nhận ưu đãi 20%</p>
                        <a href="index.php" class="btn-amber">Khám phá ngay</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
<?php include 'footer.php'; ?>
</body>
</html>