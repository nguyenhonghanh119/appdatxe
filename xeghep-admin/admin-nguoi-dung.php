<?php
$activePage = 'khach-hang';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Khách hàng</title>
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
         - GET  /api/admin/users?query=&status=      -> danh sách hành khách
         - POST /api/admin/users/:id/suspend          -> nút "Khoá tài khoản"
         - POST /api/admin/users/:id/reactivate        -> nút "Mở khoá"
         ============================================================== -->

    <div class="view" id="view-admin-users">
      <div class="topbar"><div><h1>Khách hàng</h1><p>Danh sách hành khách đã đăng ký trên hệ thống.</p></div></div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo tên, SĐT hoặc email…" id="user-search"></div>
        <select id="user-status-filter"><option value="">Tất cả trạng thái</option><option>Đang hoạt động</option><option>Bị khoá</option></select>
      </div>

      <div class="card">
        <table>
          <thead><tr><th>Khách hàng</th><th>SĐT</th><th>Ngày tham gia</th><th>Số chuyến</th><th>Trạng thái</th><th></th></tr></thead>
          <tbody id="users-table">
            <tr data-user-id="USR-301">
              <td style="font-weight:600">Nguyễn Thị Lan</td>
              <td class="mono">091 888 2233</td>
              <td class="mono">14/02/2024</td>
              <td>47</td>
              <td><span class="status-pill approved">Đang hoạt động</span></td>
              <td><button class="btn btn-coral btn-sm" onclick="toggleUserLock(this,'lock')">Khoá</button></td>
            </tr>
            <tr data-user-id="USR-302">
              <td style="font-weight:600">Lê Quốc Anh</td>
              <td class="mono">090 771 4432</td>
              <td class="mono">03/05/2024</td>
              <td>112</td>
              <td><span class="status-pill approved">Đang hoạt động</span></td>
              <td><button class="btn btn-coral btn-sm" onclick="toggleUserLock(this,'lock')">Khoá</button></td>
            </tr>
            <tr data-user-id="USR-303">
              <td style="font-weight:600">Vũ Minh Đức</td>
              <td class="mono">098 223 9981</td>
              <td class="mono">21/11/2023</td>
              <td>9</td>
              <td><span class="status-pill locked">Bị khoá</span></td>
              <td><button class="btn btn-green btn-sm" onclick="toggleUserLock(this,'unlock')">Mở khoá</button></td>
            </tr>
            <tr data-user-id="USR-304">
              <td style="font-weight:600">Trịnh Thu Hà</td>
              <td class="mono">096 550 1123</td>
              <td class="mono">09/01/2025</td>
              <td>23</td>
              <td><span class="status-pill approved">Đang hoạt động</span></td>
              <td><button class="btn btn-coral btn-sm" onclick="toggleUserLock(this,'lock')">Khoá</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function toggleUserLock(btn, action){
    const row = btn.closest('tr');
    const id = row.dataset.userId;
    const statusCell = row.querySelector('.status-pill');
    if (action === 'lock'){
      if(!confirm('Khoá tài khoản người dùng này?')) return;
      // TODO BACKEND: await fetch(`/api/admin/users/${id}/suspend`, {method:'POST'});
      statusCell.textContent = 'Bị khoá';
      statusCell.className = 'status-pill locked';
      btn.textContent = 'Mở khoá';
      btn.className = 'btn btn-green btn-sm';
      btn.setAttribute('onclick', "toggleUserLock(this,'unlock')");
    } else {
      // TODO BACKEND: await fetch(`/api/admin/users/${id}/reactivate`, {method:'POST'});
      statusCell.textContent = 'Đang hoạt động';
      statusCell.className = 'status-pill approved';
      btn.textContent = 'Khoá';
      btn.className = 'btn btn-coral btn-sm';
      btn.setAttribute('onclick', "toggleUserLock(this,'lock')");
    }
  }
</script>
</body>
</html>
