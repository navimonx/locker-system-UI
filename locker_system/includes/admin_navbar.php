<?php
require_once dirname(__DIR__) . '/db.php';
require_once __DIR__ . '/session.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentId = $_POST['studentId'] ?? '';
    $password  = $_POST['password'] ?? '';

    $sql = "SELECT id, studentId, role, password FROM Users WHERE studentId = ? LIMIT 1";
    $stmt = db_query($conn, $sql, [trim($studentId)]);

    if ($stmt === false) {
        die('Login query failed: ' . htmlspecialchars(db_last_error_message()));
    }

    $row = db_fetch($stmt);
    if ($row && app_password_matches($password, $row['password'] ?? '')) {
        if (!app_password_is_hashed($row['password'])) {
            db_query($conn, "UPDATE Users SET password = ? WHERE id = ?",
                [app_hash_password($password), (int) $row['id']]);
        }

        session_regenerate_id(true);
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['studentId'] = $row['studentId'];
        $_SESSION['role']      = $row['role'];

        $role = strtolower(trim((string) ($row['role'] ?? 'student')));
        if ($role === 'admin' || $role === 'superadmin') {
            header("Location: adminpage.php");
        } elseif ($role === 'registrar') {
            header("Location: registrarpage.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $loginError = 'Invalid ID or password. Please try again.';
    }
}

require_once __DIR__ . '/popup_alerts.php';
if (!empty($loginError)) {
    popup_add('error', $loginError);
}

$pageTitle  = 'Login — SecureLocker Inc.';
$activePage = 'login';
include __DIR__ . '/head.php';
include __DIR__ . '/navbar.php';
?>

<main class="auth-layout auth-layout--centered">
  <div class="auth-panel">
    <div class="auth-card">
      <h2>Welcome Back</h2>
      <p class="subtitle">Sign in with your student ID and password to access your locker.</p>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label for="studentId">Student ID</label>
          <input type="text" id="studentId" name="studentId" placeholder="e.g. 22-1234" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <div class="pw-wrap">
            <input type="password" id="password" name="password" required>
            <button type="button" class="toggle-eye" onclick="toggleVis('password',this)">👁</button>
          </div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Login</button>
      </form>

      <div class="divider">
        Don't have an account? <a href="signup.php">Sign up</a>
      </div>
    </div>
  </div>
  <div class="auth-panel auth-panel--visual"></div>
</main>

<script>
function toggleVis(id, btn) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
  btn.textContent = el.type === 'password' ? '👁' : '🙈';
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
