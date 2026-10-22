
<?php
require_once __DIR__ . '/../../src/auth.php';

$user = current_user();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

$pdo = get_pdo();

$staffCount = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role IN (
        'lecturer',
        'academic_advisor',
        'department_head',
        'counselor',
        'university_staff'
    )
")->fetchColumn();

$activeStaffCount = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE is_active = 1
      AND role IN (
        'lecturer',
        'academic_advisor',
        'department_head',
        'counselor',
        'university_staff'
      )
")->fetchColumn();

$studentCount = $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'student'
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            background: #f4f6f9;
            color: #222;
        }

        .container {
            max-width: 1000px;
            margin: auto;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px #00000012;
        }

        .card h2 {
            margin-top: 0;
            font-size: 16px;
            color: #555;
        }

        .number {
            font-size: 32px;
            font-weight: bold;
        }

        .actions a {
            display: inline-block;
            margin: 8px 8px 8px 0;
            padding: 12px 18px;
            background: #173b65;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>

        <p>
            Welcome,
            <?= htmlspecialchars($user['full_name']) ?>.
        </p>

        <div class="cards">
            <div class="card">
                <h2>Total Staff Accounts</h2>
                <div class="number">
                    <?= (int) $staffCount ?>
                </div>
            </div>

            <div class="card">
                <h2>Active Staff Accounts</h2>
                <div class="number">
                    <?= (int) $activeStaffCount ?>
                </div>
            </div>

            <div class="card">
                <h2>Student Accounts</h2>
                <div class="number">
                    <?= (int) $studentCount ?>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="/admin/staff.php">Manage Staff</a>
            <a href="/index.php">Home</a>
            <a href="/logout.php">Logout</a>
        </div>
    </div>
</body>
</html>
