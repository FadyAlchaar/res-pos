<?php
// Admin authentication check for all admin pages
require_once dirname(__DIR__, 2) . '/config/config.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}
?>