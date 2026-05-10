<?php
session_start();
require_once '../config/db.php';

// Lấy ID tour từ URL
$tour_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Truy vấn thông tin tour
$stmt = $conn->prepare("SELECT * FROM tours WHERE ID = ? AND Status = 1");
$stmt->execute([$tour_id]);
$tour = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tour) {
    die("<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h2 style='color:#d35400;'>Không tìm thấy thông tin Tour!</h2><a href='index.php'>Quay lại trang chủ</a></div>");
}

// 2. Lấy danh sách Xe du lịch đang hoạt động
$stmtXe = $conn->prepare("SELECT * FROM vehicles WHERE Status = 1");
$stmtXe->execute();
$vehicles = $stmtXe->fetchAll(PDO::FETCH_ASSOC);

// 3. Lấy danh sách Gói bảo hiểm đang hoạt động
$stmtBH = $conn->prepare("SELECT * FROM insurance_packages WHERE Status = 1");
$stmtBH->execute();
$insurances = $stmtBH->fetchAll(PDO::FETCH_ASSOC);

// 4. THÊM MỚI: Lấy danh sách Khách sạn được sử dụng trong Tour này
$stmtHotel = $conn->prepare("
    SELECT h.*, c.Name as CityName 
    FROM hotels h 
    INNER JOIN tour_hotel th ON h.ID = th.HotelID 
    LEFT JOIN cities c ON h.CityID = c.ID 
    WHERE th.TourID = ?
");
$stmtHotel->execute([$tour_id]);
$tour_hotels = $stmtHotel->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($tour['Name']) ?> - Tour Nội Địa</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS căn bản cho form đẹp hơn */
        .promo-box { display: flex; gap: 10px; margin-bottom: 5px; }
        .promo-box input { flex: 1; text-transform: uppercase; }
        .promo-box button { padding: 10px 15px; background: #e65100; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        #promo_msg { display: block; font-size: 0.9rem; margin-bottom: 15px; font-style: italic; }
        
        /* CSS cho danh sách khách sạn trong tour */
        .tour-hotels-section { margin-top: 40px; background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #eee; }
        .hotel-item-mini { display: flex; gap: 15px; background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #ddd; align-items: center; transition: 0.3s; }
        .hotel-item-mini:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-color: #003366; }
        .hotel-item-mini img { width: 100px; height: 75px; object-fit: cover; border-radius: 6px; }
        .hotel-info-mini { flex: 1; }
        .hotel-info-mini h4 { margin: 0 0 5px 0; font-size: 1.1rem; color: #333; }
        .hotel-info-mini .stars { color: #f2994a; font-size: 0.8rem; margin-bottom: 4px; }
        .btn-view-hotel { padding: 6px 12px; background: #003366; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: bold; transition: 0.2s; }
        .btn-view-hotel:hover { background: #e65100; color: white; }
    </style>
</head>
<body>
<?php include '../header.php'; ?>
    <div class="tour-detail-container">
        
        <div class="tour-info-col">
            <h1 class="tour-detail-title"><?= htmlspecialchars($tour['Name']) ?></h1>
            <img src="<?= htmlspecialchars($tour['Banner']) ?>" alt="<?= htmlspecialchars($tour['Name']) ?>" class="tour-detail-img">
            
            <div class="tour-meta">
                <p><strong>Thời gian:</strong> <?= htmlspecialchars($tour['Duration']) ?></p>
                <p><strong>Khởi hành:</strong> <?= htmlspecialchars($tour['TimeStart']) ?></p>
                <p class="price-adult">Giá người lớn: <?= number_format($tour['Price'], 0, ',', '.') ?> VNĐ</p>
                <p><strong>Giá trẻ em:</strong> <?= number_format($tour['PriceKid'], 0, ',', '.') ?> VNĐ</p>
            </div>
            
            <div style="margin-top: 30px; line-height: 1.8; color: #444; font-size: 1.1rem; text-align: justify;">
                <h3 style="color: #003366; margin-bottom: 10px; text-transform: uppercase;">Lịch trình / Mô tả chi tiết:</h3>
                <p><?= nl2br(htmlspecialchars($tour['Description'])) ?></p> 
            </div>

            <?php if (!empty($tour_hotels)): ?>
            <div class="tour-hotels-section">
                <h3 style="color: #003366; margin-bottom: 15px; text-transform: uppercase; border-bottom: 2px solid #e65100; display: inline-block; padding-bottom: 5px;"><i class="fa-solid fa-hotel"></i> Khách sạn lưu trú</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <?php foreach ($tour_hotels as $h): 
                        // Logic lấy ảnh (link web hoặc cục bộ)
                        $hImg = 'assets/img/default-hotel.jpg';
                        if (!empty($h['Image'])) {
                            if (strpos($h['Image'], 'http') === 0) {
                                $hImg = $h['Image'];
                            } else {
                                $hImg = 'uploads/hotels/' . $h['Image'];
                            }
                        }
                    ?>
                    <div class="hotel-item-mini">
                        <img src="<?= htmlspecialchars($hImg) ?>" alt="<?= htmlspecialchars($h['Name']) ?>">
                        <div class="hotel-info-mini">
                            <h4><?= htmlspecialchars($h['Name']) ?></h4>
                            <div class="stars">
                                <?php 
                                $stars = intval($h['Stars']);
                                for($i=1; $i<=5; $i++) {
                                    echo $i <= $stars ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
                                }
                                ?>
                            </div>
                            <span style="font-size: 0.9rem; color: #666;"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($h['CityName'] ?? 'Đang cập nhật') ?></span>
                        </div>
                        <a href="hotel_detail.php?id=<?= $h['ID'] ?>" class="btn-view-hotel" target="_blank">Xem chi tiết</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            </div>

        <div class="tour-booking-col">
            <div class="booking-card">
                <h3>ĐẶT TOUR TRỰC TUYẾN</h3>
                <form action="process_booking.php" method="POST">
                    
                    <input type="hidden" name="tour_id" value="<?= $tour['ID'] ?>">
                    <input type="hidden" name="discount_percent" id="discount_percent_input" value="0">

                    <div class="form-group">
                        <label>Họ và tên</label>
                        <input type="text" name="fullname" class="form-control" placeholder="Nhập họ tên..." 
                               value="<?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['fullname']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email (Nhận hóa đơn điện tử)</label>
                        <input type="email" name="email" class="form-control" placeholder="Nhập email..." required>
                    </div>

                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" placeholder="Nhập số điện thoại..." required>
                    </div>

                    <div class="booking-row" style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label>Số người lớn</label>
                            <input type="number" id="num_adults" name="adults" class="form-control" value="1" min="1" onchange="calculateTotal()" required>
                        </div>
                        <div class="form-group" style="flex: 1;">
                            <label>Số trẻ em</label>
                            <input type="number" id="num_children" name="children" class="form-control" value="0" min="0" onchange="calculateTotal()">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Bảo hiểm du lịch (Tính theo số người)</label>
                        <select name="insurance_id" id="insurance_id" class="form-control" onchange="calculateTotal()">
                            <option value="0" data-price="0">-- Không mua bảo hiểm --</option>
                            <?php foreach($insurances as $bh): ?>
                                <option value="<?= $bh['ID'] ?>" data-price="<?= $bh['PricePerPerson'] ?>">
                                    <?= htmlspecialchars($bh['Name']) ?> (+<?= number_format($bh['PricePerPerson'], 0, ',', '.') ?>đ/người)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Thuê xe di chuyển (Cộng thêm vào tổng)</label>
                        <select name="vehicle_id" id="vehicle_id" class="form-control" onchange="calculateTotal()">
                            <option value="0" data-price="0">-- Tự túc di chuyển --</option>
                            <?php foreach($vehicles as $xe): ?>
                                <option value="<?= $xe['ID'] ?>" data-price="<?= $xe['PricePerDay'] ?>">
                                    <?= htmlspecialchars($xe['Name']) ?> (+<?= number_format($xe['PricePerDay'], 0, ',', '.') ?>đ)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if($tour['Shuttle'] == 1): ?>
                    <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="shuttle_service" name="shuttle" value="1" style="width: 18px; height: 18px;" onchange="calculateTotal()">
                        <label for="shuttle_service" style="margin: 0; cursor: pointer;">Kèm xe trung chuyển (+ 200.000đ)</label>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Mã giảm giá (Nếu có)</label>
                        <div class="promo-box">
                            <input type="text" id="promo_code" name="promo_code" class="form-control" placeholder="Ví dụ: SALE10">
                            <button type="button" onclick="applyPromo()">ÁP DỤNG</button>
                        </div>
                        <span id="promo_msg"></span>
                    </div>

                    <div class="form-group">
                        <label>Phương thức thanh toán</label>
                        <select name="payment_method" class="form-control">
                            <option value="vnpay">Thanh toán VNPAY</option>
                            <option value="momo">Thanh toán Momo</option>
                            <option value="cash">Thanh toán tại văn phòng</option>
                        </select>
                    </div>

                    <div class="total-price-display" style="font-size: 1.2rem; font-weight: bold; margin-bottom: 15px; text-align: right; border-top: 1px solid #ccc; padding-top: 15px;">
                        Tổng tiền: <span id="total_price_text" style="color: red; font-size: 1.8rem;"><?= number_format($tour['Price'], 0, ',', '.') ?></span> VNĐ
                    </div>

                    <button type="submit" class="btn-submit-booking" style="width: 100%; padding: 15px; font-size: 1.2rem; background: #003366; color: #fff; border: none; cursor: pointer;">Tiến Hành Đặt Tour</button>
                </form>
            </div>
        </div>

    </div>

    <script>
        const priceAdult = <?= $tour['Price'] ?>;
        const priceChild = <?= $tour['PriceKid'] ?>;
        const priceShuttle = 200000; 
        let discountPercent = 0; // Lưu % giảm giá

        // Hàm kiểm tra mã giảm giá
        function applyPromo() {
            let code = document.getElementById('promo_code').value.trim().toUpperCase();
            let msgEl = document.getElementById('promo_msg');

            // Giả lập 2 mã giảm giá
            if (code === 'SALE10') {
                discountPercent = 0.10; // Giảm 10%
                msgEl.innerText = "Tuyệt vời! Bạn được giảm 10% tổng đơn.";
                msgEl.style.color = "green";
            } else if (code === 'VIP20') {
                discountPercent = 0.20; // Giảm 20%
                msgEl.innerText = "Mã VIP hợp lệ! Bạn được giảm 20% tổng đơn.";
                msgEl.style.color = "green";
            } else {
                discountPercent = 0;
                if (code !== '') {
                    msgEl.innerText = "Mã giảm giá không hợp lệ hoặc đã hết hạn!";
                    msgEl.style.color = "red";
                } else {
                    msgEl.innerText = "";
                }
            }
            
            // Gắn % giảm giá vào input ẩn để đẩy sang file PHP xử lý bill
            document.getElementById('discount_percent_input').value = discountPercent;
            
            // Tính lại tiền
            calculateTotal();
        }

        // Hàm tính toán tổng tiền
        function calculateTotal() {
            let adults = parseInt(document.getElementById('num_adults').value) || 0;
            let children = parseInt(document.getElementById('num_children').value) || 0;
            let totalPeople = adults + children; // Tổng số người
            
            // 1. Tiền Tour cơ bản
            let baseTotal = (adults * priceAdult) + (children * priceChild);
            
            // 2. Tiền Đưa đón
            let shuttleCheckbox = document.getElementById('shuttle_service');
            if (shuttleCheckbox && shuttleCheckbox.checked) {
                baseTotal += priceShuttle;
            }

            // 3. Tiền Bảo hiểm (Nhân với tổng số người)
            let insSelect = document.getElementById('insurance_id');
            let insPrice = parseInt(insSelect.options[insSelect.selectedIndex].getAttribute('data-price')) || 0;
            let totalInsPrice = insPrice * totalPeople;

            // 4. Tiền Thuê xe (Cộng thêm giá gốc của xe)
            let vehSelect = document.getElementById('vehicle_id');
            let vehPrice = parseInt(vehSelect.options[vehSelect.selectedIndex].getAttribute('data-price')) || 0;

            // TỔNG TIỀN TRƯỚC GIẢM GIÁ
            let grossTotal = baseTotal + totalInsPrice + vehPrice;

            // 5. Trừ đi Giảm giá
            let finalTotal = grossTotal - (grossTotal * discountPercent);

            // Cập nhật giao diện
            document.getElementById('total_price_text').innerText = finalTotal.toLocaleString('vi-VN');
        }

        // Gọi tính tiền lần đầu khi vừa load trang
        calculateTotal();
    </script>
</body>
</html>