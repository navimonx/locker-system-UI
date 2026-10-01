<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/user_profile.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$isAdminUser = $isAdmin;
$isStudent = !$isAdminUser;

$pwSuccess = '';
$pwError   = '';
$contactSuccess = '';
$contactError   = '';

$profile = fetch_user_profile($conn, $userId) ?? [];
$profileCols = users_profile_columns($conn);
$showContactForm = $isStudent && ($profileCols['contact'] || $profileCols['email']);

require_once __DIR__ . '/includes/popup_alerts.php';

if (isset($_GET['incomplete'])) {
    $contactError = 'Please add your course, contact number, and email before reserving a locker.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password']     ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            $pwError = 'Please fill in all password fields.';
        } elseif ($newPassword !== $confirmPassword) {
            $pwError = 'New password and confirmation do not match.';
        } elseif (strlen($newPassword) < 6) {
            $pwError = 'New password must be at least 6 characters long.';
        } elseif ($currentPassword === $newPassword) {
            $pwError = 'New password must be different from your current password.';
        } else {
            $stmtVerify = db_query($conn, "SELECT password FROM Users WHERE id = ?", [$userId]);
            $verifyRow  = $stmtVerify ? db_fetch($stmtVerify) : null;
            if ($stmtVerify === false) {
                $pwError = 'A database error occurred. Please try again.';
            } elseif (!$verifyRow || !app_password_matches($currentPassword, $verifyRow['password'] ?? '')) {
                $pwError = 'Current password is incorrect.';
            } else {
                $stmtUpdate = db_query($conn, "UPDATE Users SET password = ? WHERE id = ?", [app_hash_password($newPassword), $userId]);
                $pwSuccess = $stmtUpdate === false ? 'Failed to update password.' : 'Password changed successfully!';
            }
        }
    }

    if (isset($_POST['update_contact']) && $showContactForm) {
        $result = update_user_contact_email(
            $conn,
            $userId,
            $_POST['contact'] ?? '',
            $_POST['email'] ?? ''
        );
        if ($result['ok']) {
            $contactSuccess = $result['message'];
            $profile = fetch_user_profile($conn, $userId) ?? [];
        } else {
            $contactError = $result['message'];
        }
    }
}

if ($contactSuccess) {
    popup_add('success', $contactSuccess);
}
if ($contactError) {
    popup_add('error', $contactError);
}
if ($pwSuccess) {
    popup_add('success', $pwSuccess);
}
if ($pwError) {
    popup_add('error', $pwError);
}

$pageTitle   = 'Settings — SecureLocker Inc.';
$activePage  = 'settings';
$adminActive = 'settings';
include __DIR__ . '/includes/head.php';
if ($isAdminUser) {
    include __DIR__ . '/includes/admin_navbar.php';
} else {
    include __DIR__ . '/includes/navbar.php';
}
?>

<main class="page-main page-main--center">
  <div class="auth-card settings-card" style="max-width:520px;">
    <h2>⚙️ Account Settings</h2>
    <p class="subtitle">Logged in as <strong><?= htmlspecialchars($displayName ?? 'User') ?></strong></p>

    <?php if ($isStudent && !empty($profile['course'])): ?>
      <div class="settings-readonly">
        <label>Course / Program</label>
        <p><?= htmlspecialchars($profile['course']) ?></p>
        <span class="hint">Course cannot be changed here. Contact your admin if it is incorrect.</span>
      </div>
    <?php endif; ?>

    <?php if ($showContactForm): ?>
      <hr class="settings-divider">
      <h3 style="font-size:16px;margin-bottom:12px;">Contact Details</h3>
      <form method="POST" action="profile.php">
        <input type="hidden" name="update_contact" value="1">
        <?php if ($profileCols['contact']): ?>
        <div class="form-group">
          <label for="contact">Contact Number</label>
          <input type="text" id="contact" name="contact" placeholder="e.g. 09XXXXXXXXX"
                 value="<?= htmlspecialchars($profile['contact'] ?? '') ?>" required>
        </div>
        <?php endif; ?>
        <?php if ($profileCols['email']): ?>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="e.g. student@plv.edu.ph"
                 value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Save Contact Details</button>
      </form>
    <?php endif; ?>

    <hr class="settings-divider">
    <h3 style="font-size:16px;margin-bottom:12px;">Change Password</h3>
    <?php if (!$pwSuccess): ?>
      <form method="POST" action="profile.php">
        <input type="hidden" name="update_password" value="1">
        <div class="form-group">
          <label for="current_password">Current Password</label>
          <div class="pw-wrap">
            <input type="password" id="current_password" name="current_password" required>
            <button type="button" class="toggle-eye" onclick="toggleVis('current_password',this)">👁</button>
          </div>
        </div>
        <div class="form-group">
          <label for="new_password">New Password</label>
          <div class="pw-wrap">
            <input type="password" id="new_password" name="new_password" required oninput="checkStrength(this.value)">
            <button type="button" class="toggle-eye" onclick="toggleVis('new_password',this)">👁</button>
          </div>
          <div class="strength-bar-wrap" id="strengthWrap"><div class="strength-bar" id="strengthBar"></div></div>
          <div class="strength-label" id="strengthLabel"></div>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <div class="pw-wrap">
            <input type="password" id="confirm_password" name="confirm_password" required oninput="checkMatch()">
            <button type="button" class="toggle-eye" onclick="toggleVis('confirm_password',this)">👁</button>
          </div>
          <div class="hint" id="matchHint"></div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Update Password</button>
      </form>
    <?php endif; ?>

    <div class="divider" style="margin-top:24px;">
      <?php if ($isAdminUser): ?>
        <a href="adminpage.php">← Back to Admin Dashboard</a>
      <?php else: ?>
        <a href="index.php">← Back to Home</a>
      <?php endif; ?>
    </div>
  </div>
</main>

<script>
function toggleVis(id, btn) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
  btn.textContent = el.type === 'password' ? '👁' : '🙈';
}
function checkStrength(val) {
  const wrap = document.getElementById('strengthWrap'), bar = document.getElementById('strengthBar'), label = document.getElementById('strengthLabel');
  if (!wrap || !val) { if (wrap) wrap.style.display = 'none'; return; }
  wrap.style.display = label.style.display = 'block';
  let s = 0;
  if (val.length >= 6) s++; if (val.length >= 10) s++;
  if (/[A-Z]/.test(val)) s++; if (/[0-9]/.test(val)) s++; if (/[^A-Za-z0-9]/.test(val)) s++;
  const levels = [{p:'20%',c:'#ef4444',t:'Very Weak'},{p:'40%',c:'#f59e0b',t:'Weak'},{p:'60%',c:'#f59e0b',t:'Fair'},{p:'80%',c:'#22c55e',t:'Strong'},{p:'100%',c:'#22c55e',t:'Very Strong'}];
  const l = levels[s-1]||levels[0];
  bar.style.width=l.p; bar.style.background=l.c; label.textContent='Strength: '+l.t; label.style.color=l.c;
}
function checkMatch() {
  const np = document.getElementById('new_password')?.value, cp = document.getElementById('confirm_password')?.value, hint = document.getElementById('matchHint');
  if (!hint || !cp) return;
  hint.textContent = np===cp ? '✅ Passwords match.' : '❌ Passwords do not match.';
  hint.style.color = np===cp ? '#86efac' : '#fca5a5';
}
</script>

<?php
if ($isAdminUser) {
    include __DIR__ . '/includes/admin_footer.php';
} else {
    include __DIR__ . '/includes/footer.php';
}
?>
