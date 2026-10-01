<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/workflow.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!$isRegistrar) {
    if ($isAdmin) {
        header('Location: adminpage.php');
    } else {
        header('Location: login.php');
    }
    exit();
}

sync_expired_reservations($conn);
$stats = fetch_dashboard_stats($conn);
$notifications = fetch_staff_notifications($conn, 5);

$pageTitle = 'Registrar Dashboard — SecureLocker Inc.';
$adminActive = 'home';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main page-main--center">
  <div class="page-header">
    <div class="page-eyebrow">🧾 &nbsp;Registrar Dashboard</div>
    <h1>Welcome, Registrar</h1>
    <p>Review student applications, verify payments, assign lockers, and monitor reservation activity.</p>
  </div>

  <div class="content-box content-box--wide">
    <div class="user-pill" style="margin-bottom:24px;">
      <span class="dot"></span>
      Logged in as <strong><?= htmlspecialchars($displayName ?? 'Registrar') ?></strong>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><strong><?= (int) $stats['pendingApplications'] ?></strong><span>Pending Applications</span></div>
      <div class="stat-card"><strong><?= (int) $stats['approvedAwaiting'] ?></strong><span>Awaiting Payment</span></div>
      <div class="stat-card"><strong><?= (int) $stats['activeAssignments'] ?></strong><span>Active Assignments</span></div>
      <div class="stat-card"><strong><?= (int) $stats['availableLockers'] ?></strong><span>Available Lockers</span></div>
    </div>

    <div class="cta-row" style="margin-top:28px;">
      <a href="adminrentals.php" class="btn-primary">Review Reservations →</a>
      <a href="admin_records.php" class="btn-ghost">View Records</a>
    </div>
  </div>

  <?php if (!empty($notifications)): ?>
    <div class="content-box content-box--wide" style="margin-top:24px;text-align:left;">
      <h2 style="font-size:20px;margin-bottom:16px;">Recent Staff Notifications</h2>
      <?php foreach ($notifications as $notification): ?>
        <p style="margin:10px 0;color:rgba(255,255,255,.75);">
          <?= htmlspecialchars($notification['message'] ?? '') ?>
        </p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
