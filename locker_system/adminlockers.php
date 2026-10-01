<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/dept_bg.php';
require_once __DIR__ . '/includes/reservations.php';

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

$dept  = $_GET['dept'] ?? 'CABA';
$floor = $_GET['floor'] ?? 1;
$bgImage = dept_background($dept);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['locker_id'])) {
    $lockerId = $_POST['locker_id'];
    $action   = $_POST['action'] ?? '';

    if ($action === 'approve') {
        db_query($conn, "UPDATE Lockers SET status='occupied' WHERE id=?", [$lockerId]);
        db_query($conn, "UPDATE Reservations SET status='approved' WHERE locker_id=? AND status='pending'", [$lockerId]);
        if (reservation_has_ends_at($conn)) {
            $stmtDur = db_query($conn,
                "SELECT id, duration, reserved_at FROM Reservations WHERE locker_id=? AND status='approved' ORDER BY reserved_at DESC LIMIT 1",
                [$lockerId]
            );
            if ($stmtDur && ($durRow = db_fetch($stmtDur))) {
                $ends = compute_reservation_ends_at($durRow['duration'] ?? 'Half Semester', $durRow['reserved_at'] ?? null);
                db_query($conn, "UPDATE Reservations SET ends_at=? WHERE id=? AND ends_at IS NULL", [$ends, $durRow['id']]);
            }
        }
    } elseif ($action === 'release') {
        db_query($conn, "UPDATE Lockers SET status='available', owner_id=NULL WHERE id=?", [$lockerId]);
        db_query($conn,
            "UPDATE Reservations SET status='released', released_at=NOW()
             WHERE locker_id=? AND status IN ('pending','approved')",
            [$lockerId]
        );
    }

    sync_locker_availability($conn, (int) $lockerId, $dept, (int) $floor);

    header('Location: adminlockers.php?dept=' . urlencode((string) $dept) . '&floor=' . (int) $floor);
    exit();
}

sync_locker_availability($conn, null, $dept, (int) $floor);

$sql  = "SELECT l.*, u.studentId, r.id AS reservation_id, r.status AS res_status
         FROM Lockers l
         LEFT JOIN Users u ON l.owner_id = u.id
         LEFT JOIN Reservations r ON l.id = r.locker_id AND r.status IN ('pending','approved')
         WHERE l.department=? AND l.floor=?
         ORDER BY l.id";
$stmt = db_query($conn, $sql, [$dept, $floor]);

$pageTitle   = 'Admin Lockers — SecureLocker Inc.';
$adminActive = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main page-main--center">
  <div class="page-eyebrow">🔐 &nbsp;<?= htmlspecialchars($dept) ?> · Floor <?= htmlspecialchars($floor) ?></div>

  <div class="content-box content-box--locker-grid">
    <h2>Locker Management</h2>

    <div class="legend">
      <div class="legend-item"><div class="legend-dot available"></div> Available</div>
      <div class="legend-item"><div class="legend-dot pending"></div> Pending</div>
      <div class="legend-item"><div class="legend-dot occupied"></div> Occupied</div>
    </div>

    <table class="locker-table">
      <?php
      $count = 1;
      while ($row = db_fetch($stmt)) {
          if ($count % 5 == 1) echo "<tr>";

          $statusClass   = strtolower(trim($row['status'] ?? 'available'));
          $studentId     = htmlspecialchars($row['studentId'] ?? 'N/A');
          $reservationId = $row['reservation_id'] ?? null;

          echo "<td>";
          if ($statusClass === 'pending') {
              echo '<form method="POST" data-confirm="Approve locker for ' . $studentId . '?">';
              echo '<input type="hidden" name="locker_id" value="' . $row['id'] . '">';
              echo '<input type="hidden" name="action" value="approve">';
              echo '<button class="admin-locker-btn pending" type="submit">L' . $count . '<br><small>' . $studentId . '</small></button>';
              echo '</form>';
              if ($reservationId) {
                  echo '<a class="details-link" href="adminlocker_view.php?id=' . $reservationId . '">View Details</a>';
              }
          } elseif ($statusClass === 'occupied') {
              echo '<form method="POST" data-confirm="Release locker for ' . $studentId . '?">';
              echo '<input type="hidden" name="locker_id" value="' . $row['id'] . '">';
              echo '<input type="hidden" name="action" value="release">';
              echo '<button class="admin-locker-btn occupied" type="submit">L' . $count . '<br><small>' . $studentId . '</small></button>';
              echo '</form>';
              if ($reservationId) {
                  echo '<a class="details-link" href="adminlocker_view.php?id=' . $reservationId . '">View Details</a>';
              }
          } else {
              echo '<button class="admin-locker-btn available" disabled>L' . $count . '<br><small>Available</small></button>';
          }
          echo "</td>";

          if ($count % 5 == 0) echo "</tr>";
          $count++;
      }
      if (($count - 1) % 5 !== 0) echo "</tr>";
      ?>
    </table>

    <div style="margin-top:28px;">
      <a href="adminfloors.php?dept=<?= urlencode($dept) ?>" class="btn-ghost">← Back to Floors</a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
