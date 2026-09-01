<?php
$activePage = 'phuong-tien';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Quản lý phương tiện</title>
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
         - GET  /api/admin/vehicles?query=&status=       -> danh sách phương tiện + hồ sơ giấy tờ
         - GET  /api/admin/vehicles/:id/documents         -> nút "Xem giấy tờ" (đăng ký xe, đăng kiểm, bảo hiểm)
         - POST /api/admin/vehicles/:id/approve            -> duyệt xe mới
         - POST /api/admin/vehicles/:id/reject              -> từ chối / yêu cầu bổ sung giấy tờ
         - Cảnh báo tự động khi hạn đăng kiểm/bảo hiểm còn < 30 ngày (cron job hằng ngày)
         ============================================================== -->

    <div class="view">
      <div class="topbar">
        <div><h1>Quản lý phương tiện</h1><p>Danh sách xe của tài xế, giấy tờ đăng ký, đăng kiểm và bảo hiểm.</p></div>
        <button class="btn btn-primary" onclick="alert('Mở form thêm phương tiện thủ công (demo — cần nối API).')">+ Thêm phương tiện</button>
      </div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo biển số hoặc tên tài xế…"></div>
        <select><option value="">Tất cả loại xe</option><option>4 chỗ</option><option>7 chỗ</option><option>16 chỗ</option></select>
        <select><option value="">Tất cả trạng thái</option><option>Đã duyệt</option><option>Chờ duyệt</option><option>Sắp hết hạn đăng kiểm</option></select>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Biển số</th><th>Loại xe</th><th>Chủ xe</th><th>Số chỗ</th><th>Hạn đăng kiểm</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td class="mono" style="font-weight:600">30F-689.21</td><td>Kia Carnival</td><td>Trần Văn Hùng</td><td>7</td>
              <td class="mono">14/03/2027</td>
              <td><span class="status-pill approved">Đã duyệt</span></td>
              <td><button class="btn btn-outline btn-sm">Xem giấy tờ</button></td>
            </tr>
            <tr>
              <td class="mono" style="font-weight:600">29A-334.09</td><td>Toyota Innova</td><td>Phạm Đức Long</td><td>7</td>
              <td class="mono">02/09/2026</td>
              <td><span class="status-pill pending">Sắp hết hạn đăng kiểm</span></td>
              <td><button class="btn btn-outline btn-sm">Xem giấy tờ</button></td>
            </tr>
            <tr>
              <td class="mono" style="font-weight:600">30G-112.45</td><td>Toyota Innova</td><td>Nguyễn Văn Tùng</td><td>7</td>
              <td class="mono">—</td>
              <td><span class="status-pill pending">Chờ duyệt</span></td>
              <td><button class="btn btn-outline btn-sm">Xem giấy tờ</button></td>
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
