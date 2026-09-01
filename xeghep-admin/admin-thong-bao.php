<?php
$activePage = 'thong-bao';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Thông báo</title>
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
         - POST /api/admin/notifications/broadcast     -> nút "Gửi thông báo" (target: all|customers|drivers)
         - GET  /api/admin/notifications/history          -> bảng lịch sử bên dưới
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Thông báo</h1><p>Soạn và gửi thông báo đẩy đến khách hàng và tài xế.</p></div></div>

      <div class="card">
        <div class="list-title">Soạn thông báo mới</div>
        <div class="form-grid">
          <div class="field full"><label>Tiêu đề</label><div class="input"><input placeholder="Ví dụ: Bảo trì hệ thống 02:00 - 04:00 ngày 25/08"></div></div>
          <div class="field full"><label>Nội dung</label><textarea placeholder="Nội dung thông báo…"></textarea></div>
          <div class="field"><label>Gửi đến</label><div class="input"><select><option>Tất cả</option><option>Chỉ khách hàng</option><option>Chỉ tài xế</option></select></div></div>
        </div>
        <button class="btn btn-primary" style="margin-top:16px" onclick="alert('Đã gửi thông báo (demo — cần nối API).')">Gửi thông báo</button>
      </div>

      <div class="card">
        <div class="list-title">Lịch sử thông báo đã gửi</div>
        <table>
          <thead><tr><th>Tiêu đề</th><th>Đối tượng</th><th>Ngày gửi</th></tr></thead>
          <tbody>
            <tr><td style="font-weight:600">Ưu đãi mã KHACHMOI cho khách mới</td><td>Khách hàng</td><td class="mono">15/08/2026</td></tr>
            <tr><td style="font-weight:600">Cập nhật chính sách hoa hồng 10%</td><td>Tài xế</td><td class="mono">01/08/2026</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
