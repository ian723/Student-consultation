
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

$stmt = get_pdo()->prepare("
    SELECT
        b.id,
        b.status,
        cs.slot_date,
        cs.start_time,
        cs.end_time,
        u.full_name AS provider_name,
        u.role AS provider_role,
        u.service AS provider_service
    FROM bookings b
    JOIN consultation_slots cs
        ON cs.id = b.slot_id
    JOIN users u
        ON u.id = cs.staff_id
    WHERE b.student_id = ?
    ORDER BY cs.slot_date DESC, cs.start_time DESC
");

$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$today = date('Y-m-d');
$currentTime = date('H:i:s');

$roleLabels = [
    'lecturer' => 'Lecturer',
    'academic_advisor' => 'Academic Advisor',
    'department_head' => 'Department Head',
    'counselor' => 'Counselor',
    'university_staff' => 'University Staff'
];

$pageTitle = 'My Bookings';

require __DIR__ . '/../partials/header.php';
?>

<h1>My Bookings</h1>

<p class="page-subtitle">
    Track and manage your consultation bookings with lecturers,
    academic advisors, counselors, department heads, and other
    university staff.
</p>

<div class="card">
    <?php if (empty($bookings)): ?>

        <div class="empty-state">
            <div class="empty-state-icon">&#128197;</div>

            <p>You haven't booked any consultations yet.</p>

            <a class="btn" href="/student/lecturers.php">
                Browse Staff &amp; Services
            </a>
        </div>

    <?php else: ?>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Consultation Provider</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($bookings as $booking): ?>

                        <?php
                        $isUpcoming = $booking['status'] === 'active'
                            && (
                                $booking['slot_date'] > $today
                                || (
                                    $booking['slot_date'] === $today
                                    && $booking['start_time'] > $currentTime
                                )
                            );

                        $providerRole = $roleLabels[
                            $booking['provider_role'] ?? ''
                        ] ?? 'Staff';
                        ?>

                        <tr>
                            <td data-label="Consultation Provider">
                                <strong>
                                    <?= e($booking['provider_name']) ?>
                                </strong>

                                <br>

                                <span class="muted">
                                    <?= e($providerRole) ?>
                                </span>

                                <?php if (!empty($booking['provider_service'])): ?>
                                    <br>

                                    <span class="muted">
                                        <?= e($booking['provider_service']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Date">
                                <?= e(
                                    date(
                                        'D, d M Y',
                                        strtotime($booking['slot_date'])
                                    )
                                ) ?>

                                <?php if ($isUpcoming): ?>
                                    <span class="badge badge-available">
                                        Upcoming
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Time">
                                <?= e(
                                    date(
                                        'H:i',
                                        strtotime($booking['start_time'])
                                    )
                                ) ?>&ndash;<?= e(
                                    date(
                                        'H:i',
                                        strtotime($booking['end_time'])
                                    )
                                ) ?>
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

                            <td data-label="Action">
                                <?php if ($isUpcoming): ?>

                                    <form
                                        method="post"
                                        action="/student/cancel_booking.php"
                                        style="margin: 0;"
                                        onsubmit="return confirm('Cancel this consultation booking?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int) $booking['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            Cancel
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <span class="muted">—</span>

                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
