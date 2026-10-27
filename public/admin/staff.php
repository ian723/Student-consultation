
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

// Only administrators may manage staff accounts.
$user = require_role('admin');

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

// Create a CSRF token for this page.
if (empty($_SESSION['admin_staff_csrf'])) {
    $_SESSION['admin_staff_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['admin_staff_csrf'];
$message = '';
$error = '';

/**
 * Normalize department according to the selected staff role.
 */
function normalize_staff_department(string $role, string $department): string
{
    if ($role === 'counselor') {
        return 'Counseling Services';
    }

    if ($role === 'university_staff') {
        return 'University Services';
    }

    return trim($department);
}

/**
 * Validate the staff account fields.
 */
function validate_staff_fields(
    string $fullName,
    string $email,
    string $role,
    string $department,
    string $service,
    string $password,
    bool $creating,
    array $allowedRoles
): void {
    if ($fullName === '' || mb_strlen($fullName) > 150) {
        throw new RuntimeException(
            'Enter a name containing no more than 150 characters.'
        );
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190
    ) {
        throw new RuntimeException(
            'Enter a valid email address containing no more than 190 characters.'
        );
    }

    if (!in_array($role, $allowedRoles, true)) {
        throw new RuntimeException('Select a valid staff role.');
    }

    if (
        $role !== 'counselor'
        && $role !== 'university_staff'
        && $department === ''
    ) {
        throw new RuntimeException(
            'A department is required for the selected staff role.'
        );
    }

    if ($role === 'university_staff' && $service === '') {
        throw new RuntimeException(
            'Enter the service provided by this university staff member.'
        );
    }

    if ($password !== '' && strlen($password) < 8) {
        throw new RuntimeException(
            'Passwords must contain at least 8 characters.'
        );
    }

    if ($creating && $password === '') {
        throw new RuntimeException(
            'An initial password is required when creating a staff account.'
        );
    }
}

// Handle form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken)
        || !hash_equals($csrfToken, $submittedToken)
    ) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'create') {
                $fullName = trim($_POST['full_name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? '';
                $department = trim($_POST['department'] ?? '');
                $service = trim($_POST['service'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (!is_string($password)) {
                    throw new RuntimeException('Invalid password.');
                }

                $department = normalize_staff_department(
                    $role,
                    $department
                );

                validate_staff_fields(
                    $fullName,
                    $email,
                    $role,
                    $department,
                    $service,
                    $password,
                    true,
                    $staffRoles
                );

                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        full_name,
                        email,
                        password_hash,
                        role,
                        course,
                        department,
                        is_active,
                        service,
                        description
                    )
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
                ");

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

                if (!$id || $id < 1) {
                    throw new RuntimeException('Invalid staff account.');
                }

                // Confirm the account is a consultation-provider staff account.
                $check = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE id = ?
                      AND role IN (?, ?, ?, ?, ?)
                ");

                $check->execute(array_merge([$id], $staffRoles));

                if (!$check->fetch()) {
                    throw new RuntimeException('Staff account not found.');
                }

                $fullName = trim($_POST['full_name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? '';
                $department = trim($_POST['department'] ?? '');
                $service = trim($_POST['service'] ?? '');
                $description = trim($_POST['description'] ?? '');

                if (!is_string($password)) {
                    throw new RuntimeException('Invalid password.');
                }

                $department = normalize_staff_department(
                    $role,
                    $department
                );

                validate_staff_fields(
                    $fullName,
                    $email,
                    $role,
                    $department,
                    $service,
                    $password,
                    false,
                    $staffRoles
                );

                if ($password !== '') {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?,
                            email = ?,
                            password_hash = ?,
                            role = ?,
                            department = ?,
                            service = ?,
                            description = ?
                        WHERE id = ?
                          AND role IN (?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $fullName,
                        $email,
                        password_hash($password, PASSWORD_DEFAULT),
                        $role,
                        $department,
                        $service !== '' ? $service : null,
                        $description !== '' ? $description : null,
                        $id,
                        ...$staffRoles
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET full_name = ?,
                            email = ?,
                            role = ?,
                            department = ?,
                            service = ?,
                            description = ?
                        WHERE id = ?
                          AND role IN (?, ?, ?, ?, ?)
                    ");

                    $stmt->execute([
                        $fullName,
                        $email,
                        $role,
                        $department,
                        $service !== '' ? $service : null,
                        $description !== '' ? $description : null,
                        $id,
                        ...$staffRoles
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

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET is_active = IF(is_active = 1, 0, 1)
                    WHERE id = ?
                      AND role IN (?, ?, ?, ?, ?)
                ");

                $stmt->execute(array_merge([$id], $staffRoles));

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Staff account not found or its status could not be changed.'
                    );
                }

                $message = 'Staff account status updated successfully.';

            } else {
                throw new RuntimeException('Invalid action.');
            }

        } catch (RuntimeException $e) {
            $error = $e->getMessage();

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'That email address is already in use.';
            } else {
                error_log('Staff management database error: ' . $e->getMessage());
                $error = 'A database error occurred. Check the server error log.';
            }
        }
    }
}

// Load the selected staff account for editing.
$editingStaff = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

if ($editId) {
    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            email,
            role,
            department,
            service,
            description,
            is_active
        FROM users
        WHERE id = ?
          AND role IN (?, ?, ?, ?, ?)
    ");

    $stmt->execute(array_merge([$editId], $staffRoles));
    $editingStaff = $stmt->fetch();

    if (!$editingStaff) {
        $error = 'The selected staff account was not found.';
    }
}

// Load staff accounts without retrieving password hashes.
$placeholders = implode(',', array_fill(0, count($staffRoles), '?'));

$stmt = $pdo->prepare("
    SELECT
        id,
        full_name,
        email,
        role,
        department,
        service,
        description,
        is_active
    FROM users
    WHERE role IN ($placeholders)
    ORDER BY full_name
");

$stmt->execute($staffRoles);
$staffMembers = $stmt->fetchAll();

$pageTitle = 'Manage Staff | Student Consultation System';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>

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

        .success-message, .error-message {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 5px;
        }

        .success-message {
            background: #e5f6e9;
            color: #205c31;
        }

        .error-message {
            background: #fde8e8;
            color: #842029;
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
<main class="container">

    <nav class="top-links" aria-label="Administrator navigation">
        <a href="/admin/dashboard.php">← Admin Dashboard</a>
        <a href="/index.php">Home</a>
        <a href="/logout.php">Logout</a>
    </nav>

    <h1>Staff Management</h1>
    <p>Create staff accounts and manage their profiles and access.</p>

    <?php if ($message !== ''): ?>
        <div class="success-message" role="status">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error-message" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>
            <?= $editingStaff ? 'Edit Staff Account' : 'Create Staff Account' ?>
        </h2>

        <form
            method="post"
            action="/admin/staff.php<?= $editingStaff ? '?edit=' . (int) $editingStaff['id'] : '' ?>"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="<?= $editingStaff ? 'update' : 'create' ?>"
            >

            <?php if ($editingStaff): ?>
                <input
                    type="hidden"
                    name="staff_id"
                    value="<?= (int) $editingStaff['id'] ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div class="field">
                    <label for="full_name">Full name *</label>
                    <input
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        required
                        value="<?= e($editingStaff['full_name'] ?? '') ?>"
                    >
                </div>

                <div class="field">
                    <label for="email">Email address *</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="190"
                        required
                        value="<?= e($editingStaff['email'] ?? '') ?>"
                    >
                </div>

                <div class="field">
                    <label for="role">Staff role *</label>
                    <select id="role" name="role" required>
                        <option value="">Select a role</option>

                        <?php foreach ($roleLabels as $value => $label): ?>
                            <option
                                value="<?= e($value) ?>"
                                <?= ($editingStaff['role'] ?? '') === $value ? 'selected' : '' ?>
                            >
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field" id="departmentField">
                    <label for="department">Department *</label>
                    <input
                        id="department"
                        name="department"
                        placeholder="e.g. Law School"
                        value="<?= e($editingStaff['department'] ?? '') ?>"
                    >
                </div>

                <div class="field">
                    <label for="service">
                        Service
                        <span id="serviceRequiredMark" hidden>*</span>
                    </label>

                    <input
                        id="service"
                        name="service"
                        maxlength="100"
                        placeholder="e.g. ICT Services, Library"
                        value="<?= e($editingStaff['service'] ?? '') ?>"
                    >

                    <small id="serviceHelp">
                        Optional for this role.
                    </small>
                </div>

                <div class="field">
                    <label for="password">
                        <?= $editingStaff ? 'New password (optional)' : 'Initial password *' ?>
                    </label>

                    <input
                        id="password"
                        name="password"
                        type="password"
                        minlength="8"
                        autocomplete="new-password"
                        <?= $editingStaff ? '' : 'required' ?>
                    >

                    <?php if ($editingStaff): ?>
                        <small>Leave blank to keep the current password.</small>
                    <?php else: ?>
                        <small>Use at least 8 characters.</small>
                    <?php endif; ?>
                </div>

                <div class="field full">
                    <label for="description">Short profile description</label>
                    <textarea
                        id="description"
                        name="description"
                        placeholder="Briefly describe the staff member's service or role."
                    ><?= e($editingStaff['description'] ?? '') ?></textarea>
                </div>

            </div>

            <p>
                <button type="submit">
                    <?= $editingStaff ? 'Save Changes' : 'Create Staff Account' ?>
                </button>

                <?php if ($editingStaff): ?>
                    <a class="button secondary" href="/admin/staff.php">
                        Cancel
                    </a>
                <?php endif; ?>
            </p>
        </form>
    </section>

    <section class="panel">
        <h2>Existing Staff Accounts (<?= count($staffMembers) ?>)</h2>

        <?php if (empty($staffMembers)): ?>

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
                                <strong><?= e($staff['full_name']) ?></strong>
                                <br>
                                <?= e($staff['email']) ?>
                            </td>

                            <td>
                                <?= e($roleLabels[$staff['role']] ?? $staff['role']) ?>
                            </td>

                            <td>
                                <?= e($staff['department'] ?? '') ?>

                                <?php if (!empty($staff['service'])): ?>
                                    <br>
                                    <small><?= e($staff['service']) ?></small>
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
                                    <a
                                        class="button small-button"
                                        href="/admin/staff.php?edit=<?= (int) $staff['id'] ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="post"
                                        action="/admin/staff.php"
                                        onsubmit="return confirm('Change the access status of this staff account?');"
                                    >
                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($csrfToken) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="toggle_status"
                                        >

                                        <input
                                            type="hidden"
                                            name="staff_id"
                                            value="<?= (int) $staff['id'] ?>"
                                        >

                                        <button
                                            class="small-button <?= (int) $staff['is_active'] === 1 ? 'danger' : 'secondary' ?>"
                                            type="submit"
                                        >
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

</main>

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

        if (role === 'counselor') {
            departmentField.hidden = true;
            departmentInput.required = false;
            departmentInput.value = 'Counseling Services';

        } else if (role === 'university_staff') {
            departmentField.hidden = true;
            departmentInput.required = false;
            departmentInput.value = 'University Services';

        } else {
            departmentField.hidden = false;
            departmentInput.required = true;

            if (
                departmentInput.value === 'Counseling Services'
                || departmentInput.value === 'University Services'
            ) {
                departmentInput.value = '';
            }
        }

        const isUniversityStaff = role === 'university_staff';

        serviceInput.required = isUniversityStaff;
        serviceRequiredMark.hidden = !isUniversityStaff;

        serviceHelp.textContent = isUniversityStaff
            ? 'Required: enter the service this staff member provides.'
            : 'Optional for this role.';
    }

    roleSelect.addEventListener('change', updateDepartmentField);
    updateDepartmentField();
});
</script>

</body>
</html>
