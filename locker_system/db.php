<?php
/*
 * SecureLocker — database connection (MySQL / MariaDB).
 *
 * Works with XAMPP, WAMP, Laragon, MAMP and ordinary PHP web hosting (cPanel etc.).
 * Import locker_system.sql in phpMyAdmin first, then edit the four settings below
 * (or set DB_HOST / DB_NAME / DB_USER / DB_PASS as environment variables).
 *
 * XAMPP defaults:  host localhost, user root, empty password.
 * Shared hosting:  use the database name, user and password from your hosting panel
 *                  (they are usually prefixed, e.g. "cpuser_locker_system").
 */

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'locker_system';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = (getenv('DB_PASS') === false) ? '' : getenv('DB_PASS');

// Keep PHP and MySQL on the same clock (reservation end dates depend on it).
date_default_timezone_set('Asia/Manila');
$DB_TIMEZONE = '+08:00';

$host    = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = PHP_SAPI === 'cli'
    || stripos($host, 'localhost') === 0
    || strpos($host, '127.0.0.1') === 0
    || strpos($host, '[::1]') === 0;

if ($isLocal) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

if (!extension_loaded('mysqli')) {
    die('The PHP mysqli extension is not enabled. In XAMPP it is on by default; on other servers enable it in php.ini.');
}

// PHP 8.1+ throws exceptions from mysqli by default; we handle errors ourselves.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 8);
// Return INT columns as PHP ints (not strings) for plain queries as well.
$conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

if (!@$conn->real_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME)) {
    $msg = $conn->connect_error ?: 'Unknown connection error.';
    error_log('Database connection failed: ' . $msg);
    die('Database connection failed: ' . htmlspecialchars($msg)
        . '<br><br>Check the settings at the top of <code>db.php</code> and make sure you imported '
        . '<code>locker_system.sql</code> into phpMyAdmin.');
}

$conn->set_charset('utf8mb4');
$conn->query("SET time_zone = '" . $DB_TIMEZONE . "'");

$GLOBALS['db_last_error']     = null;
$GLOBALS['db_last_insert_id'] = 0;

// ---- Query helpers ------------------------------------------------------

/**
 * Run a query. Use ? placeholders and pass values in $params.
 * Returns a mysqli_result for SELECT, true for INSERT/UPDATE/DELETE, false on error.
 */
function db_query($conn, string $sql, array $params = []) {
    $GLOBALS['db_last_error']     = null;
    $GLOBALS['db_last_insert_id'] = 0;

    try {
        if (empty($params)) {
            $result = $conn->query($sql);
            if ($result === false) {
                return db_fail($conn->error, $sql);
            }
            $GLOBALS['db_last_insert_id'] = (int) $conn->insert_id;
            return $result;
        }

        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return db_fail($conn->error, $sql);
        }

        $types  = '';
        $values = [];
        foreach ($params as $p) {
            if ($p instanceof DateTimeInterface) {
                $p = $p->format('Y-m-d H:i:s');
            } elseif (is_bool($p)) {
                $p = (int) $p;
            }
            if (is_int($p)) {
                $types .= 'i';
            } elseif (is_float($p)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $p;
        }
        $stmt->bind_param($types, ...$values);

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            return db_fail($err, $sql);
        }

        $GLOBALS['db_last_insert_id'] = (int) $stmt->insert_id;
        $result = $stmt->get_result();   // false for statements that return no rows
        $stmt->close();
        return $result === false ? true : $result;
    } catch (Throwable $e) {
        return db_fail($e->getMessage(), $sql);
    }
}

function db_fail(string $message, string $sql) {
    $GLOBALS['db_last_error'] = $message !== '' ? $message : 'Unknown database error.';
    error_log('Database query failed: ' . $GLOBALS['db_last_error'] . ' SQL: ' . $sql);
    return false;
}

/** Fetch the next row as an associative array (or null). Date columns become DateTime objects. */
function db_fetch($stmt): ?array {
    if (!($stmt instanceof mysqli_result)) {
        return null;
    }
    $row = $stmt->fetch_assoc();
    if ($row === null || $row === false) {
        return null;
    }

    foreach ($stmt->fetch_fields() as $field) {
        $isDate = in_array($field->type, [MYSQLI_TYPE_DATETIME, MYSQLI_TYPE_TIMESTAMP, MYSQLI_TYPE_DATE], true);
        if ($isDate && isset($row[$field->name]) && $row[$field->name] !== '') {
            try {
                $row[$field->name] = new DateTime($row[$field->name]);
            } catch (Throwable $e) {
                $row[$field->name] = null;   // e.g. 0000-00-00
            }
        }
    }
    return $row;
}

function db_fetch_all($stmt): array {
    $rows = [];
    while ($row = db_fetch($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}

function db_has_rows($stmt): bool {
    return $stmt instanceof mysqli_result && $stmt->num_rows > 0;
}

function db_begin($conn): bool {
    return $conn->begin_transaction();
}

function db_commit($conn): bool {
    return $conn->commit();
}

function db_rollback($conn): bool {
    return $conn->rollback();
}

function db_last_insert_id($conn): int {
    return (int) ($GLOBALS['db_last_insert_id'] ?: $conn->insert_id);
}

function db_last_error_message(): string {
    return (string) ($GLOBALS['db_last_error'] ?? 'Unknown database error.');
}

function db_table_exists($conn, string $table): bool {
    static $cache = [];
    if (!array_key_exists($table, $cache)) {
        $stmt = db_query($conn,
            "SELECT 1 AS ok FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1",
            [$table]
        );
        $cache[$table] = (bool) db_fetch($stmt);
    }
    return $cache[$table];
}

function db_column_exists($conn, string $table, string $column): bool {
    static $cache = [];
    $key = strtolower($table . '.' . $column);
    if (!array_key_exists($key, $cache)) {
        $stmt = db_query($conn,
            "SELECT 1 AS ok FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1",
            [$table, $column]
        );
        $cache[$key] = (bool) db_fetch($stmt);
    }
    return $cache[$key];
}

require_once __DIR__ . '/includes/schema.php';
ensure_lockerflow_schema($conn);

// ---- Passwords ----------------------------------------------------------
// New passwords are stored hashed. Older plain-text passwords still work and are
// upgraded to a hash automatically the next time that person logs in.

function app_hash_password(string $plain): string {
    return password_hash($plain, PASSWORD_DEFAULT);
}

function app_password_is_hashed(?string $stored): bool {
    $info = password_get_info((string) $stored);
    return !empty($info['algo']);
}

function app_password_matches(string $plain, ?string $stored): bool {
    $stored = (string) $stored;
    if ($stored === '') {
        return false;
    }
    if (app_password_is_hashed($stored)) {
        return password_verify($plain, $stored);
    }
    return hash_equals($stored, $plain);
}
