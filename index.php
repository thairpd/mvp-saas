<?php
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'manager' ? 'manager/dashboard.php' : 'employee/dashboard.php'));
} else {
    header('Location: login.php');
}
exit;
