<?php
require_once '../config/db.php';

// Hàm chuyển đổi tiêu đề thành Slug (Ví dụ: "Tin tức mới" -> "tin-tuc-moi")
function create_slug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#',
        '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
        '#(Ì|Í|Ị|Ỉ|Ĩ)#',
        '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
        '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#',
        '#(Ỳ|Ý|Ạ|Ỷ|Ỹ)#',
        '#(Đ)#',
        '/[^a-zA-Z0-9\-\_]/',
    );
    $replace = array('a', 'e', 'i', 'o', 'u', 'y', 'd', 'A', 'E', 'I', 'O', 'U', 'Y', 'D', '-', );
    $string = preg_replace($search, $replace, $string);
    $string = preg_replace('/(-)+/', '-', $string);
    $string = strtolower(trim($string, '-'));
    return $string;
}

$rss_url = "https://vnexpress.net/rss/du-lich.rss";
$xml = @simplexml_load_file($rss_url);

if ($xml === false) {
    die("Không thể kết nối với nguồn tin.");
}

echo "<h2>Đang cập nhật tin tức từ VnExpress...</h2>";
$count = 0;

foreach ($xml->channel->item as $item) {
    $title = (string)$item->title;
    $pubDate = date('Y-m-d H:i:s', strtotime($item->pubDate));
    $description_html = (string)$item->description;
    
    // Tạo Slug từ Tiêu đề
    $slug = create_slug($title);

    preg_match('/src="([^"]+)"/', $description_html, $matches);
    $thumbnail = isset($matches[1]) ? $matches[1] : '';
    $content = strip_tags($description_html) . " (Nguồn: VnExpress)";

    // Kiểm tra xem bài viết HOẶC Slug đã tồn tại chưa
    $check = $conn->prepare("SELECT ID FROM posts WHERE Title = ? OR Slug = ?");
    $check->execute([$title, $slug]);

    if ($check->rowCount() == 0) {
        // Bổ sung thêm cột Slug vào câu lệnh INSERT
        $sql = "INSERT INTO posts (Title, Slug, Thumbnail, Content, CreatedAt, Status, Views) 
                VALUES (:title, :slug, :thumb, :content, :date, 1, :views)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':title'   => $title,
            ':slug'    => $slug,
            ':thumb'   => $thumbnail,
            ':content' => $content,
            ':date'    => $pubDate,
            ':views'   => rand(50, 500)
        ]);
        
        echo "<p style='color: green;'>✅ Đã thêm: <b>$title</b> (Slug: $slug)</p>";
        $count++;
    } else {
        echo "<p style='color: gray;'>⏭️ Đã tồn tại bài viết này.</p>";
    }
}

echo "<hr><h3>Hoàn tất! Đã thêm mới $count bài viết.</h3>";
echo "<a href='news.php'>Quay lại trang Tin tức</a>";
?>