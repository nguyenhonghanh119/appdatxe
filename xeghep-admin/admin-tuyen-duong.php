<?php
$activePage = 'tuyen-duong';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Quản lý tuyến đường</title>
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
         - GET    /api/admin/routes?query=          -> danh sách tuyến cố định
         - POST   /api/admin/routes                  -> nút "+ Thêm tuyến"
         - PUT    /api/admin/routes/:id                -> nút "Sửa"
         - DELETE /api/admin/routes/:id                 -> nút "Xoá"
         ============================================================== -->

    <div class="view">
      <div class="topbar">
        <div><h1>Quản lý tuyến đường</h1><p>Các tuyến đường dài cố định để gợi ý cho tài xế khi đăng chuyến.</p></div>
        <button class="btn btn-primary" onclick="alert('Mở form thêm tuyến đường (demo — cần nối API).')">+ Thêm tuyến</button>
      </div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo điểm đi hoặc điểm đến…"></div>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Tuyến</th><th>Khoảng cách</th><th>Giá đề xuất / ghế</th><th>Số chuyến đã chạy</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td style="font-weight:600">Hà Nội → Ninh Bình</td><td class="mono">95 km</td><td class="mono">130.000đ</td><td>318</td>
              <td><span class="status-pill approved">Đang mở</span></td>
              <td><button class="btn btn-outline btn-sm">Sửa</button> <button class="btn btn-coral btn-sm">Xoá</button></td>
            </tr>
            <tr>
              <td style="font-weight:600">Hà Nội → Hải Phòng</td><td class="mono">105 km</td><td class="mono">120.000đ</td><td>256</td>
              <td><span class="status-pill approved">Đang mở</span></td>
              <td><button class="btn btn-outline btn-sm">Sửa</button> <button class="btn btn-coral btn-sm">Xoá</button></td>
            </tr>
            <tr>
              <td style="font-weight:600">Hà Nội → Sapa</td><td class="mono">315 km</td><td class="mono">280.000đ</td><td>64</td>
              <td><span class="status-pill locked">Tạm khoá</span></td>
              <td><button class="btn btn-outline btn-sm">Sửa</button> <button class="btn btn-coral btn-sm">Xoá</button></td>
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
