<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$response = ['success' => false, 'message' => 'Lỗi không xác định'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        
        // Validate
        if (empty($name) || empty($email) || empty($message)) {
            $response['message'] = 'Vui lòng điền đầy đủ thông tin!';
            echo json_encode($response);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Email không hợp lệ!';
            echo json_encode($response);
            exit;
        }
        
        // Insert vào bảng contacts (chỉ dùng các cột thực tế)
        $stmt = $conn->prepare("INSERT INTO contacts (Name, Email, Subject, Content) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([
            $name,
            $email,
            'Liên hệ từ footer',
            $message
        ]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Cảm ơn bạn! Chúng tôi sẽ liên hệ lại sớm.';
        } else {
            $response['message'] = 'Không thể lưu liên hệ. Vui lòng thử lại!';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Lỗi CSDL: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = 'Lỗi: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
