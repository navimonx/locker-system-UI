<?php

require_once __DIR__ . '/user_profile.php';

/** Search field options for reservation lists. */
function reservation_search_fields(): array {
    return [
        'all'        => 'All fields',
        'student_id' => 'Student Number',
        'name'       => 'Name',
        'email'      => 'Email / Account',
        'contact'    => 'Contact Number',
        'duration'   => 'Duration',
        'status'     => 'Status',
        'locker'     => 'Locker / Dept',
    ];
}

function reservation_status_options(): array {
    return [
        ''          => 'Any status',
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'released'  => 'Released',
        'expired'   => 'Expired',
        'cancelled' => 'Cancelled',
        'deleted'   => 'Deleted',
    ];
}

function reservation_duration_options(): array {
    return [
        ''              => 'Any duration',
        'Half Semester' => 'Half Semester (2½ months)',
        'Full Semester' => 'Full Semester (5 months)',
        'Full Year'     => 'Full Year (10 months)',
    ];
}

/** Student list search fields. */
function student_search_fields(): array {
    return [
        'all'        => 'All fields',
        'student_id' => 'Student Number',
        'name'       => 'Name',
        'email'      => 'Email / Account',
        'contact'    => 'Contact Number',
        'course'     => 'Course',
    ];
}

/**
 * Fetch reservations with list filter + search.
 * $opts: filter (all|active|history), search_by, q, status, duration
 */
function fetch_reservations_filtered($conn, array $opts = []): array {
    $filter    = $opts['filter'] ?? 'all';
    $searchBy  = $opts['search_by'] ?? 'all';
    $q         = trim($opts['q'] ?? '');
    $statusF   = strtolower(trim($opts['status'] ?? ''));
    $durationF = trim($opts['duration'] ?? '');

    $profileCols = users_profile_columns($conn);
    $emailCol    = $profileCols['email'] ? ', u.email' : '';
    $contactCol  = $profileCols['contact'] ? ', u.contact' : '';
    $courseCol   = $profileCols['course'] ? ', u.course' : '';

    $sql = "SELECT r.id AS reservation_id, r.status, r.duration, r.reserved_at, r.ends_at,
                   r.released_at, r.course, r.yearLevel,
                   u.id AS user_id, u.studentId, u.firstName, u.lastName
                   $emailCol $contactCol $courseCol,
                   l.id AS locker_id, l.department, l.floor
            FROM Reservations r
            INNER JOIN Users u ON r.user_id = u.id
            INNER JOIN Lockers l ON r.locker_id = l.id
            WHERE 1=1";

    $params = [];

    if ($filter === 'active') {
        $sql .= " AND r.status IN ('pending', 'approved')";
    } elseif ($filter === 'history') {
        $sql .= " AND r.status IN ('released', 'expired', 'rejected', 'cancelled', 'deleted')";
    }

    if ($statusF !== '' && array_key_exists($statusF, reservation_status_options())) {
        $sql .= ' AND LOWER(r.status) = ?';
        $params[] = $statusF;
    }

    if ($durationF !== '' && array_key_exists($durationF, reservation_duration_options())) {
        $sql .= ' AND r.duration = ?';
        $params[] = $durationF;
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        switch ($searchBy) {
            case 'student_id':
                $sql .= ' AND u.studentId LIKE ?';
                $params[] = $like;
                break;
            case 'name':
                $sql .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR CONCAT(u.firstName, ' ', u.lastName) LIKE ?)";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                break;
            case 'email':
                if ($profileCols['email']) {
                    $sql .= ' AND u.email LIKE ?';
                    $params[] = $like;
                }
                break;
            case 'contact':
                if ($profileCols['contact']) {
                    $sql .= ' AND (u.contact LIKE ? OR r.contact LIKE ?)';
                    $params[] = $like;
                    $params[] = $like;
                }
                break;
            case 'duration':
                $sql .= ' AND r.duration LIKE ?';
                $params[] = $like;
                break;
            case 'status':
                $sql .= ' AND r.status LIKE ?';
                $params[] = $like;
                break;
            case 'locker':
                $sql .= " AND (CAST(l.id AS CHAR) LIKE ? OR l.department LIKE ? OR CAST(l.floor AS CHAR) LIKE ?)";
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
                break;
            default:
                $sql .= " AND (u.studentId LIKE ? OR u.firstName LIKE ? OR u.lastName LIKE ? OR r.duration LIKE ? OR r.status LIKE ? OR l.department LIKE ? OR CAST(l.id AS CHAR) LIKE ? OR r.contact LIKE ?";
                $params = array_merge($params, array_fill(0, 8, $like));
                if ($profileCols['email']) {
                    $sql .= ' OR u.email LIKE ?';
                    $params[] = $like;
                }
                if ($profileCols['contact']) {
                    $sql .= ' OR u.contact LIKE ?';
                    $params[] = $like;
                }
                $sql .= ')';
                break;
        }
    }

    $sql .= ' ORDER BY r.reserved_at DESC';

    $stmt = empty($params)
        ? db_query($conn, $sql)
        : db_query($conn, $sql, $params);

    if ($stmt === false) {
        return [];
    }
    $rows = [];
    while ($row = db_fetch($stmt)) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Filter registered students by search criteria.
 */
function filter_students_list(array $students, array $opts = []): array {
    $searchBy = $opts['search_by'] ?? 'all';
    $q        = strtolower(trim($opts['q'] ?? ''));
    if ($q === '') {
        return $students;
    }

    return array_values(array_filter($students, function ($s) use ($searchBy, $q) {
        $id      = strtolower($s['studentId'] ?? '');
        $name    = strtolower(trim(($s['firstName'] ?? '') . ' ' . ($s['lastName'] ?? '')));
        $email   = strtolower($s['email'] ?? '');
        $contact = strtolower($s['contact'] ?? '');
        $course  = strtolower($s['course'] ?? '');

        switch ($searchBy) {
            case 'student_id':
                return strpos($id, $q) !== false;
            case 'name':
                return strpos($name, $q) !== false;
            case 'email':
                return strpos($email, $q) !== false;
            case 'contact':
                return strpos($contact, $q) !== false;
            case 'course':
                return strpos($course, $q) !== false;
            default:
                return strpos($id, $q) !== false
                    || strpos($name, $q) !== false
                    || strpos($email, $q) !== false
                    || strpos($contact, $q) !== false
                    || strpos($course, $q) !== false;
        }
    }));
}

/** Render admin search/filter form for reservations. */
function render_reservation_search_form(string $actionUrl, array $current, string $listFilter = 'all'): void {
    $fields    = reservation_search_fields();
    $statuses  = reservation_status_options();
    $durations = reservation_duration_options();
    $searchBy  = $current['search_by'] ?? 'all';
    $q         = $current['q'] ?? '';
    $status    = $current['status'] ?? '';
    $duration  = $current['duration'] ?? '';
    ?>
    <form method="GET" action="<?= htmlspecialchars($actionUrl) ?>" class="admin-search-form">
      <?php if (strpos($actionUrl, 'admin_records') !== false): ?>
        <input type="hidden" name="tab" value="reservations">
      <?php endif; ?>
      <input type="hidden" name="filter" value="<?= htmlspecialchars($listFilter) ?>">

      <div class="admin-search-row">
        <div class="form-group admin-search-field">
          <label for="search_by">Search in</label>
          <select name="search_by" id="search_by">
            <?php foreach ($fields as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $searchBy === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group admin-search-field admin-search-field--grow">
          <label for="q">Search</label>
          <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Type to search…">
        </div>
      </div>

      <div class="admin-search-row">
        <div class="form-group admin-search-field">
          <label for="status">Status</label>
          <select name="status" id="status">
            <?php foreach ($statuses as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $status === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group admin-search-field">
          <label for="duration">Duration</label>
          <select name="duration" id="duration">
            <?php foreach ($durations as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $duration === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="admin-search-actions">
          <button type="submit" class="btn-primary btn-sm">Search</button>
          <a href="<?= htmlspecialchars($actionUrl) ?>?<?= strpos($actionUrl, 'admin_records') !== false ? 'tab=reservations&amp;' : '' ?>filter=<?= urlencode($listFilter) ?>" class="btn-ghost btn-sm">Clear</a>
        </div>
      </div>
    </form>
    <?php
}

function render_student_search_form(string $actionUrl, array $current): void {
    $fields   = student_search_fields();
    $searchBy = $current['search_by'] ?? 'all';
    $q        = $current['q'] ?? '';
    ?>
    <form method="GET" action="<?= htmlspecialchars($actionUrl) ?>" class="admin-search-form">
      <input type="hidden" name="tab" value="students">
      <div class="admin-search-row">
        <div class="form-group admin-search-field">
          <label for="search_by">Search in</label>
          <select name="search_by" id="search_by">
            <?php foreach ($fields as $val => $label): ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $searchBy === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group admin-search-field admin-search-field--grow">
          <label for="q">Search</label>
          <input type="text" id="q" name="q" value="<?= htmlspecialchars($q) ?>"
                 placeholder="Student number, name, email, contact…">
        </div>
        <div class="admin-search-actions">
          <button type="submit" class="btn-primary btn-sm">Search</button>
          <a href="admin_records.php?tab=students" class="btn-ghost btn-sm">Clear</a>
        </div>
      </div>
    </form>
    <?php
}

function admin_search_query_params(): array {
    return [
        'search_by' => $_GET['search_by'] ?? 'all',
        'q'         => $_GET['q'] ?? '',
        'status'    => $_GET['status'] ?? '',
        'duration'  => $_GET['duration'] ?? '',
    ];
}

function admin_search_url_suffix(array $params): string {
    $parts = [];
    foreach (['search_by', 'q', 'status', 'duration'] as $k) {
        if (!empty($params[$k])) {
            $parts[] = $k . '=' . urlencode($params[$k]);
        }
    }
    return $parts ? '&' . implode('&', $parts) : '';
}
