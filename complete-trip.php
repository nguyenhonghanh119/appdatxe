<?php
// complete-trip.php
// Nhận yêu cầu AJAX từ tài xế khi bấm "Hoàn thành chuyến".
// - trips.status -> 'done'
// - bookings.status -> 'done' (cho các vé đang 'running')
// - Tạo bản ghi transactions (hoa hồng + thu nhập tài xế) cho từng vé
//   chưa có giao dịch, dựa trên commission_rate trong system_settings.
// - Cộng ví tài xế (wallet_balance) nếu thanh toán online (giả lập auto_payout).
// - Cập nhật driver_profiles.total_trips.
// Vì Hành khách và Admin đọc chung các bảng này, họ sẽ thấy cập nhật khi tải lại trang.

header('Content-Type: application/json; charset=utf-8');

$host = '127.0.0.1';
$db   = 'xeghep_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Lỗi kết nối CSDL']);
    exit;
}

// Giả lập ID tài xế đang đăng nhập (giống index.php / start-trip.php)
$driver_id = 2;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$trip_id = $input['trip_id'] ?? ($_POST['trip_id'] ?? null);

if (!$trip_id) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Thiếu trip_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Kiểm tra chuyến thuộc đúng tài xế và đang 'running'
    $stmt = $pdo->prepare("SELECT trip_id, status FROM trips WHERE trip_id = ? AND driver_id = ? FOR UPDATE");
    $stmt->execute([$trip_id, $driver_id]);
    $trip = $stmt->fetch();

    if (!$trip) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Không tìm thấy chuyến đi hoặc bạn không có quyền']);
        exit;
    }

    if ($trip['status'] !== 'running') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'Chuyến đi chưa ở trạng thái đang chạy']);
        exit;
    }

    // 2. Cập nhật trạng thái chuyến -> 'done'
    $stmt = $pdo->prepare("UPDATE trips SET status = 'done' WHERE trip_id = ?");
    $stmt->execute([$trip_id]);

    // 3. Lấy các vé đang 'running' của chuyến để chuyển sang 'done'
    $stmt = $pdo->prepare("SELECT booking_id, passenger_id, seats, total_amount, payment_method
                           FROM bookings WHERE trip_id = ? AND status = 'running'");
    $stmt->execute([$trip_id]);
    $bookings = $stmt->fetchAll();

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'done' WHERE trip_id = ? AND status = 'running'");
    $stmt->execute([$trip_id]);

    // 4. Lấy tỉ lệ hoa hồng hệ thống
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'commission_rate'");
    $stmt->execute();
    $commissionRate = (float) ($stmt->fetchColumn() ?: 10);

    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_payout'");
    $stmt->execute();
    $autoPayout = ($stmt->fetchColumn() === 'true');

    // 5. Với mỗi vé chưa có giao dịch, tạo bản ghi transactions + cộng ví (nếu online)
    $checkTxStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE booking_id = ?");
    $insertTxStmt = $pdo->prepare("INSERT INTO transactions
        (booking_id, trip_id, passenger_id, driver_id, total_amount, commission_amount, driver_receive, payment_method, status, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $walletStmt = $pdo->prepare("UPDATE driver_profiles SET wallet_balance = wallet_balance + ? WHERE driver_id = ?");
    $bookingsTotalStmt = $pdo->prepare("UPDATE passenger_profiles SET total_bookings = total_bookings + 1 WHERE passenger_id = ?");

    $walletCredit = 0;
    $txCreated = 0;

    foreach ($bookings as $b) {
        $checkTxStmt->execute([$b['booking_id']]);
        if ($checkTxStmt->fetchColumn() > 0) continue; // đã có giao dịch trước đó, bỏ qua

        $totalAmount = (float) $b['total_amount'];
        $commission = round($totalAmount * $commissionRate / 100, 2);
        $driverReceive = round($totalAmount - $commission, 2);
        $isOnline = $b['payment_method'] === 'online';
        $txStatus = $isOnline ? 'approved' : 'pending_cash_audit';
        $note = $isOnline
            ? "Chuyến {$trip_id} thanh toán online — cộng ví tài xế"
            : "Chờ đối soát tiền mặt — chuyến {$trip_id}";

        $insertTxStmt->execute([
            $b['booking_id'], $trip_id, $b['passenger_id'], $driver_id,
            $totalAmount, $commission, $driverReceive, $b['payment_method'], $txStatus, $note
        ]);
        $txCreated++;

        if ($isOnline && $autoPayout) {
            $walletCredit += $driverReceive;
        }
    }

    if ($walletCredit > 0) {
        $walletStmt->execute([$walletCredit, $driver_id]);
    }

    // 6. Tăng tổng số chuyến đã hoàn thành của tài xế
    $stmt = $pdo->prepare("UPDATE driver_profiles SET total_trips = total_trips + 1 WHERE driver_id = ?");
    $stmt->execute([$driver_id]);

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Đã hoàn thành chuyến',
        'trip_id' => $trip_id,
        'trip_status' => 'done',
        'transactions_created' => $txCreated,
        'wallet_credit' => $walletCredit,
    ]);
} catch (\PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Lỗi khi cập nhật CSDL: ' . $e->getMessage()]);
}