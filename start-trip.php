<?php
// start-trip.php
// Nhận yêu cầu AJAX từ tài xế khi bấm "Bắt đầu chuyến".
// Cập nhật trips.status -> 'running' và bookings.status -> 'running'
// (cho các vé đã 'approved' của chuyến đó), để phía Hành khách và Admin
// đọc cùng bảng dữ liệu này sẽ tự thấy trạng thái mới.

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

// Giả lập ID tài xế đang đăng nhập (giống index.php).
// Khi có hệ thống đăng nhập thật, hãy lấy từ session: $_SESSION['user_id']
$driver_id = 2;

// Chỉ nhận POST
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

    // 1. Kiểm tra chuyến này thuộc đúng tài xế và đang ở trạng thái 'upcoming'
    $stmt = $pdo->prepare("SELECT trip_id, status FROM trips WHERE trip_id = ? AND driver_id = ? FOR UPDATE");
    $stmt->execute([$trip_id, $driver_id]);
    $trip = $stmt->fetch();

    if (!$trip) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Không tìm thấy chuyến đi hoặc bạn không có quyền']);
        exit;
    }

    if ($trip['status'] !== 'upcoming') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'Chuyến đi đã được bắt đầu hoặc không còn ở trạng thái chờ khởi hành']);
        exit;
    }

    // 2. Cập nhật trạng thái chuyến -> 'running'
    $stmt = $pdo->prepare("UPDATE trips SET status = 'running' WHERE trip_id = ?");
    $stmt->execute([$trip_id]);

    // 3. Cập nhật trạng thái các vé đã duyệt (approved) của chuyến -> 'running'
    //    Đây là bước khiến phía Hành khách nhìn thấy "Chuyến đang chạy" khi họ mở lại trang của họ.
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'running' WHERE trip_id = ? AND status = 'approved'");
    $stmt->execute([$trip_id]);
    $affectedBookings = $stmt->rowCount();

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Đã bắt đầu chuyến',
        'trip_id' => $trip_id,
        'trip_status' => 'running',
        'bookings_updated' => $affectedBookings,
    ]);
} catch (\PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Lỗi khi cập nhật CSDL: ' . $e->getMessage()]);
}