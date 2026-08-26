<?php
session_start();

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

$error_message = '';
$success_message = '';

// ============ XỬ LÝ AJAX: GỬI OTP / GỬI LẠI OTP / XÁC THỰC OTP ============
// Thời gian hiệu lực mã OTP: 1 phút 30 giây (90 giây) cho cả Người dùng và Tài xế
define('OTP_TTL_SECONDS', 90);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ajax_action = $_POST['ajax_action'];

    if ($ajax_action === 'send_otp' || $ajax_action === 'resend_otp') {
        $target = trim($_POST['otp_target'] ?? '');
        $role   = $_POST['reg_role'] ?? 'passenger';

        if ($target === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu số điện thoại/email nhận mã OTP.']);
            exit;
        }

        // Sinh mã OTP ngẫu nhiên gồm 6 chữ số
        $otp_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $_SESSION['otp_code']     = $otp_code;
        $_SESSION['otp_target']   = $target;
        $_SESSION['otp_role']     = $role;
        $_SESSION['otp_expires']  = time() + OTP_TTL_SECONDS;
        $_SESSION['otp_verified'] = false;

        // TODO: Tích hợp gửi SMS (vd: eSMS, Twilio) hoặc Email (vd: PHPMailer) thật tại đây.
        // Hiện chưa nối API gửi thật nên trả kèm mã trong response để có thể test giao diện.
        echo json_encode([
            'success'    => true,
            'expires_in' => OTP_TTL_SECONDS,
            'debug_code' => $otp_code, // Chỉ phục vụ TEST — xoá dòng này khi đã nối SMS/Email thật
        ]);
        exit;
    }

    if ($ajax_action === 'verify_otp') {
        $input_code = trim($_POST['otp_code'] ?? '');

        if (empty($_SESSION['otp_code']) || empty($_SESSION['otp_expires'])) {
            echo json_encode(['success' => false, 'message' => 'Bạn chưa yêu cầu gửi mã OTP.']);
            exit;
        }

        if (time() > $_SESSION['otp_expires']) {
            echo json_encode(['success' => false, 'expired' => true, 'message' => 'Mã OTP đã hết hạn. Vui lòng bấm "Gửi lại mã OTP".']);
            exit;
        }

        if (!hash_equals($_SESSION['otp_code'], $input_code)) {
            echo json_encode(['success' => false, 'message' => 'Mã xác nhận không chính xác.']);
            exit;
        }

        $_SESSION['otp_verified'] = true;
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_action'])) {
    $role = $_POST['reg_role'] ?? 'passenger';
    $password = $_POST['password'] ?? '';
    
    $phone = null;
    $email = null;
    $full_name = '';

    // Phân luồng dữ liệu Người dùng vs Tài xế
    if ($role === 'passenger') {
        $contact = trim($_POST['contact'] ?? '');
        $full_name = 'Khách hàng mới'; 
        
        if (strpos($contact, '@') !== false) {
            $email = $contact;
            $phone = 'EXT_' . time(); 
        } else {
            $phone = $contact;
        }
    } else {
        // Lấy dữ liệu đầy đủ của Tài xế
        $full_name = trim($_POST['full_name'] ?? '');
        $phone = trim($_POST['driver_phone'] ?? '');
        $email = trim($_POST['driver_email'] ?? null);
    }

    // Xác định lại "đích" nhận OTP tương ứng với vai trò, để so khớp với mã đã xác thực
    $otp_submitted_target = ($role === 'passenger')
        ? trim($_POST['contact'] ?? '')
        : (trim($_POST['driver_email'] ?? '') !== '' ? trim($_POST['driver_email']) : trim($_POST['driver_phone'] ?? ''));

    if (empty($_SESSION['otp_verified']) || ($_SESSION['otp_target'] ?? null) !== $otp_submitted_target) {
        $error_message = 'Vui lòng xác thực mã OTP hợp lệ (còn hiệu lực) trước khi hoàn tất đăng ký.';
    } elseif (empty($password)) {
        $error_message = 'Vui lòng nhập mật khẩu.';
    } else {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ? OR (email = ? AND email IS NOT NULL)");
        $stmt->execute([$phone, $email]);
        
        if ($stmt->fetch()) {
            $error_message = 'Số điện thoại hoặc Email này đã được đăng ký.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $status = ($role === 'driver') ? 'pending' : 'active';

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $phone, $email, $password_hash, $role, $status]);
                $user_id = $pdo->lastInsertId();

                if ($role === 'passenger') {
                    $stmt = $pdo->prepare("INSERT INTO passenger_profiles (passenger_id) VALUES (?)");
                    $stmt->execute([$user_id]);
                    $success_message = "Tạo tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.";
                } else {
                    $vehicle_type = trim($_POST['vehicle_type'] ?? '');
                    $license_plate = trim($_POST['license_plate'] ?? '');
                    
                    $stmt = $pdo->prepare("INSERT INTO driver_profiles (driver_id, vehicle_type, license_plate) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $vehicle_type, $license_plate]);

                    $upload_dir = 'uploads/docs/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                    $docs = [
                        'portrait' => 'Ảnh chân dung', 'cccd_front' => 'CCCD Mặt trước', 'cccd_back' => 'CCCD Mặt sau',
                        'gplx' => 'Giấy phép lái xe', 'lltp' => 'Lý lịch tư pháp', 'gksk' => 'Giấy khám sức khỏe',
                        'cavet' => 'Cà vẹt xe', 'baohiem' => 'Bảo hiểm xe', 'hinhxe' => 'Hình ảnh thực tế xe'
                    ];

                    $doc_stmt = $pdo->prepare("INSERT INTO driver_documents (driver_id, doc_type, doc_name, file_path, status) VALUES (?, ?, ?, ?, 'pending')");

                    foreach ($docs as $input_name => $doc_name) {
                        if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES[$input_name]['name'], PATHINFO_EXTENSION);
                            $filename = $input_name . '_' . $user_id . '_' . time() . '.' . $ext;
                            $destination = $upload_dir . $filename;
                            @move_uploaded_file($_FILES[$input_name]['tmp_name'], $destination);
                            $doc_stmt->execute([$user_id, $input_name, $doc_name, '/' . $destination]);
                        }
                    }

                    $success_message = "Đăng ký làm tài xế thành công! Hồ sơ đang chờ Admin phê duyệt.";
                }

                $pdo->commit();

                // Đăng ký thành công -> dọn dẹp dữ liệu OTP trong session
                unset($_SESSION['otp_code'], $_SESSION['otp_target'], $_SESSION['otp_expires'], $_SESSION['otp_verified'], $_SESSION['otp_role']);
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error_message = 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Đăng ký tài khoản</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{ --primary: #2563eb; --text: #111827; --muted: #6b7280; --border: #d1d5db; --bg: #f5f6f8; }
  body { margin:0; background:var(--bg); font-family:'Inter', sans-serif; color:var(--text); }
  .auth-shell{ min-height:100vh; display:flex; align-items:center; justify-content:center; padding:30px 20px; }
  
  .auth-card{ width:100%; max-width:400px; background:#fff; border:1px solid var(--border); border-radius:16px; padding:32px 28px; box-shadow:0 8px 24px rgba(0,0,0,0.06); transition: max-width 0.3s; }
  .auth-card.is-driver { max-width:650px; }
  
  .auth-brand{ display:flex; align-items:center; gap:10px; margin-bottom:20px; }
  .dot-route{ width:36px; height:36px; border-radius:10px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:18px; }
  .auth-brand div{ font-family:'Space Grotesk',sans-serif; font-weight:700; font-size:17px; }
  .auth-brand small{ display:block; font-weight:500; font-size:11px; letter-spacing:.04em; color:var(--muted); }
  
  .role-toggle { display:flex; background:var(--bg); border-radius:10px; padding:4px; margin-bottom:24px; }
  .role-toggle button { flex:1; padding:10px; border:none; background:transparent; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer; color:var(--muted); transition:0.2s; font-family:inherit; }
  .role-toggle button.active { background:#fff; color:var(--primary); box-shadow:0 2px 8px rgba(0,0,0,0.08); }

  /* Sửa lỗi CSS hiển thị sai form */
  .form-section { display: none; }
  #passenger-fields.active { display: block; }
  #driver-fields.active { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media(max-width: 600px){ #driver-fields.active { grid-template-columns: 1fr; } }
  
  .section-title { font-size:14px; text-transform:uppercase; font-weight:700; color:var(--muted); margin: 20px 0 10px; letter-spacing:0.05em; border-bottom:1px solid var(--border); padding-bottom:6px; grid-column:1/-1; }

  .field{ margin-bottom:16px; }
  .field label{ display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--text); }
  .field input{ width:100%; padding:11px 12px; border:1px solid var(--border); border-radius:10px; font-size:14px; font-family:inherit; outline:none; transition:border-color .15s; box-sizing:border-box; }
  .field input[type="file"] { padding:8px; font-size:12.5px; background:var(--bg); cursor:pointer; }
  .field input:focus{ border-color:var(--primary); }
  
  .btn-submit{ width:100%; padding:12px; border:none; border-radius:10px; background:var(--primary); color:#fff; font-weight:600; font-size:15px; cursor:pointer; font-family:inherit; margin-top:10px; }
  .btn-submit:hover{ opacity:.92; }
  .btn-outline { background:transparent; border:1.5px solid var(--border); color:var(--text); }
  
  .auth-footer{ margin-top:22px; text-align:center; font-size:13px; color:var(--muted); }
  .link{ color:var(--primary); text-decoration:none; font-weight:600; }
  
  .alert{ padding:12px; border-radius:8px; font-size:13.5px; margin-bottom:20px; font-weight:500; }
  .alert-error{ background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
  .alert-success{ background:#f0fdf4; border:1px solid #bbf7d0; color:#16a34a; }

  /* Giao diện OTP */
  #otp-screen { display: none; text-align: center; }
  .otp-inputs { display: flex; gap: 8px; justify-content: center; margin: 20px 0; }
  .otp-inputs input { width: 45px; height: 50px; font-size: 20px; text-align: center; font-weight: bold; border-radius: 8px; border: 1px solid var(--border); }
  .otp-inputs input:disabled { background: var(--bg); color: var(--muted); }
  #otp-timer-box { font-size: 13.5px; color: var(--muted); margin-bottom: 6px; }
  #otp-timer-box b { color: var(--primary); font-family:'Space Grotesk', sans-serif; }
</style>
</head>
<body>
<div class="auth-shell">
  <div class="auth-card" id="card-container">
    <div class="auth-brand">
      <span class="dot-route">X</span>
      <div>XeGhép<small>ĐĂNG KÝ TÀI KHOẢN MỚI</small></div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success_message) ?><br><br>
            <a href="login.php" class="btn-submit" style="display:inline-block; text-align:center; text-decoration:none;">Đến trang Đăng nhập</a>
        </div>
    <?php else: ?>

    <!-- BƯỚC 1: ĐIỀN THÔNG TIN -->
    <div id="step-info">
        <div class="role-toggle">
            <button type="button" id="btn-passenger" class="active" onclick="switchRole('passenger')">Khách hàng</button>
            <button type="button" id="btn-driver" onclick="switchRole('driver')">Tài xế (Đối tác)</button>
        </div>

        <form id="main-reg-form" method="POST" action="dang-ky.php" enctype="multipart/form-data">
            <input type="hidden" name="reg_role" id="reg_role" value="passenger">

            <!-- DÀNH CHO NGƯỜI DÙNG: CHỈ HIỂN THỊ 2 Ô NÀY -->
            <div id="passenger-fields" class="form-section active">
                <div class="field">
                    <label>Số điện thoại hoặc Email</label>
                    <input type="text" name="contact" id="contact" placeholder="Nhập SĐT hoặc Email" required>
                </div>
                <div class="field">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" id="passenger_pw" placeholder="Tối thiểu 6 ký tự" required>
                </div>
            </div>

            <!-- DÀNH CHO TÀI XẾ: HIỂN THỊ TOÀN BỘ GIẤY TỜ -->
            <div id="driver-fields" class="form-section">
                <div class="section-title">Thông tin & Giấy tờ tùy thân</div>
                <div class="field"><label>Họ và tên</label><input type="text" name="full_name" placeholder="Nguyễn Văn A"></div>
                <div class="field"><label>Số điện thoại</label><input type="tel" name="driver_phone" placeholder="09xx xxx xxx"></div>
                <div class="field"><label>Email (Tùy chọn)</label><input type="email" name="driver_email" placeholder="example@gmail.com"></div>
                <div class="field"><label>Số CCCD</label><input type="text" name="cccd_number" placeholder="Nhập 12 số CCCD"></div>
                
                <div class="field"><label>Mật khẩu</label><input type="password" name="driver_pw" id="driver_pw" placeholder="Tối thiểu 6 ký tự"></div>
                
                <div class="field"><label>Ảnh chân dung rõ mặt</label><input type="file" name="portrait" accept="image/*"></div>
                <div class="field"><label>Lý lịch tư pháp</label><input type="file" name="lltp" accept="image/*,.pdf"></div>
                <div class="field"><label>CCCD Mặt trước</label><input type="file" name="cccd_front" accept="image/*"></div>
                <div class="field"><label>CCCD Mặt sau</label><input type="file" name="cccd_back" accept="image/*"></div>

                <div class="section-title">Chuyên môn & Phương tiện</div>
                <div class="field"><label>Giấy phép lái xe</label><input type="file" name="gplx" accept="image/*"></div>
                <div class="field"><label>Giấy khám sức khỏe</label><input type="file" name="gksk" accept="image/*,.pdf"></div>
                <div class="field"><label>Loại xe</label><input type="text" name="vehicle_type" placeholder="VD: Kia Carnival 7 chỗ"></div>
                <div class="field"><label>Biển số xe</label><input type="text" name="license_plate" placeholder="VD: 30F-123.45"></div>
                <div class="field"><label>Giấy đăng ký xe</label><input type="file" name="cavet" accept="image/*"></div>
                <div class="field"><label>Bảo hiểm TNDS</label><input type="file" name="baohiem" accept="image/*"></div>
                <div class="field" style="grid-column:1/-1;"><label>Hình ảnh thực tế xe</label><input type="file" name="hinhxe" accept="image/*"></div>

                <div class="section-title">Tài khoản Ngân hàng</div>
                <div class="field" style="grid-column:1/-1;">
                    <label>Thông tin nhận doanh thu</label>
                    <input type="text" name="bank_info" placeholder="VD: Vietcombank - 0123456789 - NGUYEN VAN A">
                </div>
            </div>

            <button type="button" class="btn-submit" onclick="initiateRegistration()">Nhận mã xác nhận (OTP)</button>
        </form>

        <div class="auth-footer">
          Đã có tài khoản? <a href="login.php" class="link">Đăng nhập ngay</a>
        </div>
    </div>

    <!-- BƯỚC 2: NHẬP MÃ OTP -->
    <div id="otp-screen">
        <h2 style="margin-top:0">Xác thực tài khoản</h2>
        <p style="color:var(--muted); font-size:14px; margin-bottom:20px;">
            Hệ thống đã gửi một mã OTP đến <br><b id="otp-target" style="color:var(--text)"></b>
        </p>
        
        <div class="alert alert-success" id="otp-debug-note" style="font-size:12px; padding:8px; display:none;"></div>

        <div id="otp-timer-box">
            Mã có hiệu lực trong <b id="otp-countdown">01:30</b>
        </div>

        <div class="otp-inputs">
            <input type="text" maxlength="1" class="otp-box" onkeyup="moveToNext(this, event)">
            <input type="text" maxlength="1" class="otp-box" onkeyup="moveToNext(this, event)">
            <input type="text" maxlength="1" class="otp-box" onkeyup="moveToNext(this, event)">
            <input type="text" maxlength="1" class="otp-box" onkeyup="moveToNext(this, event)">
            <input type="text" maxlength="1" class="otp-box" onkeyup="moveToNext(this, event)">
            <input type="text" maxlength="1" class="otp-box" onkeyup="moveToNext(this, event)">
        </div>
        
        <div class="alert alert-error" id="otp-error" style="display:none; padding:8px;">Mã xác nhận không hợp lệ!</div>
        <div class="alert alert-error" id="otp-expired-msg" style="display:none; padding:8px;">Mã OTP đã hết hạn. Vui lòng bấm "Gửi lại mã OTP" để nhận mã mới.</div>

        <button type="button" class="btn-submit" id="btn-verify-otp" onclick="verifyOTP()">Xác nhận & Hoàn tất</button>
        <button type="button" class="btn-submit btn-outline" id="btn-resend-otp" style="display:none;" onclick="resendOTP()">Gửi lại mã OTP</button>
        <button type="button" class="btn-submit btn-outline" onclick="cancelOTP()">Quay lại sửa thông tin</button>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
  let currentRole = 'passenger';

  function switchRole(role) {
    currentRole = role;
    document.getElementById('reg_role').value = role;
    
    document.getElementById('btn-passenger').classList.toggle('active', role === 'passenger');
    document.getElementById('btn-driver').classList.toggle('active', role === 'driver');
    
    const card = document.getElementById('card-container');
    const driverFields = document.getElementById('driver-fields');
    const passengerFields = document.getElementById('passenger-fields');
    
    if(role === 'driver') {
        card.classList.add('is-driver');
        driverFields.classList.add('active');
        passengerFields.classList.remove('active');
        
        document.getElementById('contact').required = false;
        document.getElementById('passenger_pw').name = 'ignore_pw'; 
        document.getElementById('driver_pw').name = 'password';     
        
        driverFields.querySelectorAll('input').forEach(input => {
            if(input.name !== 'bank_info' && input.name !== 'driver_email') input.required = true;
        });
    } else {
        card.classList.remove('is-driver');
        driverFields.classList.remove('active');
        passengerFields.classList.add('active');
        
        document.getElementById('contact').required = true;
        document.getElementById('passenger_pw').name = 'password';
        document.getElementById('driver_pw').name = 'ignore_pw';
        
        driverFields.querySelectorAll('input').forEach(input => input.required = false);
    }
  }

  const OTP_DURATION = 90; // 1 phút 30 giây
  let otpTimerInterval = null;
  let otpSecondsLeft = OTP_DURATION;

  function initiateRegistration() {
      const form = document.getElementById('main-reg-form');
      if (!form.reportValidity()) return; 

      let target = '';
      if(currentRole === 'passenger') {
          target = document.getElementById('contact').value;
      } else {
          const phone = document.getElementsByName('driver_phone')[0].value;
          const email = document.getElementsByName('driver_email')[0].value;
          target = email ? email : phone;
      }

      document.getElementById('otp-target').textContent = target;

      requestOTP(target, 'send_otp', function() {
          document.getElementById('step-info').style.display = 'none';
          document.getElementById('otp-screen').style.display = 'block';
          document.querySelector('.otp-box').focus();
      });
  }

  function requestOTP(target, action, onSuccess) {
      const fd = new FormData();
      fd.append('ajax_action', action);
      fd.append('otp_target', target);
      fd.append('reg_role', currentRole);

      fetch('dang-ky.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('otp-error').style.display = 'none';
                document.getElementById('otp-expired-msg').style.display = 'none';
                document.getElementById('otp-timer-box').style.display = 'block';
                document.getElementById('btn-verify-otp').style.display = 'inline-block';
                document.getElementById('btn-resend-otp').style.display = 'none';

                clearOtpInputs();
                setOtpInputsEnabled(true);
                startOtpCountdown(data.expires_in || OTP_DURATION);

                // Ghi chú chỉ phục vụ TEST khi chưa nối SMS/Email thật — có thể xoá khi lên production
                const debugNote = document.getElementById('otp-debug-note');
                if (data.debug_code) {
                    debugNote.style.display = 'block';
                    debugNote.textContent = '(Test) Mã OTP của bạn: ' + data.debug_code;
                } else {
                    debugNote.style.display = 'none';
                }

                if (onSuccess) onSuccess();
            } else {
                alert(data.message || 'Không thể gửi mã OTP, vui lòng thử lại.');
            }
        })
        .catch(() => alert('Lỗi kết nối máy chủ, vui lòng thử lại sau.'));
  }

  function startOtpCountdown(seconds) {
      clearInterval(otpTimerInterval);
      otpSecondsLeft = seconds;
      updateOtpCountdownDisplay();

      otpTimerInterval = setInterval(() => {
          otpSecondsLeft--;
          if (otpSecondsLeft <= 0) {
              clearInterval(otpTimerInterval);
              onOtpExpired();
          } else {
              updateOtpCountdownDisplay();
          }
      }, 1000);
  }

  function updateOtpCountdownDisplay() {
      const m = Math.floor(otpSecondsLeft / 60).toString().padStart(2, '0');
      const s = (otpSecondsLeft % 60).toString().padStart(2, '0');
      document.getElementById('otp-countdown').textContent = m + ':' + s;
  }

  function onOtpExpired() {
      document.getElementById('otp-timer-box').style.display = 'none';
      document.getElementById('otp-expired-msg').style.display = 'block';
      document.getElementById('btn-verify-otp').style.display = 'none';
      document.getElementById('btn-resend-otp').style.display = 'inline-block';
      setOtpInputsEnabled(false);
  }

  function resendOTP() {
      const target = document.getElementById('otp-target').textContent;
      requestOTP(target, 'resend_otp');
  }

  function clearOtpInputs() {
      document.querySelectorAll('.otp-box').forEach(input => input.value = '');
  }

  function setOtpInputsEnabled(enabled) {
      document.querySelectorAll('.otp-box').forEach(input => input.disabled = !enabled);
  }

  function moveToNext(current, event) {
      if (current.value.length >= current.maxLength) {
          let next = current.nextElementSibling;
          if (next) next.focus();
      }
      if (event.key === "Backspace") {
          let prev = current.previousElementSibling;
          if (prev) prev.focus();
      }
  }

  function cancelOTP() {
      clearInterval(otpTimerInterval);
      document.getElementById('step-info').style.display = 'block';
      document.getElementById('otp-screen').style.display = 'none';
      document.getElementById('otp-error').style.display = 'none';
      document.getElementById('otp-expired-msg').style.display = 'none';
      document.getElementById('otp-debug-note').style.display = 'none';
  }

  function verifyOTP() {
      let otpCode = '';
      document.querySelectorAll('.otp-box').forEach(input => otpCode += input.value);

      if (otpCode.length < 6) {
          document.getElementById('otp-error').textContent = 'Vui lòng nhập đủ 6 số.';
          document.getElementById('otp-error').style.display = 'block';
          return;
      }

      const fd = new FormData();
      fd.append('ajax_action', 'verify_otp');
      fd.append('otp_code', otpCode);

      fetch('dang-ky.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                clearInterval(otpTimerInterval);
                document.getElementById('main-reg-form').submit();
            } else {
                document.getElementById('otp-error').textContent = data.message || 'Mã xác nhận không hợp lệ!';
                document.getElementById('otp-error').style.display = 'block';
                if (data.expired) {
                    onOtpExpired();
                }
            }
        })
        .catch(() => {
            document.getElementById('otp-error').textContent = 'Lỗi kết nối máy chủ, vui lòng thử lại.';
            document.getElementById('otp-error').style.display = 'block';
        });
  }
</script>
</body>
</html>