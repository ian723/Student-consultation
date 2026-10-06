<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('lecturer');

$stmt = get_pdo()->prepare("
    SELECT b.id, b.status, cs.slot_date, cs.start_time, cs.end_time, u.full_name AS student_name
    FROM bookings b
    JOIN consultation_slots cs ON cs.id = b.slot_id
    JOIN users u ON u.id = b.student_id
    WHERE cs.lecturer_id = ?
    ORDER BY cs.slot_date DESC, cs.start_time DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$today = date('Y-m-d');

$pageTitle = 'Student Bookings';
require __DIR__ . '/../partials/header.php';
?>

<h1>Student Bookings</h1>
<p class="page-subtitle">Students who have booked a consultation with you.</p>

<div class="card">
  <?php if (!$bookings): ?>
    <div class="empty-state">
      <div class="empty-state-icon">&#128101;</div>
      <p>No students have booked consultation slots with you yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Student</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $booking): ?>
            <?php $isUpcoming = $booking['status'] === 'active' && $booking['slot_date'] >= $today; ?>
            <tr>
              <td data-label="Student"><?= e($booking['student_name']) ?></td>
              <td data-label="Date">
                <?= e(date('D, d M Y', strtotime($booking['slot_date']))) ?>
                <?php if ($isUpcoming): ?><span class="badge badge-available">Upcoming</span><?php endif; ?>
              </td>
              <td data-label="Time"><?= e(date('H:i', strtotime($booking['start_time']))) ?>&ndash;<?= e(date('H:i', strtotime($booking['end_time']))) ?></td>
              <td data-label="Status">
                <?php if ($booking['status'] === 'active'): ?>
                  <span class="badge badge-available">Active</span>
                <?php else: ?>
                  <span class="badge badge-cancelled">Cancelled</span>
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
