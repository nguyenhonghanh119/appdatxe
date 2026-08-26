<?php
header('Content-Type: application/json; charset=utf-8');

// Cấu hình kết nối CSDL (giống ho-so.php)
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

function respond(bool $ok, string $message = '', array $extra = []): void {
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    respond(false, 'Lỗi kết nối CSDL.');
}

// Giả lập ID tài xế đang đăng nhập (giống ho-so.php)
$driver_id = 2;

// Đọc dữ liệu gửi lên (JSON)
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$doc_id = isset($input['doc_id']) ? (int)$input['doc_id'] : 0;

if ($doc_id <= 0) {
    respond(false, 'Thiếu doc_id hợp lệ.');
}

try {
    // Xác nhận giấy tờ thuộc về tài xế đang đăng nhập, đồng thời lấy đường dẫn file để xóa vật lý
    $stmt = $pdo->prepare("SELECT doc_id, file_path FROM driver_documents WHERE doc_id = ? AND driver_id = ?");
    $stmt->execute([$doc_id, $driver_id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        respond(false, 'Không tìm thấy giấy tờ hoặc bạn không có quyền xóa.');
    }

    $pdo->beginTransaction();
    $del = $pdo->prepare("DELETE FROM driver_documents WHERE doc_id = ? AND driver_id = ?");
    $del->execute([$doc_id, $driver_id]);
    $deletedRows = $del->rowCount();
    $pdo->commit();

    if ($deletedRows === 0) {
        respond(false, 'Xóa không thành công, vui lòng thử lại.');
    }

    // Xóa file vật lý trên server (nếu có), không làm hỏng response nếu lỗi
    if (!empty($doc['file_path'])) {
        $fullPath = __DIR__ . '/' . ltrim($doc['file_path'], '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    respond(true, 'Đã xóa giấy tờ.', ['docId' => $doc_id]);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(false, 'Lỗi CSDL khi xóa giấy tờ.');
}