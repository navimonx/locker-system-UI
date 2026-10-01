<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/user_profile.php';

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

sync_expired_reservations($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = $_POST['reservation_id'] ?? null;
    $action        = $_POST['action'] ?? null;

    if ($reservationId && $action) {
        if ($action === 'approve') {
            db_query($conn, "UPDATE Reservations SET status='approved' WHERE id=?", [$reservationId]);
            sync_locker_for_reservation($conn, (int) $reservationId);
            if (reservation_has_ends_at($conn)) {
                $stmtDur = db_query($conn, "SELECT duration, reserved_at FROM Reservations WHERE id=?", [$reservationId]);
                if ($stmtDur && ($durRow = db_fetch($stmtDur))) {
                    $ends = compute_reservation_ends_at($durRow['duration'] ?? 'Half Semester', $durRow['reserved_at'] ?? null);
                    db_query($conn, "UPDATE Reservations SET ends_at=? WHERE id=? AND ends_at IS NULL", [$ends, $reservationId]);
                }
            }
        } elseif ($action === 'reject') {
            db_query($conn, "UPDATE Reservations SET status='rejected', released_at=NOW() WHERE id=?", [$reservationId]);
            sync_locker_for_reservation($conn, (int) $reservationId);
        } elseif ($action === 'release') {
            db_query($conn, "UPDATE Reservations SET status='released', released_at=NOW() WHERE id=?", [$reservationId]);
            sync_locker_for_reservation($conn, (int) $reservationId);
        } elseif ($action === 'delete_history') {
            $result = delete_reservation_history($conn, (int) $reservationId);
            $qs = $result['ok'] ? 'deleted=1' : 'err=' . urlencode($result['message']);
            header('Location: adminrentals.php?filter=history&' . $qs);
            exit();
        }
        header("Location: adminrentals.php");
        exit();
    }
}

$reservationId = $_GET['id'] ?? null;
if (!$reservationId) die("No reservation selected.");

$endsSelect = reservation_has_ends_at($conn) ? ', r.ends_at, r.released_at' : '';

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
    [$reservationId]
);

if ($stmt === false) die('Query failed: ' . htmlspecialchars(db_last_error_message()));
$row = db_fetch($stmt);
if (!$row) die("Reservation not found.");

$fullName = trim(($row['firstName'] ?? '') . ' ' . ($row['middleName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
$status   = strtolower($row['status'] ?? 'pending');

$pageTitle   = 'Reservation Details — SecureLocker Admin';
$adminActive = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main page-main--center">
  <div class="content-box content-box--left" style="max-width:600px;">
    <div class="page-eyebrow" style="margin-bottom:16px;">📋 &nbsp;Admin View</div>
    <h2 style="text-align:center;margin-bottom:24px;">Reservation Details</h2>

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
      <div class="detail-row"><span>Locker</span><span>L<?= htmlspecialchars($row['locker_id']) ?></span></div>
      <div class="detail-row"><span>Status</span><span class="<?= reservation_status_class($status) ?>"><?= htmlspecialchars(reservation_status_label($status)) ?></span></div>
      <div class="detail-row"><span>Duration</span><span><?= htmlspecialchars($row['duration']) ?></span></div>
      <div class="detail-row"><span>Reserved At</span><span><?= format_reservation_datetime($row['reserved_at']) ?></span></div>
      <?php if (reservation_has_ends_at($conn)): ?>
        <div class="detail-row"><span>Ends On</span><span><?= format_reservation_date($row['ends_at'] ?? null) ?></span></div>
        <?php if (!empty($row['released_at'])): ?>
          <div class="detail-row"><span>Closed At</span><span><?= format_reservation_datetime($row['released_at']) ?></span></div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="form-actions">
      <?php if ($status === 'pending'): ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="btn-success">Approve</button>
        </form>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
          <input type="hidden" name="action" value="reject">
          <button type="submit" class="btn-danger">Reject</button>
        </form>
      <?php elseif ($status === 'approved'): ?>
        <form method="POST" style="display:inline;" data-confirm="Release this locker? The reservation will be kept in history.">
          <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
          <input type="hidden" name="action" value="release">
          <button type="submit" class="btn-ghost">Release Locker</button>
        </form>
      <?php elseif (is_reservation_history_status($status)): ?>
        <form method="POST" style="display:inline;" data-confirm="Permanently delete this reservation from history? This cannot be undone.">
          <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
          <input type="hidden" name="action" value="delete_history">
          <button type="submit" class="btn-danger">Delete from History</button>
        </form>
      <?php endif; ?>
      <a href="adminrentals.php" class="btn-ghost">← All Rentals</a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
