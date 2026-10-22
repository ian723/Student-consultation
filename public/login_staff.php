
<?php
require_once __DIR__ . '/partials/bootstrap.php';

$staffRoles = [
    'lecturer',
    'academic_advisor',
    'department_head',
    'counselor',
    'university_staff'
];

$existingUser = current_user();

if ($existingUser) {
    if ($existingUser['role'] === 'student') {
        header('Location: /student/dashboard.php');
        exit;
    }

    if ($existingUser['role'] === 'admin') {
        header('Location: /admin/dashboard.php');
        exit;
    }

    if (in_array($existingUser['role'], $staffRoles, true)) {
        header('Location: /staff/dashboard.php');
        exit;
    }
}

$error = '';
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } else {
        // Check the admin account first.
        $user = attempt_login($oldEmail, $password, 'admin');

        if ($user) {
            login_user($user);
            header('Location: /admin/dashboard.php');
            exit;
        }

        // Check all other staff roles.
        foreach ($staffRoles as $role) {
            $user = attempt_login($oldEmail, $password, $role);

            if ($user) {
                login_user($user);
                header('Location: /staff/dashboard.php');
                exit;
            }
        }

        $error = 'Invalid email or password, or your account is inactive.';
    }
}

$pageTitle = 'Staff Login';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-wrap">
  <h1>Staff Login</h1>
  <p class="page-subtitle">
    Welcome back — log in to manage your consultation appointments.
  </p>

  <div class="card">
    <?php if ($error !== ''): ?>
      <div class="alert alert-error">
        <span class="alert-icon" aria-hidden="true">&#9888;</span>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="/login_staff.php">
      <input
        type="hidden"
        name="csrf_token"
        value="<?= e(csrf_token()) ?>"
      >

      <div class="form-group">
        <label for="email">Email</label>
        <input
          type="email"
          id="email"
          name="email"
          value="<?= e($oldEmail) ?>"
          autocomplete="username"
          required
        >
      </div>

      <div class="password-wrapper">
        <label for="password">Password</label>
        <div class="password-input-container">
          <input
            type="password"
            id="password"
            name="password"
            autocomplete="current-password"
            required
          >
          <button
            type="button"
            class="toggle-password"
            aria-label="Show password"
            aria-pressed="false"
            onclick="togglePassword('password', this)"
          >&#128065;</button>
        </div>
      </div>

      <button type="submit" class="btn">Log in</button>
    </form>

    <p class="muted auth-footer-note">
      Are you a student?
      <a href="/login_student.php">Student Login</a>
    </p>
  </div>
</div>

<script>
function togglePassword(inputId, button) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const showing = input.type === 'password';
  input.type = showing ? 'text' : 'password';
  button.setAttribute('aria-pressed', String(showing));
  button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
}
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
