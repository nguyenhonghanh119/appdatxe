<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Hành khách — Chuyến của tôi</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>HÀNH KHÁCH</small></div></div>
    <nav class="side-nav">
      <a href="khach-index.php"><span class="ic">🔍</span> Tìm chuyến</a>
      <a href="khach-chuyen-cua-toi.php" class="active"><span class="ic">🧭</span> Chuyến của tôi <span class="badge-count">1</span></a>
      <a href="khach-ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; Thanh toán</a>
    </nav>
    <div class="sidebar-foot"><div class="av">L</div><div><b>Nguyễn Thị Lan</b><span>091 888 2233</span><span class="role-pill">Đã xác thực</span></div></div>
  </aside>

  <main class="main">

    <!-- ==============================================================
         TODO BACKEND:
         - GET  /api/passenger/bookings?status=upcoming|running|done|cancelled -> dữ liệu 4 tab
         - POST /api/passenger/bookings/:id/cancel   -> nút "Huỷ chuyến"
         - POST /api/passenger/bookings/:id/review    -> nút "Đánh giá tài xế"
         - GET  /api/passenger/bookings/:id           -> nút "Xem chi tiết"
         ============================================================== -->

    <div class="view" id="view-mybookings">
      <div class="topbar"><div><h1>Chuyến của tôi</h1><p>Quản lý các chuyến đã đặt và trạng thái thanh toán.</p></div></div>

      <div class="tabs">
        <button class="active" data-tab="k-upcoming" onclick="switchKTab('k-upcoming')">Sắp tới</button>
        <button data-tab="k-running" onclick="switchKTab('k-running')">Đang đi</button>
        <button data-tab="k-done" onclick="switchKTab('k-done')">Hoàn thành</button>
        <button data-tab="k-cancelled" onclick="switchKTab('k-cancelled')">Đã huỷ</button>
      </div>

      <div id="ktab-k-upcoming">
        <div class="trip-manage-card" data-booking-id="BK-001">
          <div class="top"><span class="status-pill upcoming">Sắp khởi hành</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)">06:30 · 22/08/2026</span></div>
          <div class="route-strip"><div class="pt"><div class="city">Hà Nội</div><div class="sub">Mỹ Đình</div></div><div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div><div class="pt end"><div class="city">Ninh Bình</div><div class="sub">Trung tâm</div></div></div>
          <div class="pax-list"><div class="pax-chip"><span class="av">H</span> Tài xế: Trần Văn Hùng · ★4.9</div></div>
          <div class="bottom">
            <span style="font-size:12.5px;color:var(--ink-soft)">2 ghế · <span class="method-tag online">Đã thanh toán online</span></span>
            <button class="btn btn-coral btn-sm" onclick="cancelBooking(this)">Huỷ chuyến</button>
          </div>
        </div>
      </div>

      <div id="ktab-k-running" class="hidden">
        <div class="trip-manage-card" data-booking-id="BK-002">
          <div class="top"><span class="status-pill running">Đang di chuyển</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)">Khởi hành 14:02</span></div>
          <div class="route-strip"><div class="pt"><div class="city">Hà Nội</div><div class="sub">Cầu Giấy</div></div><div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div><div class="pt end"><div class="city">Hải Phòng</div><div class="sub">Lạch Tray</div></div></div>
          <div class="pax-list"><div class="pax-chip"><span class="av">Đ</span> Tài xế: Đỗ Thị Hoa · ★5.0</div></div>
          <div class="bottom">
            <span style="font-size:12.5px;color:var(--ink-soft)">1 ghế · <span class="method-tag cash">Thanh toán tiền mặt khi lên xe</span></span>
            <span style="font-size:12.5px;color:var(--ink-faint)">Dự kiến đến 15:40</span>
          </div>
        </div>
      </div>

      <div id="ktab-k-done" class="hidden">
        <div class="trip-manage-card" data-booking-id="BK-003">
          <div class="top"><span class="status-pill done">Hoàn thành</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)">09/08/2026</span></div>
          <div class="route-strip"><div class="pt"><div class="city">Hà Nội</div><div class="sub">14:00</div></div><div class="route-line"><div class="node start"></div><div class="node end"></div></div><div class="pt end"><div class="city">Hải Phòng</div><div class="sub">15:38</div></div></div>
          <div class="bottom">
            <span style="font-size:12.5px;color:var(--ink-soft)">Đã thanh toán: 120.000đ · <span class="method-tag online">Online</span></span>
            <button class="btn btn-outline btn-sm" onclick="reviewBooking(this)">★ Đánh giá tài xế</button>
          </div>
        </div>
      </div>

      <div id="ktab-k-cancelled" class="hidden">
        <div class="trip-manage-card" data-booking-id="BK-004" style="opacity:.65">
          <div class="top"><span class="status-pill cancelled">Đã huỷ</span><span class="mono" style="font-size:12.5px;color:var(--ink-faint)">01/08/2026</span></div>
          <div class="route-strip"><div class="pt"><div class="city">Hà Nội</div><div class="sub">08:00</div></div><div class="route-line"><div class="node start"></div><div class="node end"></div></div><div class="pt end"><div class="city">Ninh Bình</div><div class="sub">—</div></div></div>
          <div class="bottom"><span style="font-size:12.5px;color:var(--ink-soft)">Đã hoàn tiền 100%</span></div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function switchKTab(tab){
    ['k-upcoming','k-running','k-done','k-cancelled'].forEach(t => document.getElementById('ktab-'+t).classList.toggle('hidden', t !== tab));
    document.querySelectorAll('.tabs button').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  }

  function cancelBooking(btn){
    const card = btn.closest('.trip-manage-card');
    const id = card.dataset.bookingId;
    if(!confirm('Bạn có chắc muốn huỷ chuyến này?')) return;
    // TODO BACKEND: await fetch(`/api/passenger/bookings/${id}/cancel`, {method:'POST'});
    alert('Đã huỷ chuyến ' + id + '. Nếu đã thanh toán online, tiền sẽ được hoàn trong 1-3 ngày làm việc.\n(demo — cần nối API).');
  }

  function reviewBooking(btn){
    const card = btn.closest('.trip-manage-card');
    const id = card.dataset.bookingId;
    // TODO BACKEND: await fetch(`/api/passenger/bookings/${id}/review`, {method:'POST', body: JSON.stringify({rating, comment})});
    alert('Mở form đánh giá tài xế cho chuyến ' + id + ' (demo — cần nối API).');
  }
</script>
</body>
</html>
