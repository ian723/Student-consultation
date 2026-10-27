
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

// Only students may view consultation slots.
$user = require_role('student');

// Read the selected consultation provider's ID.
$providerId = (int) ($_GET['staff_id'] ?? 0);

$provider = $providerId > 0
    ? get_provider($providerId)
    : null;

// Return to the provider directory if the provider does not exist.
if (!$provider) {
    flash_set(
        'error',
        'Staff member not found or account is inactive.'
    );

    header('Location: /student/lecturers.php');
    exit;
}

// Retrieve upcoming slots belonging to this provider.
$slots = list_future_slots($providerId);

$pageTitle = $provider['full_name'] . ' — Consultation Slots';

require __DIR__ . '/../partials/header.php';
?>

<h1><?= e($provider['full_name']) ?></h1>

<p class="page-subtitle">
    <?= e(ucwords(str_replace('_', ' ', $provider['role']))) ?>

    <?php if (!empty($provider['department'])): ?>
        — <?= e($provider['department']) ?>
    <?php endif; ?>
</p>

<?php if (!empty($provider['service'])): ?>
    <p class="page-subtitle">
        Service: <?= e($provider['service']) ?>
    </p>
<?php endif; ?>

<?php if (!empty($provider['description'])): ?>
    <p><?= e($provider['description']) ?></p>
<?php endif; ?>

<div class="card">
    <?php if (empty($slots)): ?>

        <div class="empty-state">
            <div class="empty-state-icon">&#128197;</div>
            <p>No upcoming consultation slots for this staff member.</p>
        </div>

    <?php else: ?>

        <ul class="slot-list">
            <?php foreach ($slots as $slot): ?>
                <li>
                    <span class="slot-when">
                        <span class="slot-date">
                            <?= e(
                                date(
                                    'D, d M Y',
                                    strtotime($slot['slot_date'])
                                )
                            ) ?>
                        </span>

                        <span class="slot-time">
                            <?= e(
                                date('H:i', strtotime($slot['start_time']))
                            ) ?>&ndash;<?= e(
                                date('H:i', strtotime($slot['end_time']))
                            ) ?>
                        </span>
                    </span>

                    <?php if ($slot['status'] === 'available'): ?>

                        <form
                            method="post"
                            action="/student/book_slot.php"
                            style="margin: 0;"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= e(csrf_token()) ?>"
                            >

                            <input
                                type="hidden"
                                name="slot_id"
                                value="<?= (int) $slot['id'] ?>"
                            >

                            <button type="submit" class="btn btn-sm">
                                Book
                            </button>
                        </form>

                    <?php else: ?>

                        <span class="badge badge-full">Booked</span>

                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

    <?php endif; ?>
</div>

<a href="/student/lecturers.php">
    &larr; Back to consultation providers
</a>

<?php require __DIR__ . '/../partials/footer.php'; ?>
