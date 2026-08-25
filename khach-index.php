<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Hành khách — Tìm chuyến</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>HÀNH KHÁCH</small></div></div>
    <nav class="side-nav">
      <a href="khach-index.php" class="active"><span class="ic">🔍</span> Tìm chuyến</a>
      <a href="khach-chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi <span class="badge-count">1</span></a>
      <a href="khach-ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; Thanh toán</a>
    </nav>
    <div class="sidebar-foot"><div class="av">L</div><div><b>Nguyễn Thị Lan</b><span>091 888 2233</span><span class="role-pill">Đã xác thực</span></div></div>
  </aside>

  <main class="main">

    <!-- ==============================================================
         TODO BACKEND:
         - GET /api/passenger/me                         -> tên, avatar, trạng thái xác thực (sidebar-foot)
         - GET /api/passenger/trips/search?from&to&date&seats -> danh sách chuyến phù hợp (#search-results)
         - GET /api/passenger/trips/suggested             -> tuyến gợi ý / phổ biến (#suggested-routes)
         ============================================================== -->

    <div class="view" id="view-search">
      <div class="topbar"><div><h1>Bạn muốn đi đâu hôm nay? 👋</h1><p>Tìm chuyến ghép phù hợp với lịch trình của bạn.</p></div></div>

      <div class="card">
        <form id="search-form" class="form-grid" onsubmit="return searchTrips(event)">
          <div class="field"><label>Điểm đón</label><div class="input">📍<input name="from" placeholder="VD: Hà Nội" value="Hà Nội" required></div></div>
          <div class="field"><label>Điểm đến</label><div class="input">🏁<input name="to" placeholder="VD: Ninh Bình" required></div></div>
          <div class="field"><label>Ngày đi</label><div class="input">📅<input type="date" name="date" value="2026-08-22" required></div></div>
          <div class="field">
            <label>Số ghế</label>
            <div class="qty-stepper">
              <button type="button" onclick="changeSeats(-1)">−</button>
              <span id="seat-count">1</span>
              <button type="button" onclick="changeSeats(1)">+</button>
              <input type="hidden" name="seats" id="seat-count-input" value="1">
            </div>
          </div>
          <button type="submit" class="btn btn-primary full" style="margin-top:4px;padding:12px 20px;justify-self:start">🔍 Tìm chuyến</button>
        </form>
      </div>

      <div class="list-title" style="margin-top:24px">Chuyến phù hợp (<span id="result-count">3</span>)</div>
      <div id="search-results">

        <div class="card">
          <div class="top" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <span class="mono" style="font-size:12.5px;color:var(--ink-faint)">06:30 · Th.Bảy 22/08/2026</span>
            <span class="mono" style="font-weight:700;color:var(--navy)">85.000đ/ghế</span>
          </div>
          <div class="route-strip" style="margin-bottom:14px"><div class="pt"><div class="city">Hà Nội</div><div class="sub">Bến xe Mỹ Đình</div></div><div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div><div class="pt end"><div class="city">Ninh Bình</div><div class="sub">Trung tâm TP</div></div></div>
          <div class="bottom" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--ink-soft)">🚐 Kia Carnival · Trần Văn Hùng · ★ 4.9 · Còn 3 ghế</span>
            <a class="btn btn-primary btn-sm" href="khach-dat-cho.php?trip=TRIP-001">Đặt chỗ</a>
          </div>
        </div>

        <div class="card">
          <div class="top" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <span class="mono" style="font-size:12.5px;color:var(--ink-faint)">08:00 · Th.Bảy 22/08/2026</span>
            <span class="mono" style="font-weight:700;color:var(--navy)">90.000đ/ghế</span>
          </div>
          <div class="route-strip" style="margin-bottom:14px"><div class="pt"><div class="city">Hà Nội</div><div class="sub">Cầu Giấy</div></div><div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div><div class="pt end"><div class="city">Ninh Bình</div><div class="sub">Tam Cốc</div></div></div>
          <div class="bottom" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--ink-soft)">🚗 Toyota Innova · Phạm Đức Long · ★ 4.7 · Còn 4 ghế</span>
            <a class="btn btn-primary btn-sm" href="khach-dat-cho.php?trip=TRIP-004">Đặt chỗ</a>
          </div>
        </div>

        <div class="card">
          <div class="top" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <span class="mono" style="font-size:12.5px;color:var(--ink-faint)">14:00 · Th.Bảy 22/08/2026</span>
            <span class="mono" style="font-weight:700;color:var(--navy)">80.000đ/ghế</span>
          </div>
          <div class="route-strip" style="margin-bottom:14px"><div class="pt"><div class="city">Hà Nội</div><div class="sub">Giáp Bát</div></div><div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div><div class="pt end"><div class="city">Ninh Bình</div><div class="sub">Ga Ninh Bình</div></div></div>
          <div class="bottom" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:13px;color:var(--ink-soft)">🚐 Ford Transit · Đỗ Thị Hoa · ★ 5.0 · Còn 2 ghế</span>
            <a class="btn btn-primary btn-sm" href="khach-dat-cho.php?trip=TRIP-005">Đặt chỗ</a>
          </div>
        </div>

      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  let seats = 1;
  function changeSeats(delta){
    seats = Math.max(1, Math.min(8, seats + delta));
    document.getElementById('seat-count').textContent = seats;
    document.getElementById('seat-count-input').value = seats;
  }

  function searchTrips(e){
    e.preventDefault();
    const form = document.getElementById('search-form');
    const data = Object.fromEntries(new FormData(form).entries());
    // TODO BACKEND: const res = await fetch('/api/passenger/trips/search?' + new URLSearchParams(data));
    // const trips = await res.json(); render lại #search-results và #result-count theo trips.
    document.querySelector('.list-title').scrollIntoView({behavior:'smooth', block:'start'});
    return false;
  }
</script>
</body>
</html>
