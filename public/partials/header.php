<?php
/** @var array|null $user Expected to be set by the including page (current_user() result). */
$user = $user ?? current_user();

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

function nav_link(string $href, string $label, string $currentPath): string
{
    $active = $currentPath === $href;
    $class = 'nav-link' . ($active ? ' active' : '');
    return sprintf('<a class="%s" href="%s">%s</a>', $class, e($href), e($label));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $letters = array_map(fn($p) => strtoupper(substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: '?';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?>Kabarak Consultation Booking</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav class="navbar">
  <div class="navbar-inner">
    <a href="/index.php" class="brand">
      <span class="brand-mark" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="3" y="4" width="18" height="17" rx="3" stroke="currentColor" stroke-width="1.8"/>
          <path d="M3 9H21" stroke="currentColor" stroke-width="1.8"/>
          <path d="M8 2.5V5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M16 2.5V5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          <path d="M7.5 13.2L10 15.7L16 10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="brand-text">Kabarak Consultation</span>
    </a>

    <div class="navbar-links">
      <?php if ($user && $user['role'] === 'student'): ?>
        <?= nav_link('/student/dashboard.php', 'Dashboard', $currentPath) ?>
        <?= nav_link('/student/lecturers.php', 'Lecturers', $currentPath) ?>
        <?= nav_link('/student/my_bookings.php', 'My Bookings', $currentPath) ?>
      <?php elseif ($user && $user['role'] === 'lecturer'): ?>
        <?= nav_link('/lecturer/dashboard.php', 'Dashboard', $currentPath) ?>
        <?= nav_link('/lecturer/manage_slots.php', 'My Slots', $currentPath) ?>
        <?= nav_link('/lecturer/bookings.php', 'Bookings', $currentPath) ?>
      <?php else: ?>
        <?= nav_link('/login_student.php', 'Student Login', $currentPath) ?>
        <?= nav_link('/login_lecturer.php', 'Lecturer Login', $currentPath) ?>
        <?= nav_link('/register.php', 'Register', $currentPath) ?>
      <?php endif; ?>
    </div>

    <?php if ($user): ?>
      <div class="navbar-user">
        <span class="avatar" aria-hidden="true"><?= e(initials($user['full_name'])) ?></span>
        <span class="navbar-user-name"><?= e($user['full_name']) ?></span>
        <a href="/logout.php" class="btn-logout" title="Log out">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M21 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
    <?php endif; ?>
  </div>
</nav>
<main class="container">
  <?php $flash = flash_get(); if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
      <span class="alert-icon" aria-hidden="true"><?= $flash['type'] === 'error' ? '&#9888;' : '&#10003;' ?></span>
      <span><?= e($flash['message']) ?></span>
    </div>
  <?php endif; ?>
