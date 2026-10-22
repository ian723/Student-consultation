<?php

require_once __DIR__ . '/db.php';

const LIMITED_SLOTS_THRESHOLD = 2; // 1..this many available slots => "Limited Slots"

/**
 * Returns 'available' | 'limited' | 'full' based on how many future,
 * unbooked slots a lecturer currently has.
 */
function availability_status(int $available_count): string
{
    if ($available_count <= 0) {
        return 'full';
    }
    if ($available_count <= LIMITED_SLOTS_THRESHOLD) {
        return 'limited';
    }
    return 'available';
}

function availability_label(string $status): string
{
    return match ($status) {
        'available' => 'Available',
        'limited' => 'Limited Slots',
        'full' => 'Fully Booked',
        default => 'Unknown',
    };
}

/**
 * Returns all lecturers with their full name, department, and computed
 * availability status/label based on future available slots.
 */
function list_lecturers_with_availability(): array
{
    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.department,
            COUNT(CASE WHEN cs.status = 'available' AND cs.slot_date >= CURDATE() THEN 1 END) AS available_count
        FROM users u
        LEFT JOIN consultation_slots cs ON cs.lecturer_id = u.id
        WHERE u.role = 'lecturer'
        GROUP BY u.id, u.full_name, u.department
        ORDER BY u.full_name
    ";

    $rows = get_pdo()->query($sql)->fetchAll();

    foreach ($rows as &$row) {
        $status = availability_status((int) $row['available_count']);
        $row['status'] = $status;
        $row['status_label'] = availability_label($status);
    }

    return $rows;
}

function get_lecturer(int $lecturerId): ?array
{
    $stmt = get_pdo()->prepare("SELECT id, full_name, department FROM users WHERE id = ? AND role = 'lecturer'");
    $stmt->execute([$lecturerId]);
    $lecturer = $stmt->fetch();
    return $lecturer ?: null;
}

/**
 * Returns a lecturer's future consultation slots (available and booked),
 * ordered chronologically, for display to students.
 */
function list_future_slots(int $lecturerId): array
{
    $stmt = get_pdo()->prepare("
        SELECT id, slot_date, start_time, end_time, status
        FROM consultation_slots
        WHERE lecturer_id = ? AND slot_date >= CURDATE()
        ORDER BY slot_date, start_time
    ");
    $stmt->execute([$lecturerId]);
    return $stmt->fetchAll();
}

/**
 * Returns ALL of a lecturer's own slots (past and future) for management.
 */
function list_lecturer_slots(int $lecturerId): array
{
    $stmt = get_pdo()->prepare("
        SELECT id, slot_date, start_time, end_time, status
        FROM consultation_slots
        WHERE lecturer_id = ?
        ORDER BY slot_date DESC, start_time
    ");
    $stmt->execute([$lecturerId]);
    return $stmt->fetchAll();
}

function list_providers_with_availability(): array
{
    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.department,
            u.role,
            COUNT(
                CASE
                    WHEN cs.status = 'available'
                     AND cs.slot_date >= CURDATE()
                    THEN 1
                END
            ) AS available_count
        FROM users u
        LEFT JOIN consultation_slots cs
            ON cs.lecturer_id = u.id
        WHERE u.role IN (
            'lecturer',
            'academic_advisor',
            'department_head',
            'counselor',
            'university_staff'
        )
        AND u.is_active = 1
        GROUP BY
            u.id,
            u.full_name,
            u.department,
            u.role
        ORDER BY u.full_name
    ";

    $rows = get_pdo()->query($sql)->fetchAll();

    foreach ($rows as &$row) {
        $status = availability_status(
            (int) $row['available_count']
        );

        $row['status'] = $status;
        $row['status_label'] = availability_label($status);
    }

    unset($row);

    return $rows;
}


function get_provider(int $providerId): ?array
{
    $stmt = get_pdo()->prepare("
        SELECT id, full_name, department, role
        FROM users
        WHERE id = ?
          AND is_active = 1
          AND role IN (
              'lecturer',
              'academic_advisor',
              'department_head',
              'counselor',
              'university_staff'
          )
    ");

    $stmt->execute([$providerId]);

    $provider = $stmt->fetch();

    return $provider ?: null;
}

