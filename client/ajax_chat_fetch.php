<?php
session_start();
require_once '../config/db.php';

// Xác định ID cần lấy tin nhắn
$chat_id = null;

if (isset($_SESSION['user'])) {
    $chat_id = (string)$_SESSION['user']['id'];
} else if (isset($_SESSION['guest_id'])) {
    $chat_id = $_SESSION['guest_id'];
}

// Nếu có chat_id (tức là user hoặc khách đã từng nhắn tin) thì mới truy vấn
if ($chat_id !== null) {
    try {
        $sql = "SELECT * FROM messages 
                WHERE (sender_id = :chat_id AND receiver_id = '0') 
                   OR (receiver_id = :chat_id) 
                ORDER BY created_at ASC";
                
        $stmt = $conn->prepare($sql);
        // Ép kiểu PARAM_STR
        $stmt->bindParam(':chat_id', $chat_id, PDO::PARAM_STR);
        $stmt->execute();
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<div class="chat-msg admin"><p>Chào bạn! Mình có thể giúp gì cho bạn hôm nay?</p></div>';

        foreach ($messages as $msg) {
            $safe_message = htmlspecialchars($msg['message']);

            // Kiểm tra xem ai là người gửi
            if ($msg['sender_id'] === $chat_id) {
                $html .= '<div class="chat-msg user"><p>' . $safe_message . '</p></div>';
            } else {
                $html .= '<div class="chat-msg admin"><p>' . $safe_message . '</p></div>';
            }
        }

        echo $html;

    } catch (PDOException $e) {
        echo "Lỗi truy vấn";
    }
} else {
    // Nếu là khách mới tinh chưa có session guest_id, chỉ in câu chào mặc định
    echo '<div class="chat-msg admin"><p>Chào bạn! Mình có thể giúp gì cho bạn hôm nay?</p></div>';
}
?>