
<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$user = require_role('student');

$providers = list_providers_with_availability();

$roleLabels = [
    'lecturer' => 'Lecturer',
    'academic_advisor' => 'Academic Advisor',
    'department_head' => 'Department Head',
    'counselor' => 'Counselor',
    'university_staff' => 'University Staff'
];

// Get unique departments for the department filter.
$departments = [];

foreach ($providers as $provider) {
    $department = trim($provider['department'] ?? '');

    if ($department !== '') {
        $departments[] = $department;
    }
}

$departments = array_values(array_unique($departments));
sort($departments, SORT_NATURAL | SORT_FLAG_CASE);

// Read the selected filters.
$selectedRole = $_GET['role'] ?? '';
$selectedDepartment = $_GET['department'] ?? '';

// Only accept known provider roles.
if ($selectedRole !== '' && !array_key_exists($selectedRole, $roleLabels)) {
    $selectedRole = '';
}

// Filter the providers.
$filteredProviders = array_filter(
    $providers,
    function ($provider) use ($selectedRole, $selectedDepartment) {
        $roleMatches = $selectedRole === ''
            || $provider['role'] === $selectedRole;

        $departmentMatches = $selectedDepartment === ''
            || ($provider['department'] ?? '') === $selectedDepartment;

        return $roleMatches && $departmentMatches;
    }
);

$pageTitle = 'Browse Providers';
require __DIR__ . '/../partials/header.php';
?>

<h1>Browse Consultation Providers</h1>

<p class="page-subtitle">
    Find the right academic or university staff member, check their
    availability, and view their consultation slots.
</p>

<?php if (!$providers): ?>
  <div class="card">
    <div class="empty-state">
      <div class="empty-state-icon">&#128101;</div>
      <p>No consultation providers are registered yet.</p>
    </div>
  </div>

<?php else: ?>

  <div class="card">
    <h2>Filter Providers</h2>

    <form method="get" action="/student/lecturers.php">
      <div class="form-group">
        <label for="role">Provider Role</label>

        <select id="role" name="role">
          <option value="">All roles</option>

          <?php foreach ($roleLabels as $role => $label): ?>
            <option
              value="<?= e($role) ?>"
              <?= $selectedRole === $role ? 'selected' : '' ?>
            >
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="department">Department</label>

        <select id="department" name="department">
          <option value="">All departments</option>

          <?php foreach ($departments as $department): ?>
            <option
              value="<?= e($department) ?>"
              <?= $selectedDepartment === $department ? 'selected' : '' ?>
            >
              <?= e($department) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="actions-row">
        <button type="submit" class="btn">Apply Filters</button>
        <a class="btn btn-secondary" href="/student/lecturers.php">
          Clear Filters
        </a>
      </div>
    </form>
  </div>

  <?php if (!$filteredProviders): ?>
    <div class="card">
      <div class="empty-state">
        <p>No providers match your selected filters.</p>
        <a class="btn btn-secondary" href="/student/lecturers.php">
          View All Providers
        </a>
      </div>
    </div>

  <?php else: ?>
    <div class="card">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Role</th>
              <th>Department</th>
              <th>Availability</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($filteredProviders as $provider): ?>
              <tr>
                <td data-label="Name">
                  <?= e($provider['full_name']) ?>
                </td>

                <td data-label="Role">
                  <?= e($roleLabels[$provider['role']] ?? 'Staff') ?>
                </td>

                <td data-label="Department">
                  <?= e($provider['department'] ?? '') ?>
                </td>

                <td data-label="Availability">
                  <span class="badge badge-<?= e($provider['status']) ?>">
                    <?= e($provider['status_label']) ?>
                  </span>
                </td>

                <td data-label="">
                  <a
                    class="btn btn-sm"
                    href="/student/lecturer_slots.php?lecturer_id=<?= (int) $provider['id'] ?>"
                  >
                    View Slots
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
