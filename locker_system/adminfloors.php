<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/dept_bg.php';

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

$dept = $_GET['dept'] ?? null;
$bgImage = dept_background($dept ?? '');

$pageTitle   = 'Admin Floors — SecureLocker Inc.';
$adminActive = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main page-main--center">

  <?php if (!$dept): ?>
    <div class="content-box">
      <h2>No Department Selected</h2>
      <p>Please select a college first.</p>
      <a href="adminrentals.php" class="btn-ghost">← Back to Colleges</a>
    </div>
  <?php else: ?>
    <div class="page-eyebrow">🏢 &nbsp;<?= htmlspecialchars($dept) ?> — Floors</div>
    <div class="content-box">
      <h2>Select a Floor</h2>
      <p>Manage lockers on each floor in <strong style="color:var(--white)"><?= htmlspecialchars($dept) ?></strong>.</p>

      <div class="tile-grid">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <a href="adminlockers.php?dept=<?= urlencode($dept) ?>&floor=<?= $i ?>">
            Floor <?= $i ?>
            <span class="tile-sub">Level <?= $i ?></span>
          </a>
        <?php endfor; ?>
      </div>

      <div style="margin-top:28px;">
        <a href="adminrentals.php" class="btn-ghost">← Back to Colleges</a>
      </div>
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
