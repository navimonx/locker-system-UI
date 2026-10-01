<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/dept_bg.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$dept = $_GET['dept'] ?? null;
$bgImage = dept_background($dept ?? '');

$pageTitle  = 'Floor Selection — SecureLocker Inc.';
$activePage = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main page-main--center">

  <?php if (!$dept): ?>
    <div class="content-box">
      <h2>No Department Selected</h2>
      <p>Please select a college first.</p>
      <a href="rentals.php" class="btn-ghost">← Back to Colleges</a>
    </div>
  <?php else: ?>
    <div class="page-eyebrow">🏢 &nbsp;<?= htmlspecialchars($dept) ?> — Floors</div>
    <div class="content-box">
      <h2>Select a Floor</h2>
      <p>Choose a floor in <strong style="color:var(--white)"><?= htmlspecialchars($dept) ?></strong> to view locker availability.</p>

      <div class="tile-grid">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <a href="lockers.php?dept=<?= urlencode($dept) ?>&floor=<?= $i ?>">
            Floor <?= $i ?>
            <span class="tile-sub">Level <?= $i ?></span>
          </a>
        <?php endfor; ?>
      </div>

      <div style="margin-top:28px;">
        <a href="rentals.php" class="btn-ghost">← Back to Colleges</a>
      </div>
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
