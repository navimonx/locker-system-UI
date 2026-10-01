<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/admin_search.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!$isAdmin && !$isRegistrar) {
    header("Location: login.php");
    exit();
}

$filter = $_GET['filter'] ?? 'active';
if (!in_array($filter, ['all', 'active', 'history'], true)) {
    $filter = 'active';
}

sync_expired_reservations($conn);

$msg = !empty($_GET['deleted']) ? 'Reservation removed from history.' : '';
$err = isset($_GET['err']) ? (string) $_GET['err'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
    if (!$isAdmin) {
        header("Location: login.php");
        exit();
    }
    $result = delete_reservation_history($conn, (int) $_POST['delete_reservation']);
    if ($result['ok']) {
        $msg = $result['message'];
    } else {
        $err = $result['message'];
    }
}

$searchParams = admin_search_query_params();
$reservations = fetch_reservations_filtered($conn, array_merge($searchParams, ['filter' => $filter]));
$expiryAlerts = fetch_expiry_alerts($conn, null, true);
queue_expiry_alert_popups($expiryAlerts, true);
popup_flash($msg ?: null, $err ?: null);
$searchSuffix = admin_search_url_suffix($searchParams);

$departments = [
  'CABA' => ['icon' => '🏢', 'label' => 'CABA'],
  'CEIT' => ['icon' => '💻', 'label' => 'CEIT'],
  'COED' => ['icon' => '📚', 'label' => 'COED'],
  'CPAG' => ['icon' => '⚖️', 'label' => 'CPAG'],
  'NB'   => ['icon' => '🔬', 'label' => 'NB'],
  'CAS'  => ['icon' => '🎭', 'label' => 'CAS'],
];

$hasEndsAt = reservation_has_ends_at($conn);

function format_user_name_rental(array $row): string {
    return trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')) ?: '—';
}

$pageTitle   = 'Admin Rentals — SecureLocker Inc.';
$adminActive = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main">
  <div class="page-header">
    <div class="page-eyebrow">🏢 &nbsp;<?= $isAdmin ? 'Admin Rentals' : 'Registrar Rentals' ?></div>
    <h1><?= $isAdmin ? 'All Reservations' : 'Reservation Review' ?></h1>
    <p><?= $isAdmin ? 'Active rentals here; ended or removed ones appear under <strong style="color:#fff;">History</strong>.' : 'Review pending and active reservations for student verification and validation.' ?></p>
  </div>

  <div class="content-box content-box--wide content-box--left">
    <div class="rental-filter-bar">
      <a href="adminrentals.php?filter=active<?= $searchSuffix ?>" class="btn-ghost btn-sm <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
      <a href="adminrentals.php?filter=history<?= $searchSuffix ?>" class="btn-ghost btn-sm <?= $filter === 'history' ? 'active' : '' ?>">History</a>
      <a href="adminrentals.php?filter=all<?= $searchSuffix ?>" class="btn-ghost btn-sm <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    </div>
    <?php if ($filter === 'history'): ?>
      <p style="font-size:13px;color:rgba(255,255,255,0.50);margin:12px 0 0;">
        Released, rejected, cancelled, expired, and removed reservations are listed here automatically.
        <?= $isAdmin ? 'Use <strong style="color:rgba(255,255,255,0.75);">Delete</strong> to permanently remove a record from history.' : '' ?>
      </p>
    <?php endif; ?>

    <?php render_reservation_search_form('adminrentals.php', $searchParams, $filter); ?>

    <?php if (empty($reservations)): ?>
      <p style="color:rgba(255,255,255,0.55);margin-top:16px;">
        <?= $filter === 'history' ? 'No reservation history yet.' : ($filter === 'active' ? 'No active reservations.' : 'No reservations found.') ?>
      </p>
    <?php else: ?>
      <div class="data-table-wrap" style="margin-top:16px;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Student ID</th>
              <th>Name</th>
              <th class="col-contact">Contact</th>
              <th class="col-email">Email</th>
              <th class="col-locker">Locker</th>
              <th class="col-dept">Dept / Floor</th>
              <th class="col-duration">Duration</th>
              <th class="col-datetime">Reserved</th>
              <?php if ($hasEndsAt): ?><th class="col-datetime">Ends</th><?php endif; ?>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $r):
              $status = strtolower($r['status'] ?? 'pending');
              $statusClass = reservation_status_class($status);
            ?>
            <tr class="<?= is_reservation_history_status($status) ? 'row-history' : '' ?>">
              <td><strong><?= htmlspecialchars($r['studentId']) ?></strong></td>
              <td><?= htmlspecialchars(format_user_name_rental($r)) ?></td>
              <td class="col-contact"><?= htmlspecialchars($r['contact'] ?? '—') ?></td>
              <td class="col-email"><?= htmlspecialchars($r['email'] ?? '—') ?></td>
              <td class="col-locker">L<?= htmlspecialchars($r['locker_id']) ?></td>
              <td class="col-dept"><?= htmlspecialchars($r['department']) ?> · F<?= htmlspecialchars($r['floor']) ?></td>
              <td class="col-duration"><?= htmlspecialchars($r['duration'] ?? '—') ?></td>
              <td class="col-datetime"><?= format_reservation_datetime($r['reserved_at']) ?></td>
              <?php if ($hasEndsAt): ?>
                <td class="col-datetime"><?= format_reservation_date($r['ends_at'] ?? null) ?></td>
              <?php endif; ?>
              <td class="<?= $statusClass ?>"><?= htmlspecialchars(reservation_status_label($status)) ?></td>
              <td class="table-actions">
                <a href="adminlocker_view.php?id=<?= (int) $r['reservation_id'] ?>" class="btn-ghost btn-sm">View</a>
                <?php if ($isAdmin && is_reservation_history_status($status)): ?>
                  <form method="POST" action="adminrentals.php?filter=<?= urlencode($filter) ?><?= $searchSuffix ?>" style="display:inline;"
                        data-confirm="Permanently delete this reservation from history? This cannot be undone.">
                    <input type="hidden" name="delete_reservation" value="<?= (int) $r['reservation_id'] ?>">
                    <button type="submit" class="btn-danger btn-sm">Delete</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($isAdmin): ?>
  <div class="page-header" style="margin-top:48px;">
    <h2 style="font-size:22px;">Manage Lockers by Department</h2>
    <p style="font-size:14px;color:rgba(255,255,255,0.55);">Open a college to approve, release, or view lockers on the floor grid.</p>
  </div>

  <div class="dept-grid">
    <?php foreach ($departments as $key => $d): ?>
      <a href="adminfloors.php?dept=<?= urlencode($key) ?>" class="dept-card">
        <div class="dept-icon"><?= $d['icon'] ?></div>
        <div class="dept-abbr"><?= htmlspecialchars($d['label']) ?></div>
        <div class="dept-arrow">Manage Floors →</div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
