
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

// Only authenticated staff members may manage their own slots.
if (!$user || !in_array($user['role'], $staffRoles, true)) {
    header('Location: /login_staff.php');
    exit;
}

$pdo = get_pdo();
$errors = [];

/**
 * Fetch a consultation slot belonging to the specified staff member.
 */
function fetch_own_staff_slot(PDO $pdo, int $slotId, int $staffId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT *
         FROM consultation_slots
         WHERE id = ? AND staff_id = ?'
    );

    $stmt->execute([$slotId, $staffId]);

    $slot = $stmt->fetch();

    return $slot ?: null;
}

/**
 * Validate the date and time submitted by the staff member.
 */
function validate_staff_slot(
    string $date,
    string $start,
    string $end,
    array &$errors
): bool {
    if ($date === '' || $start === '' || $end === '') {
        $errors[] = 'Date, start time, and end time are all required.';
        return false;
    }

    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$dateObject ||
        ($dateErrors !== false &&
            ($dateErrors['warning_count'] > 0 ||
             $dateErrors['error_count'] > 0)) ||
        $dateObject->format('Y-m-d') !== $date
    ) {
        $errors[] = 'Please enter a valid date.';
        return false;
    }

    if (
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start) ||
        !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)
    ) {
        $errors[] = 'Please enter valid start and end times.';
        return false;
    }

    if ($date < date('Y-m-d')) {
        $errors[] = 'Slot date cannot be in the past.';
        return false;
    }

    if ($end <= $start) {
        $errors[] = 'End time must be after start time.';
        return false;
    }

    if (
        $date === date('Y-m-d') &&
        $start <= date('H:i')
    ) {
        $errors[] = 'The start time must be in the future.';
        return false;
    }

    return true;
}

// Handle slot creation, editing, and deletion.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $date = trim($_POST['slot_date'] ?? '');
            $start = trim($_POST['start_time'] ?? '');
            $end = trim($_POST['end_time'] ?? '');

            if (validate_staff_slot($date, $start, $end, $errors)) {
                $stmt = $pdo->prepare(
                    'INSERT INTO consultation_slots
                        (staff_id, slot_date, start_time, end_time, status)
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $user['id'],
                    $date,
                    $start,
                    $end,
                    'available'
                ]);

                flash_set('success', 'Consultation slot created.');
                header('Location: /staff/manage_slots.php');
                exit;
            }
        } elseif ($action === 'update') {
            $slotId = (int) ($_POST['slot_id'] ?? 0);

            $slot = fetch_own_staff_slot(
                $pdo,
                $slotId,
                (int) $user['id']
            );

            if (!$slot) {
                $errors[] = 'Slot not found.';
            } elseif ($slot['status'] !== 'available') {
                $errors[] = 'Only available (unbooked) slots can be edited.';
            } else {
                $date = trim($_POST['slot_date'] ?? '');
                $start = trim($_POST['start_time'] ?? '');
                $end = trim($_POST['end_time'] ?? '');

                if (validate_staff_slot($date, $start, $end, $errors)) {
                    $stmt = $pdo->prepare(
                        'UPDATE consultation_slots
                         SET slot_date = ?, start_time = ?, end_time = ?
                         WHERE id = ?
                           AND staff_id = ?
                           AND status = ?'
                    );

                    $stmt->execute([
                        $date,
                        $start,
                        $end,
                        $slotId,
                        $user['id'],
                        'available'
                    ]);

                    flash_set('success', 'Consultation slot updated.');
                    header('Location: /staff/manage_slots.php');
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $slotId = (int) ($_POST['slot_id'] ?? 0);

            $slot = fetch_own_staff_slot(
                $pdo,
                $slotId,
                (int) $user['id']
            );

            if (!$slot) {
                $errors[] = 'Slot not found.';
            } elseif ($slot['status'] !== 'available') {
                $errors[] = 'Only available (unbooked) slots can be removed.';
            } else {
                $stmt = $pdo->prepare(
                    'DELETE FROM consultation_slots
                     WHERE id = ?
                       AND staff_id = ?
                       AND status = ?'
                );

                $stmt->execute([
                    $slotId,
                    $user['id'],
                    'available'
                ]);

                if ($stmt->rowCount() > 0) {
                    flash_set('success', 'Consultation slot removed.');
                    header('Location: /staff/manage_slots.php');
                    exit;
                }

                $errors[] = 'The slot could not be removed. Please refresh and try again.';
            }
        } else {
            $errors[] = 'Invalid action requested.';
        }
    }
}

// Load a slot for editing, if requested.
$editSlot = null;

if (isset($_GET['edit'])) {
    $editSlot = fetch_own_staff_slot(
        $pdo,
        (int) $_GET['edit'],
        (int) $user['id']
    );

    if ($editSlot && $editSlot['status'] !== 'available') {
        $editSlot = null;
    }
}

// Load only the logged-in staff member's slots.
$stmt = $pdo->prepare(
    'SELECT *
     FROM consultation_slots
     WHERE staff_id = ?
     ORDER BY slot_date ASC, start_time ASC'
);

$stmt->execute([$user['id']]);
$slots = $stmt->fetchAll();

$pageTitle = 'Manage Consultation Slots';
require __DIR__ . '/../partials/header.php';
?>

<h1>Manage Consultation Slots</h1>

<p class="page-subtitle">
    Create, edit, or remove the times you're available for consultation.
</p>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul style="margin:0; padding-left:1.25rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h2><?= $editSlot ? 'Edit Slot' : 'Create a New Slot' ?></h2>

    <form method="post" action="/staff/manage_slots.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= e(csrf_token()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="<?= $editSlot ? 'update' : 'create' ?>"
        >

        <?php if ($editSlot): ?>
            <input
                type="hidden"
                name="slot_id"
                value="<?= (int) $editSlot['id'] ?>"
            >
        <?php endif; ?>

        <label for="slot_date">Date</label>
        <input
            type="date"
            id="slot_date"
            name="slot_date"
            min="<?= date('Y-m-d') ?>"
            value="<?= e($editSlot['slot_date'] ?? '') ?>"
            required
        >

        <label for="start_time">Start time</label>
        <input
            type="time"
            id="start_time"
            name="start_time"
            value="<?= e(isset($editSlot['start_time'])
                ? substr($editSlot['start_time'], 0, 5)
                : '') ?>"
            required
        >

        <label for="end_time">End time</label>
        <input
            type="time"
            id="end_time"
            name="end_time"
            value="<?= e(isset($editSlot['end_time'])
                ? substr($editSlot['end_time'], 0, 5)
                : '') ?>"
            required
        >

        <button type="submit" class="btn">
            <?= $editSlot ? 'Save Changes' : 'Create Slot' ?>
        </button>

        <?php if ($editSlot): ?>
            <a class="btn btn-secondary" href="/staff/manage_slots.php">
                Cancel
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h2>Your Consultation Slots</h2>

    <?php if (!$slots): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128197;</div>
            <p>You haven't created any consultation slots yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($slots as $slot): ?>
                        <tr>
                            <td data-label="Date">
                                <?= e(date(
                                    'D, d M Y',
                                    strtotime($slot['slot_date'])
                                )) ?>
                            </td>

                            <td data-label="Time">
                                <?= e(date(
                                    'H:i',
                                    strtotime($slot['start_time'])
                                )) ?>&ndash;<?= e(date(
                                    'H:i',
                                    strtotime($slot['end_time'])
                                )) ?>
                            </td>

                            <td data-label="Status">
                                <?php if ($slot['status'] === 'available'): ?>
                                    <span class="badge badge-available">
                                        Available
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-full">
                                        Booked
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td data-label="Actions">
                                <?php if ($slot['status'] === 'available'): ?>
                                    <a
                                        class="btn btn-sm btn-secondary"
                                        href="/staff/manage_slots.php?edit=<?= (int) $slot['id'] ?>"
                                    >Edit</a>

                                    <form
                                        method="post"
                                        action="/staff/manage_slots.php"
                                        style="display:inline;"
                                        onsubmit="return confirm('Delete this consultation slot?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="slot_id"
                                            value="<?= (int) $slot['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">
                                        Cannot edit/remove
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

<?php require __DIR__ . '/../partials/footer.php'; ?>
