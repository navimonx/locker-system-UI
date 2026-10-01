<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/whitelist.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// ── All PLV courses ────────────────────────────────────────────
$PLV_COURSES = [
    'College of Engineering & IT' => [
        'Bachelor of Science in Electrical Engineering',
        'Bachelor of Science in Civil Engineering',
        'Bachelor of Science in Information Technology',
    ],
    'College of Accountancy & Business Admin' => [
        'Bachelor of Science in Accountancy',
        'Bachelor of Science in Accounting Technology',
        'Bachelor of Science in Business Administration Major in Financial Management',
        'Bachelor of Science in Business Administration Major in Marketing Management',
        'Bachelor of Science in Business Administration Major in Human Resource Development Management',
    ],
    'College of Public Administration & Governance' => [
        'Bachelor of Science in Public Administration',
    ],
    'College of Arts & Sciences' => [
        'Bachelor of Science in Psychology',
        'Bachelor of Science in Social Work',
        'Bachelor of Arts in Communication Studies',
        'Major in Theater Arts',
    ],
    'College of Education' => [
        'Bachelor of Elementary Education Major in Pre-School Education',
        'Bachelor of Secondary Education Major in English',
        'Bachelor of Secondary Education Major in Filipino',
        'Bachelor of Secondary Education Major in Mathematics',
        'Bachelor of Secondary Education Major in Biological Science',
        'Bachelor of Secondary Education Major in Physical Science',
    ],
];

$errorMsg   = '';
$successMsg = '';
$step       = 1;
$verifiedId = '';

$signupAction = $_POST['signup_action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($signupAction === 'verify' || isset($_POST['verify']))) {
    $inputId = trim($_POST['studentId'] ?? '');

    if (!is_valid_student_id($inputId)) {
        $errorMsg = 'Student Number must be in the format XX-XXXX (e.g. 22-1234).';
    } elseif (student_account_exists($conn, $inputId)) {
        $errorMsg = 'That Student Number already has an account. Please log in instead.';
    } elseif (is_student_id_whitelisted($conn, $inputId)) {
        $step       = 2;
        $verifiedId = $inputId;
    } else {
        $errorMsg = 'Student Number not on the approved list. Please ask your admin to add your ID first.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($signupAction === 'create' || isset($_POST['create']))) {
    $verifiedId = trim($_POST['verifiedId'] ?? '');
    $firstName  = trim($_POST['firstName']  ?? '');
    $middleName = trim($_POST['middleName']  ?? '');
    $lastName   = trim($_POST['lastName']   ?? '');
    $course     = trim($_POST['course']     ?? '');
    $contact    = trim($_POST['contact']    ?? '');
    $email      = trim($_POST['email']      ?? '');
    $password   = $_POST['password']  ?? '';
    $confirm    = $_POST['confirm']   ?? '';

    // Validate course is from approved list
    $allCourses = array_merge(...array_values($PLV_COURSES));
    $validCourse = in_array($course, $allCourses, true);

    if (!is_valid_student_id($verifiedId)) {
        $errorMsg = 'Student Number is invalid. Please verify your Student Number again.';
        $step = 1;
    } elseif (!$firstName || !$lastName || !$course || !$contact || !$email || !$password) {
        $errorMsg = 'Please fill in all required fields.';
        $step = 2;
    } elseif (!$validCourse) {
        $errorMsg = 'Please select a valid course from the list.';
        $step = 2;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
        $step = 2;
    } elseif (strlen($password) < 6) {
        $errorMsg = 'Password must be at least 6 characters.';
        $step = 2;
    } elseif ($password !== $confirm) {
        $errorMsg = 'Passwords do not match.';
        $step = 2;
    } else {
        // Check duplicate email
        $sqlEmailCheck = "SELECT id FROM Users WHERE email = ?";
        $stmtEmail = db_query($conn, $sqlEmailCheck, [$email]);
        if ($stmtEmail && db_fetch($stmtEmail)) {
            $errorMsg = 'That email address is already linked to an account. Please use a different email or log in.';
            $step = 2;
        }
    }
    if ($step === 2 && !$errorMsg) {
        // Check duplicate contact
        $sqlContactCheck = "SELECT id FROM Users WHERE contact = ?";
        $stmtContact = db_query($conn, $sqlContactCheck, [$contact]);
        if ($stmtContact && db_fetch($stmtContact)) {
            $errorMsg = 'That contact number is already linked to an account. Please use a different number.';
            $step = 2;
        }
    }
    if ($step === 2 && !$errorMsg) {
        $result = claim_student_account($conn, $verifiedId, $firstName, $middleName, $lastName, $course, $contact, $email, $password);
        if (!$result['ok']) {
            $errorMsg = 'Registration failed. ' . $result['message'];
            $step = 2;
        } else {
            header('Location: login.php?success=' . urlencode($result['message']));
            exit();
        }
    }
}

require_once __DIR__ . '/includes/popup_alerts.php';
popup_flash($successMsg ?: null, $errorMsg ?: null);

$pageTitle  = 'Sign Up — SecureLocker Inc.';
$activePage = '';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="auth-layout auth-layout--centered">
  <div class="auth-panel">
    <div class="auth-card">
      <h2>Create Account</h2>
      <p class="subtitle">Your Student ID must be on the admin-approved list before you can sign up.</p>

      <?php if ($errorMsg): ?>
        <div class="info-box" style="border-color:rgba(239,68,68,0.45);background:rgba(127,29,29,0.22);color:#fecaca;margin-bottom:16px;">
          <strong>Signup Error</strong>
          <?= htmlspecialchars($errorMsg) ?>
        </div>
      <?php endif; ?>

      <?php if ($successMsg): ?>
        <div class="info-box" style="border-color:rgba(34,197,94,0.45);background:rgba(20,83,45,0.22);color:#bbf7d0;margin-bottom:16px;">
          <strong>Account Created</strong>
          <?= htmlspecialchars($successMsg) ?>
        </div>
      <?php endif; ?>

      <?php if ($step !== 0): ?>
      <div class="steps">
        <div class="step-dot <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1</div>
        <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
        <div class="step-dot <?= $step === 2 ? 'active' : '' ?>">2</div>
      </div>
      <?php endif; ?>

      <?php if ($successMsg): ?>
        <a href="login.php" class="btn-primary" style="width:100%;justify-content:center;">Go to Login →</a>

      <?php elseif ($step === 1): ?>
        <form method="POST" action="signup.php">
          <input type="hidden" name="signup_action" value="verify">
          <div class="form-group">
            <label for="studentId">Student Number</label>
            <input type="text" id="studentId" name="studentId"
                   placeholder="e.g. 22-1234"
                   pattern="^\d{2}-\d{4}$"
                   value="<?= htmlspecialchars($_POST['studentId'] ?? '') ?>"
                   required autofocus>
            <p class="hint">Format: XX-XXXX</p>
          </div>
          <button type="submit" name="verify" class="btn-primary"
                  style="width:100%;justify-content:center;">
            Verify Student Number
          </button>
        </form>

      <?php elseif ($step === 2): ?>
        <div class="info-box">
          <strong>✓ Student Number Approved</strong>
          <?= htmlspecialchars($verifiedId) ?> — complete your account below.
        </div>

        <form method="POST" action="signup.php">
          <input type="hidden" name="signup_action" value="create">
          <input type="hidden" name="verifiedId" value="<?= htmlspecialchars($verifiedId) ?>">

          <!-- Name fields -->
          <div class="form-group">
            <label>First Name *</label>
            <input type="text" name="firstName"
                   value="<?= htmlspecialchars($_POST['firstName'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Middle Name <span class="hint">(optional)</span></label>
            <input type="text" name="middleName"
                   value="<?= htmlspecialchars($_POST['middleName'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Last Name *</label>
            <input type="text" name="lastName"
                   value="<?= htmlspecialchars($_POST['lastName'] ?? '') ?>" required>
          </div>

          <!-- Course dropdown -->
          <div class="form-group">
            <label for="course">Course / Program *</label>
            <select id="course" name="course" required
                    style="
                      width:100%; padding:10px 12px;
                      background: rgba(255,255,255,0.07);
                      border: 1px solid rgba(255,255,255,0.18);
                      border-radius: 8px;
                      color: #fff;
                      font-size: 14px;
                      font-family: inherit;
                      appearance: none;
                      -webkit-appearance: none;
                      background-image: url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2212%22 height=%228%22 viewBox=%220 0 12 8%22><path fill=%22%23ffffff%22 d=%22M1 1l5 5 5-5%22 stroke=%22%23fff%22 stroke-width=%222%22 fill=%22none%22/></svg>');
                      background-repeat: no-repeat;
                      background-position: right 12px center;
                      padding-right: 36px;
                      cursor: pointer;
                    ">
              <option value="" disabled <?= !isset($_POST['course']) ? 'selected' : '' ?>>
                — Select your course —
              </option>
              <?php foreach ($PLV_COURSES as $college => $courses): ?>
                <optgroup label="<?= htmlspecialchars($college) ?>"
                          style="color:#93c5fd; font-weight:700; font-size:12px;">
                  <?php foreach ($courses as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>"
                            <?= (($_POST['course'] ?? '') === $c) ? 'selected' : '' ?>
                            style="color:#fff; background:#1e3a6e;">
                      <?= htmlspecialchars($c) ?>
                    </option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Contact -->
          <div class="form-group">
            <label>Contact Number *</label>
            <input type="text" name="contact"
                   placeholder="e.g. 09XXXXXXXXX"
                   value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>Email Address *</label>
            <input type="email" name="email"
                   placeholder="e.g. student@plv.edu.ph"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label>Password *</label>
            <div class="pw-wrap">
              <input type="password" id="pw" name="password"
                     required oninput="checkStrength(this.value)">
              <button type="button" class="toggle-eye"
                      onclick="toggleVis('pw',this)">👁</button>
            </div>
            <div class="strength-bar-wrap" id="strengthWrap">
              <div class="strength-bar" id="strengthBar"></div>
            </div>
            <div class="strength-label" id="strengthLabel"></div>
          </div>

          <!-- Confirm password -->
          <div class="form-group">
            <label>Confirm Password *</label>
            <div class="pw-wrap">
              <input type="password" id="cpw" name="confirm"
                     required oninput="checkMatch()">
              <button type="button" class="toggle-eye"
                      onclick="toggleVis('cpw',this)">👁</button>
            </div>
            <div class="hint" id="matchHint"></div>
          </div>

          <button type="submit" name="create" value="1" class="btn-primary"
                  style="width:100%;justify-content:center;">
            Create Account
          </button>
        </form>

        <div class="divider">
          <a href="signup.php">← Use a different Student Number</a>
        </div>
      <?php endif; ?>

      <div class="divider">
        Already have an account? <a href="login.php">Log in</a>
      </div>
    </div>
  </div>
  <div class="auth-panel auth-panel--visual"></div>
</main>

<script>
function toggleVis(id, btn) {
  const el = document.getElementById(id);
  el.type  = el.type === 'password' ? 'text' : 'password';
  btn.textContent = el.type === 'password' ? '👁' : '🙈';
}
function checkStrength(val) {
  const wrap  = document.getElementById('strengthWrap');
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  if (!val) { wrap.style.display = label.style.display = 'none'; return; }
  wrap.style.display = label.style.display = 'block';
  let s = 0;
  if (val.length >= 6)           s++;
  if (val.length >= 10)          s++;
  if (/[A-Z]/.test(val))         s++;
  if (/[0-9]/.test(val))         s++;
  if (/[^A-Za-z0-9]/.test(val))  s++;
  const levels = [
    {p:'20%', c:'#ef4444', t:'Very Weak'},
    {p:'40%', c:'#f59e0b', t:'Weak'},
    {p:'60%', c:'#f59e0b', t:'Fair'},
    {p:'80%', c:'#22c55e', t:'Strong'},
    {p:'100%',c:'#22c55e', t:'Very Strong'},
  ];
  const l = levels[s - 1] || levels[0];
  bar.style.width      = l.p;
  bar.style.background = l.c;
  label.textContent    = 'Strength: ' + l.t;
  label.style.color    = l.c;
}
function checkMatch() {
  const pw   = document.getElementById('pw').value;
  const cpw  = document.getElementById('cpw').value;
  const hint = document.getElementById('matchHint');
  if (!cpw) { hint.textContent = ''; return; }
  hint.textContent = pw === cpw ? '✅ Passwords match.' : '❌ Passwords do not match.';
  hint.style.color = pw === cpw ? '#86efac' : '#fca5a5';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
