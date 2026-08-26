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

// 1. Lấy thông tin cá nhân và hồ sơ tài xế
$stmt = $pdo->prepare("SELECT u.full_name, u.avatar, dp.rating, dp.total_trips 
                       FROM users u 
                       JOIN driver_profiles dp ON u.user_id = dp.driver_id 
                       WHERE u.user_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

$nameParts = explode(' ', trim($driver['full_name']));
$firstName = end($nameParts);

// 2. Đếm số yêu cầu đặt chỗ đang chờ duyệt (Sidebar Badge)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b 
                       JOIN trips t ON b.trip_id = t.trip_id 
                       WHERE t.driver_id = ? AND b.status = 'pending_approval'");
$stmt->execute([$driver_id]);
$pendingCount = $stmt->fetchColumn();

// 3. Đếm chuyến đi trong ngày hôm nay
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM trips WHERE driver_id = ? AND DATE(departure_time) = ?");
$stmt->execute([$driver_id, $today]);
$tripsToday = $stmt->fetchColumn();

// 4. Tính thu nhập tuần này
$stmt = $pdo->prepare("SELECT SUM(driver_receive) FROM transactions 
                       WHERE driver_id = ? AND status = 'approved'
                       AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$stmt->execute([$driver_id]);
$weeklyIncome = $stmt->fetchColumn() ?: 0;

// 5. Thống kê nhận/từ chối chuyến
$stmt = $pdo->prepare("SELECT * FROM driver_acceptance_stats WHERE driver_id = ?");
$stmt->execute([$driver_id]);
$stats = $stmt->fetchAll();

$dayStat = ['accepted_count' => 0, 'rejected_count' => 0, 'period_value' => date('Y-m-d')];
$monthStat = ['accepted_count' => 0, 'rejected_count' => 0, 'period_value' => date('Y-m'), 'acceptance_rate' => 0, 'prev_period_rate' => 0];

foreach ($stats as $s) {
    if ($s['period_type'] === 'day') $dayStat = $s;
    if ($s['period_type'] === 'month') $monthStat = $s;
}

// Tính phần trăm tăng giảm của tỉ lệ chấp nhận
$rateDiff = $monthStat['acceptance_rate'] - $monthStat['prev_period_rate'];
$rateDiffText = ($rateDiff > 0 ? '+' : '') . $rateDiff . '% so với tháng trước';
$rateDiffClass = $rateDiff < 0 ? 'down' : '';

// 6. Chuyến sắp khởi hành và danh sách khách
$stmt = $pdo->prepare("SELECT * FROM trips WHERE driver_id = ? AND status = 'upcoming' ORDER BY departure_time ASC LIMIT 1");
$stmt->execute([$driver_id]);
$nextTrip = $stmt->fetch();

$paxList = [];
if ($nextTrip) {
    $stmt = $pdo->prepare("SELECT u.full_name, u.avatar, b.seats 
                           FROM bookings b 
                           JOIN users u ON b.passenger_id = u.user_id 
                           WHERE b.trip_id = ? AND b.status = 'approved'");
    $stmt->execute([$nextTrip['trip_id']]);
    $paxList = $stmt->fetchAll();
}

$pickupLat  = $nextTrip['pickup_lat']  ?? null;
$pickupLng  = $nextTrip['pickup_lng']  ?? null;
$dropoffLat = $nextTrip['dropoff_lat'] ?? null;
$dropoffLng = $nextTrip['dropoff_lng'] ?? null;
$hasMapCoords = $nextTrip && $pickupLat !== null && $pickupLng !== null && $dropoffLat !== null && $dropoffLng !== null;

// 7. Lấy Hoạt động gần đây
$activities = [];
// Lấy đánh giá
$stmt = $pdo->prepare("SELECT u.full_name, r.rating, r.created_at 
                       FROM reviews r JOIN users u ON r.passenger_id = u.user_id 
                       WHERE r.driver_id = ? ORDER BY r.created_at DESC LIMIT 2");
$stmt->execute([$driver_id]);
foreach($stmt->fetchAll() as $r) {
    $paxNameArr = explode(' ', trim($r['full_name']));
    $paxFirstName = end($paxNameArr); 
    
    $activities[] = [
        'ic' => '⭐',
        'title' => "Nhận đánh giá {$r['rating']} sao từ {$paxFirstName}",
        'time' => date('d/m H:i', strtotime($r['created_at'])),
        'ts' => strtotime($r['created_at'])
    ];
}
// Lấy giao dịch
$stmt = $pdo->prepare("SELECT driver_receive, status, created_at FROM transactions 
                       WHERE driver_id = ? ORDER BY created_at DESC LIMIT 2");
$stmt->execute([$driver_id]);
foreach($stmt->fetchAll() as $t) {
    $isApproved = $t['status'] === 'approved';
    $activities[] = [
        'ic' => $isApproved ? '💵' : '⏳',
        'title' => ($isApproved ? "Đã nhận thanh toán " : "Chờ đối soát tiền mặt ") . number_format($t['driver_receive'], 0, ',', '.') . "đ",
        'time' => date('d/m H:i', strtotime($t['created_at'])),
        'ts' => strtotime($t['created_at'])
    ];
}
usort($activities, function($a, $b) { return $b['ts'] <=> $a['ts']; });

// Tên ngày hiện tại
$days = ['Sunday'=>'Chủ Nhật', 'Monday'=>'Thứ Hai', 'Tuesday'=>'Thứ Ba', 'Wednesday'=>'Thứ Tư', 'Thursday'=>'Thứ Năm', 'Friday'=>'Thứ Sáu', 'Saturday'=>'Thứ Bảy'];
$currentDayVN = $days[date('l')] . ', ' . date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Tài xế — Tổng quan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<!-- Leaflet (OpenStreetMap) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  #trip-map{
    width:100%;
    height:280px;
    border-radius:12px;
    margin-top:10px;
    background:var(--bg-soft,#f1f3f5);
    z-index:0;
  }
  .map-actions{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-top:12px;
  }
  .map-empty{
    display:flex;
    align-items:center;
    justify-content:center;
    height:280px;
    border-radius:12px;
    background:var(--bg-soft,#f1f3f5);
    color:var(--ink-faint,#9aa0a6);
    font-size:13px;
    text-align:center;
    padding:16px;
    margin-top:10px;
  }
  /* CSS bổ sung cho Dropdown Menu Đăng xuất/Cài đặt */
  .sidebar-foot {
    position: relative;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
    border-radius: 12px;
    padding: 10px;
    margin: 0 -10px; /* Bù trừ padding để tràn viền click */
  }
  .sidebar-foot:hover {
    background: rgba(255, 255, 255, 0.08);
  }
  .profile-dropdown {
    position: absolute;
    bottom: calc(100% + 5px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 12px;
    padding: 8px;
    box-shadow: 0 4px 16px rgba(18,33,58,0.15);
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 100;
  }
  .profile-dropdown.hidden {
    display: none !important;
  }
  .profile-dropdown a {
    color: var(--navy);
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 13.5px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: 0.15s;
  }
  .profile-dropdown a:hover {
    background: var(--fog);
  }
  .profile-dropdown a.logout {
    color: var(--coral);
  }
  .profile-dropdown a.logout:hover {
    background: var(--coral-dim);
  }
</style>
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>TÀI XẾ</small></div></div>
    <nav class="side-nav">
      <a href="index.php" class="active"><span class="ic">🏠</span> Tổng quan</a>
      <a href="yeu-cau-dat-cho.php"><span class="ic">📥</span> Yêu cầu đặt chỗ <?php if($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?></a>
      <a href="chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi</a>
      <a href="thu-nhap.php"><span class="ic">₫</span> Thu nhập</a>
      <a href="ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; giấy tờ</a>
    </nav>
    
    <!-- Khu vực Click để hiện Menu -->
    <div class="sidebar-foot" id="sidebar-profile">
        <div class="av"><?= htmlspecialchars($driver['avatar'] ?? 'U') ?></div>
        <div>
            <b><?= htmlspecialchars($driver['full_name']) ?></b>
            <span>★ <?= number_format($driver['rating'], 1) ?> · Đã duyệt</span>
        </div>
        
        <!-- Dropdown Menu -->
        <div id="profile-dropdown" class="profile-dropdown hidden">
            <a href="ho-so.php">⚙️ Cài đặt tài khoản</a>
            <a href="login.php" class="logout">🚪 Đăng xuất</a>
        </div>
    </div>
  </aside>

  <main class="main">
    <div class="view" id="view-overview">

      <div class="card" id="map-card" style="margin-bottom:16px">
        <div class="list-title" style="margin-top:0">Bản đồ chuyến sắp khởi hành</div>

        <?php if($nextTrip): ?>
          <div id="trip-map"
               data-has-coords="<?= $hasMapCoords ? '1' : '0' ?>"
               data-pickup-lat="<?= htmlspecialchars($pickupLat ?? '') ?>"
               data-pickup-lng="<?= htmlspecialchars($pickupLng ?? '') ?>"
               data-pickup-label="<?= htmlspecialchars($nextTrip['pickup_location'] ?? '') ?>"
               data-dropoff-lat="<?= htmlspecialchars($dropoffLat ?? '') ?>"
               data-dropoff-lng="<?= htmlspecialchars($dropoffLng ?? '') ?>"
               data-dropoff-label="<?= htmlspecialchars($nextTrip['dropoff_location'] ?? '') ?>"></div>

          <div class="map-actions">
            <button type="button" class="btn btn-outline btn-sm" id="btn-locate-me" onclick="locateMe()">📍 Vị trí của tôi</button>
            <button type="button" class="btn btn-outline btn-sm" id="btn-fit-route" onclick="fitRoute()">🗺️ Xem toàn tuyến</button>
            <button type="button" class="btn btn-outline btn-sm" id="btn-open-gmap" onclick="openInGoogleMaps()">🔗 Mở Google Maps</button>
          </div>
        <?php else: ?>
          <div class="map-empty">Chưa có chuyến sắp khởi hành để hiển thị trên bản đồ.</div>
        <?php endif; ?>
      </div>

      <div class="topbar">
        <div><h1 id="greeting">Chào <?= htmlspecialchars($firstName) ?>, chúc một ngày chạy xe an toàn 👋</h1><p id="today-date"><?= $currentDayVN ?></p></div>
        <a class="btn btn-primary" href="yeu-cau-dat-cho.php">📥 Xem yêu cầu đặt chỗ</a>
      </div>

      <div class="stat-row" id="stat-row-main">
        <div class="stat-card"><div class="lbl">Chuyến hôm nay</div><div class="val" id="stat-trips-today"><?= $tripsToday ?></div><div class="delta"><?= $nextTrip ? '1 sắp khởi hành' : 'Không có chuyến' ?></div></div>
        <div class="stat-card"><div class="lbl">Thu nhập tuần này</div><div class="val mono" id="stat-earn-week"><?= number_format($weeklyIncome, 0, ',', '.') ?>đ</div><div class="delta">Thu nhập từ T2 đến nay</div></div>
        <div class="stat-card"><div class="lbl">Đánh giá trung bình</div><div class="val" id="stat-rating"><?= number_format($driver['rating'], 1) ?>★</div><div class="delta"><?= $driver['total_trips'] ?> chuyến đã hoàn thành</div></div>
        <div class="stat-card"><div class="lbl">Tỉ lệ chấp nhận</div><div class="val" id="stat-accept-rate"><?= number_format($monthStat['acceptance_rate'], 0) ?>%</div><div class="delta <?= $rateDiffClass ?>"><?= $rateDiffText ?></div></div>
      </div>

      <div class="list-title" style="margin-top:4px">Thống kê nhận / từ chối chuyến</div>
      <div class="stat-row">
        <div class="stat-card"><div class="lbl">Đã nhận hôm nay</div><div class="val" id="stat-accept-day"><?= $dayStat['accepted_count'] ?></div><div class="delta">Trong ngày <?= htmlspecialchars($dayStat['period_value']) ?></div></div>
        <div class="stat-card"><div class="lbl">Đã từ chối hôm nay</div><div class="val" id="stat-reject-day"><?= $dayStat['rejected_count'] ?></div><div class="delta down">Trong ngày <?= htmlspecialchars($dayStat['period_value']) ?></div></div>
        <div class="stat-card"><div class="lbl">Đã nhận tháng này</div><div class="val" id="stat-accept-month"><?= $monthStat['accepted_count'] ?></div><div class="delta">Tháng <?= htmlspecialchars(date('m/Y', strtotime($monthStat['period_value'] ?? date('Y-m')))) ?></div></div>
        <div class="stat-card"><div class="lbl">Đã từ chối tháng này</div><div class="val" id="stat-reject-month"><?= $monthStat['rejected_count'] ?></div><div class="delta down">Tháng <?= htmlspecialchars(date('m/Y', strtotime($monthStat['period_value'] ?? date('Y-m')))) ?></div></div>
      </div>

      <div class="section-block">
        <div class="card">
          <div class="list-title">Chuyến sắp khởi hành</div>
          <?php if($nextTrip): ?>
          <div class="route-strip" id="next-trip-route" style="margin-bottom:10px">
            <div class="pt"><div class="city"><?= htmlspecialchars($nextTrip['route_from']) ?></div><div class="sub"><?= date('H:i', strtotime($nextTrip['departure_time'])) ?> · <?= htmlspecialchars($nextTrip['pickup_location']) ?></div></div>
            <div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div>
            <div class="pt end"><div class="city"><?= htmlspecialchars($nextTrip['route_to']) ?></div><div class="sub"><?= htmlspecialchars($nextTrip['dropoff_location']) ?></div></div>
          </div>
          
          <div class="pax-list" id="next-trip-pax">
            <?php foreach($paxList as $pax): 
                $pNameParts = explode(' ', trim($pax['full_name']));
                $pFirstName = end($pNameParts);
            ?>
            <div class="pax-chip"><span class="av"><?= htmlspecialchars($pax['avatar'] ?? mb_substr($pFirstName, 0, 1)) ?></span> <?= htmlspecialchars($pFirstName) ?> · <?= $pax['seats'] ?> ghế</div>
            <?php endforeach; ?>
            <?php if(empty($paxList)): ?>
                <span style="font-size:12.5px;color:var(--ink-faint);">Chưa có hành khách đặt chỗ</span>
            <?php endif; ?>
          </div>
          
          <div style="margin-top:14px;display:flex;gap:8px">
            <button class="btn btn-green btn-sm" id="btn-start-trip" data-trip-id="<?= htmlspecialchars($nextTrip['trip_id']) ?>" onclick="startNextTrip(this)">Bắt đầu chuyến</button>
            <a class="btn btn-outline btn-sm" href="chuyen-cua-toi.php">Xem chi tiết</a>
          </div>
          <?php else: ?>
          <p style="font-size:13.5px;color:var(--ink-soft)">Hiện tại chưa có chuyến đi nào sắp khởi hành.</p>
          <?php endif; ?>
        </div>
        
        <div class="card">
          <div class="list-title">Hoạt động gần đây</div>
          <div id="activity-list">
            <?php foreach($activities as $act): ?>
            <div class="activity-item">
                <div class="ic"><?= $act['ic'] ?></div>
                <div><b><?= htmlspecialchars($act['title']) ?></b><span><?= $act['time'] ?> trước</span></div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($activities)): ?>
            <div class="activity-item"><div><span>Chưa có hoạt động nào.</span></div></div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function startNextTrip(btn){
    var tripId = btn.dataset.tripId;
    if (!tripId) return;

    var originalText = btn.textContent;
    btn.textContent = 'Đang xử lý…';
    btn.disabled = true;

    fetch('start-trip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ trip_id: tripId })
    })
      .then(function(res) { return res.json().then(function(data) { return { status: res.status, data: data }; }); })
      .then(function(result) {
        if (!result.data.ok) {
          alert(result.data.message || 'Không thể bắt đầu chuyến, vui lòng thử lại.');
          btn.textContent = originalText;
          btn.disabled = false;
          return;
        }
        // Cập nhật DB thành công (trips.status = 'running', bookings.status = 'running')
        // -> phía Hành khách và Admin sẽ thấy trạng thái mới khi họ tải lại trang của họ.
        btn.textContent = 'Đang di chuyển…';
        btn.classList.remove('btn-green');
        btn.classList.add('btn-primary');
      })
      .catch(function() {
        alert('Lỗi kết nối, vui lòng thử lại.');
        btn.textContent = originalText;
        btn.disabled = false;
      });
  }

  // Script xử lý mở/đóng menu cài đặt - đăng xuất
  const sidebarProfile = document.getElementById('sidebar-profile');
  const profileDropdown = document.getElementById('profile-dropdown');

  sidebarProfile.addEventListener('click', function(e) {
    profileDropdown.classList.toggle('hidden');
    e.stopPropagation(); // Ngăn sự kiện lan truyền để không đóng ngay lập tức
  });

  // Tự động đóng menu nếu click ra ngoài
  document.addEventListener('click', function(e) {
    if (!sidebarProfile.contains(e.target)) {
      profileDropdown.classList.add('hidden');
    }
  });

  // ===== Bản đồ chuyến sắp khởi hành (Leaflet + OpenStreetMap) =====
  var tripMap = null;
  var pickupMarker = null;
  var dropoffMarker = null;
  var myLocationMarker = null;
  var routeLine = null;
  var mapPickup = null;
  var mapDropoff = null;

  document.addEventListener('DOMContentLoaded', function () {
    var mapEl = document.getElementById('trip-map');
    if (!mapEl || typeof L === 'undefined') return;

    var hasCoords = mapEl.dataset.hasCoords === '1';
    var defaultCenter = [21.0278, 105.8342];
    tripMap = L.map('trip-map', { scrollWheelZoom: false }).setView(defaultCenter, 12);

    var primaryTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
      maxZoom: 19,
      subdomains: 'abcd',
      attribution: '&copy; OpenStreetMap &copy; CARTO'
    }).addTo(tripMap);

    var triedFallback = false;
    primaryTiles.on('tileerror', function () {
      if (triedFallback) return;
      triedFallback = true;
      tripMap.removeLayer(primaryTiles);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
      }).addTo(tripMap);
    });

    setTimeout(function () { tripMap.invalidateSize(); }, 200);
    window.addEventListener('resize', function () { tripMap.invalidateSize(); });

    if (hasCoords) {
      mapPickup = [parseFloat(mapEl.dataset.pickupLat), parseFloat(mapEl.dataset.pickupLng)];
      mapDropoff = [parseFloat(mapEl.dataset.dropoffLat), parseFloat(mapEl.dataset.dropoffLng)];

      pickupMarker = L.marker(mapPickup).addTo(tripMap)
        .bindPopup('Điểm đón: ' + (mapEl.dataset.pickupLabel || ''));
      dropoffMarker = L.marker(mapDropoff).addTo(tripMap)
        .bindPopup('Điểm trả: ' + (mapEl.dataset.dropoffLabel || ''));

      routeLine = L.polyline([mapPickup, mapDropoff], { color: '#2f6fed', weight: 4, opacity: 0.7 }).addTo(tripMap);
      tripMap.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
    } else {
      L.popup()
        .setLatLng(defaultCenter)
        .setContent('Chưa có toạ độ điểm đón/trả cho chuyến này.')
        .openOn(tripMap);
    }
  });

  function locateMe() {
    if (!tripMap) return;
    if (!navigator.geolocation) {
      alert('Trình duyệt không hỗ trợ định vị.');
      return;
    }
    navigator.geolocation.getCurrentPosition(function (pos) {
      var latlng = [pos.coords.latitude, pos.coords.longitude];
      if (myLocationMarker) {
        myLocationMarker.setLatLng(latlng);
      } else {
        myLocationMarker = L.circleMarker(latlng, {
          radius: 8, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.9
        }).addTo(tripMap).bindPopup('Vị trí của bạn');
      }
      tripMap.setView(latlng, 14);
      myLocationMarker.openPopup();
    }, function () {
      alert('Không thể lấy vị trí của bạn. Vui lòng cấp quyền định vị cho trình duyệt.');
    });
  }

  function fitRoute() {
    if (!tripMap) return;
    if (routeLine) {
      tripMap.fitBounds(routeLine.getBounds(), { padding: [30, 30] });
    } else {
      alert('Chuyến này chưa có toạ độ điểm đón/trả để hiển thị toàn tuyến.');
    }
  }

  function openInGoogleMaps() {
    if (mapPickup && mapDropoff) {
      var url = 'https://www.google.com/maps/dir/?api=1&origin=' + mapPickup.join(',') +
                '&destination=' + mapDropoff.join(',');
      window.open(url, '_blank');
    } else {
      var mapEl = document.getElementById('trip-map');
      var origin = encodeURIComponent(mapEl ? mapEl.dataset.pickupLabel : '');
      var dest = encodeURIComponent(mapEl ? mapEl.dataset.dropoffLabel : '');
      var url = 'https://www.google.com/maps/dir/?api=1&origin=' + origin + '&destination=' + dest;
      window.open(url, '_blank');
    }
  }
</script>
</body>
</html>