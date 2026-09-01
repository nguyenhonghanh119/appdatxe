<?php
$activePage = 'dashboard';
$pendingDriverCount = 2; // TODO BACKEND: lấy từ COUNT(*) WHERE status='pending' bảng drivers
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Dashboard</title>
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
         - GET /api/admin/stats/overview        -> 4 stat-card đầu
         - GET /api/admin/stats/revenue-trend    -> dữ liệu biểu đồ doanh thu 7 ngày
         - GET /api/admin/drivers/pending         -> danh sách tài xế chờ duyệt (rút gọn)
         - GET /api/admin/activity/recent          -> hoạt động gần đây toàn hệ thống
         ============================================================== -->

    <div class="view" id="view-admin-overview">
      <div class="topbar">
        <div><h1>Dashboard</h1><p>Thứ Tư, 19/08/2026</p></div>
        <a class="btn btn-primary" href="admin-giao-dich.php">💳 Xem thanh toán</a>
      </div>

      <div class="stat-row">
        <div class="stat-card"><div class="lbl">Tổng doanh thu tháng 08</div><div class="val mono">86.400.000đ</div><div class="delta">+18% so với tháng trước</div></div>
        <div class="stat-card"><div class="lbl">Hoa hồng nền tảng thu được</div><div class="val mono">8.640.000đ</div><div class="delta">Tỉ lệ hiện tại: 10%</div></div>
        <div class="stat-card"><div class="lbl">Chuyến hoàn thành</div><div class="val">742</div><div class="delta">Tháng 08/2026</div></div>
        <div class="stat-card"><div class="lbl">Tài xế đang hoạt động</div><div class="val">128</div><div class="delta down">2 hồ sơ chờ duyệt</div></div>
      </div>

      <div class="section-block">
        <div class="card">
          <div class="list-title">Doanh thu 7 ngày gần nhất</div>
          <div class="chart-legend"><span><i style="background:var(--amber)"></i> Tổng doanh thu (GMV)</span><span><i style="background:var(--navy-2)"></i> Hoa hồng nền tảng</span></div>
          <div class="bar-chart">
            <div class="bar"><div class="fill" style="height:62%"></div><div class="fill alt" style="height:8%"></div><span class="lbl">13/08</span></div>
            <div class="bar"><div class="fill" style="height:48%"></div><div class="fill alt" style="height:6%"></div><span class="lbl">14/08</span></div>
            <div class="bar"><div class="fill" style="height:70%"></div><div class="fill alt" style="height:9%"></div><span class="lbl">15/08</span></div>
            <div class="bar"><div class="fill" style="height:55%"></div><div class="fill alt" style="height:7%"></div><span class="lbl">16/08</span></div>
            <div class="bar"><div class="fill" style="height:88%"></div><div class="fill alt" style="height:11%"></div><span class="lbl">17/08</span></div>
            <div class="bar"><div class="fill" style="height:95%"></div><div class="fill alt" style="height:12%"></div><span class="lbl">18/08</span></div>
            <div class="bar"><div class="fill" style="height:40%"></div><div class="fill alt" style="height:5%"></div><span class="lbl">19/08</span></div>
          </div>
        </div>
        <div class="card">
          <div class="list-title">Hoạt động gần đây</div>
          <div id="activity-list">
            <div class="activity-item"><div class="ic">💵</div><div><b>Chuyến TRIP-118 thanh toán online — đã cộng 108.000đ vào ví tài xế Trần Văn Hùng</b><span>5 phút trước</span></div></div>
            <div class="activity-item"><div class="ic">📥</div><div><b>Tài xế mới đăng ký: Nguyễn Văn Tùng</b><span>32 phút trước</span></div></div>
            <div class="activity-item"><div class="ic">🏦</div><div><b>Duyệt yêu cầu rút tiền 1.000.000đ cho Trần Văn Hùng</b><span>1 giờ trước</span></div></div>
            <div class="activity-item"><div class="ic">🚫</div><div><b>Khách hàng Vũ Minh Đức bị khoá do vi phạm chính sách</b><span>Hôm qua</span></div></div>
          </div>
        </div>
      </div>

      <div class="card" style="margin-top:16px">
        <div class="list-title">Tài xế chờ duyệt hồ sơ</div>
        <div class="admin-row">
          <div class="av">T</div>
          <div class="meta"><b>Nguyễn Văn Tùng</b><div class="sub">Toyota Innova · 30G-112.45 · Nộp hồ sơ 18/08/2026</div></div>
          <div class="actions"><a class="btn btn-outline btn-sm" href="admin-tai-xe.php">Xem hồ sơ</a></div>
        </div>
        <div class="admin-row" style="margin-bottom:0">
          <div class="av">B</div>
          <div class="meta"><b>Đặng Quốc Bảo</b><div class="sub">Kia Sedona · 29H-556.78 · Nộp hồ sơ 17/08/2026</div></div>
          <div class="actions"><a class="btn btn-outline btn-sm" href="admin-tai-xe.php">Xem hồ sơ</a></div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
</body>
</html>
