<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

$providerId = (int) ($_GET['lecturer_id'] ?? 0);
$provider = $providerId ? get_provider($providerId) : null;

if (!$provider) {
    flash_set('error', 'Staff member not found or account is inactive.');
    header('Location: /student/lecturers.php');
    exit;
}

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

<div class="card">
  <?php if (!$slots): ?>
    <div class="empty-state">
      <div class="empty-state-icon">&#128197;</div>
      <p>No upcoming consultation slots for this provider.</p>
    </div>
  <?php else: ?>
    <ul class="slot-list">
      <?php foreach ($slots as $slot): ?>
        <li>
          <span class="slot-when">
            <span class="slot-date"><?= e(date('D, d M Y', strtotime($slot['slot_date']))) ?></span>
            <span class="slot-time"><?= e(date('H:i', strtotime($slot['start_time']))) ?>&ndash;<?= e(date('H:i', strtotime($slot['end_time']))) ?></span>
          </span>
          <?php if ($slot['status'] === 'available'): ?>
            <form method="post" action="/student/book_slot.php" style="margin:0;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>">
              <button type="submit" class="btn btn-sm">Book</button>
            </form>
          <?php else: ?>
            <span class="badge badge-full">Booked</span>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<a href="/student/lecturers.php">&larr; Back to lecturers</a>

<?php require __DIR__ . '/../partials/footer.php'; ?>
