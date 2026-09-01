<?php
$activePage = 'chuyen-xe';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Quản lý chuyến xe</title>
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
         - GET /api/admin/trips?status=&query=   -> danh sách tất cả chuyến xe do tài xế đăng
         - GET /api/admin/trips/:id                -> nút "Chi tiết"
         ============================================================== -->

    <div class="view" id="view-admin-trips">
      <div class="topbar"><div><h1>Quản lý chuyến xe</h1><p>Theo dõi toàn bộ chuyến xe do tài xế đăng trên hệ thống.</p></div></div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo mã chuyến, tài xế hoặc tuyến…" id="trip-search"></div>
        <select id="trip-status-filter">
          <option value="">Tất cả trạng thái</option>
          <option>Sắp khởi hành</option>
          <option>Đang chạy</option>
          <option>Hoàn thành</option>
          <option>Đã huỷ</option>
        </select>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Mã chuyến</th><th>Tuyến</th><th>Tài xế</th><th>Khách</th><th>Ngày giờ</th><th>Ghế</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody id="trips-table">
            <tr data-trip-id="TRIP-001">
              <td class="mono">TRIP-001</td><td>Hà Nội → Ninh Bình</td><td>Trần Văn Hùng</td><td>2 khách</td>
              <td class="mono">22/08 · 06:30</td><td>3/3</td>
              <td><span class="status-pill upcoming">Sắp khởi hành</span></td>
              <td><button class="btn btn-outline btn-sm" onclick="viewTrip(this)">Chi tiết</button></td>
            </tr>
            <tr data-trip-id="TRIP-002">
              <td class="mono">TRIP-002</td><td>Hà Nội → Hải Phòng</td><td>Đỗ Thị Hoa</td><td>1 khách</td>
              <td class="mono">19/08 · 14:02</td><td>1/6</td>
              <td><span class="status-pill running">Đang chạy</span></td>
              <td><button class="btn btn-outline btn-sm" onclick="viewTrip(this)">Chi tiết</button></td>
            </tr>
            <tr data-trip-id="TRIP-003">
              <td class="mono">TRIP-003</td><td>Hà Nội → Hải Phòng</td><td>Trần Văn Hùng</td><td>1 khách</td>
              <td class="mono">09/08 · 14:00</td><td>1/4</td>
              <td><span class="status-pill done">Hoàn thành</span></td>
              <td><button class="btn btn-outline btn-sm" onclick="viewTrip(this)">Chi tiết</button></td>
            </tr>
            <tr data-trip-id="TRIP-004">
              <td class="mono">TRIP-004</td><td>Hà Nội → Ninh Bình</td><td>Phạm Đức Long</td><td>0 khách</td>
              <td class="mono">01/08 · 08:00</td><td>0/4</td>
              <td><span class="status-pill cancelled">Đã huỷ</span></td>
              <td><button class="btn btn-outline btn-sm" onclick="viewTrip(this)">Chi tiết</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function viewTrip(btn){
    const id = btn.closest('tr').dataset.tripId;
    // TODO BACKEND: điều hướng đến trang chi tiết chuyến, ví dụ location.href = `admin-chuyen-di-chitiet.php?id=${id}`;
    alert('Xem chi tiết chuyến ' + id + ' (demo — cần nối API).');
  }
</script>
</body>
</html>
