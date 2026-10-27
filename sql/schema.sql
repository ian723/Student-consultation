
-- ============================================================
-- Kabarak Student Consultation Booking System
-- Enhanced Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS `kabarak_consultation`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `kabarak_consultation`;

-- ============================================================
-- TABLE 1: USERS
-- Supports students, lecturers, and other university staff.
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(150) NOT NULL,

    email VARCHAR(190) NOT NULL UNIQUE,

    password_hash VARCHAR(255) NOT NULL,

    role ENUM(
        'student',
        'lecturer',
        'academic_advisor',
        'department_head',
        'counselor',
        'university_staff',
        'admin'
    ) NOT NULL,

    course VARCHAR(150) DEFAULT NULL,

    department VARCHAR(150) DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    is_active TINYINT(1) NOT NULL DEFAULT 1,

    service VARCHAR(100) DEFAULT NULL,

    description TEXT DEFAULT NULL

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 2: CONSULTATION SLOTS
-- Stores appointment slots created by consultation providers.
-- staff_id references the corresponding user's ID.
-- ============================================================

CREATE TABLE IF NOT EXISTS consultation_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,

    staff_id INT NOT NULL,

    slot_date DATE NOT NULL,

    start_time TIME NOT NULL,

    end_time TIME NOT NULL,

    status ENUM(
        'available',
        'booked'
    ) NOT NULL DEFAULT 'available',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_slots_staff_date (staff_id, slot_date),

    CONSTRAINT fk_slots_staff
        FOREIGN KEY (staff_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT chk_slot_time
        CHECK (end_time > start_time)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 3: BOOKINGS
-- Stores student appointments with consultation providers.
--
-- A slot can have only one active booking.
-- Cancelled bookings do not prevent the slot from being booked
-- again because active_slot_id becomes NULL.
-- ============================================================

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,

    slot_id INT NOT NULL,

    student_id INT NOT NULL,

    status ENUM(
        'active',
        'cancelled'
    ) NOT NULL DEFAULT 'active',

    booked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    cancelled_at TIMESTAMP NULL DEFAULT NULL,

    active_slot_id INT
        GENERATED ALWAYS AS (
            CASE
                WHEN status = 'active' THEN slot_id
                ELSE NULL
            END
        ) STORED,

    UNIQUE KEY uq_active_slot (active_slot_id),

    INDEX idx_bookings_student (student_id),

    INDEX idx_bookings_slot (slot_id),

    CONSTRAINT fk_bookings_slot
        FOREIGN KEY (slot_id)
        REFERENCES consultation_slots(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_bookings_student
        FOREIGN KEY (student_id)
        REFERENCES users(id)
        ON DELETE CASCADE

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- END OF DATABASE SCHEMA
-- ============================================================
