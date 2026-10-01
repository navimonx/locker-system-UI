<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/whitelist.php';
require_once __DIR__ . '/includes/records_data.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/admin_search.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!$isAdmin && !$isRegistrar) {
    header("Location: login.php");
    exit();
}

$adminId = $_SESSION['user_id'] ?? null;
$tab = $_GET['tab'] ?? 'reservations';
if (!in_array($tab, ['reservations', 'students', 'admins'], true)) {
    $tab = 'reservations';
}

$msg = '';
$err = '';

sync_expired_reservations($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
    if (!$isAdmin) {
        header("Location: login.php");
        exit();
    }
    $result = delete_reservation_history($conn, (int) $_POST['delete_reservation']);
    if ($result['ok']) {
        $msg = $result['message'];
    } else {
        $err = $result['message'];
    }
    $tab = 'reservations';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!$isAdmin) {
        header("Location: login.php");
        exit();
    }
    $targetUserId = (int) ($_POST['delete_user'] ?? 0);
    $returnTab    = $_POST['return_tab'] ?? 'students';
    if (!in_array($returnTab, ['students', 'admins'], true)) {
        $returnTab = 'students';
    }
    $result = delete_user_account($conn, $targetUserId, $adminId ? (int) $adminId : null);
    if ($result['ok']) {
        $msg = $result['message'];
    } else {
        $err = $result['message'];
    }
    $tab = $returnTab;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'students') {
    $hasWhitelist = db_table_exists($conn, 'AllowedStudentIds');

    if ($hasWhitelist) {
        if (isset($_POST['add_single'])) {
            $res = add_student_ids_to_whitelist($conn, [$_POST['studentId'] ?? ''], $adminId);
            if ($res['added'] > 0) {
                $msg = 'Student ID added.';
            } else {
                $err = $res['errors'][0] ?? 'Could not add ID.';
            }
        }
        if (isset($_POST['add_bulk'])) {
            $lines = preg_split('/[\r\n,;]+/', $_POST['bulk_ids'] ?? '');
            $res = add_student_ids_to_whitelist($conn, $lines, $adminId);
            $msg = "Added {$res['added']} ID(s). Skipped {$res['skipped']}.";
            if (!empty($res['errors'])) {
                $err = implode(' ', array_slice($res['errors'], 0, 4));
            }
        }
        if (isset($_POST['remove_id'])) {
            db_query($conn, "DELETE FROM AllowedStudentIds WHERE id = ? AND claimed_at IS NULL", [(int) $_POST['remove_id']]);
            $msg = 'Removed from approved list.';
        }
    }
}

function format_user_name(array $row): string {
    return trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? '')) ?: '—';
}

function format_datetime($val): string {
    if ($val instanceof DateTime) return $val->format('Y-m-d H:i');
    return $val ? (string) $val : '—';
}

$pendingData  = fetch_pending_student_ids($conn);
$tableMissing = $pendingData['tableMissing'];

$listFilter = $_GET['filter'] ?? 'active';
if (!in_array($listFilter, ['all', 'active', 'history'], true)) {
    $listFilter = 'active';
}

$searchParams = admin_search_query_params();
$reservations = fetch_reservations_filtered($conn, array_merge($searchParams, ['filter' => $tab === 'reservations' ? $listFilter : 'all']));
$students     = fetch_registered_students($conn);
if ($tab === 'students') {
    $students = filter_students_list($students, $searchParams);
}
$searchSuffix = admin_search_url_suffix($searchParams);
$pendingIds   = $pendingData['rows'];
$admins       = fetch_admin_accounts($conn);

popup_flash($msg ?: null, $err ?: null);
if ($tableMissing && $tab === 'students') {
    popup_add('warning', 'AllowedStudentIds table is missing. Re-import locker_system.sql in phpMyAdmin to create it.');
}

$pageTitle   = 'Records — SecureLocker Admin';
$adminActive = 'records';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/admin_navbar.php';
?>

<main class="page-main">
  <div class="page-header">
    <div class="page-eyebrow">📋 &nbsp;System Records</div>
    <h1>Records</h1>
    <p>View locker reservations, student accounts, and approval records.</p>
  </div>

  <nav class="record-tabs">
    <a href="admin_records.php?tab=reservations" class="<?= $tab === 'reservations' ? 'active' : '' ?>">
      Reserved Lockers (<?= count($reservations) ?>)
    </a>
    <a href="admin_records.php?tab=students" class="<?= $tab === 'students' ? 'active' : '' ?>">
      Students (<?= count($students) ?><?= !$tableMissing ? ' + ' . count($pendingIds) . ' pending' : '' ?>)
    </a>
    <?php if ($isAdmin): ?>
      <a href="admin_records.php?tab=admins" class="<?= $tab === 'admins' ? 'active' : '' ?>">
        Admin Accounts (<?= count($admins) ?>)
      </a>
    <?php endif; ?>
  </nav>

  <?php if ($tab === 'reservations'): ?>
    <div class="content-box content-box--wide content-box--left">
      <h2 class="record-section-title" style="margin-top:0;">Reservations</h2>

      <div class="rental-filter-bar" style="margin-bottom:16px;">
        <a href="admin_records.php?tab=reservations&filter=active<?= $searchSuffix ?>" class="btn-ghost btn-sm <?= $listFilter === 'active' ? 'active' : '' ?>">Active</a>
        <a href="admin_records.php?tab=reservations&filter=history<?= $searchSuffix ?>" class="btn-ghost btn-sm <?= $listFilter === 'history' ? 'active' : '' ?>">History</a>
        <a href="admin_records.php?tab=reservations&filter=all<?= $searchSuffix ?>" class="btn-ghost btn-sm <?= $listFilter === 'all' ? 'active' : '' ?>">All</a>
      </div>
      <?php if ($listFilter === 'history'): ?>
        <p style="font-size:13px;color:rgba(255,255,255,0.50);margin-bottom:16px;">
          Released, rejected, cancelled, expired, and removed reservations appear here automatically.
          <?php if ($isAdmin): ?>Use <strong style="color:rgba(255,255,255,0.75);">Delete</strong> to permanently remove a record from history.<?php endif; ?>
        </p>
      <?php endif; ?>

      <?php render_reservation_search_form('admin_records.php', $searchParams, $listFilter); ?>

      <?php if (empty($reservations)): ?>
        <p style="color:rgba(255,255,255,0.55);">No reservations yet.</p>
      <?php else: ?>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th class="col-contact">Contact</th>
                <th class="col-email">Email</th>
                <th class="col-locker">Locker</th>
                <th class="col-dept">Dept / Floor</th>
                <th class="col-duration">Duration</th>
                <th class="col-datetime">Reserved</th>
                <?php if (reservation_has_ends_at($conn)): ?><th class="col-datetime">Ends</th><?php endif; ?>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $r):
                $status = strtolower($r['status'] ?? 'pending');
                $statusClass = reservation_status_class($status);
              ?>
              <tr class="<?= is_reservation_history_status($status) ? 'row-history' : '' ?>">
                <td><?= htmlspecialchars($r['studentId']) ?></td>
                <td><?= htmlspecialchars(format_user_name($r)) ?></td>
                <td class="col-contact"><?= htmlspecialchars($r['contact'] ?? '—') ?></td>
                <td class="col-email"><?= htmlspecialchars($r['email'] ?? '—') ?></td>
                <td class="col-locker">L<?= htmlspecialchars($r['locker_id']) ?></td>
                <td class="col-dept"><?= htmlspecialchars($r['department']) ?> · F<?= htmlspecialchars($r['floor']) ?></td>
                <td class="col-duration"><?= htmlspecialchars($r['duration'] ?? '—') ?></td>
                <td class="col-datetime"><?= format_datetime($r['reserved_at']) ?></td>
                <?php if (reservation_has_ends_at($conn)): ?>
                  <td class="col-datetime"><?= format_reservation_date($r['ends_at'] ?? null) ?></td>
                <?php endif; ?>
                <td class="<?= $statusClass ?>"><?= htmlspecialchars(reservation_status_label($status)) ?></td>
                <td class="table-actions">
                  <a href="adminlocker_view.php?id=<?= (int) $r['reservation_id'] ?>" class="btn-ghost btn-sm">View</a>
                  <?php if ($isAdmin && is_reservation_history_status($status)): ?>
                    <form method="POST" action="admin_records.php?tab=reservations&filter=<?= urlencode($listFilter) ?><?= $searchSuffix ?>" style="display:inline;"
                          data-confirm="Permanently delete this reservation from history? This cannot be undone.">
                      <input type="hidden" name="delete_reservation" value="<?= (int) $r['reservation_id'] ?>">
                      <button type="submit" class="btn-danger btn-sm">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  <?php elseif ($tab === 'students'): ?>
    <?php if (!$tableMissing): ?>
      <div class="admin-students-forms" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;width:100%;max-width:900px;margin-bottom:24px;">
        <div class="content-box content-box--left" style="max-width:none;padding:24px;">
          <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;">Add approved ID</h3>
          <form method="POST" action="admin_records.php?tab=students">
            <div class="form-group">
              <label for="studentId">Student ID</label>
              <input type="text" id="studentId" name="studentId" pattern="^\d{2}-\d{4}$" placeholder="24-3229" required>
            </div>
            <button type="submit" name="add_single" class="btn-primary btn-sm">Add</button>
          </form>
        </div>
        <div class="content-box content-box--left" style="max-width:none;padding:24px;">
          <h3 style="font-size:15px;font-weight:700;margin-bottom:12px;">Bulk add IDs</h3>
          <form method="POST" action="admin_records.php?tab=students">
            <div class="form-group">
              <textarea name="bulk_ids" rows="4" style="width:100%;padding:10px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.18);border-radius:8px;color:#fff;font-family:inherit;resize:vertical;" placeholder="24-1001&#10;24-1002&#10;24-1003"></textarea>
            </div>
            <button type="submit" name="add_bulk" class="btn-primary btn-sm">Import</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="content-box content-box--wide content-box--left">
      <h2 class="record-section-title" style="margin-top:0;">Registered Students</h2>
      <p style="font-size:13px;color:rgba(255,255,255,0.50);margin-bottom:16px;">Students who completed sign up and have an active account.</p>

      <?php render_student_search_form('admin_records.php', $searchParams); ?>

      <?php if (empty($students)): ?>
        <p style="color:rgba(255,255,255,0.55);">No registered students yet.</p>
      <?php else: ?>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Course</th>
                <th>Contact</th>
                <th>Email</th>
                <?php if ($isAdmin): ?><th></th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <tr>
                <td><strong><?= htmlspecialchars($s['studentId']) ?></strong></td>
                <td><?= htmlspecialchars(format_user_name($s)) ?></td>
                <td><?= htmlspecialchars($s['course'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['contact'] ?? '—') ?></td>
                <td><?= htmlspecialchars($s['email'] ?? '—') ?></td>
                <?php if ($isAdmin): ?>
                <td>
                  <form method="POST" action="admin_records.php?tab=students" style="display:inline;"
                        data-confirm="Delete student account <?= htmlspecialchars($s['studentId']) ?>? This removes their reservations and frees their lockers.">
                    <input type="hidden" name="delete_user" value="<?= (int) $s['id'] ?>">
                    <input type="hidden" name="return_tab" value="students">
                    <button type="submit" class="btn-danger btn-sm">Delete</button>
                  </form>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if (!$tableMissing): ?>
        <h2 class="record-section-title">Approved IDs — pending signup</h2>
        <p style="font-size:13px;color:rgba(255,255,255,0.50);margin-bottom:16px;">These students can create an account on the Sign Up page.</p>
        <?php if (empty($pendingIds)): ?>
          <p style="color:rgba(255,255,255,0.55);">No pending IDs.</p>
        <?php else: ?>
          <div class="data-table-wrap">
            <table class="data-table">
              <thead>
                <tr><th>Student ID</th><th>Added</th><?php if ($isAdmin): ?><th></th><?php endif; ?></tr>
              </thead>
              <tbody>
                <?php foreach ($pendingIds as $p): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($p['studentId']) ?></strong></td>
                  <td><?= format_datetime($p['added_at']) ?></td>
                  <?php if ($isAdmin): ?>
                  <td>
                    <form method="POST" action="admin_records.php?tab=students" style="display:inline;" data-confirm="Remove this ID?">
                      <input type="hidden" name="remove_id" value="<?= (int) $p['id'] ?>">
                      <button type="submit" class="btn-danger btn-sm">Remove</button>
                    </form>
                  </td>
                  <?php endif; ?>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  <?php elseif ($tab === 'admins'): ?>
    <?php if (!$isAdmin): header("Location: login.php"); exit(); endif; ?>
    <div class="content-box content-box--wide content-box--left">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
        <h2 class="record-section-title" style="margin:0;">Admin Accounts</h2>
        <a href="register.php" class="btn-primary btn-sm">+ Create Admin</a>
      </div>
      <?php if (empty($admins)): ?>
        <p style="color:rgba(255,255,255,0.55);">No admin accounts found.</p>
      <?php else: ?>
        <div class="data-table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Login ID</th>
                <th>Name</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($admins as $a):
                $isSelf = $adminId && (int) $a['id'] === (int) $adminId;
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($a['studentId']) ?></strong><?= $isSelf ? ' <span style="color:#93c5fd;font-size:11px;">(you)</span>' : '' ?></td>
                <td><?= htmlspecialchars(format_user_name($a)) ?></td>
                <td>
                  <?php if ($isSelf): ?>
                    <span style="color:rgba(255,255,255,0.35);font-size:12px;">—</span>
                  <?php else: ?>
                    <form method="POST" action="admin_records.php?tab=admins" style="display:inline;"
                          data-confirm="Delete admin account <?= htmlspecialchars($a['studentId']) ?>?">
                      <input type="hidden" name="delete_user" value="<?= (int) $a['id'] ?>">
                      <input type="hidden" name="return_tab" value="admins">
                      <button type="submit" class="btn-danger btn-sm">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
