<?php
$activePage = 'thanh-toan';
$pendingDriverCount = 2;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Quản trị — Thanh toán</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <?php include 'partials/sidebar.php'; ?>

  <main class="main">

    <!-- ==============================================================
         TODO BACKEND — LUỒNG CHIA HOA HỒNG TỰ ĐỘNG:
         - GET  /api/admin/settings/commission        -> tỉ lệ hoa hồng hiện tại + trạng thái tự động chuyển tiền
         - PUT  /api/admin/settings/commission         -> nút "Lưu cài đặt" (rate %, auto_payout: true/false)
         - Khi khách thanh toán TRỰC TUYẾN thành công (webhook cổng thanh toán POST /api/payments/webhook):
             1) backend tính: hoa_hong = tong_tien * commission_rate
             2) tai_xe_nhan = tong_tien - hoa_hong
             3) nếu auto_payout = true: cộng tai_xe_nhan vào bảng driver_wallet.balance NGAY LẬP TỨC (trong 1 transaction DB)
             4) ghi 1 dòng vào bảng transactions với trạng thái "Đã cộng vào ví tài xế"
           -> Đây chính là số dư "Số dư khả dụng" tài xế thấy ở trang thu-nhap.php
         - Với thanh toán TIỀN MẶT: không có dòng tiền qua nền tảng nên không tự cộng ví;
             hệ thống chỉ ghi nhận công nợ hoa hồng (tài xế nợ nền tảng) và trừ dần vào lần rút tiền tiếp theo,
             hoặc admin đối soát thủ công định kỳ.
         - GET  /api/admin/transactions?method=&status=       -> bảng "Giao dịch thanh toán"
         - GET  /api/admin/withdrawals?status=pending           -> bảng "Yêu cầu rút tiền chờ duyệt"
         - POST /api/admin/withdrawals/:id/approve               -> nút "Duyệt"
         - POST /api/admin/withdrawals/:id/reject                 -> nút "Từ chối"
         ============================================================== -->

    <div class="view" id="view-admin-transactions">
      <div class="topbar"><div><h1>Thanh toán</h1><p>Theo dõi doanh thu, giao dịch, hoa hồng và duyệt rút tiền tài xế.</p></div></div>

      <div class="card">
        <div class="list-title">Cài đặt hoa hồng nền tảng</div>
        <div class="commission-card">
          <div class="set">
            <span style="font-size:13.5px;color:var(--ink-soft)">Tỉ lệ hoa hồng áp dụng cho mọi chuyến</span>
            <div class="input"><input type="number" id="commission-rate" value="10" min="0" max="50" step="1">%</div>
          </div>
          <button class="btn btn-primary" onclick="saveCommission()">Lưu cài đặt</button>
        </div>
        <div class="settings-row">
          <div class="t"><b>Tự động cộng tiền vào ví tài xế</b><span>Khi khách thanh toán trực tuyến thành công, hệ thống tự động trích hoa hồng và chuyển phần còn lại vào số dư khả dụng của tài xế ngay lập tức.</span></div>
          <label class="switch"><input type="checkbox" id="auto-payout-toggle" checked onchange="toggleAutoPayout()"><span class="slider"></span></label>
        </div>
        <div class="settings-row">
          <div class="t"><b>Đối soát hoa hồng cho thanh toán tiền mặt</b><span>Hoa hồng của các chuyến trả tiền mặt sẽ được tự động trừ vào lần rút tiền tiếp theo của tài xế.</span></div>
          <label class="switch"><input type="checkbox" checked><span class="slider"></span></label>
        </div>
      </div>

      <div class="stat-row" style="margin-top:16px">
        <div class="stat-card"><div class="lbl">Tổng doanh thu (GMV) tháng 08</div><div class="val mono">86.400.000đ</div></div>
        <div class="stat-card"><div class="lbl">Hoa hồng thu được</div><div class="val mono" id="stat-commission-total">8.640.000đ</div></div>
        <div class="stat-card"><div class="lbl">Đã tự động chuyển cho tài xế</div><div class="val mono">72.980.000đ</div></div>
        <div class="stat-card"><div class="lbl">Tiền mặt chưa đối soát</div><div class="val mono">4.780.000đ</div></div>
      </div>

      <div class="card">
        <div class="list-title">Giao dịch thanh toán gần đây</div>
        <table>
          <thead><tr><th>Ngày</th><th>Chuyến</th><th>Khách</th><th>Tài xế</th><th>P.thức</th><th>Tổng tiền</th><th>Hoa hồng</th><th>Tài xế nhận</th><th>Trạng thái</th></tr></thead>
          <tbody id="transactions-table">
            <tr>
              <td class="mono">19/08 15:41</td><td class="mono">TRIP-118</td><td>Nguyễn Thị Lan</td><td>Trần Văn Hùng</td>
              <td><span class="method-tag online">Online</span></td>
              <td class="mono">120.000đ</td><td class="mono">−12.000đ</td><td class="mono" style="font-weight:600">108.000đ</td>
              <td><span class="status-pill approved">Đã cộng vào ví tài xế</span></td>
            </tr>
            <tr>
              <td class="mono">19/08 14:02</td><td class="mono">TRIP-002</td><td>Lê Quốc Anh</td><td>Đỗ Thị Hoa</td>
              <td><span class="method-tag cash">Tiền mặt</span></td>
              <td class="mono">130.000đ</td><td class="mono">−13.000đ</td><td class="mono" style="font-weight:600">117.000đ</td>
              <td><span class="status-pill pending">Chờ đối soát tiền mặt</span></td>
            </tr>
            <tr>
              <td class="mono">18/08 09:12</td><td class="mono">TRIP-097</td><td>Trịnh Thu Hà</td><td>Phạm Đức Long</td>
              <td><span class="method-tag online">Online</span></td>
              <td class="mono">390.000đ</td><td class="mono">−39.000đ</td><td class="mono" style="font-weight:600">351.000đ</td>
              <td><span class="status-pill approved">Đã cộng vào ví tài xế</span></td>
            </tr>
            <tr>
              <td class="mono">17/08 20:03</td><td class="mono">TRIP-081</td><td>Vũ Minh Đức</td><td>Trần Văn Hùng</td>
              <td><span class="method-tag online">Online</span></td>
              <td class="mono">100.000đ</td><td class="mono">−10.000đ</td><td class="mono" style="font-weight:600">—</td>
              <td><span class="status-pill cancelled">Đã hoàn tiền khách</span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="card">
        <div class="list-title">Yêu cầu rút tiền của tài xế — chờ duyệt</div>
        <div id="withdrawals-list">
          <div class="admin-row" data-withdraw-id="WD-401">
            <div class="av">H</div>
            <div class="meta"><b>Trần Văn Hùng</b><div class="sub">Vietcombank ****4821 · Yêu cầu lúc 15/08/2026</div></div>
            <div class="stats"><div><b class="mono">1.000.000đ</b>Số tiền</div></div>
            <div class="actions">
              <button class="btn btn-green btn-sm" onclick="approveWithdrawal(this)">Duyệt</button>
              <button class="btn btn-coral btn-sm" onclick="rejectWithdrawal(this)">Từ chối</button>
            </div>
          </div>
          <div class="admin-row" data-withdraw-id="WD-402" style="margin-bottom:0">
            <div class="av">D</div>
            <div class="meta"><b>Đỗ Thị Hoa</b><div class="sub">MB Bank ****7710 · Yêu cầu lúc 18/08/2026</div></div>
            <div class="stats"><div><b class="mono">650.000đ</b>Số tiền</div></div>
            <div class="actions">
              <button class="btn btn-green btn-sm" onclick="approveWithdrawal(this)">Duyệt</button>
              <button class="btn btn-coral btn-sm" onclick="rejectWithdrawal(this)">Từ chối</button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  function saveCommission(){
    const rate = Number(document.getElementById('commission-rate').value);
    if (rate < 0 || rate > 50){ alert('Tỉ lệ hoa hồng phải trong khoảng 0–50%.'); return; }
    // TODO BACKEND: await fetch('/api/admin/settings/commission', {method:'PUT', headers:{'Content-Type':'application/json'},
    //   body: JSON.stringify({rate, auto_payout: document.getElementById('auto-payout-toggle').checked})});
    document.getElementById('stat-commission-total').textContent = (86400000 * rate / 100).toLocaleString('vi-VN') + 'đ';
    alert('Đã lưu tỉ lệ hoa hồng: ' + rate + '%. Áp dụng cho mọi giao dịch mới kể từ bây giờ.\n(demo — cần nối API).');
  }

  function toggleAutoPayout(){
    const on = document.getElementById('auto-payout-toggle').checked;
    // TODO BACKEND: await fetch('/api/admin/settings/commission', {method:'PUT', body: JSON.stringify({auto_payout:on})});
    console.log('Tự động cộng tiền vào ví tài xế:', on ? 'BẬT' : 'TẮT');
  }

  function approveWithdrawal(btn){
    const row = btn.closest('.admin-row');
    const id = row.dataset.withdrawId;
    // TODO BACKEND: await fetch(`/api/admin/withdrawals/${id}/approve`, {method:'POST'});
    row.style.opacity = '.4';
    row.querySelector('.actions').innerHTML = '<span class="doc-status approved">Đã duyệt</span>';
  }

  function rejectWithdrawal(btn){
    const row = btn.closest('.admin-row');
    const id = row.dataset.withdrawId;
    if(!confirm('Từ chối yêu cầu rút tiền này?')) return;
    // TODO BACKEND: await fetch(`/api/admin/withdrawals/${id}/reject`, {method:'POST'});
    row.style.opacity = '.4';
    row.querySelector('.actions').innerHTML = '<span class="doc-status rejected">Đã từ chối</span>';
  }
</script>
</body>
</html>
