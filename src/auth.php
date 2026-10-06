<?php

require_once __DIR__ . '/db.php';

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function login_user(array $user): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    session_destroy();
}

function current_user(): ?array
{
    start_session();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $stmt = get_pdo()->prepare('SELECT id, full_name, email, role, course, department FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        logout_user();
        return null;
    }

    $cached = $user;
    return $cached;
}

/**
 * Redirects away if the current session isn't logged in as the given role.
 * Call at the top of every protected page, before any output.
 */
function require_role(string $role): array
{
    $user = current_user();

    if (!$user || $user['role'] !== $role) {
        $login_page = $role === 'lecturer' ? '/login_lecturer.php' : '/login_student.php';
        header('Location: ' . $login_page);
        exit;
    }

    return $user;
}

/**
 * Verifies credentials against a specific role and returns the user row on
 * success, or null on failure (wrong email, wrong password, or wrong role).
 */
function attempt_login(string $email, string $password, string $role): ?array
{
    $stmt = get_pdo()->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    start_session();
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function flash_set(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Reads and clears the one-shot flash message, if any. */
function flash_get(): ?array
{
    start_session();
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
