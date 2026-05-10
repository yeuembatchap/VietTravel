<?php
require_once '../config/db.php';

$keyword = isset($_GET['key']) ? trim($_GET['key']) : '';

if ($keyword !== '') {
    // Truy vấn các tour có tên giống từ khóa, sắp xếp theo lượt đặt (BookingCount) giảm dần
    // Giả sử bảng tours của bạn có cột 'BookingCount' hoặc bạn có thể đếm từ bảng bookings
    $sql = "SELECT ID, Name, Banner, Price 
            FROM tours 
            WHERE Status = 1 AND Name LIKE :keyword 
            ORDER BY ID DESC LIMIT 5"; // Bạn có thể thay ORDER BY theo logic lượt đi của bạn

    $stmt = $conn->prepare($sql);
    $stmt->execute(['keyword' => "%$keyword%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 0) {
        foreach ($results as $row) {
            echo '<a href="tour_detail.php?id='.$row['ID'].'" class="suggestion-item">
                    <img src="'.$row['Banner'].'" alt="">
                    <div class="sugg-info">
                        <span class="sugg-name">'.$row['Name'].'</span>
                        <span class="sugg-price">'.number_format($row['Price'], 0, ',', '.').' VNĐ</span>
                    </div>
                  </a>';
        }
    } else {
        echo '<div class="no-sugg">Không tìm thấy tour phù hợp</div>';
    }
}
?>