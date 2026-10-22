
<?php
require_once __DIR__ . '/../../src/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = current_user();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /login_staff.php');
    exit;
}

$pdo = get_pdo();

$staffRoles = [
    'lecturer',
    'academic_advisor',
    'department_head',
    'counselor',
    'university_staff'
];

$roleLabels = [
    'lecturer' => 'Lecturer',
    'academic_advisor' => 'Academic Advisor',
    'department_head' => 'Department Head',
    'counselor' => 'Counselor',
    'university_staff' => 'University Staff'
];

if (empty($_SESSION['admin_staff_csrf'])) {
    $_SESSION['admin_staff_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_staff_csrf'];
$message = '';
$error = '';

function escape_output($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function is_valid_staff_role(string $role, array $roles): bool
{
    return in_array($role, $roles, true);
}

// Handle form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $fullName = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? '';
                $department = trim($_POST['department'] ?? '');
               if ($role === 'counselor') {
                $department = 'Counseling Services';
                } elseif ($role === 'university_staff') {
                 $department = 'University Services';
                }
                $service = trim($_POST['service'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (
                    $fullName === '' ||
                    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                    ($role !== 'counselor' &&
                     $role !== 'university_staff' &&
                     $department === '') ||
                    !is_valid_staff_role($role, $staffRoles) ||
                    !is_string($password) ||
                    strlen($password) < 8
                ) {
                    throw new RuntimeException(
                        'Enter a valid name, email, department and role. ' .
                        'The password must contain at least 8 characters.'
                    );
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO users
                    (
                        full_name, email, password_hash, role, course,
                        department, is_active, service, description
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)'
                );

                $stmt->execute([
                    $fullName,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role,
                    'N/A',
                    $department,
                    $service !== '' ? $service : null,
                    $description !== '' ? $description : null
                ]);

                $message = 'Staff account created successfully.';
            } elseif ($action === 'update') {
                $id = filter_var(
                    $_POST['staff_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                $fullName = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? '';
                $department = trim($_POST['department'] ?? '');
                if ($role === 'counselor') {
                $department = 'Counseling Services';
                } elseif ($role === 'university_staff') {
                $department = 'University Services';
                }
                $service = trim($_POST['service'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (!$id || $id < 1) {
                    throw new RuntimeException('Invalid staff account.');
                }

                $check = $pdo->prepare(
                    'SELECT id FROM users
                     WHERE id = ? AND role IN (?, ?, ?, ?, ?)'
                );
                $check->execute(array_merge([$id], $staffRoles));

                if (!$check->fetch()) {
                    throw new RuntimeException('Staff account not found.');
                }

                if (
                    $fullName === '' ||
                    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                    ($role !== 'counselor' &&
                     $role !== 'university_staff' &&
                    $department === '') ||
                    !is_valid_staff_role($role, $staffRoles) ||
                    !is_string($password)
                ) {
                    throw new RuntimeException(
                        'Enter a valid name, email, department and role.'
                    );
                }

                if ($password !== '' && strlen($password) < 8) {
                    throw new RuntimeException(
                        'A new password must contain at least 8 characters.'
                    );
                }

                if ($password !== '') {
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET full_name = ?, email = ?, password_hash = ?,
                             role = ?, department = ?, service = ?,
                             description = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $fullName,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $role,
                        $department,
                        $service !== '' ? $service : null,
                        $description !== '' ? $description : null,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET full_name = ?, email = ?, role = ?,
                             department = ?, service = ?, description = ?
                         WHERE id = ?'
                    );

                    $stmt->execute([
                        $fullName,
                        $email,
                        $role,
                        $department,
                        $service !== '' ? $service : null,
                        $description !== '' ? $description : null,
                        $id
                    ]);
                }

                $message = 'Staff account updated successfully.';
            } elseif ($action === 'toggle_status') {
                $id = filter_var(
                    $_POST['staff_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$id || $id < 1) {
                    throw new RuntimeException('Invalid staff account.');
                }

                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET is_active = IF(is_active = 1, 0, 1)
                     WHERE id = ? AND role IN (?, ?, ?, ?, ?)'
                );

                $stmt->execute(array_merge([$id], $staffRoles));

                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException(
                        'Staff account not found or its status was unchanged.'
                    );
                }

                $message = 'Staff account status updated.';
            } else {
                throw new RuntimeException('Invalid action.');
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'That email address is already in use.';
            } else {
                error_log($e->getMessage());
                $error = 'A database error occurred. Check the server error log.';
            }
        }
    }
}

// Load the staff account selected for editing.
$editingStaff = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId) {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, role, department, service,
                description, is_active
         FROM users
         WHERE id = ? AND role IN (?, ?, ?, ?, ?)'
    );
    $stmt->execute(array_merge([$editId], $staffRoles));
    $editingStaff = $stmt->fetch();

    if (!$editingStaff) {
        $error = 'The selected staff account was not found.';
    }
}

// Load staff accounts without exposing their password hashes.
$placeholders = implode(',', array_fill(0, count($staffRoles), '?'));

$stmt = $pdo->prepare(
    "SELECT id, full_name, email, role, department, service,
            description, is_active
     FROM users
     WHERE role IN ($placeholders)
     ORDER BY full_name"
);
$stmt->execute($staffRoles);
$staffMembers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff | Student Consultation System</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 25px;
            background: #f4f6f9;
            color: #222;
        }

        .container {
            max-width: 1200px;
            margin: auto;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            color: #173b65;
            text-decoration: none;
            margin-right: 15px;
        }

        .panel {
            background: white;
            padding: 24px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px #00000012;
        }

        h1, h2 {
            margin-top: 0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccd2da;
            border-radius: 5px;
            font: inherit;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button, .button {
            display: inline-block;
            padding: 10px 15px;
            border: 0;
            border-radius: 5px;
            background: #173b65;
            color: white;
            cursor: pointer;
            text-decoration: none;
            font: inherit;
        }

        .secondary {
            background: #65717e;
        }

        .danger {
            background: #a62d36;
        }

        .success-message {
            padding: 12px;
            margin-bottom: 18px;
            background: #e5f6e9;
            color: #205c31;
            border-radius: 5px;
        }

        .error-message {
            padding: 12px;
            margin-bottom: 18px;
            background: #fde8e8;
            color: #842029;
            border-radius: 5px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e4e7eb;
            vertical-align: top;
        }

        th {
            background: #edf1f5;
        }

        .status {
            font-weight: bold;
        }

        .active {
            color: #218044;
        }

        .inactive {
            color: #a62d36;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .actions form {
            margin: 0;
        }

        .small-button {
            padding: 7px 10px;
            font-size: 14px;
        }

        @media (max-width: 650px) {
            body {
                padding: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: auto;
            }

            .panel {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="top-links">
        <a href="/admin/dashboard.php">← Admin Dashboard</a>
        <a href="/index.php">Home</a>
    </div>

    <h1>Staff Management</h1>
    <p>Create staff accounts and manage their profiles and access.</p>

    <?php if ($message !== ''): ?>
        <div class="success-message">
            <?= escape_output($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error-message">
            <?= escape_output($error) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>
            <?= $editingStaff ? 'Edit Staff Account' : 'Create Staff Account' ?>
        </h2>

        <form method="POST" action="/admin/staff.php<?= $editingStaff ? '?edit=' . (int) $editingStaff['id'] : '' ?>">
            <input type="hidden" name="csrf_token"
                   value="<?= escape_output($csrfToken) ?>">

            <input type="hidden" name="action"
                   value="<?= $editingStaff ? 'update' : 'create' ?>">

            <?php if ($editingStaff): ?>
                <input type="hidden" name="staff_id"
                       value="<?= (int) $editingStaff['id'] ?>">
            <?php endif; ?>

            <div class="form-grid">
                <div class="field">
                    <label for="full_name">Full name *</label>
                    <input id="full_name" name="full_name" required
                           value="<?= escape_output($editingStaff['full_name'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="email">Email address *</label>
                    <input id="email" name="email" type="email" required
                           value="<?= escape_output($editingStaff['email'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="role">Staff role *</label>
                    <select id="role" name="role" required>
                        <option value="">Select a role</option>

                        <?php foreach ($roleLabels as $value => $label): ?>
                            <option value="<?= escape_output($value) ?>"
                                <?= ($editingStaff['role'] ?? '') === $value ? 'selected' : '' ?>>
                                <?= escape_output($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field" id="departmentField">
                    <label for="department">Department *</label>
                        <input id="department" name="department" required
                        placeholder="e.g. Law School"
                        value="<?= escape_output($editingStaff['department'] ?? '') ?>">
                </div>

                <div class="field">
                <label for="service">Service <span id="serviceRequiredMark" hidden>*</span></label>
                <input id="service" name="service"
                placeholder="e.g. ICT Services, Library"
                value="<?= escape_output($editingStaff['service'] ?? '') ?>">
                <small id="serviceHelp">Optional for this role.</small>
                </div>
                <div class="field">
                    <label for="password">
                        <?= $editingStaff ? 'New password (optional)' : 'Initial password *' ?>
                    </label>
                    <input id="password" name="password" type="password"
                           minlength="8" autocomplete="new-password"
                           <?= $editingStaff ? '' : 'required' ?>>
                    <?php if ($editingStaff): ?>
                        <small>Leave blank to keep the current password.</small>
                    <?php else: ?>
                        <small>Use at least 8 characters.</small>
                    <?php endif; ?>
                </div>

                <div class="field full">
                    <label for="description">Short profile description</label>
                    <textarea id="description" name="description"
                              placeholder="Briefly describe the staff member's service or role."><?= escape_output($editingStaff['description'] ?? '') ?></textarea>
                </div>
            </div>

            <p>
                <button type="submit">
                    <?= $editingStaff ? 'Save Changes' : 'Create Staff Account' ?>
                </button>

                <?php if ($editingStaff): ?>
                    <a class="button secondary" href="/admin/staff.php">Cancel</a>
                <?php endif; ?>
            </p>
        </form>
    </section>

    <section class="panel">
        <h2>Existing Staff Accounts (<?= count($staffMembers) ?>)</h2>

        <?php if (!$staffMembers): ?>
            <p>No staff accounts have been created yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Name and Email</th>
                        <th>Role</th>
                        <th>Department / Service</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($staffMembers as $staff): ?>
                        <tr>
                            <td>
                                <strong><?= escape_output($staff['full_name']) ?></strong>
                                <br>
                                <?= escape_output($staff['email']) ?>
                            </td>

                            <td>
                                <?= escape_output($roleLabels[$staff['role']] ?? $staff['role']) ?>
                            </td>

                            <td>
                                <?= escape_output($staff['department']) ?>

                                <?php if (!empty($staff['service'])): ?>
                                    <br>
                                    <small><?= escape_output($staff['service']) ?></small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ((int) $staff['is_active'] === 1): ?>
                                    <span class="status active">Active</span>
                                <?php else: ?>
                                    <span class="status inactive">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="actions">
                                    <a class="button small-button"
                                       href="/admin/staff.php?edit=<?= (int) $staff['id'] ?>">
                                        Edit
                                    </a>

                                    <form method="POST" action="/admin/staff.php">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= escape_output($csrfToken) ?>">
                                        <input type="hidden" name="action"
                                               value="toggle_status">
                                        <input type="hidden" name="staff_id"
                                               value="<?= (int) $staff['id'] ?>">

                                        <button class="small-button <?= (int) $staff['is_active'] === 1 ? 'danger' : 'secondary' ?>"
                                                type="submit">
                                            <?= (int) $staff['is_active'] === 1 ? 'Deactivate' : 'Reactivate' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

</div>
<script>

document.addEventListener('DOMContentLoaded', function () {
    const roleSelect = document.getElementById('role');
    const departmentField = document.getElementById('departmentField');
    const departmentInput = document.getElementById('department');
    const serviceInput = document.getElementById('service');
    const serviceRequiredMark = document.getElementById('serviceRequiredMark');
    const serviceHelp = document.getElementById('serviceHelp');

    function updateDepartmentField() {
        const role = roleSelect.value;

        // Counselor: hide department and use a fixed value.
        if (role === 'counselor') {
            departmentField.hidden = true;
            departmentInput.required = false;
            departmentInput.value = 'Counseling Services';

        // University staff: hide department and require a service.
        } else if (role === 'university_staff') {
            departmentField.hidden = true;
            departmentInput.required = false;
            departmentInput.value = 'University Services';

        // Other staff roles must provide a department.
        } else {
            departmentField.hidden = false;
            departmentInput.required = true;

            // Clear the automatic values when switching roles.
            if (
                departmentInput.value === 'Counseling Services' ||
                departmentInput.value === 'University Services'
            ) {
                departmentInput.value = '';
            }
        }

        // Service is mandatory only for university staff.
        const isUniversityStaff = role === 'university_staff';

        serviceInput.required = isUniversityStaff;
        serviceRequiredMark.hidden = !isUniversityStaff;
        serviceHelp.textContent = isUniversityStaff
         ? 'Required: enter the service this staff member provides.'
        : 'Optional for this role.';
        }

    // Update the fields whenever the role changes.
    roleSelect.addEventListener('change', updateDepartmentField);

    // Set the correct fields when the page first loads,
    // including when editing an existing staff account.
    updateDepartmentField();
});

</script>
</body>
</html>
