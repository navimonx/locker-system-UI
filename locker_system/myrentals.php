<?php
include(__DIR__ . "/db.php");
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/reservations.php';
require_once __DIR__ . '/includes/popup_alerts.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$msg = '';
$err = '';

sync_expired_reservations($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cancel pending
    if (isset($_POST['cancel_id'])) {
        $reservationId = (int) $_POST['cancel_id'];
        if (cancel_pending_reservation($conn, $reservationId, $userId)) {
            $msg = 'Pending reservation cancelled.';
        } else {
            $err = 'Only pending reservations can be cancelled.';
        }
    }

    // Delete from history (cancelled / rejected / released only)
    if (isset($_POST['delete_id'])) {
        $deleteId = (int) $_POST['delete_id'];
        $sqlCheck = "SELECT id FROM Reservations
                     WHERE id = ? AND user_id = ?
                       AND status IN ('cancelled','rejected','released')";
        $stmtCheck = db_query($conn, $sqlCheck, [$deleteId, $userId]);
        if ($stmtCheck && db_fetch($stmtCheck)) {
            $sqlDel = "DELETE FROM Reservations WHERE id = ? AND user_id = ?";
            db_query($conn, $sqlDel, [$deleteId, $userId]);
            $msg = 'Record deleted from your history.';
        } else {
            $err = 'Only cancelled, rejected, or released reservations can be deleted.';
        }
    }
}

$hasEndsAt = reservation_has_ends_at($conn);
$endsCol   = $hasEndsAt ? ', r.ends_at, r.duration' : ', r.duration';

$sql = "SELECT r.id AS reservation_id, l.id AS locker_id, l.department, l.floor,
               r.reserved_at, r.status, r.released_at $endsCol
        FROM Reservations r
        INNER JOIN Lockers l ON r.locker_id = l.id
        WHERE r.user_id = ?
        ORDER BY
          CASE WHEN r.status IN ('pending','approved') THEN 0 ELSE 1 END,
          r.reserved_at DESC";
$stmt = db_query($conn, $sql, [$userId]);

if ($stmt === false) {
    die("Failed to load rentals.<br>" . htmlspecialchars(db_last_error_message()));
}

$expiryAlerts = fetch_expiry_alerts($conn, $userId, false);
queue_expiry_alert_popups($expiryAlerts, false);
popup_flash($msg ?: null, $err ?: null);

$pageTitle  = 'My Locker — SecureLocker Inc.';
$activePage = 'myrentals';
include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/navbar.php';
?>

<main class="page-main">
  <div class="page-header">
    <div class="page-eyebrow">📋 &nbsp;My Locker</div>
    <h1>My Locker</h1>
    <p>View active and past locker reservations, including when each reservation ends.</p>
  </div>

  <div class="content-box content-box--wide">
    <?php if (db_has_rows($stmt)): ?>
      <div class="data-table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th class="col-locker">Locker</th>
              <th class="col-dept">Department</th>
              <th class="col-dept">Floor</th>
              <th class="col-duration">Duration</th>
              <th class="col-datetime">Reserved</th>
              <?php if ($hasEndsAt): ?><th class="col-datetime">Ends</th><?php endif; ?>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = db_fetch($stmt)):
              $status      = strtolower($row['status'] ?? 'pending');
              $statusClass = reservation_status_class($status);
              $isHistory   = is_reservation_history_status($status);
              $isDeletable = in_array($status, ['cancelled', 'rejected', 'released'], true);
            ?>
            <tr class="<?= $isHistory ? 'row-history' : '' ?>">
              <td class="col-locker">L<?= htmlspecialchars($row['locker_id']) ?></td>
              <td class="col-dept"><?= htmlspecialchars($row['department']) ?></td>
              <td class="col-dept"><?= htmlspecialchars($row['floor']) ?></td>
              <td class="col-duration"><?= htmlspecialchars($row['duration'] ?? '—') ?></td>
              <td class="col-datetime"><?= format_reservation_datetime($row['reserved_at']) ?></td>
              <?php if ($hasEndsAt): ?>
                <td class="col-datetime"><?= format_reservation_date($row['ends_at'] ?? null) ?></td>
              <?php endif; ?>
              <td class="<?= $statusClass ?>"><?= htmlspecialchars(reservation_status_label($status)) ?></td>
              <td>
                <div class="table-actions">
                  <?php if ($status === 'pending'): ?>
                    <form method="POST" action="myrentals.php" style="display:inline;"
                          class="cancel-form" id="cancel-<?= $row['reservation_id'] ?>">
                      <input type="hidden" name="cancel_id" value="<?= $row['reservation_id'] ?>">
                      <button type="button"
                              onclick="openCancelModal('cancel-<?= $row['reservation_id'] ?>')"
                              class="btn-danger btn-sm">Cancel</button>
                    </form>
                  <?php endif; ?>

                  <form method="GET" action="receipt.php" style="display:inline;">
                    <input type="hidden" name="reservation_id" value="<?= $row['reservation_id'] ?>">
                    <button type="submit" class="btn-ghost btn-sm">Receipt</button>
                  </form>

                  <?php if ($isDeletable): ?>
                    <form method="POST" action="myrentals.php" style="display:inline;"
                          class="delete-form" id="del-<?= $row['reservation_id'] ?>">
                      <input type="hidden" name="delete_id" value="<?= $row['reservation_id'] ?>">
                      <button type="button"
                              onclick="openDeleteModal('del-<?= $row['reservation_id'] ?>')"
                              style="
                                background: rgba(127,29,29,0.30);
                                border: 1px solid rgba(239,68,68,0.35);
                                color: #fca5a5;
                                padding: 4px 10px;
                                border-radius: 6px;
                                font-size: 13px;
                                font-weight: 600;
                                cursor: pointer;
                                transition: background .2s;
                              "
                              onmouseover="this.style.background='rgba(220,38,38,0.40)'"
                              onmouseout="this.style.background='rgba(127,29,29,0.30)'">
                        🗑 Delete
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <p class="table-hint" style="margin-top:16px;font-size:13px;color:rgba(255,255,255,0.40);">
        You can delete cancelled, rejected, or released records from your history.
        Active reservations cannot be deleted.
      </p>
    <?php else: ?>
      <p style="color:rgba(255,255,255,0.55);">You have no rentals yet.</p>
      <a href="rentals.php" class="btn-primary" style="margin-top:20px;display:inline-flex;">Browse Lockers →</a>
    <?php endif; ?>
  </div>
</main>

<!-- ── Cancel Confirmation Modal ─────────────────────────────── -->
<div id="cancelModal" style="
  display:none;
  position:fixed; inset:0; z-index:9999;
  align-items:center; justify-content:center;
">
  <!-- Backdrop -->
  <div onclick="closeCancelModal()" style="
    position:absolute; inset:0;
    background:rgba(5,15,35,0.72);
    backdrop-filter:blur(6px);
  "></div>

  <!-- Dialog -->
  <div style="
    position:relative; z-index:1;
    background:linear-gradient(145deg,#0f2352,#0a1a3e);
    border:1px solid rgba(251,191,36,0.30);
    border-radius:18px;
    padding:36px 32px 28px;
    width:100%; max-width:400px;
    box-shadow:0 24px 60px rgba(0,0,0,0.55);
    text-align:center;
    animation:modalIn .25s cubic-bezier(.4,0,.2,1) both;
  ">
    <!-- Icon -->
    <div style="
      width:56px; height:56px; border-radius:50%;
      background:rgba(251,191,36,0.14);
      border:1px solid rgba(251,191,36,0.30);
      display:flex; align-items:center; justify-content:center;
      font-size:24px; margin:0 auto 18px;
    ">&#x26A0;&#xFE0F;</div>

    <h3 style="
      color:#fff; font-size:20px; font-weight:700;
      margin-bottom:10px; letter-spacing:-.3px;
    ">Cancel Reservation?</h3>

    <p style="
      color:rgba(255,255,255,0.55); font-size:14px;
      line-height:1.6; margin-bottom:28px;
    ">
      Are you sure you want to cancel this pending reservation?
      <strong style="color:rgba(255,255,255,0.75);">This cannot be undone.</strong>
    </p>

    <div style="display:flex; gap:12px; justify-content:center;">
      <button onclick="closeCancelModal()" style="
        flex:1; padding:11px 0;
        background:rgba(255,255,255,0.07);
        border:1px solid rgba(255,255,255,0.15);
        color:#fff; font-size:14px; font-weight:600;
        border-radius:10px; cursor:pointer;
        transition:background .2s;
      "
      onmouseover="this.style.background='rgba(255,255,255,0.13)'"
      onmouseout="this.style.background='rgba(255,255,255,0.07)'">
        No, Keep It
      </button>
      <button id="cancelConfirmBtn" style="
        flex:1; padding:11px 0;
        background:linear-gradient(135deg,#b45309,#f59e0b);
        border:none;
        color:#fff; font-size:14px; font-weight:700;
        border-radius:10px; cursor:pointer;
        box-shadow:0 4px 16px rgba(251,191,36,0.30);
        transition:opacity .2s, transform .2s;
      "
      onmouseover="this.style.opacity='.88'; this.style.transform='translateY(-1px)'"
      onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)'">
        Yes, Cancel
      </button>
    </div>
  </div>
</div>

<!-- ── Custom Delete Confirmation Modal ─────────────────────── -->
<div id="deleteModal" style="
  display:none;
  position:fixed; inset:0; z-index:9999;
  align-items:center; justify-content:center;
">
  <!-- Backdrop -->
  <div onclick="closeDeleteModal()" style="
    position:absolute; inset:0;
    background:rgba(5,15,35,0.72);
    backdrop-filter:blur(6px);
  "></div>

  <!-- Dialog -->
  <div style="
    position:relative; z-index:1;
    background:linear-gradient(145deg,#0f2352,#0a1a3e);
    border:1px solid rgba(239,68,68,0.30);
    border-radius:18px;
    padding:36px 32px 28px;
    width:100%; max-width:400px;
    box-shadow:0 24px 60px rgba(0,0,0,0.55);
    text-align:center;
    animation:modalIn .25s cubic-bezier(.4,0,.2,1) both;
  ">
    <!-- Icon -->
    <div style="
      width:56px; height:56px; border-radius:50%;
      background:rgba(239,68,68,0.14);
      border:1px solid rgba(239,68,68,0.30);
      display:flex; align-items:center; justify-content:center;
      font-size:24px; margin:0 auto 18px;
    ">🗑️</div>

    <h3 style="
      color:#fff; font-size:20px; font-weight:700;
      margin-bottom:10px; letter-spacing:-.3px;
    ">Delete Record?</h3>

    <p style="
      color:rgba(255,255,255,0.55); font-size:14px;
      line-height:1.6; margin-bottom:28px;
    ">
      This will permanently remove this reservation from your history.
      <strong style="color:rgba(255,255,255,0.75);">This cannot be undone.</strong>
    </p>

    <div style="display:flex; gap:12px; justify-content:center;">
      <button onclick="closeDeleteModal()" style="
        flex:1; padding:11px 0;
        background:rgba(255,255,255,0.07);
        border:1px solid rgba(255,255,255,0.15);
        color:#fff; font-size:14px; font-weight:600;
        border-radius:10px; cursor:pointer;
        transition:background .2s;
      "
      onmouseover="this.style.background='rgba(255,255,255,0.13)'"
      onmouseout="this.style.background='rgba(255,255,255,0.07)'">
        Cancel
      </button>
      <button id="modalConfirmBtn" style="
        flex:1; padding:11px 0;
        background:linear-gradient(135deg,#dc2626,#ef4444);
        border:none;
        color:#fff; font-size:14px; font-weight:700;
        border-radius:10px; cursor:pointer;
        box-shadow:0 4px 16px rgba(239,68,68,0.35);
        transition:opacity .2s, transform .2s;
      "
      onmouseover="this.style.opacity='.88'; this.style.transform='translateY(-1px)'"
      onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)'">
        Yes, Delete
      </button>
    </div>
  </div>
</div>

<style>
@keyframes modalIn {
  from { opacity:0; transform:scale(.93) translateY(12px); }
  to   { opacity:1; transform:scale(1)   translateY(0); }
}
</style>

<script>
let _pendingFormId = null;

function openDeleteModal(formId) {
  _pendingFormId = formId;
  const modal = document.getElementById('deleteModal');
  modal.style.display = 'flex';
  document.getElementById('modalConfirmBtn').onclick = function () {
    document.getElementById(_pendingFormId).submit();
  };
}
function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
  _pendingFormId = null;
}

let _pendingCancelFormId = null;

function openCancelModal(formId) {
  _pendingCancelFormId = formId;
  const modal = document.getElementById('cancelModal');
  modal.style.display = 'flex';
  document.getElementById('cancelConfirmBtn').onclick = function () {
    document.getElementById(_pendingCancelFormId).submit();
  };
}
function closeCancelModal() {
  document.getElementById('cancelModal').style.display = 'none';
  _pendingCancelFormId = null;
}

// Close both modals on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeDeleteModal();
    closeCancelModal();
  }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>