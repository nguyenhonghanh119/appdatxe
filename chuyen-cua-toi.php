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

// Giả lập ID tài xế đang đăng nhập (Trần Văn Hùng có user_id = 2)
$driver_id = 2;

// 1. Thông tin tài xế cho sidebar
$stmt = $pdo->prepare("SELECT u.full_name, u.avatar, dp.rating
                       FROM users u JOIN driver_profiles dp ON u.user_id = dp.driver_id
                       WHERE u.user_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

// 2. Badge số yêu cầu đặt chỗ đang chờ duyệt
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b
                       JOIN trips t ON b.trip_id = t.trip_id
                       WHERE t.driver_id = ? AND b.status = 'pending_approval'");
$stmt->execute([$driver_id]);
$pendingCount = $stmt->fetchColumn();

// 3. Hàm lấy danh sách hành khách đã xác nhận của 1 chuyến
function getPaxList(PDO $pdo, string $tripId, array $statuses): array {
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    $stmt = $pdo->prepare("SELECT u.full_name, u.avatar, b.seats
                           FROM bookings b JOIN users u ON b.passenger_id = u.user_id
                           WHERE b.trip_id = ? AND b.status IN ($placeholders)");
    $stmt->execute(array_merge([$tripId], $statuses));
    return $stmt->fetchAll();
}

function firstName(string $fullName): string {
    $parts = explode(' ', trim($fullName));
    return end($parts);
}

// 4. Chuyến "Sắp tới" (upcoming)
$stmt = $pdo->prepare("SELECT * FROM trips WHERE driver_id = ? AND status = 'upcoming' ORDER BY departure_time ASC");
$stmt->execute([$driver_id]);
$upcomingTrips = $stmt->fetchAll();

// 5. Chuyến "Đang chạy" (running)
$stmt = $pdo->prepare("SELECT * FROM trips WHERE driver_id = ? AND status = 'running' ORDER BY departure_time ASC");
$stmt->execute([$driver_id]);
$runningTrips = $stmt->fetchAll();

// 6. Chuyến "Hoàn thành" (done) — mới nhất trước, giới hạn 20 chuyến gần nhất
$stmt = $pdo->prepare("SELECT * FROM trips WHERE driver_id = ? AND status = 'done' ORDER BY departure_time DESC LIMIT 20");
$stmt->execute([$driver_id]);
$doneTrips = $stmt->fetchAll();

// Tỉ lệ hoa hồng hệ thống, dùng để tính thu nhập thực nhận của tài xế
$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'commission_rate'");
$stmt->execute();
$commissionRate = (float) ($stmt->fetchColumn() ?: 10);

// Số ghế đã đặt (đã duyệt/đang chạy/hoàn thành)
function bookedSeats(PDO $pdo, string $tripId): int {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(seats),0) FROM bookings
                           WHERE trip_id = ? AND status IN ('approved','running','done')");
    $stmt->execute([$tripId]);
    return (int) $stmt->fetchColumn();
}

// Thu nhập từng chuyến đã hoàn thành = (số ghế đã đặt × price_per_seat) − hoa hồng
// Tính trực tiếp từ trips.price_per_seat, không phụ thuộc dữ liệu đã lưu sẵn trong transactions
$doneIncome = [];
foreach ($doneTrips as $t) {
    $seatsDone = bookedSeats($pdo, $t['trip_id']);
    $revenue = $seatsDone * (float) $t['price_per_seat'];
    $doneIncome[$t['trip_id']] = round($revenue * (1 - $commissionRate / 100), 2);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Tài xế — Chuyến của tôi</title>
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
      <a href="yeu-cau-dat-cho.php"><span class="ic">📥</span> Yêu cầu đặt chỗ <?php if($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?></a>
      <a href="chuyen-cua-toi.php" class="active"><span class="ic">🧭</span> Chuyến của tôi</a>
      <a href="thu-nhap.php"><span class="ic">₫</span> Thu nhập</a>
      <a href="ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; giấy tờ</a>
    </nav>
    <div class="sidebar-foot">
      <div class="av"><?= htmlspecialchars($driver['avatar'] ?? 'U') ?></div>
      <div><b><?= htmlspecialchars($driver['full_name']) ?></b><span>★ <?= number_format($driver['rating'], 1) ?> · Đã duyệt</span></div>
    </div>
  </aside>

  <main class="main">

    <div class="view" id="view-mytrips">
      <div class="topbar"><div><h1>Chuyến của tôi</h1><p>Quản lý các chuyến đã đăng và hành khách đã xác nhận.</p></div></div>

      <div class="tabs">
        <button class="active" data-tab="d-upcoming" onclick="switchDTab('d-upcoming')">Sắp tới <?= count($upcomingTrips) ? '('.count($upcomingTrips).')' : '' ?></button>
        <button data-tab="d-running" onclick="switchDTab('d-running')">Đang chạy <?= count($runningTrips) ? '('.count($runningTrips).')' : '' ?></button>
        <button data-tab="d-done" onclick="switchDTab('d-done')">Hoàn thành</button>
      </div>

      <div id="dtab-d-upcoming">
        <?php if (empty($upcomingTrips)): ?>
          <p style="font-size:13.5px;color:var(--ink-soft)">Không có chuyến nào sắp khởi hành.</p>
        <?php endif; ?>
        <?php foreach ($upcomingTrips as $trip):
            $pax = getPaxList($pdo, $trip['trip_id'], ['approved']);
            $seatsBooked = bookedSeats($pdo, $trip['trip_id']);
        ?>
        <div class="trip-manage-card" data-trip-id="<?= htmlspecialchars($trip['trip_id']) ?>">
          <div class="top"><span class="status-pill upcoming">Sắp khởi hành</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)"><?= date('H:i', strtotime($trip['departure_time'])) ?> · <?= date('d/m/Y', strtotime($trip['departure_time'])) ?></span></div>
          <div class="route-strip">
            <div class="pt"><div class="city"><?= htmlspecialchars($trip['route_from']) ?></div><div class="sub"><?= htmlspecialchars($trip['pickup_location']) ?></div></div>
            <div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div>
            <div class="pt end"><div class="city"><?= htmlspecialchars($trip['route_to']) ?></div><div class="sub"><?= htmlspecialchars($trip['dropoff_location']) ?></div></div>
          </div>
          <div class="pax-list">
            <?php foreach ($pax as $p): ?>
              <div class="pax-chip"><span class="av"><?= htmlspecialchars($p['avatar'] ?? mb_substr(firstName($p['full_name']), 0, 1)) ?></span> <?= htmlspecialchars(firstName($p['full_name'])) ?> · <?= (int)$p['seats'] ?> ghế</div>
            <?php endforeach; ?>
            <?php if (empty($pax)): ?>
              <span style="font-size:12.5px;color:var(--ink-faint);">Chưa có hành khách đặt chỗ</span>
            <?php endif; ?>
          </div>
          <div class="bottom">
            <span style="font-size:12.5px;color:var(--ink-soft)"><?= $seatsBooked ?>/<?= (int)$trip['total_seats'] ?> ghế đã đặt</span>
            <button class="btn btn-green btn-sm" onclick="startTrip(this)">Bắt đầu chuyến</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div id="dtab-d-running" class="hidden">
        <?php if (empty($runningTrips)): ?>
          <p style="font-size:13.5px;color:var(--ink-soft)">Hiện không có chuyến nào đang chạy.</p>
        <?php endif; ?>
        <?php foreach ($runningTrips as $trip):
            $pax = getPaxList($pdo, $trip['trip_id'], ['running']);
        ?>
        <div class="trip-manage-card" data-trip-id="<?= htmlspecialchars($trip['trip_id']) ?>">
          <div class="top"><span class="status-pill running">Đang di chuyển</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)">Khởi hành <?= date('H:i', strtotime($trip['departure_time'])) ?></span></div>
          <div class="route-strip">
            <div class="pt"><div class="city"><?= htmlspecialchars($trip['route_from']) ?></div><div class="sub"><?= htmlspecialchars($trip['pickup_location']) ?></div></div>
            <div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div>
            <div class="pt end"><div class="city"><?= htmlspecialchars($trip['route_to']) ?></div><div class="sub"><?= htmlspecialchars($trip['dropoff_location']) ?></div></div>
          </div>
          <div class="pax-list">
            <?php foreach ($pax as $p): ?>
              <div class="pax-chip"><span class="av"><?= htmlspecialchars($p['avatar'] ?? mb_substr(firstName($p['full_name']), 0, 1)) ?></span> <?= htmlspecialchars(firstName($p['full_name'])) ?> · <?= (int)$p['seats'] ?> ghế</div>
            <?php endforeach; ?>
            <?php if (empty($pax)): ?>
              <span style="font-size:12.5px;color:var(--ink-faint);">Không có hành khách</span>
            <?php endif; ?>
          </div>
          <div class="bottom">
            <span style="font-size:12.5px;color:var(--ink-soft)"><?= htmlspecialchars($trip['route_to']) ?> · <?= number_format($trip['price_per_seat'], 0, ',', '.') ?>đ/ghế</span>
            <button class="btn btn-primary btn-sm" onclick="completeTrip(this)">Hoàn thành chuyến</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div id="dtab-d-done" class="hidden">
        <?php if (empty($doneTrips)): ?>
          <p style="font-size:13.5px;color:var(--ink-soft)">Chưa có chuyến nào hoàn thành.</p>
        <?php endif; ?>
        <?php foreach ($doneTrips as $trip): ?>
        <div class="trip-manage-card" data-trip-id="<?= htmlspecialchars($trip['trip_id']) ?>">
          <div class="top"><span class="status-pill done">Hoàn thành</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)"><?= date('d/m/Y', strtotime($trip['departure_time'])) ?></span></div>
          <div class="route-strip">
            <div class="pt"><div class="city"><?= htmlspecialchars($trip['route_from']) ?></div><div class="sub"><?= date('H:i', strtotime($trip['departure_time'])) ?></div></div>
            <div class="route-line"><div class="node start"></div><div class="node end"></div></div>
            <div class="pt end"><div class="city"><?= htmlspecialchars($trip['route_to']) ?></div><div class="sub">&nbsp;</div></div>
          </div>
          <div class="bottom">
            <span style="font-size:12.5px;color:var(--ink-soft)">Thu nhập: <?= number_format($doneIncome[$trip['trip_id']] ?? 0, 0, ',', '.') ?>đ</span>
            <button class="btn btn-outline btn-sm" onclick="viewTripDetail(this)">Xem chi tiết</button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function switchDTab(tab){
    ['d-upcoming','d-running','d-done'].forEach(t => document.getElementById('dtab-'+t).classList.toggle('hidden', t !== tab));
    document.querySelectorAll('.tabs button').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  }

  function startTrip(btn){
    const card = btn.closest('.trip-manage-card');
    const tripId = card.dataset.tripId;

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Đang xử lý…';

    fetch('start-trip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ trip_id: tripId })
    })
      .then(res => res.json())
      .then(data => {
        if (!data.ok) {
          alert(data.message || 'Không thể bắt đầu chuyến.');
          btn.disabled = false;
          btn.textContent = originalText;
          return;
        }
        // Dữ liệu trên CSDL đã đổi (trips.status = 'running', bookings liên quan = 'running')
        // -> tải lại trang để lấy dữ liệu mới nhất từ server, đồng bộ với phía Hành khách/Admin.
        location.reload();
      })
      .catch(() => {
        alert('Lỗi kết nối, vui lòng thử lại.');
        btn.disabled = false;
        btn.textContent = originalText;
      });
  }

  function completeTrip(btn){
    const card = btn.closest('.trip-manage-card');
    const tripId = card.dataset.tripId;

    const confirmed = confirm('Xác nhận đã hoàn thành chuyến đi ' + tripId + '?\nSố tiền chuyến này sẽ được cộng vào thu nhập của bạn.');
    if (!confirmed) return;

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Đang xử lý…';

    fetch('complete-trip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ trip_id: tripId })
    })
      .then(res => res.json())
      .then(data => {
        if (!data.ok) {
          alert(data.message || 'Không thể hoàn thành chuyến.');
          btn.disabled = false;
          btn.textContent = originalText;
          return;
        }
        // trips.status = 'done', bookings = 'done', giao dịch & thu nhập đã được ghi nhận
        // -> tải lại trang để hiển thị đúng dữ liệu mới (đồng thời admin/hành khách cũng thấy khi họ tải lại).
        location.reload();
      })
      .catch(() => {
        alert('Lỗi kết nối, vui lòng thử lại.');
        btn.disabled = false;
        btn.textContent = originalText;
      });
  }

  function viewTripDetail(btn){
    const card = btn.closest('.trip-manage-card');
    const tripId = card.dataset.tripId;
    // TODO: điều hướng đến trang chi tiết khi có, ví dụ: location.href = `chi-tiet-chuyen.php?trip_id=${tripId}`;
    alert('Xem chi tiết chuyến ' + tripId + ' (chưa có trang chi tiết).');
  }
</script>
</body>
</html>