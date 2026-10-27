
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

// Only allow authenticated staff members to access this dashboard.
$staffRoles = [
    'lecturer',
    'academic_advisor',
    'department_head',
    'counselor',
    'university_staff'
];

$user = current_user();

if (!$user || !in_array($user['role'], $staffRoles, true)) {
    header('Location: /login_staff.php');
    exit;
}

// Count available future consultation slots for the logged-in staff member.
$stmt = get_pdo()->prepare("
    SELECT COUNT(*)
    FROM consultation_slots
    WHERE staff_id = ?
      AND status = 'available'
      AND (
          slot_date > CURDATE()
          OR (
              slot_date = CURDATE()
              AND start_time > CURTIME()
          )
      )
");

$stmt->execute([$user['id']]);
$availableCount = (int) $stmt->fetchColumn();

// Count active upcoming bookings for the logged-in staff member.
$stmt = get_pdo()->prepare("
    SELECT COUNT(*)
    FROM bookings b
    JOIN consultation_slots cs ON cs.id = b.slot_id
    WHERE cs.staff_id = ?
      AND b.status = 'active'
      AND (
          cs.slot_date > CURDATE()
          OR (
              cs.slot_date = CURDATE()
              AND cs.start_time > CURTIME()
          )
      )
");

$stmt->execute([$user['id']]);
$upcomingBookings = (int) $stmt->fetchColumn();

// Determine the staff member's availability status.
$status = availability_status($availableCount);

// Display a readable label for each staff role.
$roleLabels = [
    'lecturer' => 'Lecturer',
    'academic_advisor' => 'Academic Advisor',
    'department_head' => 'Department Head',
    'counselor' => 'Counselor',
    'university_staff' => 'University Staff'
];

$roleLabel = $roleLabels[$user['role']] ?? 'Staff';

$pageTitle = 'Staff Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<h1>Welcome back, <?= e($user['full_name']) ?></h1>

<p class="page-subtitle">
    <?= e($roleLabel) ?> dashboard
    &mdash; Your current availability:
    <span class="badge badge-<?= e($status) ?>">
        <?= e(availability_label($status)) ?>
    </span>
</p>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Open Consultation Slots</div>
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
        <a class="btn" href="/staff/manage_slots.php">
            Manage Slots
        </a>

        <a class="btn btn-secondary" href="/staff/bookings.php">
            View Bookings
        </a>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
