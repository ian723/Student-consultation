<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

$lecturers = list_lecturers_with_availability();

$pageTitle = 'Lecturers';
require __DIR__ . '/../partials/header.php';
?>

<h1>Lecturers</h1>
<p class="page-subtitle">Pick a lecturer to see their consultation slots.</p>

<?php if (!$lecturers): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-state-icon">&#128101;</div>
      <p>No lecturers are registered yet.</p>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Department</th>
            <th>Availability</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lecturers as $lecturer): ?>
            <tr>
              <td data-label="Name"><?= e($lecturer['full_name']) ?></td>
              <td data-label="Department"><?= e($lecturer['department'] ?? '') ?></td>
              <td data-label="Availability"><span class="badge badge-<?= e($lecturer['status']) ?>"><?= e($lecturer['status_label']) ?></span></td>
              <td data-label=""><a class="btn btn-sm" href="/student/lecturer_slots.php?lecturer_id=<?= (int) $lecturer['id'] ?>">View Slots</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
