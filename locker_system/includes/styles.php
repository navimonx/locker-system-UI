<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --navy:       #0a1f44;
    --blue:       #1a56db;
    --blue-lt:    #3b82f6;
    --accent:     #f59e0b;
    --white:      #ffffff;
    --glass:      rgba(255,255,255,0.10);
    --glass-b:    rgba(255,255,255,0.18);
    --radius:     14px;
    --shadow:     0 8px 32px rgba(10,31,68,0.18);
    --transition: 0.3s cubic-bezier(.4,0,.2,1);
    --page-bg:    url('images/plv.jpg');
  }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'Inter', sans-serif;
    background: var(--navy);
    color: var(--white);
    min-height: 100vh;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
  }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
      radial-gradient(ellipse 80% 60% at 20% 30%, rgba(26,86,219,0.35) 0%, transparent 60%),
      radial-gradient(ellipse 60% 50% at 80% 70%, rgba(245,158,11,0.12) 0%, transparent 55%),
      var(--page-bg) center/cover no-repeat;
    z-index: -2;
  }
  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background: rgba(10,31,68,0.72);
    z-index: -1;
  }

  /* Navbar */
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
    flex-shrink: 0;
  }
  @keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
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

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Main layouts */
  main, .page-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 48px 24px 80px;
    width: 100%;
  }
  .page-main--center { justify-content: center; }

  .page-eyebrow, .hero-eyebrow {
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
    margin-bottom: 20px;
    animation: fadeUp .6s .1s ease both;
  }

  .page-header {
    text-align: center;
    margin-bottom: 36px;
    animation: fadeUp .6s .15s ease both;
  }
  .page-header h1, .page-header h2 {
    font-size: clamp(26px, 4vw, 40px);
    font-weight: 800;
    letter-spacing: -.5px;
    margin-bottom: 10px;
  }
  .page-header p {
    font-size: 16px;
    color: rgba(255,255,255,0.60);
    line-height: 1.65;
    max-width: 520px;
    margin: 0 auto;
  }

  .content-box {
    background: rgba(10,31,68,0.55);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 20px;
    box-shadow: var(--shadow);
    padding: 40px 44px;
    text-align: center;
    width: 100%;
    max-width: 780px;
    animation: fadeUp .55s .2s ease both;
  }
  .content-box h2 {
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.5px;
    margin-bottom: 10px;
  }
  .content-box > p {
    font-size: 15px;
    color: rgba(255,255,255,0.62);
    margin-bottom: 24px;
    line-height: 1.6;
  }
  .content-box--wide { max-width: 900px; }
  .content-box--left { text-align: left; }
  .content-box--locker-grid {
    max-width: min(1100px, 96vw);
    width: 100%;
    padding: 36px 40px 44px;
  }

  .user-strip, .user-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.10);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 999px;
    padding: 6px 16px;
    font-size: 13px;
    color: rgba(255,255,255,0.70);
    margin-bottom: 20px;
  }
  .user-strip strong, .user-pill strong { color: var(--white); }
  .user-pill .dot {
    width: 7px; height: 7px;
    background: #22c55e;
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  /* Buttons */
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
    font-family: inherit;
    transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
    box-shadow: 0 4px 20px rgba(26,86,219,0.40);
  }
  .btn-primary:hover {
    background: #1648c0;
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(26,86,219,0.55);
  }
  .btn-ghost, .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.20);
    color: var(--white);
    font-size: 14px;
    font-weight: 600;
    padding: 10px 22px;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: background var(--transition), transform var(--transition);
  }
  .btn-ghost:hover, .btn-back:hover {
    background: rgba(255,255,255,0.14);
    transform: translateY(-2px);
  }
  .btn-danger {
    background: rgba(220,38,38,0.25);
    border: 1px solid rgba(220,38,38,0.45);
    color: #fca5a5;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: background var(--transition);
  }
  .btn-danger:hover { background: rgba(220,38,38,0.40); color: #fff; }
  .btn-success {
    background: rgba(34,197,94,0.25);
    border: 1px solid rgba(34,197,94,0.45);
    color: #86efac;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
  }
  .btn-success:hover { background: rgba(34,197,94,0.40); color: #fff; }
  .btn-sm { padding: 6px 14px; font-size: 13px; }

  /* Tile / dept grids */
  .tile-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-top: 8px;
  }
  .tile-grid a {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: rgba(26,86,219,0.22);
    border: 1px solid rgba(59,130,246,0.28);
    color: var(--white);
    height: 110px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    border-radius: 12px;
    transition: background var(--transition), transform var(--transition), border-color var(--transition), box-shadow var(--transition);
  }
  .tile-grid a:hover {
    background: rgba(26,86,219,0.50);
    border-color: rgba(59,130,246,0.60);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(26,86,219,0.30);
  }
  .tile-grid a .tile-sub {
    font-size: 11px;
    font-weight: 400;
    color: rgba(255,255,255,0.55);
    margin-top: 4px;
    text-transform: uppercase;
  }

  .dept-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    width: 100%;
    max-width: 820px;
  }
  .dept-card {
    background: var(--glass);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: var(--radius);
    padding: 28px 20px 24px;
    text-decoration: none;
    color: var(--white);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    text-align: center;
    transition: transform var(--transition), background var(--transition), border-color var(--transition), box-shadow var(--transition);
  }
  .dept-card:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.13);
    border-color: rgba(59,130,246,0.35);
    box-shadow: 0 12px 40px rgba(26,86,219,0.22);
  }
  .dept-icon {
    width: 52px; height: 52px;
    background: rgba(26,86,219,0.22);
    border: 1px solid rgba(59,130,246,0.30);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
  }
  .dept-abbr { font-size: 20px; font-weight: 800; }
  .dept-full { font-size: 11px; color: rgba(255,255,255,0.48); line-height: 1.4; }
  .dept-arrow { font-size: 13px; color: rgba(255,255,255,0.30); margin-top: 4px; }
  .dept-card:hover .dept-arrow { color: var(--blue-lt); }

  /* Locker grid */
  .legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 18px;
    flex-wrap: wrap;
  }
  .legend-item { display: flex; align-items: center; gap: 7px; }
  .legend-dot { width: 12px; height: 12px; border-radius: 3px; }
  .legend-dot.available { background: #22c55e; }
  .legend-dot.pending   { background: #f59e0b; }
  .legend-dot.occupied  { background: #ef4444; }

  .locker-table { width: 100%; border-spacing: 14px; border-collapse: separate; }
  .locker-table td { text-align: center; vertical-align: middle; padding: 4px; }
  .locker-btn {
    width: 100%; min-width: 100px; max-width: 140px;
    height: 72px;
    border: none; border-radius: 12px;
    font-weight: 800; font-size: 18px;
    cursor: pointer;
    font-family: inherit;
    transition: transform var(--transition), box-shadow var(--transition);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
  }
  .locker-btn:not(:disabled):hover {
    transform: translateY(-2px) scale(1.06);
    box-shadow: 0 6px 16px rgba(0,0,0,0.25);
  }
  .locker-btn:disabled { cursor: default; filter: brightness(.75); }
  .locker-btn.available { background: #22c55e; color: #fff; }
  .locker-btn.pending   { background: #f59e0b; color: #fff; }
  .locker-btn.occupied  { background: #ef4444; color: #fff; }

  .admin-locker-btn {
    width: 100%; min-width: 130px; max-width: 160px;
    min-height: 80px;
    border: none; border-radius: 12px;
    font-weight: 700; font-size: 14px;
    cursor: pointer; font-family: inherit;
    padding: 8px;
    transition: transform var(--transition);
  }
  .admin-locker-btn:hover { transform: translateY(-2px); }
  .admin-locker-btn.available { background: #22c55e; color: #fff; }
  .admin-locker-btn.pending   { background: #f59e0b; color: #000; }
  .admin-locker-btn.occupied  { background: #ef4444; color: #fff; }
  .details-link {
    display: block; margin-top: 6px;
    font-size: 11px; color: #93c5fd;
    font-weight: 600; text-decoration: none;
  }
  .details-link:hover { color: var(--white); }

  /* Alerts */
  .alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 16px;
    text-align: left;
  }
  .alert-warning {
    background: rgba(245,158,11,0.18);
    border: 1px solid rgba(245,158,11,0.40);
    color: #fcd34d;
  }
  .alert-danger {
    background: rgba(239,68,68,0.18);
    border: 1px solid rgba(239,68,68,0.40);
    color: #fca5a5;
  }
  .alert-success {
    background: rgba(34,197,94,0.18);
    border: 1px solid rgba(34,197,94,0.40);
    color: #86efac;
  }
  .alert-error {
    background: rgba(239,68,68,0.18);
    border: 1px solid rgba(239,68,68,0.40);
    color: #fca5a5;
  }

  #app-popup-root { position: relative; z-index: 10000; }
  .app-popup-overlay {
    position: fixed;
    inset: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(3, 12, 32, 0.72);
    backdrop-filter: blur(4px);
    animation: fadeUp 0.2s ease both;
  }
  .app-popup {
    width: 100%;
    max-width: 420px;
    padding: 28px 26px 22px;
    border-radius: 18px;
    text-align: center;
    background: rgba(10, 31, 68, 0.96);
    border: 1px solid rgba(255, 255, 255, 0.14);
    box-shadow: 0 24px 48px rgba(0, 0, 0, 0.45);
    animation: fadeUp 0.25s ease both;
  }
  .app-popup-icon {
    font-size: 36px;
    line-height: 1;
    margin-bottom: 12px;
  }
  .app-popup-message {
    margin: 0 0 20px;
    font-size: 15px;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.88);
  }
  .app-popup-btn {
    width: 100%;
    justify-content: center;
  }
  .app-popup-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
  }
  .app-popup--confirm { border-color: rgba(147, 197, 253, 0.45); }
  .app-popup--success { border-color: rgba(34, 197, 94, 0.45); }
  .app-popup--error   { border-color: rgba(239, 68, 68, 0.45); }
  .app-popup--warning { border-color: rgba(245, 158, 11, 0.45); }
  .app-popup--info    { border-color: rgba(59, 130, 246, 0.45); }

  a.locker-btn { text-decoration: none; }

  /* Forms */
  .auth-layout {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: calc(100vh - 68px - 80px);
  }
  .auth-layout--centered {
    grid-template-columns: 1fr;
    justify-items: center;
    align-content: center;
    padding: 32px 20px;
  }
  .auth-layout--centered .auth-panel--visual {
    display: none;
  }
  .auth-layout--centered .auth-panel {
    width: 100%;
    max-width: 460px;
    margin: 0 auto;
    padding: 32px 24px;
  }
  .auth-panel {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 48px 40px;
  }
  .auth-panel--visual {
    background: transparent;
    position: relative;
  }
  .auth-card {
    width: 100%;
    max-width: 400px;
    background: rgba(10,31,68,0.55);
    backdrop-filter: blur(18px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 20px;
    padding: 36px 32px;
    box-shadow: var(--shadow);
    animation: fadeUp .5s ease both;
  }
  .auth-card h2 {
    font-size: 24px;
    font-weight: 800;
    text-align: center;
    margin-bottom: 6px;
  }
  .auth-card .subtitle {
    text-align: center;
    color: rgba(255,255,255,0.55);
    font-size: 14px;
    margin-bottom: 24px;
    line-height: 1.5;
  }

  .form-group { margin-bottom: 16px; text-align: left; }
  .form-group label {
    display: block;
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 6px;
    color: rgba(255,255,255,0.85);
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 10px 14px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 8px;
    color: var(--white);
    font-size: 14px;
    font-family: inherit;
    transition: border-color var(--transition), box-shadow var(--transition);
  }
  .form-group input::placeholder { color: rgba(255,255,255,0.35); }
  .form-group input:focus,
  .form-group select:focus {
    outline: none;
    border-color: rgba(59,130,246,0.60);
    box-shadow: 0 0 0 3px rgba(26,86,219,0.25);
  }
  .form-group select option { background: var(--navy); color: var(--white); }
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .hint { font-size: 12px; color: rgba(255,255,255,0.45); margin-top: 4px; }

  .pw-wrap { position: relative; }
  .pw-wrap input { padding-right: 42px; }
  .toggle-eye {
    position: absolute; right: 10px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: rgba(255,255,255,0.45); font-size: 16px; padding: 0;
  }
  .toggle-eye:hover { color: var(--white); }

  .steps {
    display: flex; justify-content: center; align-items: center;
    gap: 8px; margin-bottom: 24px;
  }
  .step-dot {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px;
    background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.50);
  }
  .step-dot.active { background: var(--blue); color: var(--white); }
  .step-dot.done   { background: #22c55e; color: var(--white); }
  .step-line { flex: 1; height: 3px; background: rgba(255,255,255,0.12); border-radius: 2px; max-width: 60px; }
  .step-line.done { background: #22c55e; }

  .info-box {
    background: rgba(26,86,219,0.18);
    border: 1px solid rgba(59,130,246,0.35);
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 16px;
    font-size: 14px;
    color: #93c5fd;
    text-align: left;
  }
  .info-box strong { color: var(--white); display: block; margin-bottom: 4px; }

  .strength-bar-wrap {
    margin-top: 6px; height: 5px;
    background: rgba(255,255,255,0.12);
    border-radius: 3px; overflow: hidden; display: none;
  }
  .strength-bar { height: 100%; width: 0; border-radius: 3px; transition: width .3s, background .3s; }
  .strength-label { font-size: 12px; margin-top: 4px; display: none; }

  .form-actions {
    display: flex; gap: 12px; justify-content: center;
    flex-wrap: wrap; margin-top: 24px;
  }
  .divider {
    text-align: center; margin-top: 16px;
    font-size: 13px; color: rgba(255,255,255,0.50);
  }
  .divider a { color: #93c5fd; font-weight: 600; text-decoration: none; }
  .divider a:hover { color: var(--white); }

  /* Data table */
  .data-table-wrap { overflow-x: auto; margin-top: 16px; }
  .data-table {
    width: 100%;
    min-width: max-content;
    border-collapse: collapse;
    font-size: 14px;
    table-layout: auto;
  }
  .data-table th {
    background: rgba(26,86,219,0.30);
    color: var(--white);
    padding: 12px 14px;
    font-weight: 600;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.10);
  }
  .data-table td {
    padding: 12px 14px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.80);
  }
  .data-table .col-contact,
  .data-table .col-email {
    min-width: 150px;
    white-space: nowrap;
  }
  .data-table .col-dept {
    min-width: 120px;
    white-space: nowrap;
  }
  .data-table .col-duration {
    min-width: 120px;
    white-space: nowrap;
  }
  .data-table .col-datetime {
    min-width: 155px;
    white-space: nowrap;
  }
  .data-table .col-locker {
    min-width: 72px;
    white-space: nowrap;
  }
  .data-table tr:hover td { background: rgba(255,255,255,0.04); }
  .status-pending  { color: #fcd34d; font-weight: 700; }
  .status-approved { color: #86efac; font-weight: 700; }
  .status-rejected { color: #fca5a5; font-weight: 700; }
  .status-released { color: rgba(255,255,255,0.50); font-weight: 700; }
  .status-expired  { color: #fb923c; font-weight: 700; }
  .status-cancelled { color: rgba(255,255,255,0.45); font-weight: 700; }
  .status-deleted   { color: #f87171; font-weight: 700; }

  .settings-card .settings-divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.12);
    margin: 28px 0 20px;
  }
  .settings-readonly {
    text-align: left;
    margin-bottom: 8px;
  }
  .settings-readonly label {
    font-size: 12px;
    color: rgba(255,255,255,0.50);
    display: block;
    margin-bottom: 4px;
  }
  .settings-readonly p {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 6px;
  }
  .data-table tr.row-history td { color: rgba(255,255,255,0.55); }
  .table-actions { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }

  .expiry-alerts { max-width: 900px; margin: 0 auto 20px; width: 100%; }
  .expiry-alert { margin-bottom: 10px; flex-wrap: wrap; }
  .expiry-alert-link { color: #93c5fd; font-weight: 700; margin-left: 6px; text-decoration: none; }
  .expiry-alert-link:hover { color: #fff; text-decoration: underline; }

  .rental-filter-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 4px;
  }
  .rental-filter-bar .btn-ghost.active {
    background: rgba(59,130,246,0.35);
    border-color: rgba(147,197,253,0.5);
    color: #fff;
  }

  .admin-search-form {
    margin: 20px 0 8px;
    padding: 18px 20px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 12px;
    text-align: left;
  }
  .admin-search-row {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: flex-end;
    margin-bottom: 12px;
  }
  .admin-search-row:last-child { margin-bottom: 0; }
  .admin-search-field { min-width: 160px; margin: 0; }
  .admin-search-field--grow { flex: 1; min-width: 200px; }
  .admin-search-field label {
    display: block;
    font-size: 12px;
    color: rgba(255,255,255,0.55);
    margin-bottom: 6px;
  }
  .admin-search-field select,
  .admin-search-field input[type="text"] {
    width: 100%;
    padding: 10px 12px;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 8px;
    color: #fff;
    font-family: inherit;
    font-size: 14px;
  }
  .admin-search-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    padding-bottom: 2px;
  }

  /* About page */
  .about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 24px;
    text-align: left;
  }
  .about-block h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--blue-lt);
    margin-bottom: 8px;
  }
  .about-block p {
    font-size: 14px;
    color: rgba(255,255,255,0.58);
    line-height: 1.65;
  }
  .contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 12px;
    margin-top: 16px;
    text-align: left;
  }
  .contact-item {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    border-radius: 10px;
    padding: 14px;
    font-size: 14px;
    color: rgba(255,255,255,0.70);
  }
  .contact-item strong {
    display: block;
    color: var(--white);
    margin-bottom: 4px;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: .4px;
  }

  /* Receipt detail rows */
  .detail-section { margin-top: 24px; text-align: left; }
  .detail-section h3 {
    font-size: 16px;
    font-weight: 700;
    color: var(--blue-lt);
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255,255,255,0.10);
  }
  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    gap: 16px;
  }
  .detail-row span:first-child { color: rgba(255,255,255,0.50); }
  .detail-row span:last-child { font-weight: 600; text-align: right; }

  /* Admin records tabs */
  .record-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 28px;
    width: 100%;
    max-width: 900px;
  }
  .record-tabs a {
    padding: 10px 20px;
    border-radius: 999px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    color: rgba(255,255,255,0.65);
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.10);
    transition: background var(--transition), color var(--transition), border-color var(--transition);
  }
  .record-tabs a:hover {
    background: rgba(255,255,255,0.12);
    color: var(--white);
  }
  .record-tabs a.active {
    background: rgba(26,86,219,0.35);
    border-color: rgba(59,130,246,0.50);
    color: var(--white);
  }
  .record-section-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--blue-lt);
    margin: 28px 0 14px;
    text-align: left;
  }
  .record-section-title:first-of-type { margin-top: 0; }

  /* Footer */
  footer {
    flex-shrink: 0;
    background: rgba(10,31,68,0.90);
    border-top: 1px solid rgba(255,255,255,0.07);
    padding: 28px 48px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }
  footer .foot-brand { font-size: 15px; font-weight: 700; color: var(--white); }
  footer p { font-size: 13px; color: rgba(255,255,255,0.40); }
  footer .foot-links { display: flex; gap: 20px; }
  footer .foot-links a {
    font-size: 13px;
    color: rgba(255,255,255,0.45);
    text-decoration: none;
    transition: color var(--transition);
  }
  footer .foot-links a:hover { color: var(--white); }

  @media (max-width: 900px) {
    .auth-layout { grid-template-columns: 1fr; }
    .auth-panel--visual { display: none; }
  }
  @media (max-width: 700px) {
    .navbar { padding: 0 20px; }
    .tile-grid, .dept-grid { grid-template-columns: repeat(2, 1fr); }
    .content-box { padding: 28px 20px; }
    .about-grid { grid-template-columns: 1fr; }
    footer { padding: 20px 24px; flex-direction: column; text-align: center; }
  }
  @media (max-width: 440px) {
    .nav-links li a { padding: 6px 8px; font-size: 12px; }
    .form-row { grid-template-columns: 1fr; }
  }

  @media print {
    .navbar, footer, .no-print { display: none !important; }
    body::before, body::after { display: none; }
    body { background: white; color: black; }
    .content-box { background: white; border: 1px solid #ccc; color: black; box-shadow: none; }
    .detail-row span { color: black !important; }
  }
</style>
