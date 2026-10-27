
<?php

require_once __DIR__ . '/../partials/bootstrap.php';

// Only administrators may access this dashboard.
$user = current_user();

if (!$user || $user['role'] !== 'admin') {
    header('Location: /index.php');
    exit;
}

$pdo = get_pdo();

// Count all consultation-provider staff accounts, including inactive ones.
$staffCount = (int) $pdo->query("
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

// Count active consultation-provider staff accounts.
$activeStaffCount = (int) $pdo->query("
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

// Count all student accounts.
$studentCount = (int) $pdo->query("
    SELECT COUNT(*)
    FROM users
    WHERE role = 'student'
")->fetchColumn();

$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($pageTitle) ?></title>

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

        .actions a:hover {
            background: #24558a;
        }

        .actions a:focus-visible {
            outline: 3px solid #7aa7d9;
            outline-offset: 3px;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <main class="container">
        <h1>Admin Dashboard</h1>

        <p>
            Welcome,
            <?= e($user['full_name']) ?>.
        </p>

        <div class="cards">
            <section class="card">
                <h2>Total Staff Accounts</h2>
                <div class="number">
                    <?= $staffCount ?>
                </div>
            </section>

            <section class="card">
                <h2>Active Staff Accounts</h2>
                <div class="number">
                    <?= $activeStaffCount ?>
                </div>
            </section>

            <section class="card">
                <h2>Student Accounts</h2>
                <div class="number">
                    <?= $studentCount ?>
                </div>
            </section>
        </div>

        <nav class="actions" aria-label="Administrator navigation">
            <a href="/admin/staff.php">Manage Staff</a>
            <a href="/index.php">Home</a>
            <a href="/logout.php">Logout</a>
        </nav>
    </main>
</body>
</html>
