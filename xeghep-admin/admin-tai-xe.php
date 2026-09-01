<?php
$activePage = 'tai-xe';
$pendingDriverCount = 2; // TODO BACKEND: COUNT(*) WHERE status='pending'
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Tài xế</title>
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
         - GET  /api/admin/drivers?status=pending|active|locked&query=  -> dữ liệu 3 tab + tìm kiếm
         - GET  /api/admin/drivers/:id/documents   -> nút "Xem giấy tờ"
         - POST /api/admin/drivers/:id/approve      -> nút "Duyệt hồ sơ"
         - POST /api/admin/drivers/:id/reject        -> nút "Từ chối"
         - POST /api/admin/drivers/:id/suspend        -> nút "Khoá tài khoản"
         - POST /api/admin/drivers/:id/reactivate      -> nút "Mở khoá"
         ============================================================== -->

    <div class="view" id="view-admin-drivers">
      <div class="topbar"><div><h1>Tài xế</h1><p>Duyệt hồ sơ đăng ký và quản lý trạng thái hoạt động của tài xế.</p></div></div>

      <div class="filter-bar">
        <div class="input">🔍<input placeholder="Tìm theo tên, SĐT hoặc biển số…" id="driver-search"></div>
        <select id="driver-vehicle-filter"><option value="">Tất cả loại xe</option><option>4 chỗ</option><option>7 chỗ</option></select>
      </div>

      <div class="tabs">
        <button class="active" data-tab="a-pending" onclick="switchATab('a-pending')">Chờ duyệt <span class="badge-count" style="background:var(--coral);color:#fff;font-size:10.5px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">2</span></button>
        <button data-tab="a-active" onclick="switchATab('a-active')">Đang hoạt động</button>
        <button data-tab="a-locked" onclick="switchATab('a-locked')">Bị khoá</button>
      </div>

      <div id="atab-a-pending">
        <div class="admin-row" data-driver-id="DRV-201">
          <div class="av">T</div>
          <div class="meta"><b>Nguyễn Văn Tùng</b><div class="sub">090 112 2345 · Toyota Innova · 30G-112.45</div></div>
          <div class="stats"><div><b>—</b>Chuyến</div><div><b>—</b>Đánh giá</div></div>
          <div class="actions">
            <button class="btn btn-outline btn-sm" onclick="viewDocs(this)">Xem giấy tờ</button>
            <button class="btn btn-green btn-sm" onclick="approveDriver(this)">Duyệt</button>
            <button class="btn btn-coral btn-sm" onclick="rejectDriver(this)">Từ chối</button>
          </div>
        </div>
        <div class="admin-row" data-driver-id="DRV-202">
          <div class="av">B</div>
          <div class="meta"><b>Đặng Quốc Bảo</b><div class="sub">098 556 7890 · Kia Sedona · 29H-556.78</div></div>
          <div class="stats"><div><b>—</b>Chuyến</div><div><b>—</b>Đánh giá</div></div>
          <div class="actions">
            <button class="btn btn-outline btn-sm" onclick="viewDocs(this)">Xem giấy tờ</button>
            <button class="btn btn-green btn-sm" onclick="approveDriver(this)">Duyệt</button>
            <button class="btn btn-coral btn-sm" onclick="rejectDriver(this)">Từ chối</button>
          </div>
        </div>
      </div>

      <div id="atab-a-active" class="hidden">
        <div class="admin-row" data-driver-id="DRV-101">
          <div class="av">H</div>
          <div class="meta"><b>Trần Văn Hùng</b><div class="sub">091 234 5678 · Kia Carnival · 30F-689.21</div></div>
          <div class="stats"><div><b>312</b>Chuyến</div><div><b>4.9★</b>Đánh giá</div></div>
          <div class="actions">
            <button class="btn btn-outline btn-sm" onclick="viewDocs(this)">Xem hồ sơ</button>
            <button class="btn btn-coral btn-sm" onclick="suspendDriver(this)">Khoá tài khoản</button>
          </div>
        </div>
        <div class="admin-row" data-driver-id="DRV-102">
          <div class="av">L</div>
          <div class="meta"><b>Phạm Đức Long</b><div class="sub">097 345 1122 · Toyota Innova · 29A-334.09</div></div>
          <div class="stats"><div><b>198</b>Chuyến</div><div><b>4.7★</b>Đánh giá</div></div>
          <div class="actions">
            <button class="btn btn-outline btn-sm" onclick="viewDocs(this)">Xem hồ sơ</button>
            <button class="btn btn-coral btn-sm" onclick="suspendDriver(this)">Khoá tài khoản</button>
          </div>
        </div>
      </div>

      <div id="atab-a-locked" class="hidden">
        <div class="admin-row" data-driver-id="DRV-050" style="opacity:.7">
          <div class="av">K</div>
          <div class="meta"><b>Vũ Anh Khoa</b><div class="sub">Bị khoá 02/07/2026 · Lý do: hủy chuyến liên tục</div></div>
          <div class="stats"><div><b>44</b>Chuyến</div><div><b>3.9★</b>Đánh giá</div></div>
          <div class="actions">
            <button class="btn btn-green btn-sm" onclick="reactivateDriver(this)">Mở khoá</button>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function switchATab(tab){
    ['a-pending','a-active','a-locked'].forEach(t => document.getElementById('atab-'+t).classList.toggle('hidden', t !== tab));
    document.querySelectorAll('.tabs button').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
  }

  function updatePendingBadge(delta){
    document.querySelectorAll('.badge-count').forEach(el => {
      if (el.closest('.tabs') || el.closest('#userMenu')) {
        const n = Math.max(0, parseInt(el.textContent, 10) + delta);
        el.textContent = n;
        if (el.closest('#userMenu') && n === 0) el.remove();
      }
    });
  }

  function viewDocs(btn){
    const id = btn.closest('.admin-row').dataset.driverId;
    // TODO BACKEND: mở modal/trang xem CCCD, GPLX, đăng kiểm, bảo hiểm -> GET /api/admin/drivers/:id/documents
    alert('Xem giấy tờ của tài xế ' + id + ' (demo — cần nối API).');
  }

  function approveDriver(btn){
    const row = btn.closest('.admin-row');
    const id = row.dataset.driverId;
    // TODO BACKEND: await fetch(`/api/admin/drivers/${id}/approve`, {method:'POST'});
    row.style.opacity = '.4';
    row.querySelector('.actions').innerHTML = '<span class="doc-status approved">Đã duyệt</span>';
    updatePendingBadge(-1);
  }

  function rejectDriver(btn){
    const row = btn.closest('.admin-row');
    const id = row.dataset.driverId;
    if(!confirm('Từ chối hồ sơ tài xế này?')) return;
    // TODO BACKEND: await fetch(`/api/admin/drivers/${id}/reject`, {method:'POST'});
    row.style.opacity = '.4';
    row.querySelector('.actions').innerHTML = '<span class="doc-status rejected">Đã từ chối</span>';
    updatePendingBadge(-1);
  }

  function suspendDriver(btn){
    const row = btn.closest('.admin-row');
    const id = row.dataset.driverId;
    if(!confirm('Khoá tài khoản tài xế này? Tài xế sẽ không thể nhận chuyến mới.')) return;
    // TODO BACKEND: await fetch(`/api/admin/drivers/${id}/suspend`, {method:'POST'});
    alert('Đã khoá tài khoản tài xế ' + id + ' (demo — cần nối API).');
  }

  function reactivateDriver(btn){
    const row = btn.closest('.admin-row');
    const id = row.dataset.driverId;
    // TODO BACKEND: await fetch(`/api/admin/drivers/${id}/reactivate`, {method:'POST'});
    alert('Đã mở khoá tài khoản tài xế ' + id + ' (demo — cần nối API).');
  }
</script>
</body>
</html>
