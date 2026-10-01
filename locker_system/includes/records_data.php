<?php

/** Sync whitelist rows with accounts that already exist. */
function sync_whitelist_claimed($conn): void {
    if (!db_table_exists($conn, 'AllowedStudentIds')) {
        return;
    }
    db_query($conn,
        "UPDATE AllowedStudentIds a
         INNER JOIN Users u ON a.studentId = u.studentId
         SET a.claimed_at = COALESCE(a.claimed_at, NOW()),
             a.user_id    = u.id
         WHERE u.password IS NOT NULL AND TRIM(IFNULL(u.password, '')) <> ''"
    );
}

function is_admin_user_row(array $row): bool {
    $role = strtolower(trim($row['role'] ?? ''));
    $id   = trim($row['studentId'] ?? '');
    return $role === 'admin' || strpos($id, '@') !== false;
}

function has_active_password(array $row): bool {
    return strlen(trim($row['password'] ?? '')) > 0;
}

/** Normalize Users row keys (camelCase or snake_case). */
function normalize_user_row(array $row): array {
    $map = [
        'studentId'   => ['studentId', 'student_id'],
        'firstName'   => ['firstName', 'first_name'],
        'middleName'  => ['middleName', 'middle_name'],
        'lastName'    => ['lastName', 'last_name'],
        'role'        => ['role'],
        'password'    => ['password', 'password_hash'],
    ];
    $out = ['id' => $row['id'] ?? null];
    foreach ($map as $canonical => $keys) {
        foreach ($keys as $k) {
            if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
                $out[$canonical] = $row[$k];
                break;
            }
        }
    }
    return $out;
}

/** All student accounts that can log in. */
function fetch_registered_students($conn): array {
    sync_whitelist_claimed($conn);

    $sql = "SELECT * FROM Users ORDER BY studentId";
    $stmt = db_query($conn, $sql);
    if ($stmt === false) {
        return [];
    }

    $students = [];
    while ($row = db_fetch($stmt)) {
        $row = normalize_user_row($row);
        if (is_admin_user_row($row) || !has_active_password($row)) {
            continue;
        }
        $students[] = $row;
    }

    usort($students, fn($a, $b) => strcmp($a['lastName'] ?? '', $b['lastName'] ?? ''));

    // Attach course/contact when columns exist
    $hasCourse  = db_column_exists($conn, 'Users', 'course');
    $hasContact = db_column_exists($conn, 'Users', 'contact');
    $hasEmail   = db_column_exists($conn, 'Users', 'email');
    if ($hasCourse || $hasContact || $hasEmail) {
        $fields = 'id' . ($hasCourse ? ', course' : '') . ($hasContact ? ', contact' : '') . ($hasEmail ? ', email' : '');
        $stmt2 = db_query($conn, "SELECT $fields FROM Users");
        $meta = [];
        if ($stmt2) {
            while ($r = db_fetch($stmt2)) {
                $meta[$r['id']] = $r;
            }
        }
        foreach ($students as &$s) {
            $m = $meta[$s['id']] ?? [];
            if ($hasCourse)  $s['course']  = $m['course']  ?? null;
            if ($hasContact) $s['contact'] = $m['contact'] ?? null;
            if ($hasEmail)   $s['email']   = $m['email']   ?? null;
        }
        unset($s);
    }

    return $students;
}

function fetch_pending_student_ids($conn): array {
    sync_whitelist_claimed($conn);

    if (!db_table_exists($conn, 'AllowedStudentIds')) {
        return ['rows' => [], 'tableMissing' => true];
    }

    $pending = [];
    $stmt = db_query($conn,
        "SELECT id, studentId, added_at FROM AllowedStudentIds
         WHERE claimed_at IS NULL ORDER BY added_at DESC"
    );
    if ($stmt) {
        while ($row = db_fetch($stmt)) {
            $pending[] = $row;
        }
    }
    return ['rows' => $pending, 'tableMissing' => false];
}

function fetch_admin_accounts($conn): array {
    $stmt = db_query($conn, "SELECT * FROM Users ORDER BY studentId");
    if ($stmt === false) {
        return [];
    }

    $admins = [];
    while ($row = db_fetch($stmt)) {
        $row = normalize_user_row($row);
        if (is_admin_user_row($row) && has_active_password($row)) {
            $admins[] = $row;
        }
    }
    usort($admins, fn($a, $b) => strcmp($a['lastName'] ?? '', $b['lastName'] ?? ''));
    return $admins;
}

/**
 * Delete a user account and clean up related data.
 * Returns ['ok' => bool, 'message' => string]
 */
function delete_user_account($conn, int $targetUserId, ?int $currentAdminId): array {
    if ($targetUserId <= 0) {
        return ['ok' => false, 'message' => 'Invalid account.'];
    }

    if ($currentAdminId && $targetUserId === $currentAdminId) {
        return ['ok' => false, 'message' => 'You cannot delete your own account while logged in.'];
    }

    $stmt = db_query($conn, "SELECT * FROM Users WHERE id = ?", [$targetUserId]);
    if ($stmt === false || !($user = db_fetch($stmt))) {
        return ['ok' => false, 'message' => 'Account not found.'];
    }

    $user = normalize_user_row($user);
    $studentId = trim($user['studentId'] ?? '');
    $isAdmin   = is_admin_user_row($user);

    if ($isAdmin) {
        $admins = fetch_admin_accounts($conn);
        if (count($admins) <= 1) {
            return ['ok' => false, 'message' => 'Cannot delete the only admin account.'];
        }
    }

    // Free lockers tied to this user's reservations
    db_query($conn,
        "UPDATE Lockers l
         INNER JOIN Reservations r ON r.locker_id = l.id
         SET l.status = 'available', l.owner_id = NULL
         WHERE r.user_id = ?",
        [$targetUserId]
    );
    db_query($conn,
        "UPDATE Lockers SET status = 'available', owner_id = NULL WHERE owner_id = ?",
        [$targetUserId]
    );
    db_query($conn, "DELETE FROM Reservations WHERE user_id = ?", [$targetUserId]);

    // Whitelist cleanup (table may not exist)
    if (db_table_exists($conn, 'AllowedStudentIds')) {
        db_query($conn, "UPDATE AllowedStudentIds SET added_by = NULL WHERE added_by = ?", [$targetUserId]);
        db_query($conn, "DELETE FROM AllowedStudentIds WHERE user_id = ?", [$targetUserId]);
        if ($studentId !== '') {
            db_query($conn, "DELETE FROM AllowedStudentIds WHERE studentId = ?", [$studentId]);
        }
    }

    $deleted = db_query($conn, "DELETE FROM Users WHERE id = ?", [$targetUserId]);
    if ($deleted === false) {
        return ['ok' => false, 'message' => 'Failed to delete account. Check for database constraints.'];
    }

    $label = $studentId !== '' ? $studentId : 'Account';
    $type  = $isAdmin ? 'Admin' : 'Student';
    return ['ok' => true, 'message' => "$type account $label deleted. Related reservations were removed and lockers freed."];
}

function fetch_all_reservations($conn, ?string $filter = null): array {
    $sql = "SELECT r.id AS reservation_id, r.status, r.duration, r.reserved_at, r.ends_at,
                   r.released_at, r.course, r.yearLevel,
                   u.id AS user_id, u.studentId, u.firstName, u.lastName,
                   l.id AS locker_id, l.department, l.floor
            FROM Reservations r
            INNER JOIN Users u ON r.user_id = u.id
            INNER JOIN Lockers l ON r.locker_id = l.id";
    if ($filter === 'active') {
        $sql .= " WHERE r.status IN ('pending', 'approved')";
    } elseif ($filter === 'history') {
        $sql .= " WHERE r.status IN ('released', 'expired', 'rejected', 'cancelled', 'deleted')";
    }
    $sql .= " ORDER BY r.reserved_at DESC";

    $stmt = db_query($conn, $sql);
    if ($stmt === false) {
        return [];
    }
    $rows = [];
    while ($row = db_fetch($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}
