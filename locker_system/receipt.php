<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/user_profile.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$reservationId = $_GET['reservation_id'] ?? null;
if (!$reservationId) {
    die("No reservation selected.");
}

$endsSelect = reservation_has_ends_at($conn) ? ', r.ends_at' : '';

$profileCols = users_profile_columns($conn);
$userCourse  = $profileCols['course'] ? ', u.course AS user_course' : '';
$userContact = $profileCols['contact'] ? ', u.contact AS user_contact' : '';
$userEmail   = $profileCols['email'] ? ', u.email AS user_email' : '';

$stmt = db_query($conn,
    "SELECT r.id AS reservation_id, r.status, r.duration, r.reserved_at $endsSelect,
            r.course, r.yearLevel, r.contact,
            u.firstName, u.middleName, u.lastName, u.studentId
            $userCourse $userContact $userEmail,
            l.id AS locker_id, l.department, l.floor
     FROM Reservations r
     INNER JOIN Users u ON r.user_id = u.id
     INNER JOIN Lockers l ON r.locker_id = l.id
     WHERE r.id = ?",
    [$reservationId]);

if ($stmt === false) die('Query failed: ' . htmlspecialchars(db_last_error_message()));
$row = db_fetch($stmt);
if (!$row) die("Reservation not found.");

$fullName = trim(($row['firstName'] ?? '') . ' ' . ($row['middleName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
$reservedAt = $row['reserved_at'] instanceof DateTime
    ? $row['reserved_at']->format('Y-m-d H:i:s')
    : htmlspecialchars($row['reserved_at']);

$pageTitle  = 'Receipt — SecureLocker Inc.';
$activePage = 'myrentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main page-main--center">
  <div class="content-box content-box--left" style="max-width:600px;">
    <div class="page-eyebrow" style="margin-bottom:16px;">🧾 &nbsp;Receipt</div>
    <h2 style="text-align:center;margin-bottom:24px;">Reservation Receipt</h2>

    <div class="detail-section">
      <h3>Student Information</h3>
      <div class="detail-row"><span>Full Name</span><span><?= htmlspecialchars($fullName) ?></span></div>
      <div class="detail-row"><span>Student ID</span><span><?= htmlspecialchars($row['studentId']) ?></span></div>
      <div class="detail-row"><span>Course</span><span><?= htmlspecialchars($row['user_course'] ?? $row['course'] ?? '—') ?></span></div>
      <div class="detail-row"><span>Contact</span><span><?= htmlspecialchars($row['user_contact'] ?? $row['contact'] ?? '—') ?></span></div>
      <?php if (!empty($row['user_email'])): ?>
      <div class="detail-row"><span>Email</span><span><?= htmlspecialchars($row['user_email']) ?></span></div>
      <?php endif; ?>
    </div>

    <div class="detail-section">
      <h3>Locker Information</h3>
      <div class="detail-row"><span>Department</span><span><?= htmlspecialchars($row['department']) ?></span></div>
      <div class="detail-row"><span>Floor</span><span><?= htmlspecialchars($row['floor']) ?></span></div>
      <div class="detail-row"><span>Locker Number</span><span><?= htmlspecialchars($row['locker_id']) ?></span></div>
      <div class="detail-row"><span>Duration</span><span><?= htmlspecialchars($row['duration']) ?></span></div>
      <?php if (reservation_has_ends_at($conn) && !empty($row['ends_at'])): ?>
        <div class="detail-row"><span>Ends On</span><span><?= format_reservation_date($row['ends_at']) ?></span></div>
      <?php endif; ?>
      <div class="detail-row"><span>Status</span><span><?= htmlspecialchars(reservation_status_label($row['status'] ?? '')) ?></span></div>
    </div>

    <div class="detail-section">
      <h3>Transaction</h3>
      <div class="detail-row"><span>Reservation ID</span><span><?= htmlspecialchars($row['reservation_id']) ?></span></div>
      <div class="detail-row"><span>Reserved At</span><span><?= $reservedAt ?></span></div>
    </div>

    <div class="form-actions no-print">
      <button onclick="window.location.href='myrentals.php'" class="btn-ghost">← My Locker</button>
      <button onclick="window.print()" class="btn-primary">🖨 Print Receipt</button>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
