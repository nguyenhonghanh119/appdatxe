<?php
$activePage = 'diem-don-tra';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Điểm đón/trả</title>
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
         - GET    /api/admin/pickup-points?city=          -> danh sách điểm đón/trả cố định
         - POST   /api/admin/pickup-points                 -> nút "+ Thêm điểm"
         - PUT    /api/admin/pickup-points/:id                -> nút "Sửa"
         - DELETE /api/admin/pickup-points/:id                 -> nút "Xoá"
         ============================================================== -->

    <div class="view">
      <div class="topbar">
        <div><h1>Quản lý điểm đón/trả</h1><p>Các điểm đón/trả cố định gợi ý cho khách và tài xế khi đặt/đăng chuyến.</p></div>
        <button class="btn btn-primary" onclick="alert('Mở form thêm điểm đón/trả (demo — cần nối API).')">+ Thêm điểm</button>
      </div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo tên điểm hoặc khu vực…"></div>
        <select><option value="">Tất cả tỉnh/thành</option><option>Hà Nội</option><option>Ninh Bình</option><option>Hải Phòng</option></select>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Tên điểm</th><th>Khu vực</th><th>Loại</th><th>Toạ độ</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td style="font-weight:600">Bến xe Mỹ Đình</td><td>Nam Từ Liêm, Hà Nội</td><td>Đón &amp; Trả</td>
              <td class="mono">21.0287, 105.7809</td>
              <td><span class="status-pill approved">Đang dùng</span></td>
              <td><button class="btn btn-outline btn-sm">Sửa</button> <button class="btn btn-coral btn-sm">Xoá</button></td>
            </tr>
            <tr>
              <td style="font-weight:600">Cổng chào Ninh Bình</td><td>TP. Ninh Bình</td><td>Trả</td>
              <td class="mono">20.2506, 105.9744</td>
              <td><span class="status-pill approved">Đang dùng</span></td>
              <td><button class="btn btn-outline btn-sm">Sửa</button> <button class="btn btn-coral btn-sm">Xoá</button></td>
            </tr>
            <tr>
              <td style="font-weight:600">Sân bay Cát Bi</td><td>Hải An, Hải Phòng</td><td>Đón &amp; Trả</td>
              <td class="mono">20.8194, 106.7248</td>
              <td><span class="status-pill locked">Tạm ẩn</span></td>
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
