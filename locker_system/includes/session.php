<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$name        = $_SESSION['name']       ?? null;
$studentId   = $_SESSION['studentId'] ?? null;
$userRole    = $_SESSION['role']      ?? null;
$loggedIn    = isset($_SESSION['user_id']) || isset($_SESSION['studentId']);
$displayName  = $name ?: $studentId;
$roleNorm     = strtolower(trim((string) ($userRole ?? '')));
$isAdmin      = in_array($roleNorm, ['admin', 'superadmin'], true);
$isRegistrar  = $roleNorm === 'registrar';
$isStaff      = $isAdmin || $isRegistrar;
