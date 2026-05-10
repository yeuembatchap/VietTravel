<?php
session_start();
session_destroy(); // Lệnh này sẽ xóa toàn bộ thông tin đăng nhập của user hiện tại
header("Location: index.php"); // Xóa xong thì chuyển hướng ngay về lại trang chủ
exit;
?>