
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

// Booking requests must use POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /student/lecturers.php');
    exit;
}

// Validate the CSRF token.
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: /student/lecturers.php');
    exit;
}

$slotId = (int) ($_POST['slot_id'] ?? 0);

if ($slotId <= 0) {
    flash_set('error', 'Invalid consultation slot selected.');
    header('Location: /student/lecturers.php');
    exit;
}

$pdo = get_pdo();
$staffId = null;

try {
    $pdo->beginTransaction();

    // Lock the slot while checking and processing the booking.
    $stmt = $pdo->prepare(
        "SELECT
            cs.*,
            u.full_name AS staff_name
         FROM consultation_slots cs
         JOIN users u ON u.id = cs.staff_id
         WHERE cs.id = ?
           AND u.is_active = 1
           AND u.role IN (
               'lecturer',
               'academic_advisor',
               'department_head',
               'counselor',
               'university_staff'
           )
         FOR UPDATE"
    );

    $stmt->execute([$slotId]);
    $slot = $stmt->fetch();

    if (!$slot) {
        $pdo->rollBack();

        flash_set(
            'error',
            'Staff member or consultation slot not found, or the account is inactive.'
        );

        header('Location: /student/lecturers.php');
        exit;
    }

    $staffId = (int) $slot['staff_id'];

    // Reject slots that are booked already or whose start time has passed.
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');

    $isPast = $slot['slot_date'] < $today
        || (
            $slot['slot_date'] === $today
            && $slot['start_time'] <= $currentTime
        );

    if ($slot['status'] !== 'available' || $isPast) {
        $pdo->rollBack();

        flash_set(
            'error',
            'Sorry, that slot is no longer available. Please choose another.'
        );

        header(
            'Location: /student/lecturer_slots.php?staff_id=' . $staffId
        );
        exit;
    }

    // Update only if the slot is still available.
    $update = $pdo->prepare(
        "UPDATE consultation_slots
         SET status = 'booked'
         WHERE id = ?
           AND status = 'available'"
    );

    $update->execute([$slotId]);

    if ($update->rowCount() !== 1) {
        $pdo->rollBack();

        flash_set(
            'error',
            'This slot is no longer available. Please choose another.'
        );

        header(
            'Location: /student/lecturer_slots.php?staff_id=' . $staffId
        );
        exit;
    }

    // Record the booking for the currently authenticated student.
    $insert = $pdo->prepare(
        "INSERT INTO bookings (slot_id, student_id, status)
         VALUES (?, ?, 'active')"
    );

    $insert->execute([$slotId, $user['id']]);

    // Commit both changes together.
    $pdo->commit();

    $staffName = $slot['staff_name'];

    $when = date(
        'D, d M Y',
        strtotime($slot['slot_date'])
    ) . ' ' . date(
        'H:i',
        strtotime($slot['start_time'])
    );

    flash_set(
        'success',
        "Booking confirmed with {$staffName} on {$when}."
    );

    header('Location: /student/my_bookings.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log the technical error for debugging without exposing it to students.
    error_log('Consultation booking failed: ' . $e->getMessage());

    flash_set(
        'error',
        'The booking could not be completed. The slot may have just been booked, or a database error occurred. Please try again.'
    );

    if ($staffId !== null) {
        header(
            'Location: /student/lecturer_slots.php?staff_id=' . $staffId
        );
    } else {
        header('Location: /student/lecturers.php');
    }

    exit;
}
