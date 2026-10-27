
<?php

require_once __DIR__ . '/partials/bootstrap.php';

$user = current_user();

if ($user !== null) {
    if ($user['role'] === 'student') {
        header('Location: /student/dashboard.php');
        exit;
    }

    $staffRoles = [
        'lecturer',
        'academic_advisor',
        'department_head',
        'counselor',
        'university_staff'
    ];

    if (in_array($user['role'], $staffRoles, true)) {
        header('Location: /staff/dashboard.php');
        exit;
    }

    if ($user['role'] === 'admin') {
        header('Location: /admin/dashboard.php');
        exit;
    }
}

$pageTitle = 'Welcome';

require __DIR__ . '/partials/header.php';
?>

<main>
    <section class="hero">
        <h1>Book a consultation with the right university staff member</h1>

        <p class="page-subtitle">
            Browse academic and university support services, check available
            times, and book a consultation in a few clicks.
        </p>
    </section>

    <section class="landing-roles" aria-label="Choose your account type">

        <article class="card">
            <span class="role-icon" aria-hidden="true">
                <svg
                    width="26"
                    height="26"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Z"
                        stroke="currentColor"
                        stroke-width="1.8"
                    />
                    <path
                        d="M4 20.5c1.4-3.6 4.5-5.5 8-5.5s6.6 1.9 8 5.5"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </span>

            <h2>Students</h2>

            <p class="muted">
                Browse lecturers, academic advisors, counselors, department
                heads, and other university staff. Check availability and
                book a consultation.
            </p>

            <a class="btn" href="/login_student.php">
                Student Login
            </a>

            <p class="muted" style="margin-top: 0.9rem;">
                New here?
                <a href="/register.php">Create an account</a>
            </p>
        </article>

        <article class="card">
            <span class="role-icon lecturer" aria-hidden="true">
                <svg
                    width="26"
                    height="26"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M4 19.5V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v13.5L12 17l-8 2.5Z"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M8 8h8M8 11.5h5"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                    />
                </svg>
            </span>

            <h2>Academic &amp; University Staff</h2>

            <p class="muted">
                Manage consultation slots, review student bookings, and
                help students access the support they need.
            </p>

            <a class="btn" href="/login_staff.php">
                Staff Login
            </a>

            <p class="muted" style="margin-top: 0.9rem;">
                Includes lecturers, academic advisors, department heads,
                counselors, and other university staff.
            </p>
        </article>

    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
