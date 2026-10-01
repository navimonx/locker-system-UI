<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/dept_bg.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId   = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'student';

$dept  = $_GET['dept']  ?? 'CABA';
$floor = (int) ($_GET['floor'] ?? 1);
$bgImage = dept_background($dept);

sync_expired_reservations($conn);
sync_locker_availability($conn, null, $dept, $floor);

$hasReservation = false;
$reservationStatus = null;

if (strtolower($userRole) === 'student') {
    $stmtCheck = db_query($conn,
        "SELECT status FROM Reservations WHERE user_id=? AND status IN ('pending','approved') LIMIT 1",
        [$userId]
    );
    if ($stmtCheck && ($rowCheck = db_fetch($stmtCheck))) {
        $hasReservation = true;
        $reservationStatus = strtolower($rowCheck['status']);
    }
}

if ($hasReservation && strtolower($userRole) === 'student') {
    if ($reservationStatus === 'pending') {
        popup_add('warning', 'You already have a pending reservation.');
    } elseif ($reservationStatus === 'approved') {
        popup_add('warning', 'You already have an approved locker.');
    }
}

$expiryAlerts = [];
if (strtolower($userRole) === 'student') {
    $expiryAlerts = fetch_expiry_alerts($conn, $userId, false);
}
queue_expiry_alert_popups($expiryAlerts, false);

$sqlLockers  = "SELECT id, status FROM Lockers WHERE department=? AND floor=? ORDER BY id";
$stmtLockers = db_query($conn, $sqlLockers, [$dept, $floor]);

$pageTitle  = 'Locker Grid — SecureLocker Inc.';
$activePage = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main page-main--center">
  <div class="page-eyebrow">🔐 &nbsp;<?= htmlspecialchars($dept) ?> · Floor <?= htmlspecialchars((string) $floor) ?></div>

  <div class="content-box content-box--locker-grid">
    <h2>Locker Grid</h2>
    <p style="font-size:14px;color:rgba(255,255,255,0.55);margin-bottom:16px;">Click a green locker to reserve. Orange is pending; red is occupied.</p>

    <div class="legend">
      <div class="legend-item"><div class="legend-dot available"></div> Available</div>
      <div class="legend-item"><div class="legend-dot pending"></div> Pending</div>
      <div class="legend-item"><div class="legend-dot occupied"></div> Occupied</div>
    </div>

    <table class="locker-table">
      <?php
      $count = 1;
      if ($stmtLockers) {
          while ($row = db_fetch($stmtLockers)) {
              if ($count % 5 === 1) {
                  echo '<tr>';
              }

              $statusClass = strtolower(trim($row['status'] ?? 'available'));
              $canReserve  = $statusClass === 'available'
                  && strtolower($userRole) === 'student'
                  && !$hasReservation;

              echo '<td>';
              if ($canReserve) {
                  $href = 'reserve.php?dept=' . urlencode($dept)
                      . '&floor=' . urlencode((string) $floor)
                      . '&slot=' . urlencode((string) $count);
                  echo '<a href="' . htmlspecialchars($href) . '" class="locker-btn available">L' . $count . '</a>';
              } else {
                  echo '<button type="button" class="locker-btn ' . htmlspecialchars($statusClass) . '" disabled>L' . $count . '</button>';
              }
              echo '</td>';

              if ($count % 5 === 0) {
                  echo '</tr>';
              }
              $count++;
          }
          if (($count - 1) % 5 !== 0) {
              echo '</tr>';
          }
      }
      ?>
    </table>

    <div style="margin-top:28px;">
      <a href="floors.php?dept=<?= urlencode($dept) ?>" class="btn-ghost">← Back to Floors</a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
