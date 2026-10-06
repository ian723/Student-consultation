<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

$stmt = get_pdo()->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN consultation_slots cs ON cs.id = b.slot_id
    WHERE b.student_id = ? AND b.status = 'active' AND cs.slot_date >= CURDATE()
");
$stmt->execute([$user['id']]);
$upcomingCount = (int) $stmt->fetchColumn();

$lecturerCount = (int) get_pdo()->query("SELECT COUNT(*) FROM users WHERE role = 'lecturer'")->fetchColumn();

$pageTitle = 'Student Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<h1>Welcome back, <?= e(explode(' ', $user['full_name'])[0]) ?></h1>
<p class="page-subtitle">Here's what's happening with your consultations.</p>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Upcoming Consultations</div>
    <div class="stat-value"><?= $upcomingCount ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Lecturers Available</div>
    <div class="stat-value"><?= $lecturerCount ?></div>
  </div>
</div>

<div class="card">
  <h2>Quick Actions</h2>
  <div class="actions-row">
    <a class="btn" href="/student/lecturers.php">Browse Lecturers</a>
    <a class="btn btn-secondary" href="/student/my_bookings.php">My Bookings</a>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
