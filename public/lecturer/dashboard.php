<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('lecturer');

$stmt = get_pdo()->prepare("
    SELECT COUNT(*) FROM consultation_slots
    WHERE staff_id = ? AND status = 'available' AND slot_date >= CURDATE()
");
$stmt->execute([$user['id']]);
$availableCount = (int) $stmt->fetchColumn();

$stmt = get_pdo()->prepare("
    SELECT COUNT(*) FROM bookings b
    JOIN consultation_slots cs ON cs.id = b.slot_id
    WHERE cs.staff_id = ? AND b.status = 'active' AND cs.slot_date >= CURDATE()
");
$stmt->execute([$user['id']]);
$upcomingBookings = (int) $stmt->fetchColumn();

$status = availability_status($availableCount);

$pageTitle = 'Lecturer Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<h1>Welcome back, <?= e($user['full_name']) ?></h1>
<p class="page-subtitle">
  Your current availability status:
  <span class="badge badge-<?= e($status) ?>"><?= e(availability_label($status)) ?></span>
</p>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Open Slots</div>
    <div class="stat-value"><?= $availableCount ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Upcoming Bookings</div>
    <div class="stat-value"><?= $upcomingBookings ?></div>
  </div>
</div>

<div class="card">
  <h2>Quick Actions</h2>
  <div class="actions-row">
    <a class="btn" href="/lecturer/manage_slots.php">Manage Slots</a>
    <a class="btn btn-secondary" href="/lecturer/bookings.php">View Bookings</a>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
