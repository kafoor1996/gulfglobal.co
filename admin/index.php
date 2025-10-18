<?php
/**
 * Admin Panel Entry Point
 * Redirects users based on login status
 */

session_start();

// Check if admin is logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // User is logged in, redirect to dashboard
    header('Location: /admin/dashboard.php');
    exit();
} else {
    // User is not logged in, redirect to login
    header('Location: /admin/login.php');
    exit();
}
?>
