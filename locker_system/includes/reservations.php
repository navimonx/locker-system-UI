<?php

/** Days before ends_at to show advance expiry warnings. */
define('RESERVATION_EXPIRY_WARN_DAYS', 14);

function reservation_has_ends_at($conn): bool {
    return db_column_exists($conn, 'Reservations', 'ends_at');
}

/** Compute reservation end from duration label and start time. */
function compute_reservation_ends_at(string $duration, $startAt = null): DateTime {
    $start = $startAt instanceof DateTime ? clone $startAt : new DateTime();
    $d = strtolower(trim($duration));

    if (strpos($d, 'year') !== false) {
        $start->modify('+10 months');
    } elseif (strpos($d, 'full') !== false) {
        $start->modify('+5 months');
    } else {
        $start->modify('+75 days');
    }
    return $start;
}

function format_reservation_datetime($val): string {
    if ($val instanceof DateTime) {
        return $val->format('Y-m-d H:i');
    }
    return $val ? (string) $val : '—';
}

function format_reservation_date($val): string {
    if ($val instanceof DateTime) {
        return $val->format('M j, Y');
    }
    return $val ? (string) $val : '—';
}

/** Mark approved reservations past ends_at as expired and free lockers. */
function sync_expired_reservations($conn): void {
    if (!reservation_has_ends_at($conn)) {
        return;
    }
    db_query($conn,
        "UPDATE Lockers l
         INNER JOIN Reservations r ON r.locker_id = l.id
         SET l.status = 'available', l.owner_id = NULL
         WHERE r.status IN ('approved', 'assigned') AND r.ends_at IS NOT NULL AND r.ends_at < NOW()"
    );
    db_query($conn,
        "UPDATE Reservations
         SET status = 'expired', released_at = COALESCE(released_at, NOW())
         WHERE status IN ('approved', 'assigned') AND ends_at IS NOT NULL AND ends_at < NOW()"
    );
    sync_locker_availability($conn);
}

/**
 * Reconcile Lockers.status / owner_id with active reservations (pending, approved).
 * Fixes lockers stuck as pending/occupied after reject, cancel, release, etc.
 */
function sync_locker_availability($conn, ?int $lockerId = null, ?string $dept = null, ?int $floor = null): void {
    $scope  = 'WHERE 1=1';
    $params = [];
    if ($lockerId !== null) {
        $scope .= ' AND l.id = ?';
        $params[] = $lockerId;
    }
    if ($dept !== null && $dept !== '') {
        $scope .= ' AND l.department = ?';
        $params[] = $dept;
    }
    if ($floor !== null) {
        $scope .= ' AND l.floor = ?';
        $params[] = $floor;
    }

    db_query($conn,
        "UPDATE Lockers l
         SET l.status = 'available', l.owner_id = NULL
         $scope
         AND NOT EXISTS (
             SELECT 1 FROM Reservations r
             WHERE r.locker_id = l.id AND r.status IN ('pending', 'approved', 'assigned')
         )",
        $params
    );

    db_query($conn,
        "UPDATE Lockers l
         INNER JOIN Reservations r ON r.locker_id = l.id AND r.status IN ('pending', 'approved')
         SET l.status = 'pending', l.owner_id = r.user_id
         $scope
         AND r.id = (
             SELECT r2.id FROM Reservations r2
             WHERE r2.locker_id = l.id AND r2.status IN ('pending', 'approved')
             ORDER BY r2.reserved_at DESC, r2.id DESC
             LIMIT 1
         )",
        $params
    );

    db_query($conn,
        "UPDATE Lockers l
         INNER JOIN Reservations r ON r.locker_id = l.id AND r.status = 'assigned'
         SET l.status = 'occupied', l.owner_id = r.user_id
         $scope
         AND r.id = (
             SELECT r2.id FROM Reservations r2
             WHERE r2.locker_id = l.id AND r2.status = 'assigned'
             ORDER BY r2.reserved_at DESC, r2.id DESC
             LIMIT 1
         )",
        $params
    );
}

/** Sync one locker after a reservation status change. */
function sync_locker_for_reservation($conn, int $reservationId): void {
    $stmt = db_query($conn, "SELECT locker_id FROM Reservations WHERE id = ?", [$reservationId]);
    if ($stmt && ($row = db_fetch($stmt))) {
        $lockerId = (int) ($row['locker_id'] ?? 0);
        if ($lockerId > 0) {
            sync_locker_availability($conn, $lockerId);
        }
    }
}

function reservation_table_has_column($conn, string $column): bool {
    return db_column_exists($conn, 'Reservations', $column);
}

/** Map grid label L{n} to database locker id for a dept/floor. */
function resolve_locker_id($conn, string $dept, int $floor, ?int $lockerId, ?int $slot): ?int {
    if ($lockerId > 0) {
        $stmt = db_query($conn,
            "SELECT id FROM Lockers WHERE id = ? AND department = ? AND floor = ?",
            [$lockerId, $dept, $floor]
        );
        if ($stmt && ($row = db_fetch($stmt))) {
            return (int) $row['id'];
        }
    }
    if ($slot > 0) {
        $stmt = db_query($conn,
            "SELECT id FROM Lockers WHERE department = ? AND floor = ? ORDER BY id",
            [$dept, $floor]
        );
        $i = 1;
        while ($stmt && ($row = db_fetch($stmt))) {
            if ($i === $slot) {
                return (int) $row['id'];
            }
            $i++;
        }
    }
    return null;
}

/**
 * Create a pending reservation and mark the locker pending.
 * @return array{ok:bool, code?:string, message?:string, reservation_id?:int, locker_id?:int, slot?:int}
 */
function create_pending_reservation(
    $conn,
    int $userId,
    string $dept,
    int $floor,
    ?int $lockerId,
    ?int $slot,
    string $duration,
    string $course,
    string $contact,
    string $paymentFrequency = 'full'
): array {
    $resolvedId = resolve_locker_id($conn, $dept, $floor, $lockerId, $slot);
    if (!$resolvedId) {
        return ['ok' => false, 'code' => 'locker_invalid', 'message' => 'Could not find that locker on this floor.'];
    }

    if (!locker_is_available_for_reserve($conn, $resolvedId)) {
        return ['ok' => false, 'code' => 'locker_unavailable', 'message' => 'That locker is not available.'];
    }

    $stmtDup = db_query($conn,
        "SELECT id FROM Reservations WHERE user_id = ? AND status IN ('pending', 'approved', 'assigned') LIMIT 1",
        [$userId]
    );
    if ($stmtDup && db_fetch($stmtDup)) {
        return ['ok' => false, 'code' => 'duplicate', 'message' => 'You already have an active or pending reservation.'];
    }

    $endsAtStr = reservation_table_has_column($conn, 'ends_at')
        ? compute_reservation_ends_at($duration)->format('Y-m-d H:i:s')
        : null;

    // One row per (user, locker) — reuse history row instead of INSERT (UQ_LockerUser).
    $stmtPrior = db_query($conn,
        "SELECT id, status FROM Reservations WHERE user_id = ? AND locker_id = ? ORDER BY reserved_at DESC LIMIT 1",
        [$userId, $resolvedId]
    );
    $prior = $stmtPrior ? db_fetch($stmtPrior) : null;

    if ($prior) {
        $priorStatus = strtolower(trim($prior['status'] ?? ''));
        if (in_array($priorStatus, ['pending', 'approved', 'assigned'], true)) {
            return ['ok' => false, 'code' => 'locker_unavailable', 'message' => 'That locker is not available.'];
        }
        if (!is_reservation_history_status($priorStatus)) {
            return ['ok' => false, 'code' => 'failed', 'message' => 'Reservation could not be saved. Please try again.'];
        }

        $reservationId = (int) $prior['id'];
        $setParts      = ["status = 'pending'", 'duration = ?', 'reserved_at = NOW()'];
        $setParams     = [$duration];

        if ($endsAtStr !== null) {
            $setParts[]  = 'ends_at = ?';
            $setParams[] = $endsAtStr;
        }
        if (reservation_table_has_column($conn, 'released_at')) {
            $setParts[] = 'released_at = NULL';
        }
        if (reservation_table_has_column($conn, 'expiry_notified_at')) {
            $setParts[] = 'expiry_notified_at = NULL';
        }
        if (reservation_table_has_column($conn, 'course')) {
            $setParts[]  = 'course = ?';
            $setParams[] = $course;
        }
        if (reservation_table_has_column($conn, 'contact')) {
            $setParts[]  = 'contact = ?';
            $setParams[] = $contact;
        }
        if (reservation_table_has_column($conn, 'payment_frequency')) {
            $setParts[]  = 'payment_frequency = ?';
            $setParams[] = function_exists('normalize_payment_frequency')
                ? normalize_payment_frequency($paymentFrequency)
                : (strtolower($paymentFrequency) === 'monthly' ? 'monthly' : 'full');
        }
        if (reservation_table_has_column($conn, 'amount_due')) {
            $setParts[]  = 'amount_due = ?';
            $setParams[] = function_exists('compute_amount_due') ? compute_amount_due($duration) : 0;
        }
        if (reservation_table_has_column($conn, 'payment_status')) {
            $setParts[] = "payment_status = 'not-claimed'";
        }
        if (reservation_table_has_column($conn, 'payment_verified')) {
            $setParts[] = 'payment_verified = 0';
        }
        if (reservation_table_has_column($conn, 'remarks')) {
            $setParts[]  = 'remarks = ?';
            $setParams[] = 'Application submitted. Awaiting Registrar review.';
        }
        $setParams[] = $reservationId;

        $sqlUpdate = 'UPDATE Reservations SET ' . implode(', ', $setParts) . ' WHERE id = ?';
        $stmt      = db_query($conn, $sqlUpdate, $setParams);
        if ($stmt === false) {
            error_log('Reservation reactivate failed: ' . db_last_error_message());
            return ['ok' => false, 'code' => 'failed', 'message' => 'Reservation could not be saved. Please try again.'];
        }
    } else {
        $columns      = ['user_id', 'locker_id', 'status', 'duration', 'reserved_at'];
        $placeholders = ['?', '?', "'pending'", '?', 'NOW()'];
        $params       = [$userId, $resolvedId, $duration];

        if ($endsAtStr !== null) {
            $columns[]      = 'ends_at';
            $placeholders[] = '?';
            $params[]       = $endsAtStr;
        }
        if (reservation_table_has_column($conn, 'course')) {
            $columns[]      = 'course';
            $placeholders[] = '?';
            $params[]       = $course;
        }
        if (reservation_table_has_column($conn, 'yearLevel')) {
            $columns[]      = 'yearLevel';
            $placeholders[] = 'NULL';
        }
        if (reservation_table_has_column($conn, 'contact')) {
            $columns[]      = 'contact';
            $placeholders[] = '?';
            $params[]       = $contact;
        }
        if (reservation_table_has_column($conn, 'payment_frequency')) {
            $columns[]      = 'payment_frequency';
            $placeholders[] = '?';
            $params[]       = function_exists('normalize_payment_frequency')
                ? normalize_payment_frequency($paymentFrequency)
                : (strtolower($paymentFrequency) === 'monthly' ? 'monthly' : 'full');
        }
        if (reservation_table_has_column($conn, 'amount_due')) {
            $columns[]      = 'amount_due';
            $placeholders[] = '?';
            $params[]       = function_exists('compute_amount_due') ? compute_amount_due($duration) : 0;
        }
        if (reservation_table_has_column($conn, 'payment_status')) {
            $columns[]      = 'payment_status';
            $placeholders[] = "'not-claimed'";
        }
        if (reservation_table_has_column($conn, 'payment_verified')) {
            $columns[]      = 'payment_verified';
            $placeholders[] = '0';
        }
        if (reservation_table_has_column($conn, 'remarks')) {
            $columns[]      = 'remarks';
            $placeholders[] = '?';
            $params[]       = 'Application submitted. Awaiting Registrar review.';
        }

        $sql  = 'INSERT INTO Reservations (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = db_query($conn, $sql, $params);
        if ($stmt === false) {
            $err = db_last_error_message();
            error_log('Reservation insert failed: ' . $err);
            if (stripos($err, 'UQ_LockerUser') !== false) {
                return ['ok' => false, 'code' => 'failed', 'message' => 'You already reserved this locker before. Refresh the page and try again.'];
            }
            return ['ok' => false, 'code' => 'failed', 'message' => 'Reservation could not be saved. Please try again.'];
        }

        $reservationId = null;
        $stmtId = db_query($conn,
            "SELECT id FROM Reservations WHERE user_id = ? AND locker_id = ? ORDER BY reserved_at DESC LIMIT 1",
            [$userId, $resolvedId]
        );
        if ($stmtId && ($row = db_fetch($stmtId))) {
            $reservationId = (int) $row['id'];
        }
    }

    db_query($conn, "UPDATE Lockers SET status = 'pending', owner_id = ? WHERE id = ?", [$userId, $resolvedId]);
    sync_locker_availability($conn, $resolvedId);

    if (function_exists('add_notification')) {
        $amount = function_exists('compute_amount_due') ? compute_amount_due($duration) : 0;
        add_notification($conn, $userId, 'Your locker application has been submitted and is pending review.');
        if (function_exists('notify_staff')) {
            notify_staff($conn, 'New locker application requires Registrar review.');
        }
        if (function_exists('add_transaction')) {
            add_transaction($conn, 'reservation', $userId, (float) $amount, 'pending');
        }
    }

    $displaySlot = $slot > 0 ? $slot : null;
    if ($displaySlot === null) {
        $stmtSlot = db_query($conn,
            "SELECT id FROM Lockers WHERE department = ? AND floor = ? ORDER BY id",
            [$dept, $floor]
        );
        $i = 1;
        while ($stmtSlot && ($r = db_fetch($stmtSlot))) {
            if ((int) $r['id'] === $resolvedId) {
                $displaySlot = $i;
                break;
            }
            $i++;
        }
    }

    return [
        'ok'             => true,
        'reservation_id' => $reservationId,
        'locker_id'      => $resolvedId,
        'slot'           => $displaySlot,
    ];
}

/** True when locker has no pending/approved reservation and status is available. */
function locker_is_available_for_reserve($conn, int $lockerId): bool {
    sync_locker_availability($conn, $lockerId);
    $stmt = db_query($conn,
        "SELECT l.status FROM Lockers l
         WHERE l.id = ?
         AND NOT EXISTS (
             SELECT 1 FROM Reservations r
             WHERE r.locker_id = l.id AND r.status IN ('pending', 'approved', 'assigned')
         )",
        [$lockerId]
    );
    if ($stmt === false || !($row = db_fetch($stmt))) {
        return false;
    }
    return strtolower(trim($row['status'] ?? '')) === 'available';
}

/**
 * In-app expiry alerts for the current user.
 * Students: their approved/pending reservations nearing end.
 * Admins: any approved reservations nearing end (all students).
 */
function fetch_expiry_alerts($conn, ?int $userId, bool $isAdmin): array {
    if (!reservation_has_ends_at($conn) || (!$isAdmin && !$userId)) {
        return [];
    }

    sync_expired_reservations($conn);

    $warnDays = RESERVATION_EXPIRY_WARN_DAYS;
    $sql = "SELECT r.id AS reservation_id, r.duration, r.ends_at, r.status,
                   u.studentId, u.firstName, u.lastName,
                   l.department, l.floor, l.id AS locker_id
            FROM Reservations r
            INNER JOIN Users u ON r.user_id = u.id
            INNER JOIN Lockers l ON r.locker_id = l.id
            WHERE r.status IN ('approved', 'assigned')
              AND r.ends_at IS NOT NULL
              AND r.ends_at >= NOW()
              AND r.ends_at <= DATE_ADD(NOW(), INTERVAL ? DAY)";

    $params = [$warnDays];
    if (!$isAdmin) {
        $sql .= ' AND r.user_id = ?';
        $params[] = $userId;
    }
    $sql .= ' ORDER BY r.ends_at ASC';

    $stmt = db_query($conn, $sql, $params);
    if ($stmt === false) {
        return [];
    }

    $alerts = [];
    while ($row = db_fetch($stmt)) {
        $ends = $row['ends_at'] instanceof DateTime ? $row['ends_at'] : new DateTime((string) $row['ends_at']);
        $daysLeft = (int) ceil(($ends->getTimestamp() - time()) / 86400);
        if ($daysLeft < 0) {
            $daysLeft = 0;
        }
        $row['days_left'] = $daysLeft;
        $alerts[] = $row;
    }
    return $alerts;
}

/** Admin: reservations expiring within N days (for dashboard summary). */
function fetch_admin_expiring_reservations($conn, int $withinDays = 14): array {
    return fetch_expiry_alerts($conn, null, true);
}

function reservation_status_label(string $status): string {
    $status = strtolower(trim($status));
    $labels = [
        'deleted'   => 'Deleted',
        'cancelled' => 'Cancelled',
        'expired'   => 'Expired',
        'released'  => 'Released',
        'rejected'  => 'Rejected',
        'assigned'  => 'Assigned',
        'approved'  => 'Approved',
        'pending'   => 'Pending',
    ];
    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
}

function reservation_status_class(string $status): string {
    return 'status-' . strtolower(trim($status));
}

/** Statuses that belong in History (not active). */
function is_reservation_history_status(string $status): bool {
    return in_array(strtolower(trim($status)), [
        'released', 'expired', 'rejected', 'cancelled', 'deleted',
    ], true);
}

/**
 * Permanently remove a ended reservation from history (admin only).
 * Only non-active statuses may be deleted.
 */
function delete_reservation_history($conn, int $reservationId): array {
    $stmt = db_query($conn,
        "SELECT id, status FROM Reservations WHERE id = ?",
        [$reservationId]
    );
    if ($stmt === false || !($row = db_fetch($stmt))) {
        return ['ok' => false, 'message' => 'Reservation not found.'];
    }

    $status = strtolower($row['status'] ?? '');
    if (!is_reservation_history_status($status)) {
        return ['ok' => false, 'message' => 'Only ended reservations in History can be deleted. Active or pending rentals cannot.'];
    }

    $stmtLocker = db_query($conn, "SELECT locker_id FROM Reservations WHERE id = ?", [$reservationId]);
    $lockerId = null;
    if ($stmtLocker && ($lr = db_fetch($stmtLocker))) {
        $lockerId = (int) ($lr['locker_id'] ?? 0);
    }

    $deleted = db_query($conn, "DELETE FROM Reservations WHERE id = ?", [$reservationId]);
    if ($deleted === false) {
        return ['ok' => false, 'message' => 'Failed to delete reservation record.'];
    }

    if ($lockerId) {
        sync_locker_availability($conn, $lockerId);
    }

    return ['ok' => true, 'message' => 'Reservation removed from history.'];
}

/** Cancel pending reservation (student); kept in history as cancelled. */
function cancel_pending_reservation($conn, int $reservationId, int $userId): bool {
    $stmt = db_query($conn,
        "SELECT id, locker_id, status FROM Reservations WHERE id = ? AND user_id = ?",
        [$reservationId, $userId]
    );
    if ($stmt === false || !($row = db_fetch($stmt))) {
        return false;
    }
    if (strtolower($row['status'] ?? '') !== 'pending') {
        return false;
    }

    db_query($conn,
        "UPDATE Reservations SET status = 'cancelled', released_at = NOW() WHERE id = ?",
        [$reservationId]
    );
    sync_locker_availability($conn, (int) $row['locker_id']);
    return true;
}

/** Queue expiry warnings as popups (requires includes/popup_alerts.php). */
function queue_expiry_alert_popups(array $alerts, bool $isAdmin): void {
    if (empty($alerts) || !function_exists('popup_add')) {
        return;
    }
    foreach ($alerts as $a) {
        $name = trim(($a['firstName'] ?? '') . ' ' . ($a['lastName'] ?? ''));
        $ends = format_reservation_date($a['ends_at'] ?? null);
        $days = (int) ($a['days_left'] ?? 0);
        $locker = ($a['department'] ?? '') . ' · F' . ($a['floor'] ?? '') . ' · L' . ($a['locker_id'] ?? '');
        if ($isAdmin) {
            $who = ($a['studentId'] ?? '') . ($name ? " ($name)" : '');
            $msg = "Expiring soon: $who — $locker ends $ends ($days day" . ($days === 1 ? '' : 's') . ' left).';
        } else {
            $msg = "Your locker ends soon: $locker — ends $ends ($days day" . ($days === 1 ? '' : 's') . ' left).';
        }
        popup_add('warning', $msg);
    }
}

/** @deprecated Use queue_expiry_alert_popups() — kept for existing call sites. */
function render_expiry_alerts(array $alerts, bool $isAdmin): void {
    if (!function_exists('popup_add')) {
        require_once __DIR__ . '/popup_alerts.php';
    }
    queue_expiry_alert_popups($alerts, $isAdmin);
}
