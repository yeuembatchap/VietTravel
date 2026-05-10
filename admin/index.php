<?php
session_start();
require_once '../config/db.php'; // Chỉnh lại đường dẫn nếu cần

// ==========================================
// 1. TÍNH NĂNG XUẤT FILE EXCEL (CSV)
// ==========================================
if (isset($_GET['export']) && $_GET['export'] == 'revenue') {
    // Đặt header để trình duyệt hiểu đây là file tải về
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=DoanhThu_' . date('Y-m-d') . '.csv');
    
    $output = fopen("php://output", "w");
    // Thêm BOM để Excel đọc tiếng Việt không bị lỗi font
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Tiêu đề các cột
    fputcsv($output, ['Mã Đơn', 'Khách Hàng', 'Số Điện Thoại', 'Ngày Đặt', 'Tổng Tiền (VNĐ)', 'Thanh Toán']);
    
    // Lấy dữ liệu các đơn đã thanh toán
    $sqlExport = "SELECT BookingCode, CustomerName, CustomerPhone, CreatedAt, FinalPrice, PaymentStatus 
                  FROM bookings 
                  ORDER BY CreatedAt DESC";
    $stmtExport = $conn->query($sqlExport);
    
    while ($row = $stmtExport->fetch(PDO::FETCH_ASSOC)) {
        // Chuyển đổi trạng thái sang tiếng Việt
        $statusText = ($row['PaymentStatus'] == 'paid') ? 'Đã thanh toán' : (($row['PaymentStatus'] == 'pending') ? 'Chờ thanh toán' : 'Thất bại');
        
        fputcsv($output, [
            $row['BookingCode'],
            $row['CustomerName'],
            $row['CustomerPhone'],
            $row['CreatedAt'],
            $row['FinalPrice'],
            $statusText
        ]);
    }
    fclose($output);
    exit(); // Dừng chạy mã bên dưới khi đang xuất file
}

// Nhúng header giao diện (Đảm bảo file này nằm DƯỚI phần code xử lý xuất file)
include 'admin_header.php'; 

// ==========================================
// 2. LẤY DỮ LIỆU THỐNG KÊ TỔNG QUAN
// ==========================================

// Tổng doanh thu (Chỉ tính đơn 'paid')
$revStmt = $conn->query("SELECT SUM(FinalPrice) as total FROM bookings WHERE PaymentStatus = 'paid'");
$totalRevenue = $revStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Tổng số đơn đặt hàng
$bookStmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
$totalBookings = $bookStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Số đơn chưa thanh toán
$pendingStmt = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE PaymentStatus = 'pending'");
$totalPending = $pendingStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ==========================================
// 3. LẤY DỮ LIỆU VẼ BIỂU ĐỒ (7 NGÀY GẦN NHẤT)
// ==========================================
$chartSql = "
    SELECT DATE(CreatedAt) as date, SUM(FinalPrice) as daily_revenue 
    FROM bookings 
    WHERE PaymentStatus = 'paid' AND CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(CreatedAt)
    ORDER BY DATE(CreatedAt) ASC
";
$chartStmt = $conn->query($chartSql);
$chartData = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị mảng để đưa vào JavaScript
$labels = [];
$revenues = [];
foreach ($chartData as $data) {
    $labels[] = date('d/m', strtotime($data['date'])); // Chỉ hiển thị ngày/tháng cho gọn
    $revenues[] = $data['daily_revenue'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 15px; }
    .card-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #fff; }
    .bg-green { background: #2ecc71; }
    .bg-blue { background: #3498db; }
    .bg-orange { background: #f39c12; }
    .card-info h3 { margin: 0; font-size: 14px; color: #7f8c8d; text-transform: uppercase; }
    .card-info p { margin: 5px 0 0; font-size: 24px; font-weight: bold; color: #2c3e50; }
    .chart-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn-export { background: #27ae60; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
    .btn-export:hover { background: #219150; color: #fff; }
</style>

<div class="content-box">
    <div class="header-actions">
        <h2 class="page-title" style="margin:0;"><i class="fas fa-tachometer-alt"></i> Tổng Quan Thống Kê</h2>
        <a href="index.php?export=revenue" class="btn-export"><i class="fas fa-file-excel"></i> Xuất file doanh thu</a>
    </div>

    <div class="dashboard-cards">
        <div class="card">
            <div class="card-icon bg-green"><i class="fas fa-money-bill-wave"></i></div>
            <div class="card-info">
                <h3>Doanh thu thực tế</h3>
                <p><?= number_format($totalRevenue, 0, ',', '.') ?> ₫</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-icon bg-blue"><i class="fas fa-ticket-alt"></i></div>
            <div class="card-info">
                <h3>Tổng lượt Booking</h3>
                <p><?= number_format($totalBookings) ?> đơn</p>
            </div>
        </div>

        <div class="card">
            <div class="card-icon bg-orange"><i class="fas fa-clock"></i></div>
            <div class="card-info">
                <h3>Đơn chờ thanh toán</h3>
                <p><?= number_format($totalPending) ?> đơn</p>
            </div>
        </div>
    </div>

    <div class="chart-container">
        <h3><i class="fas fa-chart-line"></i> Biểu đồ doanh thu 7 ngày gần nhất</h3>
        <canvas id="revenueChart" style="max-height: 400px; width: 100%;"></canvas>
    </div>
</div>

<script>
    // Lấy mảng dữ liệu từ PHP nạp vào Javascript
    const chartLabels = <?= json_encode($labels) ?>;
    const chartRevenues = <?= json_encode($revenues) ?>;

    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'bar', // Có thể đổi thành 'line' nếu bạn thích dạng đường biểu diễn
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: chartRevenues,
                backgroundColor: 'rgba(52, 152, 219, 0.5)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw.toLocaleString('vi-VN') + ' ₫';
                        }
                    }
                }
            }
        }
    });
</script>

</div></div></body></html>