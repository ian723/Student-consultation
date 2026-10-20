<?php
require_once __DIR__ . '/partials/bootstrap.php';

if (current_user()) {
    header('Location: /index.php');
    exit;
}

$error = null;
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $oldEmail = trim($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = attempt_login($oldEmail, $password, 'lecturer');

        if (!$user) {
            $error = 'Incorrect email or password.';
        } else {
            login_user($user);
            header('Location: /lecturer/dashboard.php');
            exit;
        }
    }
}

$pageTitle = 'Lecturer Login';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-wrap">
  <h1>Lecturer Login</h1>
  <p class="page-subtitle">Manage your consultation slots and bookings.</p>

  <div class="card">
    <?php if ($error): ?>
      <div class="alert alert-error">
        <span class="alert-icon" aria-hidden="true">&#9888;</span>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form method="post" action="/login_lecturer.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($oldEmail) ?>" required>
      </div>

<div class="password-wrapper">
    <label for="password">Password</label>

    <div class="password-input-container">
        <input type="password" id="password" name="password" required>

        <button type="button" class="toggle-password">
            👁
        </button>
    </div>
</div>

      <button type="submit" class="btn">Log in</button>
    </form>

    <p class="muted auth-footer-note">Lecturer accounts are created by the administrator.</p>
  </div>
</div>
<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);

    if (!input) return;

    const showing = input.type === "password";

    input.type = showing ? "text" : "password";
    button.setAttribute("aria-pressed", String(showing));
    button.setAttribute(
        "aria-label",
        showing ? "Hide password" : "Show password"
    );
}
</script>


<?php require __DIR__ . '/partials/footer.php'; ?>
