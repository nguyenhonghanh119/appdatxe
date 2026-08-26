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

// 1. Lấy thông tin tài xế cho Sidebar[cite: 2]
$stmt = $pdo->prepare("SELECT u.full_name, u.avatar, dp.rating 
                       FROM users u 
                       JOIN driver_profiles dp ON u.user_id = dp.driver_id 
                       WHERE u.user_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

// 2. Lấy danh sách các chuyến đi ĐANG CÓ yêu cầu chờ duyệt[cite: 2]
$stmt = $pdo->prepare("
    SELECT t.trip_id, t.route_from, t.route_to, t.departure_time, t.available_seats, t.pickup_location,
           COUNT(b.booking_id) as pending_req_count
    FROM trips t
    JOIN bookings b ON t.trip_id = b.trip_id
    WHERE t.driver_id = ? AND b.status = 'pending_approval'
    GROUP BY t.trip_id
    ORDER BY t.departure_time ASC
");
$stmt->execute([$driver_id]);
$tripsWithRequests = $stmt->fetchAll();

// 3. Tính tổng số yêu cầu chờ duyệt cho thẻ Badge trên Sidebar
$totalPendingCount = array_sum(array_column($tripsWithRequests, 'pending_req_count'));

// 4. Lấy chi tiết các booking cho các chuyến ở trên[cite: 2]
$requestsByTrip = [];
if ($totalPendingCount > 0) {
    $tripIds = array_column($tripsWithRequests, 'trip_id');
    $inQuery = implode(',', array_fill(0, count($tripIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT b.booking_id, b.trip_id, b.seats, u.full_name, u.avatar
        FROM bookings b
        JOIN users u ON b.passenger_id = u.user_id
        WHERE b.trip_id IN ($inQuery) AND b.status = 'pending_approval'
        ORDER BY b.created_at ASC
    ");
    $stmt->execute($tripIds);
    $allRequests = $stmt->fetchAll();
    
    // Nhóm booking theo trip_id
    foreach ($allRequests as $req) {
        $requestsByTrip[$req['trip_id']][] = $req;
    }
}

// Hàm format ngày tiếng Việt (VD: Th.Bảy 22/08)
function formatVietnameseDate($datetime) {
    $days = ['Sunday'=>'Chủ Nhật', 'Monday'=>'Th.Hai', 'Tuesday'=>'Th.Ba', 'Wednesday'=>'Th.Tư', 'Thursday'=>'Th.Năm', 'Friday'=>'Th.Sáu', 'Saturday'=>'Th.Bảy'];
    $ts = strtotime($datetime);
    return $days[date('l', $ts)] . ' ' . date('d/m', $ts);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Tài xế — Yêu cầu đặt chỗ</title>
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
      <a href="yeu-cau-dat-cho.php" class="active">
          <span class="ic">📥</span> Yêu cầu đặt chỗ 
          <?php if($totalPendingCount > 0): ?>
            <span class="badge-count" id="pending-count"><?= $totalPendingCount ?></span>
          <?php endif; ?>
      </a>
      <a href="chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi</a>
      <a href="thu-nhap.php"><span class="ic">₫</span> Thu nhập</a>
      <a href="ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; giấy tờ</a>
    </nav>
    <div class="sidebar-foot">
        <div class="av"><?= htmlspecialchars($driver['avatar'] ?? 'U') ?></div>
        <div><b><?= htmlspecialchars($driver['full_name']) ?></b><span>★ <?= number_format($driver['rating'], 1) ?> · Đã duyệt</span></div>
    </div>
  </aside>

  <main class="main">
    <div class="view" id="view-requests">
      <div class="topbar"><div><h1>Yêu cầu đặt chỗ</h1><p>Duyệt yêu cầu từ hành khách cho từng chuyến đã đăng.</p></div></div>

      <div id="requests-list">
        
        <!-- Vòng lặp hiển thị từng Chuyến Xe -->
        <?php foreach($tripsWithRequests as $trip): ?>
        <div class="trip-group-head" data-trip-id="<?= htmlspecialchars($trip['trip_id']) ?>">
          <div class="route-strip" style="max-width:420px">
              <div class="pt">
                  <div class="city" style="font-size:14px"><?= htmlspecialchars($trip['route_from']) ?></div>
                  <div class="sub"><?= formatVietnameseDate($trip['departure_time']) ?> · <?= date('H:i', strtotime($trip['departure_time'])) ?></div>
              </div>
              <div class="route-line"><div class="node start"></div><div class="node end"></div></div>
              <div class="pt end">
                  <div class="city" style="font-size:14px"><?= htmlspecialchars($trip['route_to']) ?></div>
                  <div class="sub"><?= $trip['available_seats'] ?> ghế trống</div>
              </div>
          </div>
          <span style="font-size:12.5px;color:var(--ink-faint)"><?= $trip['pending_req_count'] ?> yêu cầu chờ duyệt</span>
        </div>

        <!-- Vòng lặp hiển thị các Yêu Cầu Đặt Chỗ (Bookings) bên trong Chuyến Xe -->
        <?php 
        $reqs = $requestsByTrip[$trip['trip_id']] ?? [];
        foreach($reqs as $req): 
            $pNameArr = explode(' ', trim($req['full_name']));
            $pFirstName = end($pNameArr);
            $avatar = $req['avatar'] ?? mb_substr($pFirstName, 0, 1);
        ?>
        <div class="req-card" data-request-id="<?= htmlspecialchars($req['booking_id']) ?>">
          <div class="av"><?= htmlspecialchars($avatar) ?></div>
          <div class="meta">
              <b><?= htmlspecialchars($req['full_name']) ?></b> <span class="stars">★ 5.0</span>
              <div class="tripline">Xin <?= $req['seats'] ?> ghế · Đón tại <?= htmlspecialchars($trip['pickup_location']) ?></div>
          </div>
          <div class="actions">
              <button class="btn btn-green btn-sm" onclick="acceptReq(this)">Chấp nhận</button>
              <button class="btn btn-coral btn-sm" onclick="rejectReq(this)">Từ chối</button>
          </div>
        </div>
        <?php endforeach; ?>
        
        <?php endforeach; ?>

        <!-- Nếu không có dữ liệu -->
        <?php if(empty($tripsWithRequests)): ?>
            <p style="text-align:center; color:var(--ink-soft); padding: 40px 0;">
                Hiện tại không có yêu cầu đặt chỗ nào đang chờ duyệt.
            </p>
        <?php endif; ?>

      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function acceptReq(btn){
    const card = btn.closest('.req-card');
    const requestId = card.dataset.requestId;
    // TODO BACKEND: await fetch(`/api/requests/${requestId}/accept`, {method:'POST'});
    card.style.opacity = '.4';
    card.querySelector('.actions').innerHTML = '<span class="doc-status approved">Đã chấp nhận</span>';
    updatePendingCount(-1);
  }

  function rejectReq(btn){
    const card = btn.closest('.req-card');
    const requestId = card.dataset.requestId;
    // TODO BACKEND: await fetch(`/api/requests/${requestId}/reject`, {method:'POST'});
    card.style.opacity = '.4';
    card.querySelector('.actions').innerHTML = '<span class="doc-status rejected">Đã từ chối</span>';
    updatePendingCount(-1);
  }

  function updatePendingCount(delta){
    const el = document.getElementById('pending-count');
    if(el) {
        const next = Math.max(0, parseInt(el.textContent, 10) + delta);
        el.textContent = next;
        if(next === 0) el.style.display = 'none'; // Ẩn badge nếu bằng 0
    }
  }
</script>
</body>
</html>