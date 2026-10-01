<?php
/** @var string $adminActive home|rentals|records|register */
$adminActive = $adminActive ?? '';
?>
<header class="navbar">
  <a href="adminpage.php" class="navbar-brand">
    <div class="brand-icon">🛠️</div>
    <span>SecureLocker Admin</span>
  </a>
  <nav>
    <ul class="nav-links">
      <li><a href="adminpage.php" class="<?= $adminActive === 'home' ? 'active' : '' ?>">Home</a></li>
      <li><a href="adminrentals.php" class="<?= $adminActive === 'rentals' ? 'active' : '' ?>">Rentals</a></li>
      <li><a href="admin_records.php" class="<?= $adminActive === 'records' ? 'active' : '' ?>">Records</a></li>
      <li><a href="register.php" class="<?= $adminActive === 'register' ? 'active' : '' ?>">Create Admin</a></li>
      <li><a href="profile.php" class="<?= $adminActive === 'settings' ? 'active' : '' ?>">Settings</a></li>
      <?php if (!empty($displayName)): ?>
        <li>
          <span class="nav-badge">
            <span class="dot"></span>
            <?= htmlspecialchars($displayName) ?>
          </span>
        </li>
      <?php endif; ?>
      <li><a href="logout.php" class="btn-logout">Logout</a></li>
    </ul>
  </nav>
</header>
