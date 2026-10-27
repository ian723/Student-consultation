
<?php

require_once __DIR__ . '/partials/bootstrap.php';

// Redirect logged-in users to the correct dashboard.
$user = current_user();

if ($user) {
    if ($user['role'] === 'student') {
        header('Location: /student/dashboard.php');
    } elseif ($user['role'] === 'admin') {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /staff/dashboard.php');
    }

    exit;
}

$errors = [];

$old = [
    'full_name' => '',
    'email' => '',
    'course' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['full_name'] = trim($_POST['full_name'] ?? '');
        $old['email'] = strtolower(trim($_POST['email'] ?? ''));
        $old['course'] = trim($_POST['course'] ?? '');

        $password = $_POST['password'] ?? '';

        // Validate the submitted information.
        if (
            $old['full_name'] === ''
            || mb_strlen($old['full_name']) > 150
        ) {
            $errors[] = 'Enter your full name (maximum 150 characters).';
        }

        if (
            $old['email'] === ''
            || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)
            || strlen($old['email']) > 190
        ) {
            $errors[] = 'Enter a valid email address.';
        }

        if (
            $old['course'] === ''
            || mb_strlen($old['course']) > 150
        ) {
            $errors[] = 'Enter your course (maximum 150 characters).';
        }

        if (!is_string($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        // Check whether the email is already registered.
        if (empty($errors)) {
            $stmt = get_pdo()->prepare(
                'SELECT id FROM users WHERE email = ? LIMIT 1'
            );

            $stmt->execute([$old['email']]);

            if ($stmt->fetch()) {
                $errors[] =
                    'That email is already registered. Please log in instead.';
            }
        }

        // Create the student account.
        if (empty($errors)) {
            try {
                $stmt = get_pdo()->prepare(
                    'INSERT INTO users (
                        full_name,
                        email,
                        password_hash,
                        role,
                        course
                    ) VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $old['full_name'],
                    $old['email'],
                    password_hash($password, PASSWORD_DEFAULT),
                    'student',
                    $old['course']
                ]);

                flash_set(
                    'success',
                    'Registration successful! You can now log in.'
                );

                header('Location: /login_student.php');
                exit;

            } catch (PDOException $e) {
                // SQLSTATE 23000 covers unique-key and other integrity errors.
                if (
                    $e->getCode() === '23000'
                    && str_contains(
                        strtolower($e->getMessage()),
                        'email'
                    )
                ) {
                    $errors[] =
                        'That email is already registered. Please log in instead.';
                } else {
                    error_log(
                        'Student registration error: ' . $e->getMessage()
                    );

                    $errors[] =
                        'Registration could not be completed. Please try again.';
                }
            }
        }
    }
}

$pageTitle = 'Student Registration';

require __DIR__ . '/partials/header.php';
?>

<main class="auth-wrap">
    <h1>Create your account</h1>

    <p class="page-subtitle">
        Register to browse university staff and book consultations.
    </p>

    <div class="card">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <ul style="margin: 0; padding-left: 1.1rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/register.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-group">
                <label for="full_name">Full name</label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="150"
                    autocomplete="name"
                    value="<?= e($old['full_name']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="email">Email</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="190"
                    autocomplete="email"
                    value="<?= e($old['email']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="course">Course</label>

                <input
                    type="text"
                    id="course"
                    name="course"
                    maxlength="150"
                    value="<?= e($old['course']) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <small>Use at least 8 characters.</small>
            </div>

            <button type="submit" class="btn">
                Register
            </button>
        </form>

        <p class="muted auth-footer-note">
            Already have an account?
            <a href="/login_student.php">Log in</a>
        </p>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
