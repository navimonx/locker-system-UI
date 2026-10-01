<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$departments = [
  'CABA' => ['icon' => '🏢', 'label' => 'CABA'],
  'CEIT' => ['icon' => '💻', 'label' => 'CEIT'],
  'COED' => ['icon' => '📚', 'label' => 'COED'],
  'CPAG' => ['icon' => '⚖️', 'label' => 'CPAG'],
  'NB'   => ['icon' => '🔬', 'label' => 'NB'],
  'CAS'  => ['icon' => '🎭', 'label' => 'CAS'],
];

$pageTitle  = 'Rentals — SecureLocker Inc.';
$activePage = 'rentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main">
  <div class="page-header">
    <div class="page-eyebrow">🔍 &nbsp;Browse Lockers</div>
    <h1>Choose Your College</h1>
    <p>Select your college or building to view floor and locker availability.</p>
  </div>

  <?php if ($displayName): ?>
    <div class="user-pill">
      <span class="dot"></span>
      Logged in as <strong><?= htmlspecialchars($displayName) ?></strong>
      &nbsp;·&nbsp; <?= htmlspecialchars(ucfirst($userRole ?? 'Student')) ?>
    </div>
  <?php endif; ?>

  <div class="dept-grid">
    <?php foreach ($departments as $key => $d): ?>
      <a href="floors.php?dept=<?= urlencode($key) ?>" class="dept-card">
        <div class="dept-icon"><?= $d['icon'] ?></div>
        <div class="dept-abbr"><?= htmlspecialchars($d['label']) ?></div>
        <div class="dept-arrow">View Floors →</div>
      </a>
    <?php endforeach; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
