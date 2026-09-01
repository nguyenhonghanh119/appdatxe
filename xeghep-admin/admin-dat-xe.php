<?php
$activePage = 'dat-xe';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Quản lý đặt xe</title>
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
         - GET  /api/admin/bookings?status=&query=     -> danh sách yêu cầu đặt chỗ của khách trên từng chuyến
         - POST /api/admin/bookings/:id/cancel           -> nút "Huỷ hộ khách" (khi có tranh chấp)
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Quản lý đặt xe</h1><p>Danh sách yêu cầu đặt chỗ của khách trên các chuyến xe.</p></div></div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo mã đặt chỗ, khách hoặc chuyến…"></div>
        <select><option value="">Tất cả trạng thái</option><option>Chờ xác nhận</option><option>Đã xác nhận</option><option>Đã huỷ</option></select>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Mã đặt chỗ</th><th>Chuyến</th><th>Khách</th><th>Ghế đặt</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td class="mono">BK-5510</td><td class="mono">TRIP-001</td><td>Nguyễn Thị Lan</td><td>2</td>
              <td><span class="method-tag online">Online</span></td>
              <td><span class="status-pill approved">Đã xác nhận</span></td>
              <td><button class="btn btn-outline btn-sm">Chi tiết</button></td>
            </tr>
            <tr>
              <td class="mono">BK-5511</td><td class="mono">TRIP-002</td><td>Lê Quốc Anh</td><td>1</td>
              <td><span class="method-tag cash">Tiền mặt</span></td>
              <td><span class="status-pill pending">Chờ xác nhận</span></td>
              <td><button class="btn btn-outline btn-sm">Chi tiết</button></td>
            </tr>
            <tr>
              <td class="mono">BK-5498</td><td class="mono">TRIP-081</td><td>Vũ Minh Đức</td><td>1</td>
              <td><span class="method-tag online">Online</span></td>
              <td><span class="status-pill cancelled">Đã huỷ</span></td>
              <td><button class="btn btn-outline btn-sm">Chi tiết</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
