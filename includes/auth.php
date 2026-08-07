<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Redirect to login if nobody is logged in. */
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

/** Redirect to login unless the logged-in user has this exact role. */
function require_role(string $role): void
{
    require_login();
    if ($_SESSION['role'] !== $role) {
        header('Location: /login.php');
        exit;
    }
}

/** Small helper to grab the current user's info. */
function current_user(): array
{
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? null,
        'role' => $_SESSION['role'] ?? null,
    ];
}

/** Escape output for HTML — use this around every variable printed into a page. */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
