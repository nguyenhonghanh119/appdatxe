<?php
$activePage = 'bao-cao';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Báo cáo &amp; thống kê</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main">

    <!-- ==============================================================
         TODO BACKEND:
         - GET /api/admin/reports/summary?from=&to=      -> 4 stat-card + biểu đồ
         - GET /api/admin/reports/export?type=csv&from=&to= -> nút "Xuất báo cáo"
         ============================================================== -->

    <div class="view">
      <div class="topbar">
        <div><h1>Báo cáo &amp; thống kê</h1><p>Tổng hợp số liệu vận hành theo khoảng thời gian.</p></div>
        <button class="btn btn-primary" onclick="alert('Xuất báo cáo CSV (demo — cần nối API).')">⬇ Xuất báo cáo</button>
      </div>

      <div class="filter-bar">
        <select><option>7 ngày qua</option><option>30 ngày qua</option><option>Tháng này</option><option>Tuỳ chọn khoảng ngày</option></select>
      </div>

      <div class="stat-row">
        <div class="stat-card"><div class="lbl">Tổng doanh thu (GMV)</div><div class="val mono">86.400.000đ</div><div class="delta">+18% so với kỳ trước</div></div>
        <div class="stat-card"><div class="lbl">Chuyến hoàn thành</div><div class="val">742</div><div class="delta">+9%</div></div>
        <div class="stat-card"><div class="lbl">Khách hàng mới</div><div class="val">156</div><div class="delta">+22%</div></div>
        <div class="stat-card"><div class="lbl">Tỉ lệ huỷ chuyến</div><div class="val">4.1%</div><div class="delta down">+0.6 điểm %</div></div>
      </div>

      <div class="card">
        <div class="list-title">Doanh thu theo tuyến (top 5)</div>
        <table>
          <thead><tr><th>Tuyến</th><th>Số chuyến</th><th>Doanh thu</th></tr></thead>
          <tbody>
            <tr><td>Hà Nội → Ninh Bình</td><td>318</td><td class="mono">31.200.000đ</td></tr>
            <tr><td>Hà Nội → Hải Phòng</td><td>256</td><td class="mono">24.500.000đ</td></tr>
            <tr><td>Hà Nội → Sapa</td><td>64</td><td class="mono">14.100.000đ</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
