<?php
/**
 * Logout Script
 * Destroys the current user session and redirects to the index page.
 */
session_start(); // Start the PHP session

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the index page
header("Location: index.html");
exit();
?>
