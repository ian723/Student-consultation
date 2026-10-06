<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /student/lecturers.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: /student/lecturers.php');
    exit;
}

$slotId = (int) ($_POST['slot_id'] ?? 0);
$pdo = get_pdo();

$pdo->beginTransaction();
try {
    // Lock the slot row so a concurrent booking attempt has to wait and then
    // see the now-updated status, preventing two students booking the same slot.
    $stmt = $pdo->prepare('SELECT * FROM consultation_slots WHERE id = ? FOR UPDATE');
    $stmt->execute([$slotId]);
    $slot = $stmt->fetch();

    if (!$slot) {
        $pdo->rollBack();
        flash_set('error', 'That consultation slot no longer exists.');
        header('Location: /student/lecturers.php');
        exit;
    }

    if ($slot['status'] !== 'available' || $slot['slot_date'] < date('Y-m-d')) {
        $pdo->rollBack();
        flash_set('error', 'Sorry, that slot is no longer available.');
        header('Location: /student/lecturer_slots.php?lecturer_id=' . (int) $slot['lecturer_id']);
        exit;
    }

    $pdo->prepare('UPDATE consultation_slots SET status = ? WHERE id = ?')->execute(['booked', $slotId]);
    $pdo->prepare('INSERT INTO bookings (slot_id, student_id, status) VALUES (?, ?, ?)')
        ->execute([$slotId, $user['id'], 'active']);

    $pdo->commit();

    $lecturerStmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
    $lecturerStmt->execute([$slot['lecturer_id']]);
    $lecturerName = $lecturerStmt->fetchColumn();

    $when = date('D, d M Y', strtotime($slot['slot_date'])) . ' ' . date('H:i', strtotime($slot['start_time']));
    flash_set('success', "Booking confirmed with {$lecturerName} on {$when}.");
    header('Location: /student/my_bookings.php');
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'This slot was just booked by someone else. Please pick another.');
    $lecturerId = isset($slot) ? (int) $slot['lecturer_id'] : null;
    header('Location: ' . ($lecturerId ? "/student/lecturer_slots.php?lecturer_id=$lecturerId" : '/student/lecturers.php'));
    exit;
}
