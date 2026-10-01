<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/user_profile.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dept     = trim($_POST['dept'] ?? '');
    $floor    = (int) ($_POST['floor'] ?? 0);
    $slot     = (int) ($_POST['slot'] ?? 0);
    $lockerId = (int) ($_POST['locker_id'] ?? 0);
    $duration = trim($_POST['duration'] ?? '');

    if ($dept === '' || $floor < 1 || $duration === '') {
        header("Location: rentals.php?error=invalid");
        exit();
    }

    $profile = fetch_user_profile($conn, $userId);
    $course  = trim($profile['course'] ?? '');
    $contact = trim($profile['contact'] ?? '');
    $email   = trim($profile['email'] ?? '');

    if ($course === '' || ($contact === '' && $email === '')) {
        header("Location: profile.php?incomplete=1");
        exit();
    }

    $contactSnapshot = $contact !== '' ? $contact : $email;

    $result = create_pending_reservation(
        $conn,
        $userId,
        $dept,
        $floor,
        $lockerId > 0 ? $lockerId : null,
        $slot > 0 ? $slot : null,
        $duration,
        $course,
        $contactSnapshot
    );

    if (!$result['ok']) {
        $code = $result['code'] ?? 'failed';
        header('Location: lockers.php?dept=' . urlencode($dept) . '&floor=' . $floor . '&error=' . urlencode($code));
        exit();
    }

    header('Location: receipt.php?reservation_id=' . urlencode((string) ($result['reservation_id'] ?? '')));
    exit();
}

$dept  = trim($_GET['dept'] ?? '');
$floor = (int) ($_GET['floor'] ?? 0);
$slot  = (int) ($_GET['slot'] ?? 0);
$lockerId = (int) ($_GET['locker'] ?? 0);

$resolvedLockerId = ($dept !== '' && $floor > 0)
    ? resolve_locker_id($conn, $dept, $floor, $lockerId > 0 ? $lockerId : null, $slot > 0 ? $slot : null)
    : null;

if ($slot <= 0 && $resolvedLockerId && $dept !== '' && $floor > 0) {
    $stmtSlot = db_query($conn,
        "SELECT id FROM Lockers WHERE department = ? AND floor = ? ORDER BY id",
        [$dept, $floor]
    );
    $i = 1;
    while ($stmtSlot && ($r = db_fetch($stmtSlot))) {
        if ((int) $r['id'] === $resolvedLockerId) {
            $slot = $i;
            break;
        }
        $i++;
    }
}

$displayLabel = $slot > 0 ? (string) $slot : ($resolvedLockerId ? (string) $resolvedLockerId : '—');

$profile   = fetch_user_profile($conn, $userId);
$fullName  = trim(($profile['firstName'] ?? '') . ' ' . ($profile['lastName'] ?? ''));
$studentId = $profile['studentId'] ?? 'Unknown';
$course    = $profile['course'] ?? '—';
$contact   = $profile['contact'] ?? '';
$email     = $profile['email'] ?? '';
$canReserve = $course !== '' && $course !== '—' && ($contact !== '' || $email !== '')
    && $resolvedLockerId && locker_is_available_for_reserve($conn, $resolvedLockerId);

if (!$canReserve && $resolvedLockerId && !locker_is_available_for_reserve($conn, $resolvedLockerId)) {
    popup_add('warning', 'That locker is no longer available. Go back and choose another.');
} elseif (!$canReserve && ($course === '' || $course === '—' || ($contact === '' && $email === ''))) {
    popup_add('warning', 'Complete your profile (course, contact, and email) in Settings before reserving a locker.');
}

$pageTitle  = 'Reserve Locker — SecureLocker Inc.';
$activePage = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main page-main--center">
  <div class="content-box content-box--left" style="max-width:560px;">
    <div class="page-eyebrow" style="margin-bottom:16px;">📝 &nbsp;Reservation</div>
    <h2>Locker Reservation Form</h2>

    <?php if (!$canReserve && $resolvedLockerId): ?>
      <p style="font-size:14px;color:rgba(255,255,255,0.55);margin-bottom:16px;">
        <a href="lockers.php?dept=<?= urlencode($dept) ?>&floor=<?= $floor ?>" class="btn-ghost btn-sm">← Back to locker grid</a>
      </p>
    <?php endif; ?>

    <div class="detail-section">
      <h3>Student Information</h3>
      <div class="detail-row"><span>Full Name</span><span><?= htmlspecialchars($fullName) ?></span></div>
      <div class="detail-row"><span>Student ID</span><span><?= htmlspecialchars($studentId) ?></span></div>
      <div class="detail-row"><span>Course</span><span><?= htmlspecialchars($course) ?></span></div>
      <div class="detail-row"><span>Contact</span><span><?= htmlspecialchars($contact ?: '—') ?></span></div>
      <div class="detail-row"><span>Email</span><span><?= htmlspecialchars($email ?: '—') ?></span></div>
      <div class="detail-row"><span>Locker</span><span><?= htmlspecialchars($dept) ?> · Floor <?= htmlspecialchars((string) $floor) ?> · L<?= htmlspecialchars($displayLabel) ?></span></div>
    </div>

    <form method="POST" action="reserve.php" style="margin-top:24px;">
      <input type="hidden" name="dept" value="<?= htmlspecialchars($dept) ?>">
      <input type="hidden" name="floor" value="<?= htmlspecialchars((string) $floor) ?>">
      <input type="hidden" name="slot" value="<?= htmlspecialchars((string) $slot) ?>">
      <input type="hidden" name="locker_id" value="<?= htmlspecialchars((string) ($resolvedLockerId ?? '')) ?>">

      <div class="form-group">
        <label>Duration of Use</label>
        <select name="duration" required <?= $canReserve ? '' : 'disabled' ?>>
          <option value="Half Semester">Half Semester (2½ months)</option>
          <option value="Full Semester">Full Semester (5 months)</option>
          <option value="Full Year">Full Year (10 months)</option>
        </select>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary" <?= $canReserve ? '' : 'disabled' ?>>Submit Reservation</button>
        <button type="button" class="btn-ghost" onclick="window.history.back()">← Back</button>
      </div>
    </form>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
