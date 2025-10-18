<?php
session_start();
require_once 'includes/auth.php';

// Logout using auth system
$auth->logout();
header('Location: login.php');
exit();
?>
