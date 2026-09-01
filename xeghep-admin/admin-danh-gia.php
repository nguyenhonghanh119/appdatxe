<?php
$activePage = 'danh-gia';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Đánh giá &amp; khiếu nại</title>
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
         - GET  /api/admin/reviews?rating=            -> tab "Đánh giá"
         - GET  /api/admin/complaints?status=          -> tab "Khiếu nại"
         - POST /api/admin/complaints/:id/resolve       -> nút "Đánh dấu đã xử lý"
         ============================================================== -->

    <div class="view">
      <div class="topbar"><div><h1>Đánh giá &amp; khiếu nại</h1><p>Theo dõi đánh giá của khách và xử lý khiếu nại phát sinh.</p></div></div>

      <div class="tabs">
        <button class="active" data-subtab="reviews" onclick="switchSubTab('reviews')">Đánh giá</button>
        <button data-subtab="complaints" onclick="switchSubTab('complaints')">Khiếu nại <span class="badge-count" style="background:var(--coral);color:#fff;font-size:10.5px;font-weight:700;padding:1px 7px;border-radius:20px;margin-left:4px">1</span></button>
      </div>

      <div id="subtab-reviews" class="subtab-panel">
        <div class="card">
          <table>
            <thead><tr><th>Khách</th><th>Tài xế</th><th>Chuyến</th><th>Điểm</th><th>Nhận xét</th><th>Ngày</th></tr></thead>
            <tbody>
              <tr><td>Nguyễn Thị Lan</td><td>Trần Văn Hùng</td><td class="mono">TRIP-097</td><td>⭐⭐⭐⭐⭐</td><td>Tài xế thân thiện, xe sạch sẽ.</td><td class="mono">18/08</td></tr>
              <tr><td>Lê Quốc Anh</td><td>Đỗ Thị Hoa</td><td class="mono">TRIP-002</td><td>⭐⭐⭐⭐</td><td>Đón trễ 10 phút.</td><td class="mono">19/08</td></tr>
              <tr><td>Trịnh Thu Hà</td><td>Phạm Đức Long</td><td class="mono">TRIP-081</td><td>⭐⭐⭐</td><td>Xe hơi cũ, tài xế lái ổn.</td><td class="mono">17/08</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <div id="subtab-complaints" class="subtab-panel hidden">
        <div class="admin-row" data-complaint-id="CP-021">
          <div class="av">V</div>
          <div class="meta"><b>Vũ Minh Đức khiếu nại tài xế Trần Văn Hùng</b><div class="sub">Chuyến TRIP-081 · Nội dung: "Tài xế huỷ chuyến sát giờ khởi hành"</div></div>
          <div class="actions">
            <button class="btn btn-outline btn-sm">Xem chi tiết</button>
            <button class="btn btn-green btn-sm" onclick="resolveComplaint(this)">Đánh dấu đã xử lý</button>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>
<script src="assets/common.js"></script>
<script>
  function switchSubTab(tab){
    document.getElementById('subtab-reviews').classList.toggle('hidden', tab !== 'reviews');
    document.getElementById('subtab-complaints').classList.toggle('hidden', tab !== 'complaints');
    document.querySelectorAll('.tabs button').forEach(b => b.classList.toggle('active', b.dataset.subtab === tab));
  }
  function resolveComplaint(btn){
    const row = btn.closest('.admin-row');
    // TODO BACKEND: await fetch(`/api/admin/complaints/${row.dataset.complaintId}/resolve`, {method:'POST'});
    row.style.opacity = '.4';
    row.querySelector('.actions').innerHTML = '<span class="doc-status approved">Đã xử lý</span>';
  }
</script>
</body>
</html>
