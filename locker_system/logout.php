<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect back to homepage instead of login
header("Location: index.php");
exit();
?>
