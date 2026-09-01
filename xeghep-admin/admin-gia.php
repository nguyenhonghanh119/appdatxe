<?php
$activePage = 'gia';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Quản lý giá</title>
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
         - GET /api/admin/pricing                  -> giá/km mặc định + phụ phí
         - PUT /api/admin/pricing                   -> nút "Lưu cài đặt giá"
         - GET/PUT /api/admin/pricing/route-overrides -> bảng giá riêng theo tuyến
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Quản lý giá</h1><p>Cấu hình đơn giá mặc định và giá riêng theo từng tuyến.</p></div></div>

      <div class="card">
        <div class="list-title">Đơn giá mặc định</div>
        <div class="form-grid">
          <div class="field"><label>Giá mỗi km / ghế</label><div class="input"><input type="number" value="1500">đ</div></div>
          <div class="field"><label>Phí mở cuốc</label><div class="input"><input type="number" value="10000">đ</div></div>
          <div class="field"><label>Giá tối thiểu / chuyến</label><div class="input"><input type="number" value="50000">đ</div></div>
          <div class="field"><label>Hệ số giờ cao điểm</label><div class="input"><input type="number" value="1.2" step="0.1">x</div></div>
        </div>
        <button class="btn btn-primary" style="margin-top:16px" onclick="alert('Đã lưu cài đặt giá (demo — cần nối API).')">Lưu cài đặt giá</button>
      </div>

      <div class="card">
        <div class="list-title">Giá riêng theo tuyến (ghi đè giá mặc định)</div>
        <table>
          <thead><tr><th>Tuyến</th><th>Giá / ghế</th><th>Cập nhật lần cuối</th><th></th></tr></thead>
          <tbody>
            <tr><td style="font-weight:600">Hà Nội → Ninh Bình</td><td class="mono">130.000đ</td><td class="mono">12/08/2026</td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
            <tr><td style="font-weight:600">Hà Nội → Hải Phòng</td><td class="mono">120.000đ</td><td class="mono">08/08/2026</td><td><button class="btn btn-outline btn-sm">Sửa</button></td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
</body>
</html>
