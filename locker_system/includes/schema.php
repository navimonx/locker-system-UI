<?php

/**
 * Bring an existing locker_system database up to the LockerFlow workflow schema
 * (roles, payment fields, notifications, transactions) without a full re-import.
 */
function ensure_lockerflow_schema($conn): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $roleLen = 10;
    $stmt = db_query($conn,
        "SELECT CHARACTER_MAXIMUM_LENGTH AS len FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Users' AND COLUMN_NAME = 'role' LIMIT 1"
    );
    if ($stmt && ($row = db_fetch($stmt))) {
        $roleLen = (int) ($row['len'] ?? 10);
    }
    if ($roleLen < 20) {
        db_query($conn, "ALTER TABLE Users MODIFY role VARCHAR(20) NOT NULL DEFAULT 'student'");
    }

    $addedPayment = false;
    $reservationCols = [
        'payment_frequency' => "VARCHAR(20) NOT NULL DEFAULT 'full'",
        'amount_due'        => "DECIMAL(10,2) NOT NULL DEFAULT 0",
        'payment_status'    => "VARCHAR(20) NOT NULL DEFAULT 'not-claimed'",
        'payment_verified'  => "TINYINT(1) NOT NULL DEFAULT 0",
        'remarks'           => "VARCHAR(255) NULL",
    ];
    foreach ($reservationCols as $column => $definition) {
        if (!db_column_exists($conn, 'Reservations', $column)) {
            db_query($conn, "ALTER TABLE Reservations ADD COLUMN `$column` $definition");
            if ($column === 'payment_verified') {
                $addedPayment = true;
            }
        }
    }

    ensure_reservation_status_check($conn);

    if ($addedPayment) {
        db_query($conn,
            "UPDATE Reservations
             SET status = 'assigned', payment_status = 'verified', payment_verified = 1,
                 remarks = COALESCE(NULLIF(remarks, ''), 'Migrated from previous approved occupancy.')
             WHERE status = 'approved'"
        );
    }

    db_query($conn, "
        CREATE TABLE IF NOT EXISTS `Notifications` (
          `id`         INT          NOT NULL AUTO_INCREMENT,
          `user_id`    INT          NOT NULL,
          `message`    VARCHAR(500) NOT NULL,
          `read_flag`  TINYINT(1)   NOT NULL DEFAULT 0,
          `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `IX_Notifications_user` (`user_id`, `read_flag`),
          CONSTRAINT `FK_Notifications_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db_query($conn, "
        CREATE TABLE IF NOT EXISTS `Transactions` (
          `id`         INT          NOT NULL AUTO_INCREMENT,
          `type`       VARCHAR(40)  NOT NULL,
          `user_id`    INT          NULL,
          `amount`     DECIMAL(10,2) NOT NULL DEFAULT 0,
          `status`     VARCHAR(20)  NOT NULL DEFAULT 'completed',
          `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `IX_Transactions_user` (`user_id`),
          CONSTRAINT `FK_Transactions_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmtReg = db_query($conn, "SELECT id FROM Users WHERE studentId = ? LIMIT 1", ['registrar@plv.edu.ph']);
    if (!$stmtReg || !db_fetch($stmtReg)) {
        db_query($conn,
            "INSERT INTO Users (studentId, lastName, middleName, firstName, password, role, email)
             VALUES (?, 'One', NULL, 'Registrar', 'registrar123', 'registrar', ?)",
            ['registrar@plv.edu.ph', 'registrar@plv.edu.ph']
        );
    }
}

function ensure_reservation_status_check($conn): void {
    $stmt = db_query($conn,
        "SELECT CONSTRAINT_NAME AS name FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'Reservations'
           AND CONSTRAINT_TYPE = 'CHECK'"
    );
    $names = [];
    while ($stmt && ($row = db_fetch($stmt))) {
        $names[] = (string) ($row['name'] ?? '');
    }

    foreach ($names as $name) {
        if ($name === '') {
            continue;
        }
        db_query($conn, "ALTER TABLE Reservations DROP CHECK `$name`");
        db_query($conn, "ALTER TABLE Reservations DROP CONSTRAINT `$name`");
    }

    db_query($conn,
        "ALTER TABLE Reservations
         ADD CONSTRAINT CK_Reservations_status CHECK (`status` IN
           ('pending', 'approved', 'assigned', 'rejected', 'released', 'expired', 'cancelled', 'deleted'))"
    );
}
