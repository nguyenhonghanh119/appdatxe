<?php
$activePage = 'cau-hinh';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Cấu hình hệ thống</title>
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
         - GET /api/admin/settings/system     -> cấu hình chung
         - PUT /api/admin/settings/system      -> nút "Lưu cấu hình"
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Cấu hình hệ thống</h1><p>Thông tin nền tảng và các giới hạn vận hành chung.</p></div></div>

      <div class="card">
        <div class="list-title">Thông tin nền tảng</div>
        <div class="form-grid">
          <div class="field"><label>Tên nền tảng</label><div class="input"><input value="XeGhép"></div></div>
          <div class="field"><label>Email hỗ trợ</label><div class="input"><input value="support@xeghep.vn"></div></div>
          <div class="field"><label>Hotline</label><div class="input"><input value="1900 8888"></div></div>
          <div class="field"><label>Số ghế tối đa / chuyến</label><div class="input"><input type="number" value="16"></div></div>
        </div>
      </div>

      <div class="card">
        <div class="list-title">Giới hạn &amp; quy tắc vận hành</div>
        <div class="settings-row"><div class="t"><b>Cho phép huỷ chuyến miễn phí trước</b><span>Số phút trước giờ khởi hành mà khách/tài xế được huỷ không mất phí.</span></div><div class="input" style="width:90px"><input type="number" value="60">phút</div></div>
        <div class="settings-row"><div class="t"><b>Yêu cầu duyệt hồ sơ tài xế thủ công</b><span>Tắt để tài xế mới tự động được duyệt nếu giấy tờ hợp lệ theo OCR.</span></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
        <div class="settings-row"><div class="t"><b>Chế độ bảo trì</b><span>Tạm ẩn ứng dụng khách/tài xế để bảo trì hệ thống.</span></div><label class="switch"><input type="checkbox"><span class="slider"></span></label></div>
      </div>

      <button class="btn btn-primary" onclick="alert('Đã lưu cấu hình hệ thống (demo — cần nối API).')">Lưu cấu hình</button>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
