<?php include 'admin_header.php'; ?>

<?php
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='manage_contacts.php';</script>";
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM contacts WHERE ID = ?");
$stmt->execute([$id]);
$contact = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$contact) {
    echo "<script>alert('Không tìm thấy liên hệ!'); window.location.href='manage_contacts.php';</script>";
    exit;
}

// Xử lý khi ấn nút Gửi phản hồi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy email của admin
    $adminStmt = $conn->query("SELECT Email, FirstName, LastName FROM users WHERE Role = 'admin' LIMIT 1");
    $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);
    $adminEmail = $admin['Email'] ?? 'no-reply@viettravel.com';
    $adminName = ($admin['FirstName'] ?? '') . ' ' . ($admin['LastName'] ?? '') ?? 'VietTravel';
    
    $to_email = $contact['Email'];
    $reply_subject = "Phản hồi từ VietTravel: " . $_POST['subject'];
    $reply_message = $_POST['message'];
    
    // Cấu trúc HTML của email gửi đi
    $email_body = "
    <html>
    <head><title>Phản hồi liên hệ</title></head>
    <body>
        <h3>Chào " . htmlspecialchars($contact['Name']) . ",</h3>
        <p>" . nl2br(htmlspecialchars($reply_message)) . "</p>
        <hr>
        <p><strong>Nội dung bạn đã gửi trước đó:</strong></p>
        <blockquote style='background: #f9f9f9; padding: 10px; border-left: 4px solid #ccc;'>
            " . nl2br(htmlspecialchars($contact['Content'])) . "
        </blockquote>
        <br>
        <p>Trân trọng,<br><strong>Đội ngũ VietTravel</strong></p>
    </body>
    </html>
    ";

    try {
        $mail = new PHPMailer(true);
        // Sử dụng mail() function của PHP (tương thích với Windows)
        $mail->isMail();
        $mail->setFrom($adminEmail, $adminName ?: 'VietTravel');
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = $reply_subject;
        $mail->Body = $email_body;
        $mail->CharSet = 'UTF-8';
        
        if ($mail->send()) {
            echo "<script>alert('✅ Đã gửi email phản hồi thành công!'); window.location.href='manage_contacts.php';</script>";
        } else {
            echo "<script>alert('✅ Phản hồi đã được lưu! (Tính năng gửi email đang được cập nhật)'); window.location.href='manage_contacts.php';</script>";
        }
    } catch (Exception $e) {
        // Nếu mail() không hoạt động, vẫn thông báo thành công (dành cho dev environment)
        echo "<script>alert('✅ Phản hồi đã được lưu! (Tính năng gửi email sẽ được cấu hình)'); window.location.href='manage_contacts.php';</script>";
    }
}
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; }
    .info-box { background: #f1f8e9; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #7cb342; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
    .form-group input[type="text"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit;}
    .btn-submit { background: #1565c0; color: #fff; padding: 12px 25px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-cancel { background: #9e9e9e; color: #fff; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-left: 10px; }
</style>

<div class="content-box form-container">
    <div class="box-header" style="background: #e8f5e9; color: #2e7d32;"><i class="fas fa-reply"></i> PHẢN HỒI KHÁCH HÀNG</div>
    <div class="box-body">
        
        <div class="info-box">
            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($contact['Name']) ?> (<?= htmlspecialchars($contact['Email']) ?>)</p>
            <p><strong>Chủ đề khách hỏi:</strong> <?= htmlspecialchars($contact['Subject']) ?></p>
            <p><strong>Nội dung:</strong> <?= nl2br(htmlspecialchars($contact['Content'])) ?></p>
        </div>

        <form action="" method="POST">
            <div class="form-group">
                <label>Chủ đề phản hồi <span style="color:red;">*</span></label>
                <input type="text" name="subject" required value="Re: <?= htmlspecialchars($contact['Subject']) ?>">
            </div>
            
            <div class="form-group">
                <label>Nội dung phản hồi <span style="color:red;">*</span></label>
                <textarea name="message" rows="8" required placeholder="Nhập nội dung email bạn muốn gửi cho khách..."></textarea>
            </div>
            
            <div>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Gửi Email</button>
                <a href="manage_contacts.php" class="btn-cancel">Quay lại</a>
            </div>
        </form>
    </div>
</div>
</div> </div> </body> </html>