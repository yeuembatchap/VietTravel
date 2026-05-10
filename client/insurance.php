<?php
session_start();
require_once '../config/db.php';

// Truy vấn lấy danh sách gói bảo hiểm
$stmt = $conn->prepare("SELECT * FROM insurance_packages WHERE Status = 1");
$stmt->execute();
$insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gói Bảo Hiểm - VietTravel</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include '../header.php'; ?>

<div class="container" style="padding: 40px 20px; max-width: 1000px; margin: auto;">
    <h2 style="text-align: center; color: #003366; margin-bottom: 30px;">CÁC GÓI BẢO HIỂM DU LỊCH</h2>
    
    <div style="display: flex; flex-direction: column; gap: 15px;">
        <?php foreach($insurances as $bh): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; border: 1px solid #8bc34a; padding: 20px; border-radius: 8px; background: #f9fbe7; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <div>
                    <h3 style="margin: 0 0 10px 0; color: #33691e;"><?= htmlspecialchars($bh['Name']) ?></h3>
                    <p style="margin: 0; color: #555;"><?= htmlspecialchars($bh['Description']) ?></p>
                </div>
                <div style="text-align: right; min-width: 150px;">
                    <div style="color: #e65100; font-size: 1.4rem; font-weight: bold; margin-bottom: 10px;">
                        <?= number_format($bh['PricePerPerson'], 0, ',', '.') ?>đ <span style="font-size: 0.9rem; color: #666;">/người</span>
                    </div>
                    <a href="book_insurance.php?insurance_id=<?= $bh['ID'] ?>" style="display: inline-block; padding: 8px 15px; background: #e65100; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; text-align: center;">ĐĂNG KÝ</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'footer.php'; ?>
</body>
</html>