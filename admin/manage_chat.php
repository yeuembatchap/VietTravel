<?php include 'admin_header.php'; ?>

<style>
    /* CSS chia layout 2 cột cho Chat */
    .chat-container { display: flex; height: calc(100vh - 120px); background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; margin-top: 10px; }
    
    /* Cột bên trái: Danh sách khách hàng */
    .chat-sidebar { width: 300px; border-right: 1px solid #ddd; display: flex; flex-direction: column; background: #fafafa; }
    .chat-sidebar-header { padding: 15px; background: #f0f0f0; border-bottom: 1px solid #ddd; font-weight: bold; color: #333; }
    .user-list { flex: 1; overflow-y: auto; list-style: none; padding: 0; margin: 0; }
    .user-item { padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 10px; }
    .user-item:hover, .user-item.active { background: #e8f5e9; }
    .user-avatar { width: 40px; height: 40px; background: #ccc; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; background-color: #885b6e; }
    .user-info { flex: 1; overflow: hidden; }
    .user-name { font-weight: bold; color: #333; margin: 0; font-size: 14px; }
    
    /* Cột bên phải: Khung chat */
    .chat-main { flex: 1; display: flex; flex-direction: column; background: #fff; }
    .chat-header { padding: 15px 20px; background: #fff; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 16px; color: #003366; display: flex; align-items: center; gap: 10px; }
    .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; background: #f9f9f9; }
    
    /* Tin nhắn */
    .msg-bubble { max-width: 60%; padding: 10px 15px; border-radius: 15px; font-size: 14px; line-height: 1.4; }
    .msg-bubble p { margin: 0; }
    .msg-admin { background: #007bff; color: #fff; align-self: flex-end; border-bottom-right-radius: 2px; } /* Admin gửi */
    .msg-user { background: #e0e0e0; color: #333; align-self: flex-start; border-bottom-left-radius: 2px; } /* Khách gửi */
    
    /* Vùng nhập tin nhắn */
    .chat-input-area { padding: 15px; border-top: 1px solid #ddd; display: flex; gap: 10px; background: #fff; }
    .chat-input-area input { flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 25px; outline: none; }
    .chat-input-area button { background: #27ae60; color: white; border: none; width: 45px; height: 45px; border-radius: 50%; cursor: pointer; transition: 0.2s; }
    .chat-input-area button:hover { background: #219150; }
    
    /* Trạng thái trống */
    .empty-chat { flex: 1; display: flex; align-items: center; justify-content: center; color: #888; flex-direction: column; gap: 10px; }
    .empty-chat i { font-size: 50px; color: #ccc; }
</style>

<h2 class="page-title"><i class="fas fa-comments"></i> Hỗ trợ Khách hàng Trực tuyến</h2>

<div class="chat-container">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">Danh sách hội thoại</div>
        <ul class="user-list" id="userList">
            </ul>
    </div>

    <div class="chat-main" id="chatMain">
        <div class="empty-chat">
            <i class="fas fa-comment-dots"></i>
            <p>Chọn một khách hàng bên trái để bắt đầu trò chuyện</p>
        </div>
    </div>
</div>

<script>
    let activeUserId = null;

    // 1. Lấy danh sách khách hàng đã từng nhắn tin
    function fetchUsers() {
        fetch('ajax_admin_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=fetch_users'
        })
        .then(res => res.text())
        .then(html => {
            document.getElementById('userList').innerHTML = html;
            // Giữ trạng thái active cho user đang được chọn
            if (activeUserId) {
                let activeEl = document.querySelector(`.user-item[data-id="${activeUserId}"]`);
                if (activeEl) activeEl.classList.add('active');
            }
        });
    }

    // 2. Chọn 1 khách hàng và load tin nhắn
    function selectUser(userId, userName) {
        activeUserId = userId;
        
        // Cập nhật giao diện active
        document.querySelectorAll('.user-item').forEach(el => el.classList.remove('active'));
        document.querySelector(`.user-item[data-id="${userId}"]`).classList.add('active');

        // Setup khung chat bên phải
        document.getElementById('chatMain').innerHTML = `
            <div class="chat-header">
                <i class="fas fa-user-circle" style="font-size: 24px; color: #885b6e;"></i> 
                ${userName}
            </div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input-area">
                <input type="text" id="adminMsgInput" placeholder="Nhập tin nhắn trả lời..." onkeypress="handleEnter(event)">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        `;
        fetchMessages();
    }

    // 3. Lấy nội dung tin nhắn của user đang chọn
    function fetchMessages() {
        if (!activeUserId) return;
        fetch('ajax_admin_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=fetch_messages&user_id=${activeUserId}`
        })
        .then(res => res.text())
        .then(html => {
            let box = document.getElementById('chatMessages');
            if (box && html.trim() !== "") {
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight; // Cuộn xuống cuối
            }
        });
    }

    // 4. Admin gửi tin nhắn
    function handleEnter(e) { if(e.key === 'Enter') sendMessage(); }

    function sendMessage() {
        let input = document.getElementById('adminMsgInput');
        let msg = input.value.trim();
        if (msg === '' || !activeUserId) return;

        // In tạm ra màn hình cho mượt
        let box = document.getElementById('chatMessages');
        box.innerHTML += `<div class="msg-bubble msg-admin"><p>${msg}</p></div>`;
        input.value = '';
        box.scrollTop = box.scrollHeight;

        // Gửi xuống server
        fetch('ajax_admin_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&user_id=${activeUserId}&message=${encodeURIComponent(msg)}`
        });
    }

    // Lặp tự động
    fetchUsers();
    setInterval(() => {
        fetchUsers();
        fetchMessages();
    }, 2000);
</script>

</div> </body>
</html>