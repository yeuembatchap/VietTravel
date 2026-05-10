<?php
session_start();
// Đảm bảo đường dẫn tới file config/db.php là chính xác
require_once '../config/db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'admin') {
    exit;
}

$action = $_POST['action'] ?? '';

// MỚI: Thống nhất ID của Admin đại diện trong bảng messages luôn là '0' (dạng chuỗi)
$admin_id = '0'; 

// ==========================================
// 1. LOAD DANH SÁCH KHÁCH HÀNG (Bao gồm User & Guest)
// ==========================================
if ($action === 'fetch_users') {
    // Lấy danh sách ID độc nhất đã từng nhắn tin từ bảng messages
    $sql = "
        SELECT 
            CASE 
                WHEN sender_id = '0' THEN receiver_id 
                ELSE sender_id 
            END as chat_user_id,
            MAX(created_at) as last_msg_time
        FROM messages
        WHERE sender_id = '0' OR receiver_id = '0'
        GROUP BY chat_user_id
        ORDER BY last_msg_time DESC
    ";
    
    $stmt = $conn->query($sql);
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    foreach ($chats as $chat) {
        $u_id = $chat['chat_user_id'];
        if ($u_id === '0') continue; // Bỏ qua ID của Admin

        $name = "";
        
        // Phân biệt Guest và User đã đăng nhập
        if (strpos($u_id, 'guest_') === 0) {
            // Khách vãng lai (Cắt lấy số đuôi để làm tên)
            $parts = explode('_', $u_id);
            $guest_num = end($parts);
            $name = "Khách #" . $guest_num;
        } else {
            // Thành viên đã đăng nhập (Truy vấn lấy tên thật từ bảng users)
            $sqlUser = "SELECT Username FROM users WHERE ID = :id"; 
            $stmtUser = $conn->prepare($sqlUser);
            $stmtUser->bindParam(':id', $u_id, PDO::PARAM_STR);
            $stmtUser->execute();
            
            if ($userRow = $stmtUser->fetch(PDO::FETCH_ASSOC)) {
                $name = $userRow['Username'];
            } else {
                $name = "Thành viên #" . $u_id;
            }
        }

        $name_safe = htmlspecialchars($name);
        // Cắt lấy chữ cái đầu làm Avatar (Hỗ trợ tiếng Việt)
        $avatar = mb_strtoupper(mb_substr($name_safe, 0, 1, "UTF-8"), "UTF-8"); 
        
        // CHÚ Ý: Bọc $u_id trong dấu nháy đơn ' ' vì bây giờ ID có thể là chữ (guest_...)
        $html .= "
        <li class='user-item' data-id='{$u_id}' onclick=\"selectUser('{$u_id}', '{$name_safe}')\">
            <div class='user-avatar'>{$avatar}</div>
            <div class='user-info'>
                <p class='user-name'>{$name_safe}</p>
            </div>
        </li>";
    }
    echo $html;
}

// ==========================================
// 2. LOAD TIN NHẮN CỦA 1 KHÁCH HÀNG
// ==========================================
if ($action === 'fetch_messages') {
    // BỎ ép kiểu (int), giữ nguyên kiểu chuỗi
    $target_user_id = $_POST['user_id'] ?? ''; 
    if (!$target_user_id) exit;
    
    $sql = "SELECT * FROM messages 
            WHERE (sender_id = :target_id AND receiver_id = '0') 
               OR (sender_id = '0' AND receiver_id = :target_id)
            ORDER BY created_at ASC";
            
    $stmt = $conn->prepare($sql);
    // Đổi PARAM_INT thành PARAM_STR
    $stmt->bindParam(':target_id', $target_user_id, PDO::PARAM_STR);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    foreach ($messages as $msg) {
        $safe_msg = htmlspecialchars($msg['message']);
        
        if ((string)$msg['sender_id'] === (string)$target_user_id) {
            // Khách gửi
            $html .= "<div class='msg-bubble msg-user'><p>{$safe_msg}</p></div>";
        } else {
            // Admin gửi
            $html .= "<div class='msg-bubble msg-admin'><p>{$safe_msg}</p></div>";
        }
    }
    echo $html;
}

// ==========================================
// 3. ADMIN GỬI TIN NHẮN TRẢ LỜI
// ==========================================
if ($action === 'send_message') {
    // BỎ ép kiểu (int)
    $target_user_id = $_POST['user_id'] ?? ''; 
    $message = trim($_POST['message'] ?? '');

    if ($message !== '' && $target_user_id !== '') {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                VALUES ('0', :receiver_id, :message, NOW())";
                
        $stmt = $conn->prepare($sql);
        // Đổi PARAM_INT thành PARAM_STR
        $stmt->bindParam(':receiver_id', $target_user_id, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->execute();
    }
}
?>