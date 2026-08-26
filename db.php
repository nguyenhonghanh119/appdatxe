<?php
// db.php
$host = '127.0.0.1';
$db   = 'xeghep_db';
$user = 'xeghep_db'; // Thay bằng user CSDL của bạn
$pass = '';     // Thay bằng mật khẩu CSDL của bạn
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
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Giả lập phiên đăng nhập của tài xế Trần Văn Hùng (user_id = 2) từ bảng users
$driver_id = 2; 
?>