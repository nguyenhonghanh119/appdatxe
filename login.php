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

// Nếu người dùng đã đăng nhập từ trước, tự động chuyển hướng theo phân quyền để không bắt đăng nhập lại
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['status'] === 'pending' && $_SESSION['role'] === 'driver') {
        header("Location: tai-xe/cho-duyet.php");
    } elseif ($_SESSION['role'] === 'admin') {
        header("Location: quan-tri/index.php");
    } elseif ($_SESSION['role'] === 'driver') {
        header("Location: index.php"); // Giao diện tài xế
    } elseif ($_SESSION['role'] === 'passenger') {
        header("Location: nguoi-dung/index.php"); // Giao diện hành khách
    }
    exit;
}

$error_message = '';

// Xử lý dữ liệu khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($phone) || empty($password)) {
        $error_message = 'Vui lòng nhập đầy đủ số điện thoại và mật khẩu.';
    } else {
        // Truy vấn CSDL lấy thông tin user theo số điện thoại
        $stmt = $pdo->prepare("SELECT user_id, full_name, password_hash, role, status FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $userData = $stmt->fetch();

        // Kiểm tra mật khẩu (so sánh mật khẩu nhập vào với mã băm trong DB)
        if ($userData && password_verify($password, $userData['password_hash'])) {
            if ($userData['status'] === 'locked') {
                $error_message = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin.';
            } else {
                // Lưu thông tin vào Session
                $_SESSION['user_id'] = $userData['user_id'];
                $_SESSION['role'] = $userData['role'];
                $_SESSION['status'] = $userData['status'];
                $_SESSION['full_name'] = $userData['full_name'];
                
                // ĐIỀU HƯỚNG PHÂN QUYỀN CHÍNH THỨC DỰA VÀO DATA
                if ($userData['status'] === 'pending' && $userData['role'] === 'driver') {
                    // Tài xế chưa được duyệt giấy tờ
                    header("Location: tai-xe/cho-duyet.php");
                } elseif ($userData['role'] === 'admin') {
                    // Quản trị viên
                    header("Location: quan-tri/index.php");
                } elseif ($userData['role'] === 'driver') {
                    // Tài xế đã được duyệt
                    header("Location: index.php"); 
                } elseif ($userData['role'] === 'passenger') {
                    // Hành khách
                    header("Location: nguoi-dung/index.php"); 
                }
                exit;
            }
        } else {
            $error_message = 'Số điện thoại hoặc mật khẩu không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Đăng nhập</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
  .auth-shell{
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
    background:var(--bg, #f5f6f8);
    font-family:'Inter', sans-serif;
  }
  .auth-card{
    width:100%;
    max-width:400px;
    background:var(--card-bg, #fff);
    border:1px solid var(--border, #e5e7eb);
    border-radius:16px;
    padding:32px 28px;
    box-shadow:0 8px 24px rgba(0,0,0,0.06);
  }
  .auth-brand{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:24px;
  }
  .auth-brand .dot-route{
    width:36px;height:36px;border-radius:10px;
    background:var(--primary, #2563eb);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:18px;
  }
  .auth-brand div{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:17px;}
  .auth-brand small{display:block;font-family:'Inter',sans-serif;font-weight:500;font-size:11px;letter-spacing:.04em;color:var(--muted,#6b7280);}
  .auth-title{font-family:'Space Grotesk',sans-serif;font-weight:600;font-size:22px;margin:0 0 4px;}
  .auth-sub{color:var(--muted,#6b7280);font-size:14px;margin:0 0 24px;}
  .field{margin-bottom:16px;}
  .field label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:var(--text,#111827);}
  .field .input-wrap{position:relative;display:flex;align-items:center;}
  .field .input-wrap .ic{position:absolute;left:12px;font-size:15px;opacity:.6;}
  .field input{
    width:100%;
    padding:11px 12px 11px 38px;
    border:1px solid var(--border,#d1d5db);
    border-radius:10px;
    font-size:14px;
    font-family:'Inter',sans-serif;
    outline:none;
    transition:border-color .15s;
    box-sizing:border-box;
  }
  .field input:focus{border-color:var(--primary,#2563eb);}
  .field .toggle-pw{
    position:absolute;right:10px;background:none;border:none;cursor:pointer;font-size:13px;color:var(--muted,#6b7280);
  }
  .field-error{color:#dc2626;font-size:12px;margin-top:4px;display:none;}
  .row-between{display:flex;align-items:center;justify-content:space-between;margin:2px 0 20px;font-size:13px;}
  .remember-me{display:flex;align-items:center;gap:6px;color:var(--muted,#6b7280);}
  .link{color:var(--primary,#2563eb);text-decoration:none;font-weight:600;}
  .link:hover{text-decoration:underline;}
  .btn-login{
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:var(--primary,#2563eb);
    color:#fff;
    font-weight:600;
    font-size:15px;
    cursor:pointer;
    font-family:'Inter',sans-serif;
    transition:opacity .15s;
  }
  .btn-login:hover{opacity:.92;}
  .btn-login:disabled{opacity:.6;cursor:not-allowed;}
  .auth-footer{margin-top:22px;text-align:center;font-size:13px;color:var(--muted,#6b7280);}
  .auth-alert{
    background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;
    padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:16px;
  }
</style>
</head>
<body>
<div class="auth-shell">
  <div class="auth-card">
    <div class="auth-brand">
      <span class="dot-route">X</span>
      <div>XeGhép<small>ĐI CHUNG THÔNG MINH</small></div>
    </div>

    <h1 class="auth-title">Đăng nhập</h1>
    
    <!-- PHP in ra lỗi nếu đăng nhập sai -->
    <?php if (!empty($error_message)): ?>
        <div class="auth-alert" id="login-alert"><?= htmlspecialchars($error_message) ?></div>
    <?php else: ?>
        <div class="auth-alert" id="login-alert" style="display:none;"></div>
    <?php endif; ?>

    <!-- Form action trỏ về chính nó, dùng method POST -->
    <form id="login-form" method="POST" action="login.php" onsubmit="return validateForm()">
      <div class="field">
        <label for="phone">Số điện thoại</label>
        <div class="input-wrap">
          <span class="ic">📱</span>
          <!-- Giữ lại sđt đã gõ nếu có lỗi để người dùng không phải nhập lại từ đầu -->
          <input type="tel" id="phone" name="phone" placeholder="09xx xxx xxx" inputmode="numeric" autocomplete="tel" value="<?= htmlspecialchars($phone ?? '') ?>" required>
        </div>
        <div class="field-error" id="phone-error">Vui lòng nhập số điện thoại hợp lệ.</div>
      </div>

      <div class="field">
        <label for="password">Mật khẩu</label>
        <div class="input-wrap">
          <span class="ic">🔒</span>
          <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required>
          <button type="button" class="toggle-pw" id="toggle-pw" onclick="togglePassword()">Hiện</button>
        </div>
        <div class="field-error" id="password-error">Vui lòng nhập mật khẩu (tối thiểu 6 ký tự).</div>
      </div>

      <div class="row-between">
        <label class="remember-me">
          <input type="checkbox" id="remember" name="remember">
          Ghi nhớ đăng nhập
        </label>
        <a href="quen-mat-khau.php" class="link">Quên mật khẩu?</a>
      </div>

      <button type="submit" class="btn-login" id="btn-submit">Đăng nhập</button>
    </form>

    <div class="auth-footer">
      Chưa có tài khoản? <a href="dang-ky.php" class="link">Đăng ký ngay</a>
    </div>
  </div>
</div>
<div class="auth-footer">
      Chưa có tài khoản? <a href="dang-ky.php" class="link">Đăng ký ngay</a>
    </div>

<script>
  function togglePassword(){
    const pw = document.getElementById('password');
    const btn = document.getElementById('toggle-pw');
    const isHidden = pw.type === 'password';
    pw.type = isHidden ? 'text' : 'password';
    btn.textContent = isHidden ? 'Ẩn' : 'Hiện';
  }

  // Chặn form lại nếu chưa nhập đúng định dạng (kiểm tra Frontend)
  function validateForm(){
    const phone = document.getElementById('phone');
    const password = document.getElementById('password');
    const phoneErr = document.getElementById('phone-error');
    const pwErr = document.getElementById('password-error');
    const alertBox = document.getElementById('login-alert');
    const btn = document.getElementById('btn-submit');

    let valid = true;
    const phoneRegex = /^0\d{9}$/;
    
    if(!phoneRegex.test(phone.value.trim())){
      phoneErr.style.display = 'block';
      valid = false;
    } else {
      phoneErr.style.display = 'none';
    }
    
    if(password.value.trim().length < 6){
      pwErr.style.display = 'block';
      valid = false;
    } else {
      pwErr.style.display = 'none';
    }
    
    if (alertBox) alertBox.style.display = 'none';

    if(!valid) return false;

    // Đổi chữ nút thành "Đang đăng nhập..." để UX tốt hơn
    btn.disabled = true;
    btn.textContent = 'Đang đăng nhập…';
    return true; 
  }
</script>
</body>
</html>