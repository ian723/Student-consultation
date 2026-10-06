<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /student/my_bookings.php');
    exit;
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash_set('error', 'Your session expired. Please try again.');
    header('Location: /student/my_bookings.php');
    exit;
}

$bookingId = (int) ($_POST['booking_id'] ?? 0);
$pdo = get_pdo();

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT * FROM bookings WHERE id = ? AND student_id = ? FOR UPDATE');
    $stmt->execute([$bookingId, $user['id']]);
    $booking = $stmt->fetch();

    if (!$booking || $booking['status'] !== 'active') {
        $pdo->rollBack();
        flash_set('error', 'That booking cannot be cancelled.');
        header('Location: /student/my_bookings.php');
        exit;
    }

    $pdo->prepare('UPDATE bookings SET status = ?, cancelled_at = NOW() WHERE id = ?')
        ->execute(['cancelled', $bookingId]);
    $pdo->prepare('UPDATE consultation_slots SET status = ? WHERE id = ?')
        ->execute(['available', $booking['slot_id']]);

    $pdo->commit();

    flash_set('success', 'Booking cancelled. The slot is now available again.');
    header('Location: /student/my_bookings.php');
    exit;
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_set('error', 'Something went wrong cancelling that booking. Please try again.');
    header('Location: /student/my_bookings.php');
    exit;
}
