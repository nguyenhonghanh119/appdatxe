<?php
$activePage = 'khuyen-mai';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Khuyến mãi</title>
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
         - GET    /api/admin/promotions?status=      -> danh sách mã khuyến mãi
         - POST   /api/admin/promotions                -> nút "+ Tạo mã"
         - PUT    /api/admin/promotions/:id/toggle       -> công tắc Bật/Tắt
         - DELETE /api/admin/promotions/:id               -> nút "Xoá"
         ============================================================== -->

    <div class="view">
      <div class="topbar">
        <div><h1>Khuyến mãi</h1><p>Tạo và quản lý mã giảm giá cho khách hàng.</p></div>
        <button class="btn btn-primary" onclick="alert('Mở form tạo mã khuyến mãi (demo — cần nối API).')">+ Tạo mã</button>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Mã</th><th>Loại giảm giá</th><th>Điều kiện</th><th>Hạn dùng</th><th>Đã dùng</th><th>Trạng thái</th></tr></thead>
          <tbody>
            <tr>
              <td class="mono" style="font-weight:600">XEGHEP50</td><td>Giảm 50.000đ</td><td>Đơn từ 150.000đ</td>
              <td class="mono">31/08/2026</td><td>214</td>
              <td><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></td>
            </tr>
            <tr>
              <td class="mono" style="font-weight:600">KHACHMOI</td><td>Giảm 20%</td><td>Chuyến đầu tiên</td>
              <td class="mono">Không giới hạn</td><td>1.032</td>
              <td><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></td>
            </tr>
            <tr>
              <td class="mono" style="font-weight:600">TET2026</td><td>Giảm 15%</td><td>Tuyến liên tỉnh &gt; 100km</td>
              <td class="mono">15/02/2026</td><td>388</td>
              <td><label class="switch"><input type="checkbox"><span class="slider"></span></label></td>
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
