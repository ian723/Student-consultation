
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

$staffRoles = [
    'lecturer',
    'academic_advisor',
    'department_head',
    'counselor',
    'university_staff'
];

$user = current_user();

// Only authenticated staff members may view their own bookings.
if (!$user || !in_array($user['role'], $staffRoles, true)) {
    header('Location: /login_staff.php');
    exit;
}

$pdo = get_pdo();

$stmt = $pdo->prepare("
    SELECT
        b.id,
        b.status,
        cs.slot_date,
        cs.start_time,
        cs.end_time,
        s.full_name AS student_name,
        s.email AS student_email
    FROM bookings b
    JOIN consultation_slots cs ON cs.id = b.slot_id
    JOIN users s ON s.id = b.student_id
    WHERE cs.staff_id = ?
    ORDER BY cs.slot_date DESC, cs.start_time DESC
");

$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$today = date('Y-m-d');
$currentTime = date('H:i:s');

$pageTitle = 'My Consultation Bookings';
require __DIR__ . '/../partials/header.php';
?>

<h1>My Consultation Bookings</h1>

<p class="page-subtitle">
    View the students who have booked consultations with you.
</p>

<div class="card">
    <?php if (!$bookings): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128197;</div>
            <p>You don't have any consultation bookings yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <?php
                        $isUpcoming =
                            $booking['status'] === 'active'
                            && (
                                $booking['slot_date'] > $today
                                || (
                                    $booking['slot_date'] === $today
                                    && $booking['start_time'] > $currentTime
                                )
                            );
                        ?>

                        <tr>
                            <td data-label="Student">
                                <?= e($booking['student_name']) ?>
                            </td>

                            <td data-label="Email">
                                <?= e($booking['student_email']) ?>
                            </td>

                            <td data-label="Date">
                                <?= e(date(
                                    'D, d M Y',
                                    strtotime($booking['slot_date'])
                                )) ?>

                                <?php if ($isUpcoming): ?>
                                    <span class="badge badge-available">
                                        Upcoming
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Time">
                                <?= e(date(
                                    'H:i',
                                    strtotime($booking['start_time'])
                                )) ?>&ndash;<?= e(date(
                                    'H:i',
                                    strtotime($booking['end_time'])
                                )) ?>
                            </td>

                            <td data-label="Status">
                                <?php if ($booking['status'] === 'active'): ?>
                                    <span class="badge badge-available">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-cancelled">
                                        Cancelled
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<p>
    <a class="btn btn-secondary" href="/staff/dashboard.php">
        Back to Dashboard
    </a>
</p>

<?php require __DIR__ . '/../partials/footer.php'; ?>
