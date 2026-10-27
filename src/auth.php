
<?php

require_once __DIR__ . '/db.php';

/**
 * Starts a session if one has not already been started.
 */
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Logs in a user and regenerates the session ID.
 */
function login_user(array $user): void
{
    start_session();

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
}

/**
 * Logs out the current user and clears the session.
 */
function logout_user(): void
{
    start_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Returns the currently logged-in user.
 * Returns null if the user is not logged in, no longer exists,
 * or has been deactivated.
 */
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

    $stmt = get_pdo()->prepare(
        'SELECT id, full_name, email, role, course, department, is_active
         FROM users
         WHERE id = ?'
    );

    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Log out users whose accounts no longer exist or are inactive.
    if (!$user || (int) $user['is_active'] !== 1) {
        logout_user();
        return null;
    }

    $cached = $user;

    return $cached;
}

/**
 * Requires the user to be logged in with a specific role.
 * Redirects to the appropriate login page if access is denied.
 * Call before producing any page output.
 */
function require_role(string $role): array
{
    $user = current_user();

    if (!$user || $user['role'] !== $role) {
        $staffRoles = [
            'lecturer',
            'academic_advisor',
            'department_head',
            'counselor',
            'university_staff',
            'admin'
        ];

        $loginPage = in_array($role, $staffRoles, true)
            ? '/login_staff.php'
            : '/login_student.php';

        header('Location: ' . $loginPage);
        exit;
    }

    return $user;
}

/**
 * Verifies credentials against the requested role.
 * Returns the user record on success or null on failure.
 */
function attempt_login(
    string $email,
    string $password,
    string $role
): ?array {
    $stmt = get_pdo()->prepare(
        'SELECT *
         FROM users
         WHERE email = ?
           AND role = ?
           AND is_active = 1'
    );

    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

/**
 * Generates or retrieves a CSRF token for form protection.
 */
function csrf_token(): string
{
    start_session();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token.
 */
function verify_csrf(?string $token): bool
{
    start_session();

    return !empty($_SESSION['csrf_token'])
        && $token !== null
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escapes output to prevent HTML injection.
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Stores a one-time flash message.
 */
function flash_set(string $type, string $message): void
{
    start_session();

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieves and removes a one-time flash message.
 */
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
