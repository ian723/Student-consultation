-- Kabarak Student Consultation Booking System
-- Database schema

CREATE DATABASE IF NOT EXISTS kabarak_consultation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE kabarak_consultation;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student', 'lecturer') NOT NULL,
  course VARCHAR(150) NULL,
  department VARCHAR(150) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consultation_slots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lecturer_id INT NOT NULL,
  slot_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  status ENUM('available', 'booked') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_slots_lecturer FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_slot_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

CREATE INDEX idx_slots_lecturer_date ON consultation_slots (lecturer_id, slot_date);

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slot_id INT NOT NULL,
  student_id INT NOT NULL,
  status ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
  booked_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  cancelled_at TIMESTAMP NULL,
  CONSTRAINT fk_bookings_slot FOREIGN KEY (slot_id) REFERENCES consultation_slots(id) ON DELETE CASCADE,
  CONSTRAINT fk_bookings_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Only one active booking per slot is allowed. A cancelled booking frees the slot
-- up for a new row, so the uniqueness is enforced via a generated column that
-- collapses to the same value only while the booking is active.
ALTER TABLE bookings
  ADD COLUMN active_slot_id INT AS (CASE WHEN status = 'active' THEN slot_id ELSE NULL END) STORED,
  ADD UNIQUE KEY uq_active_slot (active_slot_id);

CREATE INDEX idx_bookings_student ON bookings (student_id);
CREATE INDEX idx_bookings_slot ON bookings (slot_id);
