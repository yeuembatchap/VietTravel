<?php
session_start();
require_once '../config/db.php';

// Truy vấn lấy danh sách xe đang hoạt động
$stmt = $conn->prepare("SELECT * FROM vehicles WHERE Status = 1");
$stmt->execute();
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thuê Xe Du Lịch - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css"> 
</head>
<body>

<?php include '../header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 1200px; margin: auto;">
    <h2 style="text-align: center; color: #003366; margin-bottom: 30px;">DỊCH VỤ THUÊ XE DU LỊCH</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <?php foreach($vehicles as $xe): ?>
            <?php
                $vehicleImage = $xe['Image'];
                if (empty($vehicleImage)) {
                    $vehicleImgSrc = 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&w=600&q=80';
                } elseif (strpos($vehicleImage, 'http') === 0) {
                    $vehicleImgSrc = $vehicleImage;
                } else {
                    $vehicleImgSrc = '../uploads/' . $vehicleImage;
                }
            ?>
            <div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background: #fff;">
                <img src="<?= htmlspecialchars($vehicleImgSrc) ?>" alt="<?= htmlspecialchars($xe['Name']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                
                <div style="padding: 15px;">
                    <h3 style="margin-top: 0; color: #d35400;"><?= htmlspecialchars($xe['Name']) ?></h3>
                    <p><strong>Số chỗ:</strong> <?= $xe['Seats'] ?> chỗ</p>
                    <p><strong>Tài xế:</strong> <?= $xe['HasDriver'] ? 'Có tài xế' : 'Tự lái' ?></p>
                    <p><strong>Giá thuê:</strong> <span style="color: red; font-weight: bold; font-size: 1.2rem;"><?= number_format($xe['PricePerDay'], 0, ',', '.') ?> VNĐ</span>/ngày</p>
                    <a href="book_vehicle.php?vehicle_id=<?= $xe['ID'] ?>" style="display: block; text-align: center; width: 100%; padding: 10px; background: #8bc34a; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 10px; box-sizing: border-box;">ĐẶT XE NGAY</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>