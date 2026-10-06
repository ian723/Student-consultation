<?php
require_once __DIR__ . '/partials/bootstrap.php';

$user = current_user();

if ($user) {
    header('Location: ' . ($user['role'] === 'lecturer' ? '/lecturer/dashboard.php' : '/student/dashboard.php'));
    exit;
}

$pageTitle = 'Welcome';
require __DIR__ . '/partials/header.php';
?>

<div class="hero">
  <h1>Book a consultation with your lecturer in a few clicks</h1>
  <p class="page-subtitle">See who's available, pick a time that works, and get confirmed instantly — no more guessing office hours.</p>
</div>

<div class="landing-roles">
  <div class="card">
    <span class="role-icon" aria-hidden="true">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z" stroke="currentColor" stroke-width="1.8"/>
        <path d="M4 20.5c1.4-3.6 4.5-5.5 8-5.5s6.6 1.9 8 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </span>
    <h2>Students</h2>
    <p class="muted">Browse lecturers, check real-time availability, and book a consultation slot.</p>
    <a class="btn" href="/login_student.php">Student Login</a>
    <p class="muted" style="margin-top:0.9rem;">New here? <a href="/register.php">Create an account</a></p>
  </div>
  <div class="card">
    <span class="role-icon lecturer" aria-hidden="true">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 19.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13.5L12 17l-8 2.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
        <path d="M8 8h8M8 11.5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
    </span>
    <h2>Lecturers</h2>
    <p class="muted">Manage your consultation slots and see who's booked in with you.</p>
    <a class="btn" href="/login_lecturer.php">Lecturer Login</a>
    <p class="muted" style="margin-top:0.9rem;">Accounts are set up by the administrator.</p>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
