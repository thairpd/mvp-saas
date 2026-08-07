<?php
// Database connection settings — update these to match your hosting environment.
$db_host = 'localhost';
$db_name = 'u986739332_dashboard';
$db_user = 'u986739332_dashboard';
$db_pass = 'zOxs6|N?F#9';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
