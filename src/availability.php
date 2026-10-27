
<?php

require_once __DIR__ . '/db.php';

const LIMITED_SLOTS_THRESHOLD = 2;

/**
 * Determines availability from the number of future available slots.
 *
 * Returns: available, limited, or full.
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

/**
 * Returns a human-readable availability label.
 */
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
 * Returns the shared SQL condition for slots that have not started yet.
 */
function future_slot_condition(): string
{
    return "(
        cs.slot_date > CURDATE()
        OR (
            cs.slot_date = CURDATE()
            AND cs.start_time > CURTIME()
        )
    )";
}

/**
 * Returns all active lecturers and their future availability.
 *
 * Retained for compatibility with existing lecturer-specific pages.
 */
function list_lecturers_with_availability(): array
{
    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.department,
            COUNT(
                CASE
                    WHEN cs.status = 'available'
                     AND " . future_slot_condition() . "
                    THEN 1
                END
            ) AS available_count
        FROM users u
        LEFT JOIN consultation_slots cs
            ON cs.staff_id = u.id
        WHERE u.role = 'lecturer'
          AND u.is_active = 1
        GROUP BY
            u.id,
            u.full_name,
            u.department
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

/**
 * Returns an active lecturer by ID.
 *
 * Retained for compatibility with existing lecturer-specific pages.
 */
function get_lecturer(int $lecturerId): ?array
{
    $stmt = get_pdo()->prepare("
        SELECT id, full_name, department
        FROM users
        WHERE id = ?
          AND role = 'lecturer'
          AND is_active = 1
    ");

    $stmt->execute([$lecturerId]);

    $lecturer = $stmt->fetch();

    return $lecturer ?: null;
}

/**
 * Returns future consultation slots for a provider.
 * Both available and booked slots are included for display.
 */
function list_future_slots(int $providerId): array
{
    $sql = "
        SELECT
            cs.id,
            cs.staff_id,
            cs.slot_date,
            cs.start_time,
            cs.end_time,
            cs.status
        FROM consultation_slots cs
        JOIN users u ON u.id = cs.staff_id
        WHERE cs.staff_id = ?
          AND u.is_active = 1
          AND u.role IN (
              'lecturer',
              'academic_advisor',
              'department_head',
              'counselor',
              'university_staff'
          )
          AND " . future_slot_condition() . "
        ORDER BY cs.slot_date ASC, cs.start_time ASC
    ";

    $stmt = get_pdo()->prepare($sql);
    $stmt->execute([$providerId]);

    return $stmt->fetchAll();
}

/**
 * Returns all consultation slots belonging to a provider,
 * including past slots, for slot management.
 *
 * Retained under its original function name for compatibility.
 */
function list_lecturer_slots(int $lecturerId): array
{
    $stmt = get_pdo()->prepare("
        SELECT
            id,
            staff_id,
            slot_date,
            start_time,
            end_time,
            status
        FROM consultation_slots
        WHERE staff_id = ?
        ORDER BY slot_date DESC, start_time ASC
    ");

    $stmt->execute([$lecturerId]);

    return $stmt->fetchAll();
}

/**
 * Returns all active consultation providers and their availability.
 * Students and administrators are deliberately excluded.
 */
function list_providers_with_availability(): array
{
    $sql = "
        SELECT
            u.id,
            u.full_name,
            u.department,
            u.role,
            u.service,
            u.description,
            COUNT(
                CASE
                    WHEN cs.status = 'available'
                     AND " . future_slot_condition() . "
                    THEN 1
                END
            ) AS available_count
        FROM users u
        LEFT JOIN consultation_slots cs
            ON cs.staff_id = u.id
        WHERE u.is_active = 1
          AND u.role IN (
              'lecturer',
              'academic_advisor',
              'department_head',
              'counselor',
              'university_staff'
          )
        GROUP BY
            u.id,
            u.full_name,
            u.department,
            u.role,
            u.service,
            u.description
        ORDER BY u.full_name ASC
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

/**
 * Returns an active consultation provider by ID.
 */
function get_provider(int $providerId): ?array
{
    $stmt = get_pdo()->prepare("
        SELECT
            id,
            full_name,
            email,
            department,
            role,
            service,
            description
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
