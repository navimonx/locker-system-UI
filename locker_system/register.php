<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';

if (!$isAdmin) {
    header("Location: login.php");
    exit();
}

$regMsg = '';
$regErr = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST['identifier'] ?? '');
    $lastName   = trim($_POST['lastName'] ?? '');
    $middleName = trim($_POST['middleName'] ?? '');
    $firstName  = trim($_POST['firstName'] ?? '');
    $password   = $_POST['password'] ?? '';

    if ($identifier && $lastName && $firstName && $password) {
        $stmt = db_query($conn,
            "INSERT INTO Users (studentId, lastName, middleName, firstName, password, role) VALUES (?, ?, ?, ?, ?, 'Admin')",
            [$identifier, $lastName, $middleName, $firstName, app_hash_password($password)]
        );

        if ($stmt) {
            $regMsg = 'Admin account created successfully.';
        } else {
            $regErr = 'Failed to create admin. ID may already exist.';
        }
    } else {
        $regErr = 'Please fill in all required fields.';
    }
}

require_once __DIR__ . '/includes/popup_alerts.php';
popup_flash($regMsg ?: null, $regErr ?: null);

$pageTitle   = 'Create Admin — SecureLocker Admin';
$adminActive = 'register';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="auth-layout auth-layout--centered">
  <div class="auth-panel">
    <div class="auth-card" style="max-width:440px;">
      <h2>Create Admin Account</h2>
      <p class="subtitle">
        Student accounts are managed on the <a href="admin_records.php?tab=students" style="color:#93c5fd;">Records</a> page.
        Use this form only for new admin logins.
      </p>

      <form method="POST" action="register.php">
        <div class="form-group">
          <label for="identifier">Admin Email / Login ID</label>
          <input type="email" id="identifier" name="identifier" placeholder="admin@plv.edu.ph" required>
        </div>
        <div class="form-group">
          <label for="lastName">Last Name</label>
          <input type="text" id="lastName" name="lastName" required>
        </div>
        <div class="form-group">
          <label for="middleName">Middle Name</label>
          <input type="text" id="middleName" name="middleName" placeholder="Optional">
        </div>
        <div class="form-group">
          <label for="firstName">First Name</label>
          <input type="text" id="firstName" name="firstName" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">Create Admin</button>
      </form>
    </div>
  </div>
  <div class="auth-panel auth-panel--visual"></div>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
