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

// 1. Thông tin cá nhân & xe
$stmt = $pdo->prepare("SELECT u.full_name, u.avatar, u.phone, u.status AS account_status, u.created_at,
                              dp.vehicle_type, dp.license_plate, dp.rating
                       FROM users u JOIN driver_profiles dp ON u.user_id = dp.driver_id
                       WHERE u.user_id = ?");
$stmt->execute([$driver_id]);
$driver = $stmt->fetch();

// 2. Badge số yêu cầu đặt chỗ đang chờ duyệt (sidebar)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings b
                       JOIN trips t ON b.trip_id = t.trip_id
                       WHERE t.driver_id = ? AND b.status = 'pending_approval'");
$stmt->execute([$driver_id]);
$pendingCount = $stmt->fetchColumn();

// 3. Danh sách giấy tờ đã nộp
$stmt = $pdo->prepare("SELECT doc_id, doc_type, doc_name, file_path, status, created_at
                       FROM driver_documents WHERE driver_id = ? ORDER BY created_at ASC");
$stmt->execute([$driver_id]);
$documents = $stmt->fetchAll();

// Icon hiển thị theo loại giấy tờ
$docIcons = [
    'cccd' => '🪪',
    'license' => '📇',
    'registration' => '🚗',
    'insurance' => '🛡️',
];
function docIcon(array $docIcons, string $type): string {
    return $docIcons[$type] ?? '📄';
}

// Trạng thái tổng thể hồ sơ: đã duyệt hết chưa
$pendingDocs = array_filter($documents, fn($d) => $d['status'] !== 'approved');
$allApproved = !empty($documents) && count($pendingDocs) === 0;

function statusLabel(string $status): array {
    switch ($status) {
        case 'approved': return ['Đã duyệt', 'approved'];
        case 'rejected': return ['Bị từ chối', 'rejected'];
        default: return ['Chờ duyệt', 'pending'];
    }
}

$nameParts = explode(' ', trim($driver['full_name']));
$avatarLetter = $driver['avatar'] ?? mb_substr(end($nameParts), 0, 1);
$driverSinceYear = date('Y', strtotime($driver['created_at']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>XeGhép — Tài xế — Hồ sơ &amp; giấy tờ</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><span class="dot-route">X</span><div>XeGhép<small>TÀI XẾ</small></div></div>
    <nav class="side-nav">
      <a href="index.php"><span class="ic">🏠</span> Tổng quan</a>
      <a href="yeu-cau-dat-cho.php"><span class="ic">📥</span> Yêu cầu đặt chỗ <?php if($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?></a>
      <a href="chuyen-cua-toi.php"><span class="ic">🧭</span> Chuyến của tôi</a>
      <a href="thu-nhap.php"><span class="ic">₫</span> Thu nhập</a>
      <a href="ho-so.php" class="active"><span class="ic">👤</span> Hồ sơ &amp; giấy tờ</a>
    </nav>
    <div class="sidebar-foot">
      <div class="av"><?= htmlspecialchars($avatarLetter) ?></div>
      <div><b><?= htmlspecialchars($driver['full_name']) ?></b><span>★ <?= number_format($driver['rating'], 1) ?> · <?= $allApproved ? 'Đã duyệt' : 'Chờ duyệt' ?></span></div>
    </div>
  </aside>

  <main class="main">

    <div class="view" id="view-profile">
      <div class="topbar"><div><h1>Hồ sơ &amp; giấy tờ</h1><p>Thông tin cá nhân, xe và trạng thái duyệt hồ sơ.</p></div></div>

      <div class="profile-layout">
        <div class="card" style="text-align:center">
          <div class="av-xl" id="profile-avatar"><?= htmlspecialchars($avatarLetter) ?></div>
          <h3 style="font-size:16px" id="profile-name-display"><?= htmlspecialchars($driver['full_name']) ?></h3>
          <p style="font-size:12px;color:var(--ink-faint);margin-top:2px">Tài xế từ <?= htmlspecialchars($driverSinceYear) ?></p>
          <div style="margin-top:10px" id="profile-overall-status">
            <?php if ($allApproved): ?>
              <span class="doc-status approved">Hồ sơ đã duyệt</span>
            <?php else: ?>
              <span class="doc-status pending"><?= count($pendingDocs) ?> giấy tờ đang chờ/cần xử lý</span>
            <?php endif; ?>
          </div>
        </div>

        <div>
          <div class="card">
            <div class="list-title">Thông tin cá nhân &amp; xe</div>
            <form id="profile-form" class="form-grid" onsubmit="return false">
              <div class="field"><label>Họ và tên</label><div class="input"><input name="fullName" value="<?= htmlspecialchars($driver['full_name']) ?>"></div></div>
              <div class="field"><label>Số điện thoại</label><div class="input"><input name="phone" value="<?= htmlspecialchars($driver['phone']) ?>"></div></div>
              <div class="field"><label>Loại xe</label><div class="input"><input name="vehicle" value="<?= htmlspecialchars($driver['vehicle_type']) ?>"></div></div>
              <div class="field"><label>Biển số</label><div class="input"><input name="plate" value="<?= htmlspecialchars($driver['license_plate']) ?>"></div></div>
            </form>
          </div>

          <div class="card">
            <div class="list-title">Giấy tờ đã nộp</div>
            <div id="documents-list">
              <?php foreach ($documents as $doc): [$label, $cls] = statusLabel($doc['status']); ?>
              <div class="doc-row" data-doc-id="<?= (int)$doc['doc_id'] ?>" data-doc-type="<?= htmlspecialchars($doc['doc_type']) ?>" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:nowrap">
                <div class="l" style="display:flex;align-items:center;gap:6px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><span class="ic"><?= docIcon($docIcons, $doc['doc_type']) ?></span> <span data-doc-name><?= htmlspecialchars($doc['doc_name']) ?></span></div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;white-space:nowrap">
                  <span class="doc-status <?= $cls ?>" data-status-label><?= $label ?></span>
                  <button type="button" class="btn btn-outline btn-sm" onclick="replaceDocument(this)">Cập nhật</button>
                  <button type="button" class="btn btn-outline btn-sm" onclick="deleteDocument(this)">Xóa</button>
                </div>
              </div>
              <?php endforeach; ?>
              <?php if (empty($documents)): ?>
                <p id="no-docs-msg" style="font-size:13px;color:var(--ink-faint)">Chưa nộp giấy tờ nào.</p>
              <?php endif; ?>
            </div>
            <button class="btn btn-outline" style="margin-top:6px" onclick="uploadDocument()">+ Tải lên giấy tờ mới</button>
            <input type="file" id="doc-file-input" class="hidden" accept=".jpg,.jpeg,.png,.pdf" onchange="onDocFileSelected(event)">
          </div>

          <button type="button" class="btn btn-primary full" id="btn-save-profile" style="padding:12px 20px" onclick="saveProfile()">Lưu thay đổi</button>
        </div>
      </div>
    </div>

  </main>
</div>

<script src="assets/common.js"></script>
<script>
  let pendingUpload = null; // { docType, docName, btn } của lượt tải file sắp chọn
  const docIcons = <?= json_encode($docIcons, JSON_UNESCAPED_UNICODE) ?>;

  async function saveProfile(){
    const form = document.getElementById('profile-form');
    const data = Object.fromEntries(new FormData(form).entries());
    const btn = document.getElementById('btn-save-profile');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Đang lưu…';

    try {
      const res = await fetch('save-profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
      });
      const result = await res.json();
      if (!result.ok) {
        alert(result.message || 'Không thể lưu thay đổi.');
        return;
      }
      document.getElementById('profile-name-display').textContent = result.fullName;
      document.getElementById('profile-avatar').textContent = result.fullName.trim().split(' ').pop().charAt(0);
      alert('Đã gửi thông tin cho admin, hồ sơ đang chờ phê duyệt.');
    } catch (err) {
      alert('Lỗi kết nối, vui lòng thử lại.');
    } finally {
      btn.disabled = false;
      btn.textContent = originalText;
    }
  }

  // Tải giấy tờ MỚI (loại chưa có trong danh sách)
  function uploadDocument(){
    const docName = prompt('Nhập tên loại giấy tờ (VD: Giấy khám sức khoẻ):');
    if (!docName) return;
    const docType = docName.toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // bỏ dấu tiếng Việt
      .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || 'other_' + Date.now();
    pendingUpload = { docType, docName, btn: null };
    document.getElementById('doc-file-input').click();
  }

  // Cập nhật lại giấy tờ đã có (thay ảnh/file mới -> reset về chờ duyệt)
  function replaceDocument(btn){
    const row = btn.closest('.doc-row');
    pendingUpload = { docType: row.dataset.docType, docName: row.querySelector('[data-doc-name]').textContent, btn };
    document.getElementById('doc-file-input').click();
  }

  async function onDocFileSelected(e){
    const file = e.target.files[0];
    e.target.value = '';
    if (!file || !pendingUpload) return;
    const { docType, docName } = pendingUpload;

    const fd = new FormData();
    fd.append('doc_type', docType);
    fd.append('doc_name', docName);
    fd.append('file', file);

    try {
      const res = await fetch('upload-document.php', { method: 'POST', body: fd });
      const result = await res.json();
      if (!result.ok) {
        alert(result.message || 'Tải lên thất bại.');
        return;
      }
      addOrUpdateDocRow(docType, docName, result.docId);
    } catch (err) {
      alert('Lỗi kết nối, vui lòng thử lại.');
    } finally {
      pendingUpload = null;
    }
  }

  // Xóa một giấy tờ đã nộp
  async function deleteDocument(btn){
    const row = btn.closest('.doc-row');
    const docId = row.dataset.docId;
    const docName = row.querySelector('[data-doc-name]').textContent;

    if (!confirm(`Xóa giấy tờ "${docName}"?`)) return;

    btn.disabled = true;
    try {
      const res = await fetch('delete-document.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ doc_id: docId, doc_type: row.dataset.docType })
      });
      const result = await res.json();
      if (!result.ok) {
        alert(result.message || 'Không thể xóa giấy tờ.');
        btn.disabled = false;
        return;
      }
      row.remove();

      const list = document.getElementById('documents-list');
      if (!list.querySelector('.doc-row')) {
        const p = document.createElement('p');
        p.id = 'no-docs-msg';
        p.style.cssText = 'font-size:13px;color:var(--ink-faint)';
        p.textContent = 'Chưa nộp giấy tờ nào.';
        list.appendChild(p);
      }
    } catch (err) {
      alert('Lỗi kết nối, vui lòng thử lại.');
      btn.disabled = false;
    }
  }

  // Thêm/ cập nhật ngay dòng giấy tờ trong "Giấy tờ đã nộp" (không cần tải lại trang),
  // hiện "Tải lên thành công" rồi tự chuyển về "Chờ duyệt".
  function addOrUpdateDocRow(docType, docName, docId){
    const list = document.getElementById('documents-list');
    const noDocsMsg = document.getElementById('no-docs-msg');
    if (noDocsMsg) noDocsMsg.remove();

    let row = list.querySelector(`.doc-row[data-doc-type="${CSS.escape(docType)}"]`);
    if (!row) {
      row = document.createElement('div');
      row.className = 'doc-row';
      row.dataset.docType = docType;
      if (docId != null) row.dataset.docId = docId;
      row.style.cssText = 'display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:nowrap';
      row.innerHTML = `<div class="l" style="display:flex;align-items:center;gap:6px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><span class="ic">${docIcons[docType] || '📄'}</span> <span data-doc-name>${docName}</span></div>
                        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;white-space:nowrap">
                          <span class="doc-status pending" data-status-label>Chờ duyệt</span>
                          <button type="button" class="btn btn-outline btn-sm" onclick="replaceDocument(this)">Cập nhật</button>
                          <button type="button" class="btn btn-outline btn-sm" onclick="deleteDocument(this)">Xóa</button>
                        </div>`;
      list.appendChild(row);
    } else {
      row.querySelector('[data-doc-name]').textContent = docName;
      if (docId != null) row.dataset.docId = docId;
    }

    const statusEl = row.querySelector('[data-status-label]');
    statusEl.className = 'doc-status approved';
    statusEl.textContent = '✅ Tải lên thành công';
    setTimeout(() => {
      statusEl.className = 'doc-status pending';
      statusEl.textContent = 'Chờ duyệt';
    }, 1800);
  }
</script>
</body>
</html>