<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

sync_expired_reservations($conn);
$expiryAlerts = fetch_expiry_alerts($conn, null, true);
queue_expiry_alert_popups($expiryAlerts, true);

$pageTitle   = 'Admin Dashboard — SecureLocker Inc.';
$adminActive = 'home';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main page-main--center">
  <div class="page-header">
    <div class="page-eyebrow">🛠️ &nbsp;Admin Dashboard</div>
    <h1>Welcome, Admin</h1>
    <p>Manage lockers, approve pending reservations, and release occupied lockers across all colleges.</p>
  </div>

  <div class="content-box">
    <?php if ($displayName): ?>
      <div class="user-pill" style="margin-bottom:24px;">
        <span class="dot"></span>
        Logged in as <strong><?= htmlspecialchars($displayName) ?></strong>
      </div>
    <?php endif; ?>
    <p style="margin-bottom:28px;">
      View <strong style="color:var(--white)">Records</strong> for reservations, students, and admins — or manage lockers under <strong style="color:var(--white)">Rentals</strong>.
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
      <a href="admin_records.php" class="btn-primary">View Records →</a>
      <a href="adminrentals.php" class="btn-ghost">Manage Rentals</a>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
