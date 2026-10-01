<?php

/** @var list<array{type:string,message:string}> */
$GLOBALS['_app_popup_queue'] = $GLOBALS['_app_popup_queue'] ?? [];

function popup_add(string $type, string $message): void {
    $message = trim($message);
    if ($message === '') {
        return;
    }
    $GLOBALS['_app_popup_queue'][] = [
        'type'    => in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info',
        'message' => $message,
    ];
}

function popup_flash(?string $success, ?string $error = null, ?string $warning = null): void {
    if ($success) {
        popup_add('success', $success);
    }
    if ($error) {
        popup_add('error', $error);
    }
    if ($warning) {
        popup_add('warning', $warning);
    }
}

function popup_consume_query_flash(): void {
    if (!empty($_GET['deleted'])) {
        popup_add('success', 'Reservation removed from history.');
    }
    if (!empty($_GET['err'])) {
        popup_add('error', (string) $_GET['err']);
    }
    if (!empty($_GET['success'])) {
        popup_add('success', (string) $_GET['success']);
    }
    if (!empty($_GET['warning'])) {
        popup_add('warning', (string) $_GET['warning']);
    }

    $code = $_GET['error'] ?? $_GET['msg'] ?? '';
    $map = [
        'duplicate'  => ['warning', 'You already have an active or pending reservation.'],
        'failed'     => ['error', 'Reservation failed. Please try again.'],
        'invalid'    => ['error', 'Invalid request. Please start again from Rentals.'],
        'incomplete' => ['warning', 'Please add your course, contact number, and email in Settings before reserving a locker.'],
        'locker_unavailable' => ['warning', 'That locker is not available. Choose another locker or refresh the floor page.'],
        'locker_invalid'     => ['error', 'Could not find that locker on this floor. Please pick a locker from the grid again.'],
    ];
    if ($code !== '' && isset($map[$code])) {
        popup_add($map[$code][0], $map[$code][1]);
    }
}

function popup_render(): void {
    popup_consume_query_flash();
    $queue = $GLOBALS['_app_popup_queue'] ?? [];
    if (empty($queue)) {
        return;
    }
    $payload = json_encode($queue, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    echo '<div id="app-popup-root" aria-live="polite"></div>';
    echo '<script>window.__APP_POPUPS=' . $payload . ';</script>';
    echo '<script src="includes/popup_alerts.js"></script>';
}
