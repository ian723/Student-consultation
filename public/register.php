<?php
require_once __DIR__ . '/partials/bootstrap.php';

if (current_user()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'course' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $old['full_name'] = trim($_POST['full_name'] ?? '');
        $old['email'] = trim($_POST['email'] ?? '');
        $old['course'] = trim($_POST['course'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($old['full_name'] === '') {
            $errors[] = 'Full name is required.';
        }
        if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if ($old['course'] === '') {
            $errors[] = 'Course is required.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!$errors) {
            $stmt = get_pdo()->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$old['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'That email is already registered. Please log in instead.';
            }
        }

        if (!$errors) {
            $stmt = get_pdo()->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, course) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $old['full_name'],
                $old['email'],
                password_hash($password, PASSWORD_DEFAULT),
                'student',
                $old['course'],
            ]);

            flash_set('success', 'Registration successful! You can now log in.');
            header('Location: /login_student.php');
            exit;
        }
    }
}

$pageTitle = 'Student Registration';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-wrap">
  <h1>Create your account</h1>
  <p class="page-subtitle">Register to browse lecturers and book consultations.</p>

  <div class="card">
    <?php if ($errors): ?>
      <div class="alert alert-error">
        <ul style="margin:0; padding-left:1.1rem;">
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="/register.php">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

      <label for="full_name">Full name</label>
      <input type="text" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>

      <label for="course">Course</label>
      <input type="text" id="course" name="course" value="<?= e($old['course']) ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" minlength="6" required>

      <button type="submit" class="btn">Register</button>
    </form>

    <p class="muted auth-footer-note">Already have an account? <a href="/login_student.php">Log in</a></p>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
