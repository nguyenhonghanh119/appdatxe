<?php
$activePage = 'phan-quyen';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Phân quyền</title>
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
         - GET    /api/admin/staff                -> danh sách tài khoản quản trị
         - POST   /api/admin/staff                  -> nút "+ Thêm quản trị viên"
         - PUT    /api/admin/staff/:id/role           -> đổi vai trò
         - DELETE /api/admin/staff/:id                  -> xoá tài khoản
         ============================================================== -->

    <div class="view">
      <div class="topbar">
        <div><h1>Phân quyền</h1><p>Quản lý tài khoản quản trị viên và vai trò truy cập hệ thống.</p></div>
        <button class="btn btn-primary" onclick="alert('Mở form thêm quản trị viên (demo — cần nối API).')">+ Thêm quản trị viên</button>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <tr><td style="font-weight:600">Admin Quản trị</td><td class="mono">quantri@xeghep.vn</td><td>Quản trị viên (Toàn quyền)</td><td><span class="status-pill approved">Đang hoạt động</span></td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
            <tr><td style="font-weight:600">Nguyễn Thảo Vy</td><td class="mono">vy.nguyen@xeghep.vn</td><td>Nhân viên hỗ trợ</td><td><span class="status-pill approved">Đang hoạt động</span></td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
            <tr><td style="font-weight:600">Hoàng Minh Khôi</td><td class="mono">khoi.hoang@xeghep.vn</td><td>Kế toán</td><td><span class="status-pill locked">Đã khoá</span></td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
