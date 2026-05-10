<footer class="main-footer">
    <div class="container footer-grid">
        <div class="footer-column">
            <a href="index.php" class="logo footer-logo">VIET<span>TRAVEL</span></a>
            <p class="footer-desc">
                Tự hào là đơn vị lữ hành hàng đầu, mang đến cho bạn những hành trình khám phá di sản và vẻ đẹp bất tận của Việt Nam.
            </p>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>

        <div class="footer-column">
            <h3>Liên Kết</h3>
            <ul class="footer-links">
                <li><a href="index.php">Trang chủ</a></li>
                <li><a href="tours.php">Tour du lịch</a></li>
                <li><a href="news.php">Cẩm nang du lịch</a></li>
                <li><a href="about.php">Về chúng tôi</a></li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Liên Hệ</h3>
            <ul class="contact-info">
                <li><i class="fas fa-map-marker-alt"></i> 123 Đường ABC, Nha Trang, Khánh Hòa</li>
                <li><i class="fas fa-phone-alt"></i> +84 123 456 789</li>
                <li><i class="fas fa-envelope"></i> info@viettravel.com</li>
                <li><i class="fas fa-clock"></i> 08:00 - 21:00 (Hàng ngày)</li>
            </ul>
        </div>

        <div class="footer-column">
            <h3>Gửi Tin Nhắn</h3>
            <p>Liên hệ trực tiếp với chúng tôi</p>
            <form class="contact-form" id="footerContactForm">
                <input type="text" name="name" placeholder="Họ và tên..." required>
                <input type="email" name="email" placeholder="Email của bạn..." required>
                <textarea name="message" placeholder="Tin nhắn..." rows="3" required></textarea>
                <button type="submit"><i class="fas fa-paper-plane"></i> Gửi</button>
            </form>
            <div id="contactMessage" style="margin-top: 10px; display: none;"></div>
        </div>

        <div class="footer-column">
            <h3>Bản Tin</h3>
            <p>Đăng ký để nhận ưu đãi tour mới nhất.</p>
            <form class="newsletter-form">
                <input type="email" placeholder="Email của bạn...">
                <button type="submit"><i class="fas fa-paper-plane"></i></button>
            </form>
        </div>
    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>Thiết kế bởi LeTienDat</p>
        </div>
    </div>
</footer>

<?php 
// SỬA LẠI ĐIỀU KIỆN: Hiển thị cho tất cả mọi người, chỉ ẨN đi nếu đang là Admin
$is_admin = isset($_SESSION['user']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';

if (!$is_admin): 
?>
<div class="chat-widget">
    <button class="chat-toggle" onclick="toggleChat()">
        <i class="fas fa-comments"></i> Chat với Admin
    </button>

    <div class="chat-box" id="chatBox">
        <div class="chat-header">
            <h4><i class="fas fa-headset"></i> Hỗ trợ trực tuyến</h4>
            <button onclick="toggleChat()" class="close-chat"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="chat-body" id="chatBody">
            <div class="chat-msg admin">
                <p>Chào bạn! Mình có thể giúp gì cho bạn hôm nay?</p>
            </div>
        </div>

        <div class="chat-footer">
            <input type="text" id="chatInput" placeholder="Nhập tin nhắn..." onkeypress="handleEnter(event)">
            <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
    // 1. Đóng mở khung chat
    function toggleChat() {
        var box = document.getElementById('chatBox');
        if (box.style.display === 'flex') {
            box.style.display = 'none';
        } else {
            box.style.display = 'flex';
            scrollToBottom();
            fetchMessages(); // Load tin nhắn khi vừa mở lên
        }
    }

    // 2. Cuộn xuống cuối cùng của khung chat
    function scrollToBottom() {
        var body = document.getElementById('chatBody');
        body.scrollTop = body.scrollHeight;
    }

    // 3. Gửi tin nhắn bằng phím Enter
    function handleEnter(e) {
        if(e.key === 'Enter') {
            sendMessage();
        }
    }

    // 4. AJAX: Gửi tin nhắn lên server
    function sendMessage() {
        var input = document.getElementById('chatInput');
        var msg = input.value.trim();
        if(msg === '') return;

        // Thêm ngay tin nhắn vào màn hình cho mượt
        var chatBody = document.getElementById('chatBody');
        chatBody.innerHTML += `<div class="chat-msg user"><p>${msg}</p></div>`;
        input.value = '';
        scrollToBottom();

        // Gửi ngầm xuống PHP
        var formData = new FormData();
        formData.append('message', msg);

        fetch('ajax_chat_send.php', {
            method: 'POST',
            body: formData
        }).then(response => response.text())
          .then(data => {
              // Gửi thành công thì không cần làm gì thêm, fetchMessages sẽ tự lấy về
          });
    }

    // 5. AJAX: Lấy tin nhắn mới mỗi 2 giây (Real-time)
    function fetchMessages() {
        var box = document.getElementById('chatBox');
        if(box.style.display !== 'flex') return; // Không gọi nếu đang ẩn khung chat

        fetch('ajax_chat_fetch.php')
            .then(response => response.text())
            .then(html => {
                var chatBody = document.getElementById('chatBody');
                // Chỉ cập nhật nếu có nội dung (tránh giật màn hình)
                if(html.trim() !== "") {
                    chatBody.innerHTML = html;
                    scrollToBottom();
                }
            });
    }

    // Lặp lại việc lấy tin nhắn mỗi 2 giây
    setInterval(fetchMessages, 2000);

    // 6. Xử lý biểu mẫu liên hệ
    document.getElementById('footerContactForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const messageDiv = document.getElementById('contactMessage');
        
        fetch('handle_contact_form.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            messageDiv.style.display = 'block';
            if(data.success) {
                messageDiv.innerHTML = '<span style="color: #4caf50; font-weight: bold;">✅ ' + data.message + '</span>';
                document.getElementById('footerContactForm').reset();
            } else {
                messageDiv.innerHTML = '<span style="color: #f44336; font-weight: bold;">❌ ' + data.message + '</span>';
            }
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 4000);
        })
        .catch(error => {
            messageDiv.style.display = 'block';
            messageDiv.innerHTML = '<span style="color: #f44336; font-weight: bold;">❌ Lỗi kết nối!</span>';
        });
    });
</script>
<?php endif; ?>

<style>
    .contact-form {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .contact-form input,
    .contact-form textarea {
        padding: 8px 10px;
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 4px;
        background: rgba(255,255,255,0.1);
        color: #fff;
        font-size: 13px;
        font-family: inherit;
        transition: 0.3s;
    }

    .contact-form input::placeholder,
    .contact-form textarea::placeholder {
        color: rgba(255,255,255,0.6);
    }

    .contact-form input:focus,
    .contact-form textarea:focus {
        outline: none;
        background: rgba(255,255,255,0.15);
        border-color: rgba(255,255,255,0.5);
    }

    .contact-form button {
        padding: 10px;
        background: linear-gradient(135deg, #1565c0, #0d47a1);
        border: none;
        border-radius: 4px;
        color: #fff;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
        font-size: 13px;
    }

    .contact-form button:hover {
        background: linear-gradient(135deg, #0d47a1, #0a3d91);
        transform: translateY(-2px);
    }
</style>