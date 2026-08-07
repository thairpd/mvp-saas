<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config.php';

// Already logged in? send them straight to their dashboard.
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'manager' ? 'manager/dashboard.php' : 'employee/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];

        header('Location: ' . ($user['role'] === 'manager' ? 'manager/dashboard.php' : 'employee/dashboard.php'));
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login — Task Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrap">
        <div class="card login-box">
            <h1>Task Dashboard</h1>
            <?php if ($error): ?>
                <div class="error"><?= h($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Log In</button>
            </form>
        </div>
    </div>
</body>
</html>
