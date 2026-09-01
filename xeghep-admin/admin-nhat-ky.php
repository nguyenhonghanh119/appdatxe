<?php
$activePage = 'nhat-ky';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Nhật ký hoạt động</title>
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
         - GET /api/admin/activity-log?actor=&action=&from=&to=   -> bảng nhật ký thao tác quản trị
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Nhật ký hoạt động</h1><p>Lịch sử thao tác của quản trị viên trên hệ thống.</p></div></div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo người thực hiện hoặc hành động…"></div>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Hành động</th><th>Đối tượng</th></tr></thead>
          <tbody>
            <tr><td class="mono">19/08 15:52</td><td>Admin Quản trị</td><td>Duyệt yêu cầu rút tiền</td><td class="mono">WD-401</td></tr>
            <tr><td class="mono">19/08 09:10</td><td>Admin Quản trị</td><td>Khoá tài khoản khách hàng</td><td class="mono">USR-303</td></tr>
            <tr><td class="mono">18/08 21:03</td><td>Nguyễn Thảo Vy</td><td>Duyệt hồ sơ tài xế</td><td class="mono">DRV-198</td></tr>
            <tr><td class="mono">17/08 08:40</td><td>Admin Quản trị</td><td>Cập nhật tỉ lệ hoa hồng: 9% → 10%</td><td class="mono">—</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
