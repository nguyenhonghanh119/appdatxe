<?php
$activePage = 'noi-dung';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Quản lý nội dung</title>
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
         - GET/POST/PUT/DELETE /api/admin/content/banners   -> banner trang chủ app
         - GET/POST/PUT/DELETE /api/admin/content/faq         -> câu hỏi thường gặp
         - GET/POST/PUT/DELETE /api/admin/content/pages        -> trang tĩnh (Điều khoản, Chính sách...)
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Quản lý nội dung</h1><p>Banner, câu hỏi thường gặp và các trang nội dung tĩnh trong app.</p></div></div>

      <div class="card">
        <div class="list-title">Banner trang chủ</div>
        <table>
          <thead><tr><th>Tên banner</th><th>Vị trí</th><th>Hiệu lực</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <tr><td style="font-weight:600">Khuyến mãi khách mới -20%</td><td>Trang chủ - đầu trang</td><td class="mono">01/08 - 31/08/2026</td><td><span class="status-pill approved">Đang hiển thị</span></td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
            <tr><td style="font-weight:600">Tuyển tài xế đối tác</td><td>Trang chủ - cuối trang</td><td class="mono">Không giới hạn</td><td><span class="status-pill approved">Đang hiển thị</span></td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="list-title">Trang nội dung tĩnh</div>
        <table>
          <thead><tr><th>Trang</th><th>Cập nhật lần cuối</th><th></th></tr></thead>
          <tbody>
            <tr><td style="font-weight:600">Điều khoản sử dụng</td><td class="mono">10/06/2026</td><td><button class="btn btn-outline btn-sm">Chỉnh sửa</button></td></tr>
            <tr><td style="font-weight:600">Chính sách bảo mật</td><td class="mono">10/06/2026</td><td><button class="btn btn-outline btn-sm">Chỉnh sửa</button></td></tr>
            <tr><td style="font-weight:600">Câu hỏi thường gặp (FAQ)</td><td class="mono">02/07/2026</td><td><button class="btn btn-outline btn-sm">Chỉnh sửa</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
