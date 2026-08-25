<?php
// Lấy mã chuyến từ URL (?trip=TRIP-001), dùng để gửi kèm khi tạo thanh toán
$trip_id = $_GET['trip'] ?? 'TRIP-001';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Hành khách — Đặt chỗ &amp; Thanh toán</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>HÀNH KHÁCH</small></div></div>
    <nav class="side-nav">
      <a href="khach-index.php" class="active"><span class="ic">🔍</span> Tìm chuyến</a>
      <a href="khach-chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi <span class="badge-count">1</span></a>
      <a href="khach-ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; Thanh toán</a>
    </nav>
    <div class="sidebar-foot"><div class="av">L</div><div><b>Nguyễn Thị Lan</b><span>091 888 2233</span><span class="role-pill">Đã xác thực</span></div></div>
  </aside>

  <main class="main">

    <!-- ==============================================================
         TODO BACKEND:
         - GET  /api/passenger/trips/:id            -> chi tiết chuyến (route, tài xế, giá/ghế, số ghế trống)
         - POST /api/passenger/bookings              -> tạo booking (trip_id, seats, payment_method)
         - POST /api/payments/checkout                -> nếu payment_method = online: tạo phiên thanh toán
             qua cổng (VNPay/Momo/ZaloPay...) và redirect người dùng đến URL cổng trả về
         - Webhook cổng thanh toán -> POST /api/payments/webhook -> xác nhận thanh toán thành công
             -> cập nhật booking.payment_status = 'paid' -> KÍCH HOẠT tự động chia hoa hồng (xem admin-giao-dich.php)
         - Nếu payment_method = cash: booking.payment_status = 'pending_cash' (thu khi lên xe, tài xế xác nhận)
         ============================================================== -->

    <div class="view" id="view-checkout">
      <input type="hidden" id="trip-id-input" value="<?= htmlspecialchars($trip_id) ?>">
      <div class="topbar"><div><h1>Xác nhận đặt chỗ</h1><p>Kiểm tra thông tin chuyến và chọn phương thức thanh toán.</p></div></div>

      <div class="post-layout">
        <div>
          <div class="card">
            <div class="list-title">Thông tin chuyến</div>
            <div class="route-strip" style="margin-bottom:14px">
              <div class="pt"><div class="city">Hà Nội</div><div class="sub">06:30 · Bến xe Mỹ Đình</div></div>
              <div class="route-line"><div class="node start"></div><span class="van">🚐</span><div class="node end"></div></div>
              <div class="pt end"><div class="city">Ninh Bình</div><div class="sub">08:20 · Trung tâm TP</div></div>
            </div>
            <div class="admin-row" style="margin-bottom:0">
              <div class="av">H</div>
              <div class="meta"><b>Trần Văn Hùng</b><div class="sub">★ 4.9 · Kia Carnival · 30F-689.21</div></div>
            </div>
          </div>

          <div class="card">
            <div class="list-title">Số ghế</div>
            <div class="qty-stepper">
              <button type="button" onclick="changeSeats(-1)">−</button>
              <span id="seat-count">1</span>
              <button type="button" onclick="changeSeats(1)">+</button>
              <span style="margin-left:12px;font-size:12.5px;color:var(--ink-faint)">Còn 3 ghế trống</span>
            </div>
          </div>

          <div class="card">
            <div class="list-title">Phương thức thanh toán</div>

            <div class="pay-option selected" data-method="online" onclick="selectPayment('online')">
              <div class="ic">💳</div>
              <div class="meta"><b>Thanh toán trực tuyến</b><span>Thẻ ATM/Visa/Mastercard, VNPay, Momo, ZaloPay</span></div>
              <div class="radio"></div>
            </div>
            <div id="online-fields" class="form-grid" style="margin:4px 0 14px;padding:0 4px">
              <div class="field full"><label>Chọn cổng thanh toán</label>
                <div class="input">🏦<select id="gateway-select">
                  <option value="vnpay">VNPay</option>
                  <option value="momo">Momo</option>
                  <option value="zalopay">ZaloPay</option>
                  <option value="card">Thẻ ATM/Visa/Mastercard</option>
                </select></div>
              </div>
            </div>

            <div class="pay-option" data-method="cash" onclick="selectPayment('cash')">
              <div class="ic">💵</div>
              <div class="meta"><b>Thanh toán tiền mặt</b><span>Trả trực tiếp cho tài xế khi lên xe</span></div>
              <div class="radio"></div>
            </div>
          </div>
        </div>

        <div class="preview-sticky">
          <div class="card">
            <div class="preview-label">Tóm tắt thanh toán</div>
            <div class="summary-box">
              <div class="summary-row"><span>Giá vé / ghế</span><span class="mono">85.000đ</span></div>
              <div class="summary-row"><span>Số ghế</span><span class="mono">× <span id="summary-seats">1</span></span></div>
              <div class="summary-row"><span>Phí dịch vụ</span><span class="mono">0đ</span></div>
              <div class="summary-row total"><span>Tổng cộng</span><span class="mono" id="summary-total">85.000đ</span></div>
            </div>
            <button class="btn btn-primary btn-block" style="margin-top:16px;padding:12px" onclick="confirmBooking()" id="btn-confirm">Xác nhận đặt chỗ &amp; Thanh toán</button>
            <p style="font-size:11.5px;color:var(--ink-faint);text-align:center;margin-top:10px">Bạn có thể huỷ chuyến miễn phí trước 3 giờ khởi hành.</p>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  const PRICE_PER_SEAT = 85000;
  let seats = 1;
  let method = 'online';

  function fmt(n){ return n.toLocaleString('vi-VN') + 'đ'; }

  function changeSeats(delta){
    seats = Math.max(1, Math.min(3, seats + delta));
    document.getElementById('seat-count').textContent = seats;
    document.getElementById('summary-seats').textContent = seats;
    document.getElementById('summary-total').textContent = fmt(seats * PRICE_PER_SEAT);
  }

  function selectPayment(m){
    method = m;
    document.querySelectorAll('.pay-option').forEach(el => el.classList.toggle('selected', el.dataset.method === m));
    document.getElementById('online-fields').classList.toggle('hidden', m !== 'online');
  }

  function confirmBooking(){
    const btn = document.getElementById('btn-confirm');
    const total = seats * PRICE_PER_SEAT;
    const tripId = document.getElementById('trip-id-input').value;

    if (method === 'online'){
      const gateway = document.getElementById('gateway-select').value;
      btn.disabled = true;
      btn.textContent = 'Đang chuyển đến cổng thanh toán…';

      // Tạo booking + chuyển hướng SANG TRANG THANH TOÁN VNPAY thật:
      // submit một form POST thật (không dùng fetch) để trình duyệt điều hướng
      // theo header Location mà khach-tao-thanh-toan.php trả về.
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'khachthanhtoan.php';

      const fields = { trip_id: tripId, seats: seats, gateway: gateway, amount: total };
      Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
      });

      document.body.appendChild(form);
      form.submit();
    } else {
      // TODO BACKEND: await fetch('/api/passenger/bookings', {method:'POST', headers:{'Content-Type':'application/json'},
      //   body: JSON.stringify({trip_id: tripId, seats, payment_method:'cash'})});
      alert('Đã đặt chỗ thành công! Vui lòng thanh toán ' + fmt(total) + ' tiền mặt cho tài xế khi lên xe.\n(demo — cần nối API).');
      window.location.href = 'khach-chuyen-cua-toi.php';
    }
  }
</script>
</body>
</html>