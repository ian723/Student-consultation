<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('lecturer');
$pdo = get_pdo();

$errors = [];

function fetch_own_slot(PDO $pdo, int $slotId, int $lecturerId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM consultation_slots WHERE id = ? AND lecturer_id = ?');
    $stmt->execute([$slotId, $lecturerId]);
    $slot = $stmt->fetch();
    return $slot ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $date = trim($_POST['slot_date'] ?? '');
            $start = trim($_POST['start_time'] ?? '');
            $end = trim($_POST['end_time'] ?? '');

            if ($date === '' || $start === '' || $end === '') {
                $errors[] = 'Date, start time, and end time are all required.';
            } elseif ($date < date('Y-m-d')) {
                $errors[] = 'Slot date cannot be in the past.';
            } elseif ($end <= $start) {
                $errors[] = 'End time must be after start time.';
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO consultation_slots (lecturer_id, slot_date, start_time, end_time) VALUES (?, ?, ?, ?)"
                );
                $stmt->execute([$user['id'], $date, $start, $end]);
                flash_set('success', 'Consultation slot created.');
                header('Location: /lecturer/manage_slots.php');
                exit;
            }
        } elseif ($action === 'update') {
            $slotId = (int) ($_POST['slot_id'] ?? 0);
            $slot = fetch_own_slot($pdo, $slotId, $user['id']);

            if (!$slot) {
                $errors[] = 'Slot not found.';
            } elseif ($slot['status'] !== 'available') {
                $errors[] = 'Only available (unbooked) slots can be edited.';
            } else {
                $date = trim($_POST['slot_date'] ?? '');
                $start = trim($_POST['start_time'] ?? '');
                $end = trim($_POST['end_time'] ?? '');

                if ($date === '' || $start === '' || $end === '') {
                    $errors[] = 'Date, start time, and end time are all required.';
                } elseif ($end <= $start) {
                    $errors[] = 'End time must be after start time.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE consultation_slots SET slot_date = ?, start_time = ?, end_time = ? WHERE id = ? AND lecturer_id = ?'
                    );
                    $stmt->execute([$date, $start, $end, $slotId, $user['id']]);
                    flash_set('success', 'Consultation slot updated.');
                    header('Location: /lecturer/manage_slots.php');
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $slotId = (int) ($_POST['slot_id'] ?? 0);
            $slot = fetch_own_slot($pdo, $slotId, $user['id']);

            if (!$slot) {
                $errors[] = 'Slot not found.';
            } elseif ($slot['status'] !== 'available') {
                $errors[] = 'Only available (unbooked) slots can be removed.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM consultation_slots WHERE id = ? AND lecturer_id = ?');
                $stmt->execute([$slotId, $user['id']]);
                flash_set('success', 'Consultation slot removed.');
                header('Location: /lecturer/manage_slots.php');
                exit;
            }
        }
    }
}

$editSlot = null;
if (isset($_GET['edit'])) {
    $editSlot = fetch_own_slot($pdo, (int) $_GET['edit'], $user['id']);
    if ($editSlot && $editSlot['status'] !== 'available') {
        $editSlot = null;
    }
}

$slots = list_lecturer_slots($user['id']);

$pageTitle = 'Manage Consultation Slots';
require __DIR__ . '/../partials/header.php';
?>

<h1>Manage Consultation Slots</h1>
<p class="page-subtitle">Create, edit, or remove the times you're available for consultation.</p>

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
  <form method="post" action="/lecturer/manage_slots.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="<?= $editSlot ? 'update' : 'create' ?>">
    <?php if ($editSlot): ?>
      <input type="hidden" name="slot_id" value="<?= (int) $editSlot['id'] ?>">
    <?php endif; ?>

    <label for="slot_date">Date</label>
    <input type="date" id="slot_date" name="slot_date" min="<?= date('Y-m-d') ?>"
           value="<?= e($editSlot['slot_date'] ?? '') ?>" required>

    <label for="start_time">Start time</label>
    <input type="time" id="start_time" name="start_time" value="<?= e(substr($editSlot['start_time'] ?? '', 0, 5)) ?>" required>

    <label for="end_time">End time</label>
    <input type="time" id="end_time" name="end_time" value="<?= e(substr($editSlot['end_time'] ?? '', 0, 5)) ?>" required>

    <button type="submit" class="btn"><?= $editSlot ? 'Save Changes' : 'Create Slot' ?></button>
    <?php if ($editSlot): ?>
      <a class="btn btn-secondary" href="/lecturer/manage_slots.php">Cancel</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>Your Slots</h2>
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
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($slots as $slot): ?>
            <tr>
              <td data-label="Date"><?= e(date('D, d M Y', strtotime($slot['slot_date']))) ?></td>
              <td data-label="Time"><?= e(date('H:i', strtotime($slot['start_time']))) ?>&ndash;<?= e(date('H:i', strtotime($slot['end_time']))) ?></td>
              <td data-label="Status">
                <?php if ($slot['status'] === 'available'): ?>
                  <span class="badge badge-available">Available</span>
                <?php else: ?>
                  <span class="badge badge-full">Booked</span>
                <?php endif; ?>
              </td>
              <td data-label="">
                <?php if ($slot['status'] === 'available'): ?>
                  <a class="btn btn-sm btn-secondary" href="/lecturer/manage_slots.php?edit=<?= (int) $slot['id'] ?>">Edit</a>
                  <form method="post" action="/lecturer/manage_slots.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                <?php else: ?>
                  <span class="muted">Cannot edit/remove</span>
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
