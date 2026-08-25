<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Hành khách — Hồ sơ &amp; Thanh toán</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>HÀNH KHÁCH</small></div></div>
    <nav class="side-nav">
      <a href="khach-index.php"><span class="ic">🔍</span> Tìm chuyến</a>
      <a href="khach-chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi <span class="badge-count">1</span></a>
      <a href="khach-ho-so.php" class="active"><span class="ic">👤</span> Hồ sơ &amp; Thanh toán</a>
    </nav>
    <div class="sidebar-foot"><div class="av">L</div><div><b>Nguyễn Thị Lan</b><span>091 888 2233</span><span class="role-pill">Đã xác thực</span></div></div>
  </aside>

  <main class="main">

    <!-- ==============================================================
         TODO BACKEND:
         - GET  /api/passenger/profile               -> thông tin cá nhân
         - PUT  /api/passenger/profile                -> nút "Lưu thay đổi"
         - GET  /api/passenger/payment-methods        -> danh sách thẻ/ví đã lưu
         - POST /api/passenger/payment-methods         -> nút "+ Thêm phương thức thanh toán"
         - DELETE /api/passenger/payment-methods/:id   -> nút "Xoá"
         - GET  /api/passenger/transactions             -> bảng "Lịch sử giao dịch"
         ============================================================== -->

    <div class="view" id="view-profile">
      <button type="button" class="btn-back" onclick="history.back()"><span class="arrow">←</span> Quay lại</button>
      <div class="topbar"><div><h1>Hồ sơ &amp; Thanh toán</h1><p>Thông tin cá nhân, phương thức thanh toán đã lưu và lịch sử giao dịch.</p></div></div>

      <div class="profile-layout">
        <div class="card" style="text-align:center">
          <div class="av-xl">L</div>
          <h3 style="font-size:16px">Nguyễn Thị Lan</h3>
          <p style="font-size:12px;color:var(--ink-faint);margin-top:2px">Hành khách từ 2024</p>
          <div style="margin-top:10px"><span class="doc-status approved">Đã xác thực SĐT</span></div>
        </div>

        <div>
          <div class="card">
            <div class="list-title">Thông tin cá nhân</div>
            <form id="profile-form" class="form-grid" onsubmit="return saveProfile(event)">
              <div class="field"><label>Họ và tên</label><div class="input"><input name="fullName" value="Nguyễn Thị Lan"></div></div>
              <div class="field"><label>Số điện thoại</label><div class="input"><input name="phone" value="091 888 2233"></div></div>
              <div class="field full"><label>Email</label><div class="input"><input type="email" name="email" value="lan.nguyen@email.com"></div></div>
              <button type="submit" class="btn btn-primary full" style="margin-top:16px;padding:11px 20px;justify-self:start">Lưu thay đổi</button>
            </form>
          </div>

          <div class="card">
            <div class="list-title">Phương thức thanh toán đã lưu</div>
            <div id="payment-methods-list">
              <div class="doc-row" data-method-id="PM-1"><div class="l"><span class="ic">💳</span> Vietcombank ****4821</div><button class="btn btn-coral btn-sm" onclick="removePaymentMethod(this)">Xoá</button></div>
              <div class="doc-row" data-method-id="PM-2"><div class="l"><span class="ic">📱</span> Ví Momo · 091 888 2233</div><button class="btn btn-coral btn-sm" onclick="removePaymentMethod(this)">Xoá</button></div>
            </div>
            <button class="btn btn-outline" style="margin-top:6px" onclick="addPaymentMethod()">+ Thêm phương thức thanh toán</button>
          </div>

          <div class="card">
            <div class="list-title">Lịch sử giao dịch</div>
            <table>
              <thead><tr><th>Ngày</th><th>Tuyến</th><th>Phương thức</th><th>Số tiền</th><th>Trạng thái</th></tr></thead>
              <tbody id="transaction-history">
                <tr><td class="mono">09/08</td><td>Hà Nội → Hải Phòng</td><td><span class="method-tag online">Online</span></td><td class="mono">120.000đ</td><td><span class="status-pill approved">Thành công</span></td></tr>
                <tr><td class="mono">01/08</td><td>Hà Nội → Ninh Bình</td><td><span class="method-tag online">Online</span></td><td class="mono">100.000đ</td><td><span class="status-pill cancelled">Đã hoàn tiền</span></td></tr>
                <tr><td class="mono">22/07</td><td>Hà Nội → Hải Phòng</td><td><span class="method-tag cash">Tiền mặt</span></td><td class="mono">130.000đ</td><td><span class="status-pill approved">Đã thanh toán tài xế</span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function saveProfile(e){
    e.preventDefault();
    const form = document.getElementById('profile-form');
    const data = Object.fromEntries(new FormData(form).entries());
    // TODO BACKEND: await fetch('/api/passenger/profile', {method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    alert('Đã lưu thay đổi (demo — cần nối API).');
    return false;
  }

  function addPaymentMethod(){
    // TODO BACKEND: mở form/modal thêm thẻ hoặc liên kết ví điện tử, sau đó POST /api/passenger/payment-methods
    alert('Mở form thêm phương thức thanh toán mới (demo — cần nối API).');
  }

  function removePaymentMethod(btn){
    const row = btn.closest('.doc-row');
    const id = row.dataset.methodId;
    if(!confirm('Xoá phương thức thanh toán này?')) return;
    // TODO BACKEND: await fetch(`/api/passenger/payment-methods/${id}`, {method:'DELETE'});
    row.remove();
  }
</script>
</body>
</html>
