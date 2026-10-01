<?php
session_start();
include(__DIR__ . "/db.php");

$name     = $_SESSION['name']      ?? null;
$studentId= $_SESSION['studentId'] ?? null;
$userRole = $_SESSION['role']      ?? null;
$loggedIn = isset($_SESSION['user_id']) || isset($_SESSION['studentId']);

// Build display name: prefer $name, fall back to studentId
$displayName = $name ?: $studentId;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SecureLocker Inc. — Smart Campus Locker Reservations</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* ─── Reset & base ───────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:      #0a1f44;
      --blue:      #1a56db;
      --blue-lt:   #3b82f6;
      --accent:    #f59e0b;
      --white:     #ffffff;
      --off-white: #f1f5f9;
      --text:      #1e293b;
      --muted:     #64748b;
      --glass:     rgba(255,255,255,0.10);
      --glass-b:   rgba(255,255,255,0.18);
      --radius:    14px;
      --shadow:    0 8px 32px rgba(10,31,68,0.18);
      --transition: 0.3s cubic-bezier(.4,0,.2,1);
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--navy);
      color: var(--white);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ─── Animated gradient background ───────────────────────── */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 20% 30%, rgba(26,86,219,0.35) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at 80% 70%, rgba(245,158,11,0.12) 0%, transparent 55%),
        url('images/plv.jpg') center/cover no-repeat;
      z-index: -2;
    }
    body::after {
      content: '';
      position: fixed;
      inset: 0;
      background: rgba(10,31,68,0.72);
      z-index: -1;
    }

    /* ─── Navbar ──────────────────────────────────────────────── */
    .navbar {
      position: sticky;
      top: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 48px;
      height: 68px;
      background: rgba(10,31,68,0.82);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid rgba(255,255,255,0.08);
      animation: slideDown .55s ease both;
    }
    @keyframes slideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to   { transform: translateY(0);     opacity: 1; }
    }

    .navbar-brand {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }
    .navbar-brand .brand-icon {
      width: 36px; height: 36px;
      background: var(--blue);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px;
    }
    .navbar-brand span {
      font-size: 18px;
      font-weight: 700;
      color: var(--white);
      letter-spacing: -.3px;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 4px;
      list-style: none;
    }
    .nav-links li a {
      color: rgba(255,255,255,0.80);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      padding: 7px 14px;
      border-radius: 8px;
      transition: background var(--transition), color var(--transition);
    }
    .nav-links li a:hover,
    .nav-links li a.active {
      background: rgba(255,255,255,0.10);
      color: var(--white);
    }

    .nav-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.10);
      border: 1px solid rgba(255,255,255,0.18);
      color: var(--white);
      font-size: 13px;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: 999px;
    }
    .nav-badge .dot {
      width: 7px; height: 7px;
      background: #22c55e;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50%      { opacity: .5; transform: scale(1.4); }
    }

    .btn-logout {
      background: rgba(220,38,38,0.18);
      border: 1px solid rgba(220,38,38,0.45);
      color: #fca5a5;
      font-size: 13px;
      font-weight: 600;
      padding: 6px 14px;
      border-radius: 8px;
      text-decoration: none;
      transition: background var(--transition);
    }
    .btn-logout:hover { background: rgba(220,38,38,0.35); color: #fff; }

    .btn-nav-login {
      background: var(--blue);
      color: var(--white);
      font-size: 13px;
      font-weight: 600;
      padding: 7px 18px;
      border-radius: 8px;
      text-decoration: none;
      transition: background var(--transition), transform var(--transition);
    }
    .btn-nav-login:hover { background: #1648c0; transform: translateY(-1px); }

    /* ─── Hero ────────────────────────────────────────────────── */
    .hero {
      min-height: calc(100vh - 68px);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 80px 24px 60px;
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(26,86,219,0.20);
      border: 1px solid rgba(59,130,246,0.40);
      color: #93c5fd;
      font-size: 13px;
      font-weight: 600;
      letter-spacing: .5px;
      text-transform: uppercase;
      padding: 6px 16px;
      border-radius: 999px;
      margin-bottom: 28px;
      animation: fadeUp .6s .1s ease both;
    }

    .hero h1 {
      font-size: clamp(38px, 6vw, 72px);
      font-weight: 800;
      line-height: 1.08;
      letter-spacing: -1.5px;
      max-width: 820px;
      animation: fadeUp .6s .2s ease both;
    }
    .hero h1 .highlight {
      background: linear-gradient(135deg, var(--blue-lt), var(--accent));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .hero-sub {
      margin-top: 22px;
      font-size: clamp(16px, 2vw, 19px);
      font-weight: 400;
      color: rgba(255,255,255,0.68);
      max-width: 600px;
      line-height: 1.65;
      animation: fadeUp .6s .3s ease both;
    }

    .hero-cta {
      margin-top: 40px;
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      justify-content: center;
      animation: fadeUp .6s .4s ease both;
    }

    .btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--blue);
      color: var(--white);
      font-size: 15px;
      font-weight: 600;
      padding: 14px 30px;
      border-radius: var(--radius);
      text-decoration: none;
      border: none;
      cursor: pointer;
      transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
      box-shadow: 0 4px 20px rgba(26,86,219,0.40);
    }
    .btn-primary:hover {
      background: #1648c0;
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(26,86,219,0.55);
    }

    .btn-ghost {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.20);
      color: var(--white);
      font-size: 15px;
      font-weight: 600;
      padding: 14px 30px;
      border-radius: var(--radius);
      text-decoration: none;
      cursor: pointer;
      transition: background var(--transition), transform var(--transition);
    }
    .btn-ghost:hover {
      background: rgba(255,255,255,0.14);
      transform: translateY(-2px);
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ─── Stats strip ─────────────────────────────────────────── */
    .stats-strip {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 0;
      margin: 0 auto;
      max-width: 760px;
      background: var(--glass-b);
      backdrop-filter: blur(14px);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: var(--radius);
      overflow: hidden;
      animation: fadeUp .6s .5s ease both;
    }
    .stat-item {
      flex: 1;
      min-width: 140px;
      padding: 22px 24px;
      text-align: center;
      border-right: 1px solid rgba(255,255,255,0.08);
      transition: background var(--transition);
    }
    .stat-item:last-child { border-right: none; }
    .stat-item:hover { background: rgba(255,255,255,0.06); }
    .stat-num {
      font-size: 30px;
      font-weight: 800;
      color: var(--white);
      line-height: 1;
    }
    .stat-num span { color: var(--accent); }
    .stat-label {
      font-size: 12px;
      color: rgba(255,255,255,0.55);
      margin-top: 5px;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    /* ─── Features section ────────────────────────────────────── */
    .section {
      padding: 90px 24px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .section-label {
      text-align: center;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--blue-lt);
      margin-bottom: 14px;
    }
    .section-title {
      text-align: center;
      font-size: clamp(26px, 4vw, 40px);
      font-weight: 800;
      letter-spacing: -.5px;
      margin-bottom: 12px;
    }
    .section-sub {
      text-align: center;
      font-size: 16px;
      color: rgba(255,255,255,0.60);
      max-width: 520px;
      margin: 0 auto 56px;
      line-height: 1.65;
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
      gap: 22px;
    }

    .feature-card {
      background: var(--glass);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: var(--radius);
      padding: 32px 28px;
      transition: transform var(--transition), background var(--transition), border-color var(--transition), box-shadow var(--transition);
      opacity: 0;
      transform: translateY(30px);
    }
    .feature-card.visible {
      animation: fadeUp .55s ease forwards;
    }
    .feature-card:hover {
      transform: translateY(-5px);
      background: rgba(255,255,255,0.13);
      border-color: rgba(59,130,246,0.35);
      box-shadow: 0 12px 40px rgba(26,86,219,0.22);
    }

    .feature-icon {
      width: 52px; height: 52px;
      border-radius: 12px;
      background: rgba(26,86,219,0.22);
      border: 1px solid rgba(59,130,246,0.30);
      display: flex; align-items: center; justify-content: center;
      font-size: 24px;
      margin-bottom: 20px;
    }
    .feature-card h3 {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--white);
    }
    .feature-card p {
      font-size: 14px;
      color: rgba(255,255,255,0.58);
      line-height: 1.65;
    }

    /* stagger delays */
    .feature-card:nth-child(1) { animation-delay: .05s; }
    .feature-card:nth-child(2) { animation-delay: .15s; }
    .feature-card:nth-child(3) { animation-delay: .25s; }
    .feature-card:nth-child(4) { animation-delay: .10s; }
    .feature-card:nth-child(5) { animation-delay: .20s; }
    .feature-card:nth-child(6) { animation-delay: .30s; }

    /* ─── CTA Banner ──────────────────────────────────────────── */
    .cta-banner {
      margin: 0 24px 90px;
      max-width: 1100px;
      margin-left: auto;
      margin-right: auto;
      background: linear-gradient(135deg, rgba(26,86,219,0.30), rgba(10,31,68,0.50));
      border: 1px solid rgba(59,130,246,0.25);
      border-radius: 20px;
      padding: 56px 48px;
      text-align: center;
      backdrop-filter: blur(10px);
    }
    .cta-banner h2 {
      font-size: clamp(24px, 3.5vw, 36px);
      font-weight: 800;
      letter-spacing: -.5px;
      margin-bottom: 14px;
    }
    .cta-banner p {
      font-size: 16px;
      color: rgba(255,255,255,0.65);
      max-width: 480px;
      margin: 0 auto 32px;
      line-height: 1.65;
    }

    /* ─── Footer ──────────────────────────────────────────────── */
    footer {
      background: rgba(10,31,68,0.90);
      border-top: 1px solid rgba(255,255,255,0.07);
      padding: 28px 48px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
    }
    footer .foot-brand {
      font-size: 15px;
      font-weight: 700;
      color: var(--white);
    }
    footer p {
      font-size: 13px;
      color: rgba(255,255,255,0.40);
    }
    footer .foot-links {
      display: flex; gap: 20px;
    }
    footer .foot-links a {
      font-size: 13px;
      color: rgba(255,255,255,0.45);
      text-decoration: none;
      transition: color var(--transition);
    }
    footer .foot-links a:hover { color: var(--white); }

    /* ─── Welcome toast ───────────────────────────────────────── */
    .welcome-toast {
      position: fixed;
      top: 80px;
      right: 24px;
      background: rgba(10,31,68,0.92);
      border: 1px solid rgba(34,197,94,0.40);
      border-radius: 12px;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      font-weight: 500;
      color: var(--white);
      z-index: 200;
      backdrop-filter: blur(12px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.30);
      animation: toastIn .4s ease both, toastOut .4s 4s ease forwards;
    }
    .welcome-toast .toast-icon {
      width: 32px; height: 32px;
      background: rgba(34,197,94,0.18);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px;
      flex-shrink: 0;
    }
    @keyframes toastIn  { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:translateX(0); } }
    @keyframes toastOut { from { opacity:1; transform:translateX(0); }    to { opacity:0; transform:translateX(30px); pointer-events:none; } }

    /* ─── Scroll-reveal utility ───────────────────────────────── */
    .reveal {
      opacity: 0;
      transform: translateY(28px);
      transition: opacity .6s ease, transform .6s ease;
    }
    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
</head>
<body>

  <!-- ── Welcome toast (logged in) ───────────────────────────── -->
  <?php if ($loggedIn && $displayName): ?>
    <div class="welcome-toast">
      <div class="toast-icon">👋</div>
      <div>
        Welcome back, <strong><?= htmlspecialchars($displayName) ?></strong>
        <br><span style="font-size:12px;color:rgba(255,255,255,.55);"><?= htmlspecialchars(ucfirst($userRole ?? 'Student')) ?></span>
      </div>
    </div>
  <?php endif; ?>

  <!-- ── Navbar ───────────────────────────────────────────────── -->
  <header class="navbar">
    <a href="index.php" class="navbar-brand">
      <div class="brand-icon">🔒</div>
      <span>SecureLocker</span>
    </a>

    <nav>
      <ul class="nav-links">
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="rentals.php">Rentals</a></li>
        <li><a href="myrentals.php">My Locker</a></li>
        <li><a href="about.php">About</a></li>
        <?php if ($loggedIn): ?>
          <li><a href="profile.php">Settings</a></li>
          <li>
            <span class="nav-badge">
              <span class="dot"></span>
              <?= htmlspecialchars($displayName ?? '') ?>
            </span>
          </li>
          <li><a href="logout.php" class="btn-logout">Logout</a></li>
        <?php else: ?>
          <li><a href="signup.php">Sign Up</a></li>
          <li><a href="login.php" class="btn-nav-login">Login →</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <!-- ── Hero ─────────────────────────────────────────────────── -->
  <section class="hero">

    <div class="hero-eyebrow">
      🎓 &nbsp;Pamantasan ng Lungsod ng Valenzuela
    </div>

    <h1>
      Smart Locker Reservations<br>
      <span class="highlight">Simplified for PLV</span>
    </h1>

    <p class="hero-sub">
      Reserve your campus locker in seconds. View availability by building and floor,
      track your reservation in real time, and manage everything from one place.
    </p>

    <div class="hero-cta">
      <?php if ($loggedIn): ?>
        <a href="rentals.php" class="btn-primary">🔍 &nbsp;Browse Lockers</a>
        <a href="myrentals.php" class="btn-ghost">My Locker →</a>
      <?php else: ?>
        <a href="signup.php" class="btn-primary">✨ &nbsp;Get Started Free</a>
        <a href="about.php" class="btn-ghost">Learn More →</a>
      <?php endif; ?>
    </div>

    <!-- Stats strip -->
    <div class="stats-strip" style="margin-top:56px;">
      <div class="stat-item">
        <div class="stat-num">6<span>+</span></div>
        <div class="stat-label">Colleges</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">6<span>F</span></div>
        <div class="stat-label">Floors Each</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">24<span>/7</span></div>
        <div class="stat-label">Online Access</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">100<span>%</span></div>
        <div class="stat-label">Digital</div>
      </div>
    </div>

  </section>

  <!-- ── Features ─────────────────────────────────────────────── -->
  <div class="section">
    <div class="section-label reveal">Platform Features</div>
    <h2 class="section-title reveal">Everything you need,<br>nothing you don't.</h2>
    <p class="section-sub reveal">
      A clean, modern system built for PLV students and staff —
      no paperwork, no queues, no confusion.
    </p>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon">🗺️</div>
        <h3>Browse by College & Floor</h3>
        <p>Navigate locker availability across all 6 PLV colleges, broken down floor by floor with a clear colour-coded grid.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">⚡</div>
        <h3>Instant Reservations</h3>
        <p>Select an available locker, fill a short form, and your reservation is logged instantly — no admin visit required.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h3>Digital Receipts</h3>
        <p>Download or print a formal reservation receipt with your full student info, locker details, and transaction timestamp.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔐</div>
        <h3>Secure Student Accounts</h3>
        <p>Accounts are linked to pre-verified student numbers, ensuring only enrolled PLV students can make reservations.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🛠️</div>
        <h3>Admin Dashboard</h3>
        <p>Admins can approve, reject, or release lockers in real time. Full visibility across every department and floor.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📱</div>
        <h3>Access Anywhere</h3>
        <p>Works on any device — desktop, tablet, or phone. Check your locker status or make a reservation on the go.</p>
      </div>
    </div>
  </div>

  <!-- ── CTA Banner ────────────────────────────────────────────── -->
  <div class="cta-banner reveal">
    <h2>Ready to reserve your locker?</h2>
    <p>
      Join hundreds of PLV students already using SecureLocker.
      <?= $loggedIn ? 'Head to Rentals to find your locker.' : 'Create your account in under a minute.' ?>
    </p>
    <?php if ($loggedIn): ?>
      <a href="rentals.php" class="btn-primary" style="display:inline-flex;">🔍 &nbsp;Browse Lockers</a>
    <?php else: ?>
      <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
        <a href="signup.php" class="btn-primary">✨ &nbsp;Create Account</a>
        <a href="login.php"  class="btn-ghost">Login →</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── Footer ────────────────────────────────────────────────── -->
  <footer>
    <div class="foot-brand">🔒 SecureLocker Inc.</div>
    <p>&copy; 2026 SecureLocker Inc. &mdash; Pamantasan ng Lungsod ng Valenzuela</p>
    <div class="foot-links">
      <a href="about.php">About</a>
      <a href="about.php">Contact</a>
      <a href="rentals.php">Rentals</a>
    </div>
  </footer>

  <script>
    // ── Scroll-reveal ──────────────────────────────────────────
    const revealEls = document.querySelectorAll('.reveal, .feature-card');
    const observer  = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          observer.unobserve(e.target);
        }
      });
    }, { threshold: 0.12 });
    revealEls.forEach(el => observer.observe(el));

    // ── Counter animation on stats ─────────────────────────────
    function animateCount(el, target, suffix) {
      let start = 0;
      const step = Math.ceil(target / 40);
      const timer = setInterval(() => {
        start = Math.min(start + step, target);
        el.innerHTML = start + '<span>' + suffix + '</span>';
        if (start >= target) clearInterval(timer);
      }, 35);
    }

    const statsObserver = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          const nums = [
            { el: document.querySelectorAll('.stat-num')[0], val: 6,   sfx: '+' },
            { el: document.querySelectorAll('.stat-num')[1], val: 6,   sfx: 'F' },
            { el: document.querySelectorAll('.stat-num')[2], val: 24,  sfx: '/7' },
            { el: document.querySelectorAll('.stat-num')[3], val: 100, sfx: '%' },
          ];
          nums.forEach(n => animateCount(n.el, n.val, n.sfx));
          statsObserver.disconnect();
        }
      });
    }, { threshold: 0.5 });

    const strip = document.querySelector('.stats-strip');
    if (strip) statsObserver.observe(strip);
  </script>

</body>
</html>