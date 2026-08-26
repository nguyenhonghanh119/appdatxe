<?php
// ===== DEBUG TẠM THỜI: bật hiển thị lỗi để tìm nguyên nhân trang trắng =====
// Sau khi debug xong nhớ XOÁ 3 dòng dưới đây (không để lộ lỗi hệ thống khi lên production)
error_reporting(E_ALL);
ini_set('display_errors', '1');
// ============================================================================

// save-profile.php
// Nhận yêu cầu AJAX từ nút "Lưu thay đổi" ở trang Hồ sơ.
// Cập nhật users.full_name/phone và driver_profiles.vehicle_type/license_plate.
// Việc sửa thông tin cá nhân/xe KHÔNG cần admin duyệt lại (khác với giấy tờ).

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

// Giả lập ID tài xế đang đăng nhập
$driver_id = 2;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$fullName = trim($input['fullName'] ?? '');
$phone    = trim($input['phone'] ?? '');
$vehicle  = trim($input['vehicle'] ?? '');
$plate    = trim($input['plate'] ?? '');

if ($fullName === '' || $phone === '' || $vehicle === '' || $plate === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Kiểm tra số điện thoại có bị trùng với tài xế/người dùng khác không
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id != ?");
    $stmt->execute([$phone, $driver_id]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'Số điện thoại đã được sử dụng bởi tài khoản khác']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?");
    $stmt->execute([$fullName, $phone, $driver_id]);

    $stmt = $pdo->prepare("UPDATE driver_profiles SET vehicle_type = ?, license_plate = ? WHERE driver_id = ?");
    $stmt->execute([$vehicle, $plate, $driver_id]);

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Đã lưu thay đổi',
        'fullName' => $fullName,
        'phone' => $phone,
        'vehicle' => $vehicle,
        'plate' => $plate,
    ]);
} catch (\PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Lỗi khi cập nhật CSDL: ' . $e->getMessage()]);
}