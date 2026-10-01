<?php
/** @var string $activePage home|rentals|myrentals|about|settings|login */
$activePage = $activePage ?? '';
?>
<header class="navbar">
  <a href="index.php" class="navbar-brand">
    <div class="brand-icon">🔒</div>
    <span>SecureLocker</span>
  </a>
  <nav>
    <ul class="nav-links">
      <li><a href="index.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">Home</a></li>
      <li><a href="rentals.php" class="<?= $activePage === 'rentals' ? 'active' : '' ?>">Rentals</a></li>
      <li><a href="myrentals.php" class="<?= $activePage === 'myrentals' ? 'active' : '' ?>">My Locker</a></li>
      <li><a href="about.php" class="<?= $activePage === 'about' ? 'active' : '' ?>">About</a></li>
      <?php if ($loggedIn): ?>
        <li><a href="profile.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">Settings</a></li>
        <li>
          <span class="nav-badge">
            <span class="dot"></span>
            <?= htmlspecialchars($displayName ?? '') ?>
          </span>
        </li>
        <li><a href="logout.php" class="btn-logout">Logout</a></li>
      <?php else: ?>
        <li><a href="signup.php">Sign Up</a></li>
        <li><a href="login.php" class="btn-nav-login <?= $activePage === 'login' ? 'active' : '' ?>">Login →</a></li>
      <?php endif; ?>
    </ul>
  </nav>
</header>
