<?php

/** Monthly locker fee used by the LockerFlow application workflow. */
define('LOCKER_MONTHLY_RATE', 700);

function duration_to_months(string $duration): int {
    $d = strtolower(trim($duration));
    if (strpos($d, 'year') !== false) {
        return 10;
    }
    if (strpos($d, 'full') !== false) {
        return 5;
    }
    return 3;
}

function compute_amount_due(string $duration): float {
    return duration_to_months($duration) * LOCKER_MONTHLY_RATE;
}

function normalize_payment_frequency(string $value): string {
    $v = strtolower(trim($value));
    return $v === 'monthly' ? 'monthly' : 'full';
}

function payment_frequency_label(string $value): string {
    return normalize_payment_frequency($value) === 'monthly' ? 'Monthly' : 'Full payment';
}

function payment_status_label(string $value): string {
    $labels = [
        'not-claimed' => 'Not claimed',
        'claimed'     => 'Claimed (awaiting verification)',
        'verified'    => 'Verified',
    ];
    $key = strtolower(trim($value));
    return $labels[$key] ?? ucfirst(str_replace('-', ' ', $key));
}

function reservation_active_statuses(): array {
    return ['pending', 'approved', 'assigned'];
}

function add_notification($conn, int $userId, string $message): void {
    if ($userId <= 0 || $message === '' || !db_table_exists($conn, 'Notifications')) {
        return;
    }
    db_query($conn, "INSERT INTO Notifications (user_id, message) VALUES (?, ?)", [$userId, $message]);
}

function notify_staff($conn, string $message): void {
    $stmt = db_query($conn,
        "SELECT id FROM Users WHERE LOWER(role) IN ('admin', 'superadmin', 'registrar')"
    );
    while ($stmt && ($row = db_fetch($stmt))) {
        add_notification($conn, (int) $row['id'], $message);
    }
}

function add_transaction($conn, string $type, ?int $userId, float $amount, string $status = 'completed'): void {
    if (!db_table_exists($conn, 'Transactions')) {
        return;
    }
    db_query($conn,
        "INSERT INTO Transactions (type, user_id, amount, status) VALUES (?, ?, ?, ?)",
        [$type, $userId, $amount, $status]
    );
}

function fetch_notifications($conn, int $userId, int $limit = 10): array {
    if ($userId <= 0 || !db_table_exists($conn, 'Notifications')) {
        return [];
    }
    $stmt = db_query($conn,
        "SELECT id, message, read_flag, created_at
         FROM Notifications WHERE user_id = ?
         ORDER BY created_at DESC LIMIT ?",
        [$userId, $limit]
    );
    return $stmt ? db_fetch_all($stmt) : [];
}

function fetch_staff_notifications($conn, int $limit = 20): array {
    if (!db_table_exists($conn, 'Notifications')) {
        return [];
    }
    $stmt = db_query($conn,
        "SELECT n.id, n.message, n.read_flag, n.created_at, u.studentId, u.firstName, u.lastName
         FROM Notifications n
         INNER JOIN Users u ON u.id = n.user_id
         WHERE LOWER(u.role) IN ('admin', 'superadmin', 'registrar')
         ORDER BY n.created_at DESC LIMIT ?",
        [$limit]
    );
    return $stmt ? db_fetch_all($stmt) : [];
}

function fetch_dashboard_stats($conn): array {
    $count = function (string $sql, array $params = []) use ($conn): int {
        $stmt = db_query($conn, $sql, $params);
        $row  = $stmt ? db_fetch($stmt) : null;
        return (int) ($row['c'] ?? 0);
    };

    $revenue = 0.0;
    if (db_table_exists($conn, 'Transactions')) {
        $stmt = db_query($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM Transactions WHERE status IN ('completed', 'verified', 'approved')");
        $row  = $stmt ? db_fetch($stmt) : null;
        $revenue = (float) ($row['total'] ?? 0);
    }

    return [
        'totalStudents'       => $count("SELECT COUNT(*) AS c FROM Users WHERE LOWER(role) = 'student' AND TRIM(IFNULL(password, '')) <> ''"),
        'totalRegistrars'     => $count("SELECT COUNT(*) AS c FROM Users WHERE LOWER(role) = 'registrar'"),
        'totalLockers'        => $count("SELECT COUNT(*) AS c FROM Lockers"),
        'availableLockers'    => $count("SELECT COUNT(*) AS c FROM Lockers WHERE status = 'available'"),
        'occupiedLockers'     => $count("SELECT COUNT(*) AS c FROM Lockers WHERE status = 'occupied'"),
        'heldLockers'         => $count("SELECT COUNT(*) AS c FROM Lockers WHERE status = 'pending'"),
        'pendingApplications' => $count("SELECT COUNT(*) AS c FROM Reservations WHERE status = 'pending'"),
        'approvedAwaiting'    => $count("SELECT COUNT(*) AS c FROM Reservations WHERE status = 'approved'"),
        'activeAssignments'   => $count("SELECT COUNT(*) AS c FROM Reservations WHERE status = 'assigned'"),
        'totalRevenue'        => $revenue,
    ];
}

function fetch_transaction_history($conn, int $limit = 10): array {
    if (!db_table_exists($conn, 'Transactions')) {
        return [];
    }
    $stmt = db_query($conn,
        "SELECT t.id, t.type, t.amount, t.status, t.created_at, u.studentId, u.firstName, u.lastName
         FROM Transactions t
         LEFT JOIN Users u ON u.id = t.user_id
         ORDER BY t.created_at DESC LIMIT ?",
        [$limit]
    );
    return $stmt ? db_fetch_all($stmt) : [];
}

function fetch_report_breakdown($conn): array {
    $byStatus = [];
    $stmt = db_query($conn, "SELECT status, COUNT(*) AS c FROM Lockers GROUP BY status");
    while ($stmt && ($row = db_fetch($stmt))) {
        $byStatus[(string) $row['status']] = (int) $row['c'];
    }

    $byDept = [];
    $stmt = db_query($conn, "SELECT department, COUNT(*) AS c FROM Lockers GROUP BY department");
    while ($stmt && ($row = db_fetch($stmt))) {
        $byDept[(string) $row['department']] = (int) $row['c'];
    }

    return ['byStatus' => $byStatus, 'byDepartment' => $byDept];
}

function load_reservation_row($conn, int $reservationId): ?array {
    $stmt = db_query($conn,
        "SELECT r.*, u.id AS user_id, u.studentId, u.firstName, u.lastName,
                l.department, l.floor, l.slot
         FROM Reservations r
         INNER JOIN Users u ON r.user_id = u.id
         INNER JOIN Lockers l ON r.locker_id = l.id
         WHERE r.id = ?",
        [$reservationId]
    );
    return $stmt ? db_fetch($stmt) : null;
}

function apply_reservation_action($conn, int $reservationId, string $action, string $remarks = ''): array {
    $row = load_reservation_row($conn, $reservationId);
    if (!$row) {
        return ['ok' => false, 'message' => 'Application not found.'];
    }

    $status  = strtolower((string) ($row['status'] ?? ''));
    $userId  = (int) ($row['user_id'] ?? 0);
    $amount  = (float) ($row['amount_due'] ?? 0);
    $remarks = trim($remarks);

    if ($action === 'approve') {
        if ($status !== 'pending') {
            return ['ok' => false, 'message' => 'Only pending applications can be approved.'];
        }
        $note = $remarks !== '' ? $remarks : 'Approved. Claim your payment, then wait for Registrar verification.';
        db_query($conn, "UPDATE Reservations SET status = 'approved', remarks = ? WHERE id = ?", [$note, $reservationId]);
        add_notification($conn, $userId, 'Your locker reservation has been approved and is awaiting payment claim.');
        add_transaction($conn, 'approval', $userId, $amount, 'approved');
        sync_locker_for_reservation($conn, $reservationId);
        return ['ok' => true, 'message' => 'Application approved. Locker is reserved until payment is verified and assigned.'];
    }

    if ($action === 'reject') {
        if (!in_array($status, ['pending', 'approved'], true)) {
            return ['ok' => false, 'message' => 'This application cannot be rejected.'];
        }
        $note = $remarks !== '' ? $remarks : 'Application rejected.';
        db_query($conn, "UPDATE Reservations SET status = 'rejected', released_at = NOW(), remarks = ? WHERE id = ?", [$note, $reservationId]);
        add_notification($conn, $userId, 'Your locker application was rejected. Please review the remarks and try again.');
        sync_locker_for_reservation($conn, $reservationId);
        return ['ok' => true, 'message' => 'Application rejected and locker released.'];
    }

    if ($action === 'cancel') {
        if (!in_array($status, ['pending', 'approved'], true)) {
            return ['ok' => false, 'message' => 'This application cannot be cancelled.'];
        }
        $note = $remarks !== '' ? $remarks : 'Cancelled by the Registrar.';
        db_query($conn, "UPDATE Reservations SET status = 'cancelled', released_at = NOW(), remarks = ? WHERE id = ?", [$note, $reservationId]);
        add_notification($conn, $userId, 'Your locker application has been cancelled.');
        sync_locker_for_reservation($conn, $reservationId);
        return ['ok' => true, 'message' => 'Application cancelled and locker released.'];
    }

    if ($action === 'verify_payment') {
        $payStatus = strtolower((string) ($row['payment_status'] ?? 'not-claimed'));
        if ($status !== 'approved') {
            return ['ok' => false, 'message' => 'Only approved applications can have payment verified.'];
        }
        if ($payStatus !== 'claimed') {
            return ['ok' => false, 'message' => 'The student must claim payment before it can be verified.'];
        }
        db_query($conn,
            "UPDATE Reservations SET payment_status = 'verified', payment_verified = 1, remarks = ? WHERE id = ?",
            [$remarks !== '' ? $remarks : 'Payment verified by Registrar.', $reservationId]
        );
        add_notification($conn, $userId, 'Your payment has been verified. Final locker assignment can now be completed.');
        add_transaction($conn, 'payment_verification', $userId, $amount, 'verified');
        return ['ok' => true, 'message' => 'Payment verified. You can now assign the locker.'];
    }

    if ($action === 'assign') {
        $verified = (int) ($row['payment_verified'] ?? 0);
        if ($status !== 'approved' || $verified !== 1) {
            return ['ok' => false, 'message' => 'Assignment requires an approved application and verified payment.'];
        }
        $sets   = ["status = 'assigned'", "payment_status = 'verified'", "payment_verified = 1", "remarks = ?"];
        $params = ['Locker assigned and activated.'];
        if (reservation_has_ends_at($conn)) {
            $ends     = compute_reservation_ends_at((string) ($row['duration'] ?? 'Half Semester'));
            $sets[]   = 'ends_at = ?';
            $params[] = $ends->format('Y-m-d H:i:s');
        }
        $params[] = $reservationId;
        db_query($conn, 'UPDATE Reservations SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
        add_notification($conn, $userId, 'Your locker assignment is now active.');
        add_transaction($conn, 'assignment', $userId, $amount, 'completed');
        sync_locker_for_reservation($conn, $reservationId);
        return ['ok' => true, 'message' => 'Locker assigned and marked occupied.'];
    }

    if ($action === 'release') {
        if (!in_array($status, ['assigned', 'approved'], true)) {
            return ['ok' => false, 'message' => 'Only reserved or assigned lockers can be released.'];
        }
        db_query($conn,
            "UPDATE Reservations SET status = 'released', released_at = NOW(), remarks = ? WHERE id = ?",
            [$remarks !== '' ? $remarks : 'Locker released.', $reservationId]
        );
        add_notification($conn, $userId, 'Your locker has been released.');
        sync_locker_for_reservation($conn, $reservationId);
        return ['ok' => true, 'message' => 'Locker released. The record was moved to history.'];
    }

    return ['ok' => false, 'message' => 'Unsupported action.'];
}

function claim_reservation_payment($conn, int $reservationId, int $userId): array {
    $stmt = db_query($conn,
        "SELECT id, status, payment_status FROM Reservations WHERE id = ? AND user_id = ?",
        [$reservationId, $userId]
    );
    $row = $stmt ? db_fetch($stmt) : null;
    if (!$row) {
        return ['ok' => false, 'message' => 'Application not found.'];
    }
    if (strtolower((string) $row['status']) !== 'approved') {
        return ['ok' => false, 'message' => 'Payment can only be claimed after the application is approved.'];
    }
    if (strtolower((string) ($row['payment_status'] ?? '')) === 'verified') {
        return ['ok' => false, 'message' => 'Payment is already verified.'];
    }

    db_query($conn, "UPDATE Reservations SET payment_status = 'claimed' WHERE id = ?", [$reservationId]);
    add_notification($conn, $userId, 'Your physical payment has been claimed and is awaiting Registrar verification.');
    notify_staff($conn, 'Payment claim submitted for application #' . $reservationId . '.');
    return ['ok' => true, 'message' => 'Payment claim recorded. Wait for Registrar verification.'];
}
