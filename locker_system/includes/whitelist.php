<?php

require_once __DIR__ . '/user_profile.php';

function is_valid_student_id(string $studentId): bool {
    return (bool) preg_match('/^\d{2}-\d{4}$/', trim($studentId));
}

function student_account_exists($conn, string $studentId): bool {
    $stmt = db_query($conn,
        "SELECT id
         FROM Users
         WHERE studentId = ?
           AND LOWER(TRIM(COALESCE(role, ''))) <> 'admin'
           AND password IS NOT NULL
           AND TRIM(COALESCE(password, '')) <> ''
         LIMIT 1",
        [trim($studentId)]
    );
    return (bool) db_fetch($stmt);
}

function is_student_id_whitelisted($conn, string $studentId): bool {
    $stmt = db_query($conn,
        "SELECT id
         FROM AllowedStudentIds
         WHERE studentId = ?
           AND claimed_at IS NULL
         LIMIT 1",
        [trim($studentId)]
    );
    return (bool) db_fetch($stmt);
}

function can_student_signup($conn, string $studentId): bool {
    $studentId = trim($studentId);
    return is_valid_student_id($studentId)
        && !student_account_exists($conn, $studentId)
        && is_student_id_whitelisted($conn, $studentId);
}

function add_student_ids_to_whitelist($conn, array $ids, ?int $addedBy = null): array {
    $result = ['added' => 0, 'skipped' => 0, 'errors' => []];

    foreach ($ids as $raw) {
        $id = trim((string) $raw);
        if ($id === '') {
            continue;
        }

        if (!is_valid_student_id($id)) {
            $result['errors'][] = "$id - invalid format (use XX-XXXX)";
            continue;
        }

        if (student_account_exists($conn, $id)) {
            $result['skipped']++;
            $result['errors'][] = "$id - already has an active account";
            continue;
        }

        $existing = db_query($conn,
            "SELECT id, claimed_at FROM AllowedStudentIds WHERE studentId = ? LIMIT 1",
            [$id]
        );
        $row = db_fetch($existing);
        if ($row) {
            if ($row['claimed_at'] === null) {
                $result['skipped']++;
            } else {
                $result['errors'][] = "$id - was claimed previously; contact IT to re-add";
            }
            continue;
        }

        $insert = db_query($conn,
            "INSERT INTO AllowedStudentIds (studentId, added_at, added_by, claimed_at, user_id)
             VALUES (?, NOW(), ?, NULL, NULL)",
            [$id, $addedBy]
        );

        if ($insert === false) {
            $result['errors'][] = "$id - database error: " . db_last_error_message();
        } else {
            $result['added']++;
        }
    }

    return $result;
}

function claim_student_account(
    $conn,
    string $studentId,
    string $firstName,
    string $middleName,
    string $lastName,
    string $course,
    string $contact,
    string $email,
    string $password
): array {
    $studentId = trim($studentId);

    if (!is_valid_student_id($studentId)) {
        return ['ok' => false, 'message' => 'Invalid Student Number.'];
    }

    if (student_account_exists($conn, $studentId)) {
        return ['ok' => false, 'message' => 'That Student Number already has an account.'];
    }

    try {
        db_begin($conn);

        $allowed = db_query($conn,
            "SELECT id
             FROM AllowedStudentIds
             WHERE studentId = ?
               AND claimed_at IS NULL
             LIMIT 1
             FOR UPDATE",
            [$studentId]
        );
        $allowedRow = db_fetch($allowed);
        if (!$allowedRow) {
            throw new RuntimeException('Student Number is no longer on the approved list.');
        }

        $insert = db_query($conn,
            "INSERT INTO Users
                (studentId, lastName, middleName, firstName, password, role, course, contact, email)
             VALUES
                (?, ?, ?, ?, ?, 'student', ?, ?, ?)",
            [$studentId, $lastName, $middleName, $firstName, app_hash_password($password), $course, $contact, $email]
        );
        if ($insert === false) {
            throw new RuntimeException(db_last_error_message());
        }

        $newUserId = db_last_insert_id($conn);
        if ($newUserId <= 0) {
            throw new RuntimeException('Account was inserted but the new user ID could not be read.');
        }

        $claim = db_query($conn,
            "UPDATE AllowedStudentIds
             SET claimed_at = NOW(), user_id = ?
             WHERE id = ?",
            [$newUserId, (int) $allowedRow['id']]
        );
        if ($claim === false) {
            throw new RuntimeException(db_last_error_message());
        }

        db_commit($conn);
        return ['ok' => true, 'message' => 'Account created successfully! You can now log in.'];
    } catch (Throwable $e) {
        db_rollback($conn);
        $GLOBALS['db_last_error'] = $e->getMessage();
        error_log('Student signup failed: ' . $e->getMessage());
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}
