<?php

/** Profile columns available on Users. */
function users_profile_columns($conn): array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [
        'course'  => db_column_exists($conn, 'Users', 'course'),
        'contact' => db_column_exists($conn, 'Users', 'contact'),
        'email'   => db_column_exists($conn, 'Users', 'email'),
    ];
    return $cache;
}

function fetch_user_profile($conn, int $userId): ?array {
    $cols = users_profile_columns($conn);
    $fields = ['id', 'studentId', 'firstName', 'lastName', 'role'];
    if ($cols['course'])  $fields[] = 'course';
    if ($cols['contact']) $fields[] = 'contact';
    if ($cols['email'])   $fields[] = 'email';

    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM Users WHERE id = ?';
    $stmt = db_query($conn, $sql, [$userId]);
    if ($stmt === false || !($row = db_fetch($stmt))) {
        return null;
    }
    return $row;
}

/**
 * Update contact and email (not course). Returns ['ok' => bool, 'message' => string]
 */
function update_user_contact_email($conn, int $userId, string $contact, string $email): array {
    $cols = users_profile_columns($conn);
    if (!$cols['contact'] && !$cols['email']) {
        return ['ok' => false, 'message' => 'Profile fields are not available. Re-import locker_system.sql in phpMyAdmin.'];
    }

    $contact = trim($contact);
    $email   = trim($email);

    if ($contact === '' && $email === '') {
        return ['ok' => false, 'message' => 'Enter a contact number or email.'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'message' => 'Please enter a valid email address.'];
    }

    $setParts = [];
    $params   = [];
    if ($cols['contact']) {
        $setParts[] = 'contact=?';
        $params[]   = $contact;
    }
    if ($cols['email']) {
        $setParts[] = 'email=?';
        $params[]   = $email;
    }
    $params[] = $userId;

    $ok = db_query($conn, 'UPDATE Users SET ' . implode(', ', $setParts) . ' WHERE id = ?', $params);
    if ($ok === false) {
        return ['ok' => false, 'message' => 'Failed to update profile.'];
    }
    return ['ok' => true, 'message' => 'Contact details updated.'];
}
