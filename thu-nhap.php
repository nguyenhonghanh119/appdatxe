<?php
// Cấu hình kết nối CSDL
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
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// Giả lập ID tài xế đang đăng nhập (Trần Văn Hùng có user_id = 2)[cite: 2]
$driver_id = 2;

// 1. Lấy thông tin tài xế và số dư ví[cite: 2]
$stmt = $pdo->prepare("SELECT u.full_name, u.avatar, dp.rating, dp.wallet_balance, dp.total_trips 
                       FROM users u 
                       JOIN driver_profiles dp ON u.user_id = dp.driver_id 
                       WHERE u.user_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

// 2. Đếm số yêu cầu đặt chỗ đang chờ duyệt (Sidebar Badge)[cite: 2]
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b 
                       JOIN trips t ON b.trip_id = t.trip_id 
                       WHERE t.driver_id = ? AND b.status = 'pending_approval'");
$stmt->execute([$driver_id]);
$pendingCount = $stmt->fetchColumn();

// 3. Tính thu nhập trong tháng hiện tại[cite: 2]
$stmt = $pdo->prepare("SELECT SUM(driver_receive) FROM transactions 
                       WHERE driver_id = ? 
                       AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                       AND YEAR(created_at) = YEAR(CURRENT_DATE()) 
                       AND status = 'approved'");
$stmt->execute([$driver_id]);
$monthIncome = $stmt->fetchColumn() ?: 0;

// Lấy tỉ lệ hoa hồng từ hệ thống để hiển thị[cite: 2]
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'commission_rate'");
$stmt->execute();
$commissionRate = $stmt->fetchColumn() ?: 10;

// 4. Lấy lịch sử giao dịch (Thu nhập chuyến đi)[cite: 2]
$stmt = $pdo->prepare("
    SELECT t.created_at, tr.route_from, tr.route_to, t.total_amount, t.commission_amount, t.driver_receive,
           (SELECT IFNULL(SUM(seats), 0) FROM bookings b WHERE b.trip_id = tr.trip_id AND b.status IN ('done', 'approved')) as total_pax
    FROM transactions t
    JOIN trips tr ON t.trip_id = tr.trip_id
    WHERE t.driver_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$driver_id]);
$transactions = $stmt->fetchAll();

// 5. Lấy lịch sử rút tiền[cite: 2]
$stmt = $pdo->prepare("SELECT created_at, amount, bank_info, status FROM withdrawals WHERE driver_id = ? ORDER BY created_at DESC");
$stmt->execute([$driver_id]);
$withdrawals = $stmt->fetchAll();

// Hàm map trạng thái rút tiền ra UI
function getWithdrawStatusHtml($status) {
    switch ($status) {
        case 'approved':
            return '<span class="status-pill approved" style="background:var(--green-dim);color:var(--green)">Thành công</span>';
        case 'pending':
            return '<span class="status-pill pending" style="background:#FFF3DF;color:#92650E">Đang xử lý</span>';
        case 'rejected':
            return '<span class="status-pill rejected" style="background:var(--coral-dim);color:var(--coral)">Từ chối</span>';
        default:
            return '<span class="status-pill">Không rõ</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Tài xế — Thu nhập</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>TÀI XẾ</small></div></div>
    <nav class="side-nav">
      <a href="index.php"><span class="ic">🏠</span> Tổng quan</a>
      <a href="yeu-cau-dat-cho.php">
          <span class="ic">📥</span> Yêu cầu đặt chỗ 
          <?php if($pendingCount > 0): ?>
            <span class="badge-count"><?= $pendingCount ?></span>
          <?php endif; ?>
      </a>
      <a href="chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi</a>
      <a href="thu-nhap.php" class="active"><span class="ic">₫</span> Thu nhập</a>
      <a href="ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; giấy tờ</a>
    </nav>
    <div class="sidebar-foot">
        <div class="av"><?= htmlspecialchars($driver['avatar'] ?? 'U') ?></div>
        <div><b><?= htmlspecialchars($driver['full_name']) ?></b><span>★ <?= number_format($driver['rating'], 1) ?> · Đã duyệt</span></div>
    </div>
  </aside>

  <main class="main">
    <div class="view" id="view-earnings">
      <div class="topbar">
        <div><h1>Thu nhập</h1><p>Theo dõi doanh thu, hoa hồng và rút tiền về tài khoản.</p></div>
        <a href="rut-tien.php" class="btn btn-primary">💵 Rút tiền</a>
      </div>

      <div class="earn-summary">
        <div class="stat-card">
          <div class="lbl">Số dư khả dụng</div>
          <div class="val mono" id="balance-val"><?= number_format($driver['wallet_balance'], 0, ',', '.') ?>đ</div>
          <div class="delta">Có thể rút bất kỳ lúc nào</div>
          <a href="rut-tien.php" class="btn btn-outline btn-sm" style="margin-top:10px;display:inline-block;text-align:center;text-decoration:none">Rút tiền ngay</a>
        </div>
        <div class="stat-card">
            <div class="lbl">Thu nhập tháng <?= date('m') ?></div>
            <div class="val mono" id="stat-month-earn"><?= number_format($monthIncome, 0, ',', '.') ?>đ</div>
            <div class="delta">Hoa hồng nền tảng <?= htmlspecialchars($commissionRate) ?>%</div>
        </div>
        <div class="stat-card">
            <div class="lbl">Tổng chuyến đã chạy</div>
            <div class="val" id="stat-total-trips"><?= $driver['total_trips'] ?></div>
        </div>
      </div>

      <div class="card">
        <div class="list-title">Lịch sử chuyến</div>
        <table>
          <thead><tr><th>Ngày</th><th>Tuyến</th><th>Khách</th><th>Doanh thu</th><th>Hoa hồng</th><th>Thực nhận</th></tr></thead>
          <tbody id="earnings-history">
            <?php foreach($transactions as $txn): ?>
            <tr>
                <td class="mono"><?= date('d/m', strtotime($txn['created_at'])) ?></td>
                <td><?= htmlspecialchars($txn['route_from']) ?> → <?= htmlspecialchars($txn['route_to']) ?></td>
                <td><?= $txn['total_pax'] ?></td>
                <td class="mono"><?= number_format($txn['total_amount'], 0, ',', '.') ?>đ</td>
                <td class="mono">−<?= number_format($txn['commission_amount'], 0, ',', '.') ?>đ</td>
                <td class="mono" style="font-weight:600"><?= number_format($txn['driver_receive'], 0, ',', '.') ?>đ</td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($transactions)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--ink-soft)">Chưa có dữ liệu chuyến đi.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="list-title">Lịch sử rút tiền</div>
        <table>
          <thead><tr><th>Ngày yêu cầu</th><th>Số tiền</th><th>Ngân hàng nhận</th><th>Trạng thái</th></tr></thead>
          <tbody id="withdraw-history">
            <?php foreach($withdrawals as $wd): ?>
            <tr>
                <td class="mono"><?= date('d/m', strtotime($wd['created_at'])) ?></td>
                <td class="mono"><?= number_format($wd['amount'], 0, ',', '.') ?>đ</td>
                <td><?= htmlspecialchars($wd['bank_info']) ?></td>
                <td><?= getWithdrawStatusHtml($wd['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($withdrawals)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--ink-soft)">Chưa có yêu cầu rút tiền nào.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
</body>
</html>