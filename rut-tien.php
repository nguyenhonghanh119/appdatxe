<?php
// Cấu hình kết nối CSDL
$host = '127.0.0.1';
$db   = 'xeghep_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// Giả lập ID tài xế đang đăng nhập (Trần Văn Hùng có user_id = 2)
$driver_id = 2;

$error_message = '';
$success_message = '';
$password_error = false; // Bật cờ này để tự mở lại popup nhập mật khẩu khi có lỗi

// Số tiền rút tối thiểu mỗi lần & thời gian xử lý tối đa (giờ)
define('MIN_WITHDRAW_AMOUNT', 50000);
define('MAX_PROCESSING_HOURS', 12);

// Lưu lại giá trị đã nhập để hiển thị lại nếu submit lỗi
$posted_amount = isset($_POST['amount']) ? (int) $_POST['amount'] : 0;
$posted_bank_choice = $_POST['bank_choice'] ?? '';
$posted_new_bank_name = trim($_POST['new_bank_name'] ?? '');
$posted_new_bank_number = trim($_POST['new_bank_number'] ?? '');
$posted_new_bank_holder = trim($_POST['new_bank_holder'] ?? '');

// ============ XỬ LÝ YÊU CẦU RÚT TIỀN (POST) ============
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $posted_amount;
    $bank_choice = $posted_bank_choice;
    $login_password = $_POST['login_password'] ?? '';

    if ($bank_choice === 'new') {
        $bank_info = ($posted_new_bank_name !== '' && $posted_new_bank_number !== '' && $posted_new_bank_holder !== '')
            ? "$posted_new_bank_name - $posted_new_bank_number - $posted_new_bank_holder"
            : '';
    } else {
        $bank_info = trim($bank_choice);
    }

    // Lấy mật khẩu (đã hash) + số dư ví hiện tại để đối chiếu
    $stmt = $pdo->prepare("SELECT u.password_hash, dp.wallet_balance 
                           FROM users u 
                           JOIN driver_profiles dp ON u.user_id = dp.driver_id 
                           WHERE u.user_id = ?");
    $stmt->execute([$driver_id]);
    $authRow = $stmt->fetch();

    // Số tiền đang chờ Admin duyệt (chưa trừ ví) — không cho rút vượt quá phần còn lại
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM withdrawals WHERE driver_id = ? AND status = 'pending'");
    $stmt->execute([$driver_id]);
    $pendingSum = (float) $stmt->fetchColumn();

    $walletBalance = (float) ($authRow['wallet_balance'] ?? 0);
    $availableToWithdraw = max(0, $walletBalance - $pendingSum);

    if (empty($login_password) || !$authRow || !password_verify($login_password, $authRow['password_hash'])) {
        $error_message = 'Mật khẩu đăng nhập không chính xác. Vui lòng thử lại.';
        $password_error = true;
    } elseif ($amount < MIN_WITHDRAW_AMOUNT) {
        $error_message = 'Số tiền rút tối thiểu là ' . number_format(MIN_WITHDRAW_AMOUNT, 0, ',', '.') . 'đ.';
    } elseif ($bank_info === '') {
        $error_message = 'Vui lòng chọn hoặc nhập đầy đủ thông tin tài khoản nhận tiền.';
    } elseif ($amount > $availableToWithdraw) {
        $error_message = 'Số tiền vượt quá số dư khả dụng (đã trừ các yêu cầu đang chờ duyệt).';
    } else {
        try {
            // CHỈ tạo yêu cầu ở trạng thái 'pending', gửi lên cho Admin duyệt.
            // KHÔNG trừ wallet_balance tại đây — số dư của tài xế chỉ được trừ
            // sau khi Admin xác nhận tiền đã chuyển thành công vào tài khoản
            // ngân hàng (ở trang quản trị, khi cập nhật status -> 'approved').
            $stmt = $pdo->prepare("INSERT INTO withdrawals (driver_id, amount, bank_info, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            $stmt->execute([$driver_id, $amount, $bank_info]);

            $success_message = 'Yêu cầu rút ' . number_format($amount, 0, ',', '.') . 'đ đã được gửi đến Admin để duyệt. '
                . 'Yêu cầu sẽ được xử lý trong tối đa ' . MAX_PROCESSING_HOURS . ' giờ. '
                . 'Số dư của bạn chỉ bị trừ sau khi tiền đã chuyển thành công vào tài khoản ngân hàng.';

            // Reset các giá trị đã nhập vì đã gửi thành công
            $posted_amount = 0;
            $posted_bank_choice = '';
            $posted_new_bank_name = $posted_new_bank_number = $posted_new_bank_holder = '';
        } catch (\Exception $e) {
            $error_message = 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage();
        }
    }
}

// ============ LẤY DỮ LIỆU HIỂN THỊ ============
$stmt = $pdo->prepare("SELECT u.full_name, u.avatar, dp.rating, dp.wallet_balance 
                       FROM users u 
                       JOIN driver_profiles dp ON u.user_id = dp.driver_id 
                       WHERE u.user_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b 
                       JOIN trips t ON b.trip_id = t.trip_id 
                       WHERE t.driver_id = ? AND b.status = 'pending_approval'");
$stmt->execute([$driver_id]);
$pendingCount = $stmt->fetchColumn();

// Tổng số tiền đang chờ Admin duyệt (chưa trừ ví)
$stmt = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM withdrawals WHERE driver_id = ? AND status = 'pending'");
$stmt->execute([$driver_id]);
$pendingWithdrawSum = (float) $stmt->fetchColumn();
$availableToWithdrawDisplay = max(0, (float) $driver['wallet_balance'] - $pendingWithdrawSum);

// 5 yêu cầu rút tiền gần nhất để hiển thị nhanh trên trang
$stmt = $pdo->prepare("SELECT created_at, amount, bank_info, status FROM withdrawals WHERE driver_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$driver_id]);
$recentWithdrawals = $stmt->fetchAll();

function getWithdrawStatusHtml($status) {
    switch ($status) {
        case 'approved':
            return '<span class="status-pill approved" style="background:var(--green-dim);color:var(--green)">Thành công</span>';
        case 'pending':
            return '<span class="status-pill pending" style="background:#FFF3DF;color:#92650E">Chờ Admin duyệt</span>';
        case 'rejected':
            return '<span class="status-pill rejected" style="background:var(--coral-dim);color:var(--coral)">Từ chối</span>';
        default:
            return '<span class="status-pill">Không rõ</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Tài xế — Rút tiền</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  .withdraw-wrap{ max-width:520px; }
  .balance-banner{ display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg,#111827,#1f2937); color:#fff; border-radius:16px; padding:22px 24px; margin-bottom:20px; }
  .balance-banner .lbl{ font-size:12.5px; opacity:.75; margin-bottom:6px; }
  .balance-banner .val{ font-size:26px; font-weight:700; font-family:'IBM Plex Mono', monospace; }
  .balance-banner .pending-note{ font-size:12px; opacity:.8; margin-top:6px; }
  .quick-amounts{ display:flex; gap:8px; margin:10px 0 18px; flex-wrap:wrap; }
  .quick-amounts button{ padding:8px 14px; border-radius:20px; border:1px solid var(--border,#d1d5db); background:#fff; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; }
  .quick-amounts button:hover{ border-color:var(--primary,#2563eb); color:var(--primary,#2563eb); }
  #new-bank-fields{ display:none; margin-top:10px; padding:14px; background:var(--bg,#f5f6f8); border-radius:10px; }
  .back-link{ display:inline-flex; align-items:center; gap:6px; font-size:13.5px; color:var(--ink-soft,#6b7280); text-decoration:none; margin-bottom:16px; }
  .back-link:hover{ color:var(--primary,#2563eb); }
  .sla-note{ display:flex; gap:8px; align-items:flex-start; background:#EFF6FF; color:#1D4ED8; border-radius:10px; padding:10px 12px; font-size:12.5px; margin-top:10px; }
  .input-plain{ width:100%; padding:10px; border:1px solid var(--border,#d1d5db); border-radius:8px; box-sizing:border-box; font-family:inherit; }
</style>
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>TÀI XẾ</small></div></div>
    <nav class="side-nav">
      <a href="index.php"><span class="ic">🏠</span> Tổng quan</a>
      <a href="yeu-cau-dat-cho.php">
          <span class="ic">📥</span> Yêu cầu đặt chỗ 
          <?php if($pendingCount > 0): ?>
            <span class="badge-count"><?= $pendingCount ?></span>
          <?php endif; ?>
      </a>
      <a href="chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi</a>
      <a href="thu-nhap.php" class="active"><span class="ic">₫</span> Thu nhập</a>
      <a href="ho-so.php"><span class="ic">👤</span> Hồ sơ &amp; giấy tờ</a>
    </nav>
    <div class="sidebar-foot">
        <div class="av"><?= htmlspecialchars($driver['avatar'] ?? 'U') ?></div>
        <div><b><?= htmlspecialchars($driver['full_name']) ?></b><span>★ <?= number_format($driver['rating'], 1) ?> · Đã duyệt</span></div>
    </div>
  </aside>

  <main class="main">
    <div class="view withdraw-wrap">
      <a href="thu-nhap.php" class="back-link">← Quay lại Thu nhập</a>

      <div class="topbar">
        <div><h1>Rút tiền</h1><p>Chuyển số dư khả dụng của bạn về tài khoản ngân hàng.</p></div>
      </div>

      <?php if (!empty($error_message)): ?>
        <div class="alert alert-error" style="padding:12px;border-radius:10px;background:var(--coral-dim,#fef2f2);color:var(--coral,#b91c1c);margin-bottom:16px;font-size:13.5px"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" style="padding:12px;border-radius:10px;background:var(--green-dim,#f0fdf4);color:var(--green,#16a34a);margin-bottom:16px;font-size:13.5px"><?= htmlspecialchars($success_message) ?></div>
      <?php endif; ?>

      <div class="balance-banner">
        <div>
          <div class="lbl">Số dư khả dụng</div>
          <div class="val" id="balance-val"><?= number_format($availableToWithdrawDisplay, 0, ',', '.') ?>đ</div>
          <?php if ($pendingWithdrawSum > 0): ?>
            <div class="pending-note">Đang chờ Admin duyệt: <?= number_format($pendingWithdrawSum, 0, ',', '.') ?>đ</div>
          <?php endif; ?>
        </div>
        <div style="font-size:26px">💵</div>
      </div>

      <div class="card">
        <div class="list-title">Thông tin rút tiền</div>

        <form method="POST" action="rut-tien.php" id="withdraw-form" onsubmit="return false;">
          <div class="field" style="margin-bottom:6px">
            <label>Số tiền muốn rút</label>
            <div class="input">₫<input type="number" name="amount" id="withdraw-amount" min="<?= MIN_WITHDRAW_AMOUNT ?>" step="10000" placeholder="VD: 500000" value="<?= $posted_amount > 0 ? $posted_amount : '' ?>" required></div>
          </div>
          <div class="quick-amounts">
            <button type="button" onclick="setAmount(200000)">200.000đ</button>
            <button type="button" onclick="setAmount(500000)">500.000đ</button>
            <button type="button" onclick="setAmount(1000000)">1.000.000đ</button>
            <button type="button" onclick="setAmount(<?= (int) $availableToWithdrawDisplay ?>)">Rút tất cả</button>
          </div>

          <div class="field">
            <label>Tài khoản nhận tiền</label>
            <div class="input">🏦
              <select name="bank_choice" id="bank_choice" onchange="toggleNewBankFields()">
                <option value="Vietcombank ****4821" <?= $posted_bank_choice === 'Vietcombank ****4821' ? 'selected' : '' ?>>Vietcombank ****4821</option>
                <option value="MB Bank ****7710" <?= $posted_bank_choice === 'MB Bank ****7710' ? 'selected' : '' ?>>MB Bank ****7710</option>
                <option value="new" <?= $posted_bank_choice === 'new' ? 'selected' : '' ?>>+ Thêm tài khoản ngân hàng khác</option>
              </select>
            </div>
          </div>

          <div id="new-bank-fields">
            <div class="field" style="margin-bottom:12px">
              <label>Tên ngân hàng</label>
              <input type="text" name="new_bank_name" placeholder="VD: Techcombank" class="input-plain" value="<?= htmlspecialchars($posted_new_bank_name) ?>">
            </div>
            <div class="field" style="margin-bottom:12px">
              <label>Số tài khoản</label>
              <input type="text" name="new_bank_number" placeholder="VD: 0123456789" class="input-plain" value="<?= htmlspecialchars($posted_new_bank_number) ?>">
            </div>
            <div class="field">
              <label>Chủ tài khoản</label>
              <input type="text" name="new_bank_holder" placeholder="VD: NGUYEN VAN A" class="input-plain" value="<?= htmlspecialchars($posted_new_bank_holder) ?>">
            </div>
          </div>

          <input type="hidden" name="login_password" id="login_password_hidden">

          <button type="button" class="btn btn-primary btn-block" style="margin-top:18px;padding:12px;width:100%" onclick="openPasswordConfirm()">Xác nhận rút tiền</button>

          <div class="sla-note">
            ⏱️ <span>Yêu cầu sẽ được Admin xét duyệt trong tối đa <b><?= MAX_PROCESSING_HOURS ?> giờ</b>. Số dư ví chỉ bị trừ sau khi tiền đã chuyển thành công vào tài khoản ngân hàng của bạn.</span>
          </div>
        </form>
      </div>

      <div class="card">
        <div class="list-title">Yêu cầu rút tiền gần đây</div>
        <table>
          <thead><tr><th>Ngày yêu cầu</th><th>Số tiền</th><th>Ngân hàng nhận</th><th>Trạng thái</th></tr></thead>
          <tbody>
            <?php foreach($recentWithdrawals as $wd): ?>
            <tr>
                <td class="mono"><?= date('d/m', strtotime($wd['created_at'])) ?></td>
                <td class="mono"><?= number_format($wd['amount'], 0, ',', '.') ?>đ</td>
                <td><?= htmlspecialchars($wd['bank_info']) ?></td>
                <td><?= getWithdrawStatusHtml($wd['status']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($recentWithdrawals)): ?>
            <tr><td colspan="4" style="text-align:center;color:var(--ink-soft)">Chưa có yêu cầu rút tiền nào.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- MODAL: XÁC NHẬN MẬT KHẨU ĐĂNG NHẬP -->
<div class="modal-overlay hidden" id="modal-confirm-password">
  <div class="modal" style="max-width:380px">
    <div class="modal-head"><h3 style="font-size:18px">Xác nhận mật khẩu</h3><button type="button" onclick="closeModal('modal-confirm-password')">✕</button></div>
    <p class="sub">Vui lòng nhập mật khẩu đăng nhập tài khoản tài xế để xác nhận yêu cầu rút <b id="confirm-amount-display" class="mono"></b>.</p>
    <div class="field">
      <label>Mật khẩu đăng nhập</label>
      <input type="password" id="confirm-password-input" placeholder="Nhập mật khẩu tài khoản" class="input-plain">
    </div>
    <div id="confirm-password-error" style="display:none;padding:8px;border-radius:8px;background:var(--coral-dim,#fef2f2);color:var(--coral,#b91c1c);font-size:12.5px;margin-top:8px"></div>
    <button type="button" class="btn btn-primary btn-block" style="margin-top:16px;padding:12px;width:100%" onclick="confirmWithdrawSubmit()">Xác nhận &amp; Gửi yêu cầu</button>
  </div>
</div>

<script src="assets/common.js"></script>
<script>
  const availableBalance = <?= floatval($availableToWithdrawDisplay) ?>;

  function setAmount(value) {
      document.getElementById('withdraw-amount').value = value;
  }

  function toggleNewBankFields() {
      const isNew = document.getElementById('bank_choice').value === 'new';
      document.getElementById('new-bank-fields').style.display = isNew ? 'block' : 'none';
  }
  toggleNewBankFields();

  function validateWithdrawForm() {
      const amount = Number(document.getElementById('withdraw-amount').value || 0);
      if (amount <= 0) { alert('Vui lòng nhập số tiền hợp lệ.'); return false; }
      if (amount > availableBalance) { alert('Số tiền vượt quá số dư khả dụng.'); return false; }

      if (document.getElementById('bank_choice').value === 'new') {
          const name = document.querySelector('[name="new_bank_name"]').value.trim();
          const number = document.querySelector('[name="new_bank_number"]').value.trim();
          const holder = document.querySelector('[name="new_bank_holder"]').value.trim();
          if (!name || !number || !holder) {
              alert('Vui lòng nhập đầy đủ thông tin tài khoản ngân hàng mới.');
              return false;
          }
      }
      return true;
  }

  function openPasswordConfirm() {
      if (!validateWithdrawForm()) return;
      const amount = Number(document.getElementById('withdraw-amount').value || 0);
      document.getElementById('confirm-amount-display').textContent = amount.toLocaleString('vi-VN') + 'đ';
      document.getElementById('confirm-password-input').value = '';
      document.getElementById('confirm-password-error').style.display = 'none';
      openModal('modal-confirm-password');
      setTimeout(() => document.getElementById('confirm-password-input').focus(), 100);
  }

  function confirmWithdrawSubmit() {
      const pw = document.getElementById('confirm-password-input').value;
      if (!pw) {
          document.getElementById('confirm-password-error').textContent = 'Vui lòng nhập mật khẩu.';
          document.getElementById('confirm-password-error').style.display = 'block';
          return;
      }
      document.getElementById('login_password_hidden').value = pw;
      document.getElementById('withdraw-form').submit();
  }

  <?php if ($password_error): ?>
  // Server báo sai mật khẩu -> tự mở lại popup để nhập lại
  document.addEventListener('DOMContentLoaded', function () {
      const amount = Number(document.getElementById('withdraw-amount').value || 0);
      document.getElementById('confirm-amount-display').textContent = amount.toLocaleString('vi-VN') + 'đ';
      document.getElementById('confirm-password-error').textContent = <?= json_encode($error_message) ?>;
      document.getElementById('confirm-password-error').style.display = 'block';
      openModal('modal-confirm-password');
  });
  <?php endif; ?>
</script>
</body>
</html>