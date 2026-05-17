<?php
session_start();
require_once '../config/db.php';

// Nhúng file tự động load thư viện của Composer
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user'])) {
    echo "<script>
            alert('Vui lòng đăng nhập để tiến hành đặt Tour!');
            window.location.href = 'login.php';
          </script>";
    exit();
}

// 2. KIỂM TRA REQUEST POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Lấy thông tin từ form
    $userID = $_SESSION['user']['id'];
    $tourID = isset($_POST['tour_id']) ? (int)$_POST['tour_id'] : 0;
    
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    $adults = isset($_POST['adults']) ? (int)$_POST['adults'] : 1;
    $children = isset($_POST['children']) ? (int)$_POST['children'] : 0;
    $totalPeople = $adults + $children;
    
    $insuranceID = isset($_POST['insurance_id']) ? (int)$_POST['insurance_id'] : NULL;
    $vehicleID = isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : NULL;
    $hasShuttle = isset($_POST['shuttle']) ? 1 : 0;
    
    $paymentMethod = $_POST['payment_method'];
    $promoCode = $_POST['promo_code'] ?? '';
    $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;

    // Chuẩn hóa cho Database
    if ($insuranceID == 0) $insuranceID = NULL;
    if ($vehicleID == 0) $vehicleID = NULL;
    
    // Database của bạn chỉ nhận 'cod' hoặc 'vnpay', ta quy đổi 'cash' và 'momo' thành 'cod'
    $paymentMethodDB = ($paymentMethod == 'vnpay') ? 'vnpay' : 'cod';

    // =========================================================================
    // 3. TÍNH TOÁN LẠI TỔNG TIỀN Ở BACKEND ĐỂ BẢO MẬT
    // =========================================================================
    $grossTotal = 0;
    $insuranceFee = 0;
    $tourName = '';

    // A. Tiền Tour
    $stmtTour = $conn->prepare("SELECT Name, Price, PriceKid FROM tours WHERE ID = ?");
    $stmtTour->execute([$tourID]);
    $tour = $stmtTour->fetch(PDO::FETCH_ASSOC);
    if($tour) {
        $tourName = $tour['Name'];
        $grossTotal += ($adults * $tour['Price']) + ($children * $tour['PriceKid']);
    }

    // B. Tiền Đưa đón
    if ($hasShuttle) {
        $grossTotal += 200000;
    }

    // C. Tiền Bảo hiểm
    if ($insuranceID) {
        $stmtIns = $conn->prepare("SELECT PricePerPerson FROM insurance_packages WHERE ID = ?");
        $stmtIns->execute([$insuranceID]);
        $ins = $stmtIns->fetch(PDO::FETCH_ASSOC);
        if($ins) {
            $insuranceFee = $ins['PricePerPerson'] * $totalPeople;
            $grossTotal += $insuranceFee;
        }
    }

    // D. Tiền Thuê xe
    if ($vehicleID) {
        $stmtVeh = $conn->prepare("SELECT PricePerDay FROM vehicles WHERE ID = ?");
        $stmtVeh->execute([$vehicleID]);
        $veh = $stmtVeh->fetch(PDO::FETCH_ASSOC);
        if($veh) {
            $grossTotal += $veh['PricePerDay'];
        }
    }

    // E. Áp dụng mã giảm giá
    if ($discountPercent > 0.20) {
        $discountPercent = 0; 
    }
    
    $discountAmount = $grossTotal * $discountPercent;
    $finalTotal = $grossTotal - $discountAmount;

    // =========================================================================
    // 4. LƯU VÀO DATABASE
    // =========================================================================
    $bookingCode = 'VT-' . time() . rand(10, 99);
    $paymentStatus = 'pending';

    try {
        $sql = "INSERT INTO bookings (
                    BookingCode, UserID, TourID, VehicleID, InsuranceID, 
                    CustomerName, CustomerEmail, CustomerPhone, 
                    Slot, SlotKid, TotalPrice, InsuranceFee, VoucherCode, 
                    DiscountAmount, FinalPrice, PaymentMethod, PaymentStatus, 
                    ShuttleService, CreatedAt
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $bookingCode, $userID, $tourID, $vehicleID, $insuranceID, 
            $fullname, $email, $phone, 
            $adults, $children, $grossTotal, $insuranceFee, $promoCode, 
            $discountAmount, $finalTotal, $paymentMethodDB, $paymentStatus, 
            $hasShuttle
        ]);

        $newBookingID = $conn->lastInsertId();

        // =========================================================================
        // 5. XUẤT HÓA ĐƠN PDF (DOMPDF)
        // =========================================================================
        $pdfHtml = "
            <div style='font-family: DejaVu Sans, sans-serif; padding: 20px;'>
                <h2 style='text-align:center; color:#003366;'>HÓA ĐƠN ĐẶT TOUR</h2>
                <p><strong>Mã Đơn:</strong> {$bookingCode}</p>
                <p><strong>Khách hàng:</strong> {$fullname}</p>
                <p><strong>Email:</strong> {$email}</p>
                <p><strong>SĐT:</strong> {$phone}</p>
                <hr>
                <h3>Chi tiết dịch vụ</h3>
                <p><strong>Tên Tour:</strong> {$tourName}</p>
                <p><strong>Số lượng:</strong> Người lớn: {$adults} | Trẻ em: {$children}</p>
                <p><strong>Dịch vụ kèm theo:</strong> 
                    ".($hasShuttle ? 'Xe trung chuyển, ' : '')."
                    ".($insuranceID ? 'Bảo hiểm, ' : '')."
                    ".($vehicleID ? 'Thuê xe di chuyển' : '')."
                </p>
                <hr>
                <h3>Thanh toán</h3>
                <p>Tổng tiền dịch vụ: ".number_format($grossTotal)." đ</p>
                <p>Giảm giá: -".number_format($discountAmount)." đ</p>
                <h2 style='color:red;'>Thành tiền: ".number_format($finalTotal)." đ</h2>
                <p><strong>Trạng thái:</strong> CHƯA THANH TOÁN</p>
                <p style='text-align:center; margin-top:40px; font-style:italic;'>Cảm ơn quý khách đã tin tưởng và sử dụng dịch vụ của chúng tôi!</p>
            </div>
        ";

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // Bắt buộc font này để không lỗi Tiếng Việt
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        // =========================================================================
        // 6. GỬI EMAIL (PHPMAILER)
        // =========================================================================
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'dat.lt.64cntt@ntu.edu.vn';     // VUI LÒNG ĐỔI THÀNH GMAIL CỦA BẠN
            $mail->Password   = 'Khongcodau123';        // MẬT KHẨU ỨNG DỤNG (16 ký tự)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('dat.lt.64cntt@ntu.edu.vn', 'Hệ Thống Tour Nội Địa');
            $mail->addAddress($email, $fullname);

            // Đính kèm PDF
            $mail->addStringAttachment($pdfOutput, "Hoa_Don_{$bookingCode}.pdf");

            $mail->isHTML(true);
            $mail->Subject = "Xác nhận đặt Tour thành công - Mã đơn: {$bookingCode}";
            $mail->Body    = "
                <h3>Chào {$fullname},</h3>
                <p>Cảm ơn bạn đã đặt tour <strong>{$tourName}</strong> tại hệ thống của chúng tôi.</p>
                <p>Đơn hàng của bạn đang ở trạng thái <strong>Chờ thanh toán</strong>.</p>
                <p>Chúng tôi có đính kèm hóa đơn điện tử (PDF) trong email này, vui lòng kiểm tra.</p>
                <br>
                <p>Trân trọng,<br>Đội ngũ Hỗ trợ</p>
            ";

            $mail->send();
        } catch (Exception $e) {
            // Có thể ghi log lỗi gửi mail ở đây nếu cần thiết
            // error_log("Lỗi gửi mail: " . $mail->ErrorInfo);
        }

        // =========================================================================
        // 7. ĐIỀU HƯỚNG DỰA TRÊN PHƯƠNG THỨC THANH TOÁN
        // =========================================================================
        if ($paymentMethod == 'cash') {
            header("Location: booking_success.php?code=" . $bookingCode);
            exit();
        } else if ($paymentMethod == 'vnpay') {
            header("Location: booking_success.php?code=" . $bookingCode . "&method=vnpay");
            exit();
        } else if ($paymentMethod == 'momo') {
            header("Location: booking_success.php?code=" . $bookingCode . "&method=momo");
            exit();
        }

    } catch (PDOException $e) {
        die("Lỗi đặt tour: " . $e->getMessage());
    }

} else {
    header("Location: index.php");
    exit();
}
?>