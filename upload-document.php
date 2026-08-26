<?php
// upload-document.php
// Nhận file giấy tờ (multipart/form-data) từ trang Hồ sơ.
// - Nếu doc_type đã tồn tại cho tài xế -> cập nhật file_path & đưa status về 'pending'
//   (giấy tờ vừa cập nhật luôn cần admin duyệt lại từ đầu).
// - Nếu là doc_type mới -> thêm dòng mới với status 'pending'.
// Admin duyệt/từ chối ở trang quản trị (cập nhật driver_documents.status) sẽ tự
// phản ánh lại trên trang Hồ sơ của tài xế khi tải lại trang, vì đọc chung 1 bảng.

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

$docType = trim($_POST['doc_type'] ?? '');
$docName = trim($_POST['doc_name'] ?? '');

if ($docType === '' || $docName === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Thiếu loại giấy tờ']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Vui lòng chọn file hợp lệ']);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
$ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Chỉ chấp nhận file JPG, PNG hoặc PDF']);
    exit;
}

if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'File tối đa 5MB']);
    exit;
}

// Thư mục lưu file thật, cùng cấp với trang tài xế: /uploads/docs/
$uploadDir = __DIR__ . '/uploads/docs';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$safeType = preg_replace('/[^a-z0-9_\-]/i', '', $docType);
$fileName = 'driver' . $driver_id . '_' . $safeType . '_' . time() . '.' . $ext;
$destPath = $uploadDir . '/' . $fileName;
$publicPath = '/uploads/docs/' . $fileName; // đường dẫn lưu vào DB (tương đối theo web root)

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Không thể lưu file lên server']);
    exit;
}

try {
    // Kiểm tra đã có giấy tờ cùng loại chưa
    $stmt = $pdo->prepare("SELECT doc_id FROM driver_documents WHERE driver_id = ? AND doc_type = ?");
    $stmt->execute([$driver_id, $docType]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE driver_documents
                               SET doc_name = ?, file_path = ?, status = 'pending', created_at = CURRENT_TIMESTAMP
                               WHERE doc_id = ?");
        $stmt->execute([$docName, $publicPath, $existing['doc_id']]);
        $docId = $existing['doc_id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO driver_documents (driver_id, doc_type, doc_name, file_path, status)
                               VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$driver_id, $docType, $docName, $publicPath]);
        $docId = $pdo->lastInsertId();
    }

    echo json_encode([
        'ok' => true,
        'message' => 'Đã tải lên, chờ admin duyệt',
        'doc_id' => $docId,
        'doc_type' => $docType,
        'doc_name' => $docName,
        'status' => 'pending',
        'file_path' => $publicPath,
    ]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Lỗi khi lưu CSDL: ' . $e->getMessage()]);
}