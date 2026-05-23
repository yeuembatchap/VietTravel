<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['Role'] !== 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập!'); window.location.href='../client/index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Quản Trị - VietTravel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { margin: 0; display: flex; height: 100vh; background-color: #f4f6f9; overflow: hidden; }
        
        /* === THANH MENU TRÁI (SIDEBAR) === */
        .sidebar { width: 260px; background-color: #5d4a4f; color: #fff; display: flex; flex-direction: column; transition: 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); z-index: 100; }
        .sidebar .logo { background-color: #724c5e; padding: 15px 20px; font-size: 24px; font-weight: 300; display: flex; justify-content: space-between; align-items: center; letter-spacing: 2px; transition: 0.3s; }
        .sidebar .logo i { cursor: pointer; font-size: 20px; transition: 0.2s; }
        .sidebar .logo i:hover { color: #e8a798; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; overflow-y: auto; flex: 1; }
        .sidebar ul li a { display: flex; align-items: center; padding: 15px 20px; color: #e0e0e0; text-decoration: none; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.05); transition: 0.2s; }
        .sidebar ul li a i { width: 30px; text-align: left; font-size: 16px; }
        .sidebar ul li a:hover, .sidebar ul li a.active { background-color: rgba(255,255,255,0.1); color: #fff; border-left: 4px solid #fff; padding-left: 16px; }
        
        /* === HIỆU ỨNG THU GỌN MENU (COLLAPSED) === */
        .sidebar.collapsed { width: 70px; }
        .sidebar.collapsed .logo { justify-content: center; padding: 15px 0; }
        .sidebar.collapsed .logo-text { display: none; } /* Ẩn chữ TRAVEL */
        .sidebar.collapsed ul li a span { display: none; } /* Ẩn chữ trong menu */
        .sidebar.collapsed ul li a { justify-content: center; padding: 15px 0; }
        .sidebar.collapsed ul li a i { width: auto; text-align: center; font-size: 20px; margin: 0; }
        .sidebar.collapsed ul li a:hover, .sidebar.collapsed ul li a.active { border-left: none; background-color: rgba(255,255,255,0.2); padding-left: 0; }

        /* === KHU VỰC NỘI DUNG CHÍNH === */
        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; transition: 0.3s; }
        
        .topbar { background: #e8a798; height: 55px; display: flex; align-items: center; justify-content: flex-end; padding: 0 20px; color: #fcfcfc; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .topbar .user-profile { background: rgba(0,0,0,0.2); padding: 5px 15px; border-radius: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; }
        
        /* Khu vực hiển thị bảng dữ liệu (Content) */
        .content { flex: 1; padding: 20px; overflow-y: auto; }
        .content-box { background: #fff; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .box-header { background-color: #e8f5e9; color: #2e7d32; padding: 15px; text-align: center; font-weight: bold; font-size: 16px; text-transform: uppercase; border-bottom: 1px solid #c8e6c9; }
        .box-body { padding: 20px; }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
</head>
<body>

<div class="sidebar" id="mySidebar">
    <div class="logo">
        <span class="logo-text">TRAVEL</span> 
        <i class="fas fa-bars" onclick="toggleSidebar()" title="Đóng/Mở Menu"></i>
    </div>
    <ul>
        <li><a href="index.php"><i class="fas fa-chart-pie"></i> <span>Bảng điều khiển</span></a></li>
        <li><a href="manage_chat.php"><i class="fas fa-comments"></i> <span>Hỗ trợ Chat</span></a></li>
        <li><a href="manage_bookings.php"><i class="fas fa-file-invoice-dollar"></i> <span>Quản lý Đơn hàng</span></a></li>
        <li><a href="manage_tours.php"><i class="fas fa-map-marked-alt"></i> <span>Quản lý Tour</span></a></li>
        <li><a href="manage_hotels.php"><i class="fas fa-hotel"></i> <span>Quản lý Khách sạn</span></a></li>
        <li><a href="manage_users.php"><i class="fas fa-users"></i> <span>Quản lý Khách hàng</span></a></li>
        <li><a href="manage_cars.php"><i class="fas fa-car"></i> <span>Quản lý Đặt xe</span></a></li>
        <li><a href="manage_insurance.php"><i class="fas fa-shield-alt"></i> <span>Quản lý Bảo hiểm</span></a></li>
        <li><a href="manage_posts.php"><i class="fas fa-newspaper"></i> <span>Quản lý Bài viết</span></a></li>
        <li><a href="manage_contacts.php"><i class="fas fa-envelope"></i> <span>Quản lý Liên hệ</span></a></li>
        <li><a href="../client/index.php" style="color: #ff9e9e;"><i class="fas fa-sign-out-alt"></i> <span>Về trang Web</span></a></li>
    </ul>
</div>

<div class="main-wrapper">
    <div class="topbar">
        <div class="user-profile">
            <i class="fas fa-user-circle" style="font-size: 18px;"></i> 
            <?= htmlspecialchars($_SESSION['user']['Username'] ?? $_SESSION['user']['username'] ?? 'Admin') ?>
        </div>
    </div>
    
    <div class="content">

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('mySidebar');
        sidebar.classList.toggle('collapsed'); // Bật/tắt class 'collapsed'
        
        // Lưu lựa chọn của người dùng vào trình duyệt
        if(sidebar.classList.contains('collapsed')) {
            localStorage.setItem('menu_state', 'collapsed');
        } else {
            localStorage.setItem('menu_state', 'expanded');
        }
    }

    // Khi trang vừa load lên, kiểm tra xem trước đó user có thu gọn menu không
    window.addEventListener('DOMContentLoaded', () => {
        if(localStorage.getItem('menu_state') === 'collapsed') {
            document.getElementById('mySidebar').classList.add('collapsed');
        }
    });
</script>